<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Verification & Administrative Security Gate
$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only logged-in verification staff can reverse check-ins.']);
    exit;
}

$checkinId = (int)($_POST['check_in_id'] ?? 0);
$invitationId = (int)($_POST['invitation_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'A valid justification reason is required to reverse an admission check-in.']);
    exit;
}

// Locate check-in record
$checkin = null;
if ($checkinId > 0) {
    $checkin = db_fetch_one("
        SELECT c.*, g.full_name as guest_name, i.reference_code, gt.name as gate_name, e.title as event_title
        FROM check_ins c
        JOIN guests g ON c.guest_id = g.id
        JOIN invitations i ON c.invitation_id = i.id
        LEFT JOIN gates gt ON c.gate_id = gt.id
        LEFT JOIN events e ON c.event_id = e.id
        WHERE c.id = :cid
        LIMIT 1
    ", ['cid' => $checkinId]);
} elseif ($invitationId > 0) {
    $checkin = db_fetch_one("
        SELECT c.*, g.full_name as guest_name, i.reference_code, gt.name as gate_name, e.title as event_title
        FROM check_ins c
        JOIN guests g ON c.guest_id = g.id
        JOIN invitations i ON c.invitation_id = i.id
        LEFT JOIN gates gt ON c.gate_id = gt.id
        LEFT JOIN events e ON c.event_id = e.id
        WHERE c.invitation_id = :iid
        ORDER BY c.id DESC
        LIMIT 1
    ", ['iid' => $invitationId]);
}

if (!$checkin) {
    echo json_encode(['success' => false, 'message' => 'Active check-in record not found or already reversed.']);
    exit;
}

$pdo = get_db();
try {
    $pdo->beginTransaction();

    $now = date('Y-m-d H:i:s');
    $officerName = $user['name'] ?? 'Authorized Officer';
    $officerId = $user['id'] ?? 1;

    // 1. Audit Log Reversal Record (Preserving complete historical audit trail)
    $auditDesc = sprintf(
        "Officer %s (ID #%d) REVERSED admission for %s (Pass: %s). Original check-in: %s at %s. Reason: %s",
        $officerName,
        $officerId,
        $checkin['guest_name'],
        $checkin['reference_code'],
        $checkin['checked_in_at'],
        $checkin['gate_name'] ?: 'Gate Entrance',
        $reason
    );

    audit_log(
        'CHECKIN_REVERSAL',
        'REVERSE_ADMISSION',
        $auditDesc,
        $officerId,
        $user['organization_id'] ?? 1
    );

    // 2. Remove the check-in record so the gate scanner will accept the pass again
    db_query("DELETE FROM check_ins WHERE id = :id", ['id' => $checkin['id']]);

    // 3. Mark invitation as not checked in
    db_update('invitations', [
        'is_checked_in' => 0
    ], 'id = :id', ['id' => $checkin['invitation_id']]);

    // 4. Decrement scan count on qr token if applicable
    db_query("
        UPDATE qr_tokens 
        SET scan_count = CASE WHEN scan_count > 0 THEN scan_count - 1 ELSE 0 END
        WHERE invitation_id = :iid
    ", ['iid' => $checkin['invitation_id']]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Check-in for {$checkin['guest_name']} successfully reversed. Guest is eligible for re-entry.",
        'guest_name' => $checkin['guest_name'],
        'check_in_id' => $checkin['id'],
        'invitation_id' => $checkin['invitation_id']
    ]);

} catch (Throwable $t) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reverse checkin error: " . $t->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error while reversing check-in: ' . $t->getMessage()]);
}
