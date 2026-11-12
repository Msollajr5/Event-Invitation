<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$token = trim($_REQUEST['token'] ?? '');
$eventId = (int)($_REQUEST['event_id'] ?? 1);
$gateId = (int)($_REQUEST['gate_id'] ?? 1);
$attendeeCount = max(1, (int)($_REQUEST['attendee_count'] ?? 1));

if (empty($token)) {
    echo json_encode(['status' => 'INVALID', 'message' => 'Token cannot be empty', 'color' => 'red']);
    exit;
}

// 1. Query token record
$tokenRow = db_fetch_one("
    SELECT qt.*, i.id as invitation_id, i.event_id, i.reference_code,
           g.id as guest_id, g.full_name as guest_name, g.permitted_attendees,
           gc.name as guest_category, gc.color as category_color,
           e.title as event_title
    FROM qr_tokens qt
    JOIN invitations i ON qt.invitation_id = i.id
    JOIN guests g ON i.guest_id = g.id
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    JOIN events e ON i.event_id = e.id
    WHERE qt.token = :tok OR i.reference_code = :tok2
    LIMIT 1
", ['tok' => $token, 'tok2' => $token]);

if (!$tokenRow) {
    echo json_encode([
        'status' => 'INVALID',
        'message' => 'INVALID PASS - TOKEN NOT FOUND IN REGISTRY',
        'color' => 'red',
        'data' => null
    ]);
    exit;
}

// 2. Validate Event
if ((int)$tokenRow['event_id'] !== $eventId) {
    echo json_encode([
        'status' => 'WRONG_EVENT',
        'message' => 'WRONG EVENT - TICKET IS FOR: ' . $tokenRow['event_title'],
        'color' => 'amber',
        'data' => [
            'guest_name' => $tokenRow['guest_name'],
            'reference_code' => $tokenRow['reference_code'],
            'event_title' => $tokenRow['event_title']
        ]
    ]);
    exit;
}

// 3. Check for Duplicate Entry
$existingCheckin = db_fetch_one("
    SELECT c.*, gt.name as gate_name 
    FROM check_ins c 
    LEFT JOIN gates gt ON c.gate_id = gt.id
    WHERE c.invitation_id = :invId 
    LIMIT 1
", ['invId' => $tokenRow['invitation_id']]);

if ($existingCheckin) {
    echo json_encode([
        'status' => 'DUPLICATE',
        'message' => 'DUPLICATE ENTRY DETECTED - PASS ALREADY USED!',
        'color' => 'red',
        'data' => [
            'invitation_id' => (int)$tokenRow['invitation_id'],
            'check_in_id' => (int)$existingCheckin['id'],
            'guest_name' => $tokenRow['guest_name'],
            'guest_category' => $tokenRow['guest_category'] ?? 'Regular',
            'reference_code' => $tokenRow['reference_code'],
            'first_checked_in_at' => $existingCheckin['checked_in_at'],
            'first_gate' => $existingCheckin['gate_name'] ?: 'Gate 1'
        ]
    ]);
    exit;
}

// 4. Valid Entry -> Record Check-in!
$gate = db_fetch_one("SELECT name FROM gates WHERE id = :gid", ['gid' => $gateId]);
$gateName = $gate['name'] ?? 'Main Gate';
$user = auth_user();
$verifiedBy = $user['id'] ?? 1;
$officerName = $user['name'] ?? 'Gate Officer';

db_insert('check_ins', [
    'invitation_id' => $tokenRow['invitation_id'],
    'guest_id' => $tokenRow['guest_id'],
    'event_id' => $eventId,
    'gate_id' => $gateId,
    'verified_by' => $verifiedBy,
    'verification_method' => 'qr',
    'attendee_count' => min($attendeeCount, (int)$tokenRow['permitted_attendees'])
]);

db_update('invitations', ['status' => 'delivered', 'is_checked_in' => 1], 'id = :id', ['id' => $tokenRow['invitation_id']]);
db_query("UPDATE qr_tokens SET scan_count = scan_count + 1, last_scanned_at = CURRENT_TIMESTAMP WHERE id = :qid", ['qid' => $tokenRow['id']]);

audit_log(
    'GATE_CHECKIN',
    'ADMISSION_APPROVED',
    "Attendee {$tokenRow['guest_name']} (Ref: {$tokenRow['reference_code']}) admitted through {$gateName} by {$officerName}.",
    $verifiedBy
);

echo json_encode([
    'status' => 'VALID',
    'message' => 'ENTRY APPROVED - ACCESS GRANTED',
    'color' => 'green',
    'data' => [
        'guest_name' => $tokenRow['guest_name'],
        'guest_category' => $tokenRow['guest_category'] ?? 'Regular',
        'reference_code' => $tokenRow['reference_code'],
        'permitted_attendees' => $tokenRow['permitted_attendees'],
        'checked_in_attendees' => min($attendeeCount, (int)$tokenRow['permitted_attendees']),
        'checked_in_at' => date('Y-m-d H:i:s'),
        'entrance_gate' => $gateName,
        'verification_officer' => $officerName
    ]
]);

