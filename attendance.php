<?php
$pageTitle = 'Live Attendance Monitor';
$currentPage = 'attendance';
require_once __DIR__ . '/config/db.php';
require_auth();

$userEvents = get_user_events();
$eventId = (int)($_GET['event_id'] ?? get_active_event_id() ?? 1);
require_event_access($eventId);
$allEvents = $userEvents;

// Metrics
$totalGuests = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM guests WHERE event_id = :id", ['id' => $eventId])['cnt'] ?? 0);
$totalCheckedIn = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM check_ins WHERE event_id = :id", ['id' => $eventId])['cnt'] ?? 0);
$checkInRate = $totalGuests > 0 ? round(($totalCheckedIn / $totalGuests) * 100, 1) : 0;

// Gate Breakdown
$gates = db_fetch_all("
    SELECT g.*, 
           (SELECT COUNT(*) FROM check_ins WHERE gate_id = g.id) as scan_count
    FROM gates g
    WHERE g.event_id = :id
    ORDER BY g.id ASC
", ['id' => $eventId]);

// Check-in Records
$checkinLog = db_fetch_all("
    SELECT c.*, g.full_name as guest_name, gc.name as category_name, 
           gt.name as gate_name, u.name as officer_name
    FROM check_ins c
    JOIN guests g ON c.guest_id = g.id
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    LEFT JOIN gates gt ON c.gate_id = gt.id
    LEFT JOIN users u ON c.verified_by = u.id
    WHERE c.event_id = :id
    ORDER BY c.id DESC
", ['id' => $eventId]);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
      <span class="badge badge-success">LIVE TELEMETRY</span>
      <span style="font-size:0.8rem;color:var(--text-muted);">Auto-refreshes every 15s</span>
    </div>
    <h1 class="page-title">Live Attendance Monitor</h1>
    <p class="page-subtitle">Real-time gate ingress throughput, turnstile telemetry and headcounts</p>
  </div>

  <div class="page-actions">
    <form method="GET" action="attendance.php" style="display:flex;gap:10px;align-items:center;">
      <select name="event_id" class="form-control" style="width:auto;min-width:240px;" onchange="this.form.submit()">
        <?php foreach ($allEvents as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $eventId ? 'selected' : '' ?>>
            <?= e($ev['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="scanner.php?event_id=<?= $eventId ?>" class="btn btn-primary">
      <?= svgIcon('qr', 16) ?> Launch Gate Scanner
    </a>
  </div>
</div>

<!-- Attendance Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon green">
      <?= svgIcon('check-circle', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalCheckedIn) ?></div>
      <div class="stat-label">Verified Attendees In Venue</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon orange">
      <?= svgIcon('users', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalGuests) ?></div>
      <div class="stat-label">Expected Guest Capacity</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">
      <?= svgIcon('chart', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $checkInRate ?>%</div>
      <div class="stat-label">Arrival Turnout Rate</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon yellow">
      <?= svgIcon('clock', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format(max(0, $totalGuests - $totalCheckedIn)) ?></div>
      <div class="stat-label">Remaining Expected</div>
    </div>
  </div>
</div>

<!-- Gate Breakdown Row -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Throughput Breakdown by Checkpoint Gate</h3>
  </div>
  <div class="card-body">
    <div class="grid grid-3">
      <?php if (empty($gates)): ?>
        <p style="color:var(--text-muted);text-align:center;grid-column:1/-1;">No gates configured for this event.</p>
      <?php else: ?>
        <?php foreach ($gates as $g): 
          $gatePct = $totalCheckedIn > 0 ? round(($g['scan_count'] / $totalCheckedIn) * 100, 1) : 0;
        ?>
          <div style="background:var(--surface-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:18px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <strong style="color:var(--text-primary);"><?= e($g['name']) ?></strong>
              <span class="badge badge-primary"><?= $g['scan_count'] ?> scans</span>
            </div>
            <div style="height:6px;background:rgba(0,0,0,0.06);border-radius:99px;overflow:hidden;margin-bottom:8px;">
              <div style="height:100%;background:#FF7A00;width:<?= $gatePct ?>%;border-radius:99px;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--text-muted);">
              <span><?= $gatePct ?>% of total volume</span>
              <a href="scanner.php?event_id=<?= $eventId ?>&gate_id=<?= $g['id'] ?>" style="color:#FF7A00;font-weight:700;">Open Scanner &rarr;</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Real-time Verified Entry Log -->
<div class="card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Live Gate Ingress Stream</h3>
      <p class="card-subtitle">Chronological record of verified attendees</p>
    </div>
    <div class="search-box" style="max-width:240px;">
      <?= svgIcon('search', 14) ?>
      <input type="text" placeholder="Filter stream..." data-table-search="#attendanceLogTable">
    </div>
  </div>
  <div class="table-responsive">
    <table class="data-table" id="attendanceLogTable">
      <thead>
        <tr>
          <th>Attendee Name</th>
          <th>VIP Category</th>
          <th>Entrance Gate</th>
          <th>Headcount</th>
          <th>Verified By Officer</th>
          <th>Timestamp</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($checkinLog)): ?>
          <tr class="empty-row"><td colspan="8">No verified check-ins recorded for this event yet.</td></tr>
        <?php else: ?>
          <?php foreach ($checkinLog as $log): ?>
            <tr id="checkin-row-<?= $log['id'] ?>">
              <td>
                <strong><?= e($log['guest_name']) ?></strong>
              </td>
              <td>
                <span class="badge badge-primary" style="background:rgba(255,122,0,0.1);color:#FF7A00;border:1px solid rgba(255,122,0,0.25);">
                  <?= e($log['category_name'] ?: 'Regular') ?>
                </span>
              </td>
              <td><?= e($log['gate_name'] ?: 'Gate 1') ?></td>
              <td><span class="badge badge-secondary"><?= $log['attendee_count'] ?> person(s)</span></td>
              <td><?= e($log['officer_name'] ?: 'Gate Officer') ?></td>
              <td style="white-space:nowrap;font-size:0.8rem;color:var(--text-secondary);"><?= e($log['checked_in_at']) ?></td>
              <td><span class="badge badge-success"><?= svgIcon('check', 12) ?> In Venue</span></td>
              <td style="text-align:right;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="reverseCheckin(<?= $log['id'] ?>, '<?= addslashes(e($log['guest_name'])) ?>', <?= $log['invitation_id'] ?>)" style="color:var(--danger);font-size:0.75rem;padding:5px 12px;border-color:rgba(220,38,38,0.25);" title="Reverse this admission check-in">
                  <?= svgIcon('refresh', 12) ?> Reverse
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
async function reverseCheckin(checkinId, guestName, invitationId) {
  const result = await window.showConfirmDialog({
    title: 'REVERSE CHECK-IN ADMISSION',
    subtitle: 'Authorized Verification Officer Action',
    message: `Are you sure you want to reverse the gate check-in for <strong>${guestName}</strong>?<br><br>This will revoke their verified entry and permit them to check in again.`,
    confirmText: 'Reverse Admission',
    cancelText: 'Cancel',
    type: 'danger',
    input: true,
    inputLabel: 'Reason for Reversal (Required):',
    inputPlaceholder: 'e.g. Accidental scan, wrong badge issued, testing...',
    inputRequired: true
  });

  if (!result || !result.confirmed) return;

  const reason = (result.value || '').trim();
  if (!reason) {
    window.Toast.warning('A justification reason is required to reverse a check-in.');
    return;
  }

  try {
    const fd = new URLSearchParams();
    fd.append('check_in_id', checkinId);
    fd.append('invitation_id', invitationId);
    fd.append('reason', reason);

    const res = await fetch('api/reverse_checkin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    });
    const data = await res.json();

    if (data.success) {
      window.Toast.success(data.message || 'Check-in successfully reversed.');
      const row = document.getElementById(`checkin-row-${checkinId}`);
      if (row) {
        row.style.transition = 'all 0.35s ease';
        row.style.opacity = '0';
        row.style.transform = 'scale(0.95)';
        setTimeout(() => row.remove(), 350);
      }
    } else {
      window.Toast.error(data.message || 'Unable to reverse check-in.');
    }
  } catch (err) {
    window.Toast.error('Network communication error with reversal API.');
  }
}

// Auto-refresh attendance every 30 seconds if idle
let refreshTimer = setTimeout(() => {
  window.location.reload();
}, 30000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
