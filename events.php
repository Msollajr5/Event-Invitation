<?php
$pageTitle = 'Events Registry';
$currentPage = 'events';
require_once __DIR__ . '/config/db.php';
require_role(2);

// Handle Create Event POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    $title = trim($_POST['title'] ?? '');
    $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
    $startTime = trim($_POST['start_time'] ?? '18:00');
    $endTime = trim($_POST['end_time'] ?? '23:30');
    $venue = trim($_POST['venue_name'] ?? 'Main Venue');
    $address = trim($_POST['venue_address'] ?? '');
    $capacity = (int)($_POST['guest_capacity'] ?? 500);
    $desc = trim($_POST['description'] ?? '');

    if (!empty($title)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $eventId = db_insert('events', [
            'organization_id' => 1,
            'created_by' => $_SESSION['user_id'] ?? 1,
            'title' => $title,
            'slug' => $slug,
            'description' => $desc,
            'start_date' => $startDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'venue_name' => $venue,
            'venue_address' => $address,
            'guest_capacity' => $capacity,
            'status' => 'published',
            'is_published' => 1
        ]);

        // Auto-create default entrance gate
        db_insert('gates', [
            'event_id' => $eventId,
            'name' => 'Main Gate 1',
            'code' => 'GATE-01'
        ]);

        flash_set('success', 'Event "' . $title . '" has been created and published successfully!');
        header('Location: events.php');
        exit;
    }
}

// Fetch Events List with Aggregate Counters
$events = db_fetch_all("
    SELECT e.*,
           (SELECT COUNT(*) FROM guests WHERE event_id = e.id) as guest_count,
           (SELECT COUNT(*) FROM check_ins WHERE event_id = e.id) as checkin_count,
           (SELECT COUNT(*) FROM gates WHERE event_id = e.id) as gate_count
    FROM events e
    ORDER BY e.start_date DESC
");

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Events Registry</h1>
    <p class="page-subtitle">Configure conferences, galas, and VIP access control checkpoints</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" data-modal-open="createEventModal">
      <?= svgIcon('plus', 16) ?> Create New Event
    </button>
  </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
  <div class="search-box">
    <?= svgIcon('search', 16) ?>
    <input type="text" placeholder="Search events by name, venue or date..." data-table-search="#eventsTable">
  </div>
</div>

<!-- Events Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table" id="eventsTable">
      <thead>
        <tr>
          <th>Event Title</th>
          <th>Date & Schedule</th>
          <th>Venue Location</th>
          <th>Capacity & Guests</th>
          <th>Gates</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($events)): ?>
          <tr class="empty-row"><td colspan="7">No events registered yet. Click "Create New Event" to get started.</td></tr>
        <?php else: ?>
          <?php foreach ($events as $evt): ?>
            <tr>
              <td>
                <a href="event-detail.php?id=<?= $evt['id'] ?>" style="font-weight:700;color:var(--text-primary);">
                  <?= e($evt['title']) ?>
                </a>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                  ID: #<?= $evt['id'] ?> &bull; Slug: <?= e($evt['slug']) ?>
                </div>
              </td>
              <td>
                <div style="font-weight:600;"><?= date('M d, Y', strtotime($evt['start_date'])) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($evt['start_time']) ?> – <?= e($evt['end_time'] ?: 'Close') ?></div>
              </td>
              <td>
                <div style="font-weight:500;"><?= e($evt['venue_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($evt['venue_address']) ?></div>
              </td>
              <td>
                <div style="font-weight:700;"><?= number_format($evt['guest_count']) ?> <span style="font-size:0.75rem;color:var(--text-muted);font-weight:500;">/ <?= number_format($evt['guest_capacity']) ?></span></div>
                <div style="font-size:0.75rem;color:var(--success);font-weight:600;"><?= $evt['checkin_count'] ?> checked in</div>
              </td>
              <td>
                <span class="badge badge-secondary"><?= $evt['gate_count'] ?> Gate<?= $evt['gate_count'] == 1 ? '' : 's' ?></span>
              </td>
              <td>
                <span class="badge badge-success">Published</span>
              </td>
              <td style="text-align:right;">
                <div class="actions" style="justify-content:flex-end;">
                  <a href="designer.php?event_id=<?= $evt['id'] ?>" class="btn btn-secondary btn-sm" title="Design Invitation Card">
                    <?= svgIcon('palette', 14) ?> Design
                  </a>
                  <a href="event-detail.php?id=<?= $evt['id'] ?>" class="btn btn-secondary btn-sm" title="View Event Dashboard">
                    <?= svgIcon('eye', 14) ?> Manage
                  </a>
                  <a href="guests.php?event_id=<?= $evt['id'] ?>" class="btn btn-secondary btn-sm" title="Manage Guest Roster">
                    <?= svgIcon('users', 14) ?> Guests
                  </a>
                  <a href="scanner.php?event_id=<?= $evt['id'] ?>" class="btn btn-primary btn-sm" title="Launch Gate Scanner">
                    <?= svgIcon('qr', 14) ?> Scan
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Create New Event -->
<div class="modal-backdrop" id="createEventModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Create New Event</h3>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="events.php">
      <input type="hidden" name="action" value="create_event">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Event Title <span style="color:var(--danger)">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="Annual Fintech Gala & Awards 2026" required>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Event Date <span style="color:var(--danger)">*</span></label>
            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Guest Capacity</label>
            <input type="number" name="guest_capacity" class="form-control" value="500" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="18:00" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" value="23:30">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Venue Name <span style="color:var(--danger)">*</span></label>
          <input type="text" name="venue_name" class="form-control" placeholder="Serena Grand Ballroom" required>
        </div>

        <div class="form-group">
          <label class="form-label">Venue Physical Address</label>
          <input type="text" name="venue_address" class="form-control" placeholder="Ohio Street, City Centre, Dar es Salaam">
        </div>

        <div class="form-group">
          <label class="form-label">Event Description & Dress Code</label>
          <textarea name="description" class="form-control" placeholder="Dress code: Black Tie. VIP attendees must present digital QR pass at Gate 1."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 16) ?> Create & Publish Event</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
