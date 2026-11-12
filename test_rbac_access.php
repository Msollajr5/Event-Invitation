<?php
// Role-Based Access Control (RBAC) Verification Suite
require_once __DIR__ . '/config/db.php';

echo "=== LEULA EVENTS RBAC ACCESS CONTROL TEST ===\n\n";

function testRoleAccess(string $page, array $sessionData, array $get = []): array {
    $runnerCode = '<?php
        chdir(' . var_export(__DIR__, true) . ');
        require ' . var_export(__DIR__ . '/config/db.php', true) . ';
        $_SESSION = ' . var_export($sessionData, true) . ';
        $_GET = ' . var_export($get, true) . ';
        $_POST = [];
        $_REQUEST = $_GET;
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $_SERVER["HTTP_USER_AGENT"] = "RBACTestRunner";
        require ' . var_export(__DIR__ . '/' . $page, true) . ';
    ';
    $tmpFile = __DIR__ . '/tmp_runner.php';
    file_put_contents($tmpFile, $runnerCode);
    $cmd = 'C:\\xampp\\php\\php.exe ' . escapeshellarg($tmpFile);
    $raw = shell_exec($cmd);
    @unlink($tmpFile);
    return ['raw' => (string)$raw];
}

$admin = [
    'user_id' => 1,
    'user' => ['id' => 1, 'name' => 'Super Admin', 'email' => 'admin@leulaevents.com', 'role_id' => 1, 'role' => 'Super Administrator']
];

$director = [
    'user_id' => 2,
    'user' => ['id' => 2, 'name' => 'Sarah Mwangi', 'email' => 'organizer@leulaevents.com', 'role_id' => 2, 'role' => 'Event Director']
];

$gateOfficer = [
    'user_id' => 3,
    'user' => ['id' => 3, 'name' => 'David Gate Lead', 'email' => 'officer@leulaevents.com', 'role_id' => 3, 'role' => 'Verification Officer']
];

$tests = [];

// ==========================================
// 1. GATE OFFICER RESTRICTIONS (Role 3)
// ==========================================
echo "--- Testing Gate Officer (Role 3) Restrictions ---\n";

// Gate officer should NOT access users.php
$r1 = testRoleAccess('users.php', $gateOfficer);
$tests['Gate Officer cannot access users.php'] = (strpos($r1['raw'], 'Register Team Member') === false);

// Gate officer should NOT access settings.php
$r2 = testRoleAccess('settings.php', $gateOfficer);
$tests['Gate Officer cannot access settings.php'] = (strpos($r2['raw'], 'System Settings') === false);

// Gate officer should NOT access designer.php
$r3 = testRoleAccess('designer.php', $gateOfficer);
$tests['Gate Officer cannot access designer.php'] = (strpos($r3['raw'], 'Template Library') === false);

// Gate officer should NOT access events.php
$r4 = testRoleAccess('events.php', $gateOfficer);
$tests['Gate Officer cannot access events.php'] = (strpos($r4['raw'], 'Create New Event') === false);

// Gate officer should NOT access guests.php
$r5 = testRoleAccess('guests.php', $gateOfficer);
$tests['Gate Officer cannot access guests.php'] = (strpos($r5['raw'], 'Add Individual Guest') === false);

// Gate officer CAN access scanner.php
$r6 = testRoleAccess('scanner.php', $gateOfficer);
$tests['Gate Officer CAN access scanner.php'] = (strpos($r6['raw'], 'Gate Verification Terminal') !== false);

// Gate officer CAN access attendance.php
$r7 = testRoleAccess('attendance.php', $gateOfficer);
$tests['Gate Officer CAN access attendance.php'] = (strpos($r7['raw'], 'Live Attendance Monitor') !== false);

// Gate officer Sidebar should ONLY show Verification & Access
$tests['Gate Officer Sidebar hides Designer & Users'] = (
    strpos($r6['raw'], 'Card Designer') === false &&
    strpos($r6['raw'], 'Team Members') === false &&
    strpos($r6['raw'], 'QR Gate Scanner') !== false
);

// ==========================================
// 2. EVENT DIRECTOR PERMISSIONS & RESTRICTIONS (Role 2)
// ==========================================
echo "\n--- Testing Event Director (Role 2) Permissions & Limits ---\n";

// Event Director CAN access dashboard.php
$d1 = testRoleAccess('dashboard.php', $director);
$tests['Event Director CAN access dashboard.php'] = (strpos($d1['raw'], 'Operational Overview') !== false);

// Event Director CAN access guests.php
$d2 = testRoleAccess('guests.php', $director);
$tests['Event Director CAN access guests.php'] = (strpos($d2['raw'], 'Guest Directory') !== false);

// Event Director CAN access designer.php
$d3 = testRoleAccess('designer.php', $director);
$tests['Event Director CAN access designer.php'] = (strpos($d3['raw'], 'Canva-Style') !== false || strpos($d3['raw'], 'Card Designer') !== false);

// Event Director should NOT access users.php (Admin only)
$d4 = testRoleAccess('users.php', $director);
$tests['Event Director cannot access users.php (Admin Only)'] = (strpos($d4['raw'], 'Add Team Member') === false && strpos($d4['raw'], 'Register Team Member') === false);

// Event Director should NOT access settings.php (Admin only)
$d5 = testRoleAccess('settings.php', $director);
$tests['Event Director cannot access settings.php (Admin Only)'] = (strpos($d5['raw'], 'Access Control & Gate Policies') === false);

// Event Director Sidebar should NOT show Team Members or Settings
$tests['Event Director Sidebar hides Users and Settings'] = (
    strpos($d1['raw'], 'Team Members') === false &&
    strpos($d1['raw'], 'Settings') === false &&
    strpos($d1['raw'], 'Guest Directory') !== false
);

// ==========================================
// 3. SUPER ADMINISTRATOR FULL ACCESS (Role 1)
// ==========================================
echo "\n--- Testing Super Administrator (Role 1) Full Access ---\n";

// Super Admin CAN access users.php
$a1 = testRoleAccess('users.php', $admin);
$tests['Super Admin CAN access users.php'] = (strpos($a1['raw'], 'Team Members') !== false);

// Super Admin CAN access settings.php
$a2 = testRoleAccess('settings.php', $admin);
$tests['Super Admin CAN access settings.php'] = (strpos($a2['raw'], 'System Settings') !== false);

// Super Admin Sidebar shows EVERYTHING
$tests['Super Admin Sidebar shows Team Members & Settings'] = (
    strpos($a1['raw'], 'Team Members') !== false &&
    strpos($a1['raw'], 'Settings') !== false &&
    strpos($a1['raw'], 'Guest Directory') !== false
);

echo "\n=== RBAC ACCESS TEST RESULTS ===\n";
$allPass = true;
foreach ($tests as $title => $passed) {
    echo sprintf("  %-65s : %s\n", $title, $passed ? "PASS [OK]" : "FAIL [X]");
    if (!$passed) $allPass = false;
}

echo "\nOVERALL RBAC RESULT: " . ($allPass ? "100% PASSED" : "SOME TESTS FAILED") . "\n";
exit($allPass ? 0 : 1);
