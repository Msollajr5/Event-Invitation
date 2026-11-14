<?php
$pageTitle = 'Guest Directory';
$currentPage = 'guests';
require_once __DIR__ . '/config/db.php';
require_role(2);

$userEvents = get_user_events();
$selectedEventId = (int)($_GET['event_id'] ?? get_active_event_id() ?? 1);
require_event_access($selectedEventId);

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="guest_roster_event_' . $selectedEventId . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Full Name', 'Category', 'Email', 'Phone', 'Permitted Attendees', 'Reference Code', 'Token', 'Pass Link', 'Status']);

    $exportRows = db_fetch_all("
        SELECT g.*, gc.name as category_name, i.reference_code, qt.token, i.is_checked_in
        FROM guests g
        LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
        LEFT JOIN invitations i ON i.guest_id = g.id
        LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
        WHERE g.event_id = :eid
        ORDER BY g.id ASC
    ", ['eid' => $selectedEventId]);

    foreach ($exportRows as $r) {
        $link = APP_URL . '/pass.php?token=' . ($r['token'] ?: $r['reference_code'] ?: '');
        fputcsv($out, [
            $r['id'],
            $r['full_name'],
            $r['category_name'] ?: 'Regular',
            $r['email'],
            $r['phone'],
            $r['permitted_attendees'],
            $r['reference_code'],
            $r['token'],
            $link,
            $r['is_checked_in'] ? 'CHECKED IN' : 'INVITED'
        ]);
    }
    fclose($out);
    exit;
}

// Handle Add Individual Guest
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_guest') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $attendees = max(1, (int)($_POST['permitted_attendees'] ?? 1));

    if (!empty($fullName)) {
        $parts = explode(' ', $fullName, 2);
        $firstName = $parts[0];
        $lastName = $parts[1] ?? '';

        $guestId = db_insert('guests', [
            'organization_id' => $_SESSION['user']['organization_id'] ?? 1,
            'event_id' => $selectedEventId,
            'guest_category_id' => $categoryId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'permitted_attendees' => $attendees,
            'status' => 'active'
        ]);

        $refCode = 'INV-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $firstName), 0, 3)) . '-' . rand(1000, 9999);
        $invId = db_insert('invitations', [
            'organization_id' => $_SESSION['user']['organization_id'] ?? 1,
            'event_id' => $selectedEventId,
            'guest_id' => $guestId,
            'reference_code' => $refCode,
            'status' => 'delivered'
        ]);

        $tokenStr = 'TKN-' . strtoupper(bin2hex(random_bytes(6)));
        db_insert('qr_tokens', [
            'invitation_id' => $invId,
            'token' => $tokenStr
        ]);

        $u = auth_user();
        audit_log('GUEST_MANAGEMENT', 'GUEST_ADDED', "Added guest '{$fullName}' (Ref: {$refCode}) to event #{$selectedEventId} by {$u['name']}");
        flash_set('success', 'Guest "' . $fullName . '" registered and digital pass generated!');
        header('Location: guests.php?event_id=' . $selectedEventId);
        exit;
    }
}

// Handle Delete Guest
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $guest = db_fetch_one("SELECT * FROM guests WHERE id = :gid AND event_id = :eid", ['gid' => $delId, 'eid' => $selectedEventId]);
    if ($guest) {
        $inv = db_fetch_one("SELECT id FROM invitations WHERE guest_id = :gid", ['gid' => $delId]);
        if ($inv) {
            db_query("DELETE FROM qr_tokens WHERE invitation_id = :iid", ['iid' => $inv['id']]);
            db_query("DELETE FROM check_ins WHERE invitation_id = :iid", ['iid' => $inv['id']]);
            db_query("DELETE FROM rsvp_records WHERE invitation_id = :iid", ['iid' => $inv['id']]);
            db_query("DELETE FROM invitations WHERE id = :iid", ['iid' => $inv['id']]);
        }
        db_query("DELETE FROM guests WHERE id = :gid", ['gid' => $delId]);
        audit_log('GUEST_MANAGEMENT', 'DELETE_GUEST', "Removed guest '{$guest['full_name']}' (ID #{$delId}) and voided pass.");
        flash_set('success', 'Guest record removed successfully.');
    }
    header('Location: guests.php?event_id=' . $selectedEventId);
    exit;
}

// Fetch Guests for this event
$guests = db_fetch_all("
    SELECT g.*, gc.name as category_name, gc.color as category_color,
           i.id as invitation_id, i.reference_code, i.is_checked_in, qt.token,
           (SELECT response FROM rsvp_records WHERE guest_id = g.id ORDER BY id DESC LIMIT 1) as rsvp_status
    FROM guests g
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    LEFT JOIN invitations i ON i.guest_id = g.id
    LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
    WHERE g.event_id = :eid
    ORDER BY g.id DESC
", ['eid' => $selectedEventId]);

$categories = db_fetch_all("SELECT * FROM guest_categories ORDER BY id ASC");
$currentEvent = db_fetch_one("SELECT * FROM events WHERE id = ?", [$selectedEventId]);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
      <span class="badge badge-primary"><?= e($currentEvent['title'] ?? 'Event') ?></span>
      <span style="font-size:0.8rem;color:var(--text-muted);">&bull; <?= count($guests) ?> Guests Enrolled</span>
    </div>
    <h1 class="page-title">Guest Directory & Access Control</h1>
    <p class="page-subtitle">Instant 1-click category updates, QR token dispatch, and attendee roster management</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-secondary" data-modal-open="manageCategoriesModal">
      <?= svgIcon('palette', 15) ?> Manage Categories
    </button>
    <button type="button" class="btn btn-primary" data-modal-open="addGuestModal">
      <?= svgIcon('plus', 15) ?> Add Guest
    </button>
    <a href="guests.php?event_id=<?= $selectedEventId ?>&action=export_csv" class="btn btn-secondary">
      <?= svgIcon('download', 15) ?> Export CSV
    </a>
  </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
  <div class="search-box" style="flex:1;">
    <?= svgIcon('search', 16) ?>
    <input type="text" placeholder="Search guests by name, phone, email, token, or category..." data-table-search="#guestsTable">
  </div>

  <div style="display:flex;gap:10px;align-items:center;">
    <select class="form-control" style="width:auto;min-width:140px;" onchange="filterByCategory(this.value)">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= strtolower($cat['name']) ?>"><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- Guests Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table" id="guestsTable">
      <thead>
        <tr>
          <th>Guest Name</th>
          <th>Access Category (1-Click Change)</th>
          <th>Contact Info</th>
          <th>Pass Token</th>
          <th>Pax</th>
          <th>RSVP</th>
          <th>Gate Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($guests)): ?>
          <tr class="empty-row"><td colspan="8">No guests registered for this event yet. Click "Add Guest" above to begin.</td></tr>
        <?php else: ?>
          <?php foreach ($guests as $g): 
            $passUrl = APP_URL . '/pass.php?token=' . urlencode($g['token'] ?: $g['reference_code'] ?: '');
            $waText = urlencode("Hello " . $g['full_name'] . ", here is your digital pass for " . ($currentEvent['title'] ?? 'the event') . ": " . $passUrl);
            $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $g['phone']) . "?text=" . $waText;
            $catColor = $g['category_color'] ?: '#3b82f6';
          ?>
            <tr id="guest-row-<?= $g['id'] ?>">
              <td>
                <div style="font-weight:700;color:var(--text-primary);font-size:0.95rem;"><?= e($g['full_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                  Ref: <code style="font-size:0.75rem;"><?= e($g['reference_code'] ?: '—') ?></code>
                </div>
              </td>
              <td>
                <!-- 1-Click Inline Category Switcher -->
                <div style="position:relative;display:inline-block;">
                  <select 
                    class="category-switcher-select" 
                    id="cat-select-<?= $g['id'] ?>"
                    onchange="changeGuestCategory(<?= $g['id'] ?>, this.value, '<?= htmlspecialchars(addslashes($g['full_name']), ENT_QUOTES, 'UTF-8') ?>')"
                    style="background:<?= $catColor ?>15;color:<?= $catColor ?>;border:1px solid <?= $catColor ?>50;border-radius:20px;padding:4px 10px;font-size:0.78rem;font-weight:700;cursor:pointer;outline:none;"
                    title="Change guest category (preserves QR token & permissions update instantly)"
                  >
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= $c['id'] ?>" <?= ((int)$c['id'] === (int)$g['guest_category_id']) ? 'selected' : '' ?> data-color="<?= e($c['color'] ?: '#3b82f6') ?>">
                        <?= e($c['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </td>
              <td>
                <div style="font-size:0.84rem;font-weight:600;color:var(--text-primary);"><?= e($g['email'] ?: 'No email') ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($g['phone'] ?: 'No phone') ?></div>
              </td>
              <td>
                <code style="font-size:0.78rem;"><?= e($g['token'] ?: '—') ?></code>
              </td>
              <td>
                <span class="badge badge-secondary"><?= $g['permitted_attendees'] ?> pax</span>
              </td>
              <td>
                <?php if ($g['rsvp_status'] === 'accepted'): ?>
                  <span class="badge badge-success">Accepted</span>
                <?php elseif ($g['rsvp_status'] === 'declined'): ?>
                  <span class="badge badge-danger">Declined</span>
                <?php else: ?>
                  <span class="badge badge-warning">Pending</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($g['is_checked_in']): ?>
                  <span class="badge badge-success"><?= svgIcon('check', 12) ?> In Venue</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Not Scanned</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <div class="actions" style="justify-content:flex-end;">
                  <?php if (!empty($g['token'])): ?>
                    <a href="pass.php?token=<?= urlencode($g['token']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="View Digital Pass">
                      <?= svgIcon('ticket', 14) ?> Pass
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="copyToClipboard('<?= $passUrl ?>', 'Pass link copied!')" title="Copy Link">
                      <?= svgIcon('copy', 14) ?>
                    </button>
                    <?php if (!empty($g['phone'])): ?>
                      <a href="<?= $waLink ?>" target="_blank" class="btn btn-secondary btn-sm" title="Send WhatsApp Pass" style="color:#059669;">
                        <?= svgIcon('whatsapp', 14) ?>
                      </a>
                    <?php endif; ?>
                  <?php endif; ?>
                  <button type="button" class="btn btn-secondary btn-sm" style="color:var(--danger)" onclick="confirmDeleteGuest(<?= $g['id'] ?>, '<?= addslashes(e($g['full_name'])) ?>', <?= $selectedEventId ?>)" title="Delete Guest">
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

<!-- Modal: Add Individual Guest -->
<div class="modal-backdrop" id="addGuestModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Register Guest & Generate Pass</h3>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="guests.php?event_id=<?= $selectedEventId ?>">
      <input type="hidden" name="action" value="add_guest">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name <span style="color:var(--danger)">*</span></label>
          <input type="text" name="full_name" class="form-control" placeholder="Dr. Reginald Massawe" required>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Guest Category</label>
            <select name="category_id" class="form-control">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Permitted Attendees</label>
            <input type="number" name="permitted_attendees" class="form-control" value="1" min="1" max="10">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">WhatsApp Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="+255 712 000 000">
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="reginald.m@tanzaniatech.org">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 16) ?> Issue Digital Pass</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Manage Categories -->
<div class="modal-backdrop" id="manageCategoriesModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Access Categories & Badge Colors</h3>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:16px;">
        Define security tiers and badge styling. Gates can be restricted to specific categories (e.g. VIP Priority Gate).
      </p>

      <!-- Category List Table -->
      <table class="data-table" style="margin-bottom:20px;">
        <thead>
          <tr>
            <th>Badge Preview</th>
            <th>Name</th>
            <th>Color Code</th>
            <th>Slug</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody id="categoryListBody">
          <?php foreach ($categories as $cat): ?>
            <tr id="cat-tr-<?= $cat['id'] ?>">
              <td>
                <span class="badge" style="background:<?= e($cat['color'] ?: '#3b82f6') ?>;color:#fff;font-weight:700;">
                  <?= e($cat['name']) ?>
                </span>
              </td>
              <td><strong><?= e($cat['name']) ?></strong></td>
              <td><code><?= e($cat['color'] ?: '#3b82f6') ?></code></td>
              <td><code><?= e($cat['slug']) ?></code></td>
              <td style="text-align:right;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="editCategoryPrompt(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name']), ENT_QUOTES, 'UTF-8') ?>', '<?= e($cat['color'] ?: '#3b82f6') ?>')">
                  <?= svgIcon('edit', 12) ?> Edit
                </button>
                <button type="button" class="btn btn-secondary btn-sm" style="color:var(--danger)" onclick="deleteCategoryPrompt(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name']), ENT_QUOTES, 'UTF-8') ?>')">
                  <?= svgIcon('trash', 12) ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Add New Category Form -->
      <div style="background:var(--surface-3);padding:16px;border-radius:10px;border:1px solid var(--border-color);">
        <h4 style="font-size:0.95rem;font-weight:700;margin-bottom:12px;">Create New Category</h4>
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;">
          <div>
            <label class="form-label" style="font-size:0.75rem;">Category Name</label>
            <input type="text" id="newCatName" class="form-control" placeholder="e.g. Media / Press">
          </div>
          <div>
            <label class="form-label" style="font-size:0.75rem;">Badge Color</label>
            <div style="display:flex;align-items:center;gap:6px;">
              <input type="color" id="newCatColor" value="#ec4899" style="width:36px;height:36px;padding:2px;border-radius:6px;border:1px solid var(--border-color);cursor:pointer;">
              <input type="text" id="newCatColorHex" value="#ec4899" class="form-control" style="font-size:0.8rem;" oninput="document.getElementById('newCatColor').value=this.value">
            </div>
          </div>
          <div>
            <label class="form-label" style="font-size:0.75rem;">Description</label>
            <input type="text" id="newCatDesc" class="form-control" placeholder="Optional notes">
          </div>
          <div>
            <button type="button" onclick="submitCreateCategory()" class="btn btn-primary" style="height:38px;">
              <?= svgIcon('plus', 14) ?> Add
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
    </div>
  </div>
</div>

<script>
// 1-Click Category Change via AJAX
async function changeGuestCategory(guestId, newCategoryId, guestName) {
  const selectElem = document.getElementById(`cat-select-${guestId}`);
  const opt = selectElem.options[selectElem.selectedIndex];
  const newColor = opt.getAttribute('data-color') || '#3b82f6';

  try {
    const fd = new URLSearchParams();
    fd.append('guest_id', guestId);
    fd.append('category_id', newCategoryId);

    const res = await fetch('api/update_guest_category.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    });

    const data = await res.json();
    if (data.success) {
      selectElem.style.background = `${newColor}15`;
      selectElem.style.color = newColor;
      selectElem.style.borderColor = `${newColor}50`;
      if (window.Toast) {
        window.Toast.success(data.message || `Updated ${guestName} to ${data.category_name}`);
      }
    } else {
      if (window.Toast) window.Toast.error(data.message || 'Failed to update category.');
    }
  } catch (err) {
    if (window.Toast) window.Toast.error('Network error updating category.');
  }
}

// Category Management AJAX Functions
async function submitCreateCategory() {
  const name = document.getElementById('newCatName').value.trim();
  const color = document.getElementById('newCatColor').value;
  const desc = document.getElementById('newCatDesc').value.trim();

  if (!name) {
    if (window.Toast) window.Toast.warning('Please enter a category name.');
    return;
  }

  const fd = new URLSearchParams();
  fd.append('action', 'create');
  fd.append('name', name);
  fd.append('color', color);
  fd.append('description', desc);

  try {
    const res = await fetch('api/manage_categories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    });
    const data = await res.json();

    if (data.success) {
      if (window.Toast) window.Toast.success(data.message);
      setTimeout(() => location.reload(), 800);
    } else {
      if (window.Toast) window.Toast.error(data.message);
    }
  } catch (e) {
    if (window.Toast) window.Toast.error('Failed to create category.');
  }
}

async function editCategoryPrompt(id, currentName, currentColor) {
  const result = await window.showConfirmDialog({
    title: 'EDIT ACCESS CATEGORY',
    subtitle: 'Category Settings',
    message: `Update name and color for <strong>${currentName}</strong>`,
    input: true,
    inputLabel: 'Category Name:',
    inputValue: currentName,
    inputRequired: true,
    confirmText: 'Save Changes'
  });

  if (!result || !result.confirmed) return;
  const newName = (result.value || '').trim();
  if (!newName) return;

  const fd = new URLSearchParams();
  fd.append('action', 'edit');
  fd.append('id', id);
  fd.append('name', newName);
  fd.append('color', currentColor);

  try {
    const res = await fetch('api/manage_categories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    });
    const data = await res.json();

    if (data.success) {
      if (window.Toast) window.Toast.success(data.message);
      setTimeout(() => location.reload(), 800);
    } else {
      if (window.Toast) window.Toast.error(data.message);
    }
  } catch (e) {
    if (window.Toast) window.Toast.error('Error updating category.');
  }
}

async function deleteCategoryPrompt(id, name) {
  const result = await window.showConfirmDialog({
    title: 'DELETE ACCESS CATEGORY?',
    subtitle: 'Security Tier Removal',
    message: `Are you sure you want to delete <strong>${name}</strong>?<br><br>Categories with active guests assigned cannot be deleted.`,
    confirmText: 'Delete Category',
    type: 'danger'
  });

  if (!result || !result.confirmed) return;

  const fd = new URLSearchParams();
  fd.append('action', 'delete');
  fd.append('id', id);

  try {
    const res = await fetch('api/manage_categories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    });
    const data = await res.json();

    if (data.success) {
      if (window.Toast) window.Toast.success(data.message);
      const row = document.getElementById(`cat-tr-${id}`);
      if (row) row.remove();
    } else {
      if (window.Toast) window.Toast.error(data.message);
    }
  } catch (e) {
    if (window.Toast) window.Toast.error('Failed to delete category.');
  }
}

async function confirmDeleteGuest(guestId, guestName, eventId) {
  const result = await window.showConfirmDialog({
    title: 'DELETE GUEST RECORD?',
    subtitle: 'Guest directory and pass revocation',
    message: `Are you sure you want to remove <strong>${guestName}</strong>?<br><br>Their digital pass, QR tokens, and gate access rights will be permanently revoked.`,
    confirmText: 'Delete Guest',
    cancelText: 'Cancel',
    type: 'danger'
  });

  if (result && result.confirmed) {
    location.href = `guests.php?event_id=${eventId}&delete_id=${guestId}`;
  }
}

function filterByCategory(category) {
  const input = document.querySelector('[data-table-search="#guestsTable"]');
  if (input) {
    input.value = category;
    input.dispatchEvent(new Event('input'));
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
