<?php
$pageTitle = 'Events Registry';
$currentPage = 'events';
require_once __DIR__ . '/config/db.php';
require_role(2);

$user = auth_user();
$userId = (int)($user['id'] ?? 1);
$roleId = (int)($user['role_id'] ?? 2);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. CREATE EVENT
    if ($action === 'create_event') {
        $title = trim($_POST['title'] ?? '');
        $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
        $endDate = trim($_POST['end_date'] ?? $startDate);
        $startTime = trim($_POST['start_time'] ?? '18:00');
        $endTime = trim($_POST['end_time'] ?? '23:30');
        $venue = trim($_POST['venue_name'] ?? 'Main Venue');
        $address = trim($_POST['venue_address'] ?? '');
        $capacity = (int)($_POST['guest_capacity'] ?? 500);
        $desc = trim($_POST['description'] ?? '');

        if (!empty($title)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . rand(100, 999);
            $eventId = db_insert('events', [
                'organization_id' => $user['organization_id'] ?? 1,
                'created_by' => $userId,
                'title' => $title,
                'slug' => $slug,
                'description' => $desc,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'venue_name' => $venue,
                'venue_address' => $address,
                'guest_capacity' => $capacity,
                'status' => 'published',
                'is_published' => 1
            ]);

            // Auto-create default entrance gates
            db_insert('gates', [
                'event_id' => $eventId,
                'name' => 'Main Gate Entrance',
                'code' => 'GATE-MAIN',
                'allowed_categories' => 'all'
            ]);
            db_insert('gates', [
                'event_id' => $eventId,
                'name' => 'VIP Priority Gate',
                'code' => 'GATE-VIP',
                'allowed_categories' => 'vvip,vip'
            ]);

            // Set as active event in session
            $_SESSION['active_event_id'] = $eventId;

            audit_log('EVENT_MANAGEMENT', 'EVENT_CREATED', "Event '{$title}' (ID: {$eventId}) created by {$user['name']}");
            flash_set('success', 'Event "' . $title . '" has been created and published successfully!');
            header('Location: events.php');
            exit;
        }
    }

    // 2. EDIT EVENT
    elseif ($action === 'edit_event') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        if (!can_user_access_event($eventId)) {
            flash_set('danger', 'Unauthorized: You cannot edit this event.');
            header('Location: events.php');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
        $endDate = trim($_POST['end_date'] ?? $startDate);
        $startTime = trim($_POST['start_time'] ?? '18:00');
        $endTime = trim($_POST['end_time'] ?? '23:30');
        $venue = trim($_POST['venue_name'] ?? '');
        $address = trim($_POST['venue_address'] ?? '');
        $capacity = (int)($_POST['guest_capacity'] ?? 500);
        $desc = trim($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['published', 'draft', 'archived']) ? $_POST['status'] : 'published';

        if (!empty($title)) {
            db_update('events', [
                'title' => $title,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'venue_name' => $venue,
                'venue_address' => $address,
                'guest_capacity' => $capacity,
                'description' => $desc,
                'status' => $status
            ], 'id = :id', ['id' => $eventId]);

            audit_log('EVENT_MANAGEMENT', 'EVENT_UPDATED', "Event #{$eventId} ('{$title}') updated by {$user['name']}");
            flash_set('success', "Event '{$title}' updated successfully.");
            header('Location: events.php');
            exit;
        }
    }

    // 3. ARCHIVE / RESTORE EVENT
    elseif ($action === 'toggle_archive') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        if (!can_user_access_event($eventId)) {
            flash_set('danger', 'Unauthorized action.');
            header('Location: events.php');
            exit;
        }

        $ev = db_fetch_one("SELECT id, title, status FROM events WHERE id = ?", [$eventId]);
        if ($ev) {
            $newStatus = ($ev['status'] === 'archived') ? 'published' : 'archived';
            db_update('events', ['status' => $newStatus], 'id = :id', ['id' => $eventId]);
            audit_log('EVENT_MANAGEMENT', 'EVENT_STATUS_CHANGED', "Event #{$eventId} status changed to '{$newStatus}' by {$user['name']}");
            flash_set('success', "Event '{$ev['title']}' has been " . ($newStatus === 'archived' ? 'archived' : 'restored to active status') . ".");
        }
        header('Location: events.php');
        exit;
    }

    // 4. DELETE EVENT (Soft delete with audit trail)
    elseif ($action === 'delete_event') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        if (!can_user_access_event($eventId)) {
            flash_set('danger', 'Unauthorized action.');
            header('Location: events.php');
            exit;
        }

        $ev = db_fetch_one("SELECT id, title FROM events WHERE id = ?", [$eventId]);
        if ($ev) {
            try {
                db_update('events', [
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'status' => 'archived'
                ], 'id = :id', ['id' => $eventId]);
            } catch (Throwable $t) {
                // If deleted_at doesn't exist on sqlite
                db_query("DELETE FROM events WHERE id = ?", [$eventId]);
            }

            audit_log('EVENT_MANAGEMENT', 'EVENT_DELETED', "Event #{$eventId} ('{$ev['title']}') was safely archived/deleted by {$user['name']}");
            flash_set('success', "Event '{$ev['title']}' has been deleted and archived.");
            
            // Switch active event in session if deleted
            if (isset($_SESSION['active_event_id']) && (int)$_SESSION['active_event_id'] === $eventId) {
                unset($_SESSION['active_event_id']);
            }
        }
        header('Location: events.php');
        exit;
    }
}

// Fetch Scoped Events List (Admin sees all, Director only sees created_by)
$whereClause = "WHERE e.deleted_at IS NULL";
$params = [];

if ($roleId === 2) {
    $whereClause .= " AND e.created_by = :uid";
    $params['uid'] = $userId;
}

try {
    $events = db_fetch_all("
        SELECT e.*,
               (SELECT COUNT(*) FROM guests WHERE event_id = e.id) as guest_count,
               (SELECT COUNT(*) FROM check_ins WHERE event_id = e.id) as checkin_count,
               (SELECT COUNT(*) FROM gates WHERE event_id = e.id) as gate_count
        FROM events e
        {$whereClause}
        ORDER BY e.start_date DESC
    ", $params);
} catch (Throwable $t) {
    // Fallback if deleted_at column missing
    $fallbackWhere = ($roleId === 2) ? "WHERE e.created_by = :uid" : "";
    $events = db_fetch_all("
        SELECT e.*,
               (SELECT COUNT(*) FROM guests WHERE event_id = e.id) as guest_count,
               (SELECT COUNT(*) FROM check_ins WHERE event_id = e.id) as checkin_count,
               (SELECT COUNT(*) FROM gates WHERE event_id = e.id) as gate_count
        FROM events e
        {$fallbackWhere}
        ORDER BY e.start_date DESC
    ", $params);
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Events Registry</h1>
    <p class="page-subtitle">
      <?= ($roleId === 1) ? 'Global Event Master Directory & Access Control' : 'Your Managed Events & Security Portals' ?>
    </p>
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
    <input type="text" placeholder="Search events by title, venue or date..." data-table-search="#eventsTable">
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
          <tr class="empty-row"><td colspan="7">No events registered yet under your account. Click "Create New Event" to get started.</td></tr>
        <?php else: ?>
          <?php foreach ($events as $evt): 
            $isArchived = ($evt['status'] === 'archived');
          ?>
            <tr style="<?= $isArchived ? 'opacity:0.65;background:rgba(0,0,0,0.02);' : '' ?>">
              <td>
                <a href="event-detail.php?id=<?= $evt['id'] ?>" style="font-weight:700;color:var(--text-primary);font-size:0.95rem;">
                  <?= e($evt['title']) ?>
                </a>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                  ID: #<?= $evt['id'] ?> &bull; Capacity: <?= number_format($evt['guest_capacity']) ?>
                </div>
              </td>
              <td>
                <div style="font-weight:600;"><?= date('M d, Y', strtotime($evt['start_date'])) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($evt['start_time']) ?> – <?= e($evt['end_time'] ?: 'Close') ?></div>
              </td>
              <td>
                <div style="font-weight:600;color:var(--text-primary);"><?= e($evt['venue_name']) ?></div>
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
                <?php if ($isArchived): ?>
                  <span class="badge badge-secondary">Archived</span>
                <?php elseif ($evt['status'] === 'draft'): ?>
                  <span class="badge badge-warning">Draft</span>
                <?php else: ?>
                  <span class="badge badge-success">Published</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <div class="actions" style="justify-content:flex-end;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="openEditEventModal(<?= htmlspecialchars(json_encode($evt), ENT_QUOTES, 'UTF-8') ?>)" title="Edit Event Details">
                    <?= svgIcon('edit', 14) ?> Edit
                  </button>
                  <a href="designer.php?event_id=<?= $evt['id'] ?>" class="btn btn-secondary btn-sm" title="Design Invitation Card">
                    <?= svgIcon('palette', 14) ?> Cards
                  </a>
                  <a href="guests.php?event_id=<?= $evt['id'] ?>" class="btn btn-secondary btn-sm" title="Manage Guest Directory">
                    <?= svgIcon('users', 14) ?> Guests
                  </a>
                  <a href="scanner.php?event_id=<?= $evt['id'] ?>" class="btn btn-primary btn-sm" title="Launch Gate Scanner">
                    <?= svgIcon('qr', 14) ?> Scan
                  </a>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="confirmDeleteEvent(<?= $evt['id'] ?>, '<?= htmlspecialchars(addslashes($evt['title']), ENT_QUOTES, 'UTF-8') ?>', <?= (int)$evt['guest_count'] ?>)" title="Delete / Archive Event" style="color:var(--danger);">
                    <?= svgIcon('trash', 14) ?>
                  </button>
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
            <label class="form-label">Start Date <span style="color:var(--danger)">*</span></label>
            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
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

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Venue Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="venue_name" class="form-control" placeholder="Serena Grand Ballroom" required>
          </div>
          <div class="form-group">
            <label class="form-label">Guest Capacity</label>
            <input type="number" name="guest_capacity" class="form-control" value="500" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Venue Physical Address</label>
          <input type="text" name="venue_address" class="form-control" placeholder="Ohio Street, City Centre, Dar es Salaam">
        </div>

        <div class="form-group">
          <label class="form-label">Event Description & Protocol Guidelines</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Dress code: Black Tie. VIP attendees must present digital QR pass at Gate 1."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 16) ?> Create & Publish Event</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Existing Event -->
<div class="modal-backdrop" id="editEventModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Edit Event Specifications</h3>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="events.php" id="editEventForm">
      <input type="hidden" name="action" value="edit_event">
      <input type="hidden" name="event_id" id="edit_event_id">

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Event Title <span style="color:var(--danger)">*</span></label>
          <input type="text" name="title" id="edit_title" class="form-control" required>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Start Date <span style="color:var(--danger)">*</span></label>
            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" id="edit_end_date" class="form-control">
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" id="edit_end_time" class="form-control">
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Venue Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="venue_name" id="edit_venue_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Guest Capacity</label>
            <input type="number" name="guest_capacity" id="edit_guest_capacity" class="form-control" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Venue Physical Address</label>
          <input type="text" name="venue_address" id="edit_venue_address" class="form-control">
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Publication Status</label>
            <select name="status" id="edit_status" class="form-control">
              <option value="published">Published (Active)</option>
              <option value="draft">Draft (Private)</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Event Description & Protocol Guidelines</label>
          <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 16) ?> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Hidden Form for Delete Action -->
<form id="deleteEventForm" method="POST" action="events.php" style="display:none;">
  <input type="hidden" name="action" value="delete_event">
  <input type="hidden" name="event_id" id="delete_event_id">
</form>

<script>
function openEditEventModal(evt) {
  document.getElementById('edit_event_id').value = evt.id;
  document.getElementById('edit_title').value = evt.title || '';
  document.getElementById('edit_start_date').value = evt.start_date || '';
  document.getElementById('edit_end_date').value = evt.end_date || evt.start_date || '';
  document.getElementById('edit_start_time').value = evt.start_time || '18:00';
  document.getElementById('edit_end_time').value = evt.end_time || '23:30';
  document.getElementById('edit_venue_name').value = evt.venue_name || '';
  document.getElementById('edit_guest_capacity').value = evt.guest_capacity || 500;
  document.getElementById('edit_venue_address').value = evt.venue_address || '';
  document.getElementById('edit_status').value = evt.status || 'published';
  document.getElementById('edit_description').value = evt.description || '';

  const modal = document.getElementById('editEventModal');
  modal.classList.add('active');
}

async function confirmDeleteEvent(eventId, eventTitle, guestCount) {
  const result = await window.showConfirmDialog({
    title: 'DELETE & ARCHIVE EVENT?',
    subtitle: 'High Impact Event Action',
    message: `Are you sure you want to delete and archive <strong>${eventTitle}</strong>?<br><br>
              <div style="background:rgba(220,38,38,0.08);padding:10px 14px;border-radius:8px;border-left:3px solid #DC2626;font-size:0.84rem;color:var(--text-secondary);text-align:left;">
                <strong>Warning:</strong> This event currently has <strong>${guestCount}</strong> registered guests. Archiving will disable public RSVP submissions and gate check-in, but preserved records remain in your audit logs.
              </div>`,
    confirmText: 'Yes, Archive Event',
    cancelText: 'Cancel',
    type: 'danger'
  });

  if (result && result.confirmed) {
    document.getElementById('delete_event_id').value = eventId;
    document.getElementById('deleteEventForm').submit();
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
