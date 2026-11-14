<?php
require_once __DIR__ . '/config/db.php';

$token = trim($_GET['token'] ?? 'DEMO-TOKEN-VIP-REG-01');

// Fetch pass and guest information
$pass = db_fetch_one("
    SELECT qt.*, i.id as invitation_id, i.reference_code, i.is_checked_in,
           g.id as guest_id, g.full_name as guest_name, g.permitted_attendees,
           gc.name as category_name, gc.color as category_color,
           e.id as event_id, e.title as event_title, e.start_date, e.start_time, e.end_time,
           e.venue_name, e.venue_address, e.google_maps_link, e.description,
           (SELECT response FROM rsvp_records WHERE invitation_id = i.id ORDER BY id DESC LIMIT 1) as rsvp_response
    FROM qr_tokens qt
    JOIN invitations i ON qt.invitation_id = i.id
    JOIN guests g ON i.guest_id = g.id
    LEFT JOIN guest_categories gc ON g.guest_category_id = gc.id
    JOIN events e ON i.event_id = e.id
    WHERE qt.token = :tok OR i.reference_code = :tok2
    LIMIT 1
", ['tok' => $token, 'tok2' => $token]);

// Handle iCalendar (.ics) download
if (isset($_GET['download']) && $_GET['download'] === 'ics' && $pass) {
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="invitation.ics"');
    $dtStart = date('Ymd\THis', strtotime($pass['start_date'] . ' ' . $pass['start_time']));
    $dtEnd = date('Ymd\THis', strtotime($pass['start_date'] . ' ' . ($pass['end_time'] ?: '23:30')));
    echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Leula Events//Digital Pass//EN\r\n";
    echo "BEGIN:VEVENT\r\n";
    echo "UID:" . $pass['reference_code'] . "@leulaevents.com\r\n";
    echo "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
    echo "DTSTART:{$dtStart}\r\n";
    echo "DTEND:{$dtEnd}\r\n";
    echo "SUMMARY:" . $pass['event_title'] . "\r\n";
    echo "LOCATION:" . $pass['venue_name'] . "\r\n";
    echo "DESCRIPTION:Your personalized VIP invitation pass: " . APP_URL . "/pass.php?token=" . $pass['token'] . "\r\n";
    echo "END:VEVENT\r\n";
    echo "END:VCALENDAR\r\n";
    exit;
}

// Fetch event-specific custom design from Canva Studio if saved
$designConfig = [];
$savedDesignRow = null;
if ($pass) {
    $savedDesignRow = db_fetch_one("SELECT id, canvas_data, canva_bg_data, canva_bg_mime FROM invitation_designs WHERE event_id = :eid ORDER BY id DESC LIMIT 1", ['eid' => $pass['event_id']]);
    if ($savedDesignRow && !empty($savedDesignRow['canvas_data'])) {
        $designConfig = json_decode($savedDesignRow['canvas_data'], true) ?: [];
    }
}

// Canva Cloned Card Mode Configuration
$cardMode = $designConfig['cardMode'] ?? 'canva';

// Resolve canvaBgData: check the BLOB column first, then fall back to JSON (legacy)
$canvaBgData = null;
if ($savedDesignRow && !empty($savedDesignRow['canva_bg_data'])) {
    $rawBlob = is_resource($savedDesignRow['canva_bg_data'])
        ? stream_get_contents($savedDesignRow['canva_bg_data'])
        : $savedDesignRow['canva_bg_data'];
    $mime = $savedDesignRow['canva_bg_mime'] ?: 'image/png';
    $canvaBgData = 'data:' . $mime . ';base64,' . base64_encode($rawBlob);
} elseif (!empty($designConfig['canvaBgData']) && $designConfig['canvaBgData'] !== '__STORED_IN_DB__') {
    $canvaBgData = $designConfig['canvaBgData'];
}
$qrXPercent = (float)($designConfig['qrXPercent'] ?? 50.0);
$qrYPercent = (float)($designConfig['qrYPercent'] ?? 78.0);
$qrSizePercent = (float)($designConfig['qrSizePercent'] ?? 28.0);
$qrRotation = (int)($designConfig['qrRotation'] ?? 0);
$qrPadding = (int)($designConfig['qrPadding'] ?? 10);
$qrRadius = (int)($designConfig['qrRadius'] ?? 18);
$qrBadgeStyle = $designConfig['qrBadgeStyle'] ?? 'badge-glass';
$canvaQrDark = $designConfig['canvaQrDark'] ?? '#09090B';
$canvaQrLight = $designConfig['canvaQrLight'] ?? '#FFFFFF';
$qrBgTransparent = !empty($designConfig['qrBgTransparent']);
$badgeTextColor = $designConfig['badgeTextColor'] ?? ($qrBadgeStyle === 'badge-dark' ? '#FFFFFF' : '#09090B');
$showAttendee = ($designConfig['showAttendee'] ?? true) !== false;
$showRef = ($designConfig['showRef'] ?? true) !== false;
$showTier = ($designConfig['showTier'] ?? true) !== false;

// Studio Mode Configuration
$fontFamily = $designConfig['fontFamily'] ?? "'Sora', sans-serif";
$headerColor1 = $designConfig['headerColor1'] ?? '#FF7A00';
$headerColor2 = $designConfig['headerColor2'] ?? '#E96800';
$headingColor = $designConfig['headingColor'] ?? '#FFFFFF';
$bodyBg = $designConfig['bodyBg'] ?? '#FFFFFF';
$textColor = $designConfig['textColor'] ?? '#09090B';
$accentColor = $designConfig['accentColor'] ?? '#FF7A00';
$borderColor = $designConfig['borderColor'] ?? null;
$borderFrame = $designConfig['borderFrame'] ?? 'none';
$seal = $designConfig['seal'] ?? 'ribbon';
$texture = $designConfig['texture'] ?? 'none';
$qrDark = $designConfig['qrDark'] ?? '#09090B';
$logoData = $designConfig['logoData'] ?? null;
$orientation = $designConfig['orientation'] ?? 'portrait';
$badgeText = !empty($designConfig['badge']) ? $designConfig['badge'] : (!empty($pass['category_name']) ? strtoupper($pass['category_name']) . ' PASS' : 'VIP DELEGATE PASS');
$quoteText = $designConfig['quote'] ?? 'Honored to request the pleasure of your company';
$footerNote = $designConfig['footer'] ?? 'Black Tie Required • Non-Transferable Digital QR Ticket';

// Top Card Cover Photo 5-Layout Configuration
$coverImgData = $designConfig['coverImgData'] ?? null;
$coverLayout = $designConfig['coverLayout'] ?? 'top';
$coverHeight = (int)($designConfig['coverHeight'] ?? 190);
$coverPositionY = (int)($designConfig['coverPositionY'] ?? 50);
$coverPositionX = (int)($designConfig['coverPositionX'] ?? 50);
$coverScale = (int)($designConfig['coverScale'] ?? 100);
$coverOverlay = (int)($designConfig['coverOverlay'] ?? 40);
$coverCropFit = $designConfig['coverCropFit'] ?? 'cover';
$coverBrightness = (int)($designConfig['coverBrightness'] ?? 100);
$coverContrast = (int)($designConfig['coverContrast'] ?? 100);
$coverOpacity = (int)($designConfig['coverOpacity'] ?? 100);
$coverTextContrast = $designConfig['coverTextContrast'] ?? 'glass';
$coverHalfSide = $designConfig['coverHalfSide'] ?? 'left';
$coverHalfRatio = (int)($designConfig['coverHalfRatio'] ?? 50);
$coverCustomX = (float)($designConfig['coverCustomX'] ?? 50);
$coverCustomY = (float)($designConfig['coverCustomY'] ?? 35);
$coverCustomW = (int)($designConfig['coverCustomW'] ?? 240);
$coverCustomH = (int)($designConfig['coverCustomH'] ?? 180);
$coverCustomRotation = (int)($designConfig['coverCustomRotation'] ?? 0);
$headerStyle = $designConfig['headerStyle'] ?? 'split';
$showArtwork = !isset($designConfig['showArtwork']) || !empty($designConfig['showArtwork']);
$artworkOpacity = (int)($designConfig['artworkOpacity'] ?? 100);
$decorativeArtwork = $designConfig['decorativeArtwork'] ?? '';
$sampleCouple = $designConfig['sampleCouple'] ?? '';
$scriptFont = $designConfig['scriptFont'] ?? "'Great Vibes', cursive";
$templateId = $designConfig['template'] ?? '';

// Check if dark surface
$isDark = false;
if (str_starts_with($bodyBg, '#') && strlen($bodyBg) === 7) {
    $rgb = hexdec(substr($bodyBg, 1));
    $r = ($rgb >> 16) & 0xff;
    $g = ($rgb >> 8) & 0xff;
    $b = $rgb & 0xff;
    $luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    $isDark = ($luma < 90);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pass ? htmlspecialchars($pass['event_title']) : 'Digital Pass' ?> &bull; <?= APP_NAME ?></title>
  
  <!-- Luxury Typography Fonts Collection -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Bebas+Neue&family=Cinzel:wght@500;600;700;800;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Serif+Display:ital@0;1&family=Great+Vibes&family=Inter:wght@400;500;600;700;800&family=Lato:ital,wght@0,400;0,700;1,400&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Marcellus&family=Montserrat:wght@400;500;600;700;800;900&family=Outfit:wght@400;600;700;800&family=Parisienne&family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,900;1,400;1,600&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Poppins:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@500;700&family=Tangerine:wght@700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="assets/css/portal.css?v=<?= time() ?>">
  <script src="assets/js/luxe_art.js?v=<?= time() ?>"></script>
  <script src="assets/js/templates_library.js?v=<?= time() ?>"></script>
  <link rel="stylesheet" href="assets/css/notifications.css?v=<?= time() ?>">
  <script src="assets/js/notifications.js?v=<?= time() ?>"></script>
  <script src="assets/js/qrcode.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

  <style>
    body {
      background: #F0F2F5;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 30px 16px;
      font-family: 'Inter', sans-serif;
    }

    .pass-container {
      width: 100%;
      max-width: <?= $orientation === 'landscape' ? '640px' : '430px' ?>;
      margin: 0 auto;
    }

    /* ============================================================
       CANVA CLONED CARD STYLES
       ============================================================ */
    .canva-pass-card {
      position: relative;
      width: 100%;
      border-radius: 26px;
      overflow: hidden;
      box-shadow: 0 25px 60px rgba(0,0,0,0.25);
      background: #FFFFFF;
      margin-bottom: 20px;
      user-select: none;
    }

    .canva-pass-img {
      width: 100%;
      height: auto;
      display: block;
      border-radius: 26px;
    }

    /* Floating QR Badge Content on Canva Card */
    .canva-qr-badge-content {
      position: absolute;
      z-index: 10;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      user-select: none;
      box-sizing: border-box;
      transform-origin: center center;
    }

    /* Badge Aesthetics */
    .badge-glass {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1.5px solid rgba(255, 255, 255, 0.9);
      box-shadow: 0 12px 36px rgba(0, 0, 0, 0.22), 0 0 0 1px rgba(255, 122, 0, 0.2);
    }
    .badge-white {
      background: #FFFFFF;
      border: 1.5px solid rgba(0, 0, 0, 0.08);
      box-shadow: 0 12px 34px rgba(0, 0, 0, 0.25);
    }
    .badge-dark {
      background: rgba(15, 23, 42, 0.9);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1.5px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 12px 36px rgba(0, 0, 0, 0.4);
      color: #FFFFFF;
    }
    .badge-dark .badge-text-primary {
      color: #FFFFFF !important;
    }
    .badge-dark .badge-text-secondary {
      color: rgba(255, 255, 255, 0.75) !important;
    }
    .badge-transparent {
      background: transparent !important;
      border: none !important;
      box-shadow: none !important;
      padding: 2px !important;
    }

    /* ============================================================
       STUDIO TEMPLATE STYLES
       ============================================================ */
    .digital-pass-card-custom {
      background: <?= $bodyBg ?>;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: 0 25px 60px rgba(0,0,0,0.18);
      position: relative;
      margin-bottom: 20px;
      <?= $orientation === 'landscape' ? 'display:grid; grid-template-columns: 1.15fr 1fr;' : '' ?>
    }

    .frame-gold-single {
      border: 2px solid #D4AF37 !important;
      box-shadow: 0 25px 60px rgba(212,175,55,0.22) !important;
    }
    .frame-gold-double {
      border: 3px double #D4AF37 !important;
      outline: 1.5px solid #D4AF37;
      outline-offset: -6px;
    }
    .frame-glass-glow {
      border: 2px solid <?= $accentColor ?> !important;
      box-shadow: 0 0 35px <?= $accentColor ?>44 !important;
    }
    .frame-deco-corners {
      border: 2px solid #C28945 !important;
    }

    .texture-overlay {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      pointer-events: none;
      z-index: 1;
      background-size: cover;
      background-position: center;
    }
    .texture-dots {
      background-image: radial-gradient(rgba(0,0,0,0.07) 1.5px, transparent 1.5px);
      background-size: 16px 16px;
    }
    .texture-glow {
      background-image: radial-gradient(circle at 50% 50%, <?= $accentColor ?>22 0%, transparent 70%);
    }
    .texture-marble {
      background-image: radial-gradient(circle at 10% 20%, rgba(212,175,55,0.08) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(255,122,0,0.08) 0%, transparent 40%);
    }
    .texture-stardust {
      background-image: radial-gradient(circle at 30% 30%, rgba(255,215,0,0.15) 1px, transparent 1px),
                        radial-gradient(circle at 70% 60%, rgba(255,215,0,0.12) 1.5px, transparent 1.5px);
      background-size: 32px 32px;
    }

    .digital-pass-header-custom {
      background: linear-gradient(135deg, <?= $headerColor1 ?>, <?= $headerColor2 ?>);
      color: #FFFFFF;
      padding: 30px 24px;
      text-align: center;
      position: relative;
      z-index: 2;
      <?= $orientation === 'landscape' ? 'display:flex; flex-direction:column; justify-content:center;' : '' ?>
    }

    .badge-pill-custom {
      display: inline-block;
      padding: 4px 14px;
      background: rgba(255,255,255,0.22);
      border-radius: 99px;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      margin-bottom: 12px;
      backdrop-filter: blur(4px);
      color: #FFFFFF;
    }

    .event-title-custom {
      font-size: 1.45rem;
      font-weight: 800;
      line-height: 1.25;
      margin: 0 0 6px;
      color: #FFFFFF;
      font-family: <?= $fontFamily ?>;
    }

    .digital-pass-body-custom {
      padding: 24px 24px;
      text-align: center;
      position: relative;
      z-index: 2;
      color: <?= $textColor ?>;
    }

    .guest-name-custom {
      font-size: 1.45rem;
      font-weight: 800;
      color: <?= $textColor ?>;
      margin: 2px 0 12px;
      line-height: 1.2;
      font-family: <?= $fontFamily ?>;
    }

    .seal-pill-custom {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      background: <?= $accentColor ?>18;
      color: <?= $accentColor ?>;
      border: 1px solid <?= $accentColor ?>44;
      border-radius: 99px;
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .qr-frame-custom {
      display: inline-block;
      padding: 10px;
      background: #FFFFFF;
      border-radius: 18px;
      border: 1.5px solid rgba(0,0,0,0.08);
      box-shadow: 0 6px 18px rgba(0,0,0,0.06);
      margin-bottom: 10px;
    }

    .meta-box-custom {
      background: <?= $isDark ? 'rgba(255,255,255,0.06)' : '#F9FAFB' ?>;
      border: 1px solid <?= $isDark ? 'rgba(255,255,255,0.12)' : 'rgba(0,0,0,0.08)' ?>;
      border-radius: 12px;
      padding: 12px 16px;
      text-align: left;
      font-size: 0.82rem;
      margin-bottom: 14px;
      color: <?= $textColor ?>;
    }

    /* 5-Option Cover Photo Layout Styles for Public Pass */
    .card-cover-full-wrap {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      border-radius: inherit;
      overflow: hidden;
      z-index: 0;
      pointer-events: none;
    }

    /* When Full Card Cover is active, remove default background colors across the whole card */
    .cover-layout-full,
    .card-cover-full-active,
    .digital-pass-card-custom.cover-layout-full,
    .digital-pass-card-custom.card-cover-full-active {
      background: transparent !important;
    }
    .cover-layout-full .digital-pass-header-custom,
    .card-cover-full-active .digital-pass-header-custom {
      background: transparent !important;
      background-image: none !important;
      box-shadow: none !important;
    }
    .cover-layout-full .digital-pass-body-custom,
    .card-cover-full-active .digital-pass-body-custom {
      background: transparent !important;
      background-image: none !important;
    }
    .cover-layout-full .meta-box-custom {
      background: rgba(0, 0, 0, 0.42) !important;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      color: #FFFFFF !important;
    }
    .cover-layout-full .meta-box-custom div,
    .cover-layout-full .meta-box-custom span {
      color: #FFFFFF !important;
    }
    .cover-layout-full .guest-name-custom,
    .cover-layout-full #cardAdmitBearer,
    .cover-layout-full #cardQuoteText,
    .cover-layout-full #cardFooterNote {
      color: #FFFFFF !important;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
    }
    .card-cover-full-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .card-cover-full-overlay {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
    }
    .full-cover-glass .digital-pass-header-custom > div {
      background: rgba(0, 0, 0, 0.45);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-radius: 20px;
      padding: 16px 14px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    .full-cover-glass .meta-box-custom {
      background: rgba(0, 0, 0, 0.5) !important;
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid rgba(255, 255, 255, 0.15) !important;
      color: #FFFFFF !important;
    }
    .full-cover-glass .meta-box-custom div {
      color: #FFFFFF !important;
    }
    .full-cover-glass .guest-name-custom,
    .full-cover-glass #cardQuoteText,
    .full-cover-glass #cardAdmitBearer,
    .full-cover-glass #cardFooterNote {
      color: #FFFFFF !important;
      text-shadow: 0 2px 6px rgba(0, 0, 0, 0.85);
    }
    .full-cover-shadow .guest-name-custom,
    .full-cover-shadow .event-title-custom,
    .full-cover-shadow #cardQuoteText,
    .full-cover-shadow #cardFooterNote {
      text-shadow: 0 2px 8px rgba(0,0,0,0.9), 0 0 20px rgba(0,0,0,0.8);
    }

    .card-cover-bottom-wrap {
      position: relative;
      width: 100%;
      overflow: hidden;
      margin-top: 12px;
      border-radius: 16px;
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.15);
    }
    .card-cover-bottom-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .artboard-half-split {
      display: flex !important;
      flex-direction: row !important;
      overflow: hidden;
    }
    .artboard-half-split.split-side-right {
      flex-direction: row-reverse !important;
    }
    .artboard-half-split.split-vertical {
      flex-direction: column !important;
    }
    .artboard-half-split.split-vertical.split-side-bottom {
      flex-direction: column-reverse !important;
    }

    .card-cover-custom-wrap {
      position: absolute;
      z-index: 10;
      user-select: none;
      pointer-events: none;
    }
    .custom-cover-frame {
      position: relative;
      width: 100%;
      height: 100%;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.28);
      border: 2px solid rgba(255, 255, 255, 0.4);
    }
    .card-cover-custom-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
  </style>
</head>
<body>

<div class="pass-container">
  <?php if (!$pass): ?>
    <div class="card" style="padding:48px 24px;text-align:center;">
      <div style="width:64px;height:64px;border-radius:50%;background:rgba(220,38,38,0.12);color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <?= svgIcon('alert', 32) ?>
      </div>
      <h2 style="font-weight:800;color:var(--text-primary);margin-bottom:6px;">Invalid Pass Token</h2>
      <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:20px;">
        The requested digital invitation ticket was not found in the registry.
      </p>
      <a href="index.php" class="btn btn-secondary">Return to Home</a>
    </div>
  <?php else: ?>
    <!-- Language Switcher & Portal Topbar -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding:0 4px;">
      <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;font-size:0.84rem;font-weight:700;color:var(--text-secondary);text-decoration:none;">
        <?= svgIcon('ticket', 15) ?> <span><?= APP_NAME ?></span>
      </a>
      <div style="display:inline-flex;background:#FFFFFF;border:1px solid rgba(0,0,0,0.1);border-radius:20px;padding:3px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <button type="button" id="langBtnEn" onclick="setLanguage('en')" style="border:none;border-radius:16px;padding:4px 12px;font-size:0.75rem;font-weight:700;cursor:pointer;background:#FF7A00;color:#FFFFFF;transition:all 0.2s ease;">
          English
        </button>
        <button type="button" id="langBtnSw" onclick="setLanguage('sw')" style="border:none;border-radius:16px;padding:4px 12px;font-size:0.75rem;font-weight:700;cursor:pointer;background:transparent;color:#4B5563;transition:all 0.2s ease;">
          Kiswahili
        </button>
      </div>
    </div>
    
    <!-- PASS CARD DISPLAY -->
    <?php if ($cardMode === 'canva' && !empty($canvaBgData)): ?>
      
      <!-- ========================================== -->
      <!-- VIEW 1: CLONED CANVA POSTER WITH DIRECT QR -->
      <!-- ========================================== -->
      <div id="targetPassCard" class="canva-pass-card">
        <!-- Cloned Canva Card Image -->
        <img src="<?= htmlspecialchars($canvaBgData) ?>" alt="<?= htmlspecialchars($pass['event_title']) ?>" class="canva-pass-img">

        <!-- Positioned QR Code Overlay Badge -->
        <div class="canva-qr-badge-content <?= htmlspecialchars($qrBadgeStyle) ?>" style="
          left: <?= $qrXPercent ?>%;
          top: <?= $qrYPercent ?>%;
          transform: translate(-50%, -50%) rotate(<?= $qrRotation ?>deg);
          padding: <?= $qrPadding ?>px;
          border-radius: <?= $qrRadius ?>px;
        ">
          <?php if ($showTier): ?>
            <div class="badge badge-primary" style="font-size:0.68rem;padding:2px 8px;margin-bottom:6px;background:<?= $accentColor ?>;color:#FFFFFF;border:none;">
              <?= htmlspecialchars($badgeText) ?>
            </div>
          <?php endif; ?>

          <?php if ($qrBgTransparent): ?>
            <div id="passQrWrapper" style="padding:0;background:transparent;box-shadow:none;line-height:0;">
              <div id="passQr"></div>
            </div>
          <?php else: ?>
            <div id="passQrWrapper" style="padding:6px;background:<?= htmlspecialchars($canvaQrLight) ?>;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.12);line-height:0;">
              <div id="passQr"></div>
            </div>
          <?php endif; ?>

          <?php 
            $isLightAttendee = ($badgeTextColor === '#FFFFFF' || strtolower($badgeTextColor) === '#ffffff');
            $textShadow = ($qrBgTransparent && $isLightAttendee) ? 'text-shadow:0 1px 4px rgba(0,0,0,0.85);' : '';
          ?>
          <?php if ($showAttendee): ?>
            <div class="badge-text-primary" style="font-weight:800;font-size:0.82rem;margin-top:6px;color:<?= htmlspecialchars($badgeTextColor) ?>;white-space:nowrap;<?= $textShadow ?>">
              <?= e($pass['guest_name']) ?>
            </div>
          <?php endif; ?>

          <?php if ($showRef): ?>
            <div class="badge-text-secondary" style="font-family:monospace;font-size:0.72rem;letter-spacing:0.8px;color:<?= htmlspecialchars($badgeTextColor) ?>;opacity:0.85;<?= $textShadow ?>">
              <?= e($pass['reference_code']) ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    <?php else: ?>

      <!-- ========================================== -->
      <!-- VIEW 2: BESPOKE STUDIO MULTI-ELEMENT CARD  -->
      <!-- ========================================== -->
      <?php
      $extraClasses = [];
      $isUnifiedPass = ($headerStyle === 'unified') && !( !empty($coverImgData) && ($coverLayout === 'full' || $coverLayout === 'top') );
      if ($isUnifiedPass) $extraClasses[] = 'artboard-unified';
      if ($borderFrame !== 'none') $extraClasses[] = 'frame-' . htmlspecialchars($borderFrame);
      if (!empty($coverImgData)) {
          if ($coverLayout === 'full') {
              $extraClasses[] = 'cover-layout-full card-cover-full-active';
              if ($coverTextContrast === 'glass') $extraClasses[] = 'full-cover-glass';
              elseif ($coverTextContrast === 'shadow') $extraClasses[] = 'full-cover-shadow';
          } elseif ($coverLayout === 'half') {
              $extraClasses[] = 'artboard-half-split';
              if ($orientation === 'portrait') {
                  if ($coverHalfSide === 'right') $extraClasses[] = 'split-side-right';
              } else {
                  $extraClasses[] = 'split-vertical';
                  if ($coverHalfSide === 'right' || $coverHalfSide === 'bottom') $extraClasses[] = 'split-side-bottom';
              }
          }
      }
      ?>
      <div id="targetPassCard" class="digital-pass-card-custom <?= implode(' ', $extraClasses) ?>" style="position:relative;<?= (!empty($coverImgData) && $coverLayout === 'full') ? 'background:transparent !important;' : ($isUnifiedPass ? 'background:' . htmlspecialchars($bodyBg) . ' !important;' : '') ?><?= !empty($borderColor) ? 'border-color:' . htmlspecialchars($borderColor) . ';' : '' ?>">
        <div class="texture-overlay <?= $texture !== 'none' ? 'texture-' . htmlspecialchars($texture) : '' ?>"></div>

        <?php if ($showArtwork): ?>
          <div id="passDecorativeArtwork" class="card-decorative-artwork" data-template-id="<?= htmlspecialchars($templateId) ?>" data-accent="<?= htmlspecialchars($accentColor) ?>" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:2;overflow:hidden;border-radius:inherit;opacity:<?= number_format($artworkOpacity / 100, 2) ?>;">
            <?= $decorativeArtwork ?>
          </div>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              var artEl = document.getElementById('passDecorativeArtwork');
              if (artEl && (!artEl.innerHTML || !artEl.innerHTML.trim()) && typeof generateTemplateArtworkSvg === 'function') {
                var tid = artEl.getAttribute('data-template-id');
                var acc = artEl.getAttribute('data-accent');
                if (tid) artEl.innerHTML = generateTemplateArtworkSvg(tid, acc);
              }
            });
          </script>
        <?php endif; ?>

        <?php if (!empty($coverImgData) && $coverLayout === 'full'): ?>
          <!-- Layout Option 1: Full Card Background Cover -->
          <div class="card-cover-full-wrap">
            <img src="<?= htmlspecialchars($coverImgData) ?>" alt="Cover" class="card-cover-full-img" style="object-fit:<?= htmlspecialchars($coverCropFit) ?>;object-position:<?= $coverPositionX ?>% <?= $coverPositionY ?>%;transform:scale(<?= $coverScale / 100 ?>);filter:brightness(<?= $coverBrightness ?>%) contrast(<?= $coverContrast ?>%);opacity:<?= $coverOpacity / 100 ?>;">
            <div class="card-cover-full-overlay" style="background:rgba(0,0,0,<?= number_format($coverOverlay / 100, 2) ?>);"></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($coverImgData) && $coverLayout === 'custom'): ?>
          <!-- Layout Option 5: Custom Position Floating Photo -->
          <div class="card-cover-custom-wrap" style="left:<?= $coverCustomX ?>%;top:<?= $coverCustomY ?>%;width:<?= $coverCustomW ?>px;height:<?= $coverCustomH ?>px;transform:translate(-50%,-50%) rotate(<?= $coverCustomRotation ?>deg);">
            <div class="custom-cover-frame">
              <img src="<?= htmlspecialchars($coverImgData) ?>" alt="Cover" class="card-cover-custom-img" style="object-fit:<?= htmlspecialchars($coverCropFit) ?>;filter:brightness(<?= $coverBrightness ?>%) contrast(<?= $coverContrast ?>%);opacity:<?= $coverOpacity / 100 ?>;">
              <div style="position:absolute;inset:0;background:rgba(0,0,0,<?= number_format($coverOverlay / 100, 2) ?>);"></div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Pass Header -->
        <div class="digital-pass-header-custom" style="position:relative;overflow:hidden;<?= ((!empty($coverImgData) && $coverLayout === 'full') || $isUnifiedPass) ? 'background:transparent !important;background-image:none !important;box-shadow:none !important;' : '' ?><?= (!empty($coverImgData) && ($coverLayout === 'top' || $coverLayout === 'half')) ? (($coverLayout === 'half' ? 'flex:0 0 ' . $coverHalfRatio . '%;' : '') . 'min-height:' . $coverHeight . 'px;') : '' ?>">
          <?php if (!empty($coverImgData) && ($coverLayout === 'top' || $coverLayout === 'half')): ?>
            <div style="position:absolute;inset:0;overflow:hidden;z-index:1;pointer-events:none;">
              <img src="<?= htmlspecialchars($coverImgData) ?>" alt="Cover" style="width:100%;height:100%;object-fit:<?= htmlspecialchars($coverCropFit) ?>;object-position:<?= $coverPositionX ?>% <?= $coverPositionY ?>%;transform:scale(<?= $coverScale / 100 ?>);transform-origin:<?= $coverPositionX ?>% <?= $coverPositionY ?>%;filter:brightness(<?= $coverBrightness ?>%) contrast(<?= $coverContrast ?>%);opacity:<?= $coverOpacity / 100 ?>;">
              <div style="position:absolute;inset:0;background:rgba(0,0,0,<?= number_format($coverOverlay / 100, 2) ?>);"></div>
            </div>
          <?php endif; ?>

          <div style="position:relative;z-index:4;">
            <?php if (!empty($logoData)): ?>
              <div style="margin-bottom:10px;">
                <img src="<?= htmlspecialchars($logoData) ?>" style="max-height:44px;max-width:150px;object-fit:contain;filter:drop-shadow(0 2px 6px rgba(0,0,0,0.25));" alt="Logo">
              </div>
            <?php endif; ?>

            <span class="badge-pill-custom" style="color:<?= htmlspecialchars($headingColor) ?>;border-color:<?= htmlspecialchars($headingColor) ?>55;">
              <?= htmlspecialchars($badgeText) ?>
            </span>

            <?php if (!empty($sampleCouple)): ?>
              <div class="card-couple-script" style="font-family:<?= htmlspecialchars($scriptFont) ?>;font-size:2.1rem;line-height:1.15;margin:4px 0 6px;color:<?= htmlspecialchars($headingColor) ?>;">
                <?= htmlspecialchars($sampleCouple) ?>
              </div>
            <?php endif; ?>

            <h1 class="event-title-custom" style="color:<?= htmlspecialchars($headingColor) ?>;">
              <?= e($pass['event_title']) ?>
            </h1>

            <div style="font-size:0.82rem;opacity:0.95;margin-top:4px;color:<?= htmlspecialchars($headingColor) ?>;">
              <?= date('l, M d, Y', strtotime($pass['start_date'])) ?> &bull; <?= e($pass['start_time']) ?>
            </div>
          </div>
        </div>

        <!-- Pass Body -->
        <div class="digital-pass-body-custom" style="position:relative;z-index:4;<?= ((!empty($coverImgData) && $coverLayout === 'full') || $isUnifiedPass) ? 'background:transparent !important;' : '' ?>">
          <?php if ($seal !== 'none'): ?>
            <div style="display:flex;justify-content:center;margin-bottom:4px;">
              <span class="seal-pill-custom">
                <?php if ($seal === 'crown'): ?>👑 ROYAL GUEST PASS
                <?php elseif ($seal === 'verified'): ?>🛡️ ACCREDITED ADMISSION
                <?php elseif ($seal === 'diamond'): ?>💎 DIAMOND ADMISSION
                <?php elseif ($seal === 'ticket'): ?>🎟️ OFFICIAL ADMISSION
                <?php else: ?>⭐ OFFICIAL VIP PASS<?php endif; ?>
              </span>
            </div>
          <?php endif; ?>

          <?php if (!empty($quoteText)): ?>
            <div id="cardQuoteText" style="font-size:0.76rem;font-style:italic;opacity:0.8;margin:4px 0 6px;">
              <?= htmlspecialchars($quoteText) ?>
            </div>
          <?php endif; ?>

          <div id="cardAdmitBearer" style="font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:1.2px;opacity:0.65;margin-bottom:2px;">
            Admit Attendee Bearer
          </div>

          <h2 class="guest-name-custom">
            <?= e($pass['guest_name']) ?>
          </h2>

          <div class="qr-frame-custom" style="<?= $qrBgTransparent ? 'background:transparent;box-shadow:none;border:none;padding:0;' : '' ?>">
            <div id="passQr"></div>
          </div>

          <div style="font-family:monospace;font-size:0.82rem;font-weight:700;letter-spacing:1px;margin-bottom:8px;opacity:0.85;">
            <?= e($pass['reference_code']) ?>
          </div>

          <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:14px;">
            <?php if ($pass['is_checked_in']): ?>
              <span id="cardCheckinBadge" class="badge badge-success"><?= svgIcon('check', 12) ?> CHECKED IN</span>
            <?php else: ?>
              <span id="cardActiveBadge" class="badge badge-primary" style="background:<?= $accentColor ?>18;color:<?= $accentColor ?>;border:1px solid <?= $accentColor ?>44;">
                ACTIVE PASS &bull; <?= $pass['permitted_attendees'] ?> PERSON(S)
              </span>
            <?php endif; ?>
          </div>

          <div class="meta-box-custom">
            <div style="font-weight:700;margin-bottom:2px;">
              <?= e($pass['venue_name']) ?>
            </div>
            <div style="font-size:0.78rem;opacity:0.8;line-height:1.4;">
              <?= e($pass['venue_address']) ?>
            </div>
          </div>

          <?php if (!empty($footerNote)): ?>
            <div id="cardFooterNote" style="font-size:0.7rem;opacity:0.65;line-height:1.4;border-top:1px dashed rgba(0,0,0,0.12);padding-top:10px;margin-top:14px;">
              <?= htmlspecialchars($footerNote) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($coverImgData) && $coverLayout === 'bottom'): ?>
            <!-- Layout Option 3: Bottom Cover Wrap -->
            <div class="card-cover-bottom-wrap" style="height:<?= $coverHeight ?>px;">
              <img src="<?= htmlspecialchars($coverImgData) ?>" alt="Cover" class="card-cover-bottom-img" style="object-fit:<?= htmlspecialchars($coverCropFit) ?>;object-position:<?= $coverPositionX ?>% <?= $coverPositionY ?>%;transform:scale(<?= $coverScale / 100 ?>);transform-origin:<?= $coverPositionX ?>% <?= $coverPositionY ?>%;filter:brightness(<?= $coverBrightness ?>%) contrast(<?= $coverContrast ?>%);opacity:<?= $coverOpacity / 100 ?>;">
              <div style="position:absolute;inset:0;background:rgba(0,0,0,<?= number_format($coverOverlay / 100, 2) ?>);"></div>
            </div>
          <?php endif; ?>
        </div>
      </div>

    <?php endif; ?>

    <!-- PASS ACTION CONTROLS & RSVP CARD -->
    <div class="card" style="padding:18px 20px;text-align:center;">
      <!-- Pass Status Pill -->
      <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:14px;">
        <?php if ($pass['is_checked_in']): ?>
          <span id="passStatusPill" class="badge badge-success" style="padding:6px 14px;font-size:0.82rem;">
            <?= svgIcon('check', 14) ?> ADMITTED &bull; CHECKED IN AT GATE
          </span>
        <?php else: ?>
          <span id="passStatusPill" class="badge badge-primary" style="padding:6px 14px;font-size:0.82rem;background:rgba(255,122,0,0.12);color:#FF7A00;border:1px solid rgba(255,122,0,0.25);">
            <?= svgIcon('ticket', 14) ?> VALID DIGITAL PASS &bull; <?= $pass['permitted_attendees'] ?> PERSON(S)
          </span>
        <?php endif; ?>
      </div>

      <!-- RSVP Section (Liquid Glass Reversible UI) -->
      <div id="rsvpSection" style="margin-bottom:16px;">
        <div id="rsvpCardContainer" style="background:var(--surface-3, #F7F7F8);border:1px solid var(--border, #E4E4E7);border-radius:14px;padding:14px 16px;transition:all 0.25s ease;">
          <div id="rsvpContent">
            <!-- Dynamic via renderRsvpUI -->
          </div>
        </div>
      </div>

      <!-- Action Buttons: Download Card PNG + Add to Calendar + Print -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <button type="button" class="btn btn-primary btn-sm" id="btnDownloadPng" onclick="downloadPassCard()" style="justify-content:center;font-size:0.8rem;">
          <?= svgIcon('download', 14) ?> <span>Download Card PNG</span>
        </button>
        <a href="pass.php?token=<?= urlencode($pass['token']) ?>&download=ics" class="btn btn-secondary btn-sm" id="btnAddCalendar" style="justify-content:center;font-size:0.8rem;">
          <?= svgIcon('calendar', 14) ?> <span>Add to Calendar (.ics)</span>
        </a>
      </div>

      <div style="margin-top:10px;">
        <button type="button" class="btn btn-secondary btn-sm" id="btnPrintCard" onclick="window.print()" style="width:100%;justify-content:center;font-size:0.78rem;">
          <?= svgIcon('eye', 13) ?> <span>Print Physical Card</span>
        </button>
      </div>
    </div>

  <?php endif; ?>
</div>

<script>
<?php if ($pass): ?>
let currentRsvpState = <?= json_encode($pass['rsvp_response'] ?: 'pending') ?>;
const passToken = <?= json_encode($pass['token']) ?>;
let currentLang = localStorage.getItem('leula_pass_lang') || 'en';

const I18N = {
  en: {
    cordially_invited: "<?= !empty($quoteText) ? addslashes(htmlspecialchars($quoteText)) : 'Honored to request the pleasure of your company' ?>",
    admit_bearer: "Admit Attendee Bearer",
    active_pass: "ACTIVE PASS &bull; <?= $pass['permitted_attendees'] ?> PERSON(S)",
    checked_in: "CHECKED IN",
    admitted_gate: "ADMITTED &bull; CHECKED IN AT GATE",
    valid_digital_pass: "VALID DIGITAL PASS &bull; <?= $pass['permitted_attendees'] ?> PERSON(S)",
    rsvp_accepted: "You have accepted this invitation.",
    rsvp_accepted_sub: "We look forward to welcoming you to the event.",
    rsvp_declined: "You have declined this invitation.",
    rsvp_declined_sub: "Plans changed? You can update your RSVP below.",
    rsvp_prompt: "Please confirm your attendance",
    rsvp_prompt_sub: "Let the event host know if you will be attending.",
    btn_confirm: "Confirm Attending",
    btn_decline: "Can't Make It",
    btn_change_accepted: "Change to Accepted",
    btn_change_declined: "Change to Declined",
    btn_download_png: "Download Card PNG",
    btn_calendar: "Add to Calendar (.ics)",
    btn_print: "Print Physical Card",
    footer_note: "<?= !empty($footerNote) ? addslashes(htmlspecialchars($footerNote)) : 'Black Tie Required • Non-Transferable Digital QR Ticket' ?>"
  },
  sw: {
    cordially_invited: "Umealikwa kwa heshima kubwa kuhudhuria hafla hii",
    admit_bearer: "Mruhusu Mhudhuriaji Rasmi",
    active_pass: "PASI HALISI &bull; WATU <?= $pass['permitted_attendees'] ?>",
    checked_in: "UMESHAINGIA UKUMBINI",
    admitted_gate: "UMERUHUSIWA &bull; UMEHAKIKIWA GETINI",
    valid_digital_pass: "PASI HALISI YA KIDIJI TALI &bull; WATU <?= $pass['permitted_attendees'] ?>",
    rsvp_accepted: "Umekubali mwaliko huu.",
    rsvp_accepted_sub: "Tunatazamia kwa furaha kukukaribisha kwenye hafla hii.",
    rsvp_declined: "Umekataa mwaliko huu.",
    rsvp_declined_sub: "Mipango imebadilika? Unaweza kubadilisha majibu yako hapa chini.",
    rsvp_prompt: "Tafadhali thibitisha mahudhurio yako",
    rsvp_prompt_sub: "Mfahamishe mratibu wa hafla iwapo utaweza kuhudhuria.",
    btn_confirm: "Thibitisha Kuhudhuria",
    btn_decline: "Sitahudhuria",
    btn_change_accepted: "Badilisha kuwa Umekubali",
    btn_change_declined: "Badilisha kuwa Umekataa",
    btn_download_png: "Pakua Kadi (PNG)",
    btn_calendar: "Weka kwenye Kalenda (.ics)",
    btn_print: "Chapa Kadi ya Mwaliko",
    footer_note: "Mavazi Rasmi &bull; Kadi Halisi ya Kidijitali Isiyohamishika"
  }
};

function setLanguage(lang) {
  currentLang = lang;
  localStorage.setItem('leula_pass_lang', lang);

  const btnEn = document.getElementById('langBtnEn');
  const btnSw = document.getElementById('langBtnSw');
  if (btnEn && btnSw) {
    if (lang === 'sw') {
      btnSw.style.background = '#FF7A00';
      btnSw.style.color = '#FFFFFF';
      btnEn.style.background = 'transparent';
      btnEn.style.color = '#4B5563';
    } else {
      btnEn.style.background = '#FF7A00';
      btnEn.style.color = '#FFFFFF';
      btnSw.style.background = 'transparent';
      btnSw.style.color = '#4B5563';
    }
  }

  const t = I18N[lang] || I18N.en;

  // Update Card labels
  const quoteElem = document.getElementById('cardQuoteText');
  if (quoteElem) quoteElem.textContent = t.cordially_invited;

  const admitElem = document.getElementById('cardAdmitBearer');
  if (admitElem) admitElem.textContent = t.admit_bearer;

  const activeBadge = document.getElementById('cardActiveBadge');
  if (activeBadge) activeBadge.innerHTML = t.active_pass;

  const footerElem = document.getElementById('cardFooterNote');
  if (footerElem) footerElem.innerHTML = t.footer_note;

  // Update Status Pill
  const statusPill = document.getElementById('passStatusPill');
  if (statusPill) {
    <?php if ($pass['is_checked_in']): ?>
      statusPill.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> ${t.admitted_gate}`;
    <?php else: ?>
      statusPill.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><line x1="13" y1="5" x2="13" y2="19" stroke-dasharray="2"/></svg> ${t.valid_digital_pass}`;
    <?php endif; ?>
  }

  // Update Action Buttons
  const btnPng = document.getElementById('btnDownloadPng');
  if (btnPng) btnPng.querySelector('span').textContent = t.btn_download_png;

  const btnCal = document.getElementById('btnAddCalendar');
  if (btnCal) btnCal.querySelector('span').textContent = t.btn_calendar;

  const btnPrint = document.getElementById('btnPrintCard');
  if (btnPrint) btnPrint.querySelector('span').textContent = t.btn_print;

  // Re-render RSVP UI with new language
  renderRsvpUI(currentRsvpState);
}

document.addEventListener('DOMContentLoaded', () => {
  const targetCard = document.getElementById('targetPassCard');
  const cardWidth = targetCard ? targetCard.offsetWidth : 420;
  const qrPercent = <?= $cardMode === 'canva' ? $qrSizePercent : 28.0 ?>;
  const qrPadding = <?= $cardMode === 'canva' ? $qrPadding : 10 ?>;
  const isTransparent = <?= $qrBgTransparent ? 'true' : 'false' ?>;
  const innerPad = isTransparent ? 0 : 12;
  const totalBadgePx = (qrPercent / 100) * cardWidth;
  const qrPixelDimension = Math.max(70, Math.round(totalBadgePx - (qrPadding * 2) - innerPad));
  const qrColor = "<?= $cardMode === 'canva' ? $canvaQrDark : $qrDark ?>";
  const lightColor = isTransparent ? "rgba(0,0,0,0)" : "<?= $canvaQrLight ?? '#FFFFFF' ?>";
  
  new QRCode(document.getElementById("passQr"), {
    text: "<?= APP_URL ?>/pass.php?token=<?= urlencode($pass['token']) ?>",
    width: qrPixelDimension,
    height: qrPixelDimension,
    colorDark: qrColor,
    colorLight: lightColor,
    correctLevel: QRCode.CorrectLevel.H
  });

  // Initialize selected language
  setLanguage(currentLang);
});

function renderRsvpUI(state) {
  currentRsvpState = state;
  const content = document.getElementById('rsvpContent');
  if (!content) return;
  const t = I18N[currentLang] || I18N.en;

  if (state === 'accepted') {
    content.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:center;gap:6px;color:#059669;font-weight:700;font-size:0.88rem;margin-bottom:6px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span>${t.rsvp_accepted}</span>
      </div>
      <p style="font-size:0.75rem;color:var(--text-muted, #71717A);margin:0 0 10px;">${t.rsvp_accepted_sub}</p>
      <button type="button" class="btn btn-secondary btn-sm" onclick="handleRsvpChange('declined')" style="margin:0 auto;font-size:0.8rem;padding:6px 14px;border-color:rgba(220,38,38,0.25);color:var(--text-secondary, #52525B);">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        ${t.btn_change_declined}
      </button>
    `;
  } else if (state === 'declined') {
    content.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:center;gap:6px;color:#DC2626;font-weight:700;font-size:0.88rem;margin-bottom:6px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        <span>${t.rsvp_declined}</span>
      </div>
      <p style="font-size:0.75rem;color:var(--text-muted, #71717A);margin:0 0 10px;">${t.rsvp_declined_sub}</p>
      <button type="button" class="btn btn-primary btn-sm" onclick="handleRsvpChange('accepted')" style="margin:0 auto;font-size:0.8rem;padding:6px 14px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        ${t.btn_change_accepted}
      </button>
    `;
  } else {
    content.innerHTML = `
      <div style="font-size:0.84rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
        ${t.rsvp_prompt}
      </div>
      <p style="font-size:0.75rem;color:var(--text-muted, #71717A);margin:0 0 10px;">${t.rsvp_prompt_sub}</p>
      <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
        <button type="button" class="btn btn-success btn-sm" onclick="handleRsvpChange('accepted')" style="padding:7px 16px;font-size:0.82rem;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> ${t.btn_confirm}
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="handleRsvpChange('declined')" style="padding:7px 16px;font-size:0.82rem;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> ${t.btn_decline}
        </button>
      </div>
    `;
  }
}

async function handleRsvpChange(newStatus) {
  if (newStatus === currentRsvpState) return;

  // If changing from an existing response, present modern confirmation dialog
  if (currentRsvpState && currentRsvpState !== 'pending') {
    const fromLabel = currentRsvpState.toUpperCase();
    const toLabel = newStatus.toUpperCase();

    const confirmed = await window.showConfirmDialog({
      title: 'CHANGE RSVP?',
      subtitle: 'Update your event attendance response',
      message: `Your current response is <strong>${fromLabel}</strong>.<br><br>Are you sure you want to change it to <strong>${toLabel}</strong>?`,
      confirmText: 'Confirm Change',
      cancelText: 'Cancel',
      type: newStatus === 'declined' ? 'warning' : 'primary'
    });

    if (!confirmed) return;
  }

  const container = document.getElementById('rsvpCardContainer');
  if (container) {
    container.style.opacity = '0.6';
    container.style.pointerEvents = 'none';
  }

  try {
    const res = await fetch('api/rsvp.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `token=${encodeURIComponent(passToken)}&response=${encodeURIComponent(newStatus)}`
    });
    const data = await res.json();

    if (data.success) {
      // 1. Update the DOM immediately using verified server response (fixes sync discrepancy)
      renderRsvpUI(data.response);
      // 2. Display elegant Liquid Glass success notification
      window.Toast.success(data.message || 'Your RSVP response has been saved successfully.');
    } else {
      window.Toast.error(data.message || 'Unable to update your RSVP. Please try again.');
    }
  } catch (err) {
    window.Toast.error('Network communication error. Please try again.');
  } finally {
    if (container) {
      container.style.opacity = '1';
      container.style.pointerEvents = 'auto';
    }
  }
}

function downloadPassCard() {
  const target = document.getElementById('targetPassCard');
  if (!target) return;

  html2canvas(target, {
    scale: 3, // Ultra-sharp 3x HD
    useCORS: true,
    backgroundColor: null
  }).then(canvas => {
    const link = document.createElement('a');
    link.download = `invitation_pass_<?= preg_replace('/[^A-Za-z0-9]/', '_', $pass['guest_name']) ?>.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
    window.Toast.success('Card PNG downloaded successfully!');
  }).catch(err => {
    window.Toast.error('Download failed: ' + err);
  });
}
<?php endif; ?>
</script>

</body>
</html>
