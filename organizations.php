<?php
$pageTitle = 'Organization Profile';
$currentPage = 'organizations';
require_once __DIR__ . '/config/db.php';
require_role(1);

$user = auth_user();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Organization Profile</h1>
    <p class="page-subtitle">Manage organization credentials, verification branding and workspace keys</p>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Corporate Profile</h3>
    </div>
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:18px;margin-bottom:24px;">
        <div style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg, #FF7A00, #E96800);color:#FFFFFF;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(255,122,0,0.32);">
          <?= svgIcon('building', 32) ?>
        </div>
        <div>
          <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-primary);margin-bottom:2px;">Leula Events & Tech</h2>
          <span class="badge badge-success">Verified Enterprise Account</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Organization Name</label>
        <input type="text" class="form-control" value="Leula Events Management Platform" readonly>
      </div>

      <div class="form-group">
        <label class="form-label">Headquarters / Regional Office</label>
        <input type="text" class="form-control" value="Ohio Street, City Centre, Dar es Salaam, Tanzania" readonly>
      </div>

      <div class="form-group">
        <label class="form-label">Support Email Contact</label>
        <input type="text" class="form-control" value="support@leulaevents.com" readonly>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Platform Quota & Subscriptions</h3>
    </div>
    <div class="card-body">
      <div style="margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.85rem;">
          <span style="color:var(--text-secondary);font-weight:600;">Monthly Pass Ingress Quota</span>
          <strong style="color:var(--accent);">3,420 / 50,000 passes</strong>
        </div>
        <div style="height:8px;background:var(--surface-3);border-radius:99px;overflow:hidden;">
          <div style="height:100%;background:#FF7A00;width:7%;border-radius:99px;"></div>
        </div>
      </div>

      <div style="background:var(--surface-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:18px;margin-bottom:20px;">
        <div style="font-weight:700;color:var(--text-primary);margin-bottom:4px;">Enterprise Subscription</div>
        <div style="font-size:0.85rem;color:var(--text-secondary);line-height:1.5;">
          Includes unlimited gate scanner terminals, sub-second anti-passback rule processing, and custom vector QR card templates.
        </div>
      </div>

      <button type="button" class="btn btn-secondary w-100" onclick="showToast('Subscription is current and active', 'info')">
        <?= svgIcon('check', 16) ?> Subscription Active
      </button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
