<?php
$pageTitle = 'Reports & Analytics';
$currentPage = 'reports';
require_once __DIR__ . '/config/db.php';
require_role(2);

$selectedEventId = (int)($_GET['event_id'] ?? 1);
$allEvents = db_fetch_all("SELECT id, title FROM events ORDER BY start_date DESC");

// Metrics
$totalGuests = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM guests WHERE event_id = :id", ['id' => $selectedEventId])['cnt'] ?? 0);
$totalCheckedIn = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM check_ins WHERE event_id = :id", ['id' => $selectedEventId])['cnt'] ?? 0);
$totalRsvps = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id", ['id' => $selectedEventId])['cnt'] ?? 0);
$acceptedRsvps = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'accepted'", ['id' => $selectedEventId])['cnt'] ?? 0);

// Gate Performance
$gateStats = db_fetch_all("
    SELECT g.name, g.code, COUNT(c.id) as scans,
           MIN(c.checked_in_at) as first_scan,
           MAX(c.checked_in_at) as last_scan
    FROM gates g
    LEFT JOIN check_ins c ON c.gate_id = g.id
    WHERE g.event_id = :id
    GROUP BY g.id, g.name, g.code
", ['id' => $selectedEventId]);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Reports & Analytics</h1>
    <p class="page-subtitle">Post-event attendance analysis, gate throughput efficiency and conversion rates</p>
  </div>
  <div class="page-actions">
    <form method="GET" action="reports.php" style="display:flex;gap:10px;align-items:center;">
      <select name="event_id" class="form-control" style="width:auto;min-width:240px;" onchange="this.form.submit()">
        <?php foreach ($allEvents as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $selectedEventId ? 'selected' : '' ?>>
            <?= e($ev['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="guests.php?event_id=<?= $selectedEventId ?>&action=export_csv" class="btn btn-secondary">
      <?= svgIcon('download', 16) ?> Export Attendance Log
    </a>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon orange">
      <?= svgIcon('users', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalGuests) ?></div>
      <div class="stat-label">Total Guest Callset</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <?= svgIcon('check-circle', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalCheckedIn) ?></div>
      <div class="stat-label">Actual Turnout Ingress</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">
      <?= svgIcon('chart', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $totalGuests > 0 ? round(($totalCheckedIn / $totalGuests) * 100, 1) : 0 ?>%</div>
      <div class="stat-label">Show-up Conversion</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon yellow">
      <?= svgIcon('message', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($acceptedRsvps) ?></div>
      <div class="stat-label">Confirmed RSVPs</div>
    </div>
  </div>
</div>

<!-- Gate Performance Card -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Gate Verification Workstation Performance</h3>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Checkpoint Gate</th>
          <th>Gate Identifier</th>
          <th>Total Attendees Admitted</th>
          <th>Ingress Share</th>
          <th>First Verification</th>
          <th>Last Verification</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($gateStats)): ?>
          <tr class="empty-row"><td colspan="6">No gate statistics available for this event.</td></tr>
        <?php else: ?>
          <?php foreach ($gateStats as $gs): 
            $pct = $totalCheckedIn > 0 ? round(($gs['scans'] / $totalCheckedIn) * 100, 1) : 0;
          ?>
            <tr>
              <td><strong><?= e($gs['name']) ?></strong></td>
              <td><code><?= e($gs['code']) ?></code></td>
              <td><span class="badge badge-success"><?= number_format($gs['scans']) ?> pax</span></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="flex:1;max-width:100px;height:6px;background:var(--surface-3);border-radius:99px;overflow:hidden;">
                    <div style="height:100%;background:#FF7A00;width:<?= $pct ?>%;"></div>
                  </div>
                  <span style="font-size:0.8rem;font-weight:600;"><?= $pct ?>%</span>
                </div>
              </td>
              <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($gs['first_scan'] ?: '—') ?></td>
              <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($gs['last_scan'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
