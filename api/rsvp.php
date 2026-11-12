<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$token = trim($_REQUEST['token'] ?? '');
$response = strtolower(trim($_REQUEST['response'] ?? 'accepted'));
$dietary = trim($_REQUEST['dietary_notes'] ?? '');

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token is required']);
    exit;
}

if (!in_array($response, ['accepted', 'declined', 'tentative'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid RSVP response option']);
    exit;
}

$tokenRow = db_fetch_one("
    SELECT qt.*, i.id as invitation_id, i.event_id, i.guest_id, g.full_name,
           e.title as event_title
    FROM qr_tokens qt
    JOIN invitations i ON qt.invitation_id = i.id
    JOIN guests g ON i.guest_id = g.id
    JOIN events e ON i.event_id = e.id
    WHERE qt.token = :tok OR i.reference_code = :tok2
    LIMIT 1
", ['tok' => $token, 'tok2' => $token]);

if (!$tokenRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid pass token']);
    exit;
}

$pdo = get_db();
try {
    $pdo->beginTransaction();

    // Check if existing RSVP
    $existing = db_fetch_one("
        SELECT id, response FROM rsvp_records 
        WHERE invitation_id = :iid 
        ORDER BY id DESC LIMIT 1
    ", ['iid' => $tokenRow['invitation_id']]);

    $prevResponse = $existing ? $existing['response'] : 'pending';
    $now = date('Y-m-d H:i:s');

    if ($existing) {
        // Update the record
        db_update('rsvp_records', [
            'response' => $response,
            'dietary_notes' => $dietary,
            'responded_at' => $now
        ], 'id = :id', ['id' => $existing['id']]);

        // Clean up any stale duplicate rows for this invitation to maintain absolute database integrity
        db_query("DELETE FROM rsvp_records WHERE invitation_id = :iid AND id != :id", [
            'iid' => $tokenRow['invitation_id'],
            'id' => $existing['id']
        ]);
    } else {
        db_insert('rsvp_records', [
            'invitation_id' => $tokenRow['invitation_id'],
            'guest_id' => $tokenRow['guest_id'],
            'event_id' => $tokenRow['event_id'],
            'response' => $response,
            'dietary_notes' => $dietary,
            'responded_at' => $now
        ]);
    }

    // Record Action in Audit Log
    $actionDesc = "Guest {$tokenRow['full_name']} changed RSVP from " . strtoupper($prevResponse) . " to " . strtoupper($response) . " for event: {$tokenRow['event_title']}";
    audit_log(
        'RSVP_CHANGE',
        'UPDATE_RSVP',
        $actionDesc,
        null, // Guest self-service via secure signed pass token
        1
    );

    $pdo->commit();

    $responseLabel = ($response === 'accepted') ? 'Accepted' : (($response === 'declined') ? 'Declined' : 'Tentative');

    echo json_encode([
        'success' => true,
        'message' => 'Your RSVP response has been saved successfully.',
        'response' => $response,
        'response_label' => $responseLabel,
        'previous_response' => $prevResponse,
        'responded_at' => $now,
        'guest_name' => $tokenRow['full_name']
    ]);

} catch (Throwable $t) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("RSVP transaction error: " . $t->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update your RSVP. Please try again.'
    ]);
}
