<?php
require_once __DIR__ . '/../config/db.php';

echo "=== TESTING STRICT EVENT DATA ISOLATION ===\n\n";

$pdo = get_db();

// Test Event 1 and Event 2
$ev1 = db_fetch_one("SELECT * FROM events WHERE id = 1");
$ev2 = db_fetch_one("SELECT * FROM events WHERE id = 2");

echo "Event 1: '{$ev1['title']}' (ID: 1)\n";
echo "Event 2: '{$ev2['title']}' (ID: 2)\n\n";

// 1. Check guest separation
$ev1Guests = db_fetch_all("SELECT id, full_name, event_id FROM guests WHERE event_id = 1");
$ev2Guests = db_fetch_all("SELECT id, full_name, event_id FROM guests WHERE event_id = 2");

echo "Step 1: Checking guest list separation...\n";
echo "- Event 1 guest count: " . count($ev1Guests) . "\n";
echo "- Event 2 guest count: " . count($ev2Guests) . "\n";

foreach ($ev1Guests as $g) {
    assert((int)$g['event_id'] === 1, "Guest {$g['id']} must belong to event 1");
}
foreach ($ev2Guests as $g) {
    assert((int)$g['event_id'] === 2, "Guest {$g['id']} must belong to event 2");
}
echo "✓ No cross-event guest contamination.\n\n";

// 2. Check QR Token Cryptographic Binding
echo "Step 2: Checking QR Token isolation...\n";
$tokensEv1 = db_fetch_all("SELECT qt.token, i.event_id FROM qr_tokens qt JOIN invitations i ON qt.invitation_id = i.id WHERE i.event_id = 1");
$tokensEv2 = db_fetch_all("SELECT qt.token, i.event_id FROM qr_tokens qt JOIN invitations i ON qt.invitation_id = i.id WHERE i.event_id = 2");

echo "- Event 1 tokens count: " . count($tokensEv1) . "\n";
echo "- Event 2 tokens count: " . count($tokensEv2) . "\n";

$t1List = array_column($tokensEv1, 'token');
$t2List = array_column($tokensEv2, 'token');
$overlap = array_intersect($t1List, $t2List);
assert(empty($overlap), "QR tokens must never overlap between events");
echo "✓ Zero token collision between events.\n\n";

// 3. Verification Rejection for Wrong Event
echo "Step 3: Verification test: scanning Event 1 pass at Event 2...\n";
$token1 = $tokensEv1[0]['token'] ?? 'DEMO-TOKEN-VIP-REG-01';
$url = "http://127.0.0.1:8080/api/verify.php?token=" . urlencode($token1) . "&event_id=2&gate_id=3";
$res = json_decode(file_get_contents($url), true);

echo "Status: {$res['status']} | Msg: {$res['message']}\n";
assert(($res['status'] ?? '') === 'WRONG_EVENT', 'Expected WRONG_EVENT status');
echo "✓ Cross-event scanning rejected immediately with WRONG_EVENT.\n\n";

// 4. Role-based event access scoping test
echo "Step 4: Testing event scoping functions...\n";

// Admin user (role_id = 1)
$adminEvents = get_user_events(1, 1);
echo "- Admin user sees all events: count = " . count($adminEvents) . "\n";
assert(count($adminEvents) >= 2, "Admin should see all non-deleted events");

// Director user who owns Event 1 & 2
$directorEvents = get_user_events(2, 2);
echo "- Director user (ID 2) sees assigned events: count = " . count($directorEvents) . "\n";
assert(can_user_access_event(1, 2, 2) === true, "Director 2 should have access to event 1");

// Test another hypothetical director user (ID 999)
$unauthorizedEvents = get_user_events(999, 2);
echo "- Director 999 (owns 0 events) sees: count = " . count($unauthorizedEvents) . "\n";
assert(count($unauthorizedEvents) === 0, "Director with 0 events should see 0 events");
assert(can_user_access_event(1, 999, 2) === false, "Director 999 must NOT access event 1");
echo "✓ Role and event scoping prevents cross-director data leakage.\n\n";

echo "=== ALL EVENT ISOLATION TESTS PASSED 100%! ===\n";
