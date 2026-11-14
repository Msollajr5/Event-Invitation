<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_role(2);

$action = $_POST['action'] ?? '';
$user = auth_user();

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '#3b82f6');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name cannot be empty.']);
        exit;
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $id = db_insert('guest_categories', [
        'name' => $name,
        'slug' => $slug,
        'color' => $color,
        'description' => $desc
    ]);

    audit_log('CATEGORY_MANAGEMENT', 'CATEGORY_CREATED', "Category '{$name}' (color: {$color}) created by {$user['name']}");
    echo json_encode([
        'success' => true,
        'message' => "Category '{$name}' created successfully.",
        'category' => ['id' => $id, 'name' => $name, 'slug' => $slug, 'color' => $color]
    ]);
    exit;
}

if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '#3b82f6');
    $desc = trim($_POST['description'] ?? '');

    if (!$id || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid category parameters.']);
        exit;
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    db_update('guest_categories', [
        'name' => $name,
        'slug' => $slug,
        'color' => $color,
        'description' => $desc
    ], 'id = :id', ['id' => $id]);

    audit_log('CATEGORY_MANAGEMENT', 'CATEGORY_UPDATED', "Category #{$id} updated to '{$name}' (color: {$color}) by {$user['name']}");
    echo json_encode([
        'success' => true,
        'message' => "Category '{$name}' updated successfully."
    ]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
        exit;
    }

    // Check if guests are currently assigned to this category
    $count = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM guests WHERE guest_category_id = ?", [$id])['cnt'] ?? 0);
    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete this category because {$count} guest(s) are currently assigned to it. Please reassign them first."
        ]);
        exit;
    }

    $cat = db_fetch_one("SELECT name FROM guest_categories WHERE id = ?", [$id]);
    db_query("DELETE FROM guest_categories WHERE id = ?", [$id]);
    audit_log('CATEGORY_MANAGEMENT', 'CATEGORY_DELETED', "Category #{$id} ('" . ($cat['name'] ?? '') . "') deleted by {$user['name']}");

    echo json_encode([
        'success' => true,
        'message' => "Category deleted successfully."
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unrecognized category action.']);
