<?php
$pageTitle = 'Settings';
$currentPage = 'settings';
require_once __DIR__ . '/config/db.php';
require_role(1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    flash_set('success', 'System preferences and security policies updated successfully.');
    header('Location: settings.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">System Settings</h1>
    <p class="page-subtitle">Configure gate verification policies, anti-passback rules, and branding preferences</p>
  </div>
</div>

<form method="POST" action="settings.php">
  <div class="grid grid-2">
    <!-- Verification Rules -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Access Control & Gate Policies</h3>
      </div>
      <div class="card-body">
        <div class="form-group" style="margin-bottom:20px;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <strong>Strict Anti-Passback Mode</strong>
              <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:2px;">
                Instantly flag duplicate passes if presented more than once at any entrance checkpoint.
              </div>
            </div>
            <input type="checkbox" checked style="width:20px;height:20px;accent-color:#FF7A00;">
          </div>
        </div>

        <div class="form-group" style="margin-bottom:20px;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <strong>Audio Chime Synthesizer</strong>
              <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:2px;">
                Play high/low frequency Web Audio beeps upon valid or rejected QR verification scans.
              </div>
            </div>
            <input type="checkbox" checked style="width:20px;height:20px;accent-color:#FF7A00;">
          </div>
        </div>

        <div class="form-group">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <strong>Strict Gate Allocation</strong>
              <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:2px;">
                Require VIP attendees to enter exclusively through their designated gate tier.
              </div>
            </div>
            <input type="checkbox" style="width:20px;height:20px;accent-color:#FF7A00;">
          </div>
        </div>
      </div>
    </div>

    <!-- Branding & Environment -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Brand Aesthetics & Identifiers</h3>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">System Brand Title</label>
          <input type="text" class="form-control" value="Leula Events" readonly>
        </div>

        <div class="form-group">
          <label class="form-label">Primary Brand Accent Color</label>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:8px;background:#FF7A00;border:1px solid rgba(0,0,0,0.1);"></div>
            <input type="text" class="form-control" value="#FF7A00 (Liquid Orange)" readonly>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Public App URL Base</label>
          <input type="text" class="form-control" value="<?= APP_URL ?>" readonly>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:10px;">
    <button type="submit" class="btn btn-primary">
      <?= svgIcon('check', 16) ?> Save Settings Changes
    </button>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
