<?php
// Fully Isolated Subprocess Test Suite
echo "=== LEULA EVENTS VERIFICATION TEST SUITE ===\n\n";

function runIsolated(string $file, array $get = [], array $post = [], array $session = []): array {
    $code = '
        require ' . var_export(__DIR__ . '/config/db.php', true) . ';
        if (!empty(' . var_export($session, true) . ')) {
            $_SESSION = ' . var_export($session, true) . ';
        } else {
            $_SESSION = [];
        }
        $_GET = ' . var_export($get, true) . ';
        $_POST = ' . var_export($post, true) . ';
        $_REQUEST = array_merge($_GET, $_POST);
        $_SERVER["REQUEST_METHOD"] = ' . var_export(!empty($post) ? 'POST' : 'GET', true) . ';
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $_SERVER["HTTP_USER_AGENT"] = "IsolatedTestRunner";
        require ' . var_export(__DIR__ . '/' . $file, true) . ';
    ';

    $b64 = base64_encode($code);
    $cmd = 'C:\\xampp\\php\\php.exe -r "eval(base64_decode(\'' . $b64 . '\'));"';
    $out = shell_exec($cmd);
    return [
        'raw' => $out,
        'json' => json_decode($out, true)
    ];
}

$token = 'DEMO-TOKEN-VIP-REG-01';
$officer = [
    'user_id' => 1,
    'user' => [
        'id' => 1,
        'name' => 'Officer Alpha',
        'email' => 'admin@leulaevents.com',
        'role_id' => 1,
        'organization_id' => 1
    ]
];

$tests = [];

// [1] Guest Accepts Invitation
$t1 = runIsolated('api/rsvp.php', [], ['token' => $token, 'response' => 'accepted']);
$tests['1. Guest RSVP Accepted'] = ($t1['json']['success'] === true && $t1['json']['response'] === 'accepted');

// [2] Guest Changes to Declined
$t2 = runIsolated('api/rsvp.php', [], ['token' => $token, 'response' => 'declined']);
$tests['2. Guest RSVP Changed to Declined'] = ($t2['json']['success'] === true && $t2['json']['response'] === 'declined');

// [3] Guest Changes back to Accepted
$t3 = runIsolated('api/rsvp.php', [], ['token' => $token, 'response' => 'accepted']);
$tests['3. Guest RSVP Changed back to Accepted'] = ($t3['json']['success'] === true && $t3['json']['response'] === 'accepted');

// [4] Invalid token returns error
$t4 = runIsolated('api/rsvp.php', [], ['token' => 'INVALID-TOKEN-XYZ', 'response' => 'accepted']);
$tests['4. Invalid Token Error Handled'] = ($t4['json']['success'] === false);

// [5] Pass Page includes notifications and zero native alerts
$t5 = runIsolated('pass.php', ['token' => $token]);
$hasCss = strpos($t5['raw'], 'notifications.css') !== false;
$hasJs = strpos($t5['raw'], 'notifications.js') !== false;
$hasRev = strpos($t5['raw'], 'handleRsvpChange') !== false;
$noNative = strpos($t5['raw'], 'alert(') === false && strpos($t5['raw'], 'confirm(') === false;
$tests['5. Pass Page Reversible UI & No Native Alerts'] = ($hasCss && $hasJs && $hasRev && $noNative);

// [6] Anti-Passback Gate Duplicate Detection
$s1 = runIsolated('api/verify.php', ['token' => $token, 'event_id' => 1, 'gate_id' => 1], [], $officer);
$s2 = runIsolated('api/verify.php', ['token' => $token, 'event_id' => 1, 'gate_id' => 1], [], $officer);
$tests['6. Gate Scanner Anti-Passback Duplicate Blocked'] = ($s2['json']['status'] === 'DUPLICATE');

$invId = $s2['json']['data']['invitation_id'] ?? 1;

// [7] Check-in Reversal by Authorized Officer
$rUnauthorized = runIsolated('api/reverse_checkin.php', [], ['invitation_id' => $invId, 'reason' => 'test'], []);
$tests['7a. Guest Cannot Reverse Own Check-in (Unauthorized Rejected)'] = ($rUnauthorized['json']['success'] === false);

$rNoReason = runIsolated('api/reverse_checkin.php', [], ['invitation_id' => $invId, 'reason' => ''], $officer);
$tests['7b. Check-in Reversal Requires Reason (Rejected Without Reason)'] = ($rNoReason['json']['success'] === false);

$rValid = runIsolated('api/reverse_checkin.php', [], ['invitation_id' => $invId, 'reason' => 'Admitted to wrong section; reversed for re-entry.'], $officer);
$tests['7c. Authorized Officer Reversal Succeeded'] = (($rValid['json']['success'] ?? false) === true);

// [8] Re-entry after reversal
$s3 = runIsolated('api/verify.php', ['token' => $token, 'event_id' => 1, 'gate_id' => 1], [], $officer);
$tests['8. Pass Admitted Again for Re-Entry After Reversal'] = ($s3['json']['status'] === 'VALID');

// [9] Permanent Audit Log Records
require_once __DIR__ . '/config/db.php';
$auditLogs = db_fetch_all("SELECT event_type, action, description, created_at FROM audit_logs ORDER BY id DESC LIMIT 5");
$tests['9. Action History & Audit Log Preserved'] = (count($auditLogs) >= 3);

// [10] Zero native alerts across all app views
$files = ['pass.php', 'scanner.php', 'guests.php', 'users.php', 'distribution.php', 'designer.php', 'attendance.php', 'rsvp.php'];
$foundNative = false;
foreach ($files as $f) {
    $c = file_get_contents(__DIR__ . '/' . $f);
    if (preg_match('/\b(alert|confirm|prompt)\s*\(/i', $c)) {
        $foundNative = true;
    }
}
$tests['10. Zero Browser-Native Alerts/Confirms in Codebase'] = !$foundNative;

$allOk = true;
foreach ($tests as $title => $ok) {
    echo sprintf("  %-65s : %s\n", $title, $ok ? "PASS [OK]" : "FAIL [X]");
    if (!$ok) $allOk = false;
}

echo "\nAudit Log Sample Entries:\n";
foreach ($auditLogs as $l) {
    echo "  * [{$l['event_type']}] {$l['description']}\n";
}

echo "\nFINAL SUMMARY: " . ($allOk ? "ALL 10 VERIFICATION REQUIREMENTS 100% PASSED!" : "FAILURES DETECTED") . "\n";
exit($allOk ? 0 : 1);
