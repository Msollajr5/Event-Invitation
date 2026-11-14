<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_auth();

$q = trim($_GET['q'] ?? '');
$eventId = (int)($_GET['event_id'] ?? 1);

if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . $q . '%';
$rows = db_fetch_all("
    SELECT g.id as guest_id, g.full_name, g.phone, g.email, i.is_checked_in,
           gc.name as category_name, gc.color as category_color,
           i.id as invitation_id, i.reference_code, i.status as invitation_status,
           qt.token as qr_token, qt.is_revoked as token_revoked
    FROM guests g
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    LEFT JOIN invitations i ON i.guest_id = g.id AND i.event_id = :ev1
    LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
    WHERE g.event_id = :ev2
      AND (g.full_name LIKE :q1 OR g.phone LIKE :q2 OR i.reference_code LIKE :q3 OR qt.token LIKE :q4)
    ORDER BY g.full_name ASC
    LIMIT 10
", [
    'ev1' => $eventId,
    'ev2' => $eventId,
    'q1' => $like,
    'q2' => $like,
    'q3' => $like,
    'q4' => $like
]);

echo json_encode(['results' => $rows]);
