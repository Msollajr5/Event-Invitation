<?php
$pageTitle = 'Event Details';
$currentPage = 'events';
require_once __DIR__ . '/config/db.php';
require_role(2);

$eventId = (int)($_GET['id'] ?? 1);
require_event_access($eventId);

$event = db_fetch_one("SELECT * FROM events WHERE id = :id LIMIT 1", ['id' => $eventId]);

if (!$event) {
    flash_set('danger', 'Event not found.');
    header('Location: events.php');
    exit;
}

// Handle Add Gate POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_gate') {
    $gateName = trim($_POST['gate_name'] ?? '');
    $gateCode = trim($_POST['gate_code'] ?? 'GATE-' . rand(10, 99));
    $allowedCats = trim($_POST['allowed_categories'] ?? 'all');

    if (!empty($gateName)) {
        db_insert('gates', [
            'event_id' => $eventId,
            'name' => $gateName,
            'code' => $gateCode,
            'allowed_categories' => $allowedCats
        ]);
        audit_log('EVENT_MANAGEMENT', 'GATE_CREATED', "Gate '{$gateName}' ({$gateCode}) added to event #{$eventId}");
        flash_set('success', 'Gate "' . $gateName . '" added successfully!');
        header('Location: event-detail.php?id=' . $eventId);
        exit;
    }
}

// Event Metrics
$totalGuests = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM guests WHERE event_id = :id AND deleted_at IS NULL", ['id' => $eventId])['cnt'] ?? 0);
$checkedIn = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM check_ins WHERE event_id = :id", ['id' => $eventId])['cnt'] ?? 0);
$acceptedRsvps = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'accepted'", ['id' => $eventId])['cnt'] ?? 0);
$gates = db_fetch_all("
    SELECT g.*, 
           (SELECT COUNT(*) FROM check_ins WHERE gate_id = g.id) as scan_count 
    FROM gates g 
    WHERE g.event_id = :id
", ['id' => $eventId]);

$recentGuests = db_fetch_all("
    SELECT g.*, gc.name as category_name, gc.color as category_color, i.reference_code, i.is_checked_in, qt.token
    FROM guests g
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    LEFT JOIN invitations i ON i.guest_id = g.id
    LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
    WHERE g.event_id = :id
    ORDER BY g.id DESC
    LIMIT 10
", ['id' => $eventId]);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
      <a href="events.php" style="color:var(--text-muted);font-size:0.85rem;display:inline-flex;align-items:center;gap:4px;">
        &larr; Events Registry
      </a>
      <span style="color:var(--text-muted);">&bull;</span>
      <?php if ($event['status'] === 'archived'): ?>
        <span class="badge badge-secondary">Archived</span>
      <?php else: ?>
        <span class="badge badge-success">Published & Active</span>
      <?php endif; ?>
    </div>
    <h1 class="page-title"><?= e($event['title']) ?></h1>
    <p class="page-subtitle"><?= e($event['venue_name']) ?> &bull; <?= date('l, F j, Y', strtotime($event['start_date'])) ?> at <?= e($event['start_time']) ?></p>
  </div>
  <div class="page-actions">
    <a href="designer.php?event_id=<?= $event['id'] ?>" class="btn btn-secondary">
      <?= svgIcon('palette', 16) ?> Invitation Cards
    </a>
    <a href="scanner.php?event_id=<?= $event['id'] ?>" class="btn btn-primary">
      <?= svgIcon('qr', 16) ?> Gate Scanner
    </a>
    <a href="guests.php?event_id=<?= $event['id'] ?>" class="btn btn-secondary">
      <?= svgIcon('users', 16) ?> Guest Roster
    </a>
    <button type="button" class="btn btn-secondary" data-modal-open="addGateModal">
      <?= svgIcon('plus', 16) ?> Add Gate
    </button>
  </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon orange">
      <?= svgIcon('users', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalGuests) ?></div>
      <div class="stat-label">Total Guests Registered</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <?= svgIcon('check-circle', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($checkedIn) ?></div>
      <div class="stat-label">Verified Check-ins</div>
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

  <div class="stat-card">
    <div class="stat-icon blue">
      <?= svgIcon('building', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= count($gates) ?></div>
      <div class="stat-label">Active Gates</div>
    </div>
  </div>
</div>

<!-- Gates Management & Recent Guests Grid -->
<div class="grid grid-dashboard">
  <!-- Gates Card -->
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h3 class="card-title">Entrance Gates & Access Checkpoints</h3>
        <p class="card-subtitle">Configured scan gates with category rules and live counts</p>
      </div>
      <button type="button" class="btn btn-primary btn-sm" data-modal-open="addGateModal">
        <?= svgIcon('plus', 14) ?> Add Gate
      </button>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Gate Name</th>
            <th>Code</th>
            <th>Allowed Categories</th>
            <th>Total Scans</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($gates)): ?>
            <tr class="empty-row"><td colspan="5">No gates added yet.</td></tr>
          <?php else: ?>
            <?php foreach ($gates as $g): ?>
              <tr>
                <td><strong><?= e($g['name']) ?></strong></td>
                <td><code><?= e($g['code']) ?></code></td>
                <td>
                  <span class="badge badge-info" style="font-size:0.75rem;">
                    <?= strtoupper(e($g['allowed_categories'] ?? 'ALL')) ?>
                  </span>
                </td>
                <td><span class="badge badge-primary"><?= number_format($g['scan_count']) ?> verified</span></td>
                <td>
                  <a href="scanner.php?event_id=<?= $eventId ?>&gate_id=<?= $g['id'] ?>" class="btn btn-secondary btn-sm">
                    <?= svgIcon('qr', 14) ?> Scan Pass
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Event Details Summary Card -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Venue & Operational Specifications</h3>
    </div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:16px;">
        <div>
          <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Physical Venue</span>
          <div style="font-weight:600;font-size:0.95rem;color:var(--text-primary);margin-top:2px;"><?= e($event['venue_name']) ?></div>
          <div style="font-size:0.85rem;color:var(--text-secondary);"><?= e($event['venue_address'] ?: 'Address not specified') ?></div>
        </div>

        <div>
          <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Capacity Utilization</span>
          <div style="font-weight:700;font-size:1.1rem;color:var(--brand-primary);margin-top:2px;">
            <?= $totalGuests ?> / <?= number_format($event['guest_capacity']) ?> guests
          </div>
        </div>

        <div>
          <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Description & Protocol</span>
          <p style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px;line-height:1.5;">
            <?= nl2br(e($event['description'] ?: 'No description provided.')) ?>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Add Gate -->
<div class="modal-backdrop" id="addGateModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Add Entrance Gate</h3>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="event-detail.php?id=<?= $eventId ?>">
      <input type="hidden" name="action" value="add_gate">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Gate Name</label>
          <input type="text" name="gate_name" class="form-control" placeholder="VIP North Gate Entrance" required>
        </div>
        <div class="form-group">
          <label class="form-label">Gate Code</label>
          <input type="text" name="gate_code" class="form-control" value="GATE-<?= rand(10, 99) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Permitted Guest Categories</label>
          <input type="text" name="allowed_categories" class="form-control" value="all" placeholder="e.g. all OR vvip,vip">
          <small style="color:var(--text-muted);font-size:0.76rem;margin-top:4px;display:block;">Enter 'all' for general gate, or comma-separated slugs (e.g. vvip,vip) for restricted gate.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 16) ?> Save Gate</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
