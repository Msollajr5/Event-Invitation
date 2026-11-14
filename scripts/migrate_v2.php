<?php
require_once __DIR__ . '/../config/db.php';
$pdo = get_db();

echo "Starting Database Updates...\n";

// 1. Ensure gates has allowed_categories (already added)
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM gates LIKE 'allowed_categories'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE gates ADD COLUMN allowed_categories TEXT DEFAULT 'all'");
        echo "✓ Added 'allowed_categories' column to gates table.\n";
    } else {
        echo "✓ 'allowed_categories' column already exists in gates table.\n";
    }
} catch (Exception $e) {
    echo "! Gates table check: " . $e->getMessage() . "\n";
}

// 2. Ensure guest_categories has all defaults
$categories = [
    ['name' => 'VVIP', 'slug' => 'vvip', 'color' => '#8b5cf6', 'description' => 'Very Very Important Person - Full All-Access'],
    ['name' => 'VIP', 'slug' => 'vip', 'color' => '#f59e0b', 'description' => 'VIP Access - Priority Seating & Gate'],
    ['name' => 'Regular', 'slug' => 'regular', 'color' => '#3b82f6', 'description' => 'Standard General Admission'],
    ['name' => 'General', 'slug' => 'general', 'color' => '#10b981', 'description' => 'General Public Access'],
    ['name' => 'Staff', 'slug' => 'staff', 'color' => '#64748b', 'description' => 'Event Organization & Support Staff'],
    ['name' => 'Media', 'slug' => 'media', 'color' => '#ec4899', 'description' => 'Accredited Press and Media'],
    ['name' => 'Special Guest', 'slug' => 'special-guest', 'color' => '#e11d48', 'description' => 'Honored Guest and Dignitary']
];

foreach ($categories as $cat) {
    $stmt = $pdo->prepare("SELECT id FROM guest_categories WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$cat['name']]);
    $existing = $stmt->fetch();
    if (!$existing) {
        $ins = $pdo->prepare("INSERT INTO guest_categories (name, slug, color, description, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $ins->execute([$cat['name'], $cat['slug'], $cat['color'], $cat['description']]);
        echo "✓ Inserted category: {$cat['name']}\n";
    } else {
        echo "✓ Category exists: {$cat['name']}\n";
    }
}

// 3. Update gates allowed_categories defaults if empty
$pdo->exec("UPDATE gates SET allowed_categories = 'all' WHERE allowed_categories IS NULL OR allowed_categories = ''");

// Ensure VIP gate has restricted allowed_categories if present
$pdo->exec("UPDATE gates SET allowed_categories = 'vvip,vip' WHERE LOWER(name) LIKE '%vip%' AND (allowed_categories = 'all' OR allowed_categories IS NULL)");

echo "✓ Gates allowed_categories updated.\n";

// List all categories
echo "\nCurrent Guest Categories:\n";
$allCats = $pdo->query("SELECT id, name, slug, color FROM guest_categories")->fetchAll(PDO::FETCH_ASSOC);
foreach ($allCats as $c) {
    echo "- [ID: {$c['id']}] {$c['name']} ({$c['slug']}, color: {$c['color']})\n";
}

// List all gates
echo "\nCurrent Gates:\n";
$allGates = $pdo->query("SELECT id, event_id, name, code, allowed_categories FROM gates")->fetchAll(PDO::FETCH_ASSOC);
foreach ($allGates as $g) {
    echo "- [ID: {$g['id']}] {$g['name']} (Code: {$g['code']}, Event: {$g['event_id']}, Allowed: {$g['allowed_categories']})\n";
}

echo "\nMigration completed successfully.\n";
