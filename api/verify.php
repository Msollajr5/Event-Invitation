<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$token = trim($_REQUEST['token'] ?? '');
$eventId = (int)($_REQUEST['event_id'] ?? 1);
$gateId = (int)($_REQUEST['gate_id'] ?? 1);
$attendeeCount = max(1, (int)($_REQUEST['attendee_count'] ?? 1));

if (empty($token)) {
    echo json_encode([
        'status' => 'INVALID',
        'message' => 'Pass token or reference code cannot be empty',
        'color' => 'red',
        'data' => null
    ]);
    exit;
}

// 1. Query token / reference / phone / guest
$tokenRow = db_fetch_one("
    SELECT qt.*, qt.is_revoked as token_revoked, qt.is_active as token_active,
           i.id as invitation_id, i.event_id, i.reference_code, i.status as invitation_status,
           g.id as guest_id, g.full_name as guest_name, g.phone as guest_phone, g.permitted_attendees,
           gc.name as guest_category, gc.slug as category_slug, gc.color as category_color,
           e.title as event_title, e.start_date as event_date, e.venue_name
    FROM invitations i
    LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
    JOIN guests g ON i.guest_id = g.id
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    JOIN events e ON i.event_id = e.id
    WHERE qt.token = :tok 
       OR i.reference_code = :tok2
       OR g.phone = :tok3
    LIMIT 1
", ['tok' => $token, 'tok2' => $token, 'tok3' => $token]);

// Fallback search by guest full name if exact match
if (!$tokenRow) {
    $tokenRow = db_fetch_one("
        SELECT qt.*, qt.is_revoked as token_revoked, qt.is_active as token_active,
               i.id as invitation_id, i.event_id, i.reference_code, i.status as invitation_status,
               g.id as guest_id, g.full_name as guest_name, g.phone as guest_phone, g.permitted_attendees,
               gc.name as guest_category, gc.slug as category_slug, gc.color as category_color,
               e.title as event_title, e.start_date as event_date, e.venue_name
        FROM invitations i
        LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
        JOIN guests g ON i.guest_id = g.id
        LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
        JOIN events e ON i.event_id = e.id
        WHERE LOWER(g.full_name) = LOWER(:tok)
        LIMIT 1
    ", ['tok' => $token]);
}

if (!$tokenRow) {
    audit_log('GATE_VERIFY', 'INVALID_TOKEN_ATTEMPT', "Unrecognized token scanned or entered: {$token}");
    echo json_encode([
        'status' => 'INVALID',
        'message' => 'INVALID PASS - TOKEN OR GUEST NOT FOUND IN REGISTRY',
        'color' => 'red',
        'data' => null
    ]);
    exit;
}

// 2. Check for Revocation
$isRevoked = ((int)($tokenRow['token_revoked'] ?? 0) === 1) ||
             ($tokenRow['invitation_status'] === 'revoked') ||
             ((int)($tokenRow['token_active'] ?? 1) === 0);

if ($isRevoked) {
    audit_log('GATE_SECURITY', 'REVOKED_ENTRY_ATTEMPT', "Attempted entry with revoked pass for {$tokenRow['guest_name']} (Ref: {$tokenRow['reference_code']})");
    echo json_encode([
        'status' => 'REVOKED',
        'message' => 'REVOKED INVITATION - THIS PASS HAS BEEN CANCELLED OR VOIDED',
        'color' => 'red',
        'data' => [
            'guest_name' => $tokenRow['guest_name'],
            'reference_code' => $tokenRow['reference_code'],
            'guest_category' => $tokenRow['guest_category'] ?? 'Regular',
            'category_color' => $tokenRow['category_color'] ?? '#3b82f6',
            'event_title' => $tokenRow['event_title']
        ]
    ]);
    exit;
}

// 3. Strict Event Isolation & Validation
if ((int)$tokenRow['event_id'] !== $eventId) {
    audit_log('GATE_SECURITY', 'CROSS_EVENT_REJECTION', "Pass for '{$tokenRow['event_title']}' scanned at event ID {$eventId}");
    echo json_encode([
        'status' => 'WRONG_EVENT',
        'message' => 'WRONG EVENT - TICKET IS ISSUED FOR: ' . $tokenRow['event_title'],
        'color' => 'amber',
        'data' => [
            'guest_name' => $tokenRow['guest_name'],
            'reference_code' => $tokenRow['reference_code'],
            'guest_category' => $tokenRow['guest_category'] ?? 'Regular',
            'category_color' => $tokenRow['category_color'] ?? '#3b82f6',
            'event_title' => $tokenRow['event_title'],
            'venue_name' => $tokenRow['venue_name'],
            'event_date' => $tokenRow['event_date']
        ]
    ]);
    exit;
}

// 4. Gate Category-Based Access Control
$gate = db_fetch_one("SELECT * FROM gates WHERE id = :gid", ['gid' => $gateId]);
$gateName = $gate['name'] ?? 'Main Gate';
$allowedCats = trim($gate['allowed_categories'] ?? 'all');

if (!empty($allowedCats) && strtolower($allowedCats) !== 'all') {
    $allowedList = array_map('trim', explode(',', strtolower($allowedCats)));
    $guestSlug = strtolower(trim($tokenRow['category_slug'] ?? ''));
    $guestCatName = strtolower(trim($tokenRow['guest_category'] ?? ''));

    $isAllowed = in_array($guestSlug, $allowedList) || in_array($guestCatName, $allowedList);

    if (!$isAllowed) {
        $allowedHuman = strtoupper(implode(', ', $allowedList));
        audit_log(
            'GATE_SECURITY',
            'UNAUTHORIZED_CATEGORY_REJECTED',
            "Guest {$tokenRow['guest_name']} ({$tokenRow['guest_category']}) rejected at {$gateName}. Allowed: {$allowedHuman}."
        );

        echo json_encode([
            'status' => 'UNAUTHORIZED_CATEGORY',
            'message' => "GATE RESTRICTION: {$tokenRow['guest_category']} pass is not authorized at {$gateName}. This entrance is reserved for: {$allowedHuman}.",
            'color' => 'amber',
            'data' => [
                'guest_name' => $tokenRow['guest_name'],
                'reference_code' => $tokenRow['reference_code'],
                'guest_category' => $tokenRow['guest_category'] ?? 'Regular',
                'category_color' => $tokenRow['category_color'] ?? '#3b82f6',
                'entrance_gate' => $gateName,
                'allowed_categories' => $allowedHuman
            ]
        ]);
        exit;
    }
}

// 5. Anti-Passback / Duplicate Entry Check
$existingCheckin = db_fetch_one("
    SELECT c.*, gt.name as gate_name 
    FROM check_ins c 
    LEFT JOIN gates gt ON c.gate_id = gt.id
    WHERE c.invitation_id = :invId 
    ORDER BY c.id DESC
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
            'category_color' => $tokenRow['category_color'] ?? '#3b82f6',
            'reference_code' => $tokenRow['reference_code'],
            'first_checked_in_at' => $existingCheckin['checked_in_at'],
            'first_gate' => $existingCheckin['gate_name'] ?: 'Gate 1',
            'verified_by' => $existingCheckin['verified_by']
        ]
    ]);
    exit;
}

// 6. VALID ENTRY -> Process Atomic Check-in
$user = auth_user();
$verifiedBy = $user['id'] ?? 1;
$officerName = $user['name'] ?? 'Gate Officer';
$checkinAttendees = min($attendeeCount, (int)$tokenRow['permitted_attendees']);

$checkinId = db_insert('check_ins', [
    'invitation_id' => $tokenRow['invitation_id'],
    'guest_id' => $tokenRow['guest_id'],
    'event_id' => $eventId,
    'gate_id' => $gateId,
    'verified_by' => $verifiedBy,
    'verification_method' => 'qr',
    'attendee_count' => $checkinAttendees,
    'checked_in_at' => date('Y-m-d H:i:s')
]);

// Update invitation status and check-in flag
db_update('invitations', [
    'status' => 'delivered',
    'is_checked_in' => 1
], 'id = :id', ['id' => $tokenRow['invitation_id']]);

if (!empty($tokenRow['id'])) {
    db_query("UPDATE qr_tokens SET scan_count = scan_count + 1, last_scanned_at = CURRENT_TIMESTAMP WHERE id = :qid", ['qid' => $tokenRow['id']]);
}

audit_log(
    'GATE_CHECKIN',
    'ADMISSION_APPROVED',
    "Attendee {$tokenRow['guest_name']} ({$tokenRow['guest_category']}, Ref: {$tokenRow['reference_code']}) admitted through {$gateName} ({$checkinAttendees} attendees).",
    $verifiedBy
);

echo json_encode([
    'status' => 'VALID',
    'message' => 'ENTRY APPROVED - ACCESS GRANTED',
    'color' => 'green',
    'data' => [
        'invitation_id' => (int)$tokenRow['invitation_id'],
        'check_in_id' => $checkinId,
        'guest_name' => $tokenRow['guest_name'],
        'guest_category' => $tokenRow['guest_category'] ?? 'Regular',
        'category_color' => $tokenRow['category_color'] ?? '#3b82f6',
        'reference_code' => $tokenRow['reference_code'],
        'permitted_attendees' => $tokenRow['permitted_attendees'],
        'checked_in_attendees' => $checkinAttendees,
        'checked_in_at' => date('Y-m-d H:i:s'),
        'entrance_gate' => $gateName,
        'verification_officer' => $officerName,
        'event_title' => $tokenRow['event_title']
    ]
]);
