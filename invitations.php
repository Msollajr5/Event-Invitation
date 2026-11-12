<?php
$pageTitle = 'Digital Passes Registry';
$currentPage = 'invitations';
require_once __DIR__ . '/config/db.php';
require_role(2);

$selectedEventId = (int)($_GET['event_id'] ?? 1);
$allEvents = db_fetch_all("SELECT id, title FROM events ORDER BY start_date DESC");

$invitations = db_fetch_all("
    SELECT i.*, g.full_name as guest_name, g.phone, g.email,
           gc.name as category_name, e.title as event_title,
           qt.token, qt.scan_count, qt.last_scanned_at
    FROM invitations i
    JOIN guests g ON i.guest_id = g.id
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    JOIN events e ON i.event_id = e.id
    LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
    WHERE i.event_id = :eid
    ORDER BY i.id DESC
", ['eid' => $selectedEventId]);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Digital Passes Registry</h1>
    <p class="page-subtitle">Track generated QR passes, verification tokens, and dispatch states</p>
  </div>
  <div class="page-actions">
    <form method="GET" action="invitations.php" style="display:flex;gap:10px;align-items:center;">
      <select name="event_id" class="form-control" style="width:auto;min-width:240px;" onchange="this.form.submit()">
        <?php foreach ($allEvents as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $selectedEventId ? 'selected' : '' ?>>
            <?= e($ev['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="distribution.php?event_id=<?= $selectedEventId ?>" class="btn btn-primary">
      <?= svgIcon('send', 16) ?> Distribution Center
    </a>
  </div>
</div>

<!-- Search Bar -->
<div class="filter-bar">
  <div class="search-box">
    <?= svgIcon('search', 16) ?>
    <input type="text" placeholder="Search passes by guest name, token, or reference code..." data-table-search="#invitationsTable">
  </div>
</div>

<!-- Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table" id="invitationsTable">
      <thead>
        <tr>
          <th>Reference Code</th>
          <th>Attendee Name</th>
          <th>VIP Category</th>
          <th>QR Token Key</th>
          <th>Pass State</th>
          <th>Scans</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invitations)): ?>
          <tr class="empty-row"><td colspan="7">No digital passes generated for this event yet.</td></tr>
        <?php else: ?>
          <?php foreach ($invitations as $inv): 
            $passUrl = APP_URL . '/pass.php?token=' . urlencode($inv['token'] ?: $inv['reference_code'] ?: '');
          ?>
            <tr>
              <td>
                <strong style="font-family:monospace;"><?= e($inv['reference_code']) ?></strong>
              </td>
              <td>
                <strong><?= e($inv['guest_name']) ?></strong>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($inv['email'] ?: $inv['phone']) ?></div>
              </td>
              <td>
                <span class="badge badge-primary" style="background:rgba(255,122,0,0.1);color:#FF7A00;border:1px solid rgba(255,122,0,0.25);">
                  <?= e($inv['category_name'] ?: 'Regular') ?>
                </span>
              </td>
              <td>
                <code><?= e($inv['token'] ?: '—') ?></code>
              </td>
              <td>
                <?php if ($inv['is_checked_in']): ?>
                  <span class="badge badge-success"><?= svgIcon('check', 12) ?> Admitted</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Active</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-secondary"><?= $inv['scan_count'] ?? 0 ?> scans</span>
              </td>
              <td style="text-align:right;">
                <div class="actions" style="justify-content:flex-end;">
                  <a href="pass.php?token=<?= urlencode($inv['token']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <?= svgIcon('ticket', 14) ?> Open Pass
                  </a>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="copyToClipboard('<?= $passUrl ?>', 'Pass link copied!')">
                    <?= svgIcon('copy', 14) ?> Copy
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
