<?php
$pageTitle = 'Notifications & Audit Trail';
$currentPage = 'notifications';
require_once __DIR__ . '/config/db.php';
require_auth();

$user = auth_user();

// Fetch Live Audit Logs from DB
$auditLogs = db_fetch_all("
    SELECT a.*, u.name as user_name 
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC 
    LIMIT 60
");

$systemAlerts = [
    [
        'title' => 'Gate Verification Stations Online',
        'message' => 'Check-in terminals connected with real-time anti-passback and reversal logging.',
        'time' => 'Live Telemetry',
        'type' => 'success',
        'icon' => 'check-circle'
    ],
    [
        'title' => 'Reversible RSVP Protocol Active',
        'message' => 'Guests can seamlessly update their attendance responses (Accepted ⇄ Declined) with instant database sync.',
        'time' => 'Active',
        'type' => 'info',
        'icon' => 'message'
    ],
    [
        'title' => 'Anti-Passback Duplicate Enforcement',
        'message' => 'Duplicate scan enforcement is active across all gates. Duplicate passes trigger red alerts with one-click authorized reversal.',
        'time' => 'Security Active',
        'type' => 'warning',
        'icon' => 'shield'
    ]
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Notifications & Security Audit Trail</h1>
    <p class="page-subtitle">Historical log of RSVP adjustments, gate admissions, check-in reversals, and administrative events</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-secondary" onclick="window.Toast.success('All system notifications marked as reviewed.')">
      <?= svgIcon('check', 16) ?> Mark Alerts Read
    </button>
  </div>
</div>

<!-- System Status Alerts -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3 class="card-title">Live System Status & Security Rules</h3>
  </div>
  <div class="card-body" style="padding:10px 24px;">
    <?php foreach ($systemAlerts as $n): ?>
      <div style="display:flex;align-items:flex-start;gap:16px;padding:16px 0;border-bottom:1px solid var(--border);">
        <div style="width:40px;height:40px;border-radius:12px;background:var(--accent-pale);color:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <?= svgIcon($n['icon'], 20) ?>
        </div>
        <div style="flex:1;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <strong style="font-size:0.95rem;color:var(--text-primary);"><?= e($n['title']) ?></strong>
            <span style="font-size:0.75rem;color:var(--text-muted);font-weight:600;"><?= e($n['time']) ?></span>
          </div>
          <p style="font-size:0.86rem;color:var(--text-secondary);margin:0;line-height:1.5;">
            <?= e($n['message']) ?>
          </p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($user && ($user['role_id'] ?? 3) <= 2): ?>
<!-- Historical Security & Action Audit Log Table -->
<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
      <h3 class="card-title">Action History & Reversible Audit Trail</h3>
      <p class="card-subtitle">Permanent audit log preserving RSVP updates, check-ins, reversals, and user changes</p>
    </div>
    <div class="search-box" style="max-width:260px;">
      <?= svgIcon('search', 14) ?>
      <input type="text" placeholder="Filter audit trail..." data-table-search="#auditTable">
    </div>
  </div>

  <div class="table-responsive">
    <table class="data-table" id="auditTable">
      <thead>
        <tr>
          <th>Event / Action</th>
          <th>Description & Justification</th>
          <th>Actor / Officer</th>
          <th>IP Address</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($auditLogs)): ?>
          <tr class="empty-row">
            <td colspan="5">No audit log entries recorded yet. Actions like RSVP changes and gate check-ins will appear here.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($auditLogs as $log): 
            $badgeColor = '#FF7A00';
            $badgeBg = 'rgba(255,122,0,0.1)';
            if (str_contains($log['event_type'], 'CHECKIN_REVERSAL')) {
                $badgeColor = '#DC2626';
                $badgeBg = 'rgba(220,38,38,0.12)';
            } elseif (str_contains($log['event_type'], 'GATE_CHECKIN')) {
                $badgeColor = '#059669';
                $badgeBg = 'rgba(5,150,105,0.12)';
            } elseif (str_contains($log['event_type'], 'RSVP')) {
                $badgeColor = '#2563EB';
                $badgeBg = 'rgba(37,99,235,0.12)';
            }
          ?>
            <tr>
              <td style="white-space:nowrap;">
                <span class="badge" style="background:<?= $badgeBg ?>;color:<?= $badgeColor ?>;border:1px solid <?= $badgeColor ?>30;font-weight:700;">
                  <?= e($log['event_type']) ?>
                </span>
                <div style="font-size:0.72rem;color:var(--text-muted);margin-top:3px;font-family:monospace;">
                  <?= e($log['action']) ?>
                </div>
              </td>
              <td>
                <div style="font-size:0.86rem;color:var(--text-primary);line-height:1.45;font-weight:500;">
                  <?= e($log['description']) ?>
                </div>
              </td>
              <td style="white-space:nowrap;">
                <div style="font-weight:600;font-size:0.84rem;color:var(--text-primary);">
                  <?= e($log['user_name'] ?: 'Guest / Self-Service') ?>
                </div>
                <?php if ($log['user_id']): ?>
                  <div style="font-size:0.72rem;color:var(--text-muted);">Staff ID #<?= (int)$log['user_id'] ?></div>
                <?php endif; ?>
              </td>
              <td style="white-space:nowrap;font-size:0.78rem;font-family:monospace;color:var(--text-secondary);">
                <?= e($log['ip_address'] ?: '127.0.0.1') ?>
              </td>
              <td style="white-space:nowrap;font-size:0.8rem;color:var(--text-secondary);">
                <?= e($log['created_at']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
