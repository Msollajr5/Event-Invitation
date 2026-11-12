<?php
$pageTitle = 'RSVP Management';
$currentPage = 'rsvp';
require_once __DIR__ . '/config/db.php';
require_role(2);

$selectedEventId = (int)($_GET['event_id'] ?? 1);
$filterStatus = trim($_GET['status'] ?? 'all');

$allEvents = db_fetch_all("SELECT id, title FROM events ORDER BY start_date DESC");

// Metrics
$totalAccepted = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'accepted'", ['id' => $selectedEventId])['cnt'] ?? 0);
$totalDeclined = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'declined'", ['id' => $selectedEventId])['cnt'] ?? 0);
$totalTentative = (int)(db_fetch_one("SELECT COUNT(*) as cnt FROM rsvp_records WHERE event_id = :id AND response = 'tentative'", ['id' => $selectedEventId])['cnt'] ?? 0);

$whereSql = "WHERE g.event_id = :id";
$params = ['id' => $selectedEventId];

if ($filterStatus === 'accepted') {
    $whereSql .= " AND r.response = 'accepted'";
} elseif ($filterStatus === 'declined') {
    $whereSql .= " AND r.response = 'declined'";
} elseif ($filterStatus === 'tentative') {
    $whereSql .= " AND r.response = 'tentative'";
} elseif ($filterStatus === 'pending') {
    $whereSql .= " AND r.response IS NULL";
}

$rsvpList = db_fetch_all("
    SELECT g.full_name, g.email, g.phone, g.permitted_attendees,
           gc.name as category_name, i.reference_code, qt.token,
           r.response, r.dietary_notes, r.message, r.responded_at
    FROM guests g
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    LEFT JOIN invitations i ON i.guest_id = g.id
    LEFT JOIN qr_tokens qt ON qt.invitation_id = i.id
    LEFT JOIN rsvp_records r ON r.invitation_id = i.id
    {$whereSql}
    ORDER BY r.responded_at DESC, g.id DESC
", $params);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">RSVP Management</h1>
    <p class="page-subtitle">Track invitee confirmations, seating headcounts and dietary restrictions</p>
  </div>
  <div class="page-actions">
    <form method="GET" action="rsvp.php" style="display:flex;gap:10px;align-items:center;">
      <select name="event_id" class="form-control" style="width:auto;min-width:240px;" onchange="this.form.submit()">
        <?php foreach ($allEvents as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $selectedEventId ? 'selected' : '' ?>>
            <?= e($ev['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon green">
      <?= svgIcon('check', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalAccepted) ?></div>
      <div class="stat-label">Attending (Confirmed)</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon yellow">
      <?= svgIcon('clock', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalTentative) ?></div>
      <div class="stat-label">Tentative / Pending</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon red">
      <?= svgIcon('x', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalDeclined) ?></div>
      <div class="stat-label">Declined Invitations</div>
    </div>
  </div>
</div>

<!-- Filter Tabs -->
<div class="tabs">
  <a href="rsvp.php?event_id=<?= $selectedEventId ?>&status=all" class="tab-btn <?= $filterStatus === 'all' ? 'active' : '' ?>">
    All Guests (<?= count($rsvpList) ?>)
  </a>
  <a href="rsvp.php?event_id=<?= $selectedEventId ?>&status=accepted" class="tab-btn <?= $filterStatus === 'accepted' ? 'active' : '' ?>">
    Attending (<?= $totalAccepted ?>)
  </a>
  <a href="rsvp.php?event_id=<?= $selectedEventId ?>&status=tentative" class="tab-btn <?= $filterStatus === 'tentative' ? 'active' : '' ?>">
    Tentative (<?= $totalTentative ?>)
  </a>
  <a href="rsvp.php?event_id=<?= $selectedEventId ?>&status=declined" class="tab-btn <?= $filterStatus === 'declined' ? 'active' : '' ?>">
    Declined (<?= $totalDeclined ?>)
  </a>
</div>

<!-- RSVP Table -->
<div class="card">
  <div class="card-header">
    <div class="search-box">
      <?= svgIcon('search', 14) ?>
      <input type="text" placeholder="Search by attendee name or dietary request..." data-table-search="#rsvpTable">
    </div>
  </div>
  <div class="table-responsive">
    <table class="data-table" id="rsvpTable">
      <thead>
        <tr>
          <th>Invitee</th>
          <th>VIP Category</th>
          <th>RSVP Status</th>
          <th>Pax</th>
          <th>Dietary / Notes</th>
          <th>Responded At</th>
          <th>Pass Link</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rsvpList)): ?>
          <tr class="empty-row"><td colspan="7">No matching RSVP records found.</td></tr>
        <?php else: ?>
          <?php foreach ($rsvpList as $r): ?>
            <tr>
              <td>
                <strong><?= e($r['full_name']) ?></strong>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($r['email'] ?: $r['phone']) ?></div>
              </td>
              <td>
                <span class="badge badge-primary" style="background:rgba(255,122,0,0.1);color:#FF7A00;border:1px solid rgba(255,122,0,0.25);">
                  <?= e($r['category_name'] ?: 'Regular') ?>
                </span>
              </td>
              <td>
                <?php if ($r['response'] === 'accepted'): ?>
                  <span class="badge badge-success">Attending</span>
                <?php elseif ($r['response'] === 'declined'): ?>
                  <span class="badge badge-danger">Declined</span>
                <?php elseif ($r['response'] === 'tentative'): ?>
                  <span class="badge badge-warning">Tentative</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Pending Response</span>
                <?php endif; ?>
              </td>
              <td><?= $r['permitted_attendees'] ?> pax</td>
              <td>
                <?= e($r['dietary_notes'] ?: ($r['message'] ?: '—')) ?>
              </td>
              <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($r['responded_at'] ?: 'Not responded') ?></td>
              <td>
                <?php if (!empty($r['token'])): ?>
                  <a href="pass.php?token=<?= urlencode($r['token']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <?= svgIcon('ticket', 14) ?> View Pass
                  </a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
