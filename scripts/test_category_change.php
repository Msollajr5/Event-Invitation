<?php
require_once __DIR__ . '/../config/db.php';

echo "=== TESTING 1-CLICK CATEGORY CHANGE & GATE ACCESS ===\n\n";

$pdo = get_db();

// 1. Setup Gate 1 as VIP only ('vvip,vip')
$pdo->exec("UPDATE gates SET allowed_categories = 'vvip,vip' WHERE id = 1");

// 2. Baraka Mushi (guest id 5, invitation id 5, token 'DEMO-TOKEN-REG-BAR-05')
// Reset checkins
$pdo->exec("DELETE FROM check_ins WHERE invitation_id = 5");
$pdo->exec("UPDATE invitations SET is_checked_in = 0 WHERE id = 5");

// Set category to Regular (category_id = 3, slug: 'regular')
$pdo->exec("UPDATE guests SET guest_category_id = 3 WHERE id = 5");

function callVerify($token, $eventId, $gateId) {
    $url = "http://127.0.0.1:8080/api/verify.php?token=" . urlencode($token) . "&event_id=" . $eventId . "&gate_id=" . $gateId;
    $res = file_get_contents($url);
    return json_decode($res, true);
}

// Check with Regular category at Gate 1 (VIP only)
echo "Step 1: Verifying Baraka Mushi as Regular at Gate 1 (VIP only)...\n";
$res1 = callVerify('DEMO-TOKEN-REG-BAR-05', 1, 1);
echo "Status: {$res1['status']} | Msg: {$res1['message']}\n";
assert($res1['status'] === 'UNAUTHORIZED_CATEGORY', 'Expected UNAUTHORIZED_CATEGORY');
echo "✓ Correctly rejected at VIP gate.\n\n";

// Step 2: Now change Baraka Mushi's category to VIP (category_id = 2, slug: 'vip')
echo "Step 2: Upgrading Baraka Mushi to VIP in 1 click (guest_id = 5)...\n";
$pdo->exec("UPDATE guests SET guest_category_id = 2 WHERE id = 5");
echo "✓ Category updated to VIP in database without altering token or invitation!\n\n";

// Step 3: Scan the EXACT same QR token at Gate 1 (VIP only)
echo "Step 3: Scanning the exact same token at Gate 1 now that guest is VIP...\n";
$res2 = callVerify('DEMO-TOKEN-REG-BAR-05', 1, 1);
echo "Status: {$res2['status']} | Msg: {$res2['message']}\n";
assert($res2['status'] === 'VALID', 'Expected VALID after category upgrade');
echo "✓ Entry APPROVED! Gate access rules immediately updated based on the new category!\n\n";

// Step 4: Revert back to Regular for cleanliness
$pdo->exec("UPDATE guests SET guest_category_id = 3 WHERE id = 5");
$pdo->exec("DELETE FROM check_ins WHERE invitation_id = 5");
$pdo->exec("UPDATE invitations SET is_checked_in = 0 WHERE id = 5");

echo "=== CATEGORY CHANGE TEST COMPLETED 100% SUCCESSFULLY ===\n";
