<?php
$pageTitle = 'Executive Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/config/db.php';
require_role(2);

$userEvents = get_user_events();
$selectedEventId = get_active_event_id() ?? 1;
require_event_access($selectedEventId);

$currentEvent = db_fetch_one("SELECT * FROM events WHERE id = ?", [$selectedEventId]);

// 1. Event-Specific Guest & Category Metrics
$totalGuests = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM guests WHERE event_id = :id AND deleted_at IS NULL", ['id' => $selectedEventId])['cnt'] ?? 0);

$vvipGuests = (int)(db_fetch_one("
    SELECT COUNT(*) as cnt FROM guests g 
    JOIN guest_categories gc ON g.guest_category_id = gc.id 
    WHERE g.event_id = :id AND gc.slug = 'vvip' AND g.deleted_at IS NULL
", ['id' => $selectedEventId])['cnt'] ?? 0);

$vipGuests = (int)(db_fetch_one("
    SELECT COUNT(*) as cnt FROM guests g 
    JOIN guest_categories gc ON g.guest_category_id = gc.id 
    WHERE g.event_id = :id AND gc.slug = 'vip' AND g.deleted_at IS NULL
", ['id' => $selectedEventId])['cnt'] ?? 0);

$regularGuests = (int)(db_fetch_one("
    SELECT COUNT(*) as cnt FROM guests g 
    JOIN guest_categories gc ON g.guest_category_id = gc.id 
    WHERE g.event_id = :id AND gc.slug = 'regular' AND g.deleted_at IS NULL
", ['id' => $selectedEventId])['cnt'] ?? 0);

$otherGuests = max(0, $totalGuests - ($vvipGuests + $vipGuests + $regularGuests));

// 2. Invitations & Check-ins
$totalInvitations = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM invitations WHERE event_id = :id", ['id' => $selectedEventId])['cnt'] ?? 0);
$totalCheckedIn = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM check_ins WHERE event_id = :id", ['id' => $selectedEventId])['cnt'] ?? 0);
$remainingGuests = max(0, $totalGuests - $totalCheckedIn);

// 3. RSVP Breakdown
$acceptedRsvp = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'accepted'", ['id' => $selectedEventId])['cnt'] ?? 0);
$declinedRsvp = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'declined'", ['id' => $selectedEventId])['cnt'] ?? 0);
$pendingRsvp = max(0, $totalGuests - ($acceptedRsvp + $declinedRsvp));

$attendanceRate = $totalGuests > 0 ? round(($totalCheckedIn / $totalGuests) * 100, 1) : 0;

// 4. Attendance Breakdown by Category
$categoryBreakdown = db_fetch_all("
    SELECT gc.name, gc.color, COUNT(g.id) as guest_count,
           COUNT(c.id) as checkin_count
    FROM guest_categories gc
    JOIN guests g ON g.guest_category_id = gc.id AND g.event_id = :eid AND g.deleted_at IS NULL
    LEFT JOIN check_ins c ON c.guest_id = g.id AND c.event_id = :eid2
    GROUP BY gc.id, gc.name, gc.color
    ORDER BY guest_count DESC
", ['eid' => $selectedEventId, 'eid2' => $selectedEventId]);

// 5. Recent Gate Check-in Activities for This Event
$recentCheckins = db_fetch_all("
    SELECT c.*, g.full_name as guest_name, gc.name as category_name, gc.color as category_color,
           e.title as event_title, gt.name as gate_name
    FROM check_ins c
    JOIN guests g ON c.guest_id = g.id
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    JOIN events e ON c.event_id = e.id
    LEFT JOIN gates gt ON c.gate_id = gt.id
    WHERE c.event_id = :id
    ORDER BY c.id DESC
    LIMIT 8
", ['id' => $selectedEventId]);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header" style="margin-bottom:20px;">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
      <span class="badge badge-primary"><?= e($currentEvent['title'] ?? 'Event') ?></span>
      <span style="font-size:0.8rem;color:var(--text-muted);">&bull; <?= date('M d, Y', strtotime($currentEvent['start_date'] ?? 'now')) ?> &bull; <?= e($currentEvent['venue_name'] ?? '') ?></span>
    </div>
    <h1 class="page-title">Event Operations &amp; Verification Telemetry</h1>
    <p class="page-subtitle">Real-time attendance velocity, RSVP responses, and access tier breakdown</p>
  </div>
  <div class="page-actions">
    <a href="scanner.php?event_id=<?= $selectedEventId ?>" class="btn btn-primary">
      <?= svgIcon('qr', 16) ?> Launch Gate Scanner
    </a>
    <a href="designer.php?event_id=<?= $selectedEventId ?>" class="btn btn-secondary">
      <?= svgIcon('palette', 16) ?> Card Studio
    </a>
    <a href="guests.php?event_id=<?= $selectedEventId ?>" class="btn btn-secondary">
      <?= svgIcon('users', 16) ?> Guest Directory
    </a>
    <a href="reports.php?event_id=<?= $selectedEventId ?>" class="btn btn-secondary">
      <?= svgIcon('chart', 16) ?> Detailed Reports
    </a>
  </div>
</div>

<!-- Primary Stats Grid (Event Scoped) -->
<div class="stats-grid" style="margin-bottom:20px;">
  <div class="stat-card">
    <div class="stat-icon blue">
      <?= svgIcon('users', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalGuests) ?></div>
      <div class="stat-label">Total Enrolled Guests</div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
        VVIP: <strong><?= $vvipGuests ?></strong> &bull; VIP: <strong><?= $vipGuests ?></strong> &bull; Reg: <strong><?= $regularGuests ?></strong>
      </div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <?= svgIcon('check-circle', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalCheckedIn) ?></div>
      <div class="stat-label">Verified Check-ins</div>
      <div style="font-size:0.75rem;color:var(--success);font-weight:700;margin-top:2px;">
        <?= $attendanceRate ?>% Venue Turnout
      </div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon yellow">
      <?= svgIcon('message', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($acceptedRsvp) ?></div>
      <div class="stat-label">Confirmed RSVP Attendance</div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
        Declined: <strong><?= $declinedRsvp ?></strong> &bull; Pending: <strong><?= $pendingRsvp ?></strong>
      </div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon orange">
      <?= svgIcon('ticket', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($remainingGuests) ?></div>
      <div class="stat-label">Remaining Expected</div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
        Passes Issued: <strong><?= number_format($totalInvitations) ?></strong>
      </div>
    </div>
  </div>
</div>

<!-- Category Breakdown & RSVP Telemetry Grid -->
<div class="grid grid-dashboard" style="margin-bottom:24px;">
  <!-- Attendance Breakdown by Category Table -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h3 class="card-title">Category Ingress Telemetry</h3>
        <p class="card-subtitle">Real-time attendance breakdown by security tier</p>
      </div>
      <span class="badge badge-success">ACTIVE EVENT</span>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Security Category</th>
            <th>Invited</th>
            <th>Admitted</th>
            <th>Turnout Rate</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categoryBreakdown)): ?>
            <tr class="empty-row"><td colspan="4">No categorized attendees recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach ($categoryBreakdown as $cat): 
              $rate = $cat['guest_count'] > 0 ? round(($cat['checkin_count'] / $cat['guest_count']) * 100, 1) : 0;
            ?>
              <tr>
                <td>
                  <span class="badge" style="background:<?= e($cat['color'] ?: '#3b82f6') ?>18;color:<?= e($cat['color'] ?: '#3b82f6') ?>;border:1px solid <?= e($cat['color'] ?: '#3b82f6') ?>40;font-weight:700;">
                    <?= e($cat['name']) ?>
                  </span>
                </td>
                <td><strong><?= number_format($cat['guest_count']) ?></strong></td>
                <td><strong style="color:var(--success);"><?= number_format($cat['checkin_count']) ?></strong></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex:1;max-width:90px;height:6px;background:var(--surface-3);border-radius:99px;overflow:hidden;">
                      <div style="height:100%;background:<?= e($cat['color'] ?: '#FF7A00') ?>;width:<?= $rate ?>%;"></div>
                    </div>
                    <span style="font-size:0.8rem;font-weight:700;"><?= $rate ?>%</span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- RSVP Breakdown Donut -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header">
      <div>
        <h3 class="card-title">RSVP Distribution Ratio</h3>
        <p class="card-subtitle">Confirmed vs Declined vs Pending for this event</p>
      </div>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
      <div style="width:100%;max-width:210px;">
        <canvas id="rsvpDonutChart"></canvas>
      </div>
      <div style="display:flex;gap:16px;margin-top:16px;flex-wrap:wrap;justify-content:center;">
        <span style="font-size:0.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:#059669;display:inline-block"></span> Attending (<?= $acceptedRsvp ?>)
        </span>
        <span style="font-size:0.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:#D97706;display:inline-block"></span> Pending (<?= $pendingRsvp ?>)
        </span>
        <span style="font-size:0.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:#DC2626;display:inline-block"></span> Declined (<?= $declinedRsvp ?>)
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Live Check-in Stream for This Event -->
<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
      <h3 class="card-title">Live Gate Admission Stream</h3>
      <p class="card-subtitle">Real-time attendee arrivals for <?= e($currentEvent['title'] ?? 'this event') ?></p>
    </div>
    <a href="scanner.php?event_id=<?= $selectedEventId ?>" class="btn btn-secondary btn-sm">Open Scanner</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Guest Name</th>
          <th>Access Tier</th>
          <th>Admission Gate</th>
          <th>Attendee Count</th>
          <th>Verification Time</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentCheckins)): ?>
          <tr class="empty-row"><td colspan="6">No check-ins scanned for this event yet. Use the Gate Scanner to verify passes.</td></tr>
        <?php else: ?>
          <?php foreach ($recentCheckins as $chk): ?>
            <tr>
              <td><strong><?= e($chk['guest_name']) ?></strong></td>
              <td>
                <span class="badge" style="background:<?= e($chk['category_color'] ?: '#3b82f6') ?>18;color:<?= e($chk['category_color'] ?: '#3b82f6') ?>;border:1px solid <?= e($chk['category_color'] ?: '#3b82f6') ?>40;font-weight:700;">
                  <?= e($chk['category_name'] ?: 'Regular') ?>
                </span>
              </td>
              <td><?= e($chk['gate_name'] ?: 'Main Gate') ?></td>
              <td><span class="badge badge-secondary"><?= $chk['attendee_count'] ?> person(s)</span></td>
              <td style="font-size:0.82rem;color:var(--text-secondary);"><?= date('M d, Y H:i:s', strtotime($chk['checked_in_at'])) ?></td>
              <td><span class="badge badge-success"><?= svgIcon('check', 12) ?> Admitted</span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // RSVP Donut Chart
  const rsvpCtx = document.getElementById('rsvpDonutChart');
  if (rsvpCtx) {
    new Chart(rsvpCtx, {
      type: 'doughnut',
      data: {
        labels: ['Attending', 'Pending', 'Declined'],
        datasets: [{
          data: [<?= $acceptedRsvp ?>, <?= $pendingRsvp ?>, <?= $declinedRsvp ?>],
          backgroundColor: ['#059669', '#D97706', '#DC2626'],
          borderWidth: 0
        }]
      },
      options: {
        cutout: '72%',
        responsive: true,
        plugins: {
          legend: { display: false }
        }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
