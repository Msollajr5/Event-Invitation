<?php
$pageTitle = 'Reports & Analytics';
$currentPage = 'reports';
require_once __DIR__ . '/config/db.php';
require_role(2);

$userEvents = get_user_events();
$selectedEventId = (int)($_GET['event_id'] ?? get_active_event_id() ?? 1);
require_event_access($selectedEventId);

$currentEvent = db_fetch_one("SELECT * FROM events WHERE id = ?", [$selectedEventId]);
$categories = db_fetch_all("SELECT * FROM guest_categories ORDER BY id ASC");

// Filter Parameters
$categoryFilter = $_GET['category_id'] ?? '';
$rsvpFilter = $_GET['rsvp_status'] ?? '';
$checkinFilter = $_GET['checkin_status'] ?? '';

// Build Query Filters
$whereConditions = ["g.event_id = :eid", "g.deleted_at IS NULL"];
$params = ['eid' => $selectedEventId];

if (!empty($categoryFilter)) {
    $whereConditions[] = "g.guest_category_id = :cid";
    $params['cid'] = (int)$categoryFilter;
}

if ($rsvpFilter === 'accepted') {
    $whereConditions[] = "EXISTS (SELECT 1 FROM rsvp_records WHERE guest_id = g.id AND response = 'accepted')";
} elseif ($rsvpFilter === 'declined') {
    $whereConditions[] = "EXISTS (SELECT 1 FROM rsvp_records WHERE guest_id = g.id AND response = 'declined')";
} elseif ($rsvpFilter === 'pending') {
    $whereConditions[] = "NOT EXISTS (SELECT 1 FROM rsvp_records WHERE guest_id = g.id)";
}

if ($checkinFilter === 'checked_in') {
    $whereConditions[] = "EXISTS (SELECT 1 FROM check_ins WHERE guest_id = g.id)";
} elseif ($checkinFilter === 'not_checked_in') {
    $whereConditions[] = "NOT EXISTS (SELECT 1 FROM check_ins WHERE guest_id = g.id)";
}

$whereSql = implode(' AND ', $whereConditions);

// Handle Filtered CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_report_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_report_event_' . $selectedEventId . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Guest ID', 'Full Name', 'Category', 'Email', 'Phone', 'Permitted Attendees', 'RSVP Status', 'Checked In', 'Gate', 'Check-in Time']);

    $exportRows = db_fetch_all("
        SELECT g.*, gc.name as category_name,
               (SELECT response FROM rsvp_records WHERE guest_id = g.id ORDER BY id DESC LIMIT 1) as rsvp_status,
               (SELECT checked_in_at FROM check_ins WHERE guest_id = g.id ORDER BY id DESC LIMIT 1) as checked_in_time,
               (SELECT gt.name FROM check_ins c LEFT JOIN gates gt ON c.gate_id = gt.id WHERE c.guest_id = g.id ORDER BY c.id DESC LIMIT 1) as gate_name
        FROM guests g
        LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
        WHERE {$whereSql}
        ORDER BY g.full_name ASC
    ", $params);

    foreach ($exportRows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['full_name'],
            $r['category_name'] ?: 'Regular',
            $r['email'],
            $r['phone'],
            $r['permitted_attendees'],
            strtoupper($r['rsvp_status'] ?: 'PENDING'),
            !empty($r['checked_in_time']) ? 'YES' : 'NO',
            $r['gate_name'] ?: '—',
            $r['checked_in_time'] ?: '—'
        ]);
    }
    fclose($out);
    exit;
}

// Core Metrics for this event
$totalGuests = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM guests WHERE event_id = :id AND deleted_at IS NULL", ['id' => $selectedEventId])['cnt'] ?? 0);
$totalCheckedIn = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM check_ins WHERE event_id = :id", ['id' => $selectedEventId])['cnt'] ?? 0);
$acceptedRsvps = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'accepted'", ['id' => $selectedEventId])['cnt'] ?? 0);
$declinedRsvps = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'declined'", ['id' => $selectedEventId])['cnt'] ?? 0);
$pendingRsvps = max(0, $totalGuests - ($acceptedRsvps + $declinedRsvps));
$conversionRate = $totalGuests > 0 ? round(($totalCheckedIn / $totalGuests) * 100, 1) : 0;

// Gate Performance
$gateStats = db_fetch_all("
    SELECT g.name, g.code, g.allowed_categories, COUNT(c.id) as scans,
           MIN(c.checked_in_at) as first_scan,
           MAX(c.checked_in_at) as last_scan
    FROM gates g
    LEFT JOIN check_ins c ON c.gate_id = g.id
    WHERE g.event_id = :id
    GROUP BY g.id, g.name, g.code, g.allowed_categories
", ['id' => $selectedEventId]);

// Filtered Report Guests
$reportGuests = db_fetch_all("
    SELECT g.*, gc.name as category_name, gc.color as category_color,
           i.reference_code,
           (SELECT response FROM rsvp_records WHERE guest_id = g.id ORDER BY id DESC LIMIT 1) as rsvp_status,
           (SELECT checked_in_at FROM check_ins WHERE guest_id = g.id ORDER BY id DESC LIMIT 1) as checked_in_time,
           (SELECT gt.name FROM check_ins c LEFT JOIN gates gt ON c.gate_id = gt.id WHERE c.guest_id = g.id ORDER BY c.id DESC LIMIT 1) as gate_name
    FROM guests g
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    LEFT JOIN invitations i ON i.guest_id = g.id
    WHERE {$whereSql}
    ORDER BY g.full_name ASC
    LIMIT 100
", $params);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header" style="margin-bottom:20px;">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
      <span class="badge badge-primary"><?= e($currentEvent['title'] ?? 'Event') ?></span>
      <span style="font-size:0.8rem;color:var(--text-muted);">&bull; Attendance Analytics &amp; Roster Audit</span>
    </div>
    <h1 class="page-title">Executive Reporting &amp; Gate Throughput</h1>
    <p class="page-subtitle">Multi-criteria filtering, attendance conversion, and gate ingress performance</p>
  </div>
  <div class="page-actions">
    <a href="reports.php?event_id=<?= $selectedEventId ?>&category_id=<?= urlencode($categoryFilter) ?>&rsvp_status=<?= urlencode($rsvpFilter) ?>&checkin_status=<?= urlencode($checkinFilter) ?>&action=export_report_csv" class="btn btn-primary">
      <?= svgIcon('download', 16) ?> Export Filtered Report CSV
    </a>
  </div>
</div>

<!-- Event Metrics Grid -->
<div class="stats-grid" style="margin-bottom:20px;">
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
      <div class="stat-value"><?= $conversionRate ?>%</div>
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
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
        Declined: <strong><?= $declinedRsvps ?></strong> &bull; Pending: <strong><?= $pendingRsvps ?></strong>
      </div>
    </div>
  </div>
</div>

<!-- Multi-Filter Toolbar -->
<div class="card" style="padding:16px 20px;margin-bottom:20px;background:var(--surface-3, #f8f9fa);border:1px solid var(--border-color);">
  <form method="GET" action="reports.php" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <input type="hidden" name="event_id" value="<?= $selectedEventId ?>">

    <div style="display:flex;align-items:center;gap:6px;">
      <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Category:</span>
      <select name="category_id" class="form-control" style="font-size:0.84rem;padding:6px 12px;width:auto;" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= ((string)$cat['id'] === (string)$categoryFilter) ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex;align-items:center;gap:6px;">
      <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">RSVP:</span>
      <select name="rsvp_status" class="form-control" style="font-size:0.84rem;padding:6px 12px;width:auto;" onchange="this.form.submit()">
        <option value="">All RSVP States</option>
        <option value="accepted" <?= ($rsvpFilter === 'accepted') ? 'selected' : '' ?>>Confirmed Attending</option>
        <option value="declined" <?= ($rsvpFilter === 'declined') ? 'selected' : '' ?>>Declined</option>
        <option value="pending" <?= ($rsvpFilter === 'pending') ? 'selected' : '' ?>>Pending Response</option>
      </select>
    </div>

    <div style="display:flex;align-items:center;gap:6px;">
      <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Gate Status:</span>
      <select name="checkin_status" class="form-control" style="font-size:0.84rem;padding:6px 12px;width:auto;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="checked_in" <?= ($checkinFilter === 'checked_in') ? 'selected' : '' ?>>Checked In at Gate</option>
        <option value="not_checked_in" <?= ($checkinFilter === 'not_checked_in') ? 'selected' : '' ?>>Not Checked In</option>
      </select>
    </div>

    <?php if (!empty($categoryFilter) || !empty($rsvpFilter) || !empty($checkinFilter)): ?>
      <a href="reports.php?event_id=<?= $selectedEventId ?>" class="btn btn-secondary btn-sm" style="font-size:0.8rem;">
        Clear Filters
      </a>
    <?php endif; ?>
  </form>
</div>

<!-- Gate Performance Card -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3 class="card-title">Gate Verification Workstation Performance</h3>
    <p class="card-subtitle">Throughput and category distribution across entrances</p>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Checkpoint Gate</th>
          <th>Gate Identifier</th>
          <th>Allowed Categories</th>
          <th>Attendees Admitted</th>
          <th>Ingress Share</th>
          <th>First Verification</th>
          <th>Last Verification</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($gateStats)): ?>
          <tr class="empty-row"><td colspan="7">No gate statistics recorded for this event.</td></tr>
        <?php else: ?>
          <?php foreach ($gateStats as $gs): 
            $pct = $totalCheckedIn > 0 ? round(($gs['scans'] / $totalCheckedIn) * 100, 1) : 0;
          ?>
            <tr>
              <td><strong><?= e($gs['name']) ?></strong></td>
              <td><code><?= e($gs['code']) ?></code></td>
              <td>
                <span class="badge badge-info" style="font-size:0.75rem;">
                  <?= strtoupper(e($gs['allowed_categories'] ?? 'ALL')) ?>
                </span>
              </td>
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

<!-- Filtered Guest Records Table -->
<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
      <h3 class="card-title">Attendee Audit &amp; Attendance Records</h3>
      <p class="card-subtitle">Showing <?= count($reportGuests) ?> matching attendees based on active filters</p>
    </div>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Guest Name</th>
          <th>Security Category</th>
          <th>Reference Code</th>
          <th>RSVP Response</th>
          <th>Gate Admission</th>
          <th>Check-in Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reportGuests)): ?>
          <tr class="empty-row"><td colspan="6">No attendees matched the selected filters.</td></tr>
        <?php else: ?>
          <?php foreach ($reportGuests as $rg): ?>
            <tr>
              <td><strong><?= e($rg['full_name']) ?></strong></td>
              <td>
                <span class="badge" style="background:<?= e($rg['category_color'] ?: '#3b82f6') ?>18;color:<?= e($rg['category_color'] ?: '#3b82f6') ?>;border:1px solid <?= e($rg['category_color'] ?: '#3b82f6') ?>40;font-weight:700;">
                  <?= e($rg['category_name'] ?: 'Regular') ?>
                </span>
              </td>
              <td><code><?= e($rg['reference_code'] ?: '—') ?></code></td>
              <td>
                <?php if ($rg['rsvp_status'] === 'accepted'): ?>
                  <span class="badge badge-success">Accepted</span>
                <?php elseif ($rg['rsvp_status'] === 'declined'): ?>
                  <span class="badge badge-danger">Declined</span>
                <?php else: ?>
                  <span class="badge badge-warning">Pending</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($rg['checked_in_time'])): ?>
                  <span class="badge badge-success"><?= svgIcon('check', 12) ?> <?= e($rg['gate_name'] ?: 'Gate') ?></span>
                <?php else: ?>
                  <span class="badge badge-secondary">Not Checked In</span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.82rem;color:var(--text-secondary);">
                <?= !empty($rg['checked_in_time']) ? date('M d, Y H:i:s', strtotime($rg['checked_in_time'])) : '—' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
