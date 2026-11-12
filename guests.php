<?php
$pageTitle = 'Guest Directory';
$currentPage = 'guests';
require_once __DIR__ . '/config/db.php';
require_role(2);

$selectedEventId = (int)($_GET['event_id'] ?? 1);

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
        $link = APP_URL . '/pass.php?token=' . ($r['token'] ?: '');
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
            'organization_id' => 1,
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
            'organization_id' => 1,
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

        flash_set('success', 'Guest "' . $fullName . '" added and digital pass generated!');
        header('Location: guests.php?event_id=' . $selectedEventId);
        exit;
    }
}

// Handle Delete Guest
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $inv = db_fetch_one("SELECT id FROM invitations WHERE guest_id = :gid", ['gid' => $delId]);
    if ($inv) {
        db_query("DELETE FROM qr_tokens WHERE invitation_id = :iid", ['iid' => $inv['id']]);
        db_query("DELETE FROM check_ins WHERE invitation_id = :iid", ['iid' => $inv['id']]);
        db_query("DELETE FROM rsvp_records WHERE invitation_id = :iid", ['iid' => $inv['id']]);
        db_query("DELETE FROM invitations WHERE id = :iid", ['iid' => $inv['id']]);
    }
    db_query("DELETE FROM guests WHERE id = :gid", ['gid' => $delId]);
    audit_log('GUEST_MANAGEMENT', 'DELETE_GUEST', "Removed guest ID #{$delId} and revoked associated digital passes.");
    flash_set('success', 'Guest record removed successfully.');
    header('Location: guests.php?event_id=' . $selectedEventId);
    exit;
}

// Fetch Events for filter dropdown
$allEvents = db_fetch_all("SELECT id, title FROM events ORDER BY start_date DESC");

// Fetch Guests
$guests = db_fetch_all("
    SELECT g.*, gc.name as category_name, gc.color as category_color,
           i.reference_code, i.is_checked_in, qt.token,
           (SELECT response FROM rsvp_records WHERE guest_id = g.id ORDER BY id DESC LIMIT 1) as rsvp_status
    FROM guests g
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    LEFT JOIN invitations i ON i.guest_id = g.id
    LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
    WHERE g.event_id = :eid
    ORDER BY g.id DESC
", ['eid' => $selectedEventId]);

$categories = db_fetch_all("SELECT * FROM guest_categories ORDER BY id ASC");

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Guest Directory</h1>
    <p class="page-subtitle">Manage VIP invitees, track pass distribution, and generate WhatsApp links</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" data-modal-open="addGuestModal">
      <?= svgIcon('plus', 16) ?> Add Individual Guest
    </button>
    <a href="guests.php?event_id=<?= $selectedEventId ?>&action=export_csv" class="btn btn-secondary">
      <?= svgIcon('download', 16) ?> Export CSV Roster
    </a>
  </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
  <div class="search-box">
    <?= svgIcon('search', 16) ?>
    <input type="text" placeholder="Search guests by name, phone, email or token..." data-table-search="#guestsTable">
  </div>

  <form method="GET" action="guests.php" style="display:flex;gap:10px;align-items:center;">
    <select name="event_id" class="form-control" style="width:auto;min-width:240px;" onchange="this.form.submit()">
      <?php foreach ($allEvents as $ev): ?>
        <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $selectedEventId ? 'selected' : '' ?>>
          <?= e($ev['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<!-- Guests Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table" id="guestsTable">
      <thead>
        <tr>
          <th>Guest Name</th>
          <th>Category</th>
          <th>Contact</th>
          <th>Pass Token</th>
          <th>Allowed</th>
          <th>RSVP</th>
          <th>Access</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($guests)): ?>
          <tr class="empty-row"><td colspan="8">No guests registered for this event yet.</td></tr>
        <?php else: ?>
          <?php foreach ($guests as $g): 
            $passUrl = APP_URL . '/pass.php?token=' . urlencode($g['token'] ?: $g['reference_code'] ?: '');
            $waText = urlencode("Hello " . $g['full_name'] . ", here is your VIP digital pass for the event: " . $passUrl);
            $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $g['phone']) . "?text=" . $waText;
          ?>
            <tr>
              <td>
                <div style="font-weight:700;color:var(--text-primary);"><?= e($g['full_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                  Ref: <code><?= e($g['reference_code'] ?: '—') ?></code>
                </div>
              </td>
              <td>
                <span class="badge badge-primary" style="background:rgba(255,122,0,0.1);color:#FF7A00;border:1px solid rgba(255,122,0,0.25);">
                  <?= e($g['category_name'] ?: 'Regular') ?>
                </span>
              </td>
              <td>
                <div style="font-size:0.84rem;font-weight:500;"><?= e($g['email'] ?: 'No email') ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($g['phone'] ?: 'No phone') ?></div>
              </td>
              <td>
                <code><?= e($g['token'] ?: '—') ?></code>
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

<script>
async function confirmDeleteGuest(guestId, guestName, eventId) {
  const confirmed = await window.showConfirmDialog({
    title: 'DELETE GUEST RECORD?',
    subtitle: 'Guest directory and pass revocation',
    message: `Are you sure you want to remove <strong>${guestName}</strong>?<br><br>Their digital pass, QR tokens, and access rights will be permanently revoked.`,
    confirmText: 'Delete Guest',
    cancelText: 'Cancel',
    type: 'danger'
  });

  if (confirmed) {
    location.href = `guests.php?event_id=${eventId}&delete_id=${guestId}`;
  }
}
</script>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
