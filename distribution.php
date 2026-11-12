<?php
$pageTitle = 'Distribution Hub';
$currentPage = 'distribution';
require_once __DIR__ . '/config/db.php';
require_role(2);

$selectedEventId = (int)($_GET['event_id'] ?? 1);
$allEvents = db_fetch_all("SELECT id, title FROM events ORDER BY start_date DESC");

// Handle Batch Dispatch Trigger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_dispatch') {
    db_query("UPDATE invitations SET status = 'delivered', delivered_at = CURRENT_TIMESTAMP WHERE event_id = :id AND status != 'delivered'", ['id' => $selectedEventId]);
    flash_set('success', 'Batch invitation dispatch queue processed! All digital passes marked delivered.');
    header('Location: distribution.php?event_id=' . $selectedEventId);
    exit;
}

$recipients = db_fetch_all("
    SELECT g.*, gc.name as category_name, i.id as invitation_id, i.reference_code, i.status as inv_status, qt.token
    FROM guests g
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    JOIN invitations i ON i.guest_id = g.id
    LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
    WHERE g.event_id = :eid
    ORDER BY g.id ASC
", ['eid' => $selectedEventId]);

$totalRecipients = count($recipients);
$totalDelivered = count(array_filter($recipients, fn($r) => $r['inv_status'] === 'delivered'));

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Distribution Hub</h1>
    <p class="page-subtitle">Batch dispatch digital passes via WhatsApp and multi-channel messaging</p>
  </div>
  <div class="page-actions">
    <form method="POST" id="batchDispatchForm" action="distribution.php?event_id=<?= $selectedEventId ?>">
      <input type="hidden" name="action" value="batch_dispatch">
      <button type="button" class="btn btn-primary" onclick="confirmBatchDispatch()">
        <?= svgIcon('send', 16) ?> Batch Dispatch All Passes
      </button>
    </form>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon orange">
      <?= svgIcon('users', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalRecipients) ?></div>
      <div class="stat-label">Total Recipients in Queue</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <?= svgIcon('check-circle', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalDelivered) ?></div>
      <div class="stat-label">Passes Delivered</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">
      <?= svgIcon('whatsapp', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format(max(0, $totalRecipients - $totalDelivered)) ?></div>
      <div class="stat-label">Pending Broadcast</div>
    </div>
  </div>
</div>

<!-- Recipients List Card -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Attendee Dispatch Queue</h3>
    <div class="search-box">
      <?= svgIcon('search', 14) ?>
      <input type="text" placeholder="Filter recipients..." data-table-search="#dispatchTable">
    </div>
  </div>
  <div class="table-responsive">
    <table class="data-table" id="dispatchTable">
      <thead>
        <tr>
          <th>Invitee</th>
          <th>VIP Tier</th>
          <th>Phone (WhatsApp)</th>
          <th>Reference Code</th>
          <th>Delivery Status</th>
          <th style="text-align:right;">1-Click Dispatch</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recipients)): ?>
          <tr class="empty-row"><td colspan="6">No recipients in queue.</td></tr>
        <?php else: ?>
          <?php foreach ($recipients as $r): 
            $passUrl = APP_URL . '/pass.php?token=' . urlencode($r['token'] ?: $r['reference_code'] ?: '');
            $waMsg = urlencode("Hello " . $r['full_name'] . ", here is your official digital pass: " . $passUrl);
            $cleanPhone = preg_replace('/[^0-9]/', '', $r['phone']);
            $waLink = !empty($cleanPhone) ? "https://wa.me/{$cleanPhone}?text={$waMsg}" : '#';
          ?>
            <tr>
              <td>
                <strong><?= e($r['full_name']) ?></strong>
              </td>
              <td>
                <span class="badge badge-primary" style="background:rgba(255,122,0,0.1);color:#FF7A00;border:1px solid rgba(255,122,0,0.25);">
                  <?= e($r['category_name'] ?: 'Regular') ?>
                </span>
              </td>
              <td><?= e($r['phone'] ?: 'No phone') ?></td>
              <td><code><?= e($r['reference_code']) ?></code></td>
              <td>
                <?php if ($r['inv_status'] === 'delivered'): ?>
                  <span class="badge badge-success">Delivered</span>
                <?php else: ?>
                  <span class="badge badge-warning">Queued</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <div class="actions" style="justify-content:flex-end;">
                  <?php if (!empty($cleanPhone)): ?>
                    <a href="<?= $waLink ?>" target="_blank" class="btn btn-secondary btn-sm" style="color:#059669;font-weight:700;">
                      <?= svgIcon('whatsapp', 14) ?> Send via WhatsApp
                    </a>
                  <?php endif; ?>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="copyToClipboard('<?= $passUrl ?>', 'Pass link copied!')">
                    <?= svgIcon('copy', 14) ?>
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
async function confirmBatchDispatch() {
  const confirmed = await window.showConfirmDialog({
    title: 'BATCH DISPATCH PASSES?',
    subtitle: 'Multi-channel messaging & WhatsApp queue',
    message: 'Are you sure you want to dispatch personalized digital passes with live QR verification codes to all pending invitees?',
    confirmText: 'Dispatch Passes Now',
    cancelText: 'Cancel',
    type: 'primary'
  });

  if (confirmed) {
    document.getElementById('batchDispatchForm').submit();
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
