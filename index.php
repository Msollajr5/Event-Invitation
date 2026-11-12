<?php
require_once __DIR__ . '/config/db.php';
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> &bull; <?= APP_TAGLINE ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/portal.css?v=<?= time() ?>">
</head>
<body style="background:var(--bg);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;">

<div style="width:100%;max-width:880px;">
  <!-- Main Hero Card -->
  <div class="card" style="padding:48px 40px;text-align:center;border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:68px;height:68px;border-radius:20px;background:linear-gradient(135deg, #FF7A00, #E96800);color:#FFFFFF;margin-bottom:20px;box-shadow:0 8px 24px rgba(255,122,0,0.38);">
      <?= svgIcon('qr', 36) ?>
    </div>

    <span class="badge badge-primary" style="margin-bottom:14px;padding:6px 14px;font-size:0.78rem;">
      Enterprise Event Technology Platform
    </span>

    <h1 style="font-size:2.4rem;font-weight:800;color:var(--text-primary);line-height:1.2;margin-bottom:12px;">
      Leula Events Management
    </h1>
    <p style="font-size:1.05rem;color:var(--text-secondary);max-width:620px;margin:0 auto 32px;line-height:1.6;">
      Universal digital invitation design, multi-channel guest distribution, and high-speed anti-passback QR access control system.
    </p>

    <!-- CTAs -->
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:44px;">
      <?php if ($user): ?>
        <a href="dashboard.php" class="btn btn-primary btn-lg">
          <?= svgIcon('dashboard', 18) ?> Open Control Dashboard
        </a>
        <a href="designer.php" class="btn btn-secondary btn-lg">
          <?= svgIcon('palette', 18) ?> Card Designer Studio
        </a>
      <?php else: ?>
        <a href="login.php" class="btn btn-primary btn-lg">
          <?= svgIcon('logout', 18) ?> Sign In to Portal
        </a>
        <a href="designer.php" class="btn btn-secondary btn-lg">
          <?= svgIcon('palette', 18) ?> Card Studio
        </a>
      <?php endif; ?>
      <a href="scanner.php" class="btn btn-secondary btn-lg">
        <?= svgIcon('qr', 18) ?> Launch Gate Scanner
      </a>
      <a href="pass.php?token=DEMO-TOKEN-VIP-REG-01" class="btn btn-outline btn-lg" target="_blank">
        <?= svgIcon('ticket', 18) ?> View Demo Pass
      </a>
    </div>

    <!-- Feature Highlights -->
    <div class="stats-grid" style="margin-bottom:0;text-align:left;">
      <div class="stat-card" style="padding:18px;">
        <div class="stat-icon orange" style="width:40px;height:40px;">
          <?= svgIcon('palette', 18) ?>
        </div>
        <div>
          <div style="font-size:0.9rem;font-weight:700;color:var(--text-primary);">WYSIWYG Designer</div>
          <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">Custom VIP card canvases</div>
        </div>
      </div>

      <div class="stat-card" style="padding:18px;">
        <div class="stat-icon green" style="width:40px;height:40px;">
          <?= svgIcon('shield', 18) ?>
        </div>
        <div>
          <div style="font-size:0.9rem;font-weight:700;color:var(--text-primary);">Anti-Passback</div>
          <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">Sub-second duplicate catch</div>
        </div>
      </div>

      <div class="stat-card" style="padding:18px;">
        <div class="stat-icon blue" style="width:40px;height:40px;">
          <?= svgIcon('whatsapp', 18) ?>
        </div>
        <div>
          <div style="font-size:0.9rem;font-weight:700;color:var(--text-primary);">Instant Dispatch</div>
          <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">1-Click WhatsApp distribution</div>
        </div>
      </div>

      <div class="stat-card" style="padding:18px;">
        <div class="stat-icon yellow" style="width:40px;height:40px;">
          <?= svgIcon('chart', 18) ?>
        </div>
        <div>
          <div style="font-size:0.9rem;font-weight:700;color:var(--text-primary);">Live Telemetry</div>
          <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">Gate check-in velocity</div>
        </div>
      </div>
    </div>
  </div>

  <div style="text-align:center;color:var(--text-muted);font-size:0.8rem;margin-top:20px;">
    &copy; <?= date('Y') ?> Leula Events Management System. All rights reserved.
  </div>
</div>

</body>
</html>
