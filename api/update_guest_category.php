<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_role(2);

$guestId = (int)($_POST['guest_id'] ?? 0);
$categoryId = (int)($_POST['category_id'] ?? 0);

if (!$guestId || !$categoryId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
    exit;
}

$guest = db_fetch_one("
    SELECT g.*, gc.name as old_category_name 
    FROM guests g 
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id 
    WHERE g.id = ?
", [$guestId]);

if (!$guest) {
    echo json_encode(['success' => false, 'message' => 'Guest record not found.']);
    exit;
}

// Verify event ownership
if (!can_user_access_event((int)$guest['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: You do not have permission to modify guests for this event.']);
    exit;
}

$newCategory = db_fetch_one("SELECT * FROM guest_categories WHERE id = ?", [$categoryId]);
if (!$newCategory) {
    echo json_encode(['success' => false, 'message' => 'Selected category does not exist.']);
    exit;
}

// Update guest category while preserving token, invitation reference, and check-in history
db_update('guests', ['guest_category_id' => $categoryId], 'id = :id', ['id' => $guestId]);

$user = auth_user();
audit_log(
    'GUEST_MANAGEMENT',
    'CATEGORY_CHANGED',
    "Guest '{$guest['full_name']}' category changed from '{$guest['old_category_name']}' to '{$newCategory['name']}' by {$user['name']}."
);

echo json_encode([
    'success' => true,
    'message' => "Category for {$guest['full_name']} updated to {$newCategory['name']}.",
    'guest_id' => $guestId,
    'category_name' => $newCategory['name'],
    'category_color' => $newCategory['color'] ?: '#3b82f6'
]);
