<?php
require_once __DIR__ . '/../config/db.php';

echo "=== RUNNING VERIFICATION TEST SUITE ===\n\n";

$pdo = get_db();

// 1. Setup test event A (id 1) and event B (id 2)
// Gate 1 on Event 1 is VIP only ('vvip,vip')
// Gate 2 on Event 1 is All ('all')
$pdo->exec("UPDATE gates SET allowed_categories = 'vvip,vip' WHERE id = 1");
$pdo->exec("UPDATE gates SET allowed_categories = 'all' WHERE id = 2");

// Clear existing checkins for invitation 1 & 5
$pdo->exec("DELETE FROM check_ins WHERE invitation_id IN (1, 5)");
$pdo->exec("UPDATE invitations SET is_checked_in = 0 WHERE id IN (1, 5)");
$pdo->exec("UPDATE qr_tokens SET is_revoked = 0, is_active = 1 WHERE invitation_id IN (1, 5)");

function callVerify($token, $eventId, $gateId) {
    $url = "http://127.0.0.1:8080/api/verify.php?token=" . urlencode($token) . "&event_id=" . $eventId . "&gate_id=" . $gateId;
    $res = file_get_contents($url);
    return json_decode($res, true);
}

// TEST 1: Regular guest (Baraka Mushi) at VIP restricted gate (id 1) -> UNAUTHORIZED_CATEGORY
echo "Test 1: Regular pass ('DEMO-TOKEN-REG-BAR-05') at VIP-only gate (id 1)...\n";
$res1 = callVerify('DEMO-TOKEN-REG-BAR-05', 1, 1);
echo "Status: " . ($res1['status'] ?? 'ERROR') . " | Msg: " . ($res1['message'] ?? '') . "\n";
assert(($res1['status'] ?? '') === 'UNAUTHORIZED_CATEGORY', 'Expected UNAUTHORIZED_CATEGORY');
echo "✓ Test 1 PASSED.\n\n";

// TEST 2: Regular guest at General gate (id 2) -> VALID
echo "Test 2: Regular pass at General gate (id 2)...\n";
$res2 = callVerify('DEMO-TOKEN-REG-BAR-05', 1, 2);
echo "Status: " . ($res2['status'] ?? 'ERROR') . " | Msg: " . ($res2['message'] ?? '') . "\n";
assert(($res2['status'] ?? '') === 'VALID', 'Expected VALID');
echo "✓ Test 2 PASSED.\n\n";

// TEST 3: Scan same pass again -> DUPLICATE
echo "Test 3: Scanning the same pass again at Gate 2...\n";
$res3 = callVerify('DEMO-TOKEN-REG-BAR-05', 1, 2);
echo "Status: " . ($res3['status'] ?? 'ERROR') . " | Msg: " . ($res3['message'] ?? '') . "\n";
assert(($res3['status'] ?? '') === 'DUPLICATE', 'Expected DUPLICATE');
echo "✓ Test 3 PASSED.\n\n";

// TEST 4: Wrong Event -> WRONG_EVENT
echo "Test 4: Scanning Event 1 pass at Event 2...\n";
$res4 = callVerify('DEMO-TOKEN-VIP-REG-01', 2, 3);
echo "Status: " . ($res4['status'] ?? 'ERROR') . " | Msg: " . ($res4['message'] ?? '') . "\n";
assert(($res4['status'] ?? '') === 'WRONG_EVENT', 'Expected WRONG_EVENT');
echo "✓ Test 4 PASSED.\n\n";

// TEST 5: Revoked Pass -> REVOKED
echo "Test 5: Scanning a revoked pass...\n";
$pdo->exec("UPDATE qr_tokens SET is_revoked = 1 WHERE token = 'DEMO-TOKEN-VIP-REG-01'");
$res5 = callVerify('DEMO-TOKEN-VIP-REG-01', 1, 1);
echo "Status: " . ($res5['status'] ?? 'ERROR') . " | Msg: " . ($res5['message'] ?? '') . "\n";
assert(($res5['status'] ?? '') === 'REVOKED', 'Expected REVOKED');
// Reset revocation
$pdo->exec("UPDATE qr_tokens SET is_revoked = 0 WHERE token = 'DEMO-TOKEN-VIP-REG-01'");
echo "✓ Test 5 PASSED.\n\n";

// TEST 6: Invalid Token -> INVALID
echo "Test 6: Scanning non-existent token...\n";
$res6 = callVerify('THIS-IS-A-FAKE-TOKEN-99999', 1, 1);
echo "Status: " . ($res6['status'] ?? 'ERROR') . " | Msg: " . ($res6['message'] ?? '') . "\n";
assert(($res6['status'] ?? '') === 'INVALID', 'Expected INVALID');
echo "✓ Test 6 PASSED.\n\n";

echo "=====================================================\n";
echo "✓ ALL 6 VERIFICATION SUITE TESTS PASSED 100% PERFECTLY!\n";
echo "=====================================================\n";
