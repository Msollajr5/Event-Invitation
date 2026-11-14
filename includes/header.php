<?php
// Layout Header — Leula Events Management Portal
require_once __DIR__ . '/../config/db.php';

$user = auth_user();
$roleId = (int)($user['role_id'] ?? 3);
$flash = flash_get();
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$userEvents = get_user_events();
$activeEventId = get_active_event_id();
$activeEvent = get_active_event();

// Master Navigation with Role-Based Visibility (1: Super Admin, 2: Event Director, 3: Gate Officer)
$allNavSections = [
    [
        'label' => 'Overview',
        'min_role' => 2,
        'items' => [
            ['key' => 'dashboard', 'title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'dashboard', 'min_role' => 2],
        ]
    ],
    [
        'label' => 'Events & Guests',
        'min_role' => 2,
        'items' => [
            ['key' => 'organizations', 'title' => 'Organizations',    'url' => 'organizations.php', 'icon' => 'building', 'min_role' => 1],
            ['key' => 'events',        'title' => 'Events Registry',  'url' => 'events.php',        'icon' => 'calendar', 'min_role' => 2],
            ['key' => 'guests',        'title' => 'Guest Directory',  'url' => 'guests.php',        'icon' => 'users',    'min_role' => 2],
            ['key' => 'rsvp',          'title' => 'RSVP Roster',      'url' => 'rsvp.php',          'icon' => 'message',  'min_role' => 2],
        ]
    ],
    [
        'label' => 'Passes & Creative',
        'min_role' => 2,
        'items' => [
            ['key' => 'designer',      'title' => 'Card Designer',    'url' => 'designer.php',      'icon' => 'palette',  'min_role' => 2],
            ['key' => 'invitations',   'title' => 'Digital Passes',   'url' => 'invitations.php',   'icon' => 'ticket',   'min_role' => 2],
            ['key' => 'distribution',  'title' => 'Distribution Hub', 'url' => 'distribution.php',  'icon' => 'send',     'min_role' => 2],
        ]
    ],
    [
        'label' => 'Verification & Access',
        'min_role' => 3,
        'items' => [
            ['key' => 'scanner',       'title' => 'QR Gate Scanner',  'url' => 'scanner.php',       'icon' => 'qr',           'min_role' => 3],
            ['key' => 'attendance',    'title' => 'Live Attendance',  'url' => 'attendance.php',    'icon' => 'check-circle', 'min_role' => 3],
        ]
    ],
    [
        'label' => 'System & Reports',
        'min_role' => 2,
        'items' => [
            ['key' => 'reports',       'title' => 'Reports & Analytics', 'url' => 'reports.php',       'icon' => 'chart',    'min_role' => 2],
            ['key' => 'notifications', 'title' => 'Notifications',       'url' => 'notifications.php', 'icon' => 'bell',     'min_role' => 2],
            ['key' => 'users',         'title' => 'Team Members',        'url' => 'users.php',         'icon' => 'shield',   'min_role' => 1],
            ['key' => 'settings',      'title' => 'Settings',            'url' => 'settings.php',      'icon' => 'settings', 'min_role' => 1],
        ]
    ],
];

// Prune navigation items that the specific user role does not need
$navSections = [];
foreach ($allNavSections as $sec) {
    if ($roleId > $sec['min_role']) {
        continue;
    }
    $allowedItems = [];
    foreach ($sec['items'] as $item) {
        if ($roleId <= $item['min_role']) {
            $allowedItems[] = $item;
        }
    }
    if (!empty($allowedItems)) {
        $sec['items'] = $allowedItems;
        $navSections[] = $sec;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Portal') ?> &bull; <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/portal.css?v=<?= time() ?>">
  <link rel="stylesheet" href="assets/css/notifications.css?v=<?= time() ?>">
  <script src="assets/js/notifications.js?v=<?= time() ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="portal-wrapper">

<!-- Mobile Sidebar Backdrop Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <a href="<?= ($roleId === 3) ? 'scanner.php' : 'dashboard.php' ?>" class="sidebar-logo-link">
      <div class="sidebar-logo-badge">
        <?= svgIcon('qr', 22) ?>
      </div>
      <div class="sidebar-logo-text">
        <span class="sidebar-logo-title">Leula Events</span>
        <span class="sidebar-logo-tagline"><?= ($roleId === 3) ? 'Gate Verification' : 'Access & Verification' ?></span>
      </div>
    </a>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($navSections as $sec): ?>
      <div class="nav-section-label"><?= e($sec['label']) ?></div>
      <?php foreach ($sec['items'] as $item): 
        $isActive = ($currentPage === $item['key'] || ($item['key'] === 'events' && $currentPage === 'event-detail'));
      ?>
        <div class="nav-item">
          <a href="<?= e($item['url']) ?>" class="nav-link <?= $isActive ? 'active' : '' ?>">
            <?= svgIcon($item['icon']) ?>
            <span><?= e($item['title']) ?></span>
            <?php if ($item['key'] === 'scanner'): ?>
              <span class="nav-badge" style="background:#059669">LIVE</span>
            <?php endif; ?>
          </a>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <?php if ($roleId === 1): ?>
      <a href="settings.php" class="sidebar-user" title="System Settings">
    <?php else: ?>
      <div class="sidebar-user" style="cursor:default;">
    <?php endif; ?>
      <div class="sidebar-avatar">
        <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
      </div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($user['name'] ?? 'User') ?></div>
        <div class="sidebar-user-role"><?= e($user['role'] ?? 'Staff') ?></div>
      </div>
    <?php if ($roleId === 1): ?>
      </a>
    <?php else: ?>
      </div>
    <?php endif; ?>
  </div>
</aside>

<!-- Main View Content Area -->
<div class="main-content">
  <!-- Topbar: Clean & Minimal (Contains ONLY Logout Button per System Requirements) -->
  <header class="topbar" style="display:flex;align-items:center;justify-content:space-between;padding:12px 24px;border-bottom:1px solid var(--border);background:var(--surface);">
    <button class="hamburger" id="sidebarToggle" aria-label="Toggle navigation">
      <span></span><span></span><span></span>
    </button>
    <div style="flex:1;"></div>
    <div class="topbar-actions">
      <a href="logout.php" id="globalLogoutBtn" class="btn btn-secondary btn-sm" title="Sign Out of Leula Events" style="display:inline-flex;align-items:center;gap:7px;color:#DC2626;border-color:rgba(220,38,38,0.25);background:rgba(220,38,38,0.06);font-weight:700;padding:6px 14px;border-radius:var(--radius-sm, 8px);">
        <?= svgIcon('logout', 16) ?>
        <span>Sign Out</span>
      </a>
    </div>
  </header>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var logoutBtn = document.getElementById('globalLogoutBtn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
          e.preventDefault();
          if (typeof showConfirmDialog === 'function') {
            showConfirmDialog({
              title: 'Sign Out',
              subtitle: 'Ending authenticated session',
              message: 'Are you sure you want to log out of Leula Events?',
              confirmText: 'Sign Out',
              cancelText: 'Cancel',
              type: 'warning'
            }).then(function(confirmed) {
              if (confirmed) {
                window.location.href = 'logout.php';
              }
            });
          } else {
            window.location.href = 'logout.php';
          }
        });
      }
    });
  </script>

  <!-- Page Content Container -->
  <div class="page-content">
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : 'success')) ?>">
        <?= svgIcon($flash['type'] === 'danger' ? 'alert' : 'check') ?>
        <div style="flex:1"><?= e($flash['message']) ?></div>
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          if (window.Toast) {
            window.Toast.<?= ($flash['type'] === 'danger' || $flash['type'] === 'error') ? 'error' : (($flash['type'] === 'warning') ? 'warning' : 'success') ?>(<?= json_encode($flash['message']) ?>);
          }
        });
      </script>
    <?php endif; ?>
