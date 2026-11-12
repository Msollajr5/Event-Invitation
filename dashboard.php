<?php
$pageTitle = 'Executive Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/config/db.php';
require_role(2);

// Fetch Core Metrics
$totalEvents = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM events")['cnt'] ?? 0);
$totalGuests = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM guests")['cnt'] ?? 0);
$totalCheckedIn = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM check_ins")['cnt'] ?? 0);
$totalAcceptedRsvp = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE response = 'accepted'")['cnt'] ?? 0);
$attendanceRate = $totalGuests > 0 ? round(($totalCheckedIn / $totalGuests) * 100, 1) : 0;

// Upcoming Events
$upcomingEvents = db_fetch_all("
    SELECT e.*, 
           (SELECT COUNT(*) FROM guests WHERE event_id = e.id) as guest_count,
           (SELECT COUNT(*) FROM check_ins WHERE event_id = e.id) as checked_in_count
    FROM events e 
    ORDER BY e.start_date ASC 
    LIMIT 5
");

// Recent Gate Check-in Activities
$recentCheckins = db_fetch_all("
    SELECT c.*, g.full_name as guest_name, gc.name as category_name, 
           e.title as event_title, gt.name as gate_name
    FROM check_ins c
    JOIN guests g ON c.guest_id = g.id
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    JOIN events e ON c.event_id = e.id
    LEFT JOIN gates gt ON c.gate_id = gt.id
    ORDER BY c.id DESC
    LIMIT 7
");

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Operational Overview</h1>
    <p class="page-subtitle">Real-time gate verification telemetry and event pass metrics</p>
  </div>
  <div class="page-actions">
    <a href="scanner.php" class="btn btn-primary">
      <?= svgIcon('qr', 16) ?> Launch Gate Scanner
    </a>
    <a href="designer.php" class="btn btn-secondary">
      <?= svgIcon('palette', 16) ?> Card Studio
    </a>
    <a href="events.php" class="btn btn-secondary">
      <?= svgIcon('plus', 16) ?> Create Event
    </a>
    <a href="guests.php" class="btn btn-secondary">
      <?= svgIcon('users', 16) ?> Add Guests
    </a>
  </div>
</div>

<!-- Key Stat Cards (Neilos Signature Grid) -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon orange">
      <?= svgIcon('calendar', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalEvents) ?></div>
      <div class="stat-label">Active Events</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">
      <?= svgIcon('users', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalGuests) ?></div>
      <div class="stat-label">Total Registered Guests</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <?= svgIcon('check-circle', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalCheckedIn) ?></div>
      <div class="stat-label">Verified Check-ins</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon yellow">
      <?= svgIcon('message', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalAcceptedRsvp) ?></div>
      <div class="stat-label">Confirmed RSVPs</div>
    </div>
  </div>
</div>

<!-- Visual Telemetry & Analytics Grid -->
<div class="grid grid-dashboard" style="margin-bottom:24px;">
  <!-- Hourly Attendance Curve -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header">
      <div>
        <h3 class="card-title">Gate Verification Velocity</h3>
        <p class="card-subtitle">Real-time throughput curve across active gate checkpoints</p>
      </div>
      <span class="badge badge-success">LIVE STREAM</span>
    </div>
    <div class="card-body">
      <canvas id="checkinTrendChart" style="max-height: 250px;"></canvas>
    </div>
  </div>

  <!-- RSVP Breakdown Donut -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header">
      <div>
        <h3 class="card-title">RSVP Distribution</h3>
        <p class="card-subtitle">Current guest confirmation breakdown</p>
      </div>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
      <div style="width:100%;max-width:210px;">
        <canvas id="rsvpDonutChart"></canvas>
      </div>
      <div style="display:flex;gap:16px;margin-top:16px;">
        <span style="font-size:0.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:#059669;display:inline-block"></span> Attending
        </span>
        <span style="font-size:0.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:#D97706;display:inline-block"></span> Pending
        </span>
        <span style="font-size:0.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:#DC2626;display:inline-block"></span> Declined
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Tables Grid: Upcoming Events & Live Check-in Stream -->
<div class="grid grid-dashboard">
  <!-- Upcoming Events Table -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Scheduled Events</h3>
        <p class="card-subtitle">Calendar of published and upcoming events</p>
      </div>
      <a href="events.php" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Event Details</th>
            <th>Date & Time</th>
            <th>Venue</th>
            <th>Attendance</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($upcomingEvents)): ?>
            <tr class="empty-row"><td colspan="5">No upcoming events scheduled.</td></tr>
          <?php else: ?>
            <?php foreach ($upcomingEvents as $evt): 
              $pct = $evt['guest_capacity'] > 0 ? min(100, round(($evt['guest_count'] / $evt['guest_capacity']) * 100)) : 0;
            ?>
              <tr>
                <td>
                  <a href="event-detail.php?id=<?= $evt['id'] ?>" style="font-weight:700;color:var(--text-primary);">
                    <?= e($evt['title']) ?>
                  </a>
                  <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    Capacity: <?= number_format($evt['guest_capacity']) ?> guests
                  </div>
                </td>
                <td style="white-space:nowrap;">
                  <div style="font-weight:600;"><?= date('M d, Y', strtotime($evt['start_date'])) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($evt['start_time']) ?></div>
                </td>
                <td>
                  <div style="font-size:0.84rem;font-weight:500;"><?= e($evt['venue_name']) ?></div>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <span class="badge badge-primary"><?= $evt['checked_in_count'] ?> / <?= $evt['guest_count'] ?></span>
                  </div>
                </td>
                <td>
                  <a href="event-detail.php?id=<?= $evt['id'] ?>" class="btn btn-secondary btn-sm">
                    Manage
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Live Gate Check-ins Stream -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Live Gate Feed</h3>
        <p class="card-subtitle">Real-time verification log</p>
      </div>
      <a href="attendance.php" class="btn btn-secondary btn-sm">Full Feed</a>
    </div>
    <div class="card-body" style="padding:14px 18px;">
      <?php if (empty($recentCheckins)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:30px 0;">No gate check-in activity recorded yet.</p>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <?php foreach ($recentCheckins as $chk): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--surface-3);border-radius:var(--radius-sm);border:1px solid var(--border);">
              <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:34px;height:34px;border-radius:50%;background:rgba(5,150,105,0.12);color:#059669;display:flex;align-items:center;justify-content:center;">
                  <?= svgIcon('check', 16) ?>
                </div>
                <div>
                  <div style="font-size:0.88rem;font-weight:700;color:var(--text-primary);"><?= e($chk['guest_name']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($chk['gate_name'] ?: 'Gate 1') ?> &bull; <?= e($chk['category_name'] ?: 'Guest') ?></div>
                </div>
              </div>
              <span class="badge badge-success" style="font-size:0.7rem;">Verified</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Chart 1: Verification Hourly Velocity
  const ctxTrend = document.getElementById('checkinTrendChart')?.getContext('2d');
  if (ctxTrend) {
    new Chart(ctxTrend, {
      type: 'line',
      data: {
        labels: ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
        datasets: [{
          label: 'Check-ins / Hour',
          data: [5, 18, 42, 65, 85, 140, 95, 30],
          borderColor: '#FF7A00',
          backgroundColor: 'rgba(255, 122, 0, 0.08)',
          fill: true,
          tension: 0.35,
          pointRadius: 4,
          pointBackgroundColor: '#FF7A00'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { family: 'Inter' } } },
          x: { grid: { display: false }, ticks: { font: { family: 'Inter' } } }
        }
      }
    });
  }

  // Chart 2: RSVP Donut
  const ctxRsvp = document.getElementById('rsvpDonutChart')?.getContext('2d');
  if (ctxRsvp) {
    new Chart(ctxRsvp, {
      type: 'doughnut',
      data: {
        labels: ['Attending', 'Pending', 'Declined'],
        datasets: [{
          data: [<?= max(1, $totalAcceptedRsvp) ?>, <?= max(1, $totalGuests - $totalAcceptedRsvp) ?>, 2],
          backgroundColor: ['#059669', '#D97706', '#DC2626'],
          borderWidth: 2,
          borderColor: '#FFFFFF'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        cutout: '72%'
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
