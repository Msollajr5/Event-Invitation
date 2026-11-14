<?php
$pageTitle = 'Canva-Style Invitation Designer Studio';
$currentPage = 'designer';
require_once __DIR__ . '/config/db.php';
require_role(2);

$userEvents = get_user_events();
$selectedEventId = (int)($_GET['event_id'] ?? get_active_event_id() ?? 1);
require_event_access($selectedEventId);
$event = db_fetch_one("SELECT * FROM events WHERE id = :id LIMIT 1", ['id' => $selectedEventId]);
if (!$event) {
    $event = !empty($userEvents) ? $userEvents[0] : null;
    $selectedEventId = $event['id'] ?? 1;
}
$allEvents = $userEvents;

// Handle Design Save (Persists to MySQL invitation_designs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_design') {
    $designName = trim($_POST['design_name'] ?? 'Bespoke Pass Design');
    $orientation = trim($_POST['orientation'] ?? 'portrait');
    $canvasData = $_POST['canvas_data'] ?? '{}';
    $bgColor = trim($_POST['background_color'] ?? '#FFFFFF');

    // ---------------------------------------------------------------
    // LARGE-PAYLOAD FIX: Extract canvaBgData (base64 poster image)
    // from the JSON and store it in a dedicated LONGBLOB column so
    // the canvas_data JSON stays small and never hits max_allowed_packet
    // ---------------------------------------------------------------
    $canvasBgData   = null;  // raw binary
    $canvasBgMime   = null;
    $canvasBgSentAs = null;  // the original data-URL, restored on load

    $cfg = json_decode($canvasData, true);
    if ($cfg && !empty($cfg['canvaBgData']) && str_starts_with($cfg['canvaBgData'], 'data:image/')) {
        $dataUrl = $cfg['canvaBgData'];
        if (preg_match('/^data:(image\/[a-z+]+);base64,(.+)$/s', $dataUrl, $bm)) {
            $canvasBgMime = $bm[1];
            $canvasBgData = base64_decode($bm[2]);
        }
        // Replace in JSON with a placeholder; JS will re-fetch from the BG endpoint
        $cfg['canvaBgData'] = '__STORED_IN_DB__';
        $canvasData = json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $pdo = get_db();
    // Raise packet limit for this session before any large write
    try { $pdo->exec("SET SESSION max_allowed_packet = 67108864;"); } catch (Throwable $t) {}

    $existing = db_fetch_one("SELECT id FROM invitation_designs WHERE event_id = :eid LIMIT 1", ['eid' => $selectedEventId]);

    if ($existing) {
        $designId = $existing['id'];
        // Update text fields first (small)
        db_update('invitation_designs', [
            'name'             => $designName,
            'orientation'      => in_array($orientation, ['portrait', 'landscape']) ? $orientation : 'portrait',
            'canvas_data'      => $canvasData,
            'background_color' => $bgColor,
            'updated_at'       => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $designId]);

        // Update poster blob separately if provided
        if ($canvasBgData !== null) {
            $stmt = $pdo->prepare("UPDATE invitation_designs SET canva_bg_data = :bg, canva_bg_mime = :mime WHERE id = :id");
            $stmt->bindParam(':bg',   $canvasBgData, PDO::PARAM_LOB);
            $stmt->bindParam(':mime', $canvasBgMime, PDO::PARAM_STR);
            $stmt->bindParam(':id',   $designId,     PDO::PARAM_INT);
            $stmt->execute();
        }
    } else {
        // Insert text row first
        db_insert('invitation_designs', [
            'event_id'         => $selectedEventId,
            'name'             => $designName,
            'orientation'      => in_array($orientation, ['portrait', 'landscape']) ? $orientation : 'portrait',
            'canvas_data'      => $canvasData,
            'background_color' => $bgColor,
            'width'            => ($orientation === 'landscape' ? 640 : 420),
            'height'           => ($orientation === 'landscape' ? 400 : 640),
        ]);
        $designId = $pdo->lastInsertId();

        // Write blob separately
        if ($canvasBgData !== null && $designId) {
            $stmt = $pdo->prepare("UPDATE invitation_designs SET canva_bg_data = :bg, canva_bg_mime = :mime WHERE id = :id");
            $stmt->bindParam(':bg',   $canvasBgData, PDO::PARAM_LOB);
            $stmt->bindParam(':mime', $canvasBgMime, PDO::PARAM_STR);
            $stmt->bindParam(':id',   $designId,     PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    flash_set('success', 'Invitation card design & QR placement saved and cloned to all event passes successfully!');
    header('Location: designer.php?event_id=' . $selectedEventId);
    exit;
}

// Fetch Existing Saved Design if any
$savedDesign = db_fetch_one("SELECT id, name, orientation, canvas_data, background_color, canva_bg_mime FROM invitation_designs WHERE event_id = :eid ORDER BY id DESC LIMIT 1", ['eid' => $selectedEventId]);
$savedConfigJson = $savedDesign['canvas_data'] ?? '{}';

// If poster image is stored in the BLOB column, inject a URL the JS can fetch
// so we never embed large base64 into the HTML page itself
if ($savedDesign && !empty($savedDesign['id'])) {
    $savedCfgArr = json_decode($savedConfigJson, true);
    if ($savedCfgArr && isset($savedCfgArr['canvaBgData']) && $savedCfgArr['canvaBgData'] === '__STORED_IN_DB__') {
        // Replace placeholder with a URL to the poster-serving endpoint
        $savedCfgArr['canvaBgData'] = '__FETCH:' . APP_URL . '/api/poster.php?design_id=' . $savedDesign['id'] . '__';
        $savedConfigJson = json_encode($savedCfgArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Luxury Typography Fonts (Canva / Editorial Collection) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Bebas+Neue&family=Cinzel:wght@500;600;700;800;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Serif+Display:ital@0;1&family=Great+Vibes&family=Inter:wght@400;500;600;700;800&family=Lato:ital,wght@0,400;0,700;1,400&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Marcellus&family=Montserrat:wght@400;500;600;700;800;900&family=Outfit:wght@400;600;700;800&family=Parisienne&family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,900;1,400;1,600&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Poppins:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@500;700&family=Tangerine:wght@700&display=swap" rel="stylesheet">

<!-- High-Resolution Export Engine, Vector QR, Fabric.js, LuxeArt SVG Engine & Templates Library -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="assets/js/vendor/fabric.min.js"></script>
<script src="assets/js/qrcode.min.js"></script>
<script src="assets/js/luxe_art.js?v=<?= time() ?>"></script>
<script src="assets/js/templates_library.js?v=<?= time() ?>"></script>

<!-- Canva Studio Topbar -->
<!-- Canva Studio Responsive Topbar -->
<div class="card designer-topbar-card">
  <div class="designer-topbar-main">
    <div class="designer-branding">
      <div class="designer-brand-icon">
        <?= svgIcon('palette', 22) ?>
      </div>
      <div>
        <h1 class="page-title" style="font-size:1.3rem;margin:0;">Canva Interactive Card Studio</h1>
        <p class="page-subtitle" style="margin:2px 0 0;font-size:0.8rem;">Upload invitation poster, freely drag &amp; drop, resize, rotate, and customize QR codes</p>
      </div>
    </div>

    <!-- Mobile View Mode Switcher: Controls vs Live Canvas -->
    <div class="designer-view-switcher">
      <button type="button" id="viewBtnControls" class="view-switch-btn active" onclick="switchWorkView('controls')">
        <?= svgIcon('sliders', 14) ?> Controls
      </button>
      <button type="button" id="viewBtnPreview" class="view-switch-btn" onclick="switchWorkView('preview')">
        <?= svgIcon('eye', 14) ?> Live Canvas
      </button>
    </div>
  </div>

  <div class="designer-topbar-controls">
    <!-- Event Selector -->
    <div class="designer-control-item event-select-wrap">
      <select id="eventSelector" class="form-control" onchange="location.href='designer.php?event_id='+this.value">
        <?php foreach ($allEvents as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $selectedEventId ? 'selected' : '' ?>>
            <?= e($ev['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Segment 1: Card Mode Toggle (Canva vs Studio) -->
    <div class="canva-segmented-control mode-segment">
      <button type="button" id="btnModeCanva" class="segment-btn active" onclick="setCardMode('canva')">
        ✨ Canva Cloner
      </button>
      <button type="button" id="btnModeStudio" class="segment-btn" onclick="setCardMode('studio')">
        🎨 50 Templates
      </button>
    </div>

    <!-- Segment 2: Orientation Switcher -->
    <div class="canva-segmented-control orientation-segment">
      <button type="button" id="btnPortrait" class="segment-btn active" onclick="setOrientation('portrait')">
        <?= svgIcon('ticket', 13) ?> Portrait
      </button>
      <button type="button" id="btnLandscape" class="segment-btn" onclick="setOrientation('landscape')">
        <?= svgIcon('calendar', 13) ?> Landscape
      </button>
    </div>

    <!-- Action Buttons Group -->
    <div class="designer-action-buttons">
      <!-- Undo / Redo History Controls -->
      <div style="display:inline-flex;gap:4px;margin-right:2px;">
        <button type="button" id="btnUndoAction" class="btn btn-secondary btn-sm" onclick="undoState()" title="Undo change (Ctrl+Z)" disabled style="padding:6px 9px;font-size:0.75rem;">
          <?= svgIcon('undo', 13) ?> <span>Undo</span>
        </button>
        <button type="button" id="btnRedoAction" class="btn btn-secondary btn-sm" onclick="redoState()" title="Redo change (Ctrl+Y)" disabled style="padding:6px 9px;font-size:0.75rem;">
          <?= svgIcon('redo', 13) ?> <span>Redo</span>
        </button>
      </div>

      <div class="dropdown" style="position:relative;display:inline-block;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleExportMenu(event)" title="Export High-Res Invitation">
          <?= svgIcon('download', 14) ?> <span>Export Pass ▾</span>
        </button>
        <div id="exportDropdownMenu" class="dropdown-menu" style="display:none;position:absolute;right:0;top:110%;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm);box-shadow:0 10px 25px rgba(0,0,0,0.22);z-index:999;min-width:175px;padding:6px 0;">
          <a href="javascript:void(0)" class="dropdown-item" onclick="downloadCardImage('png'); toggleExportMenu();" style="display:flex;align-items:center;gap:8px;padding:8px 14px;color:var(--text-primary);text-decoration:none;font-size:0.8rem;font-weight:600;">
            <span>🖼️</span> Export High-Res PNG
          </a>
          <a href="javascript:void(0)" class="dropdown-item" onclick="downloadCardImage('jpg'); toggleExportMenu();" style="display:flex;align-items:center;gap:8px;padding:8px 14px;color:var(--text-primary);text-decoration:none;font-size:0.8rem;font-weight:600;">
            <span>📸</span> Export Standard JPG
          </a>
          <a href="javascript:void(0)" class="dropdown-item" onclick="downloadCardPdf(); toggleExportMenu();" style="display:flex;align-items:center;gap:8px;padding:8px 14px;color:var(--text-primary);text-decoration:none;font-size:0.8rem;font-weight:600;">
            <span>📄</span> Export Vector PDF
          </a>
        </div>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="printCard()" title="Print Invitation">
        <?= svgIcon('eye', 14) ?> <span>Print</span>
      </button>
      <button type="button" class="btn btn-primary btn-sm save-card-btn" onclick="submitSaveDesign()">
        <?= svgIcon('check', 14) ?> <span>Save &amp; Clone Card</span>
      </button>
    </div>
  </div>
</div>

<!-- Studio Workspace: Toolset (Left) + Live Canvas (Center/Right) -->
<div id="designerWorkspace" class="designer-workspace show-controls">

  <!-- Left Column: Canva-Style Toolset Navigation -->
  <div id="designerSidebar" class="card designer-toolset-card">

    <!-- Active Template Indicator & Back to Gallery Banner -->
    <div id="activeTemplateBanner" class="active-template-bar" style="margin:12px 14px 4px;display:none;">
      <div style="display:flex;align-items:center;gap:8px;min-width:0;">
        <span style="font-size:1.1rem;flex-shrink:0;">🎨</span>
        <div style="min-width:0;">
          <div style="font-size:0.64rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;letter-spacing:0.5px;">Active Design</div>
          <div id="activeTemplateName" style="font-size:0.82rem;font-weight:800;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Royal Gold Wedding</div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="switchStudioTab('tabTemplates')" style="font-size:0.72rem;padding:4px 9px;font-weight:700;flex-shrink:0;">
        ← Back to Templates
      </button>
    </div>

    <!-- Toolset Tabs Bar -->
    <div class="tabs designer-tabs-bar">
      <button type="button" class="tab-btn" data-tab="tabTemplates" data-tab-group="studio" style="padding:8px 7px;font-size:0.74rem;">
        🎨 50 Templates
      </button>
      <button type="button" class="tab-btn active" data-tab="tabCoverLayout" data-tab-group="studio" style="padding:8px 7px;font-size:0.74rem;">
        🖼️ Cover Layout
      </button>
      <button type="button" class="tab-btn" data-tab="tabContent" data-tab-group="studio" style="padding:8px 7px;font-size:0.74rem;">
        ✍️ Content
      </button>
      <button type="button" class="tab-btn" data-tab="tabStyling" data-tab-group="studio" style="padding:8px 7px;font-size:0.74rem;">
        🔤 Style
      </button>
      <button type="button" class="tab-btn" data-tab="tabElements" data-tab-group="studio" style="padding:8px 7px;font-size:0.74rem;">
        💎 Badges
      </button>
      <button type="button" class="tab-btn" data-tab="tabCanva" data-tab-group="studio" style="padding:8px 7px;font-size:0.74rem;">
        📱 QR Pass
      </button>
    </div>

    <!-- Scrollable Toolset Body -->
    <div style="padding:18px 20px;overflow-y:auto;flex:1;">
      
      <!-- TAB 1: CANVA CARD CLONER & DIRECT QR CONTROLS -->
      <div class="tab-panel" data-tab-panel="tabCanva" data-tab-group="studio">
        
        <!-- Minimalist Canva Banner -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#FF7A00,#E96800);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.95rem;box-shadow:0 2px 8px rgba(255,122,0,0.25);">
              <?= svgIcon('palette', 18) ?>
            </div>
            <div>
              <div style="font-weight:700;color:var(--text-primary);font-size:0.88rem;">Canva Card Cloner</div>
              <div style="font-size:0.72rem;color:var(--text-muted);">Upload invitation poster &amp; freely position QR pass</div>
            </div>
          </div>
          <p style="font-size:0.78rem;color:var(--text-secondary);margin:0 0 10px;line-height:1.4;">
            Upload your invitation poster. Click and drag the QR code directly on the canvas, use corner handles to resize, or use precision alignment tools.
          </p>
          <div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('canvaBgInput').click()" style="width:100%;justify-content:center;font-size:0.8rem;padding:9px 12px;font-weight:700;box-shadow:0 3px 10px rgba(255,122,0,0.25);">
              <?= svgIcon('upload', 14) ?> Upload Card Poster
            </button>
          </div>
          <input type="file" id="canvaBgInput" accept="image/*" style="display:none;" onchange="handleCanvaBgUpload(this)">
        </div>

        <div id="canvaBgPreviewWrapper" style="display:none;margin-top:8px;background:var(--surface-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px;align-items:center;justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:10px;">
            <img id="canvaBgThumb" src="" style="height:36px;width:36px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
            <div>
              <div style="font-size:0.78rem;font-weight:700;color:var(--text-primary);">Poster Active</div>
              <div style="font-size:0.7rem;color:#059669;font-weight:600;">✓ Ready to position &amp; clone</div>
            </div>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="removeCanvaBg()" style="color:var(--danger);font-size:0.72rem;padding:3px 8px;">Remove</button>
        </div>

        <!-- ============================================================
             DEDICATED QR CODE POSITION, SIZE, ROTATION & STYLING PANEL
             ============================================================ -->
        <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:14px;">
          
          <!-- Position Section -->
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <div style="font-size:0.8rem;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
              <?= svgIcon('qr', 14) ?> 1. Position (X &amp; Y)
            </div>
            <div style="display:flex;gap:4px;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="centerQrX()" style="padding:2px 6px;font-size:0.72rem;" title="Center Horizontally">
                Center X
              </button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="centerQrY()" style="padding:2px 6px;font-size:0.72rem;" title="Center Vertically">
                Center Y
              </button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="resetQrPosition()" style="padding:2px 6px;font-size:0.72rem;" title="Reset to Bottom-Center">
                Reset
              </button>
            </div>
          </div>

          <div class="form-grid-2" style="margin-bottom:8px;">
            <div class="form-group" style="margin-bottom:6px;">
              <label class="form-label" style="font-size:0.75rem;">X Coordinate (%): <span id="valQrX" style="font-weight:700;color:#FF7A00;">50%</span></label>
              <input type="range" id="cfgQrX" min="5" max="95" step="0.5" value="50" class="form-control" style="padding:0;" oninput="onPositionSliderChange()">
            </div>
            <div class="form-group" style="margin-bottom:6px;">
              <label class="form-label" style="font-size:0.75rem;">Y Coordinate (%): <span id="valQrY" style="font-weight:700;color:#FF7A00;">78%</span></label>
              <input type="range" id="cfgQrY" min="5" max="95" step="0.5" value="78" class="form-control" style="padding:0;" oninput="onPositionSliderChange()">
            </div>
          </div>

          <!-- Nudge Directional Pad -->
          <div class="nudge-pad-wrapper">
            <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);margin-right:4px;">Nudge:</span>
            <button type="button" class="btn btn-secondary btn-sm nudge-btn" onclick="nudgeQr(0, -1)" title="Move Up">▲</button>
            <button type="button" class="btn btn-secondary btn-sm nudge-btn" onclick="nudgeQr(0, 1)" title="Move Down">▼</button>
            <button type="button" class="btn btn-secondary btn-sm nudge-btn" onclick="nudgeQr(-1, 0)" title="Move Left">◀</button>
            <button type="button" class="btn btn-secondary btn-sm nudge-btn" onclick="nudgeQr(1, 0)" title="Move Right">▶</button>
          </div>

          <!-- Size Section -->
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <div style="font-size:0.8rem;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-primary);">
              2. Dimensions &amp; Scale
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="resetQrSize()" style="padding:4px 10px;font-size:0.74rem;font-weight:600;">
              Reset Size
            </button>
          </div>

          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:0.75rem;">Scale: <span id="valQrSize" style="font-weight:700;color:#FF7A00;">28% (~140px)</span></label>
            <input type="range" id="cfgQrSizeRange" min="12" max="65" step="0.5" value="28" class="form-control" style="padding:0;" oninput="onSizeSliderChange()">
          </div>

          <!-- Rotation Section -->
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;margin-top:12px;">
            <div style="font-size:0.8rem;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-primary);">
              3. Rotation Angle
            </div>
            <div style="display:flex;gap:4px;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="rotateQrStep(90)" style="padding:2px 6px;font-size:0.72rem;">
                +90°
              </button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="resetQrRotation()" style="padding:2px 6px;font-size:0.72rem;">
                0° Reset
              </button>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <input type="range" id="cfgQrRotation" min="0" max="360" step="1" value="0" class="form-control" style="padding:0;flex:1;" oninput="onRotationSliderChange()">
              <div style="min-width:44px;font-size:0.8rem;font-weight:700;color:#FF7A00;text-align:right;">
                <span id="valQrRotation">0</span>°
              </div>
            </div>
          </div>

          <!-- Appearance & Badge Styling -->
          <div style="font-size:0.8rem;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-primary);margin-bottom:8px;margin-top:14px;">
            4. Badge Style &amp; Colors
          </div>

          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:0.75rem;">Badge Frame Aesthetic</label>
            <select id="cfgQrBadgeStyle" class="form-control" style="font-size:0.82rem;padding:6px 10px;" onchange="renderCanvas()">
              <option value="badge-glass" selected>🔮 Liquid Glass (Translucent Frosted Glass)</option>
              <option value="badge-white">⚪ Pure Crisp White Badge</option>
              <option value="badge-dark">⚫ Dark Onyx Glass (For Dark Canva Cards)</option>
              <option value="badge-transparent">✨ Minimal QR Only (Borderless)</option>
            </select>
          </div>

          <!-- QR Background Fill & Transparency Controls -->
          <div class="form-group" style="margin-bottom:10px;background:var(--surface-3);padding:10px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
              <span style="font-size:0.75rem;font-weight:700;color:var(--text-primary);">QR Background Fill</span>
              <label style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;font-weight:700;color:#FF7A00;font-size:0.75rem;">
                <input type="checkbox" id="cfgQrBgTransparent" onchange="toggleQrTransparentBg(this.checked)">
                <span>🚫 Remove Background</span>
              </label>
            </div>
            
            <div style="display:flex;gap:6px;align-items:center;">
              <input type="color" id="cfgCanvaQrLight" value="#FFFFFF" class="form-control" style="height:32px;padding:2px;cursor:pointer;flex:1;" onchange="renderCanvas()">
              <button type="button" class="btn btn-secondary btn-sm" onclick="setQrBgPreset(true)" style="padding:4px 8px;font-size:0.72rem;" title="Make QR Background 100% Transparent">
                🚫 Transparent
              </button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="setQrBgPreset(false, '#FFFFFF')" style="padding:4px 8px;font-size:0.72rem;" title="Solid White Background">
                ⚪ White
              </button>
            </div>
            <div id="qrBgTransparentHint" style="display:none;font-size:0.71rem;color:#FF7A00;margin-top:6px;font-weight:600;line-height:1.3;">
              ✨ QR background removed! The uploaded Canva poster shows directly through the QR code cutouts.
            </div>
          </div>

          <!-- QR Barcode Color & Text Color -->
          <div class="form-grid-2" style="margin-bottom:8px;">
            <div class="form-group" style="margin-bottom:6px;">
              <label class="form-label" style="font-size:0.75rem;">QR Barcode Color</label>
              <div style="display:flex;gap:4px;align-items:center;">
                <input type="color" id="cfgCanvaQrDark" value="#09090B" class="form-control" style="height:34px;padding:2px;cursor:pointer;flex:1;" onchange="renderCanvas()">
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQrDarkPreset('#09090B')" style="padding:3px 6px;font-size:0.7rem;" title="Dark Onyx">⚫</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQrDarkPreset('#FFFFFF')" style="padding:3px 6px;font-size:0.7rem;" title="Crisp White">⚪</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQrDarkPreset('#FF7A00')" style="padding:3px 6px;font-size:0.7rem;" title="Leula Orange">🟠</button>
              </div>
            </div>
            <div class="form-group" style="margin-bottom:6px;">
              <label class="form-label" style="font-size:0.75rem;">Badge Text Color</label>
              <input type="color" id="cfgBadgeTextColor" value="#09090B" class="form-control" style="height:34px;padding:2px;cursor:pointer;" onchange="renderCanvas()">
            </div>
          </div>

          <div class="form-grid-2" style="margin-bottom:8px;">
            <div class="form-group" style="margin-bottom:6px;">
              <label class="form-label" style="font-size:0.75rem;">Padding (px): <span id="valQrPadding">10px</span></label>
              <input type="range" id="cfgQrPadding" min="0" max="24" step="1" value="10" class="form-control" style="padding:0;" oninput="renderCanvas()">
            </div>
            <div class="form-group" style="margin-bottom:6px;">
              <label class="form-label" style="font-size:0.75rem;">Border Radius: <span id="valQrRadius">18px</span></label>
              <input type="range" id="cfgQrRadius" min="0" max="32" step="1" value="18" class="form-control" style="padding:0;" oninput="renderCanvas()">
            </div>
          </div>

          <!-- Layer, Lock & Elements Toggles -->
          <div style="background:var(--surface-3);padding:10px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <span style="font-size:0.78rem;font-weight:700;color:var(--text-primary);">Lock QR Position</span>
              <label style="display:inline-flex;align-items:center;cursor:pointer;">
                <input type="checkbox" id="cfgQrLocked" onchange="toggleQrLock(this.checked)">
                <span style="font-size:0.75rem;margin-left:6px;font-weight:600;" id="valQrLockedText">Unlocked</span>
              </label>
            </div>
            
            <div style="border-top:1px dashed var(--border);padding-top:6px;display:flex;flex-direction:column;gap:6px;">
              <label style="display:flex;align-items:center;gap:8px;font-size:0.78rem;cursor:pointer;">
                <input type="checkbox" id="cfgShowAttendee" checked onchange="renderCanvas()">
                <span>Show Attendee Name</span>
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-size:0.78rem;cursor:pointer;">
                <input type="checkbox" id="cfgShowRef" checked onchange="renderCanvas()">
                <span>Show Reference Code</span>
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-size:0.78rem;cursor:pointer;">
                <input type="checkbox" id="cfgShowTier" checked onchange="renderCanvas()">
                <span>Show VIP Tier Badge</span>
              </label>
            </div>
          </div>

          <!-- STUDIO TEMPLATE QR PLACEMENT & GUEST CATEGORY CONTROLS -->
          <div style="margin-top:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <span style="font-size:0.78rem;font-weight:800;color:var(--text-primary);">🎨 Studio Template QR &amp; Category</span>
              <span id="qrContrastStatus" style="font-size:0.68rem;font-weight:700;padding:2px 7px;border-radius:99px;background:rgba(5,150,105,0.12);color:#059669;">✓ High Contrast</span>
            </div>

            <div class="form-grid-2" style="margin-bottom:8px;">
              <div class="form-group" style="margin-bottom:4px;">
                <label class="form-label" style="font-size:0.72rem;">Guest Category Label</label>
                <select id="cfgGuestCategorySelect" class="form-control" style="font-size:0.76rem;padding:5px 8px;" onchange="onGuestCategorySelectChange(this.value)">
                  <option value="VIP">⭐ VIP</option>
                  <option value="VVIP">👑 VVIP</option>
                  <option value="REGULAR">🎟️ REGULAR</option>
                  <option value="FAMILY">💐 FAMILY</option>
                  <option value="SPEAKER">🎙️ SPEAKER</option>
                  <option value="PRESS">📸 PRESS</option>
                  <option value="CUSTOM">✏️ Custom Category...</option>
                </select>
              </div>
              <div class="form-group" style="margin-bottom:4px;">
                <label class="form-label" style="font-size:0.72rem;">Category Text</label>
                <input type="text" id="cfgGuestCategory" class="form-control" value="VIP" style="font-size:0.76rem;padding:5px 8px;" oninput="state.guestCategory = this.value; renderCanvas();">
              </div>
            </div>

            <label style="display:flex;align-items:center;gap:8px;font-size:0.75rem;font-weight:600;cursor:pointer;margin-bottom:8px;">
              <input type="checkbox" id="cfgShowQrCategoryLabel" checked onchange="state.showQrCategoryLabel = this.checked; renderCanvas(); pushHistoryState();">
              <span>Show Guest Category Pill on Studio QR</span>
            </label>

            <div class="form-group" style="margin-bottom:8px;">
              <label class="form-label" style="font-size:0.72rem;">Studio QR Placement Mode</label>
              <select id="cfgStudioQrDock" class="form-control" style="font-size:0.76rem;padding:5px 8px;" onchange="setStudioQrDock(this.value)">
                <option value="bottom-center">Bottom Center (Classic Balanced)</option>
                <option value="bottom-right">Bottom Right Corner (Editorial / Floral Left)</option>
                <option value="bottom-left">Bottom Left Corner (Asymmetrical Right)</option>
                <option value="floating">Floating Custom X/Y (Drag Anywhere on Card)</option>
              </select>
            </div>

            <div class="form-grid-2" style="margin-bottom:6px;">
              <div class="form-group" style="margin-bottom:4px;">
                <label class="form-label" style="font-size:0.71rem;">Studio QR Size: <span id="valStudioQrSize" style="color:#FF7A00;font-weight:700;">76px</span></label>
                <input type="range" id="cfgStudioQrSize" min="54" max="150" step="2" value="76" class="form-control" style="padding:0;" oninput="state.studioQrSize = parseInt(this.value,10); state.qrSize = state.studioQrSize; document.getElementById('valStudioQrSize').textContent = this.value + 'px'; renderCanvas();">
              </div>
              <div class="form-group" style="margin-bottom:4px;">
                <label class="form-label" style="font-size:0.71rem;">Studio QR Rotate: <span id="valStudioQrRot" style="color:#FF7A00;font-weight:700;">0°</span></label>
                <input type="range" id="cfgStudioQrRot" min="-45" max="45" step="1" value="0" class="form-control" style="padding:0;" oninput="state.studioQrRotation = parseInt(this.value,10); document.getElementById('valStudioQrRot').textContent = this.value + '°'; renderCanvas();">
              </div>
            </div>

            <div id="studioQrFloatingControls" style="display:none;" class="form-grid-2">
              <div class="form-group" style="margin-bottom:4px;">
                <label class="form-label" style="font-size:0.71rem;">QR X (%): <span id="valStudioQrX">50%</span></label>
                <input type="range" id="cfgStudioQrX" min="10" max="90" step="1" value="50" class="form-control" style="padding:0;" oninput="state.studioQrX = parseFloat(this.value); document.getElementById('valStudioQrX').textContent = this.value + '%'; renderCanvas();">
              </div>
              <div class="form-group" style="margin-bottom:4px;">
                <label class="form-label" style="font-size:0.71rem;">QR Y (%): <span id="valStudioQrY">88%</span></label>
                <input type="range" id="cfgStudioQrY" min="10" max="94" step="1" value="88" class="form-control" style="padding:0;" oninput="state.studioQrY = parseFloat(this.value); document.getElementById('valStudioQrY').textContent = this.value + '%'; renderCanvas();">
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- TAB 2: 50 PREMIUM TEMPLATES LIBRARY (Studio Mode) -->
      <div class="tab-panel" data-tab-panel="tabTemplates" data-tab-group="studio">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:8px;flex-wrap:wrap;">
          <div>
            <div style="font-size:0.88rem;font-weight:800;color:var(--text-primary);letter-spacing:-0.2px;">
              50 Luxury Stationery Templates
            </div>
            <div style="font-size:0.72rem;color:var(--text-muted);">
              Original floral watercolor, botanical, editorial, classic &amp; African luxury artwork
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:6px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="saveCurrentDesignAsTemplate()" title="Save current edited card into your Template Library" style="font-size:0.7rem;padding:4px 9px;font-weight:700;">
              💾 Save as Template
            </button>
            <span style="font-size:0.72rem;background:linear-gradient(135deg,#B8955A,#8F6B32);color:#FFFFFF;font-weight:800;padding:3px 10px;border-radius:99px;box-shadow:0 2px 8px rgba(184,149,90,0.3);">
              50 Templates
            </span>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="form-group" style="margin-bottom:10px;position:relative;">
          <input type="text" id="tplSearchInput" class="form-control" placeholder="🔍 Search 50 luxury templates (e.g. Ivory Bloom, Peony, Eucalyptus, Zanzibar, Arch)..." style="padding-left:12px;font-size:0.8rem;" oninput="onTemplateSearchInput(this.value)">
        </div>

        <!-- Category Filter Pills Bar -->
        <div class="tpl-filter-bar">
          <button type="button" class="tpl-filter-pill active" data-cat="all" onclick="filterTemplatesByCategory('all')">
            ✨ All (50)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="wedding" onclick="filterTemplatesByCategory('wedding')">
            💍 Luxury Floral Weddings (10)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="minimalist" onclick="filterTemplatesByCategory('minimalist')">
            🏛️ Minimalist &amp; Editorial (10)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="nature" onclick="filterTemplatesByCategory('nature')">
            🌿 Botanical &amp; Nature (10)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="classic" onclick="filterTemplatesByCategory('classic')">
            👑 Luxury &amp; Classic (8)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="african" onclick="filterTemplatesByCategory('african')">
            🌍 African Luxury (7)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="celebrations" onclick="filterTemplatesByCategory('celebrations')">
            🥂 Modern Celebrations (5)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="recent" onclick="filterTemplatesByCategory('recent')">
            🕒 Recently Used
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="favorites" onclick="filterTemplatesByCategory('favorites')">
            ⭐ Favourites
          </button>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <span id="tplResultsCount" style="font-size:0.72rem;font-weight:700;color:var(--text-muted);">
            50 luxury templates available
          </span>
          <span style="font-size:0.7rem;color:#B8955A;font-weight:700;">
            Vector SVG Artwork • Fully Editable
          </span>
        </div>

        <!-- 50 Templates Dynamic Grid Container -->
        <div id="templatesMarketplaceGrid" class="tpl-marketplace-grid">
          <!-- Rendered dynamically by templates_library.js -->
        </div>
      </div>

      <!-- TAB 3: CONTENT & TEXT EDITING -->
      <div class="tab-panel" data-tab-panel="tabContent" data-tab-group="studio">
        <!-- Language Switcher Bar -->
        <div style="background:var(--surface-3);padding:10px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <span style="font-size:0.75rem;font-weight:700;color:var(--text-primary);text-transform:uppercase;letter-spacing:0.5px;">
              🌐 Card Language
            </span>
            <div style="display:flex;gap:4px;">
              <button type="button" id="langToggleEn" class="btn btn-sm btn-primary" onclick="setDesignerLanguage('en')" style="padding:3px 10px;font-size:0.75rem;font-weight:700;">
                🇬🇧 English
              </button>
              <button type="button" id="langToggleSw" class="btn btn-sm btn-secondary" onclick="setDesignerLanguage('sw')" style="padding:3px 10px;font-size:0.75rem;font-weight:700;">
                🇹🇿 Kiswahili
              </button>
            </div>
          </div>
          <div style="font-size:0.7rem;color:var(--text-muted);line-height:1.3;">
            Instantly translate headers, quote phrasing, tier badges and etiquette between English and Kiswahili.
          </div>
        </div>

        <!-- Quick VIP Tier Selectors & Custom Badge Input -->
        <div class="form-group" style="margin-bottom:10px;">
          <label class="form-label" style="font-size:0.75rem;display:flex;justify-content:space-between;align-items:center;">
            <span style="display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_badge" checked onchange="toggleFieldVisibility('badge', this.checked)">
              <span>Pass Badge / Invitation Header Tag</span>
            </span>
            <span style="color:var(--text-muted);font-weight:normal;font-size:0.68rem;">Quick Select:</span>
          </label>
          <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('👑 VVIP PASS')" style="padding:2px 7px;font-size:0.7rem;">👑 VVIP</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('⭐ VIP PASS')" style="padding:2px 7px;font-size:0.7rem;">⭐ VIP</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('🎟️ REGULAR PASS')" style="padding:2px 7px;font-size:0.7rem;">🎟️ Regular</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('💍 WEDDING INVITATION')" style="padding:2px 7px;font-size:0.7rem;">💍 Wedding</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('🥂 GALA INVITATION')" style="padding:2px 7px;font-size:0.7rem;">🥂 Gala</button>
          </div>
          <input type="text" id="cfgBadge" class="form-control" value="VIP DELEGATE PASS" oninput="renderCanvas()">
        </div>

        <!-- ============================================================
             DEDICATED DRESS CODE STUDIO PANEL (Requirement 1)
             ============================================================ -->
        <div style="background:linear-gradient(135deg, rgba(184,149,90,0.10), rgba(184,149,90,0.03));border:1.5px solid rgba(184,149,90,0.38);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:0.78rem;font-weight:800;color:var(--text-primary);display:inline-flex;align-items:center;gap:6px;">
              👔 Dedicated Dress Code Section
            </span>
            <label style="display:inline-flex;align-items:center;gap:5px;font-size:0.74rem;font-weight:700;cursor:pointer;color:#B8955A;">
              <input type="checkbox" id="cfgShowDressCode" checked onchange="state.showDressCode = this.checked; renderCanvas(); pushHistoryState();">
              <span>Show Section</span>
            </label>
          </div>

          <div class="form-grid-2" style="margin-bottom:8px;">
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:0.71rem;">12 Dress Code Presets</label>
              <select id="cfgDressCodePreset" class="form-control" style="font-size:0.75rem;padding:5px 8px;" onchange="onDressCodePresetSelect(this.value)">
                <option value="Formal Attire">Formal Attire</option>
                <option value="Black Tie">Black Tie</option>
                <option value="White Tie">White Tie</option>
                <option value="Smart Casual">Smart Casual</option>
                <option value="Cocktail Attire">Cocktail Attire</option>
                <option value="Traditional African Wear">Traditional African Wear</option>
                <option value="African Formal Attire">African Formal Attire</option>
                <option value="All White">All White</option>
                <option value="All Black">All Black</option>
                <option value="Cultural Attire">Cultural Attire</option>
                <option value="Casual Wear">Casual Wear</option>
                <option value="Custom Dress Code">✏️ Custom Dress Code...</option>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:0.71rem;">Custom Wording</label>
              <input type="text" id="cfgDressCode" class="form-control" value="Formal Attire" style="font-size:0.75rem;padding:5px 8px;" placeholder="e.g. Black Tie &amp; Gold" oninput="state.dressCode = this.value; renderCanvas();">
            </div>
          </div>

          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:0.71rem;">Additional Dress Code Instructions</label>
            <input type="text" id="cfgDressCodeNote" class="form-control" value="Shades of Champagne, Warm Ivory &amp; Sage Requested" style="font-size:0.74rem;padding:5px 8px;" placeholder="e.g. Floor-length gowns &amp; dark suits — No white" oninput="state.dressCodeNote = this.value; renderCanvas();">
          </div>

          <div class="form-grid-2" style="margin-bottom:8px;">
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:0.71rem;">Artistic Template Style</label>
              <select id="cfgDressCodeStyle" class="form-control" style="font-size:0.74rem;padding:5px 8px;" onchange="state.dressCodeStyle = this.value; renderCanvas(); pushHistoryState();">
                <option value="luxury_serif">🏛️ Luxury Serif Rules</option>
                <option value="floral_wreath">🌸 Floral Wreath Frame</option>
                <option value="african_ornament">🌍 African Geometric Frame</option>
                <option value="black_tie_gold">👑 Black-Tie Gold Plaque</option>
                <option value="corporate_minimal">💼 Corporate Minimal Bar</option>
                <option value="birthday_festive">🥂 Celebratory Sparkle Pill</option>
                <option value="arch_pill">🏺 Architectural Arch Capsule</option>
                <option value="monogram_scroll">📜 Classical Heraldic Scroll</option>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:0.71rem;">Dress Code Icon</label>
              <select id="cfgDressCodeIcon" class="form-control" style="font-size:0.74rem;padding:5px 8px;" onchange="state.dressCodeIcon = this.value; renderCanvas(); pushHistoryState();">
                <option value="tuxedo">🤵 Tuxedo Lapel</option>
                <option value="gown">👗 Evening Gown</option>
                <option value="bowtie">🎀 Classic Bow-Tie</option>
                <option value="crown">👑 Royal Crown</option>
                <option value="floral">🌿 Botanical Sprig</option>
                <option value="african_shield">🛡️ African Diamond Crest</option>
                <option value="sparkles">✨ Celebration Sparkles</option>
                <option value="hanger">🧥 Couture Hanger</option>
                <option value="diamond">💎 Faceted Diamond</option>
                <option value="none">🚫 No Icon</option>
              </select>
            </div>
          </div>

          <div class="form-grid-2" style="margin-bottom:6px;">
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:0.71rem;">Dress Code Font</label>
              <select id="cfgDressCodeFont" class="form-control" style="font-size:0.74rem;padding:4px 8px;" onchange="state.dressCodeFont = this.value; renderCanvas();">
                <option value="'Cormorant Garamond', serif">Cormorant Garamond</option>
                <option value="'Playfair Display', serif">Playfair Display</option>
                <option value="'Cinzel', serif">Cinzel Roman</option>
                <option value="'Montserrat', sans-serif">Montserrat Sans</option>
                <option value="'Libre Baskerville', serif">Libre Baskerville</option>
                <option value="'Marcellus', serif">Marcellus</option>
                <option value="'Great Vibes', cursive">Great Vibes Script</option>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:0.71rem;">Color &amp; Scale (<span id="valDressCodeScale">100%</span>)</label>
              <div style="display:flex;gap:6px;align-items:center;">
                <input type="color" id="cfgDressCodeColor" value="#B8955A" onchange="state.dressCodeColor = this.value; renderCanvas();" style="width:34px;height:28px;padding:1px;border-radius:6px;border:1px solid var(--border);cursor:pointer;">
                <input type="range" id="cfgDressCodeScale" min="75" max="140" step="5" value="100" class="form-control" style="padding:0;flex:1;" oninput="state.dressCodeScale = parseInt(this.value,10); document.getElementById('valDressCodeScale').textContent = this.value + '%'; renderCanvas();">
              </div>
            </div>
          </div>

          <div class="form-grid-2" style="margin-bottom:2px;">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label" style="font-size:0.69rem;">Section Width: <span id="valDressCodeWidth">245px</span></label>
              <input type="range" id="cfgDressCodeWidth" min="150" max="360" step="5" value="245" class="form-control" style="padding:0;" oninput="state.dressCodeWidth = parseInt(this.value,10); document.getElementById('valDressCodeWidth').textContent = this.value + 'px'; renderCanvas();">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label" style="font-size:0.69rem;">Vertical Offset: <span id="valDressCodeOffset">0px</span></label>
              <input type="range" id="cfgDressCodeOffsetY" min="-60" max="60" step="2" value="0" class="form-control" style="padding:0;" oninput="state.dressCodeOffsetY = parseInt(this.value,10); document.getElementById('valDressCodeOffset').textContent = this.value + 'px'; renderCanvas();">
            </div>
          </div>
        </div>

        <!-- ============================================================
             FLEXIBLE INVITATION CONTENT FIELDS (19 Supported Fields)
             ============================================================ -->
        <div class="form-group" style="margin-bottom:8px;">
          <label class="form-label" style="display:flex;justify-content:space-between;align-items:center;">
            <span style="display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_hostNames" checked onchange="toggleFieldVisibility('hostNames', this.checked)">
              <span>Host Names / Families Line</span>
            </span>
          </label>
          <input type="text" id="cfgHostNames" class="form-control" value="Together with their families" placeholder="e.g. Hosted by The Mensah &amp; Laurent Families" oninput="renderCanvas()">
        </div>

        <div class="form-group" style="margin-bottom:8px;">
          <label class="form-label" style="display:flex;justify-content:space-between;align-items:center;">
            <span style="display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_sampleCouple" checked onchange="toggleFieldVisibility('sampleCouple', this.checked)">
              <span>Couple Names (Calligraphy Script Line)</span>
            </span>
          </label>
          <input type="text" id="cfgCouple" class="form-control" value="Amelia &amp; Sebastian" placeholder="e.g. Amelia &amp; Sebastian" oninput="renderCanvas()">
        </div>

        <div class="form-grid-2" style="margin-bottom:8px;">
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_celebrantName" onchange="toggleFieldVisibility('celebrantName', this.checked)">
              <span>Celebrant Name</span>
            </label>
            <input type="text" id="cfgCelebrantName" class="form-control" value="" placeholder="For Birthdays / Graduations" style="font-size:0.76rem;" oninput="renderCanvas()">
          </div>
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_guestOfHonour" onchange="toggleFieldVisibility('guestOfHonour', this.checked)">
              <span>Guest of Honour</span>
            </label>
            <input type="text" id="cfgGuestOfHonour" class="form-control" value="" placeholder="For Corporate / Galas" style="font-size:0.76rem;" oninput="renderCanvas()">
          </div>
        </div>

        <div class="form-group" style="margin-bottom:8px;">
          <label class="form-label" style="display:inline-flex;align-items:center;gap:5px;">
            <input type="checkbox" id="chkField_title" checked onchange="toggleFieldVisibility('title', this.checked)">
            <span>Event Headline Title</span>
          </label>
          <input type="text" id="cfgTitle" class="form-control" value="<?= e($event['title'] ?? 'Annual Gala & Tech Awards') ?>" oninput="renderCanvas()">
        </div>

        <div class="form-group" style="margin-bottom:8px;">
          <label class="form-label" style="display:inline-flex;align-items:center;gap:5px;">
            <input type="checkbox" id="chkField_subtitle" onchange="toggleFieldVisibility('subtitle', this.checked)">
            <span>Event Subtitle / Edition</span>
          </label>
          <input type="text" id="cfgSubtitle" class="form-control" value="Celebrating Technological Leadership &amp; Excellence" oninput="renderCanvas()">
        </div>

        <div class="form-group" style="margin-bottom:8px;">
          <label class="form-label" style="display:inline-flex;align-items:center;gap:5px;">
            <input type="checkbox" id="chkField_quote" checked onchange="toggleFieldVisibility('quote', this.checked)">
            <span>Invitation Message / Event Description</span>
          </label>
          <input type="text" id="cfgQuote" class="form-control" value="Honored to request the pleasure of your company" oninput="renderCanvas()">
        </div>

        <div class="form-group" style="margin-bottom:8px;">
          <label class="form-label" style="display:inline-flex;align-items:center;gap:5px;">
            <input type="checkbox" id="chkField_guest" checked onchange="toggleFieldVisibility('guest', this.checked)">
            <span>Sample Attendee Name</span>
          </label>
          <input type="text" id="cfgGuest" class="form-control" value="Dr. Reginald Massawe" oninput="renderCanvas()">
        </div>

        <div class="form-grid-2" style="margin-bottom:8px;">
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_date" checked onchange="toggleFieldVisibility('date', this.checked)">
              <span>Event Date</span>
            </label>
            <input type="text" id="cfgDate" class="form-control" value="<?= date('l, M d, Y', strtotime($event['start_date'] ?? 'now')) ?>" style="font-size:0.76rem;" oninput="renderCanvas()">
          </div>
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_time" checked onchange="toggleFieldVisibility('time', this.checked)">
              <span>Start &amp; End Time</span>
            </label>
            <div style="display:flex;gap:4px;">
              <input type="text" id="cfgTime" class="form-control" value="<?= e($event['start_time'] ?? '18:00') ?>" style="font-size:0.74rem;padding:5px 6px;" placeholder="Start" oninput="renderCanvas()">
              <input type="text" id="cfgEndTime" class="form-control" value="23:30 EAT" style="font-size:0.74rem;padding:5px 6px;" placeholder="End" oninput="renderCanvas()">
            </div>
          </div>
        </div>

        <div class="form-grid-2" style="margin-bottom:8px;">
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_venue" checked onchange="toggleFieldVisibility('venue', this.checked)">
              <span>Venue Name</span>
            </label>
            <input type="text" id="cfgVenue" class="form-control" value="<?= e($event['venue_name'] ?? 'Serena Grand Ballroom') ?>" style="font-size:0.76rem;" oninput="renderCanvas()">
          </div>
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_fullAddress" checked onchange="toggleFieldVisibility('fullAddress', this.checked)">
              <span>Full Address</span>
            </label>
            <input type="text" id="cfgFullAddress" class="form-control" value="Oceanfront Gardens, Dar es Salaam" style="font-size:0.76rem;" oninput="renderCanvas()">
          </div>
        </div>

        <div class="form-grid-2" style="margin-bottom:8px;">
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;">Seating / Gate Access</label>
            <input type="text" id="cfgSeat" class="form-control" value="Table 04 &bull; VIP North Gate 1" style="font-size:0.76rem;" oninput="renderCanvas()">
          </div>
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_rsvp" checked onchange="toggleFieldVisibility('rsvp', this.checked)">
              <span>RSVP Deadline</span>
            </label>
            <input type="text" id="cfgRsvp" class="form-control" value="RSVP by Nov 10, 2026" style="font-size:0.76rem;" oninput="renderCanvas()">
          </div>
        </div>

        <div class="form-grid-2" style="margin-bottom:8px;">
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_contactInfo" onchange="toggleFieldVisibility('contactInfo', this.checked)">
              <span>Contact Info</span>
            </label>
            <input type="text" id="cfgContactInfo" class="form-control" value="+255 754 000 111" style="font-size:0.75rem;" oninput="renderCanvas()">
          </div>
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_additionalInstructions" onchange="toggleFieldVisibility('additionalInstructions', this.checked)">
              <span>Extra Instructions</span>
            </label>
            <input type="text" id="cfgAdditionalInstructions" class="form-control" value="Valet parking at North Gate" style="font-size:0.75rem;" oninput="renderCanvas()">
          </div>
        </div>

        <div class="form-grid-2" style="margin-bottom:8px;">
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_eventWebsite" onchange="toggleFieldVisibility('eventWebsite', this.checked)">
              <span>Event Website</span>
            </label>
            <input type="text" id="cfgEventWebsite" class="form-control" value="www.leulaevents.com" style="font-size:0.75rem;" oninput="renderCanvas()">
          </div>
          <div class="form-group" style="margin-bottom:4px;">
            <label class="form-label" style="font-size:0.72rem;display:inline-flex;align-items:center;gap:5px;">
              <input type="checkbox" id="chkField_socialMedia" onchange="toggleFieldVisibility('socialMedia', this.checked)">
              <span>Social / Hashtag</span>
            </label>
            <input type="text" id="cfgSocialMedia" class="form-control" value="#LeulaLuxuryEvents" style="font-size:0.75rem;" oninput="renderCanvas()">
          </div>
        </div>

        <div class="form-group" style="margin-bottom:4px;">
          <label class="form-label" style="font-size:0.72rem;">Card Footer Etiquette Note</label>
          <input type="text" id="cfgFooter" class="form-control" value="Black Tie Required &bull; Non-Transferable Digital QR Ticket" style="font-size:0.76rem;" oninput="renderCanvas()">
        </div>
      </div>

      <!-- TAB 4: TYPOGRAPHY & COLORS -->
      <div class="tab-panel" data-tab-panel="tabStyling" data-tab-group="studio">
        <div class="form-group">
          <label class="form-label">Primary Headline &amp; Serif Font Family</label>
          <select id="cfgFont" class="form-control" onchange="renderCanvas()">
            <optgroup label="Luxury Editorial Serif Fonts">
              <option value="'Cormorant Garamond', serif">Cormorant Garamond (Classical Luxury Serif)</option>
              <option value="'Playfair Display', serif">Playfair Display (Haute Couture Editorial Serif)</option>
              <option value="'Cinzel', serif">Cinzel (Regal Imperial Roman)</option>
              <option value="'Libre Baskerville', serif">Libre Baskerville (Timeless Engraved Serif)</option>
              <option value="'DM Serif Display', serif">DM Serif Display (Architectural High-Contrast)</option>
              <option value="'Marcellus', serif">Marcellus (Antiquity Roman)</option>
            </optgroup>
            <optgroup label="Calligraphy &amp; Script Fonts">
              <option value="'Great Vibes', cursive">Great Vibes (Pointed-Pen Calligraphy)</option>
              <option value="'Allura', cursive">Allura (Editorial Script Calligraphy)</option>
              <option value="'Tangerine', cursive">Tangerine (Heirloom Italic Script)</option>
              <option value="'Parisienne', cursive">Parisienne (French Couture Script)</option>
              <option value="'Alex Brush', cursive">Alex Brush (Flowing Brush Calligraphy)</option>
            </optgroup>
            <optgroup label="Modern Sans-Serif Fonts">
              <option value="'Montserrat', sans-serif">Montserrat (Geometric Modern Sans)</option>
              <option value="'Lato', sans-serif">Lato (Warm Humanist Sans)</option>
              <option value="'Poppins', sans-serif">Poppins (Contemporary Studio Sans)</option>
              <option value="'Inter', sans-serif">Inter (Clean Swiss Enterprise)</option>
              <option value="'Outfit', sans-serif">Outfit (Clean Luxury Geometric)</option>
              <option value="'Sora', sans-serif">Sora (Modern Futuristic Sans)</option>
              <option value="'Plus Jakarta Sans', sans-serif">Plus Jakarta Sans (Corporate Executive)</option>
              <option value="'Space Grotesk', sans-serif">Space Grotesk (Tech Monospace)</option>
              <option value="'Bebas Neue', sans-serif">Bebas Neue (Bold Display)</option>
            </optgroup>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Calligraphy / Script Accent Font</label>
          <select id="cfgScriptFont" class="form-control" onchange="renderCanvas()">
            <option value="'Great Vibes', cursive">Great Vibes (Romantic Pointed-Pen Calligraphy)</option>
            <option value="'Allura', cursive">Allura (Soft Flowing Editorial Script)</option>
            <option value="'Tangerine', cursive">Tangerine (Tall Heirloom Calligraphy)</option>
            <option value="'Parisienne', cursive">Parisienne (Parisian Couture Script)</option>
            <option value="'Alex Brush', cursive">Alex Brush (Classic Wedding Brush Script)</option>
            <option value="'Cormorant Garamond', serif">Cormorant Garamond Italic (Serif Italic)</option>
          </select>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Title Size: <span id="valTitleScale" style="color:#FF7A00;font-weight:700;">100%</span></label>
            <input type="range" id="cfgTitleScale" min="70" max="150" value="100" class="form-control" style="padding:0;" oninput="updateScales(); renderCanvas();">
          </div>
          <div class="form-group">
            <label class="form-label">Guest Size: <span id="valGuestScale" style="color:#FF7A00;font-weight:700;">100%</span></label>
            <input type="range" id="cfgGuestScale" min="70" max="150" value="100" class="form-control" style="padding:0;" oninput="updateScales(); renderCanvas();">
          </div>
        </div>

        <!-- Typography & Alignment Controls -->
        <div class="form-group">
          <label class="form-label">Font Styling &amp; Text Alignment</label>
          <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <button type="button" id="btnFontBold" class="btn btn-secondary btn-sm" onclick="toggleFontFormat('bold')" style="font-weight:800;padding:6px 14px;">B</button>
            <button type="button" id="btnFontItalic" class="btn btn-secondary btn-sm" onclick="toggleFontFormat('italic')" style="font-style:italic;font-family:serif;padding:6px 14px;">I</button>
            <button type="button" id="btnFontUnderline" class="btn btn-secondary btn-sm" onclick="toggleFontFormat('underline')" style="text-decoration:underline;padding:6px 14px;">U</button>
            <div style="width:1px;height:24px;background:var(--border-color);margin:0 4px;"></div>
            <button type="button" id="btnAlignLeft" class="btn btn-secondary btn-sm" onclick="setTextAlignment('left')">Left</button>
            <button type="button" id="btnAlignCenter" class="btn btn-secondary btn-sm active" onclick="setTextAlignment('center')">Center</button>
            <button type="button" id="btnAlignRight" class="btn btn-secondary btn-sm" onclick="setTextAlignment('right')">Right</button>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Letter Spacing: <span id="valLetterSpacing" style="color:#FF7A00;font-weight:700;">Normal</span></label>
            <input type="range" id="cfgLetterSpacing" min="-1" max="6" value="0" class="form-control" style="padding:0;" oninput="updateLetterSpacing(this.value); renderCanvas();">
          </div>
          <div class="form-group">
            <label class="form-label">Line Spacing: <span id="valLineHeight" style="color:#FF7A00;font-weight:700;">1.2x</span></label>
            <input type="range" id="cfgLineHeight" min="10" max="22" value="12" class="form-control" style="padding:0;" oninput="updateLineHeight(this.value); renderCanvas();">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Header Gradient Colors</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" id="cfgHeaderColor1" value="#FF7A00" onchange="renderCanvas()" style="width:44px;height:40px;border-radius:8px;border:1px solid var(--border);cursor:pointer;padding:2px;">
            <input type="color" id="cfgHeaderColor2" value="#E96800" onchange="renderCanvas()" style="width:44px;height:40px;border-radius:8px;border:1px solid var(--border);cursor:pointer;padding:2px;">
            <span style="font-size:0.8rem;color:var(--text-secondary);">Start &amp; End Gradient</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Heading &amp; Event Title Color</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" id="cfgHeadingColor" value="#FFFFFF" onchange="renderCanvas()" style="width:44px;height:40px;border-radius:8px;border:1px solid var(--border);cursor:pointer;padding:2px;">
            <span style="font-size:0.8rem;color:var(--text-secondary);">Event Title &amp; Top Header Text</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Card Body Background Color</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" id="cfgBodyBg" value="#FFFFFF" onchange="renderCanvas()" style="width:44px;height:40px;border-radius:8px;border:1px solid var(--border);cursor:pointer;padding:2px;">
            <span style="font-size:0.8rem;color:var(--text-secondary);">Card Surface Color</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Primary Text Color</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" id="cfgTextColor" value="#09090B" onchange="renderCanvas()" style="width:44px;height:40px;border-radius:8px;border:1px solid var(--border);cursor:pointer;padding:2px;">
            <span style="font-size:0.8rem;color:var(--text-secondary);">Main Attendee &amp; Labels</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Border &amp; Frame Accent Color</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" id="cfgBorderColor" value="#E4E4E7" onchange="renderCanvas()" style="width:44px;height:40px;border-radius:8px;border:1px solid var(--border);cursor:pointer;padding:2px;">
            <span style="font-size:0.8rem;color:var(--text-secondary);">Outer Card Frame &amp; Inset Borders</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Brand Accent &amp; Highlights Color</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" id="cfgAccentColor" value="#FF7A00" onchange="renderCanvas()" style="width:44px;height:40px;border-radius:8px;border:1px solid var(--border);cursor:pointer;padding:2px;">
            <span style="font-size:0.8rem;color:var(--text-secondary);">Badges, Borders &amp; Highlights</span>
          </div>
        </div>
      </div>

      <!-- TAB 5: ORNAMENTS & FRAMES -->
      <div class="tab-panel" data-tab-panel="tabElements" data-tab-group="studio">
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:0.78rem;font-weight:800;color:var(--text-primary);">🌸 Original Vector Artwork Layer</span>
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;font-weight:700;cursor:pointer;">
              <input type="checkbox" id="cfgShowArtwork" checked onchange="state.showArtwork = this.checked; renderCanvas(); pushHistoryState();">
              <span>Show Artwork</span>
            </label>
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
              <label class="form-label" style="font-size:0.72rem;margin:0;">Artwork Illustration Opacity</label>
              <span id="valArtworkOpacity" style="font-size:0.72rem;font-weight:700;color:#B8955A;">100%</span>
            </div>
            <input type="range" id="cfgArtworkOpacity" min="15" max="100" step="5" value="100" class="form-control" style="padding:0;" oninput="state.artworkOpacity = parseInt(this.value, 10); document.getElementById('valArtworkOpacity').textContent = this.value + '%'; renderCanvas();">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" style="font-size:0.72rem;">Stationery Architecture</label>
            <select id="cfgHeaderStyle" class="form-control" style="font-size:0.76rem;" onchange="state.headerStyle = this.value; renderCanvas(); pushHistoryState();">
              <option value="unified">Unified Luxury Stationery Sheet (Full-Card Artwork)</option>
              <option value="split">Classic Split Header Banner</option>
            </select>
          </div>
        </div>

        <!-- ============================================================
             INDEPENDENTLY EDITABLE VECTOR DECORATIVE OBJECTS STUDIO
             ============================================================ -->
        <div id="decorativeObjectsStudio" style="background:linear-gradient(135deg,rgba(184,149,90,0.12),rgba(184,149,90,0.03));border:1px solid rgba(184,149,90,0.38);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:0.77rem;font-weight:800;color:var(--text-primary);">✨ Editable Vector Decorative Objects</span>
            <span id="decorObjectsCountBadge" style="font-size:0.66rem;font-weight:700;color:#B8955A;background:rgba(184,149,90,0.16);padding:2px 8px;border-radius:99px;">2 Objects</span>
          </div>
          <p style="font-size:0.71rem;color:var(--text-secondary);margin:0 0 8px;line-height:1.35;">
            Add, drag, resize, rotate, lock or delete standalone floral bouquets, botanical stems, crests &amp; cultural ornaments directly on the canvas.
          </p>

          <div style="display:flex;gap:6px;margin-bottom:10px;">
            <select id="addDecorMotifSelect" class="form-control" style="font-size:0.74rem;height:32px;flex:1;">
              <option value="ivory_bloom_cluster">🌸 Ivory Watercolor Peony Bouquet</option>
              <option value="blush_rose_duo">🌹 Blush Rose &amp; Botanical Duo</option>
              <option value="gold_olive_sprig">🌿 Metallic Gold Olive Branch</option>
              <option value="eucalyptus_stem">🍃 Sage Eucalyptus Stem</option>
              <option value="royal_crest_emblem">👑 Royal Monogram Crest</option>
              <option value="african_diamond_seal">🔶 African Geometric Gold Emblem</option>
              <option value="baroque_corner">⚜️ Baroque Filigree Ornament</option>
              <option value="pampas_plume">🌾 Boho Pampas Plume</option>
              <option value="orchid_blossom">🌺 Exotic Orchid Stem</option>
              <option value="diamond_rings">💍 Interlocking Wedding Rings</option>
              <option value="sunburst_medallion">☀️ Celestial Sunburst Medallion</option>
            </select>
            <button type="button" class="btn btn-primary btn-sm" onclick="addDecorativeObject()" style="font-size:0.72rem;padding:0 10px;height:32px;white-space:nowrap;">
              + Add Motif
            </button>
          </div>

          <!-- Active Decorative Objects List -->
          <div id="decorObjectsList" style="display:flex;flex-direction:column;gap:5px;max-height:150px;overflow-y:auto;margin-bottom:8px;"></div>

          <!-- Selected Decorative Object Inspector -->
          <div id="activeDecorInspector" style="display:none;background:var(--surface-2);border:1px solid var(--border);border-radius:6px;padding:9px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
              <span id="activeDecorTitle" style="font-size:0.72rem;font-weight:700;color:var(--text-primary);">Selected Motif</span>
              <div style="display:flex;gap:4px;">
                <button type="button" id="btnToggleDecorLock" class="btn btn-secondary btn-sm" onclick="toggleActiveDecorLock()" style="font-size:0.65rem;padding:2px 6px;">🔓 Lock</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="deleteActiveDecorObject()" style="font-size:0.65rem;padding:2px 6px;color:#EF4444;">🗑 Delete</button>
              </div>
            </div>
            <div class="form-grid-2" style="gap:6px;margin-bottom:6px;">
              <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.66rem;margin-bottom:1px;">Size: <span id="valDecorSize">95px</span></label>
                <input type="range" id="cfgDecorSize" min="36" max="240" step="2" value="95" class="form-control" style="padding:0;" oninput="updateActiveDecorProp('size', parseInt(this.value,10))">
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.66rem;margin-bottom:1px;">Rotation: <span id="valDecorRot">0°</span></label>
                <input type="range" id="cfgDecorRot" min="-180" max="180" step="2" value="0" class="form-control" style="padding:0;" oninput="updateActiveDecorProp('rotation', parseInt(this.value,10))">
              </div>
            </div>
            <div class="form-grid-2" style="gap:6px;">
              <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.66rem;margin-bottom:1px;">Pos X: <span id="valDecorX">50%</span></label>
                <input type="range" id="cfgDecorX" min="0" max="100" step="1" value="50" class="form-control" style="padding:0;" oninput="updateActiveDecorProp('x', parseInt(this.value,10))">
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.66rem;margin-bottom:1px;">Pos Y: <span id="valDecorY">20%</span></label>
                <input type="range" id="cfgDecorY" min="0" max="100" step="1" value="20" class="form-control" style="padding:0;" oninput="updateActiveDecorProp('y', parseInt(this.value,10))">
              </div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Card Border &amp; Frame Aesthetic</label>
          <select id="cfgBorderFrame" class="form-control" onchange="renderCanvas()">
            <option value="none">Standard Smooth Border</option>
            <option value="gold_single">Metallic Accent Frame</option>
            <option value="gold_double">Regal Double-Line Border</option>
            <option value="glass_glow">Ambient Neon Glass Glow</option>
            <option value="deco_corners">Art Deco Inset Notches</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Official Seal / Ribbon Emblem</label>
          <select id="cfgSeal" class="form-control" onchange="renderCanvas()">
            <option value="ribbon">⭐ Star Ribbon Badge</option>
            <option value="crown">👑 Royal Crown Emblem</option>
            <option value="verified">🛡️ Official Pass Seal</option>
            <option value="diamond">💎 Diamond Crest</option>
            <option value="ticket">🎟️ Golden Admission Ticket</option>
            <option value="none">None (Clean Minimal)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Surface Background Texture</label>
          <select id="cfgTexture" class="form-control" onchange="renderCanvas()">
            <option value="none">None (Solid Pure Surface)</option>
            <option value="dots">Subtle Geometric Grid</option>
            <option value="glow">Central Ambient Glow</option>
            <option value="marble">Luxury Marble Veins</option>
            <option value="stardust">Stardust Golden Shimmer</option>
          </select>
        </div>
      </div>

      <!-- TAB 2: COVER PHOTO & ADVANCED LAYOUT OPTIONS -->
      <div class="tab-panel" data-tab-panel="tabCoverLayout" data-tab-group="studio">

        <!-- ============================================================
             SECTION A: REPLACEABLE TEMPLATE PORTRAITS & MULTI-PHOTO LAYERS
             ============================================================ -->
        <div id="templatePhotosStudio" style="background:linear-gradient(135deg,rgba(184,149,90,0.14),rgba(255,122,0,0.06));border:1px solid rgba(184,149,90,0.45);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <div>
              <div style="font-size:0.84rem;font-weight:800;color:var(--text-primary);">🖼️ Editable Photographs &amp; Frames</div>
              <div style="font-size:0.7rem;color:var(--text-muted);">Replace template portraits, add multiple photos, mask, crop &amp; rotate</div>
            </div>
            <span id="photoLayersCountBadge" style="font-size:0.68rem;font-weight:800;color:#B8955A;background:rgba(184,149,90,0.18);padding:2px 8px;border-radius:99px;">0 Photos</span>
          </div>

          <div style="display:flex;gap:6px;margin-bottom:10px;">
            <button type="button" class="btn btn-primary" onclick="triggerAddOrReplacePhotoLayer('add')" style="flex:1;justify-content:center;font-size:0.76rem;padding:7px 10px;font-weight:700;">
              <?= svgIcon('upload', 13) ?> <span>+ Upload / Add Photo Layer</span>
            </button>
            <button type="button" class="btn btn-secondary" onclick="addSamplePortraitLayer()" style="font-size:0.72rem;padding:7px 10px;font-weight:700;" title="Insert an editable portrait frame">
              + Portrait Frame
            </button>
          </div>
          <input type="file" id="photoLayerFileInput" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none;" onchange="handlePhotoLayerFileSelect(this)">

          <!-- Multi-Photo Layer Stack List -->
          <div id="photoLayersList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px;max-height:165px;overflow-y:auto;"></div>

          <!-- Selected Photo Layer Inspector -->
          <div id="activePhotoInspector" style="display:none;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <span id="activePhotoInspectorTitle" style="font-size:0.75rem;font-weight:800;color:var(--text-primary);">Selected Photo Frame</span>
              <div style="display:flex;gap:4px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="triggerAddOrReplacePhotoLayer('replace')" style="font-size:0.66rem;padding:2px 7px;">🔄 Replace</button>
                <button type="button" id="btnTogglePhotoLock" class="btn btn-secondary btn-sm" onclick="toggleActivePhotoLock()" style="font-size:0.66rem;padding:2px 7px;">🔓 Lock</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="shiftActivePhotoZIndex(1)" style="font-size:0.66rem;padding:2px 6px;" title="Bring Forward">↑</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="shiftActivePhotoZIndex(-1)" style="font-size:0.66rem;padding:2px 6px;" title="Send Backward">↓</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="deleteActivePhotoLayer()" style="font-size:0.66rem;padding:2px 6px;color:#EF4444;" title="Delete Photo">🗑</button>
              </div>
            </div>

            <!-- Mask / Shape & Border -->
            <div class="form-grid-2" style="gap:8px;margin-bottom:8px;">
              <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.68rem;margin-bottom:2px;">Image Mask / Shape</label>
                <select id="cfgPhotoLayerShape" class="form-control" style="font-size:0.73rem;height:30px;padding:2px 6px;" onchange="updateActivePhotoProp('shape', this.value)">
                  <option value="arch">🏛️ Luxury Arch Frame</option>
                  <option value="circle">⭕ Circular Medallion</option>
                  <option value="oval">🥚 Editorial Oval Cameo</option>
                  <option value="rounded">▢ Rounded Portrait</option>
                  <option value="rect">▭ Sharp Editorial Rect</option>
                  <option value="octagon">🛑 Royal Octagon Crest</option>
                  <option value="diamond">🔶 Diamond Cutout</option>
                  <option value="shield">🛡️ Heraldic Shield</option>
                </select>
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.68rem;margin-bottom:2px;">Frame Border Color &amp; Width</label>
                <div style="display:flex;gap:4px;align-items:center;">
                  <input type="color" id="cfgPhotoLayerBorderColor" value="#C5A059" style="width:30px;height:30px;padding:1px;border-radius:4px;border:1px solid var(--border);" oninput="updateActivePhotoProp('borderColor', this.value)">
                  <input type="range" id="cfgPhotoLayerBorderWidth" min="0" max="8" step="0.5" value="2" class="form-control" style="padding:0;flex:1;" oninput="updateActivePhotoProp('borderWidth', parseFloat(this.value))">
                  <span id="valPhotoBorderW" style="font-size:0.66rem;font-weight:700;min-width:24px;">2px</span>
                </div>
              </div>
            </div>

            <!-- Position X, Y & Size W, H -->
            <div class="form-grid-2" style="gap:8px;margin-bottom:8px;">
              <div class="form-group" style="margin:0;">
                <div style="display:flex;justify-content:space-between;">
                  <label class="form-label" style="font-size:0.68rem;margin:0;">Width</label>
                  <span id="valPhotoLayerW" style="font-size:0.68rem;font-weight:700;color:#B8955A;">150px</span>
                </div>
                <input type="range" id="cfgPhotoLayerW" min="55" max="420" step="2" value="150" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('w', parseInt(this.value,10))">
              </div>
              <div class="form-group" style="margin:0;">
                <div style="display:flex;justify-content:space-between;">
                  <label class="form-label" style="font-size:0.68rem;margin:0;">Height</label>
                  <span id="valPhotoLayerH" style="font-size:0.68rem;font-weight:700;color:#B8955A;">190px</span>
                </div>
                <input type="range" id="cfgPhotoLayerH" min="55" max="460" step="2" value="190" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('h', parseInt(this.value,10))">
              </div>
            </div>

            <div class="form-grid-2" style="gap:8px;margin-bottom:8px;">
              <div class="form-group" style="margin:0;">
                <div style="display:flex;justify-content:space-between;">
                  <label class="form-label" style="font-size:0.68rem;margin:0;">Pos X</label>
                  <span id="valPhotoLayerX" style="font-size:0.68rem;font-weight:700;color:#B8955A;">50%</span>
                </div>
                <input type="range" id="cfgPhotoLayerX" min="0" max="100" step="1" value="50" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('x', parseInt(this.value,10))">
              </div>
              <div class="form-group" style="margin:0;">
                <div style="display:flex;justify-content:space-between;">
                  <label class="form-label" style="font-size:0.68rem;margin:0;">Pos Y</label>
                  <span id="valPhotoLayerY" style="font-size:0.68rem;font-weight:700;color:#B8955A;">30%</span>
                </div>
                <input type="range" id="cfgPhotoLayerY" min="0" max="100" step="1" value="30" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('y', parseInt(this.value,10))">
              </div>
            </div>

            <!-- Rotation & Corner Radius -->
            <div class="form-grid-2" style="gap:8px;margin-bottom:8px;">
              <div class="form-group" style="margin:0;">
                <div style="display:flex;justify-content:space-between;">
                  <label class="form-label" style="font-size:0.68rem;margin:0;">Rotation</label>
                  <span id="valPhotoLayerRot" style="font-size:0.68rem;font-weight:700;color:#B8955A;">0°</span>
                </div>
                <input type="range" id="cfgPhotoLayerRot" min="-180" max="180" step="1" value="0" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('rotation', parseInt(this.value,10))">
              </div>
              <div class="form-group" style="margin:0;">
                <div style="display:flex;justify-content:space-between;">
                  <label class="form-label" style="font-size:0.68rem;margin:0;">Corner Radius</label>
                  <span id="valPhotoLayerRadius" style="font-size:0.68rem;font-weight:700;color:#B8955A;">14px</span>
                </div>
                <input type="range" id="cfgPhotoLayerRadius" min="0" max="60" step="2" value="14" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('cornerRadius', parseInt(this.value,10))">
              </div>
            </div>

            <!-- Crop Zoom & Pan X / Y -->
            <div style="border-top:1px dashed var(--border);padding-top:8px;margin-top:4px;">
              <div style="font-size:0.68rem;font-weight:700;color:var(--text-secondary);margin-bottom:4px;">Crop, Zoom &amp; Image Adjustments</div>
              <div class="form-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:6px;">
                <div class="form-group" style="margin:0;">
                  <label class="form-label" style="font-size:0.64rem;margin:0;">Crop Zoom: <span id="valPhotoCropZoom">100%</span></label>
                  <input type="range" id="cfgPhotoCropZoom" min="100" max="250" step="5" value="100" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('cropZoom', parseInt(this.value,10))">
                </div>
                <div class="form-group" style="margin:0;">
                  <label class="form-label" style="font-size:0.64rem;margin:0;">Pan X: <span id="valPhotoCropX">50%</span></label>
                  <input type="range" id="cfgPhotoCropX" min="0" max="100" step="1" value="50" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('cropX', parseInt(this.value,10))">
                </div>
                <div class="form-group" style="margin:0;">
                  <label class="form-label" style="font-size:0.64rem;margin:0;">Pan Y: <span id="valPhotoCropY">50%</span></label>
                  <input type="range" id="cfgPhotoCropY" min="0" max="100" step="1" value="50" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('cropY', parseInt(this.value,10))">
                </div>
              </div>
              <div class="form-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;">
                <div class="form-group" style="margin:0;">
                  <label class="form-label" style="font-size:0.64rem;margin:0;">Bright: <span id="valPhotoBright">100%</span></label>
                  <input type="range" id="cfgPhotoBright" min="50" max="150" step="2" value="100" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('brightness', parseInt(this.value,10))">
                </div>
                <div class="form-group" style="margin:0;">
                  <label class="form-label" style="font-size:0.64rem;margin:0;">Contrast: <span id="valPhotoContrast">100%</span></label>
                  <input type="range" id="cfgPhotoContrast" min="50" max="150" step="2" value="100" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('contrast', parseInt(this.value,10))">
                </div>
                <div class="form-group" style="margin:0;">
                  <label class="form-label" style="font-size:0.64rem;margin:0;">Opacity: <span id="valPhotoOpacity">100%</span></label>
                  <input type="range" id="cfgPhotoOpacity" min="15" max="100" step="5" value="100" class="form-control" style="padding:0;" oninput="updateActivePhotoProp('opacity', parseInt(this.value,10))">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cover Photo Upload & Status Header -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#FF7A00,#E96800);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(255,122,0,0.25);">
              <?= svgIcon('palette', 18) ?>
            </div>
            <div>
              <div style="font-weight:700;color:var(--text-primary);font-size:0.86rem;">Card Background Cover Photo</div>
              <div style="font-size:0.72rem;color:var(--text-muted);">5 distinct background layouts (Full, Top, Bottom, Half, Custom)</div>
            </div>
          </div>

          <p style="font-size:0.75rem;color:var(--text-secondary);margin:0 0 10px;line-height:1.4;">
            Upload an event poster, keynote speaker or couple photo. Choose between 5 layout architectures without losing your text or QR codes.
          </p>

          <div style="display:flex;gap:6px;">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('cardCoverInput').click()" style="flex:1;justify-content:center;font-size:0.8rem;padding:9px 12px;font-weight:700;box-shadow:0 3px 10px rgba(255,122,0,0.25);">
              <?= svgIcon('upload', 14) ?> <span id="coverUploadBtnText">Upload Cover Photo</span>
            </button>
            <button type="button" id="btnRemoveCover" class="btn btn-secondary" onclick="removeCoverPhoto()" style="display:none;color:#DC2626;padding:9px 12px;" title="Remove Cover Photo">
              <?= svgIcon('trash', 14) ?>
            </button>
          </div>
          <input type="file" id="cardCoverInput" accept="image/*" style="display:none;" onchange="handleCoverUpload(this)">

          <!-- Active Photo Preview Thumbnail -->
          <div id="cardCoverPreviewWrapper" style="display:none;margin-top:10px;background:var(--surface-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 10px;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
              <img id="cardCoverThumb" src="" style="height:36px;width:52px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
              <div>
                <div style="font-size:0.76rem;font-weight:700;color:var(--text-primary);">Photo Loaded</div>
                <div id="coverLayoutStatusText" style="font-size:0.7rem;color:#059669;font-weight:600;">Layout: Top Section</div>
              </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('cardCoverInput').click()" style="font-size:0.7rem;padding:3px 8px;">
              Replace
            </button>
          </div>
        </div>

        <!-- ============================================================
             SECTION: COVER PHOTO LAYOUT (5 OPTIONS)
             ============================================================ -->
        <div style="margin-bottom:18px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <label class="form-label" style="font-size:0.78rem;font-weight:800;color:var(--text-primary);letter-spacing:0.5px;margin:0;">
              COVER PHOTO LAYOUT
            </label>
            <span style="font-size:0.68rem;color:#FF7A00;font-weight:700;">5 Layout Options</span>
          </div>

          <div class="photo-layout-grid">
            <!-- OPTION 1: FULL CARD -->
            <div id="optLayoutFull" class="photo-layout-card" onclick="setCoverLayout('full')" title="Option 1: Full Card Background Cover">
              <div class="photo-layout-icon-box">
                <div style="position:absolute;inset:0;background:linear-gradient(135deg,#FF7A00,#E96800);opacity:0.85;"></div>
                <div style="position:absolute;inset:4px;display:flex;flex-direction:column;gap:3px;justify-content:center;">
                  <div style="height:3px;background:#fff;border-radius:2px;width:70%;"></div>
                  <div style="height:3px;background:#fff;border-radius:2px;width:90%;"></div>
                  <div style="height:8px;width:8px;background:#fff;border-radius:2px;margin:2px auto 0;"></div>
                </div>
              </div>
              <div class="photo-layout-name">1. Full Card</div>
            </div>

            <!-- OPTION 2: TOP SECTION -->
            <div id="optLayoutTop" class="photo-layout-card active" onclick="setCoverLayout('top')" title="Option 2: Top Header Section Cover">
              <div class="photo-layout-icon-box">
                <div style="position:absolute;top:0;left:0;right:0;height:45%;background:#FF7A00;"></div>
                <div style="position:absolute;bottom:4px;left:4px;right:4px;display:flex;flex-direction:column;gap:2px;">
                  <div style="height:2px;background:#aaa;border-radius:1px;"></div>
                  <div style="height:2px;background:#aaa;border-radius:1px;width:60%;"></div>
                </div>
              </div>
              <div class="photo-layout-name">2. Top Section</div>
            </div>

            <!-- OPTION 3: BOTTOM SECTION -->
            <div id="optLayoutBottom" class="photo-layout-card" onclick="setCoverLayout('bottom')" title="Option 3: Bottom Base Section Cover">
              <div class="photo-layout-icon-box">
                <div style="position:absolute;top:4px;left:4px;right:4px;display:flex;flex-direction:column;gap:2px;">
                  <div style="height:2px;background:#aaa;border-radius:1px;"></div>
                  <div style="height:2px;background:#aaa;border-radius:1px;width:70%;"></div>
                </div>
                <div style="position:absolute;bottom:0;left:0;right:0;height:45%;background:#FF7A00;"></div>
              </div>
              <div class="photo-layout-name">3. Bottom</div>
            </div>

            <!-- OPTION 4: HALF CARD -->
            <div id="optLayoutHalf" class="photo-layout-card" onclick="setCoverLayout('half')" title="Option 4: Half Card Split Layout">
              <div class="photo-layout-icon-box">
                <div style="position:absolute;top:0;left:0;bottom:0;width:50%;background:#FF7A00;"></div>
                <div style="position:absolute;top:6px;right:3px;width:40%;display:flex;flex-direction:column;gap:2px;">
                  <div style="height:2px;background:#aaa;border-radius:1px;"></div>
                  <div style="height:2px;background:#aaa;border-radius:1px;"></div>
                  <div style="height:6px;width:6px;background:#aaa;margin-top:2px;"></div>
                </div>
              </div>
              <div class="photo-layout-name">4. Half Card</div>
            </div>

            <!-- OPTION 5: CUSTOM POSITION -->
            <div id="optLayoutCustom" class="photo-layout-card" onclick="setCoverLayout('custom')" title="Option 5: Custom Floating Draggable &amp; Rotatable">
              <div class="photo-layout-icon-box">
                <div style="position:absolute;top:10px;left:7px;width:24px;height:18px;background:#FF7A00;border-radius:3px;border:1px dashed #fff;box-shadow:0 1px 4px rgba(0,0,0,0.3);"></div>
              </div>
              <div class="photo-layout-name">5. Custom</div>
            </div>
          </div>
        </div>

        <!-- ============================================================
             LAYOUT CONTEXTUAL ADJUSTMENT CONTROLS
             ============================================================ -->
        <div id="layoutControlsPanel" style="background:var(--surface-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;margin-bottom:16px;">
          
          <!-- Controls for Option 1: Full Card -->
          <div id="ctrlFullCard" style="display:none;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-primary);margin-bottom:8px;">Full Card Text Readability &amp; Overlay</div>
            <div class="form-group" style="margin-bottom:10px;">
              <label class="form-label" style="font-size:0.72rem;">Text Readability Backing</label>
              <div style="display:flex;gap:4px;">
                <button type="button" id="btnContrastGlass" class="btn btn-secondary btn-sm" onclick="setCoverTextContrast('glass')" style="flex:1;font-size:0.7rem;padding:4px 6px;">
                  ❄️ Frosted Glass
                </button>
                <button type="button" id="btnContrastShadow" class="btn btn-secondary btn-sm" onclick="setCoverTextContrast('shadow')" style="flex:1;font-size:0.7rem;padding:4px 6px;">
                  🌑 Drop Shadow
                </button>
                <button type="button" id="btnContrastNone" class="btn btn-secondary btn-sm" onclick="setCoverTextContrast('none')" style="flex:1;font-size:0.7rem;padding:4px 6px;">
                  ✨ Clear
                </button>
              </div>
            </div>
          </div>

          <!-- Controls for Option 2 & 3: Top / Bottom Section -->
          <div id="ctrlSectionHeight" style="display:block;">
            <div class="form-group" style="margin-bottom:10px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                <label class="form-label" style="font-size:0.72rem;margin:0;">Section Height</label>
                <span id="valCoverHeight" style="font-size:0.72rem;font-weight:700;color:#FF7A00;">190px</span>
              </div>
              <input type="range" id="cfgCoverHeight" min="120" max="380" step="5" value="190" class="form-control" style="padding:0;" oninput="onCoverHeightChange(this.value)">
            </div>
            <div class="form-grid-2">
              <div class="form-group" style="margin-bottom:6px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                  <label class="form-label" style="font-size:0.7rem;margin:0;">Vertical (Y)</label>
                  <span id="valCoverPositionY" style="font-size:0.7rem;font-weight:700;color:#FF7A00;">50%</span>
                </div>
                <input type="range" id="cfgCoverPositionY" min="0" max="100" step="1" value="50" class="form-control" style="padding:0;" oninput="onCoverPositionYChange(this.value)">
              </div>
              <div class="form-group" style="margin-bottom:6px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                  <label class="form-label" style="font-size:0.7rem;margin:0;">Horizontal (X)</label>
                  <span id="valCoverPositionX" style="font-size:0.7rem;font-weight:700;color:#FF7A00;">50%</span>
                </div>
                <input type="range" id="cfgCoverPositionX" min="0" max="100" step="1" value="50" class="form-control" style="padding:0;" oninput="onCoverPositionXChange(this.value)">
              </div>
            </div>
          </div>

          <!-- Controls for Option 4: Half Card -->
          <div id="ctrlHalfCard" style="display:none;">
            <div class="form-group" style="margin-bottom:10px;">
              <label class="form-label" style="font-size:0.72rem;">Photo Position Side</label>
              <div style="display:flex;gap:4px;">
                <button type="button" id="btnHalfLeft" class="btn btn-primary btn-sm" onclick="setCoverHalfSide('left')" style="flex:1;font-size:0.72rem;padding:4px 6px;">
                  ⬅️ Left Photo
                </button>
                <button type="button" id="btnHalfRight" class="btn btn-secondary btn-sm" onclick="setCoverHalfSide('right')" style="flex:1;font-size:0.72rem;padding:4px 6px;">
                  ➡️ Right Photo
                </button>
              </div>
            </div>
            <div class="form-group" style="margin-bottom:6px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                <label class="form-label" style="font-size:0.72rem;margin:0;">Split Proportion</label>
                <span id="valHalfRatio" style="font-size:0.72rem;font-weight:700;color:#FF7A00;">50% / 50%</span>
              </div>
              <input type="range" id="cfgHalfRatio" min="30" max="70" step="5" value="50" class="form-control" style="padding:0;" oninput="onCoverHalfRatioChange(this.value)">
            </div>
          </div>

          <!-- Controls for Option 5: Custom Position -->
          <div id="ctrlCustomPos" style="display:none;">
            <div style="font-size:0.74rem;font-weight:700;color:var(--text-primary);margin-bottom:6px;">Custom Floating Positioning</div>
            <div class="form-grid-2" style="margin-bottom:8px;">
              <div class="form-group" style="margin-bottom:4px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                  <label class="form-label" style="font-size:0.7rem;margin:0;">Position X</label>
                  <span id="valCustomX" style="font-size:0.7rem;font-weight:700;color:#FF7A00;">50%</span>
                </div>
                <input type="range" id="cfgCustomX" min="0" max="100" step="1" value="50" class="form-control" style="padding:0;" oninput="onCustomPosChange('x', this.value)">
              </div>
              <div class="form-group" style="margin-bottom:4px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                  <label class="form-label" style="font-size:0.7rem;margin:0;">Position Y</label>
                  <span id="valCustomY" style="font-size:0.7rem;font-weight:700;color:#FF7A00;">35%</span>
                </div>
                <input type="range" id="cfgCustomY" min="0" max="100" step="1" value="35" class="form-control" style="padding:0;" oninput="onCustomPosChange('y', this.value)">
              </div>
            </div>
            <div class="form-grid-2" style="margin-bottom:8px;">
              <div class="form-group" style="margin-bottom:4px;">
                <label class="form-label" style="font-size:0.7rem;margin-bottom:2px;">Width: <span id="valCustomW" style="font-weight:700;color:#FF7A00;">240px</span></label>
                <input type="range" id="cfgCustomW" min="80" max="420" step="5" value="240" class="form-control" style="padding:0;" oninput="onCustomSizeChange('w', this.value)">
              </div>
              <div class="form-group" style="margin-bottom:4px;">
                <label class="form-label" style="font-size:0.7rem;margin-bottom:2px;">Height: <span id="valCustomH" style="font-weight:700;color:#FF7A00;">180px</span></label>
                <input type="range" id="cfgCustomH" min="80" max="420" step="5" value="180" class="form-control" style="padding:0;" oninput="onCustomSizeChange('h', this.value)">
              </div>
            </div>
            <div class="form-group" style="margin-bottom:8px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                <label class="form-label" style="font-size:0.7rem;margin:0;">Rotation Angle</label>
                <span id="valCustomRot" style="font-size:0.7rem;font-weight:700;color:#FF7A00;">0°</span>
              </div>
              <input type="range" id="cfgCustomRot" min="-180" max="180" step="1" value="0" class="form-control" style="padding:0;" oninput="onCustomRotChange(this.value)">
            </div>
            <div style="display:flex;gap:4px;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="centerCustomCoverX()" style="flex:1;font-size:0.7rem;padding:3px 6px;">↔ Center X</button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="centerCustomCoverY()" style="flex:1;font-size:0.7rem;padding:3px 6px;">↕ Center Y</button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="resetCustomCoverBox()" style="flex:1;font-size:0.7rem;padding:3px 6px;">↺ Reset</button>
            </div>
          </div>

          <!-- Common Overlay Tint Slider -->
          <div class="form-group" style="margin-top:10px;margin-bottom:4px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
              <label class="form-label" style="font-size:0.72rem;margin:0;">Dark Tint Overlay (Readability)</label>
              <span id="valCoverOverlay" style="font-size:0.72rem;font-weight:700;color:#FF7A00;">40%</span>
            </div>
            <input type="range" id="cfgCoverOverlay" min="0" max="90" step="2" value="40" class="form-control" style="padding:0;" oninput="onCoverOverlayChange(this.value)">
          </div>
        </div>

        <!-- ============================================================
             SECTION: ADDITIONAL PHOTO CONTROLS (ZOOM, CROP, BRIGHTNESS, ETC.)
             ============================================================ -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <span style="font-size:0.76rem;font-weight:800;color:var(--text-primary);letter-spacing:0.5px;">
              ADDITIONAL PHOTO CONTROLS
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="resetCoverAdjustments()" style="font-size:0.68rem;padding:2px 8px;">
              ↺ Reset Adjustments
            </button>
          </div>

          <!-- Zoom & Crop Fit -->
          <div class="form-grid-2" style="margin-bottom:10px;">
            <div class="form-group" style="margin-bottom:4px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                <label class="form-label" style="font-size:0.72rem;margin:0;">Image Zoom</label>
                <span id="valCoverScale" style="font-size:0.72rem;font-weight:700;color:#FF7A00;">100%</span>
              </div>
              <input type="range" id="cfgCoverScale" min="50" max="250" step="5" value="100" class="form-control" style="padding:0;" oninput="onCoverScaleChange(this.value)">
            </div>
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:0.72rem;margin-bottom:2px;">Image Crop / Fit</label>
              <select id="cfgCoverCropFit" class="form-control" style="height:32px;font-size:0.72rem;" onchange="setCoverCropFit(this.value)">
                <option value="cover">Cover (Fill Area)</option>
                <option value="contain">Contain (Fit Inside)</option>
                <option value="fill">Stretch / Fill</option>
              </select>
            </div>
          </div>

          <!-- Brightness & Contrast -->
          <div class="form-grid-2" style="margin-bottom:10px;">
            <div class="form-group" style="margin-bottom:4px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                <label class="form-label" style="font-size:0.72rem;margin:0;">Brightness</label>
                <span id="valCoverBrightness" style="font-size:0.72rem;font-weight:700;color:#FF7A00;">100%</span>
              </div>
              <input type="range" id="cfgCoverBrightness" min="50" max="150" step="2" value="100" class="form-control" style="padding:0;" oninput="onCoverBrightnessChange(this.value)">
            </div>
            <div class="form-group" style="margin-bottom:4px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                <label class="form-label" style="font-size:0.72rem;margin:0;">Contrast</label>
                <span id="valCoverContrast" style="font-size:0.72rem;font-weight:700;color:#FF7A00;">100%</span>
              </div>
              <input type="range" id="cfgCoverContrast" min="50" max="150" step="2" value="100" class="form-control" style="padding:0;" oninput="onCoverContrastChange(this.value)">
            </div>
          </div>

          <!-- Opacity -->
          <div class="form-group" style="margin-bottom:4px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
              <label class="form-label" style="font-size:0.72rem;margin:0;">Image Transparency / Opacity</label>
              <span id="valCoverOpacity" style="font-size:0.72rem;font-weight:700;color:#FF7A00;">100%</span>
            </div>
            <input type="range" id="cfgCoverOpacity" min="10" max="100" step="2" value="100" class="form-control" style="padding:0;" oninput="onCoverOpacityChange(this.value)">
          </div>
        </div>

        <!-- Brand / Event Logo Upload -->
        <div style="background:var(--surface-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;">
          <label class="form-label" style="font-size:0.74rem;font-weight:700;margin-bottom:6px;">Event / Host Brand Logo</label>
          <div style="display:flex;gap:6px;align-items:center;">
            <input type="file" id="cfgLogo" accept="image/*" class="form-control" style="font-size:0.72rem;flex:1;" onchange="handleLogoUpload(this)">
            <button type="button" class="btn btn-secondary btn-sm" onclick="removeLogo()" style="font-size:0.7rem;padding:6px 10px;">Remove</button>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Center / Right Column: Live Interactive Direct-Manipulation Preview Stage -->
  <div id="previewStage" class="designer-preview-stage" onclick="onStageClick(event)">
    
    <!-- Stage Header Bar: Dimensions, Badge & Interactive Zoom Toolbar -->
    <div class="stage-header-bar">
      <div class="stage-badge-group">
        <span id="canvasDimIndicator" class="stage-dim-indicator">Canva Card Canvas</span>
        <span id="canvasModeBadge" class="stage-mode-pill">
          CANVA CARD CLONER
        </span>
        <span id="canvasCompositionBadge" style="display:none;font-size:0.68rem;font-weight:800;color:#B8955A;background:rgba(184,149,90,0.14);border:1px solid rgba(184,149,90,0.38);border-radius:99px;padding:2px 9px;letter-spacing:0.6px;text-transform:uppercase;">
          ASYMMETRICAL RIGHT
        </span>
        <span id="canvasEditHint" style="font-size:0.72rem;color:var(--text-secondary);background:var(--surface);border:1px solid var(--border);border-radius:99px;padding:3px 10px;display:inline-flex;align-items:center;gap:5px;">
          <?= svgIcon('edit', 12) ?> <span>Click text, photos, dress code or QR on card to edit</span>
        </span>
      </div>

      <!-- Zoom & View Tools -->
      <div class="canvas-zoom-toolbar">
        <button type="button" class="zoom-btn" onclick="zoomCanvas(-0.1)" title="Zoom Out">
          <?= svgIcon('minus', 13) ?>
        </button>
        <span id="zoomLevelDisplay" class="zoom-level">100%</span>
        <button type="button" class="zoom-btn" onclick="zoomCanvas(0.1)" title="Zoom In">
          <?= svgIcon('plus', 13) ?>
        </button>
        <button type="button" class="zoom-pill-btn" onclick="zoomCanvasFit()" title="Fit Card to Viewport">
          Fit
        </button>
        <button type="button" class="zoom-pill-btn" onclick="zoomCanvasReset()" title="100% Native Size">
          1:1
        </button>
      </div>
    </div>

    <!-- Intelligent Text-Fitting Warning Banner -->
    <div id="textFitWarningBanner" style="display:none;margin:0 auto 10px;max-width:480px;background:rgba(245,158,11,0.14);border:1px solid rgba(245,158,11,0.45);color:#D97706;padding:6px 12px;border-radius:99px;font-size:0.72rem;font-weight:700;align-items:center;justify-content:center;gap:6px;">
      <span>⚠️ Long text detected — Typography automatically scaled to preserve template composition.</span>
    </div>

    <!-- Alignment Guide Lines (Appear when snapping to horizontal / vertical center) -->
    <div id="guideV" class="canvas-guide-v"></div>
    <div id="guideH" class="canvas-guide-h"></div>

    <!-- The Scalable Canvas Artboard (Target for html2canvas export) -->
    <div id="artboardWrapper" class="artboard-wrapper">

      <!-- ============================================================
           VIEW A: CANVA FULL-CARD CLONED VIEW WITH DRAGGABLE QR OBJECT
           ============================================================ -->
      <div id="canvaCardArtboard" class="canva-card-artboard">
        <!-- Full Canva Design Image -->
        <img id="canvaCardImg" src="" alt="Canva Invitation Card" class="canva-card-img">

        <!-- INTERACTIVE EDITABLE QR CODE OBJECT (Drag, Resize, Rotate) -->
        <div id="canvaQrElement" class="interactive-qr-element" tabIndex="0" onpointerdown="onQrPointerDown(event)">
          
          <!-- Direct Manipulation Handles & Selection Box -->
          <div id="selectionFrame" class="selection-frame">
            <!-- Rotation Handle -->
            <div class="rotate-stem"></div>
            <div class="handle handle-rotate" data-handle="rotate" onpointerdown="onRotatePointerDown(event)" title="Drag to Rotate QR Code">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
            </div>

            <!-- Corner Resize Handles -->
            <div class="handle handle-tl" data-handle="tl" onpointerdown="onResizePointerDown(event, 'tl')" title="Resize Corner"></div>
            <div class="handle handle-tr" data-handle="tr" onpointerdown="onResizePointerDown(event, 'tr')" title="Resize Corner"></div>
            <div class="handle handle-bl" data-handle="bl" onpointerdown="onResizePointerDown(event, 'bl')" title="Resize Corner"></div>
            <div class="handle handle-br" data-handle="br" onpointerdown="onResizePointerDown(event, 'br')" title="Resize Corner"></div>

            <!-- Dimensions Tooltip -->
            <div id="dimensionTooltip" class="dimension-tooltip">140 &times; 140 px</div>
          </div>

          <!-- Inside Badge Content (QR Code + Optional Text/Tier) -->
          <div id="canvaQrBadgeContent" class="canva-qr-badge-content badge-glass">
            <div id="canvaBadgePill" class="badge badge-primary editable-canvas-text" contenteditable="true" spellcheck="false" style="font-size:0.68rem;padding:2px 8px;margin-bottom:6px;background:#FF7A00;color:#FFFFFF;border:none;" title="Click to edit badge text">
              VIP GUEST
            </div>
            
            <div id="canvaQrBoxWrapper" class="qr-canvas-box" style="padding:6px;background:#FFFFFF;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.12);line-height:0;">
              <div id="canvaQrBox"></div>
            </div>

            <div id="canvaAttendeeText" class="badge-text-primary editable-canvas-text" contenteditable="true" spellcheck="false" style="font-weight:800;font-size:0.82rem;margin-top:6px;color:#09090B;white-space:nowrap;" title="Click to edit attendee name">
              Dr. Reginald Massawe
            </div>
            <div id="canvaRefText" class="badge-text-secondary editable-canvas-text" contenteditable="true" spellcheck="false" style="font-family:monospace;font-size:0.72rem;letter-spacing:0.8px;color:#52525B;" title="Click to edit reference code">
              INV-REG-8821
            </div>
          </div>

        </div>
      </div>

      <!-- ============================================================
           VIEW B: STUDIO MULTI-ELEMENT ARTBOARD (Used when in Studio Template mode)
           ============================================================ -->
      <div id="cardArtboard" class="artboard-portrait" style="display:none;">
        <div id="cardTextureOverlay" class="texture-overlay"></div>
        <!-- Original Multi-Element Vector Artwork Layer (Flowers, Washes, Botanicals, Arches, Frames) -->
        <div id="cardDecorativeArtwork" class="card-decorative-artwork"></div>

        <!-- Independently Editable Vector Decorative Motifs Layer (Move, Resize, Rotate, Lock, Delete) -->
        <div id="cardInteractiveDecorLayer" class="card-interactive-decor-layer" style="position:absolute;inset:0;pointer-events:none;z-index:4;overflow:hidden;"></div>

        <!-- Replaceable Multi-Photo Frames Layer (Move, Resize, Rotate, Crop, Mask, Z-Index) -->
        <div id="cardPhotoLayersContainer" class="card-photo-layers-container" style="position:absolute;inset:0;pointer-events:none;z-index:5;overflow:hidden;"></div>

        <!-- Layout Option 1: Full Card Background Cover Wrap -->
        <div id="cardCoverFullWrap" class="card-cover-full-wrap" style="display:none;">
          <img id="cardCoverFullImg" src="" alt="Full Card Cover" class="card-cover-full-img">
          <div id="cardCoverFullOverlay" class="card-cover-full-overlay"></div>
        </div>

        <!-- Layout Option 5: Custom Floating Draggable & Rotatable Photo Wrap -->
        <div id="cardCoverCustomWrap" class="card-cover-custom-wrap" style="display:none;" onpointerdown="onCustomCoverPointerDown(event)">
          <div class="custom-cover-frame">
            <img id="cardCoverCustomImg" src="" alt="Custom Photo" class="card-cover-custom-img">
            <div id="cardCoverCustomOverlay" class="card-cover-overlay"></div>
            <div class="custom-cover-handle-tl" onpointerdown="onCustomCoverResizeDown(event, 'tl')"></div>
            <div class="custom-cover-handle-tr" onpointerdown="onCustomCoverResizeDown(event, 'tr')"></div>
            <div class="custom-cover-handle-bl" onpointerdown="onCustomCoverResizeDown(event, 'bl')"></div>
            <div class="custom-cover-handle-br" onpointerdown="onCustomCoverResizeDown(event, 'br')"></div>
            <div class="custom-cover-rotate-handle" onpointerdown="onCustomCoverRotateDown(event)" title="Drag to rotate photo">↻</div>
          </div>
        </div>

        <div id="artboardHeader" class="artboard-header">
          <!-- Layout Option 2: Top Card Cover Photo Layer (Draggable for repositioning) -->
          <div id="cardCoverContainer" class="card-cover-container" style="display:none;" onpointerdown="onCoverPointerDown(event, 'top')">
            <img id="cardCoverImg" src="" alt="Top Cover Photo" class="card-cover-img">
            <div id="cardCoverOverlay" class="card-cover-overlay"></div>
            <div class="cover-reposition-pill">↕ Drag to reposition photo</div>
          </div>

          <div id="artboardHeaderContent" class="artboard-header-content">
            <div id="cardLogoContainer" style="display:none;margin-bottom:8px;">
              <img id="cardLogoImg" src="" style="max-height:42px;max-width:140px;object-fit:contain;filter:drop-shadow(0 2px 6px rgba(0,0,0,0.25));">
            </div>
            <div id="cardHostNames" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;font-size:0.65rem;letter-spacing:1.8px;text-transform:uppercase;font-weight:700;margin-bottom:4px;opacity:0.86;" title="Click to edit host names">The Families of Vance &amp; Sterling</div>
            <div id="cardBadge" class="card-badge editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit badge directly">TOGETHER WITH THEIR FAMILIES</div>
            <div id="cardCoupleScript" class="card-couple-script editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit calligraphy script line directly">Amelia &amp; Sebastian</div>
            <div id="cardCelebrantName" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;font-size:1.35rem;font-weight:800;margin:2px 0;" title="Click to edit celebrant name">Lady Victoria Sterling</div>
            <h2 id="cardTitle" class="card-title-text editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit event title directly"><?= e($event['title'] ?? 'Annual Gala & Awards') ?></h2>
            <div id="cardSubtitle" class="card-subtitle-text editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit subtitle directly">Celebrating Technological Leadership</div>
            <div id="cardGuestOfHonour" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;font-size:0.74rem;font-weight:700;letter-spacing:0.8px;margin-top:4px;" title="Click to edit Guest of Honour">Guest of Honour: H.E. Amb. Dr. Amina Kenyatta</div>
          </div>
        </div>

        <div id="artboardBody" class="artboard-body">
          <div id="cardSealWrapper" style="display:flex;justify-content:center;margin-bottom:4px;">
            <span id="cardSealBadge" class="seal-pill">⭐ OFFICIAL ACCREDITED PASS</span>
          </div>
          <div id="cardQuote" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-size:0.76rem;font-style:italic;color:var(--text-secondary);margin:3px 0 5px;" title="Click to edit invitation quote directly">
            Honored to request the pleasure of your company
          </div>
          <div id="cardAttendeeLabel" class="card-attendee-label">ADMIT ATTENDEE BEARER</div>
          <h3 id="cardGuest" class="card-guest-name editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit guest name directly">Dr. Reginald Massawe</h3>

          <!-- Interactive Studio QR Code & Guest Category Block (Docked or Floating) -->
          <div id="studioQrInteractiveWrap" class="studio-qr-interactive-wrap" onpointerdown="onStudioQrPointerDown(event)" title="Drag to reposition QR code or use QR controls">
            <div id="cardQrCategoryBadge" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-size:0.6rem;font-weight:800;letter-spacing:1.6px;text-transform:uppercase;padding:2px 9px;border-radius:99px;margin-bottom:4px;display:inline-block;">
              VIP GUEST
            </div>
            <div id="studioQrFrameBox" class="card-qr-frame" style="margin:2px auto;">
              <div id="cardQrBox"></div>
            </div>
            <div id="cardRefCode" class="card-ref-code">INV-REG-8821 &bull; TOKEN-VIP-01</div>
          </div>

          <!-- Event Date, Time, Venue & Full Address Meta Box -->
          <div id="cardMetaBox" class="card-meta-box">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
              <span id="cardDate" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-weight:700;" title="Click to edit date directly">Saturday, Nov 21, 2026</span>
              <span style="font-size:0.76rem;">
                <span id="cardTime" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="color:var(--text-muted);" title="Click to edit start time">18:00 EAT</span>
                <span id="cardEndTimeWrap" style="display:none;color:var(--text-muted);"> &ndash; <span id="cardEndTime" class="editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit ending time">23:30 EAT</span></span>
              </span>
            </div>
            <div id="cardVenue" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-weight:600;margin-bottom:2px;" title="Click to edit venue directly">Serena Grand Ballroom</div>
            <div id="cardFullAddress" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;font-size:0.7rem;opacity:0.82;margin-bottom:3px;" title="Click to edit full street address">Ohio Street, Dar es Salaam, Tanzania</div>
            <div id="cardSeat" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-size:0.75rem;color:#FF7A00;font-weight:700;" title="Click to edit table/seat directly">Table 04 &bull; VIP North Gate 1</div>
          </div>

          <!-- ============================================================
               DEDICATED TEMPLATE-MATCHED DRESS CODE SECTION (ON ALL 50 TEMPLATES)
               ============================================================ -->
          <div id="cardDressCodeSection" class="card-dress-code-section" onpointerdown="onDressCodePointerDown(event)" title="Drag vertically to adjust dress code position or click text to edit">
            <div id="cardDressCodeInner" class="card-dress-code-inner">
              <span id="cardDressCodeIconBox" class="card-dress-code-icon"></span>
              <div class="card-dress-code-copy">
                <div class="card-dress-code-heading">DRESS CODE</div>
                <div id="cardDressCodeText" class="card-dress-code-value editable-canvas-text" contenteditable="true" spellcheck="false">Black Tie Optional</div>
                <div id="cardDressCodeNote" class="card-dress-code-note editable-canvas-text" contenteditable="true" spellcheck="false">Floor-length gowns &amp; dark tailored tuxedos</div>
              </div>
            </div>
          </div>

          <!-- Optional Extended Content Fields (RSVP, Contact, Website, Social, Instructions) -->
          <div id="cardOptionalFieldsBox" class="card-optional-fields-box" style="display:none;margin-top:4px;font-size:0.68rem;line-height:1.35;">
            <div id="cardRsvp" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;font-weight:700;">RSVP by October 15, 2026</div>
            <div id="cardContactInfo" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;opacity:0.85;">Concierge: +255 754 000 111</div>
            <div id="cardAdditionalInstructions" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;font-style:italic;opacity:0.85;">Valet parking at North Gate</div>
            <div id="cardEventWebsite" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;font-weight:600;">www.leulaevents.com</div>
            <div id="cardSocialMedia" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="display:none;opacity:0.85;">#LeulaLuxuryEvents</div>
          </div>

          <div id="cardFooter" class="card-footer-note editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit footer note directly">
            Black Tie Required &bull; Non-Transferable Digital QR Ticket
          </div>

          <!-- Layout Option 3: Bottom Base Cover Photo Wrap -->
          <div id="cardCoverBottomWrap" class="card-cover-bottom-wrap" style="display:none;" onpointerdown="onCoverPointerDown(event, 'bottom')">
            <img id="cardCoverBottomImg" src="" alt="Bottom Cover Photo" class="card-cover-bottom-img">
            <div id="cardCoverBottomOverlay" class="card-cover-overlay"></div>
            <div class="cover-reposition-pill">↕ Drag to reposition photo</div>
          </div>
        </div>
      </div>

    </div>

  </div>

</div>

<!-- Mobile Quick Floating Bottom Bar (Visible on mobile/tablet <= 768px) -->
<div class="designer-mobile-bar">
  <button type="button" id="mobNavControls" class="mob-nav-btn active" onclick="switchWorkView('controls')">
    <?= svgIcon('sliders', 15) ?> <span>Tools</span>
  </button>
  <button type="button" id="mobNavPreview" class="mob-nav-btn" onclick="switchWorkView('preview')">
    <?= svgIcon('eye', 15) ?> <span>Preview</span>
  </button>
  <button type="button" class="mob-nav-btn mob-save-btn" onclick="submitSaveDesign()">
    <?= svgIcon('check', 15) ?> <span>Save Card</span>
  </button>
</div>

<!-- Hidden Persistence Form -->
<form id="saveDesignForm" method="POST" action="designer.php?event_id=<?= $selectedEventId ?>">
  <input type="hidden" name="action" value="save_design">
  <input type="hidden" name="design_name" id="postDesignName" value="Canva Cloned Pass Design">
  <input type="hidden" name="orientation" id="postOrientation" value="portrait">
  <input type="hidden" name="background_color" id="postBgColor" value="#FFFFFF">
  <input type="hidden" name="canvas_data" id="postCanvasData" value="{}">
</form>

<!-- Template Full Preview Modal -->
<div id="templatePreviewModal" class="tpl-modal-backdrop" onclick="if(event.target===this) closeTemplatePreviewModal()">
  <div class="tpl-modal-dialog">
    <button type="button" class="tpl-modal-close" onclick="closeTemplatePreviewModal()" title="Close Preview">✕</button>
    <div id="modalPreviewContent"></div>
  </div>
</div>

<style>
/* ============================================================
   50 Premium Templates Marketplace UI Styles
   ============================================================ */
.tpl-filter-bar {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  padding-bottom: 8px;
  margin-bottom: 12px;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,122,0,0.4) transparent;
}
.tpl-filter-bar::-webkit-scrollbar {
  height: 4px;
}
.tpl-filter-bar::-webkit-scrollbar-thumb {
  background: rgba(255,122,0,0.4);
  border-radius: 4px;
}
.tpl-filter-pill {
  flex-shrink: 0;
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  font-size: 0.72rem;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 99px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}
.tpl-filter-pill:hover {
  border-color: #FF7A00;
  color: #FF7A00;
  background: rgba(255,122,0,0.06);
}
.tpl-filter-pill.active {
  background: linear-gradient(135deg, #FF7A00, #E96800);
  border-color: #FF7A00;
  color: #FFFFFF;
  box-shadow: 0 2px 8px rgba(255,122,0,0.35);
}
.tpl-marketplace-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 12px;
  max-height: 580px;
  overflow-y: auto;
  padding-right: 4px;
  scrollbar-width: thin;
}
.tpl-card-item {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
  display: flex;
  flex-direction: column;
  position: relative;
}
.tpl-card-item:hover {
  border-color: #FF7A00;
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}
.tpl-card-item.active {
  border-color: #FF7A00;
  box-shadow: 0 0 0 2px rgba(255,122,0,0.4);
}
.tpl-card-swatch {
  height: 95px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
}
.tpl-card-category-tag {
  align-self: flex-start;
  font-size: 0.62rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 2px 6px;
  border-radius: 4px;
  background: rgba(0,0,0,0.3);
  backdrop-filter: blur(4px);
  color: #FFFFFF;
}
.tpl-fav-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(0,0,0,0.3);
  backdrop-filter: blur(4px);
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #FFFFFF;
  font-size: 0.8rem;
  cursor: pointer;
  transition: transform 0.2s ease;
}
.tpl-fav-btn:hover {
  transform: scale(1.2);
}
.tpl-fav-btn.favorited {
  color: #FACC15;
}
.tpl-swatch-badge {
  font-size: 0.58rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  opacity: 0.85;
}
.tpl-swatch-title {
  font-size: 0.88rem;
  font-weight: 800;
  line-height: 1.15;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.tpl-card-footer {
  padding: 8px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  background: var(--surface);
  border-top: 1px solid var(--border);
}
.tpl-card-info {
  flex: 1;
  min-width: 0;
}
.tpl-card-name {
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.tpl-card-tagline {
  font-size: 0.65rem;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.tpl-card-actions {
  display: flex;
  gap: 4px;
}
.tpl-use-btn {
  padding: 3px 8px;
  font-size: 0.68rem;
  font-weight: 700;
}
.tpl-preview-btn {
  padding: 3px 6px;
  font-size: 0.7rem;
}

/* Template Preview Modal */
.tpl-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.65);
  backdrop-filter: blur(8px);
  z-index: 9999;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  animation: fadeIn 0.2s ease;
}
.tpl-modal-dialog {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg, 16px);
  max-width: 440px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  padding: 24px;
  position: relative;
  box-shadow: 0 25px 60px rgba(0,0,0,0.35);
}
.tpl-modal-close {
  position: absolute;
  top: 14px;
  right: 14px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}
.tpl-modal-close:hover {
  background: var(--danger, #EF4444);
  color: #FFFFFF;
  border-color: var(--danger, #EF4444);
}

/* ============================================================
   Cover Photo Layout Options (Full, Top, Bottom, Half, Custom)
   ============================================================ */
.photo-layout-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 6px;
  margin-bottom: 12px;
}
@media (max-width: 600px) {
  .photo-layout-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
.photo-layout-card {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm, 8px);
  padding: 8px 4px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
}
.photo-layout-card:hover {
  border-color: #FF7A00;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255,122,0,0.18);
}
.photo-layout-card.active {
  border-color: #FF7A00;
  background: rgba(255,122,0,0.08);
  box-shadow: 0 0 0 2px rgba(255,122,0,0.35);
}
.photo-layout-icon-box {
  width: 36px;
  height: 48px;
  border: 1.5px solid var(--border);
  border-radius: 4px;
  position: relative;
  overflow: hidden;
  background: var(--surface-2);
}
.photo-layout-card.active .photo-layout-icon-box {
  border-color: #FF7A00;
}
.photo-layout-name {
  font-size: 0.67rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}

/* Original Multi-Element Vector Artwork Layer (LuxeArt SVG) */
.card-decorative-artwork {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 2;
  overflow: hidden;
  border-radius: inherit;
}
.card-decorative-artwork svg {
  width: 100%;
  height: 100%;
  display: block;
}
.card-couple-script {
  font-family: 'Great Vibes', cursive;
  font-size: 2.15rem;
  line-height: 1.15;
  margin: 4px 0 6px;
  font-weight: 400;
}
/* Unified Luxury Stationery Sheet Architecture */
.artboard-unified .artboard-header {
  background: transparent !important;
  padding-top: 38px;
  padding-bottom: 10px;
}
.artboard-unified .artboard-body {
  background: transparent !important;
  padding-top: 6px;
  padding-bottom: 34px;
}
.artboard-unified .artboard-header-content,
.artboard-unified .artboard-body {
  position: relative;
  z-index: 4;
}

/* Layout Option 1: Full Card Cover */
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
#cardArtboard.cover-layout-full,
#cardArtboard.card-cover-full-active {
  background: transparent !important;
}
.cover-layout-full .artboard-header,
.card-cover-full-active .artboard-header {
  background: transparent !important;
  background-image: none !important;
  box-shadow: none !important;
}
.cover-layout-full .artboard-body,
.card-cover-full-active .artboard-body {
  background: transparent !important;
  background-image: none !important;
}
.cover-layout-full .card-meta-box {
  background: rgba(0, 0, 0, 0.42) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
  color: #FFFFFF !important;
}
.cover-layout-full .card-meta-box span,
.cover-layout-full .card-meta-box div {
  color: #FFFFFF !important;
}
.cover-layout-full .card-guest-name,
.cover-layout-full .card-attendee-label,
.cover-layout-full .card-quote,
.cover-layout-full .card-footer-note,
.cover-layout-full .card-ref-code {
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
  background: rgba(0,0,0,0.4);
}
.full-cover-glass .artboard-header-content {
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-radius: 20px;
  padding: 16px 14px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}
.full-cover-glass .card-meta-box {
  background: rgba(0, 0, 0, 0.5) !important;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid rgba(255, 255, 255, 0.15) !important;
  color: #FFFFFF !important;
}
.full-cover-glass .card-meta-box span,
.full-cover-glass .card-meta-box div {
  color: #FFFFFF !important;
}
.full-cover-glass .card-guest-name,
.full-cover-glass .card-quote,
.full-cover-glass .card-attendee-label,
.full-cover-glass .card-footer-note,
.full-cover-glass .card-ref-code {
  color: #FFFFFF !important;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.85);
}
.full-cover-shadow .card-guest-name,
.full-cover-shadow .card-title-text,
.full-cover-shadow .card-subtitle-text,
.full-cover-shadow .card-quote,
.full-cover-shadow .card-footer-note {
  text-shadow: 0 2px 8px rgba(0,0,0,0.9), 0 0 20px rgba(0,0,0,0.8);
}

/* Layout Option 3: Bottom Cover Wrap */
.card-cover-bottom-wrap {
  position: relative;
  width: 100%;
  overflow: hidden;
  margin-top: 12px;
  border-radius: 16px;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.15);
  cursor: grab;
}
.card-cover-bottom-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Layout Option 4: Half Card Split */
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

/* Layout Option 5: Custom Position Draggable Floating Photo */
.card-cover-custom-wrap {
  position: absolute;
  z-index: 10;
  cursor: move;
  user-select: none;
  touch-action: none;
}
.custom-cover-frame {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 14px;
  overflow: visible;
  border: 2px dashed #FF7A00;
  box-shadow: 0 8px 30px rgba(0,0,0,0.25);
}
.custom-cover-frame:hover {
  border-style: solid;
}
.card-cover-custom-img {
  width: 100%;
  height: 100%;
  border-radius: 12px;
  display: block;
  pointer-events: none;
}
.custom-cover-handle-tl, .custom-cover-handle-tr,
.custom-cover-handle-bl, .custom-cover-handle-br {
  position: absolute;
  width: 12px;
  height: 12px;
  background: #FFFFFF;
  border: 2px solid #FF7A00;
  border-radius: 3px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  z-index: 2;
}
.custom-cover-handle-tl { top: -6px; left: -6px; cursor: nwse-resize; }
.custom-cover-handle-tr { top: -6px; right: -6px; cursor: nesw-resize; }
.custom-cover-handle-bl { bottom: -6px; left: -6px; cursor: nesw-resize; }
.custom-cover-handle-br { bottom: -6px; right: -6px; cursor: nwse-resize; }
.custom-cover-rotate-handle {
  position: absolute;
  top: -28px;
  left: 50%;
  transform: translateX(-50%);
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #FF7A00;
  color: #FFFFFF;
  font-size: 13px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  box-shadow: 0 2px 8px rgba(255,122,0,0.4);
  z-index: 2;
}

/* Active Template Banner */
.active-template-bar {
  background: linear-gradient(135deg, rgba(255,122,0,0.12), rgba(233,104,0,0.06));
  border: 1px solid rgba(255,122,0,0.3);
  border-radius: var(--radius-sm, 8px);
  padding: 8px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* ============================================================
   Canva Direct Manipulation & QR Element Styles
   ============================================================ */
.template-card {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
}
.template-card:hover {
  border-color: #FF7A00;
  transform: translateY(-2px);
  box-shadow: 0 4px 14px rgba(255,122,0,0.2);
}
.template-card.active {
  border-color: #FF7A00;
  box-shadow: 0 0 0 2px rgba(255,122,0,0.35);
}
.template-name {
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-top: 5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Canva Poster Container */
.canva-card-artboard {
  position: relative;
  width: 420px;
  max-width: 100%;
  border-radius: 26px;
  overflow: hidden;
  box-shadow: 0 25px 60px rgba(0,0,0,0.25);
  background: #FFFFFF;
  transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
  user-select: none;
}
.canva-card-artboard.landscape {
  width: 620px;
}
.canva-card-img {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 26px;
  pointer-events: none;
}

/* Dynamic Alignment Guides */
.canvas-guide-v {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 50%;
  width: 1px;
  border-left: 1.5px dashed #FF7A00;
  pointer-events: none;
  z-index: 50;
  display: none;
}
.canvas-guide-h {
  position: absolute;
  left: 0;
  right: 0;
  top: 50%;
  height: 1px;
  border-top: 1.5px dashed #FF7A00;
  pointer-events: none;
  z-index: 50;
  display: none;
}

/* Interactive Direct-Manipulation QR Element */
.interactive-qr-element {
  position: absolute;
  z-index: 20;
  transform: translate(-50%, -50%);
  cursor: move;
  touch-action: none;
  user-select: none;
  outline: none;
}

.interactive-qr-element.is-locked {
  cursor: not-allowed !important;
}

/* Selection Frame (Outline & Handles) */
.selection-frame {
  position: absolute;
  top: -6px;
  left: -6px;
  right: -6px;
  bottom: -6px;
  border: 2px solid #FF7A00;
  border-radius: 12px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.15s ease;
  z-index: 25;
}

.interactive-qr-element.is-selected .selection-frame {
  opacity: 1;
}

/* Resize & Rotate Handles */
.handle {
  position: absolute;
  width: 12px;
  height: 12px;
  background: #FFFFFF;
  border: 2px solid #FF7A00;
  border-radius: 50%;
  pointer-events: auto;
  box-shadow: 0 2px 6px rgba(0,0,0,0.25);
  transition: transform 0.1s ease;
}
.handle:hover {
  transform: scale(1.3);
  background: #FF7A00;
}

.handle-tl { top: -6px; left: -6px; cursor: nwse-resize; }
.handle-tr { top: -6px; right: -6px; cursor: nesw-resize; }
.handle-bl { bottom: -6px; left: -6px; cursor: nesw-resize; }
.handle-br { bottom: -6px; right: -6px; cursor: nwse-resize; }

/* Rotation Stem & Handle */
.rotate-stem {
  position: absolute;
  top: -26px;
  left: 50%;
  width: 1.5px;
  height: 20px;
  background: #FF7A00;
  transform: translateX(-50%);
}
.handle-rotate {
  top: -38px;
  left: 50%;
  width: 22px;
  height: 22px;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  background: #FFFFFF;
  color: #FF7A00;
}
.handle-rotate:active {
  cursor: grabbing;
}

/* Dimension Tooltip */
.dimension-tooltip {
  position: absolute;
  bottom: -30px;
  left: 50%;
  transform: translateX(-50%);
  background: #09090B;
  color: #FFFFFF;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 0.68rem;
  font-family: monospace;
  font-weight: 700;
  white-space: nowrap;
  pointer-events: none;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

/* QR Badge Content Inner */
.canva-qr-badge-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

/* Badge Aesthetics */
.badge-glass {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1.5px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 12px 36px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 122, 0, 0.2);
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

/* Studio Template Defaults */
.artboard-portrait {
  width: 390px;
  background: #FFFFFF;
  border-radius: 28px;
  border: 1.5px solid rgba(0,0,0,0.08);
  box-shadow: 0 25px 60px rgba(0,0,0,0.2);
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
  position: relative;
}
.artboard-landscape {
  width: 620px;
  background: #FFFFFF;
  border-radius: 28px;
  border: 1.5px solid rgba(0,0,0,0.08);
  box-shadow: 0 25px 60px rgba(0,0,0,0.2);
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
  position: relative;
  display: grid;
  grid-template-columns: 1.15fr 1fr;
}
.artboard-landscape .artboard-header {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 36px 30px;
}
.artboard-landscape .artboard-body {
  padding: 28px 24px;
}
.artboard-header {
  background: linear-gradient(135deg, #FF7A00, #E96800);
  color: #FFFFFF;
  padding: 0;
  text-align: center;
  position: relative;
  overflow: hidden;
  z-index: 2;
  transition: min-height 0.25s ease;
}
.artboard-header-content {
  position: relative;
  z-index: 2;
  padding: 30px 24px;
}
/* Top Card Cover Photo Layer */
.card-cover-container {
  position: absolute;
  inset: 0;
  overflow: hidden;
  z-index: 1;
  cursor: grab;
  user-select: none;
}
.card-cover-container:active {
  cursor: grabbing;
}
.card-cover-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  pointer-events: none;
  user-select: none;
  -webkit-user-drag: none;
  transition: transform 0.08s ease;
}
.card-cover-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: rgba(0,0,0,0.4);
}
.cover-reposition-pill {
  position: absolute;
  bottom: 8px;
  right: 8px;
  background: rgba(0,0,0,0.7);
  color: #FFFFFF;
  font-size: 0.68rem;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 99px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.2s ease;
  backdrop-filter: blur(4px);
  z-index: 3;
}
.artboard-header:hover .cover-reposition-pill {
  opacity: 0.95;
}

/* In-Canvas WYSIWYG Editable Text */
.editable-canvas-text {
  cursor: text !important;
  outline: 1.5px dashed transparent;
  border-radius: 4px;
  transition: outline 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
  user-select: text;
}
.editable-canvas-text:hover {
  outline: 1.5px dashed #FF7A00 !important;
  background: rgba(255, 122, 0, 0.08) !important;
}
.editable-canvas-text:focus {
  outline: 2px solid #FF7A00 !important;
  background: rgba(255, 122, 0, 0.15) !important;
  box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.25) !important;
}
.card-badge {
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
  border: 1px solid rgba(255,255,255,0.3);
}
.card-title-text {
  font-size: 1.45rem;
  font-weight: 800;
  line-height: 1.2;
  margin: 0 0 6px;
  color: #FFFFFF;
  transition: font-size 0.2s;
}
.card-subtitle-text {
  font-size: 0.8rem;
  opacity: 0.95;
  line-height: 1.4;
}
.artboard-body {
  padding: 24px 24px;
  text-align: center;
  position: relative;
  z-index: 2;
}
.card-attendee-label {
  font-size: 0.68rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  color: var(--text-muted);
  margin-bottom: 2px;
}
.card-guest-name {
  font-size: 1.45rem;
  font-weight: 800;
  color: #09090B;
  margin: 2px 0 12px;
  line-height: 1.2;
  transition: font-size 0.2s;
}
.seal-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  background: rgba(255,122,0,0.1);
  color: #E96800;
  border: 1px solid rgba(255,122,0,0.25);
  border-radius: 99px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.card-qr-frame {
  display: inline-block;
  padding: 10px;
  background: #FFFFFF;
  border-radius: 18px;
  border: 1.5px solid rgba(0,0,0,0.08);
  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
  margin-bottom: 10px;
}
.card-ref-code {
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-secondary);
  letter-spacing: 1.2px;
  margin-bottom: 12px;
}
.card-meta-box {
  background: #F9FAFB;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 12px;
  padding: 12px 16px;
  text-align: left;
  font-size: 0.82rem;
  margin-bottom: 12px;
}
.card-footer-note {
  font-size: 0.72rem;
  color: var(--text-muted);
  line-height: 1.4;
  border-top: 1px dashed rgba(0,0,0,0.12);
  padding-top: 10px;
}
.frame-gold-single { border: 2px solid #D4AF37 !important; box-shadow: 0 25px 60px rgba(212,175,55,0.22) !important; }
.frame-gold-double { border: 3px double #D4AF37 !important; outline: 1.5px solid #D4AF37; outline-offset: -6px; }
.frame-glass-glow { border: 2px solid #FF7A00 !important; box-shadow: 0 0 35px rgba(255,122,0,0.35) !important; }
.frame-deco-corners { border: 2px solid #C28945 !important; }
.texture-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; background-size: cover; background-position: center; }
.texture-dots { background-image: radial-gradient(rgba(0,0,0,0.07) 1.5px, transparent 1.5px); background-size: 16px 16px; }
.texture-glow { background-image: radial-gradient(circle at 50% 50%, rgba(255,122,0,0.12) 0%, transparent 70%); }
.texture-marble { background-image: radial-gradient(circle at 10% 20%, rgba(212,175,55,0.08) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(255,122,0,0.08) 0%, transparent 40%); }
.texture-stardust { background-image: radial-gradient(circle at 30% 30%, rgba(255,215,0,0.15) 1px, transparent 1px), radial-gradient(circle at 70% 60%, rgba(255,215,0,0.12) 1.5px, transparent 1.5px); background-size: 32px 32px; }

/* ============================================================
   RESPONSIVE DESIGNER ARCHITECTURE & LIQUID GLASS UI
   ============================================================ */

/* Topbar Card */
.designer-topbar-card {
  padding: 14px 20px;
  margin-bottom: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.designer-topbar-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 12px;
}

.designer-branding {
  display: flex;
  align-items: center;
  gap: 12px;
}

.designer-brand-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(255, 122, 0, 0.15), rgba(255, 122, 0, 0.05));
  border: 1.5px solid rgba(255, 122, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #FF7A00;
  flex-shrink: 0;
}

/* View Mode Switcher (Controls vs Live Canvas on mobile/tablet) */
.designer-view-switcher {
  display: none; /* hidden on large desktop */
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 99px;
  padding: 3px;
  gap: 4px;
}

.view-switch-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  border-radius: 99px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.view-switch-btn.active {
  background: #FF7A00;
  color: #FFFFFF;
  box-shadow: 0 2px 8px rgba(255, 122, 0, 0.35);
}

/* Topbar Controls Row */
.designer-topbar-controls {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding-top: 10px;
  border-top: 1px solid var(--border);
}

.event-select-wrap {
  flex: 1 1 200px;
  min-width: 180px;
}

/* Canva Segmented Control */
.canva-segmented-control {
  display: inline-flex;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 99px;
  padding: 3px;
  gap: 3px;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.04);
}

.segment-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 13px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  border-radius: 99px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
  white-space: nowrap;
  touch-action: manipulation;
}

.segment-btn:hover:not(.active) {
  color: var(--text-primary);
  background: rgba(0, 0, 0, 0.04);
}

.segment-btn.active {
  background: #FFFFFF;
  color: #FF7A00;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(255, 122, 0, 0.2);
}

/* Action Buttons Group */
.designer-action-buttons {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
  flex-wrap: wrap;
}

.designer-action-buttons .btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  white-space: nowrap;
}

.save-card-btn {
  background: linear-gradient(135deg, #FF7A00, #E96800) !important;
  color: #FFFFFF !important;
  border: none !important;
  box-shadow: 0 4px 14px rgba(255, 122, 0, 0.35);
}

.save-card-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(255, 122, 0, 0.45);
}

/* Workspace 2-Column Grid Architecture */
.designer-workspace {
  display: grid;
  grid-template-columns: 430px 1fr;
  gap: 24px;
  align-items: start;
}

.designer-toolset-card {
  position: sticky;
  top: 84px;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
  overflow-x: hidden;
  padding: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
  scrollbar-width: thin;
}

.designer-tabs-bar {
  display: flex;
  overflow-x: auto;
  gap: 4px;
  padding-bottom: 8px;
  margin-bottom: 14px;
  border-bottom: 1.5px solid var(--border);
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}
.designer-tabs-bar::-webkit-scrollbar {
  display: none;
}
.designer-tabs-bar .tab-btn {
  flex-shrink: 0;
  border-radius: 99px;
  white-space: nowrap;
}

/* Interactive Preview Stage */
.designer-preview-stage {
  position: relative;
  background: radial-gradient(circle at 50% 30%, #FDFEFE 0%, #F1F4F8 100%);
  border: 1.5px dashed var(--border);
  border-radius: var(--radius-lg);
  padding: 24px 20px 40px;
  min-height: 680px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  overflow: hidden;
  box-shadow: inset 0 2px 8px rgba(0,0,0,0.03);
}

/* Stage Header Bar & Zoom Toolbar */
.stage-header-bar {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  z-index: 10;
}

.stage-badge-group {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.stage-dim-indicator {
  font-family: monospace;
  font-size: 0.74rem;
  color: var(--text-muted);
  font-weight: 600;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 3px 8px;
}

.stage-mode-pill {
  font-size: 0.68rem;
  font-weight: 800;
  color: #FF7A00;
  letter-spacing: 0.8px;
  background: rgba(255, 122, 0, 0.1);
  border: 1px solid rgba(255, 122, 0, 0.25);
  border-radius: 99px;
  padding: 3px 10px;
  text-transform: uppercase;
}

.canvas-zoom-toolbar {
  display: inline-flex;
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 99px;
  padding: 2px 4px;
  gap: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.zoom-btn {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: transparent;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  touch-action: manipulation;
}

.zoom-btn:hover {
  background: rgba(0, 0, 0, 0.06);
  color: #FF7A00;
}

.zoom-level {
  font-family: monospace;
  font-size: 0.76rem;
  font-weight: 700;
  padding: 0 6px;
  min-width: 44px;
  text-align: center;
  color: var(--text-secondary);
}

.zoom-pill-btn {
  padding: 4px 10px;
  border: none;
  background: transparent;
  border-radius: 99px;
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.15s ease;
  touch-action: manipulation;
}

.zoom-pill-btn:hover {
  background: rgba(255, 122, 0, 0.1);
  color: #FF7A00;
}

/* Scalable Artboard Wrapper with Smooth Zoom */
.artboard-wrapper {
  transform-origin: top center;
  transition: transform 0.2s cubic-bezier(0.2, 0, 0.2, 1);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  width: 100%;
}

/* Touch Expansion for QR Selection Handles (Minimum 36px touch hit area) */
.handle::after {
  content: '';
  position: absolute;
  top: -14px;
  left: -14px;
  right: -14px;
  bottom: -14px;
  border-radius: 50%;
  cursor: inherit;
}

.handle-rotate::after {
  content: '';
  position: absolute;
  top: -10px;
  left: -10px;
  right: -10px;
  bottom: -10px;
  border-radius: 50%;
  cursor: inherit;
}

/* Directional Nudge Pad Touch Targets */
.nudge-pad-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-bottom: 14px;
  background: var(--surface-3);
  padding: 8px;
  border-radius: var(--radius-sm);
  flex-wrap: wrap;
}

.nudge-btn {
  min-width: 36px;
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  padding: 4px 10px;
  border-radius: 8px;
  touch-action: manipulation;
}

/* Mobile Quick Floating Bottom Bar */
.designer-mobile-bar {
  display: none; /* hidden on desktop */
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-top: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
  padding: 8px 16px;
  z-index: 1000;
  justify-content: space-around;
  align-items: center;
  gap: 12px;
}

.mob-nav-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  background: transparent;
  border: none;
  padding: 6px 8px;
  border-radius: 12px;
  color: var(--text-secondary);
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
  touch-action: manipulation;
}

.mob-nav-btn.active {
  color: #FF7A00;
  background: rgba(255, 122, 0, 0.08);
}

.mob-nav-btn svg {
  flex-shrink: 0;
}

.mob-save-btn {
  flex: 1.3;
  background: linear-gradient(135deg, #FF7A00, #E96800) !important;
  color: #FFFFFF !important;
  box-shadow: 0 4px 12px rgba(255, 122, 0, 0.35);
  flex-direction: row;
  gap: 6px;
  padding: 10px 12px;
}

/* ============================================================
   RESPONSIVE BREAKPOINTS (Tablets, Mobiles, Ultra-small screens)
   ============================================================ */

/* Tablet & Smaller (<= 992px) */
@media (max-width: 992px) {
  .designer-view-switcher {
    display: inline-flex;
  }

  .designer-workspace {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .designer-toolset-card {
    position: static;
    max-height: none;
    overflow-y: visible;
  }

  /* View Switcher State: Controls vs Preview */
  .designer-workspace.show-controls #designerSidebar {
    display: block;
  }
  .designer-workspace.show-controls #previewStage {
    display: none;
  }

  .designer-workspace.show-preview #designerSidebar {
    display: none;
  }
  .designer-workspace.show-preview #previewStage {
    display: flex;
  }

  .designer-topbar-controls {
    justify-content: space-between;
  }

  .designer-action-buttons {
    margin-left: 0;
    width: 100%;
    justify-content: flex-end;
  }
}

/* Mobile Devices (<= 768px) */
@media (max-width: 768px) {
  .designer-topbar-card {
    padding: 12px 14px;
    margin-bottom: 14px;
  }

  .designer-topbar-main {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .designer-branding {
    justify-content: flex-start;
  }

  .designer-view-switcher {
    display: flex;
    width: 100%;
    justify-content: center;
  }

  .view-switch-btn {
    flex: 1;
    justify-content: center;
    padding: 8px 12px;
  }

  .designer-topbar-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .event-select-wrap {
    width: 100%;
  }

  .canva-segmented-control {
    width: 100%;
    display: flex;
  }

  .canva-segmented-control .segment-btn {
    flex: 1;
    justify-content: center;
    padding: 8px 10px;
    font-size: 0.78rem;
  }

  .designer-action-buttons {
    width: 100%;
    display: flex;
    justify-content: space-between;
  }

  .designer-action-buttons .btn {
    flex: 1;
    justify-content: center;
    padding: 8px 6px;
    font-size: 0.76rem;
  }

  .designer-preview-stage {
    padding: 16px 10px 30px;
    min-height: 520px;
  }

  .stage-header-bar {
    flex-direction: column;
    align-items: center;
    gap: 10px;
  }

  /* Reveal mobile floating bottom bar */
  .designer-mobile-bar {
    display: flex;
  }

  /* Padding at workspace bottom to avoid content obscured by mobile bar */
  .designer-workspace {
    padding-bottom: 74px;
  }
}

/* Compact Mobile (<= 480px) */
@media (max-width: 480px) {
  .designer-action-buttons .btn span {
    display: none; /* Show only icons on very small screens to fit cleanly */
  }
  .designer-action-buttons .save-card-btn span {
    display: inline; /* Keep Save text visible */
  }
  .designer-action-buttons .btn {
    padding: 8px 10px;
  }
}

/* ============================================================
   DEDICATED TEMPLATE-MATCHED DRESS CODE STYLES (8 ARCHETYPES)
   ============================================================ */
.card-dress-code-section {
  position: relative;
  z-index: 9;
  margin: 6px auto 4px;
  width: 88%;
  cursor: grab;
  transition: box-shadow 0.15s ease, border-color 0.15s ease;
  user-select: none;
}
.card-dress-code-section:hover {
  outline: 1px dashed rgba(255, 122, 0, 0.55);
  outline-offset: 2px;
}
.card-dress-code-inner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 8px;
  transition: all 0.2s ease;
}
.card-dress-code-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.card-dress-code-copy {
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.card-dress-code-heading {
  font-size: 0.54rem;
  font-weight: 800;
  letter-spacing: 2.2px;
  text-transform: uppercase;
  opacity: 0.78;
  margin-bottom: 1px;
}
.card-dress-code-value {
  font-size: 0.76rem;
  font-weight: 700;
  line-height: 1.2;
}
.card-dress-code-note {
  font-size: 0.63rem;
  opacity: 0.82;
  font-style: italic;
  line-height: 1.2;
  margin-top: 1px;
}
.dc-style-luxury_serif .card-dress-code-inner {
  border-top: 1px solid currentColor;
  border-bottom: 1px solid currentColor;
  border-radius: 0;
  background: transparent;
}
.dc-style-floral_wreath .card-dress-code-inner {
  border: 1px dashed currentColor;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.35);
  padding: 6px 16px;
}
.dc-style-african_ornament .card-dress-code-inner {
  border: 1.5px solid currentColor;
  border-left-width: 5px;
  border-right-width: 5px;
  border-radius: 4px;
  background: rgba(197, 160, 89, 0.08);
}
.dc-style-black_tie_gold .card-dress-code-inner {
  background: rgba(15, 15, 18, 0.88);
  border: 1px solid #D4AF37;
  color: #F6D580 !important;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}
.dc-style-corporate_minimal .card-dress-code-inner {
  border-left: 3px solid currentColor;
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.035);
  justify-content: flex-start;
  text-align: left;
}
.dc-style-birthday_festive .card-dress-code-inner {
  border: 1.5px solid currentColor;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.14), rgba(255, 255, 255, 0.2));
}
.dc-style-arch_pill .card-dress-code-inner {
  border: 1px solid currentColor;
  border-radius: 18px 18px 4px 4px;
  background: rgba(255, 255, 255, 0.25);
}
.dc-style-monogram_scroll .card-dress-code-inner {
  border-top: 2px double currentColor;
  border-bottom: 2px double currentColor;
  border-radius: 2px;
}

/* ============================================================
   EDITABLE MULTI-PHOTO FRAMES & 8 MASK SHAPES
   ============================================================ */
.studio-photo-layer {
  position: absolute;
  pointer-events: auto;
  cursor: grab;
  user-select: none;
  transform-origin: center center;
  transition: box-shadow 0.15s ease;
}
.studio-photo-layer.is-selected {
  outline: 2px solid #FF7A00;
  outline-offset: 3px;
  z-index: 30 !important;
}
.studio-photo-layer.is-locked {
  cursor: default;
}
.studio-photo-mask {
  width: 100%;
  height: 100%;
  overflow: hidden;
  position: relative;
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.16);
  background: rgba(255, 255, 255, 0.15);
}
.studio-photo-mask.mask-arch {
  border-radius: 999px 999px 12px 12px;
}
.studio-photo-mask.mask-circle {
  border-radius: 50%;
}
.studio-photo-mask.mask-oval {
  border-radius: 50% / 50%;
}
.studio-photo-mask.mask-rounded {
  border-radius: 14px;
}
.studio-photo-mask.mask-rect {
  border-radius: 2px;
}
.studio-photo-mask.mask-octagon {
  clip-path: polygon(28% 0%, 72% 0%, 100% 28%, 100% 72%, 72% 100%, 28% 100%, 0% 72%, 0% 28%);
}
.studio-photo-mask.mask-diamond {
  clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
}
.studio-photo-mask.mask-shield {
  clip-path: polygon(0% 0%, 100% 0%, 100% 65%, 50% 100%, 0% 65%);
}
.studio-photo-layer-handle-br,
.studio-decor-handle-br {
  position: absolute;
  right: -7px;
  bottom: -7px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #FF7A00;
  border: 2px solid #FFFFFF;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  cursor: nwse-resize;
  display: none;
  z-index: 35;
}
.studio-photo-layer-handle-rot,
.studio-decor-handle-rot {
  position: absolute;
  top: -22px;
  left: 50%;
  transform: translateX(-50%);
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #09090B;
  color: #FFFFFF;
  font-size: 10px;
  display: none;
  align-items: center;
  justify-content: center;
  cursor: grab;
  border: 1.5px solid #FFFFFF;
  z-index: 35;
}
.studio-photo-layer.is-selected:not(.is-locked) .studio-photo-layer-handle-br,
.studio-decor-motif.is-selected:not(.is-locked) .studio-decor-handle-br {
  display: block;
}
.studio-photo-layer.is-selected:not(.is-locked) .studio-photo-layer-handle-rot,
.studio-decor-motif.is-selected:not(.is-locked) .studio-decor-handle-rot {
  display: flex;
}

/* ============================================================
   INDEPENDENTLY EDITABLE VECTOR DECORATIVE OBJECTS
   ============================================================ */
.studio-decor-motif {
  position: absolute;
  pointer-events: auto;
  cursor: grab;
  user-select: none;
  transform-origin: center center;
}
.studio-decor-motif.is-selected {
  outline: 1.5px dashed #B8955A;
  outline-offset: 2px;
  z-index: 28 !important;
}

/* ============================================================
   STUDIO QR INTERACTIVE PLACEMENT (DOCKED OR FLOATING)
   ============================================================ */
.studio-qr-interactive-wrap {
  position: relative;
  z-index: 12;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 4px auto;
  cursor: grab;
  transition: transform 0.12s ease;
}
.studio-qr-interactive-wrap:hover {
  outline: 1px dashed rgba(255, 122, 0, 0.55);
  outline-offset: 3px;
  border-radius: 10px;
}
.studio-qr-interactive-wrap.dock-bottom-right {
  align-self: flex-end;
  margin-left: auto;
  margin-right: 0;
}
.studio-qr-interactive-wrap.dock-bottom-left {
  align-self: flex-start;
  margin-right: auto;
  margin-left: 0;
}
.studio-qr-interactive-wrap.dock-floating {
  position: absolute !important;
  margin: 0 !important;
  z-index: 25 !important;
}
</style>

<script>
// ============================================================
// Direct Manipulation & Canvas State Engine
// ============================================================
let currentOrientation = 'portrait';
let uploadedLogoData = null;
let isQrSelected = false;
let activePhotoLayerId = null;
let activeDecorId = null;
let photoLayerUploadMode = 'add'; // 'add' | 'replace'

// Direct Manipulation Interaction State
let dragMode = null; // 'DRAGGING' | 'RESIZING' | 'ROTATING' | null
let dragStartPointer = { x: 0, y: 0 };
let dragStartCoords = { x: 50, y: 78, size: 28, rotation: 0 };
let activeResizeHandle = null;
let qrCenterScreen = { x: 0, y: 0 };

// Main Design State
let state = {
  cardMode: 'canva', // 'canva' (poster clone) vs 'studio' (template)
  template: 'wedding_ivory_bloom',
  orientation: 'portrait',
  headerStyle: 'unified',
  compositionType: 'asymmetrical-right',
  contentPadding: '38px 26px 26px 140px',
  showArtwork: true,
  artworkOpacity: 100,
  decorativeArtwork: '',
  sampleCouple: 'Amelia & Sebastian',
  scriptFont: "'Great Vibes', cursive",
  canvaBgData: null,
  
  // Normalized QR Coordinates (% relative to poster bounds)
  qrXPercent: 50.0,
  qrYPercent: 78.0,
  qrSizePercent: 28.0, // % of poster width
  qrRotation: 0,       // degrees (0 to 360)
  qrPadding: 10,       // padding in px
  qrRadius: 18,        // border radius in px
  qrBadgeStyle: 'badge-glass',
  canvaQrDark: '#09090B',
  canvaQrLight: '#FFFFFF',
  qrBgTransparent: false,
  badgeTextColor: '#09090B',
  qrLocked: false,
  showAttendee: true,
  showRef: true,
  showTier: true,

  // Studio QR & Guest Category Placement
  studioQrDock: 'bottom-right', // 'bottom-center' | 'bottom-right' | 'bottom-left' | 'floating'
  studioQrX: 78,
  studioQrY: 82,
  studioQrSize: 104,
  studioQrRotation: 0,
  guestCategory: 'VIP',
  showQrCategoryLabel: true,

  // 19 Supported Invitation Content Fields
  badge: 'TOGETHER WITH THEIR FAMILIES',
  hostNames: 'The Families of Vance & Sterling',
  title: '<?= addslashes($event['title'] ?? 'Annual Gala & Awards 2026') ?>',
  subtitle: 'Celebrating Technological Leadership',
  celebrantName: 'Lady Victoria Sterling',
  guestOfHonour: 'Guest of Honour: H.E. Amb. Dr. Amina Kenyatta',
  guest: 'Dr. Reginald Massawe',
  quote: 'Honored to request the pleasure of your company',
  date: '<?= date('l, M d, Y', strtotime($event['start_date'] ?? 'now')) ?>',
  time: '<?= addslashes($event['start_time'] ?? '18:00') ?>',
  endTime: '23:30 EAT',
  venue: '<?= addslashes($event['venue_name'] ?? 'Serena Grand Ballroom') ?>',
  fullAddress: 'Ohio Street, Dar es Salaam, Tanzania',
  seat: 'Table 04 • VIP North Gate 1',
  rsvp: 'RSVP by October 15, 2026',
  contactInfo: 'Concierge: +255 754 000 111',
  additionalInstructions: 'Valet parking available at North Gate',
  eventWebsite: 'www.leulaevents.com',
  socialMedia: '#LeulaLuxuryEvents',
  footer: 'Black Tie Required • Non-Transferable Digital QR Ticket',

  // Field Visibility Map (Controls which of the 19 fields are shown)
  visibleFields: {
    badge: true,
    hostNames: false,
    sampleCouple: true,
    celebrantName: false,
    title: true,
    subtitle: true,
    guestOfHonour: false,
    quote: true,
    guest: true,
    date: true,
    time: true,
    endTime: false,
    venue: true,
    fullAddress: false,
    seat: true,
    dressCode: true,
    rsvp: true,
    contactInfo: false,
    additionalInstructions: false,
    eventWebsite: false,
    socialMedia: false,
    guestCategory: true,
    footer: true
  },

  // Dedicated Dress Code Section (On Every Template)
  showDressCode: true,
  dressCode: 'Black Tie Optional',
  dressCodeNote: 'Floor-length gowns & dark tailored tuxedos',
  dressCodeStyle: 'floral_wreath',
  dressCodeIcon: 'tuxedo',
  dressCodeFont: '',
  dressCodeColor: '#8B7355',
  dressCodeScale: 100,
  dressCodeWidth: 88,
  dressCodeOffsetY: 0,

  // Replaceable Photographs & Independent Decorative Objects
  photoLayers: [],
  decorativeObjects: [],
  layoutConfig: null,

  fontFamily: "'Cormorant Garamond', serif",
  textAlign: 'center',
  titleScale: 100,
  guestScale: 100,
  headerColor1: '#FF7A00',
  headerColor2: '#E96800',
  headingColor: '#2B231D',
  bodyBg: '#FAF6F0',
  textColor: '#2B231D',
  accentColor: '#B8955A',
  borderColor: '#DCCBBE',
  borderFrame: 'none',
  seal: 'ribbon',
  texture: 'none',
  qrSize: 108,
  qrDark: '#2B231D',
  logoData: null,

  // Top Card Cover Photo & 5 Layout Options Engine
  coverLayout: 'top',          // 'full' | 'top' | 'bottom' | 'half' | 'custom'
  coverImgData: null,
  coverHeight: 190,            // px height for top/bottom (120 to 380)
  coverPositionY: 50,          // % vertical alignment (0 = top, 50 = center, 100 = bottom)
  coverPositionX: 50,          // % horizontal alignment (0 = left, 50 = center, 100 = right)
  coverScale: 100,             // % zoom scale (50% to 250%)
  coverCropFit: 'cover',       // 'cover' | 'contain' | 'fill'
  coverBrightness: 100,        // % brightness (50% to 150%)
  coverContrast: 100,          // % contrast (50% to 150%)
  coverOpacity: 100,           // % opacity (10% to 100%)
  coverOverlay: 40,            // % dark legibility overlay tint (0% to 90%)
  coverTextContrast: 'glass',  // 'glass' | 'shadow' | 'none'
  coverHalfSide: 'left',       // 'left' | 'right' (portrait) / 'top' | 'bottom' (landscape)
  coverHalfRatio: 50,          // % split ratio (30% to 70%)
  coverCustomX: 50,            // % custom X position (0 to 100)
  coverCustomY: 35,            // % custom Y position (0 to 100)
  coverCustomW: 240,           // px width (80 to 420)
  coverCustomH: 180,           // px height (80 to 420)
  coverCustomRotation: 0       // degrees (-180 to 180)
};

// Restore saved DB configuration
try {
  const saved = <?= $savedConfigJson ?>;
  if (saved && Object.keys(saved).length > 0) {
    state = Object.assign(state, saved);
  }
} catch (e) {}

// If poster is stored in DB blob, fetch it asynchronously via api/poster.php
if (state.canvaBgData && typeof state.canvaBgData === 'string' && state.canvaBgData.startsWith('__FETCH:')) {
  const fetchUrl = state.canvaBgData.replace('__FETCH:', '').replace('__', '');
  state.canvaBgData = createSampleCanvaCardData(); // show sample while loading
  fetch(fetchUrl)
    .then(r => r.json())
    .then(data => {
      if (data && data.dataUrl) {
        state.canvaBgData = data.dataUrl;
        const thumb = document.getElementById('canvaBgThumb');
        if (thumb) { thumb.src = state.canvaBgData; document.getElementById('canvaBgPreviewWrapper').style.display = 'flex'; }
        renderCanvas();
        showToast('Canva poster restored from database.', 'info');
      }
    })
    .catch(() => {
      showToast('Could not reload saved poster image. Please re-upload.', 'warning');
    });
} else if (!state.canvaBgData) {
  state.canvaBgData = createSampleCanvaCardData();
}

// Generate luxury sample poster SVG
function createSampleCanvaCardData() {
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="1280" viewBox="0 0 800 1280">
    <defs>
      <linearGradient id="g1" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#18181B" />
        <stop offset="50%" stop-color="#09090B" />
        <stop offset="100%" stop-color="#1C1917" />
      </linearGradient>
      <linearGradient id="gold" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#F6D580" />
        <stop offset="50%" stop-color="#D4AF37" />
        <stop offset="100%" stop-color="#AA771C" />
      </linearGradient>
      <radialGradient id="glow" cx="50%" cy="38%" r="50%">
        <stop offset="0%" stop-color="#FF7A00" stop-opacity="0.25" />
        <stop offset="100%" stop-color="#000" stop-opacity="0" />
      </radialGradient>
    </defs>
    <rect width="800" height="1280" fill="url(#g1)" />
    <rect width="800" height="1280" fill="url(#glow)" />
    <rect x="36" y="36" width="728" height="1208" rx="28" fill="none" stroke="url(#gold)" stroke-width="3" />
    <rect x="52" y="52" width="696" height="1176" rx="20" fill="none" stroke="url(#gold)" stroke-width="1.5" stroke-dasharray="8 6" />
    <circle cx="52" cy="52" r="6" fill="#D4AF37" /><circle cx="748" cy="52" r="6" fill="#D4AF37" />
    <circle cx="52" cy="1228" r="6" fill="#D4AF37" /><circle cx="748" cy="1228" r="6" fill="#D4AF37" />
    <circle cx="400" cy="175" r="42" fill="none" stroke="url(#gold)" stroke-width="2" />
    <text x="400" y="187" font-family="'Cinzel', serif" font-size="32" font-weight="bold" fill="url(#gold)" text-anchor="middle">LE</text>
    <text x="400" y="260" font-family="'Cinzel', serif" font-size="19" font-weight="600" letter-spacing="6" fill="#D4AF37" text-anchor="middle">OFFICIAL INVITATION</text>
    <text x="400" y="330" font-family="'Cinzel', serif" font-size="38" font-weight="800" fill="#FFFFFF" text-anchor="middle">ANNUAL GALA &amp; AWARDS</text>
    <text x="400" y="370" font-family="'Sora', sans-serif" font-size="18" fill="#A1A1AA" text-anchor="middle">Celebrating Leadership &amp; Innovation Excellence</text>
    <path d="M 260 410 L 540 410" stroke="url(#gold)" stroke-width="2" />
    <polygon points="400,404 406,410 400,416 394,410" fill="#D4AF37" />
    <text x="400" y="470" font-family="'Playfair Display', serif" font-style="italic" font-size="22" fill="#E4E4E7" text-anchor="middle">Cordially requesting the distinguished honour of your presence</text>
    <rect x="120" y="520" width="560" height="145" rx="16" fill="rgba(255,255,255,0.04)" stroke="rgba(212,175,55,0.3)" stroke-width="1" />
    <text x="400" y="565" font-family="'Cinzel', serif" font-size="24" font-weight="700" fill="#F6D580" text-anchor="middle">SATURDAY, NOVEMBER 21, 2026</text>
    <text x="400" y="605" font-family="'Sora', sans-serif" font-size="18" fill="#FFFFFF" text-anchor="middle">AT 18:00 HOURS EAT</text>
    <text x="400" y="640" font-family="'Sora', sans-serif" font-size="16" fill="#A1A1AA" text-anchor="middle">Serena Grand Ballroom • Dar es Salaam</text>
    <text x="400" y="715" font-family="'Sora', sans-serif" font-size="15" letter-spacing="2" fill="#D4AF37" text-anchor="middle">BLACK TIE REQUIRED • VIP ACCREDITATION ONLY</text>
    <text x="400" y="1200" font-family="'Sora', sans-serif" font-size="13" letter-spacing="1" fill="#71717A" text-anchor="middle">DIGITAL QR PASS POSITIONED ANYWHERE ON POSTER</text>
  </svg>`;
  return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
}

function loadSampleCanvaCard() {
  state.canvaBgData = createSampleCanvaCardData();
  setCardMode('canva');
  document.getElementById('canvaBgThumb').src = state.canvaBgData;
  document.getElementById('canvaBgPreviewWrapper').style.display = 'flex';
  renderCanvas();
  selectQr(true);
  showToast('Luxury Canva poster loaded! Click & drag QR to place anywhere.', 'success');
}

// ============================================================
// DIRECT MANIPULATION POINTER EVENTS (Drag, Resize, Rotate)
// ============================================================
function onQrPointerDown(e) {
  if (state.qrLocked) {
    showToast('QR Code is currently locked. Unlock in the left panel to move.', 'info');
    return;
  }
  // If clicked a handle, let handle's listener process it
  if (e.target.classList.contains('handle')) return;

  e.preventDefault();
  e.stopPropagation();

  selectQr(true);
  dragMode = 'DRAGGING';
  dragStartPointer = { x: e.clientX, y: e.clientY };
  dragStartCoords = { x: state.qrXPercent, y: state.qrYPercent };

  window.addEventListener('pointermove', onGlobalPointerMove);
  window.addEventListener('pointerup', onGlobalPointerUp);
  window.addEventListener('pointercancel', onGlobalPointerUp);
}

function onResizePointerDown(e, handle) {
  if (state.qrLocked) return;
  e.preventDefault();
  e.stopPropagation();

  dragMode = 'RESIZING';
  activeResizeHandle = handle;
  dragStartPointer = { x: e.clientX, y: e.clientY };
  dragStartCoords = { size: state.qrSizePercent };

  window.addEventListener('pointermove', onGlobalPointerMove);
  window.addEventListener('pointerup', onGlobalPointerUp);
  window.addEventListener('pointercancel', onGlobalPointerUp);
}

function onRotatePointerDown(e) {
  if (state.qrLocked) return;
  e.preventDefault();
  e.stopPropagation();

  dragMode = 'ROTATING';
  const qrEl = document.getElementById('canvaQrElement');
  const rect = qrEl.getBoundingClientRect();
  qrCenterScreen = {
    x: rect.left + rect.width / 2,
    y: rect.top + rect.height / 2
  };
  dragStartCoords = { rotation: state.qrRotation };

  window.addEventListener('pointermove', onGlobalPointerMove);
  window.addEventListener('pointerup', onGlobalPointerUp);
  window.addEventListener('pointercancel', onGlobalPointerUp);
}

function onGlobalPointerMove(e) {
  if (!dragMode) return;
  const artboard = document.getElementById('canvaCardArtboard');
  const artRect = artboard.getBoundingClientRect();

  if (dragMode === 'DRAGGING') {
    // Delta in pixels converted to percentage of artboard
    const deltaX = ((e.clientX - dragStartPointer.x) / artRect.width) * 100;
    const deltaY = ((e.clientY - dragStartPointer.y) / artRect.height) * 100;

    let newX = dragStartCoords.x + deltaX;
    let newY = dragStartCoords.y + deltaY;

    // Alignment Snapping to 50% Center lines
    const guideV = document.getElementById('guideV');
    const guideH = document.getElementById('guideH');

    if (Math.abs(newX - 50) < 1.5) {
      newX = 50;
      guideV.style.display = 'block';
    } else {
      guideV.style.display = 'none';
    }

    if (Math.abs(newY - 50) < 1.5) {
      newY = 50;
      guideH.style.display = 'block';
    } else {
      guideH.style.display = 'none';
    }

    // Boundaries clamping (keep within 5% to 95%)
    state.qrXPercent = Math.max(5, Math.min(95, parseFloat(newX.toFixed(1))));
    state.qrYPercent = Math.max(5, Math.min(95, parseFloat(newY.toFixed(1))));

    updateQrPositionElement();
    syncPositionInputs();
  }
  else if (dragMode === 'RESIZING') {
    // Calculate distance from center or delta in X
    const deltaPx = (e.clientX - dragStartPointer.x);
    // Factor based on handle direction
    const factor = (activeResizeHandle === 'br' || activeResizeHandle === 'tr') ? 1 : -1;
    const deltaPercent = ((deltaPx * factor * 2) / artRect.width) * 100;

    let newSize = dragStartCoords.size + deltaPercent;
    // Limits: min 12% to max 65% of poster width
    state.qrSizePercent = Math.max(12, Math.min(65, parseFloat(newSize.toFixed(1))));

    updateQrSizeElement();
    syncSizeInputs();
  }
  else if (dragMode === 'ROTATING') {
    // Calculate angle from center of QR element
    const radians = Math.atan2(e.clientY - qrCenterScreen.y, e.clientX - qrCenterScreen.x);
    let degrees = (radians * 180 / Math.PI) + 90;
    if (degrees < 0) degrees += 360;

    // Snap to 0, 90, 180, 270 if close
    if (Math.abs(degrees - 0) < 3 || Math.abs(degrees - 360) < 3) degrees = 0;
    else if (Math.abs(degrees - 90) < 3) degrees = 90;
    else if (Math.abs(degrees - 180) < 3) degrees = 180;
    else if (Math.abs(degrees - 270) < 3) degrees = 270;

    state.qrRotation = Math.round(degrees);
    updateQrRotationElement();
    syncRotationInputs();
  }
}

function onGlobalPointerUp(e) {
  if (dragMode) {
    dragMode = null;
    activeResizeHandle = null;
    document.getElementById('guideV').style.display = 'none';
    document.getElementById('guideH').style.display = 'none';
    window.removeEventListener('pointermove', onGlobalPointerMove);
    window.removeEventListener('pointerup', onGlobalPointerUp);
    window.removeEventListener('pointercancel', onGlobalPointerUp);
    renderQrCodeCanvas(); // Regenerate vector QR at final size
  }
}

function onStageClick(e) {
  // If clicking stage outside of QR element, photo layer, or decorative object, deselect
  if (!e.target.closest('#canvaQrElement') && !e.target.closest('.studio-photo-layer') && !e.target.closest('.studio-decor-motif') && !e.target.closest('.tabs') && !e.target.closest('.card')) {
    selectQr(false);
    if (activePhotoLayerId) {
      activePhotoLayerId = null;
      renderPhotoLayersOnCanvas();
      renderPhotoLayersSidebarList();
    }
    if (activeDecorId) {
      activeDecorId = null;
      renderDecorObjectsOnCanvas();
      renderDecorObjectsSidebarList();
    }
  }
}

function selectQr(select) {
  isQrSelected = select;
  const qrEl = document.getElementById('canvaQrElement');
  if (qrEl) {
    if (select) qrEl.classList.add('is-selected');
    else qrEl.classList.remove('is-selected');
  }
}

// Real-time Element Transform Updates
function updateQrPositionElement() {
  const qrEl = document.getElementById('canvaQrElement');
  if (qrEl) {
    qrEl.style.left = `${state.qrXPercent}%`;
    qrEl.style.top = `${state.qrYPercent}%`;
  }
}

function updateQrSizeElement() {
  const badgeContent = document.getElementById('canvaQrBadgeContent');
  const tooltip = document.getElementById('dimensionTooltip');
  const artboard = document.getElementById('canvaCardArtboard');
  const artWidth = artboard ? artboard.offsetWidth : 420;

  // Approximate pixel width on screen
  const pxWidth = Math.round((state.qrSizePercent / 100) * artWidth);
  if (badgeContent) {
    badgeContent.style.minWidth = `${pxWidth}px`;
  }
  if (tooltip) {
    tooltip.textContent = `${pxWidth} × ${pxWidth} px`;
  }
}

function updateQrRotationElement() {
  const qrEl = document.getElementById('canvaQrElement');
  if (qrEl) {
    qrEl.style.transform = `translate(-50%, -50%) rotate(${state.qrRotation}deg)`;
  }
}

// Sidebar Numerical / Slider Syncing
function syncPositionInputs() {
  document.getElementById('cfgQrX').value = state.qrXPercent;
  document.getElementById('cfgQrY').value = state.qrYPercent;
  document.getElementById('valQrX').textContent = `${state.qrXPercent}%`;
  document.getElementById('valQrY').textContent = `${state.qrYPercent}%`;
}

function syncSizeInputs() {
  document.getElementById('cfgQrSizeRange').value = state.qrSizePercent;
  const artboard = document.getElementById('canvaCardArtboard');
  const artWidth = artboard ? artboard.offsetWidth : 420;
  const pxWidth = Math.round((state.qrSizePercent / 100) * artWidth);
  document.getElementById('valQrSize').textContent = `${state.qrSizePercent}% (~${pxWidth}px)`;
}

function syncRotationInputs() {
  document.getElementById('cfgQrRotation').value = state.qrRotation;
  document.getElementById('valQrRotation').textContent = state.qrRotation;
}

function onPositionSliderChange() {
  state.qrXPercent = parseFloat(document.getElementById('cfgQrX').value);
  state.qrYPercent = parseFloat(document.getElementById('cfgQrY').value);
  syncPositionInputs();
  updateQrPositionElement();
  selectQr(true);
}

function onSizeSliderChange() {
  state.qrSizePercent = parseFloat(document.getElementById('cfgQrSizeRange').value);
  syncSizeInputs();
  updateQrSizeElement();
  renderQrCodeCanvas();
  selectQr(true);
}

function onRotationSliderChange() {
  state.qrRotation = parseInt(document.getElementById('cfgQrRotation').value, 10);
  syncRotationInputs();
  updateQrRotationElement();
  selectQr(true);
}

function nudgeQr(dx, dy) {
  state.qrXPercent = Math.max(5, Math.min(95, parseFloat((state.qrXPercent + dx).toFixed(1))));
  state.qrYPercent = Math.max(5, Math.min(95, parseFloat((state.qrYPercent + dy).toFixed(1))));
  syncPositionInputs();
  updateQrPositionElement();
  selectQr(true);
}

function centerQrX() {
  state.qrXPercent = 50.0;
  syncPositionInputs();
  updateQrPositionElement();
  selectQr(true);
  showToast('Centered horizontally', 'info');
}

function centerQrY() {
  state.qrYPercent = 50.0;
  syncPositionInputs();
  updateQrPositionElement();
  selectQr(true);
  showToast('Centered vertically', 'info');
}

function resetQrPosition() {
  state.qrXPercent = 50.0;
  state.qrYPercent = 78.0;
  syncPositionInputs();
  updateQrPositionElement();
  selectQr(true);
  showToast('Reset position to bottom-center', 'info');
}

function resetQrSize() {
  state.qrSizePercent = 28.0;
  syncSizeInputs();
  updateQrSizeElement();
  renderQrCodeCanvas();
  selectQr(true);
  showToast('Reset QR size', 'info');
}

function rotateQrStep(step) {
  state.qrRotation = (state.qrRotation + step) % 360;
  syncRotationInputs();
  updateQrRotationElement();
  selectQr(true);
}

function resetQrRotation() {
  state.qrRotation = 0;
  syncRotationInputs();
  updateQrRotationElement();
  selectQr(true);
  showToast('Reset rotation to 0°', 'info');
}

function toggleQrLock(locked) {
  state.qrLocked = locked;
  const qrEl = document.getElementById('canvaQrElement');
  const text = document.getElementById('valQrLockedText');
  if (locked) {
    qrEl.classList.add('is-locked');
    text.textContent = '🔒 Locked';
    text.style.color = '#DC2626';
    selectQr(false);
    showToast('QR Code position locked', 'info');
  } else {
    qrEl.classList.remove('is-locked');
    text.textContent = 'Unlocked';
    text.style.color = 'var(--text-secondary)';
    selectQr(true);
    showToast('QR Code unlocked for direct editing', 'success');
  }
}

// ============================================================
// Undo / Redo History Stack & Workspace Tabs Engine
// ============================================================
window.isDesignDirty = false;
const historyStack = [];
let historyIndex = -1;
const MAX_HISTORY = 40;
let isHistoryAction = false;

function pushHistoryState() {
  if (isHistoryAction) return;
  const snapshot = JSON.stringify(state);
  if (historyIndex >= 0 && historyStack[historyIndex] === snapshot) return;
  if (historyIndex < historyStack.length - 1) {
    historyStack.splice(historyIndex + 1);
  }
  historyStack.push(snapshot);
  if (historyStack.length > MAX_HISTORY) historyStack.shift();
  historyIndex = historyStack.length - 1;
  updateUndoRedoButtons();
  window.isDesignDirty = true;
}
window.saveHistoryState = pushHistoryState;

function undoState() {
  if (historyIndex > 0) {
    historyIndex--;
    isHistoryAction = true;
    try {
      const snapshot = JSON.parse(historyStack[historyIndex]);
      Object.assign(state, snapshot);
      syncStateToInputs();
      renderCanvas();
      showToast('Undo performed', 'info');
    } finally {
      isHistoryAction = false;
    }
    updateUndoRedoButtons();
  }
}

function redoState() {
  if (historyIndex < historyStack.length - 1) {
    historyIndex++;
    isHistoryAction = true;
    try {
      const snapshot = JSON.parse(historyStack[historyIndex]);
      Object.assign(state, snapshot);
      syncStateToInputs();
      renderCanvas();
      showToast('Redo performed', 'info');
    } finally {
      isHistoryAction = false;
    }
    updateUndoRedoButtons();
  }
}

function updateUndoRedoButtons() {
  const btnUndo = document.getElementById('btnUndoAction');
  const btnRedo = document.getElementById('btnRedoAction');
  if (btnUndo) btnUndo.disabled = (historyIndex <= 0);
  if (btnRedo) btnRedo.disabled = (historyIndex >= historyStack.length - 1);
}

// Global Keyboard Shortcuts (Ctrl+Z / Ctrl+Y)
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
    if (!e.target.isContentEditable && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
      e.preventDefault();
      undoState();
    }
  } else if ((e.ctrlKey || e.metaKey) && (e.key.toLowerCase() === 'y' || (e.key.toLowerCase() === 'z' && e.shiftKey))) {
    if (!e.target.isContentEditable && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
      e.preventDefault();
      redoState();
    }
  }
});

function switchStudioTab(tabId) {
  document.querySelectorAll('[data-tab-group="studio"].tab-btn').forEach(btn => {
    btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
  });
  document.querySelectorAll('[data-tab-group="studio"].tab-panel').forEach(panel => {
    panel.classList.toggle('active', panel.getAttribute('data-tab-panel') === tabId);
  });
}

// ============================================================
// TEMPLATE ENGINE (50 Premium Designer Templates Library)
// ============================================================
const TEMPLATE_PRESETS = (typeof TEMPLATES_LIBRARY !== 'undefined') ? TEMPLATES_LIBRARY : {
  wedding_royal_gold: {
    id: 'wedding_royal_gold',
    name: 'Royal Gold Wedding',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#18181B',
    headerColor2: '#09090B',
    headingColor: '#F6D580',
    bodyBg: '#09090B',
    textColor: '#F6D580',
    accentColor: '#D4AF37',
    borderColor: '#D4AF37',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#18181B',
    canvaQrLight: '#F6D580',
    badge: 'ROYAL VIP WEDDING PASS',
    quote: 'Request the pleasure of your company at the holy matrimony celebration',
    footer: 'Strict Formal Black Tie & Evening Gown • Royal Invitation Only'
  }
};

function applyTemplate(templateId) {
  const preset = (typeof TEMPLATES_LIBRARY !== 'undefined' && TEMPLATES_LIBRARY[templateId])
    ? TEMPLATES_LIBRARY[templateId]
    : TEMPLATE_PRESETS[templateId];
  if (!preset) return;

  // Deep clone arrays/objects from preset so editing doesn't mutate TEMPLATES_LIBRARY
  const clonedPreset = JSON.parse(JSON.stringify(preset));
  state.template = templateId;
  Object.assign(state, clonedPreset);

  // Build visibleFields map from template's defaultVisibleFields (object or array)
  const all19Fields = [
    'badge', 'hostNames', 'sampleCouple', 'celebrantName', 'title', 'subtitle',
    'guestOfHonour', 'quote', 'guest', 'date', 'time', 'endTime', 'venue',
    'fullAddress', 'seat', 'dressCode', 'rsvp', 'contactInfo',
    'additionalInstructions', 'eventWebsite', 'socialMedia', 'guestCategory', 'footer'
  ];
  state.visibleFields = {};
  if (clonedPreset.defaultVisibleFields && typeof clonedPreset.defaultVisibleFields === 'object' && !Array.isArray(clonedPreset.defaultVisibleFields)) {
    all19Fields.forEach(f => {
      state.visibleFields[f] = clonedPreset.defaultVisibleFields[f] !== false;
    });
  } else {
    const defaultFields = Array.isArray(clonedPreset.defaultVisibleFields)
      ? clonedPreset.defaultVisibleFields
      : ['badge', 'sampleCouple', 'title', 'subtitle', 'quote', 'guest', 'date', 'time', 'venue', 'seat', 'dressCode', 'rsvp', 'guestCategory', 'footer'];
    all19Fields.forEach(f => {
      state.visibleFields[f] = defaultFields.includes(f);
    });
  }
  state.showDressCode = true;
  state.titleScale = 100;
  state.guestScale = 100;
  state.dressCodeScale = 100;
  state.dressCodeOffsetY = 0;

  activePhotoLayerId = (state.photoLayers && state.photoLayers.length > 0) ? state.photoLayers[0].id : null;
  activeDecorId = null;

  // Sync inputs FIRST so the inputs hold the new preset values
  syncStateToInputs();

  // Switch to Studio mode so user immediately sees the editorial template rendered
  setCardMode('studio');

  // Update Active Template Banner & Composition Badge
  const banner = document.getElementById('activeTemplateBanner');
  const nameEl = document.getElementById('activeTemplateName');
  if (banner) banner.style.display = 'flex';
  if (nameEl) nameEl.textContent = preset.name;

  const compBadge = document.getElementById('canvasCompositionBadge');
  if (compBadge) {
    compBadge.style.display = 'inline-flex';
    compBadge.textContent = (state.compositionType || 'editorial-layout').replace(/-/g, ' ').toUpperCase();
  }

  // Highlight active card in both marketplace grid and classic preset lists
  document.querySelectorAll('.tpl-card-item, .template-card').forEach(el => {
    el.classList.remove('active');
  });
  const clickedCard = document.querySelector(`[data-template-id="${templateId}"]`) || 
                      document.querySelector(`.tpl-card-item[onclick*="'${templateId}'"]`) ||
                      document.querySelector(`.template-card[onclick*="'${templateId}'"]`);
  if (clickedCard) clickedCard.classList.add('active');

  renderCanvas();
  pushHistoryState();
  showToast(`Applied "${preset.name}" (${(state.compositionType || 'luxury').replace(/-/g, ' ')})!`, 'success');
}

// Master Canvas Renderer
function renderCanvas() {
  pullInputsToState();

  if (state.cardMode === 'canva') {
    renderCanvaCard();
  } else {
    renderStudioCard();
  }
}

function renderCanvaCard() {
  const canvaArtboard = document.getElementById('canvaCardArtboard');
  const canvaImg = document.getElementById('canvaCardImg');
  const badgeContent = document.getElementById('canvaQrBadgeContent');
  const badgePill = document.getElementById('canvaBadgePill');
  const attendeeText = document.getElementById('canvaAttendeeText');
  const refText = document.getElementById('canvaRefText');

  if (state.canvaBgData) {
    canvaImg.src = state.canvaBgData;
  }

  canvaArtboard.className = 'canva-card-artboard ' + (state.orientation === 'landscape' ? 'landscape' : '');

  // Update QR Element Transform
  updateQrPositionElement();
  updateQrSizeElement();
  updateQrRotationElement();

  // Badge Aesthetics
  badgeContent.className = `canva-qr-badge-content ${state.qrBadgeStyle}`;
  badgeContent.style.padding = `${state.qrPadding}px`;
  badgeContent.style.borderRadius = `${state.qrRadius}px`;

  // QR Box Wrapper Styling (Support Transparent Background)
  const qrBoxWrapper = document.getElementById('canvaQrBoxWrapper');
  if (qrBoxWrapper) {
    if (state.qrBgTransparent) {
      qrBoxWrapper.style.background = 'transparent';
      qrBoxWrapper.style.boxShadow = 'none';
      qrBoxWrapper.style.border = 'none';
      qrBoxWrapper.style.padding = '0';
    } else {
      qrBoxWrapper.style.background = state.canvaQrLight || '#FFFFFF';
      qrBoxWrapper.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)';
      qrBoxWrapper.style.border = 'none';
      qrBoxWrapper.style.padding = '6px';
      qrBoxWrapper.style.borderRadius = `${Math.min(12, state.qrRadius)}px`;
    }
  }

  // Text color customization
  const textColor = state.badgeTextColor || (state.qrBadgeStyle === 'badge-dark' ? '#FFFFFF' : '#09090B');
  const isLightText = (textColor.toLowerCase() === '#ffffff' || textColor.toLowerCase() === '#fff');

  // Toggle optional items
  badgePill.style.display = state.showTier ? 'inline-block' : 'none';
  badgePill.textContent = state.badge || 'VIP GUEST';
  badgePill.style.background = state.accentColor || '#FF7A00';

  attendeeText.style.display = state.showAttendee ? 'block' : 'none';
  attendeeText.textContent = state.guest || 'Dr. Reginald Massawe';
  attendeeText.style.color = textColor;
  attendeeText.style.textShadow = (state.qrBgTransparent && isLightText) ? '0 1px 4px rgba(0,0,0,0.85)' : 'none';

  refText.style.display = state.showRef ? 'block' : 'none';
  refText.style.color = textColor;
  refText.style.opacity = '0.85';
  refText.style.textShadow = (state.qrBgTransparent && isLightText) ? '0 1px 4px rgba(0,0,0,0.85)' : 'none';

  renderQrCodeCanvas();
}

function renderQrCodeCanvas() {
  const qrBox = document.getElementById("canvaQrBox");
  if (!qrBox) return;
  qrBox.innerHTML = "";

  const artboard = document.getElementById('canvaCardArtboard');
  const artWidth = artboard ? artboard.offsetWidth : 420;
  // Calculate QR size in pixels
  const totalBadgePx = (state.qrSizePercent / 100) * artWidth;
  const innerPad = state.qrBgTransparent ? 0 : 12;
  const qrPixelDimension = Math.max(70, Math.round(totalBadgePx - (state.qrPadding * 2) - innerPad));

  const lightColor = state.qrBgTransparent ? "rgba(0,0,0,0)" : (state.canvaQrLight || "#FFFFFF");

  new QRCode(qrBox, {
    text: "<?= APP_URL ?>/pass.php?token=DEMO-TOKEN-VIP-REG-01",
    width: qrPixelDimension,
    height: qrPixelDimension,
    colorDark: state.canvaQrDark || "#09090B",
    colorLight: lightColor,
    correctLevel: QRCode.CorrectLevel.H
  });
}

// Render Studio Multi-Element Card
function renderStudioCard() {
  const artboard = document.getElementById('cardArtboard');
  const header = document.getElementById('artboardHeader');
  const headerContent = document.getElementById('artboardHeaderContent');
  const body = document.getElementById('artboardBody');
  const title = document.getElementById('cardTitle');
  const guest = document.getElementById('cardGuest');
  const quote = document.getElementById('cardQuote');
  const seal = document.getElementById('cardSealBadge');
  const metaBox = document.getElementById('cardMetaBox');
  const textureOverlay = document.getElementById('cardTextureOverlay');
  const badgeEl = document.getElementById('cardBadge');
  const hostNamesEl = document.getElementById('cardHostNames');
  const celebrantEl = document.getElementById('cardCelebrantName');
  const guestHonourEl = document.getElementById('cardGuestOfHonour');
  const subtitleEl = document.getElementById('cardSubtitle');
  const dateEl = document.getElementById('cardDate');
  const timeEl = document.getElementById('cardTime');
  const endTimeWrap = document.getElementById('cardEndTimeWrap');
  const endTimeEl = document.getElementById('cardEndTime');
  const venueEl = document.getElementById('cardVenue');
  const fullAddrEl = document.getElementById('cardFullAddress');
  const seatEl = document.getElementById('cardSeat');
  const footerEl = document.getElementById('cardFooter');
  const refCodeEl = document.getElementById('cardRefCode');
  const optBox = document.getElementById('cardOptionalFieldsBox');
  const rsvpEl = document.getElementById('cardRsvp');
  const contactEl = document.getElementById('cardContactInfo');
  const instrEl = document.getElementById('cardAdditionalInstructions');
  const webEl = document.getElementById('cardEventWebsite');
  const socialEl = document.getElementById('cardSocialMedia');

  const vf = state.visibleFields || {};
  const isFieldVisible = (k) => vf[k] !== false;

  // Text values & visibility (only update DOM text if user is not actively editing that element)
  if (badgeEl) {
    if (document.activeElement !== badgeEl) badgeEl.textContent = state.badge || '';
    badgeEl.style.display = (isFieldVisible('badge') && state.badge) ? 'inline-block' : 'none';
  }
  if (hostNamesEl) {
    if (document.activeElement !== hostNamesEl) hostNamesEl.textContent = state.hostNames || '';
    hostNamesEl.style.display = (isFieldVisible('hostNames') && state.hostNames) ? 'block' : 'none';
    hostNamesEl.style.color = state.headingColor || state.textColor;
  }
  if (celebrantEl) {
    if (document.activeElement !== celebrantEl) celebrantEl.textContent = state.celebrantName || '';
    celebrantEl.style.display = (isFieldVisible('celebrantName') && state.celebrantName) ? 'block' : 'none';
    celebrantEl.style.fontFamily = state.scriptFont || state.fontFamily;
    celebrantEl.style.color = state.accentColor || state.headingColor;
  }
  if (title) {
    if (document.activeElement !== title) title.textContent = state.title || '';
    title.style.display = (isFieldVisible('title') && state.title) ? 'block' : 'none';
  }
  if (subtitleEl) {
    if (document.activeElement !== subtitleEl) subtitleEl.textContent = state.subtitle || '';
    subtitleEl.style.display = (isFieldVisible('subtitle') && state.subtitle) ? 'block' : 'none';
  }
  if (guestHonourEl) {
    if (document.activeElement !== guestHonourEl) guestHonourEl.textContent = state.guestOfHonour || '';
    guestHonourEl.style.display = (isFieldVisible('guestOfHonour') && state.guestOfHonour) ? 'block' : 'none';
    guestHonourEl.style.color = state.accentColor || state.headingColor;
  }
  if (guest) {
    if (document.activeElement !== guest) guest.textContent = state.guest || '';
    guest.style.display = isFieldVisible('guest') ? 'block' : 'none';
    const attLbl = document.getElementById('cardAttendeeLabel');
    if (attLbl) attLbl.style.display = isFieldVisible('guest') ? 'block' : 'none';
  }
  if (quote) {
    if (document.activeElement !== quote) quote.textContent = state.quote || '';
    quote.style.display = (isFieldVisible('quote') && state.quote) ? 'block' : 'none';
  }
  if (dateEl) {
    if (document.activeElement !== dateEl) dateEl.textContent = state.date || '';
    dateEl.style.display = isFieldVisible('date') ? 'inline' : 'none';
  }
  if (timeEl) {
    if (document.activeElement !== timeEl) timeEl.textContent = state.time || '';
    timeEl.style.display = isFieldVisible('time') ? 'inline' : 'none';
  }
  if (endTimeWrap && endTimeEl) {
    if (document.activeElement !== endTimeEl) endTimeEl.textContent = state.endTime || '';
    endTimeWrap.style.display = (isFieldVisible('endTime') && state.endTime) ? 'inline' : 'none';
  }
  if (venueEl) {
    if (document.activeElement !== venueEl) venueEl.textContent = state.venue || '';
    venueEl.style.display = isFieldVisible('venue') ? 'block' : 'none';
  }
  if (fullAddrEl) {
    if (document.activeElement !== fullAddrEl) fullAddrEl.textContent = state.fullAddress || '';
    fullAddrEl.style.display = (isFieldVisible('fullAddress') && state.fullAddress) ? 'block' : 'none';
  }
  if (seatEl) {
    if (document.activeElement !== seatEl) seatEl.textContent = state.seat || '';
    seatEl.style.display = (isFieldVisible('seat') && state.seat) ? 'block' : 'none';
  }
  if (footerEl) {
    if (document.activeElement !== footerEl) footerEl.textContent = state.footer || '';
    footerEl.style.display = (isFieldVisible('footer') && state.footer) ? 'block' : 'none';
  }

  // Optional extended fields (RSVP, Contact, Additional Instructions, Website, Social)
  let anyOptVisible = false;
  const optMap = [
    { el: rsvpEl, key: 'rsvp' },
    { el: contactEl, key: 'contactInfo' },
    { el: instrEl, key: 'additionalInstructions' },
    { el: webEl, key: 'eventWebsite' },
    { el: socialEl, key: 'socialMedia' }
  ];
  optMap.forEach(({ el, key }) => {
    if (!el) return;
    if (document.activeElement !== el) el.textContent = state[key] || '';
    const show = isFieldVisible(key) && state[key] && String(state[key]).trim().length > 0;
    el.style.display = show ? 'block' : 'none';
    if (show) anyOptVisible = true;
  });
  if (optBox) {
    optBox.style.display = anyOptVisible ? 'block' : 'none';
    optBox.style.color = state.textColor;
  }

  // Template-specific intelligent content arrangement & alignment
  const align = state.textAlign || 'center';
  if (headerContent) headerContent.style.textAlign = align;
  if (body) body.style.textAlign = align;

  // Typography & Scales
  title.style.fontSize = `${(1.42 * (state.titleScale / 100)).toFixed(2)}rem`;
  guest.style.fontSize = `${(1.38 * (state.guestScale / 100)).toFixed(2)}rem`;
  title.style.fontFamily = state.fontFamily;
  guest.style.fontFamily = state.fontFamily;

  const headingCol = state.headingColor || '#FFFFFF';
  title.style.color = headingCol;
  if (badgeEl) {
    badgeEl.style.color = headingCol;
    badgeEl.style.borderColor = `${headingCol}55`;
    badgeEl.style.fontFamily = state.fontFamily;
  }
  if (subtitleEl) {
    subtitleEl.style.color = headingCol;
    subtitleEl.style.opacity = '0.92';
  }
  if (seatEl) {
    seatEl.style.color = state.accentColor || '#FF7A00';
  }

  title.style.fontWeight = state.fontBold ? '800' : '700';
  title.style.fontStyle = state.fontItalic ? 'italic' : 'normal';
  title.style.textDecoration = state.fontUnderline ? 'underline' : 'none';
  title.style.textAlign = align;
  title.style.letterSpacing = `${state.letterSpacing || 0}px`;
  title.style.lineHeight = `${((state.lineHeight || 12) / 10).toFixed(1)}`;

  guest.style.fontWeight = state.fontBold ? '800' : '700';
  guest.style.fontStyle = state.fontItalic ? 'italic' : 'normal';
  guest.style.letterSpacing = `${state.letterSpacing || 0}px`;
  guest.style.textAlign = align;

  if (state.borderColor) {
    artboard.style.borderColor = state.borderColor;
  }

  const isFullCover = (state.coverImgData && state.coverLayout === 'full');
  const isUnified = (state.headerStyle === 'unified') && !isFullCover && !(state.coverImgData && state.coverLayout === 'top');

  // Set base orientation, composition & border frame classes
  artboard.className = (state.orientation === 'landscape' ? 'artboard-landscape' : 'artboard-portrait');
  if (state.compositionType) artboard.classList.add('comp-' + state.compositionType);
  if (state.borderFrame === 'gold_single') artboard.classList.add('frame-gold-single');
  else if (state.borderFrame === 'gold_double') artboard.classList.add('frame-gold-double');
  else if (state.borderFrame === 'glass_glow') artboard.classList.add('frame-glass-glow');
  else if (state.borderFrame === 'deco_corners') artboard.classList.add('frame-deco-corners');

  // Render Calligraphy Names / Script Accent Line
  const coupleEl = document.getElementById('cardCoupleScript');
  if (coupleEl) {
    if (document.activeElement !== coupleEl) coupleEl.textContent = state.sampleCouple || '';
    const showCouple = isFieldVisible('sampleCouple') && state.sampleCouple && state.sampleCouple.trim();
    coupleEl.style.display = showCouple ? 'block' : 'none';
    coupleEl.style.fontFamily = state.scriptFont || "'Great Vibes', cursive";
    coupleEl.style.color = isFullCover ? '#FFFFFF' : (state.headingColor || state.textColor);
    coupleEl.style.textAlign = align;
  }

  // Render Original Multi-Element Vector Artwork Layer (LuxeArt SVG)
  const artLayer = document.getElementById('cardDecorativeArtwork');
  if (artLayer) {
    if (state.showArtwork !== false) {
      let svgStr = state.decorativeArtwork || '';
      if (typeof generateTemplateArtworkSvg === 'function' && state.template && typeof TEMPLATES_LIBRARY !== 'undefined' && TEMPLATES_LIBRARY[state.template]) {
        svgStr = generateTemplateArtworkSvg(state.template, state.accentColor);
        state.decorativeArtwork = svgStr;
      }
      artLayer.innerHTML = svgStr;
      artLayer.style.display = svgStr ? 'block' : 'none';
      artLayer.style.opacity = (((state.artworkOpacity !== undefined ? state.artworkOpacity : 100) / 100)).toFixed(2);
    } else {
      artLayer.style.display = 'none';
    }
  }

  // Render Independently Editable Vector Decorative Objects Layer
  renderDecorObjectsOnCanvas();

  // Render Replaceable Multi-Photo Frames Layer
  renderPhotoLayersOnCanvas();

  // Render Dedicated Dress Code Section (Matches Each Template's Artistic Style)
  renderDressCodeSectionOnCanvas(isFullCover);

  if (isFullCover) {
    header.style.background = 'transparent';
    header.style.backgroundImage = 'none';
    body.style.background = 'transparent';
    body.style.backgroundImage = 'none';
    artboard.style.background = 'transparent';
    artboard.classList.add('cover-layout-full', 'card-cover-full-active');
  } else if (isUnified) {
    header.style.background = 'transparent';
    header.style.backgroundImage = 'none';
    body.style.background = 'transparent';
    body.style.backgroundImage = 'none';
    artboard.style.background = state.bodyBg || '#FAF6F0';
    artboard.classList.add('artboard-unified');
  } else {
    header.style.background = `linear-gradient(135deg, ${state.headerColor1}, ${state.headerColor2})`;
    body.style.background = state.bodyBg;
    artboard.style.background = '';
  }
  body.style.color = state.textColor;
  guest.style.color = isFullCover ? '#FFFFFF' : state.textColor;

  // Cover Photo Rendering across 5 Layout Options
  const fullWrap = document.getElementById('cardCoverFullWrap');
  const fullImg = document.getElementById('cardCoverFullImg');
  const fullOverlay = document.getElementById('cardCoverFullOverlay');

  const topWrap = document.getElementById('cardCoverContainer');
  const topImg = document.getElementById('cardCoverImg');
  const topOverlay = document.getElementById('cardCoverOverlay');

  const bottomWrap = document.getElementById('cardCoverBottomWrap');
  const bottomImg = document.getElementById('cardCoverBottomImg');
  const bottomOverlay = document.getElementById('cardCoverBottomOverlay');

  const customWrap = document.getElementById('cardCoverCustomWrap');
  const customImg = document.getElementById('cardCoverCustomImg');
  const customOverlay = document.getElementById('cardCoverCustomOverlay');

  if (state.coverImgData) {
    const filterStyle = `brightness(${state.coverBrightness || 100}%) contrast(${state.coverContrast || 100}%)`;
    const alphaOverlay = (((state.coverOverlay !== undefined ? state.coverOverlay : 40) / 100)).toFixed(2);
    const imgOpacity = ((state.coverOpacity !== undefined ? state.coverOpacity : 100) / 100).toFixed(2);
    const fitStyle = state.coverCropFit || 'cover';
    const scale = (state.coverScale || 100) / 100;
    const posX = state.coverPositionX !== undefined ? state.coverPositionX : 50;
    const posY = state.coverPositionY !== undefined ? state.coverPositionY : 50;

    if (state.coverLayout === 'full') {
      if (fullWrap) fullWrap.style.display = 'block';
      if (fullImg) {
        fullImg.src = state.coverImgData;
        fullImg.style.objectFit = fitStyle;
        fullImg.style.objectPosition = `${posX}% ${posY}%`;
        fullImg.style.transform = `scale(${scale})`;
        fullImg.style.filter = filterStyle;
        fullImg.style.opacity = imgOpacity;
      }
      if (fullOverlay) fullOverlay.style.background = `rgba(0, 0, 0, ${alphaOverlay})`;
      if (topWrap) topWrap.style.display = 'none';
      if (bottomWrap) bottomWrap.style.display = 'none';
      if (customWrap) customWrap.style.display = 'none';
      header.style.minHeight = '';

      header.style.background = 'transparent';
      header.style.backgroundImage = 'none';
      body.style.background = 'transparent';
      body.style.backgroundImage = 'none';
      artboard.style.background = 'transparent';
      artboard.classList.add('cover-layout-full', 'card-cover-full-active');

      if (state.coverTextContrast === 'glass') artboard.classList.add('full-cover-glass');
      else if (state.coverTextContrast === 'shadow') artboard.classList.add('full-cover-shadow');
    } else if (state.coverLayout === 'top') {
      if (fullWrap) fullWrap.style.display = 'none';
      if (bottomWrap) bottomWrap.style.display = 'none';
      if (customWrap) customWrap.style.display = 'none';
      if (topWrap) {
        topWrap.style.display = 'block';
        if (topImg) {
          topImg.src = state.coverImgData;
          topImg.style.objectFit = fitStyle;
          topImg.style.objectPosition = `${posX}% ${posY}%`;
          topImg.style.transform = `scale(${scale})`;
          topImg.style.transformOrigin = `${posX}% ${posY}%`;
          topImg.style.filter = filterStyle;
          topImg.style.opacity = imgOpacity;
        }
        if (topOverlay) topOverlay.style.background = `rgba(0, 0, 0, ${alphaOverlay})`;
      }
      header.style.minHeight = `${state.coverHeight || 190}px`;
    } else if (state.coverLayout === 'bottom') {
      if (fullWrap) fullWrap.style.display = 'none';
      if (topWrap) topWrap.style.display = 'none';
      if (customWrap) customWrap.style.display = 'none';
      header.style.minHeight = '';
      if (bottomWrap) {
        bottomWrap.style.display = 'block';
        bottomWrap.style.height = `${state.coverHeight || 190}px`;
        if (bottomImg) {
          bottomImg.src = state.coverImgData;
          bottomImg.style.objectFit = fitStyle;
          bottomImg.style.objectPosition = `${posX}% ${posY}%`;
          bottomImg.style.transform = `scale(${scale})`;
          bottomImg.style.transformOrigin = `${posX}% ${posY}%`;
          bottomImg.style.filter = filterStyle;
          bottomImg.style.opacity = imgOpacity;
        }
        if (bottomOverlay) bottomOverlay.style.background = `rgba(0, 0, 0, ${alphaOverlay})`;
      }
    } else if (state.coverLayout === 'half') {
      if (fullWrap) fullWrap.style.display = 'none';
      if (bottomWrap) bottomWrap.style.display = 'none';
      if (customWrap) customWrap.style.display = 'none';
      artboard.classList.add('artboard-half-split');
      if (state.orientation === 'portrait') {
        if (state.coverHalfSide === 'right') artboard.classList.add('split-side-right');
      } else {
        artboard.classList.add('split-vertical');
        if (state.coverHalfSide === 'right' || state.coverHalfSide === 'bottom') artboard.classList.add('split-side-bottom');
      }
      if (topWrap) {
        topWrap.style.display = 'block';
        topWrap.style.flex = `0 0 ${state.coverHalfRatio || 50}%`;
        if (topImg) {
          topImg.src = state.coverImgData;
          topImg.style.objectFit = fitStyle;
          topImg.style.objectPosition = `${posX}% ${posY}%`;
          topImg.style.transform = `scale(${scale})`;
          topImg.style.filter = filterStyle;
          topImg.style.opacity = imgOpacity;
        }
        if (topOverlay) topOverlay.style.background = `rgba(0, 0, 0, ${alphaOverlay})`;
      }
      header.style.minHeight = '100%';
    } else if (state.coverLayout === 'custom') {
      if (fullWrap) fullWrap.style.display = 'none';
      if (topWrap) topWrap.style.display = 'none';
      if (bottomWrap) bottomWrap.style.display = 'none';
      header.style.minHeight = '';
      if (customWrap) {
        customWrap.style.display = 'block';
        customWrap.style.left = `${state.coverCustomX || 50}%`;
        customWrap.style.top = `${state.coverCustomY || 35}%`;
        customWrap.style.width = `${state.coverCustomW || 240}px`;
        customWrap.style.height = `${state.coverCustomH || 180}px`;
        customWrap.style.transform = `translate(-50%, -50%) rotate(${state.coverCustomRotation || 0}deg)`;
        if (customImg) {
          customImg.src = state.coverImgData;
          customImg.style.objectFit = fitStyle;
          customImg.style.filter = filterStyle;
          customImg.style.opacity = imgOpacity;
        }
        if (customOverlay) customOverlay.style.background = `rgba(0, 0, 0, ${alphaOverlay})`;
      }
    }
  } else {
    if (fullWrap) fullWrap.style.display = 'none';
    if (topWrap) topWrap.style.display = 'none';
    if (bottomWrap) bottomWrap.style.display = 'none';
    if (customWrap) customWrap.style.display = 'none';
    header.style.minHeight = '';
  }

  textureOverlay.className = 'texture-overlay';
  if (state.texture === 'dots') textureOverlay.classList.add('texture-dots');
  else if (state.texture === 'glow') textureOverlay.classList.add('texture-glow');
  else if (state.texture === 'marble') textureOverlay.classList.add('texture-marble');
  else if (state.texture === 'stardust') textureOverlay.classList.add('texture-stardust');
  else textureOverlay.style.backgroundImage = 'none';

  const isDark = isDarkColor(state.bodyBg);
  if (isDark) {
    metaBox.style.background = 'rgba(255,255,255,0.06)';
    metaBox.style.borderColor = 'rgba(255,255,255,0.12)';
    metaBox.style.color = state.textColor;
    quote.style.color = 'rgba(255,255,255,0.75)';
    if (footerEl) footerEl.style.color = 'rgba(255,255,255,0.6)';
    if (refCodeEl) refCodeEl.style.color = 'rgba(255,255,255,0.75)';
  } else {
    metaBox.style.background = 'rgba(255,255,255,0.55)';
    metaBox.style.borderColor = 'rgba(0,0,0,0.08)';
    metaBox.style.color = state.textColor;
    quote.style.color = 'var(--text-secondary)';
    if (footerEl) footerEl.style.color = 'var(--text-muted)';
    if (refCodeEl) refCodeEl.style.color = 'var(--text-secondary)';
  }

  if (state.seal === 'none') {
    seal.style.display = 'none';
  } else {
    seal.style.display = 'inline-flex';
    if (state.seal === 'crown') seal.innerHTML = '👑 ROYAL GUEST PASS';
    else if (state.seal === 'verified') seal.innerHTML = '🛡️ ACCREDITED ADMISSION';
    else if (state.seal === 'diamond') seal.innerHTML = '💎 DIAMOND ADMISSION';
    else if (state.seal === 'ticket') seal.innerHTML = '🎟️ OFFICIAL ADMISSION';
    else seal.innerHTML = '⭐ OFFICIAL VIP PASS';
    seal.style.borderColor = `${state.accentColor}55`;
    seal.style.color = state.accentColor;
  }

  const logoContainer = document.getElementById('cardLogoContainer');
  const logoImg = document.getElementById('cardLogoImg');
  if (state.logoData) {
    logoImg.src = state.logoData;
    logoContainer.style.display = 'block';
  } else {
    logoContainer.style.display = 'none';
  }

  // Studio QR Code & Guest Category Badge Rendering
  const qrWrap = document.getElementById('studioQrInteractiveWrap');
  const qrCatBadge = document.getElementById('cardQrCategoryBadge');
  const dock = state.studioQrDock || 'bottom-center';
  if (qrWrap) {
    qrWrap.className = 'studio-qr-interactive-wrap dock-' + dock;
    if (dock === 'floating') {
      qrWrap.style.left = `${state.studioQrX !== undefined ? state.studioQrX : 50}%`;
      qrWrap.style.top = `${state.studioQrY !== undefined ? state.studioQrY : 78}%`;
      qrWrap.style.transform = `translate(-50%, -50%) rotate(${state.studioQrRotation || 0}deg)`;
    } else {
      qrWrap.style.left = '';
      qrWrap.style.top = '';
      qrWrap.style.transform = state.studioQrRotation ? `rotate(${state.studioQrRotation}deg)` : '';
    }
  }
  if (qrCatBadge) {
    const showCat = (state.showQrCategoryLabel !== false) && isFieldVisible('guestCategory');
    qrCatBadge.style.display = showCat ? 'inline-block' : 'none';
    const catLabel = state.guestCategory || 'VIP';
    if (document.activeElement !== qrCatBadge) {
      qrCatBadge.textContent = catLabel.includes('GUEST') || catLabel.includes('PASS') ? catLabel : `${catLabel} PASS`;
    }
    qrCatBadge.style.background = state.accentColor || '#B8955A';
    qrCatBadge.style.color = isDarkColor(state.accentColor || '#B8955A') ? '#FFFFFF' : '#18181B';
  }

  const qrBox = document.getElementById("cardQrBox");
  qrBox.innerHTML = "";
  const lightColor = state.qrBgTransparent ? "rgba(0,0,0,0)" : (state.canvaQrLight || "#FFFFFF");
  const qrFrame = document.getElementById('studioQrFrameBox') || document.querySelector('.card-qr-frame');
  if (qrFrame) {
    if (state.qrBgTransparent) {
      qrFrame.style.background = 'transparent';
      qrFrame.style.border = 'none';
      qrFrame.style.boxShadow = 'none';
      qrFrame.style.padding = '0';
    } else {
      qrFrame.style.background = state.canvaQrLight || '#FFFFFF';
      qrFrame.style.border = `1.5px solid ${state.accentColor || 'rgba(0,0,0,0.12)'}55`;
      qrFrame.style.boxShadow = '0 6px 18px rgba(0,0,0,0.12)';
      qrFrame.style.padding = '8px';
    }
  }
  const studioQrPx = parseInt(state.studioQrSize || state.qrSize || 104, 10);
  new QRCode(qrBox, {
    text: "<?= APP_URL ?>/pass.php?token=DEMO-TOKEN-" + encodeURIComponent(state.guestCategory || 'VIP') + "-01",
    width: studioQrPx,
    height: studioQrPx,
    colorDark: state.qrDark || state.canvaQrDark || "#09090B",
    colorLight: lightColor,
    correctLevel: QRCode.CorrectLevel.H
  });

  checkQrContrast();
  fitStudioTextIntelligently();
}

function setCardMode(mode) {
  state.cardMode = mode;
  const btnCanva = document.getElementById('btnModeCanva');
  const btnStudio = document.getElementById('btnModeStudio');
  const canvaView = document.getElementById('canvaCardArtboard');
  const studioView = document.getElementById('cardArtboard');
  const modeBadge = document.getElementById('canvasModeBadge');

  if (mode === 'canva') {
    if (btnCanva) btnCanva.className = 'segment-btn active';
    if (btnStudio) btnStudio.className = 'segment-btn';
    if (canvaView) canvaView.style.display = 'block';
    if (studioView) studioView.style.display = 'none';
    if (modeBadge) {
      modeBadge.textContent = 'CANVA CARD CLONER';
      modeBadge.style.background = 'rgba(255, 122, 0, 0.1)';
      modeBadge.style.color = '#FF7A00';
    }
    selectQr(true);
  } else {
    if (btnCanva) btnCanva.className = 'segment-btn';
    if (btnStudio) btnStudio.className = 'segment-btn active';
    if (canvaView) canvaView.style.display = 'none';
    if (studioView) studioView.style.display = 'block';
    if (modeBadge) {
      modeBadge.textContent = 'STUDIO TEMPLATE';
      modeBadge.style.background = 'linear-gradient(135deg,#FF7A00,#E96800)';
      modeBadge.style.color = '#FFFFFF';
    }
    selectQr(false);
  }
  renderCanvas();
  if (typeof zoomCanvasFit === 'function' && window.innerWidth <= 992) {
    setTimeout(zoomCanvasFit, 50);
  }
}

function setOrientation(mode) {
  state.orientation = mode;
  const btnPortrait = document.getElementById('btnPortrait');
  const btnLandscape = document.getElementById('btnLandscape');
  const dimInd = document.getElementById('canvasDimIndicator');
  if (btnPortrait) btnPortrait.className = mode === 'portrait' ? 'segment-btn active' : 'segment-btn';
  if (btnLandscape) btnLandscape.className = mode === 'landscape' ? 'segment-btn active' : 'segment-btn';
  if (dimInd) {
    dimInd.textContent = mode === 'portrait' 
      ? 'Portrait Pass: 420 × 640 px' 
      : 'Landscape Card: 620 × 400 px';
  }
  renderCanvas();
  if (typeof zoomCanvasFit === 'function') {
    setTimeout(zoomCanvasFit, 50);
  }
}

function handleCanvaBgUpload(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      state.canvaBgData = e.target.result;
      setCardMode('canva');
      document.getElementById('canvaBgThumb').src = e.target.result;
      document.getElementById('canvaBgPreviewWrapper').style.display = 'flex';
      renderCanvas();
      selectQr(true);
      showToast('Canva poster loaded! Click and drag the QR code to place anywhere.', 'success');
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function removeCanvaBg() {
  state.canvaBgData = null;
  document.getElementById('canvaBgPreviewWrapper').style.display = 'none';
  setCardMode('studio');
  showToast('Canva design removed, switched to Studio Presets', 'info');
}

function handleLogoUpload(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      state.logoData = e.target.result;
      document.getElementById('logoPreviewImg').src = e.target.result;
      document.getElementById('logoPreviewWrapper').style.display = 'flex';
      renderCanvas();
      showToast('Brand logo added to card canvas', 'success');
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function removeLogo() {
  state.logoData = null;
  document.getElementById('logoPreviewWrapper').style.display = 'none';
  renderCanvas();
  showToast('Logo removed from canvas', 'info');
}

// ============================================================
// Cover Photo 5-Layout Engine & Direct On-Canvas Controls
// ============================================================
function handleCoverUpload(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = new Image();
      img.onload = function() {
        const maxDim = 1200;
        let w = img.width;
        let h = img.height;
        if (w > maxDim || h > maxDim) {
          if (w > h) {
            h = Math.round((h * maxDim) / w);
            w = maxDim;
          } else {
            w = Math.round((w * maxDim) / h);
            h = maxDim;
          }
        }
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);
        state.coverImgData = canvas.toDataURL('image/jpeg', 0.88);
        if (!state.coverLayout) state.coverLayout = 'top';
        setCardMode('studio');
        syncCoverControls();
        renderCanvas();
        saveHistoryState();
        showToast('Cover photo uploaded successfully! Select layout & adjust.', 'success');
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

function removeCoverPhoto() {
  state.coverImgData = null;
  syncCoverControls();
  renderCanvas();
  saveHistoryState();
  showToast('Cover photo removed, reverted to design background', 'info');
}

function setCoverLayout(layout) {
  state.coverLayout = layout;
  
  document.querySelectorAll('.photo-layout-card').forEach(card => card.classList.remove('active'));
  const activeCardMap = {
    'full': 'optLayoutFull',
    'top': 'optLayoutTop',
    'bottom': 'optLayoutBottom',
    'half': 'optLayoutHalf',
    'custom': 'optLayoutCustom'
  };
  const activeEl = document.getElementById(activeCardMap[layout]);
  if (activeEl) activeEl.classList.add('active');

  const names = {
    'full': 'Full Card Background',
    'top': 'Top Section',
    'bottom': 'Bottom Section',
    'half': 'Half Card Split',
    'custom': 'Custom Position'
  };
  const statusEl = document.getElementById('coverLayoutStatusText');
  if (statusEl) statusEl.textContent = `Layout: ${names[layout] || layout}`;

  const topBox = document.getElementById('boxTopControls');
  const bottomBox = document.getElementById('boxBottomControls');
  const halfBox = document.getElementById('boxHalfControls');
  const fullBox = document.getElementById('boxFullControls');
  const customBox = document.getElementById('boxCustomControls');

  if (topBox) topBox.style.display = (layout === 'top') ? 'block' : 'none';
  if (bottomBox) bottomBox.style.display = (layout === 'bottom') ? 'block' : 'none';
  if (halfBox) halfBox.style.display = (layout === 'half') ? 'block' : 'none';
  if (fullBox) fullBox.style.display = (layout === 'full') ? 'block' : 'none';
  if (customBox) customBox.style.display = (layout === 'custom') ? 'block' : 'none';

  renderCanvas();
  saveHistoryState();
  showToast(`Cover photo layout set to: ${names[layout] || layout}`, 'info');
}

function onCoverHeightChange(val) {
  state.coverHeight = parseInt(val, 10);
  const el1 = document.getElementById('valCoverHeight');
  if (el1) el1.textContent = `${state.coverHeight}px`;
  const el2 = document.getElementById('valCoverBottomHeight');
  if (el2) el2.textContent = `${state.coverHeight}px`;
  renderCanvas();
}

function onCoverPositionYChange(val) {
  state.coverPositionY = parseInt(val, 10);
  const el1 = document.getElementById('valCoverPositionY');
  if (el1) el1.textContent = `${state.coverPositionY}%`;
  const el2 = document.getElementById('valCoverBottomPositionY');
  if (el2) el2.textContent = `${state.coverPositionY}%`;
  renderCanvas();
}

function onCoverPositionXChange(val) {
  state.coverPositionX = parseInt(val, 10);
  const el1 = document.getElementById('valCoverPositionX');
  if (el1) el1.textContent = `${state.coverPositionX}%`;
  const el2 = document.getElementById('valCoverBottomPositionX');
  if (el2) el2.textContent = `${state.coverPositionX}%`;
  renderCanvas();
}

function onCoverScaleChange(val) {
  state.coverScale = parseInt(val, 10);
  const el = document.getElementById('valCoverScale');
  if (el) el.textContent = `${state.coverScale}%`;
  renderCanvas();
}

function onCoverOverlayChange(val) {
  state.coverOverlay = parseInt(val, 10);
  const el = document.getElementById('valCoverOverlay');
  if (el) el.textContent = `${state.coverOverlay}%`;
  renderCanvas();
}

function setCoverCropFit(fit) {
  state.coverCropFit = fit;
  ['cover', 'contain', 'fill'].forEach(f => {
    const btn = document.getElementById(`btnCrop${f.charAt(0).toUpperCase() + f.slice(1)}`);
    if (btn) btn.classList.toggle('active', f === fit);
  });
  renderCanvas();
  saveHistoryState();
}

function onCoverBrightnessChange(val) {
  state.coverBrightness = parseInt(val, 10);
  const el = document.getElementById('valCoverBrightness');
  if (el) el.textContent = `${state.coverBrightness}%`;
  renderCanvas();
}

function onCoverContrastChange(val) {
  state.coverContrast = parseInt(val, 10);
  const el = document.getElementById('valCoverContrast');
  if (el) el.textContent = `${state.coverContrast}%`;
  renderCanvas();
}

function onCoverOpacityChange(val) {
  state.coverOpacity = parseInt(val, 10);
  const el = document.getElementById('valCoverOpacity');
  if (el) el.textContent = `${state.coverOpacity}%`;
  renderCanvas();
}

function setCoverTextContrast(contrast) {
  state.coverTextContrast = contrast;
  const btnGlass = document.getElementById('btnContrastGlass');
  const btnShadow = document.getElementById('btnContrastShadow');
  const btnNone = document.getElementById('btnContrastNone');
  if (btnGlass) btnGlass.classList.toggle('active', contrast === 'glass');
  if (btnShadow) btnShadow.classList.toggle('active', contrast === 'shadow');
  if (btnNone) btnNone.classList.toggle('active', contrast === 'none');
  renderCanvas();
  saveHistoryState();
}

function setCoverHalfSide(side) {
  state.coverHalfSide = side;
  const btnLeft = document.getElementById('btnHalfLeft');
  const btnRight = document.getElementById('btnHalfRight');
  if (btnLeft) btnLeft.classList.toggle('active', side === 'left');
  if (btnRight) btnRight.classList.toggle('active', side === 'right');
  renderCanvas();
  saveHistoryState();
}

function onCoverHalfRatioChange(val) {
  state.coverHalfRatio = parseInt(val, 10);
  const el = document.getElementById('valCoverHalfRatio');
  if (el) el.textContent = `${state.coverHalfRatio}%`;
  renderCanvas();
}

function onCustomPosChange(axis, val) {
  const num = parseFloat(val);
  if (axis === 'x') state.coverCustomX = num;
  if (axis === 'y') state.coverCustomY = num;
  renderCanvas();
}

function onCustomSizeChange(dim, val) {
  const num = parseInt(val, 10);
  if (dim === 'w') state.coverCustomW = num;
  if (dim === 'h') state.coverCustomH = num;
  renderCanvas();
}

function onCustomRotChange(val) {
  state.coverCustomRotation = parseInt(val, 10);
  const el = document.getElementById('valCustomRot');
  if (el) el.textContent = `${state.coverCustomRotation}°`;
  renderCanvas();
}

function centerCustomCoverX() {
  state.coverCustomX = 50;
  const inp = document.getElementById('cfgCustomX');
  if (inp) inp.value = 50;
  renderCanvas();
  saveHistoryState();
}

function centerCustomCoverY() {
  state.coverCustomY = 50;
  const inp = document.getElementById('cfgCustomY');
  if (inp) inp.value = 50;
  renderCanvas();
  saveHistoryState();
}

function resetCustomCoverBox() {
  state.coverCustomX = 50;
  state.coverCustomY = 35;
  state.coverCustomW = 240;
  state.coverCustomH = 180;
  state.coverCustomRotation = 0;
  syncCoverControls();
  renderCanvas();
  saveHistoryState();
  showToast('Custom photo position and size reset', 'info');
}

function resetCoverAdjustments() {
  state.coverScale = 100;
  state.coverCropFit = 'cover';
  state.coverBrightness = 100;
  state.coverContrast = 100;
  state.coverOpacity = 100;
  state.coverOverlay = 40;
  state.coverPositionY = 50;
  state.coverPositionX = 50;
  state.coverTextContrast = 'glass';
  syncCoverControls();
  renderCanvas();
  saveHistoryState();
  showToast('Cover photo adjustments reset to defaults', 'info');
}

// Interactive Pointer Drag on Cover Photo (Top / Bottom section)
let isCoverDragging = false;
let coverDragSection = 'top';
let coverDragStartX = 0;
let coverDragStartY = 0;
let coverStartPosX = 50;
let coverStartPosY = 50;

function onCoverPointerDown(e, section = 'top') {
  if (e.target.isContentEditable || e.target.closest('[contenteditable="true"]')) return;
  isCoverDragging = true;
  coverDragSection = section;
  coverDragStartX = e.clientX;
  coverDragStartY = e.clientY;
  coverStartPosX = state.coverPositionX !== undefined ? state.coverPositionX : 50;
  coverStartPosY = state.coverPositionY !== undefined ? state.coverPositionY : 50;
  window.addEventListener('pointermove', onCoverPointerMove);
  window.addEventListener('pointerup', onCoverPointerUp);
  e.preventDefault();
}

function onCoverPointerMove(e) {
  if (!isCoverDragging) return;
  const deltaX = e.clientX - coverDragStartX;
  const deltaY = e.clientY - coverDragStartY;
  
  let newX = Math.round(coverStartPosX - (deltaX * 0.35));
  let newY = Math.round(coverStartPosY - (deltaY * 0.35));
  newX = Math.max(0, Math.min(100, newX));
  newY = Math.max(0, Math.min(100, newY));
  
  state.coverPositionX = newX;
  state.coverPositionY = newY;
  
  const imgId = coverDragSection === 'bottom' ? 'cardCoverBottomImg' : 'cardCoverImg';
  const coverImg = document.getElementById(imgId);
  if (coverImg) {
    coverImg.style.objectPosition = `${newX}% ${newY}%`;
    coverImg.style.transformOrigin = `${newX}% ${newY}%`;
  }
  
  ['cfgCoverPositionY', 'cfgCoverBottomPositionY'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = newY;
  });
  ['valCoverPositionY', 'valCoverBottomPositionY'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = `${newY}%`;
  });
  ['cfgCoverPositionX', 'cfgCoverBottomPositionX'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = newX;
  });
  ['valCoverPositionX', 'valCoverBottomPositionX'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = `${newX}%`;
  });
}

function onCoverPointerUp() {
  if (isCoverDragging) {
    isCoverDragging = false;
    window.removeEventListener('pointermove', onCoverPointerMove);
    window.removeEventListener('pointerup', onCoverPointerUp);
    saveHistoryState();
  }
}

// Custom Floating Cover Dragging & Resizing & Rotation Handlers
let isCustomCoverDragging = false;
let customDragStartX = 0;
let customDragStartY = 0;
let customStartPosX = 50;
let customStartPosY = 35;

function onCustomCoverPointerDown(e) {
  if (e.target.classList.contains('custom-cover-handle-tl') ||
      e.target.classList.contains('custom-cover-handle-tr') ||
      e.target.classList.contains('custom-cover-handle-bl') ||
      e.target.classList.contains('custom-cover-handle-br') ||
      e.target.classList.contains('custom-cover-rotate-handle')) {
    return;
  }
  isCustomCoverDragging = true;
  customDragStartX = e.clientX;
  customDragStartY = e.clientY;
  customStartPosX = state.coverCustomX !== undefined ? state.coverCustomX : 50;
  customStartPosY = state.coverCustomY !== undefined ? state.coverCustomY : 35;
  window.addEventListener('pointermove', onCustomCoverPointerMove);
  window.addEventListener('pointerup', onCustomCoverPointerUp);
  e.preventDefault();
  e.stopPropagation();
}

function onCustomCoverPointerMove(e) {
  if (!isCustomCoverDragging) return;
  const artboard = document.getElementById('cardArtboard');
  if (!artboard) return;
  const rect = artboard.getBoundingClientRect();
  const deltaX = (e.clientX - customDragStartX) / rect.width * 100;
  const deltaY = (e.clientY - customDragStartY) / rect.height * 100;
  
  let newX = Math.round(customStartPosX + deltaX);
  let newY = Math.round(customStartPosY + deltaY);
  newX = Math.max(0, Math.min(100, newX));
  newY = Math.max(0, Math.min(100, newY));
  
  state.coverCustomX = newX;
  state.coverCustomY = newY;
  
  const wrap = document.getElementById('cardCoverCustomWrap');
  if (wrap) {
    wrap.style.left = `${newX}%`;
    wrap.style.top = `${newY}%`;
  }
  const inpX = document.getElementById('cfgCustomX');
  if (inpX) inpX.value = newX;
  const inpY = document.getElementById('cfgCustomY');
  if (inpY) inpY.value = newY;
}

function onCustomCoverPointerUp() {
  if (isCustomCoverDragging) {
    isCustomCoverDragging = false;
    window.removeEventListener('pointermove', onCustomCoverPointerMove);
    window.removeEventListener('pointerup', onCustomCoverPointerUp);
    saveHistoryState();
  }
}

let isCustomResizing = false;
let resizeHandleType = 'br';
let resizeStartX = 0;
let resizeStartY = 0;
let resizeStartW = 240;
let resizeStartH = 180;

function onCustomCoverResizeDown(e, handle) {
  isCustomResizing = true;
  resizeHandleType = handle;
  resizeStartX = e.clientX;
  resizeStartY = e.clientY;
  resizeStartW = state.coverCustomW || 240;
  resizeStartH = state.coverCustomH || 180;
  window.addEventListener('pointermove', onCustomCoverResizeMove);
  window.addEventListener('pointerup', onCustomCoverResizeUp);
  e.preventDefault();
  e.stopPropagation();
}

function onCustomCoverResizeMove(e) {
  if (!isCustomResizing) return;
  let deltaX = e.clientX - resizeStartX;
  let deltaY = e.clientY - resizeStartY;
  
  if (resizeHandleType === 'tl') { deltaX = -deltaX; deltaY = -deltaY; }
  else if (resizeHandleType === 'tr') { deltaY = -deltaY; }
  else if (resizeHandleType === 'bl') { deltaX = -deltaX; }
  
  let newW = Math.max(80, Math.min(500, Math.round(resizeStartW + deltaX)));
  let newH = Math.max(60, Math.min(500, Math.round(resizeStartH + deltaY)));
  
  state.coverCustomW = newW;
  state.coverCustomH = newH;
  
  const wrap = document.getElementById('cardCoverCustomWrap');
  if (wrap) {
    wrap.style.width = `${newW}px`;
    wrap.style.height = `${newH}px`;
  }
  const inpW = document.getElementById('cfgCustomW');
  if (inpW) inpW.value = newW;
  const inpH = document.getElementById('cfgCustomH');
  if (inpH) inpH.value = newH;
}

function onCustomCoverResizeUp() {
  if (isCustomResizing) {
    isCustomResizing = false;
    window.removeEventListener('pointermove', onCustomCoverResizeMove);
    window.removeEventListener('pointerup', onCustomCoverResizeUp);
    saveHistoryState();
  }
}

let isCustomRotating = false;
let rotCenter = { x: 0, y: 0 };

function onCustomCoverRotateDown(e) {
  isCustomRotating = true;
  const wrap = document.getElementById('cardCoverCustomWrap');
  if (wrap) {
    const rect = wrap.getBoundingClientRect();
    rotCenter = { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
  }
  window.addEventListener('pointermove', onCustomCoverRotateMove);
  window.addEventListener('pointerup', onCustomCoverRotateUp);
  e.preventDefault();
  e.stopPropagation();
}

function onCustomCoverRotateMove(e) {
  if (!isCustomRotating) return;
  const radians = Math.atan2(e.clientX - rotCenter.x, -(e.clientY - rotCenter.y));
  let deg = Math.round(radians * (180 / Math.PI));
  if (deg < -180) deg += 360;
  if (deg > 180) deg -= 360;
  
  state.coverCustomRotation = deg;
  const wrap = document.getElementById('cardCoverCustomWrap');
  if (wrap) {
    wrap.style.transform = `translate(-50%, -50%) rotate(${deg}deg)`;
  }
  const inpRot = document.getElementById('cfgCustomRot');
  if (inpRot) inpRot.value = deg;
  const valRot = document.getElementById('valCustomRot');
  if (valRot) valRot.textContent = `${deg}°`;
}

function onCustomCoverRotateUp() {
  if (isCustomRotating) {
    isCustomRotating = false;
    window.removeEventListener('pointermove', onCustomCoverRotateMove);
    window.removeEventListener('pointerup', onCustomCoverRotateUp);
    saveHistoryState();
  }
}

function syncCoverControls() {
  const preview = document.getElementById('cardCoverPreviewWrapper');
  const btnRemove = document.getElementById('btnRemoveCover');
  const thumb = document.getElementById('cardCoverThumb');
  const layout = state.coverLayout || 'top';

  if (state.coverImgData) {
    if (preview) preview.style.display = 'flex';
    if (btnRemove) btnRemove.style.display = 'inline-flex';
    if (thumb) thumb.src = state.coverImgData;
    
    document.querySelectorAll('.photo-layout-card').forEach(card => card.classList.remove('active'));
    const activeCardMap = {
      'full': 'optLayoutFull',
      'top': 'optLayoutTop',
      'bottom': 'optLayoutBottom',
      'half': 'optLayoutHalf',
      'custom': 'optLayoutCustom'
    };
    const activeEl = document.getElementById(activeCardMap[layout]);
    if (activeEl) activeEl.classList.add('active');

    const names = {
      'full': 'Full Card Background',
      'top': 'Top Section',
      'bottom': 'Bottom Section',
      'half': 'Half Card Split',
      'custom': 'Custom Position'
    };
    const statusEl = document.getElementById('coverLayoutStatusText');
    if (statusEl) statusEl.textContent = `Layout: ${names[layout] || layout}`;

    const topBox = document.getElementById('boxTopControls');
    const bottomBox = document.getElementById('boxBottomControls');
    const halfBox = document.getElementById('boxHalfControls');
    const fullBox = document.getElementById('boxFullControls');
    const customBox = document.getElementById('boxCustomControls');

    if (topBox) topBox.style.display = (layout === 'top') ? 'block' : 'none';
    if (bottomBox) bottomBox.style.display = (layout === 'bottom') ? 'block' : 'none';
    if (halfBox) halfBox.style.display = (layout === 'half') ? 'block' : 'none';
    if (fullBox) fullBox.style.display = (layout === 'full') ? 'block' : 'none';
    if (customBox) customBox.style.display = (layout === 'custom') ? 'block' : 'none';

    const heightInp = document.getElementById('cfgCoverHeight');
    const posYInp = document.getElementById('cfgCoverPositionY');
    const posXInp = document.getElementById('cfgCoverPositionX');
    if (heightInp) heightInp.value = state.coverHeight || 190;
    if (posYInp) posYInp.value = state.coverPositionY !== undefined ? state.coverPositionY : 50;
    if (posXInp) posXInp.value = state.coverPositionX !== undefined ? state.coverPositionX : 50;
    if (document.getElementById('valCoverHeight')) document.getElementById('valCoverHeight').textContent = `${state.coverHeight || 190}px`;
    if (document.getElementById('valCoverPositionY')) document.getElementById('valCoverPositionY').textContent = `${state.coverPositionY !== undefined ? state.coverPositionY : 50}%`;
    if (document.getElementById('valCoverPositionX')) document.getElementById('valCoverPositionX').textContent = `${state.coverPositionX !== undefined ? state.coverPositionX : 50}%`;

    const bHeightInp = document.getElementById('cfgCoverBottomHeight');
    const bPosYInp = document.getElementById('cfgCoverBottomPositionY');
    const bPosXInp = document.getElementById('cfgCoverBottomPositionX');
    if (bHeightInp) bHeightInp.value = state.coverHeight || 190;
    if (bPosYInp) bPosYInp.value = state.coverPositionY !== undefined ? state.coverPositionY : 50;
    if (bPosXInp) bPosXInp.value = state.coverPositionX !== undefined ? state.coverPositionX : 50;
    if (document.getElementById('valCoverBottomHeight')) document.getElementById('valCoverBottomHeight').textContent = `${state.coverHeight || 190}px`;
    if (document.getElementById('valCoverBottomPositionY')) document.getElementById('valCoverBottomPositionY').textContent = `${state.coverPositionY !== undefined ? state.coverPositionY : 50}%`;
    if (document.getElementById('valCoverBottomPositionX')) document.getElementById('valCoverBottomPositionX').textContent = `${state.coverPositionX !== undefined ? state.coverPositionX : 50}%`;

    const side = state.coverHalfSide || 'left';
    const btnLeft = document.getElementById('btnHalfLeft');
    const btnRight = document.getElementById('btnHalfRight');
    if (btnLeft) btnLeft.classList.toggle('active', side === 'left');
    if (btnRight) btnRight.classList.toggle('active', side === 'right');
    const halfRatioInp = document.getElementById('cfgCoverHalfRatio');
    if (halfRatioInp) halfRatioInp.value = state.coverHalfRatio || 50;
    if (document.getElementById('valCoverHalfRatio')) document.getElementById('valCoverHalfRatio').textContent = `${state.coverHalfRatio || 50}%`;

    const contrast = state.coverTextContrast || 'glass';
    const btnGlass = document.getElementById('btnContrastGlass');
    const btnShadow = document.getElementById('btnContrastShadow');
    const btnNone = document.getElementById('btnContrastNone');
    if (btnGlass) btnGlass.classList.toggle('active', contrast === 'glass');
    if (btnShadow) btnShadow.classList.toggle('active', contrast === 'shadow');
    if (btnNone) btnNone.classList.toggle('active', contrast === 'none');

    const custX = document.getElementById('cfgCustomX');
    const custY = document.getElementById('cfgCustomY');
    const custW = document.getElementById('cfgCustomW');
    const custH = document.getElementById('cfgCustomH');
    const custRot = document.getElementById('cfgCustomRot');
    if (custX) custX.value = state.coverCustomX !== undefined ? state.coverCustomX : 50;
    if (custY) custY.value = state.coverCustomY !== undefined ? state.coverCustomY : 35;
    if (custW) custW.value = state.coverCustomW || 240;
    if (custH) custH.value = state.coverCustomH || 180;
    if (custRot) custRot.value = state.coverCustomRotation || 0;
    if (document.getElementById('valCustomRot')) document.getElementById('valCustomRot').textContent = `${state.coverCustomRotation || 0}°`;

    const scaleInp = document.getElementById('cfgCoverScale');
    const overlayInp = document.getElementById('cfgCoverOverlay');
    const brightInp = document.getElementById('cfgCoverBrightness');
    const contInp = document.getElementById('cfgCoverContrast');
    const opacInp = document.getElementById('cfgCoverOpacity');

    if (scaleInp) scaleInp.value = state.coverScale || 100;
    if (overlayInp) overlayInp.value = state.coverOverlay !== undefined ? state.coverOverlay : 40;
    if (brightInp) brightInp.value = state.coverBrightness || 100;
    if (contInp) contInp.value = state.coverContrast || 100;
    if (opacInp) opacInp.value = state.coverOpacity !== undefined ? state.coverOpacity : 100;

    if (document.getElementById('valCoverScale')) document.getElementById('valCoverScale').textContent = `${state.coverScale || 100}%`;
    if (document.getElementById('valCoverOverlay')) document.getElementById('valCoverOverlay').textContent = `${state.coverOverlay !== undefined ? state.coverOverlay : 40}%`;
    if (document.getElementById('valCoverBrightness')) document.getElementById('valCoverBrightness').textContent = `${state.coverBrightness || 100}%`;
    if (document.getElementById('valCoverContrast')) document.getElementById('valCoverContrast').textContent = `${state.coverContrast || 100}%`;
    if (document.getElementById('valCoverOpacity')) document.getElementById('valCoverOpacity').textContent = `${state.coverOpacity !== undefined ? state.coverOpacity : 100}%`;

    const fit = state.coverCropFit || 'cover';
    ['cover', 'contain', 'fill'].forEach(f => {
      const btn = document.getElementById(`btnCrop${f.charAt(0).toUpperCase() + f.slice(1)}`);
      if (btn) btn.classList.toggle('active', f === fit);
    });
  } else {
    if (preview) preview.style.display = 'none';
    if (btnRemove) btnRemove.style.display = 'none';
    document.querySelectorAll('.photo-layout-card').forEach(card => card.classList.remove('active'));
    const topBox = document.getElementById('boxTopControls');
    const bottomBox = document.getElementById('boxBottomControls');
    const halfBox = document.getElementById('boxHalfControls');
    const fullBox = document.getElementById('boxFullControls');
    const customBox = document.getElementById('boxCustomControls');
    if (topBox) topBox.style.display = 'none';
    if (bottomBox) bottomBox.style.display = 'none';
    if (halfBox) halfBox.style.display = 'none';
    if (fullBox) fullBox.style.display = 'none';
    if (customBox) customBox.style.display = 'none';
  }
}

function updateScales() {
  state.titleScale = parseInt(document.getElementById('cfgTitleScale').value, 10);
  state.guestScale = parseInt(document.getElementById('cfgGuestScale').value, 10);
  document.getElementById('valTitleScale').textContent = state.titleScale + '%';
  document.getElementById('valGuestScale').textContent = state.guestScale + '%';
}

function isDarkColor(hex) {
  if (!hex || hex[0] !== '#') return false;
  const rgb = parseInt(hex.substring(1), 16);
  const r = (rgb >> 16) & 0xff;
  const g = (rgb >>  8) & 0xff;
  const b = (rgb >>  0) & 0xff;
  const luma = 0.2126 * r + 0.7152 * g + 0.0722 * b;
  return luma < 90;
}

function syncStateToInputs() {
  const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
  const setChk = (id, val) => { const el = document.getElementById(id); if (el) el.checked = !!val; };

  setVal('cfgBadge', state.badge || 'TOGETHER WITH THEIR FAMILIES');
  setVal('cfgHostNames', state.hostNames || '');
  setVal('cfgCouple', state.sampleCouple !== undefined ? state.sampleCouple : 'Amelia & Sebastian');
  setVal('cfgCelebrantName', state.celebrantName || '');
  setVal('cfgTitle', state.title || '');
  setVal('cfgSubtitle', state.subtitle || '');
  setVal('cfgGuestOfHonour', state.guestOfHonour || '');
  setVal('cfgGuest', state.guest || '');
  setVal('cfgQuote', state.quote || '');
  setVal('cfgDate', state.date || '');
  setVal('cfgTime', state.time || '');
  setVal('cfgEndTime', state.endTime || '');
  setVal('cfgVenue', state.venue || '');
  setVal('cfgFullAddress', state.fullAddress || '');
  setVal('cfgSeat', state.seat || '');
  setVal('cfgRsvp', state.rsvp || '');
  setVal('cfgContactInfo', state.contactInfo || '');
  setVal('cfgAdditionalInstructions', state.additionalInstructions || '');
  setVal('cfgEventWebsite', state.eventWebsite || '');
  setVal('cfgSocialMedia', state.socialMedia || '');
  setVal('cfgFooter', state.footer || '');

  // Sync 19 field visibility checkboxes
  const vf = state.visibleFields || {};
  Object.keys(vf).forEach(k => {
    setChk('chkField_' + k, vf[k] !== false);
  });

  // Sync Dedicated Dress Code Controls
  setChk('cfgShowDressCode', state.showDressCode !== false && vf.dressCode !== false);
  setVal('cfgDressCode', state.dressCode || 'Black Tie Optional');
  setVal('cfgDressCodeNote', state.dressCodeNote || '');
  setVal('cfgDressCodeStyle', state.dressCodeStyle || 'luxury_serif');
  setVal('cfgDressCodeIcon', state.dressCodeIcon || 'tuxedo');
  setVal('cfgDressCodeFont', state.dressCodeFont || '');
  setVal('cfgDressCodeColor', state.dressCodeColor || state.accentColor || '#B8955A');
  setVal('cfgDressCodeScale', state.dressCodeScale || 100);
  setVal('cfgDressCodeWidth', state.dressCodeWidth || 88);
  setVal('cfgDressCodeOffsetY', state.dressCodeOffsetY || 0);
  if (document.getElementById('valDressCodeScale')) document.getElementById('valDressCodeScale').textContent = (state.dressCodeScale || 100) + '%';
  if (document.getElementById('valDressCodeWidth')) document.getElementById('valDressCodeWidth').textContent = (state.dressCodeWidth || 88) + '%';
  if (document.getElementById('valDressCodeOffsetY')) document.getElementById('valDressCodeOffsetY').textContent = (state.dressCodeOffsetY || 0) + 'px';

  // Sync Studio QR & Guest Category Controls
  setVal('cfgStudioQrSize', state.studioQrSize || state.qrSize || 104);
  if (document.getElementById('valStudioQrSize')) document.getElementById('valStudioQrSize').textContent = (state.studioQrSize || state.qrSize || 104) + 'px';
  setVal('cfgStudioQrRot', state.studioQrRotation || 0);
  if (document.getElementById('valStudioQrRot')) document.getElementById('valStudioQrRot').textContent = (state.studioQrRotation || 0) + '°';
  setVal('cfgStudioQrX', state.studioQrX !== undefined ? state.studioQrX : 50);
  setVal('cfgStudioQrY', state.studioQrY !== undefined ? state.studioQrY : 78);
  if (document.getElementById('valStudioQrX')) document.getElementById('valStudioQrX').textContent = (state.studioQrX !== undefined ? state.studioQrX : 50) + '%';
  if (document.getElementById('valStudioQrY')) document.getElementById('valStudioQrY').textContent = (state.studioQrY !== undefined ? state.studioQrY : 78) + '%';
  const dock = state.studioQrDock || 'bottom-center';
  ['bottom-center', 'bottom-right', 'bottom-left', 'floating'].forEach(d => {
    const mapId = { 'bottom-center': 'btnQrDockCenter', 'bottom-right': 'btnQrDockRight', 'bottom-left': 'btnQrDockLeft', 'floating': 'btnQrDockFloat' }[d];
    const b = document.getElementById(mapId);
    if (b) {
      b.classList.toggle('btn-primary', d === dock);
      b.classList.toggle('btn-secondary', d !== dock);
    }
  });
  const floatBox = document.getElementById('studioQrFloatingCoords');
  if (floatBox) floatBox.style.display = (dock === 'floating') ? 'grid' : 'none';
  setChk('cfgShowQrCategoryLabel', state.showQrCategoryLabel !== false);
  const stdCats = ['VIP', 'VVIP', 'REGULAR', 'FAMILY', 'SPEAKER', 'PRESS'];
  if (stdCats.includes(state.guestCategory)) {
    setVal('cfgGuestCategorySelect', state.guestCategory);
    if (document.getElementById('cfgCustomGuestCategory')) document.getElementById('cfgCustomGuestCategory').style.display = 'none';
  } else {
    setVal('cfgGuestCategorySelect', 'CUSTOM');
    const custCat = document.getElementById('cfgCustomGuestCategory');
    if (custCat) { custCat.style.display = 'block'; custCat.value = state.guestCategory || ''; }
  }

  setVal('cfgFont', state.fontFamily || "'Cormorant Garamond', serif");
  setVal('cfgScriptFont', state.scriptFont || "'Great Vibes', cursive");
  setVal('cfgTitleScale', state.titleScale || 100);
  setVal('cfgGuestScale', state.guestScale || 100);
  updateScales();
  setVal('cfgHeaderColor1', state.headerColor1 || '#FF7A00');
  setVal('cfgHeaderColor2', state.headerColor2 || '#E96800');
  setVal('cfgHeadingColor', state.headingColor || '#FFFFFF');
  setVal('cfgBodyBg', state.bodyBg || '#FFFFFF');
  setVal('cfgTextColor', state.textColor || '#09090B');
  setVal('cfgBorderColor', state.borderColor || '#E4E4E7');
  setVal('cfgAccentColor', state.accentColor || '#FF7A00');
  setVal('cfgBorderFrame', state.borderFrame || 'none');
  setVal('cfgSeal', state.seal || 'ribbon');
  setVal('cfgTexture', state.texture || 'none');
  setChk('cfgShowArtwork', state.showArtwork !== false);
  if (document.getElementById('cfgArtworkOpacity')) {
    const op = state.artworkOpacity !== undefined ? state.artworkOpacity : 100;
    document.getElementById('cfgArtworkOpacity').value = op;
    if (document.getElementById('valArtworkOpacity')) document.getElementById('valArtworkOpacity').textContent = op + '%';
  }
  setVal('cfgHeaderStyle', state.headerStyle || 'unified');

  if (document.getElementById('cfgLetterSpacing')) {
    document.getElementById('cfgLetterSpacing').value = state.letterSpacing || 0;
    document.getElementById('valLetterSpacing').textContent = state.letterSpacing ? `${state.letterSpacing}px` : 'Normal';
  }
  if (document.getElementById('cfgLineHeight')) {
    document.getElementById('cfgLineHeight').value = state.lineHeight || 12;
    document.getElementById('valLineHeight').textContent = `${((state.lineHeight || 12) / 10).toFixed(1)}x`;
  }
  updateFormatButtonStyles();

  // Canva QR Controls
  syncPositionInputs();
  syncSizeInputs();
  syncRotationInputs();
  setVal('cfgQrBadgeStyle', state.qrBadgeStyle || 'badge-glass');
  setVal('cfgCanvaQrDark', state.canvaQrDark || '#09090B');
  setVal('cfgCanvaQrLight', state.canvaQrLight || '#FFFFFF');
  setChk('cfgQrBgTransparent', !!state.qrBgTransparent);
  if (document.getElementById('cfgCanvaQrLight')) {
    document.getElementById('cfgCanvaQrLight').disabled = !!state.qrBgTransparent;
    document.getElementById('cfgCanvaQrLight').style.opacity = state.qrBgTransparent ? '0.4' : '1';
  }
  if (document.getElementById('qrBgTransparentHint')) {
    document.getElementById('qrBgTransparentHint').style.display = state.qrBgTransparent ? 'block' : 'none';
  }
  setVal('cfgBadgeTextColor', state.badgeTextColor || (state.qrBadgeStyle === 'badge-dark' ? '#FFFFFF' : '#09090B'));
  setVal('cfgQrPadding', state.qrPadding || 10);
  if (document.getElementById('valQrPadding')) document.getElementById('valQrPadding').textContent = `${state.qrPadding || 10}px`;
  setVal('cfgQrRadius', state.qrRadius || 18);
  if (document.getElementById('valQrRadius')) document.getElementById('valQrRadius').textContent = `${state.qrRadius || 18}px`;
  setChk('cfgQrLocked', !!state.qrLocked);
  if (document.getElementById('valQrLockedText')) document.getElementById('valQrLockedText').textContent = state.qrLocked ? '🔒 Locked' : 'Unlocked';
  setChk('cfgShowAttendee', state.showAttendee !== false);
  setChk('cfgShowRef', state.showRef !== false);
  setChk('cfgShowTier', state.showTier !== false);

  if (state.canvaBgData && document.getElementById('canvaBgThumb')) {
    document.getElementById('canvaBgThumb').src = state.canvaBgData;
    document.getElementById('canvaBgPreviewWrapper').style.display = 'flex';
  }

  // Cover photo & multi-photo layers & decorative objects sync
  syncCoverControls();
  renderPhotoLayersSidebarList();
  renderDecorObjectsSidebarList();
}

function pullInputsToState() {
  const getVal = (id, fallback = '') => { const el = document.getElementById(id); return el ? el.value : fallback; };

  state.badge = getVal('cfgBadge', state.badge);
  state.hostNames = getVal('cfgHostNames', state.hostNames);
  state.sampleCouple = getVal('cfgCouple', state.sampleCouple);
  state.celebrantName = getVal('cfgCelebrantName', state.celebrantName);
  state.title = getVal('cfgTitle', state.title);
  state.subtitle = getVal('cfgSubtitle', state.subtitle);
  state.guestOfHonour = getVal('cfgGuestOfHonour', state.guestOfHonour);
  state.guest = getVal('cfgGuest', state.guest);
  state.quote = getVal('cfgQuote', state.quote);
  state.date = getVal('cfgDate', state.date);
  state.time = getVal('cfgTime', state.time);
  state.endTime = getVal('cfgEndTime', state.endTime);
  state.venue = getVal('cfgVenue', state.venue);
  state.fullAddress = getVal('cfgFullAddress', state.fullAddress);
  state.seat = getVal('cfgSeat', state.seat);
  state.rsvp = getVal('cfgRsvp', state.rsvp);
  state.contactInfo = getVal('cfgContactInfo', state.contactInfo);
  state.additionalInstructions = getVal('cfgAdditionalInstructions', state.additionalInstructions);
  state.eventWebsite = getVal('cfgEventWebsite', state.eventWebsite);
  state.socialMedia = getVal('cfgSocialMedia', state.socialMedia);
  state.footer = getVal('cfgFooter', state.footer);

  // Pull Dress Code inputs
  if (document.getElementById('cfgDressCode')) state.dressCode = document.getElementById('cfgDressCode').value;
  if (document.getElementById('cfgDressCodeNote')) state.dressCodeNote = document.getElementById('cfgDressCodeNote').value;
  if (document.getElementById('cfgDressCodeStyle')) state.dressCodeStyle = document.getElementById('cfgDressCodeStyle').value;
  if (document.getElementById('cfgDressCodeIcon')) state.dressCodeIcon = document.getElementById('cfgDressCodeIcon').value;
  if (document.getElementById('cfgDressCodeFont')) state.dressCodeFont = document.getElementById('cfgDressCodeFont').value;
  if (document.getElementById('cfgDressCodeColor')) state.dressCodeColor = document.getElementById('cfgDressCodeColor').value;

  state.fontFamily = getVal('cfgFont', state.fontFamily);
  state.scriptFont = getVal('cfgScriptFont', state.scriptFont);
  state.titleScale = parseInt(getVal('cfgTitleScale', state.titleScale || 100), 10);
  state.guestScale = parseInt(getVal('cfgGuestScale', state.guestScale || 100), 10);
  state.headerColor1 = getVal('cfgHeaderColor1', state.headerColor1);
  state.headerColor2 = getVal('cfgHeaderColor2', state.headerColor2);
  state.headingColor = getVal('cfgHeadingColor', state.headingColor);
  state.bodyBg = getVal('cfgBodyBg', state.bodyBg);
  state.textColor = getVal('cfgTextColor', state.textColor);
  state.borderColor = getVal('cfgBorderColor', state.borderColor);
  state.accentColor = getVal('cfgAccentColor', state.accentColor);
  state.borderFrame = getVal('cfgBorderFrame', state.borderFrame);
  state.seal = getVal('cfgSeal', state.seal);
  state.texture = getVal('cfgTexture', state.texture);
  if (document.getElementById('cfgShowArtwork')) {
    state.showArtwork = document.getElementById('cfgShowArtwork').checked;
  }
  if (document.getElementById('cfgArtworkOpacity')) {
    state.artworkOpacity = parseInt(document.getElementById('cfgArtworkOpacity').value, 10);
  }
  if (document.getElementById('cfgHeaderStyle')) {
    state.headerStyle = document.getElementById('cfgHeaderStyle').value;
  }
  if (document.getElementById('cfgLetterSpacing')) {
    state.letterSpacing = parseInt(document.getElementById('cfgLetterSpacing').value, 10);
  }
  if (document.getElementById('cfgLineHeight')) {
    state.lineHeight = parseInt(document.getElementById('cfgLineHeight').value, 10);
  }

  // Cover photo 5-layout inputs pull
  if (document.getElementById('cfgCoverHeight')) state.coverHeight = parseInt(document.getElementById('cfgCoverHeight').value, 10);
  if (document.getElementById('cfgCoverPositionY')) state.coverPositionY = parseInt(document.getElementById('cfgCoverPositionY').value, 10);
  if (document.getElementById('cfgCoverPositionX')) state.coverPositionX = parseInt(document.getElementById('cfgCoverPositionX').value, 10);
  if (document.getElementById('cfgCoverScale')) state.coverScale = parseInt(document.getElementById('cfgCoverScale').value, 10);
  if (document.getElementById('cfgCoverOverlay')) state.coverOverlay = parseInt(document.getElementById('cfgCoverOverlay').value, 10);
  if (document.getElementById('cfgCoverBrightness')) state.coverBrightness = parseInt(document.getElementById('cfgCoverBrightness').value, 10);
  if (document.getElementById('cfgCoverContrast')) state.coverContrast = parseInt(document.getElementById('cfgCoverContrast').value, 10);
  if (document.getElementById('cfgCoverOpacity')) state.coverOpacity = parseInt(document.getElementById('cfgCoverOpacity').value, 10);
  if (document.getElementById('cfgCoverHalfRatio')) state.coverHalfRatio = parseInt(document.getElementById('cfgCoverHalfRatio').value, 10);
  if (document.getElementById('cfgCustomX')) state.coverCustomX = parseFloat(document.getElementById('cfgCustomX').value);
  if (document.getElementById('cfgCustomY')) state.coverCustomY = parseFloat(document.getElementById('cfgCustomY').value);
  if (document.getElementById('cfgCustomW')) state.coverCustomW = parseInt(document.getElementById('cfgCustomW').value, 10);
  if (document.getElementById('cfgCustomH')) state.coverCustomH = parseInt(document.getElementById('cfgCustomH').value, 10);
  if (document.getElementById('cfgCustomRot')) state.coverCustomRotation = parseInt(document.getElementById('cfgCustomRot').value, 10);

  // Canva QR Inputs
  state.qrXPercent = parseFloat(getVal('cfgQrX', state.qrXPercent));
  state.qrYPercent = parseFloat(getVal('cfgQrY', state.qrYPercent));
  state.qrSizePercent = parseFloat(getVal('cfgQrSizeRange', state.qrSizePercent));
  state.qrRotation = parseInt(getVal('cfgQrRotation', state.qrRotation), 10);
  state.qrBadgeStyle = getVal('cfgQrBadgeStyle', state.qrBadgeStyle);
  state.canvaQrDark = getVal('cfgCanvaQrDark', state.canvaQrDark);
  state.qrDark = state.canvaQrDark;
  state.canvaQrLight = getVal('cfgCanvaQrLight', state.canvaQrLight);
  if (document.getElementById('cfgQrBgTransparent')) state.qrBgTransparent = document.getElementById('cfgQrBgTransparent').checked;
  state.badgeTextColor = getVal('cfgBadgeTextColor', state.badgeTextColor);
  state.qrPadding = parseInt(getVal('cfgQrPadding', state.qrPadding), 10);
  state.qrRadius = parseInt(getVal('cfgQrRadius', state.qrRadius), 10);
  if (document.getElementById('cfgQrLocked')) state.qrLocked = document.getElementById('cfgQrLocked').checked;
  if (document.getElementById('cfgShowAttendee')) state.showAttendee = document.getElementById('cfgShowAttendee').checked;
  if (document.getElementById('cfgShowRef')) state.showRef = document.getElementById('cfgShowRef').checked;
  if (document.getElementById('cfgShowTier')) state.showTier = document.getElementById('cfgShowTier').checked;
}

// ============================================================
// REQUIREMENT 1: DEDICATED DRESS CODE SECTION ENGINE
// ============================================================
function renderDressCodeSectionOnCanvas(isFullCover) {
  const dcSec = document.getElementById('cardDressCodeSection');
  const dcIcon = document.getElementById('cardDressCodeIconBox');
  const dcVal = document.getElementById('cardDressCodeText');
  const dcNote = document.getElementById('cardDressCodeNote');
  if (!dcSec) return;

  const vf = state.visibleFields || {};
  const visible = (state.showDressCode !== false) && (vf.dressCode !== false) && state.dressCode && String(state.dressCode).trim().length > 0;
  dcSec.style.display = visible ? 'block' : 'none';
  if (!visible) return;

  const dcStyle = state.dressCodeStyle || 'luxury_serif';
  dcSec.className = 'card-dress-code-section dc-style-' + dcStyle;
  dcSec.style.width = `${state.dressCodeWidth || 88}%`;
  dcSec.style.transform = `translateY(${state.dressCodeOffsetY || 0}px) scale(${(state.dressCodeScale || 100) / 100})`;
  const col = isFullCover ? '#FFFFFF' : (state.dressCodeColor || state.accentColor || state.textColor);
  dcSec.style.color = col;
  if (state.dressCodeFont) {
    dcSec.style.fontFamily = state.dressCodeFont;
  } else {
    dcSec.style.fontFamily = state.fontFamily;
  }

  if (dcVal && document.activeElement !== dcVal) {
    dcVal.textContent = state.dressCode || 'Formal Attire';
  }
  if (dcNote) {
    if (document.activeElement !== dcNote) dcNote.textContent = state.dressCodeNote || '';
    dcNote.style.display = (state.dressCodeNote && String(state.dressCodeNote).trim()) ? 'block' : 'none';
  }
  if (dcIcon) {
    const iconType = state.dressCodeIcon || 'tuxedo';
    if (iconType === 'none' || typeof LuxeArt === 'undefined' || typeof LuxeArt.dressCodeIcon !== 'function') {
      dcIcon.style.display = 'none';
      dcIcon.innerHTML = '';
    } else {
      dcIcon.style.display = 'inline-flex';
      dcIcon.innerHTML = LuxeArt.dressCodeIcon(iconType, dcStyle === 'black_tie_gold' ? '#F6D580' : col);
    }
  }
}

function onDressCodePresetSelect(presetVal) {
  if (!presetVal) return;
  const presetsMap = {
    'Formal Attire': { note: 'Evening gowns & dark tailored suits', icon: 'tuxedo' },
    'Black Tie': { note: 'Floor-length evening gowns & black tuxedos', icon: 'bowtie' },
    'White Tie': { note: 'Full evening dress, white bow tie & ballgowns', icon: 'crown' },
    'Smart Casual': { note: 'Polished contemporary attire', icon: 'hanger' },
    'Cocktail Attire': { note: 'Chic cocktail dresses & tailored blazers', icon: 'sparkles' },
    'Traditional African Wear': { note: 'Regal Aso-Ebi, Agbada, Kente or Kitenge', icon: 'african_shield' },
    'African Formal Attire': { note: 'Luxury African couture & gold accessories', icon: 'crown' },
    'All White': { note: 'Strictly all-white luxury resort or evening wear', icon: 'diamond' },
    'All Black': { note: 'Strictly all-black sleek formal attire', icon: 'bowtie' },
    'Cultural Attire': { note: 'Traditional heritage & ceremonial dress', icon: 'african_shield' },
    'Casual Wear': { note: 'Comfortable & relaxed celebration attire', icon: 'hanger' }
  };
  if (presetVal === 'Custom Dress Code') {
    const inp = document.getElementById('cfgDressCode');
    if (inp) { inp.focus(); inp.select(); }
    return;
  }
  state.dressCode = presetVal;
  if (presetsMap[presetVal]) {
    state.dressCodeNote = presetsMap[presetVal].note;
    state.dressCodeIcon = presetsMap[presetVal].icon;
  }
  syncStateToInputs();
  renderCanvas();
  pushHistoryState();
  showToast(`Dress code updated to "${presetVal}"`, 'success');
}

let isDressCodeDragging = false;
let dcDragStartY = 0;
let dcStartOffset = 0;
function onDressCodePointerDown(e) {
  if (e.target.isContentEditable || e.target.closest('[contenteditable="true"]')) return;
  isDressCodeDragging = true;
  dcDragStartY = e.clientY;
  dcStartOffset = state.dressCodeOffsetY || 0;
  window.addEventListener('pointermove', onDressCodePointerMove);
  window.addEventListener('pointerup', onDressCodePointerUp);
  e.preventDefault();
  e.stopPropagation();
}
function onDressCodePointerMove(e) {
  if (!isDressCodeDragging) return;
  const dy = Math.round((e.clientY - dcDragStartY) / (canvasZoom || 1));
  state.dressCodeOffsetY = Math.max(-60, Math.min(60, dcStartOffset + dy));
  const sec = document.getElementById('cardDressCodeSection');
  if (sec) sec.style.transform = `translateY(${state.dressCodeOffsetY}px) scale(${(state.dressCodeScale || 100) / 100})`;
  const inp = document.getElementById('cfgDressCodeOffsetY');
  if (inp) inp.value = state.dressCodeOffsetY;
  const lbl = document.getElementById('valDressCodeOffsetY');
  if (lbl) lbl.textContent = state.dressCodeOffsetY + 'px';
}
function onDressCodePointerUp() {
  if (isDressCodeDragging) {
    isDressCodeDragging = false;
    window.removeEventListener('pointermove', onDressCodePointerMove);
    window.removeEventListener('pointerup', onDressCodePointerUp);
    pushHistoryState();
  }
}

// ============================================================
// REQUIREMENT 4: FLEXIBLE 19-FIELD VISIBILITY MANAGER
// ============================================================
function toggleContentFieldVisibility(fieldKey, isChecked) {
  if (!state.visibleFields) state.visibleFields = {};
  state.visibleFields[fieldKey] = !!isChecked;
  if (fieldKey === 'dressCode') {
    state.showDressCode = !!isChecked;
    const dcChk = document.getElementById('cfgShowDressCode');
    if (dcChk) dcChk.checked = !!isChecked;
  }
  renderCanvas();
  pushHistoryState();
}

function setAllContentFieldsVisibility(showAll) {
  const all19Fields = [
    'badge', 'hostNames', 'sampleCouple', 'celebrantName', 'title', 'subtitle',
    'guestOfHonour', 'quote', 'guest', 'date', 'time', 'endTime', 'venue',
    'fullAddress', 'seat', 'dressCode', 'rsvp', 'contactInfo',
    'additionalInstructions', 'eventWebsite', 'socialMedia', 'guestCategory', 'footer'
  ];
  if (!state.visibleFields) state.visibleFields = {};
  all19Fields.forEach(k => {
    state.visibleFields[k] = showAll ? true : ['title', 'guest', 'date', 'time', 'venue', 'dressCode', 'guestCategory'].includes(k);
  });
  state.showDressCode = true;
  syncStateToInputs();
  renderCanvas();
  pushHistoryState();
  showToast(showAll ? 'All 19 invitation content fields enabled!' : 'Reset to essential invitation fields.', 'info');
}

// ============================================================
// REQUIREMENT 2: REPLACEABLE PHOTOGRAPHS & MULTI-IMAGE LAYER MANAGER
// ============================================================
function renderPhotoLayersOnCanvas() {
  const container = document.getElementById('cardPhotoLayersContainer');
  if (!container) return;
  container.innerHTML = '';

  const layers = Array.isArray(state.photoLayers) ? state.photoLayers : [];
  layers.forEach((layer) => {
    if (!layer || !layer.src) return;
    const wrap = document.createElement('div');
    wrap.className = 'studio-photo-layer' +
      (layer.id === activePhotoLayerId ? ' is-selected' : '') +
      (layer.locked ? ' is-locked' : '');
    wrap.style.left = `${layer.x !== undefined ? layer.x : 50}%`;
    wrap.style.top = `${layer.y !== undefined ? layer.y : 30}%`;
    wrap.style.width = `${layer.w || 140}px`;
    wrap.style.height = `${layer.h || 175}px`;
    wrap.style.zIndex = layer.zIndex || 5;
    wrap.style.transform = `translate(-50%, -50%) rotate(${layer.rotation || 0}deg)`;
    wrap.setAttribute('data-photo-id', layer.id);
    wrap.onpointerdown = (ev) => onPhotoLayerPointerDown(ev, layer.id);

    const mask = document.createElement('div');
    const shape = layer.shape || 'arch';
    mask.className = `studio-photo-mask mask-${shape}`;
    if (shape === 'rounded' || shape === 'rect') {
      mask.style.borderRadius = `${layer.cornerRadius !== undefined ? layer.cornerRadius : (shape === 'rounded' ? 14 : 2)}px`;
    }
    if ((layer.borderWidth || 0) > 0) {
      mask.style.border = `${layer.borderWidth}px solid ${layer.borderColor || state.accentColor || '#C5A059'}`;
    }

    const img = document.createElement('img');
    img.src = layer.src;
    img.alt = layer.label || 'Template Photograph';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'cover';
    img.style.objectPosition = `${layer.cropX !== undefined ? layer.cropX : 50}% ${layer.cropY !== undefined ? layer.cropY : 50}%`;
    const zoom = (layer.cropZoom || 100) / 100;
    img.style.transform = `scale(${zoom})`;
    img.style.filter = `brightness(${layer.brightness || 100}%) contrast(${layer.contrast || 100}%)`;
    img.style.opacity = ((layer.opacity !== undefined ? layer.opacity : 100) / 100).toFixed(2);
    img.draggable = false;

    mask.appendChild(img);
    wrap.appendChild(mask);

    // Direct-manipulation resize & rotate handles
    const hBr = document.createElement('div');
    hBr.className = 'studio-photo-layer-handle-br';
    hBr.title = 'Drag to resize photograph';
    hBr.onpointerdown = (ev) => onPhotoLayerResizeDown(ev, layer.id);
    wrap.appendChild(hBr);

    const hRot = document.createElement('div');
    hRot.className = 'studio-photo-layer-handle-rot';
    hRot.title = 'Drag to rotate photograph';
    hRot.textContent = '↻';
    hRot.onpointerdown = (ev) => onPhotoLayerRotateDown(ev, layer.id);
    wrap.appendChild(hRot);

    container.appendChild(wrap);
  });
}

function renderPhotoLayersSidebarList() {
  const listEl = document.getElementById('photoLayersList');
  const countBadge = document.getElementById('photoLayersCountBadge');
  const inspector = document.getElementById('activePhotoInspector');
  if (!listEl) return;

  const layers = Array.isArray(state.photoLayers) ? state.photoLayers : [];
  if (countBadge) countBadge.textContent = `${layers.length} Photo${layers.length === 1 ? '' : 's'}`;

  if (layers.length === 0) {
    listEl.innerHTML = `<div style="font-size:0.72rem;color:var(--text-muted);padding:8px;text-align:center;background:var(--surface-2);border-radius:6px;">No portrait frames on this card yet. Click "+ Upload / Add Photo Layer" or "+ Portrait Frame" above.</div>`;
    if (inspector) inspector.style.display = 'none';
    return;
  }

  if (!activePhotoLayerId || !layers.some(l => l.id === activePhotoLayerId)) {
    activePhotoLayerId = layers[0].id;
  }

  listEl.innerHTML = layers.map((layer, idx) => {
    const isSel = layer.id === activePhotoLayerId;
    return `
      <div onclick="selectPhotoLayer('${layer.id}')" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;background:${isSel ? 'rgba(255,122,0,0.12)' : 'var(--surface-2)'};border:1px solid ${isSel ? '#FF7A00' : 'var(--border)'};">
        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
          <img src="${layer.src}" style="width:28px;height:28px;object-fit:cover;border-radius:4px;border:1px solid var(--border);flex-shrink:0;">
          <div style="min-width:0;">
            <div style="font-size:0.72rem;font-weight:700;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${layer.label || ('Photo Frame #' + (idx + 1))}</div>
            <div style="font-size:0.64rem;color:var(--text-muted);">Shape: ${(layer.shape || 'arch').toUpperCase()} &bull; ${layer.w}×${layer.h}px ${layer.locked ? '🔒' : ''}</div>
          </div>
        </div>
        <span style="font-size:0.66rem;font-weight:700;color:${isSel ? '#FF7A00' : 'var(--text-muted)'};">${isSel ? 'Editing' : 'Select'}</span>
      </div>
    `;
  }).join('');

  const activeLayer = layers.find(l => l.id === activePhotoLayerId);
  if (activeLayer && inspector) {
    inspector.style.display = 'block';
    const setV = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    const setT = (id, t) => { const el = document.getElementById(id); if (el) el.textContent = t; };
    setT('activePhotoInspectorTitle', activeLayer.label || 'Selected Photo Frame');
    setV('cfgPhotoLayerShape', activeLayer.shape || 'arch');
    setV('cfgPhotoLayerBorderColor', activeLayer.borderColor || state.accentColor || '#C5A059');
    setV('cfgPhotoLayerBorderWidth', activeLayer.borderWidth !== undefined ? activeLayer.borderWidth : 2);
    setT('valPhotoBorderW', (activeLayer.borderWidth !== undefined ? activeLayer.borderWidth : 2) + 'px');
    setV('cfgPhotoLayerW', activeLayer.w || 140);
    setT('valPhotoLayerW', (activeLayer.w || 140) + 'px');
    setV('cfgPhotoLayerH', activeLayer.h || 175);
    setT('valPhotoLayerH', (activeLayer.h || 175) + 'px');
    setV('cfgPhotoLayerX', Math.round(activeLayer.x !== undefined ? activeLayer.x : 50));
    setT('valPhotoLayerX', Math.round(activeLayer.x !== undefined ? activeLayer.x : 50) + '%');
    setV('cfgPhotoLayerY', Math.round(activeLayer.y !== undefined ? activeLayer.y : 30));
    setT('valPhotoLayerY', Math.round(activeLayer.y !== undefined ? activeLayer.y : 30) + '%');
    setV('cfgPhotoLayerRot', activeLayer.rotation || 0);
    setT('valPhotoLayerRot', (activeLayer.rotation || 0) + '°');
    setV('cfgPhotoLayerRadius', activeLayer.cornerRadius !== undefined ? activeLayer.cornerRadius : 14);
    setT('valPhotoLayerRadius', (activeLayer.cornerRadius !== undefined ? activeLayer.cornerRadius : 14) + 'px');
    setV('cfgPhotoCropZoom', activeLayer.cropZoom || 100);
    setT('valPhotoCropZoom', (activeLayer.cropZoom || 100) + '%');
    setV('cfgPhotoCropX', activeLayer.cropX !== undefined ? activeLayer.cropX : 50);
    setT('valPhotoCropX', (activeLayer.cropX !== undefined ? activeLayer.cropX : 50) + '%');
    setV('cfgPhotoCropY', activeLayer.cropY !== undefined ? activeLayer.cropY : 50);
    setT('valPhotoCropY', (activeLayer.cropY !== undefined ? activeLayer.cropY : 50) + '%');
    setV('cfgPhotoBright', activeLayer.brightness || 100);
    setT('valPhotoBright', (activeLayer.brightness || 100) + '%');
    setV('cfgPhotoContrast', activeLayer.contrast || 100);
    setT('valPhotoContrast', (activeLayer.contrast || 100) + '%');
    setV('cfgPhotoOpacity', activeLayer.opacity !== undefined ? activeLayer.opacity : 100);
    setT('valPhotoOpacity', (activeLayer.opacity !== undefined ? activeLayer.opacity : 100) + '%');
    const lockBtn = document.getElementById('btnTogglePhotoLock');
    if (lockBtn) lockBtn.textContent = activeLayer.locked ? '🔒 Unlock' : '🔓 Lock';
  } else if (inspector) {
    inspector.style.display = 'none';
  }
}

function selectPhotoLayer(id) {
  activePhotoLayerId = id;
  renderPhotoLayersOnCanvas();
  renderPhotoLayersSidebarList();
}

function triggerAddOrReplacePhotoLayer(mode) {
  photoLayerUploadMode = mode || 'add';
  const inp = document.getElementById('photoLayerFileInput');
  if (inp) {
    inp.value = '';
    inp.click();
  }
}

function handlePhotoLayerFileSelect(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const reader = new FileReader();
  reader.onload = function(ev) {
    const img = new Image();
    img.onload = function() {
      const maxDim = 900;
      let w = img.width, h = img.height;
      if (w > maxDim || h > maxDim) {
        if (w > h) { h = Math.round((h * maxDim) / w); w = maxDim; }
        else { w = Math.round((w * maxDim) / h); h = maxDim; }
      }
      const c = document.createElement('canvas');
      c.width = w; c.height = h;
      c.getContext('2d').drawImage(img, 0, 0, w, h);
      const dataUrl = c.toDataURL('image/jpeg', 0.88);

      if (!Array.isArray(state.photoLayers)) state.photoLayers = [];
      if (photoLayerUploadMode === 'replace' && activePhotoLayerId) {
        const target = state.photoLayers.find(l => l.id === activePhotoLayerId);
        if (target) {
          target.src = dataUrl;
          showToast('Photograph replaced in template frame!', 'success');
        }
      } else if (state.photoLayers.length > 0 && photoLayerUploadMode === 'add' && state.photoLayers.length === 1 && String(state.photoLayers[0].src).startsWith('data:image/svg+xml')) {
        // If template has a default SVG sample portrait, replace it seamlessly or add
        state.photoLayers[0].src = dataUrl;
        activePhotoLayerId = state.photoLayers[0].id;
        showToast('Uploaded photo placed into template frame!', 'success');
      } else {
        const newId = 'photo_' + Date.now();
        state.photoLayers.push({
          id: newId,
          label: 'Photo Frame #' + (state.photoLayers.length + 1),
          src: dataUrl,
          shape: 'arch',
          x: 74,
          y: 28,
          w: 135,
          h: 170,
          rotation: 0,
          opacity: 100,
          brightness: 100,
          contrast: 100,
          cropZoom: 100,
          cropX: 50,
          cropY: 50,
          borderColor: state.accentColor || '#C5A059',
          borderWidth: 2,
          cornerRadius: 16,
          zIndex: 6,
          locked: false
        });
        activePhotoLayerId = newId;
        showToast('New editable photo layer added to canvas!', 'success');
      }
      setCardMode('studio');
      renderCanvas();
      renderPhotoLayersSidebarList();
      pushHistoryState();
    };
    img.src = ev.target.result;
  };
  reader.readAsDataURL(file);
}

function addSamplePortraitLayer() {
  if (!Array.isArray(state.photoLayers)) state.photoLayers = [];
  const sampleUrl = (typeof LuxeArt !== 'undefined' && typeof LuxeArt.samplePortraitDataUrl === 'function')
    ? LuxeArt.samplePortraitDataUrl('couple_floral', state.accentColor || '#B8955A', state.bodyBg || '#FAF6F0')
    : '';
  const newId = 'photo_' + Date.now();
  state.photoLayers.push({
    id: newId,
    label: 'Portrait Frame #' + (state.photoLayers.length + 1),
    src: sampleUrl,
    shape: 'arch',
    x: 50 + (state.photoLayers.length * 8) % 30,
    y: 28,
    w: 130,
    h: 165,
    rotation: 0,
    opacity: 100,
    brightness: 100,
    contrast: 100,
    cropZoom: 100,
    cropX: 50,
    cropY: 50,
    borderColor: state.accentColor || '#C5A059',
    borderWidth: 2,
    cornerRadius: 16,
    zIndex: 5 + state.photoLayers.length,
    locked: false
  });
  activePhotoLayerId = newId;
  setCardMode('studio');
  renderCanvas();
  renderPhotoLayersSidebarList();
  pushHistoryState();
  showToast('Added editable portrait frame! Drag on canvas or upload a photo.', 'success');
}

function updateActivePhotoProp(prop, val) {
  if (!Array.isArray(state.photoLayers) || !activePhotoLayerId) return;
  const layer = state.photoLayers.find(l => l.id === activePhotoLayerId);
  if (!layer) return;
  layer[prop] = val;
  renderPhotoLayersOnCanvas();
  renderPhotoLayersSidebarList();
}

function toggleActivePhotoLock() {
  if (!Array.isArray(state.photoLayers) || !activePhotoLayerId) return;
  const layer = state.photoLayers.find(l => l.id === activePhotoLayerId);
  if (!layer) return;
  layer.locked = !layer.locked;
  renderPhotoLayersOnCanvas();
  renderPhotoLayersSidebarList();
  pushHistoryState();
  showToast(layer.locked ? 'Photo layer locked' : 'Photo layer unlocked', 'info');
}

function shiftActivePhotoZIndex(delta) {
  if (!Array.isArray(state.photoLayers) || !activePhotoLayerId) return;
  const layer = state.photoLayers.find(l => l.id === activePhotoLayerId);
  if (!layer) return;
  layer.zIndex = Math.max(1, Math.min(25, (layer.zIndex || 5) + delta));
  renderPhotoLayersOnCanvas();
  pushHistoryState();
  showToast(`Photo layer depth set to z-index ${layer.zIndex}`, 'info');
}

function deleteActivePhotoLayer() {
  if (!Array.isArray(state.photoLayers) || !activePhotoLayerId) return;
  state.photoLayers = state.photoLayers.filter(l => l.id !== activePhotoLayerId);
  activePhotoLayerId = state.photoLayers.length > 0 ? state.photoLayers[0].id : null;
  renderPhotoLayersOnCanvas();
  renderPhotoLayersSidebarList();
  pushHistoryState();
  showToast('Photograph removed from template.', 'info');
}

let photoDragState = null;
function onPhotoLayerPointerDown(e, id) {
  if (e.target.classList.contains('studio-photo-layer-handle-br') || e.target.classList.contains('studio-photo-layer-handle-rot')) return;
  e.preventDefault();
  e.stopPropagation();
  selectPhotoLayer(id);
  const layer = (state.photoLayers || []).find(l => l.id === id);
  if (!layer || layer.locked) return;
  const artboard = document.getElementById('cardArtboard');
  const rect = artboard.getBoundingClientRect();
  photoDragState = { mode: 'move', id, startX: e.clientX, startY: e.clientY, origX: layer.x, origY: layer.y, rect };
  window.addEventListener('pointermove', onPhotoLayerPointerMove);
  window.addEventListener('pointerup', onPhotoLayerPointerUp);
}
function onPhotoLayerResizeDown(e, id) {
  e.preventDefault();
  e.stopPropagation();
  const layer = (state.photoLayers || []).find(l => l.id === id);
  if (!layer || layer.locked) return;
  photoDragState = { mode: 'resize', id, startX: e.clientX, startY: e.clientY, origW: layer.w, origH: layer.h };
  window.addEventListener('pointermove', onPhotoLayerPointerMove);
  window.addEventListener('pointerup', onPhotoLayerPointerUp);
}
function onPhotoLayerRotateDown(e, id) {
  e.preventDefault();
  e.stopPropagation();
  const layer = (state.photoLayers || []).find(l => l.id === id);
  if (!layer || layer.locked) return;
  const el = document.querySelector(`[data-photo-id="${id}"]`);
  const r = el.getBoundingClientRect();
  photoDragState = { mode: 'rotate', id, cx: r.left + r.width / 2, cy: r.top + r.height / 2 };
  window.addEventListener('pointermove', onPhotoLayerPointerMove);
  window.addEventListener('pointerup', onPhotoLayerPointerUp);
}
function onPhotoLayerPointerMove(e) {
  if (!photoDragState) return;
  const layer = (state.photoLayers || []).find(l => l.id === photoDragState.id);
  if (!layer) return;
  if (photoDragState.mode === 'move') {
    const dx = ((e.clientX - photoDragState.startX) / photoDragState.rect.width) * 100;
    const dy = ((e.clientY - photoDragState.startY) / photoDragState.rect.height) * 100;
    layer.x = Math.max(5, Math.min(95, Math.round(photoDragState.origX + dx)));
    layer.y = Math.max(5, Math.min(95, Math.round(photoDragState.origY + dy)));
  } else if (photoDragState.mode === 'resize') {
    const dx = (e.clientX - photoDragState.startX) / (canvasZoom || 1);
    const dy = (e.clientY - photoDragState.startY) / (canvasZoom || 1);
    layer.w = Math.max(55, Math.min(400, Math.round(photoDragState.origW + dx)));
    layer.h = Math.max(55, Math.min(440, Math.round(photoDragState.origH + dy)));
  } else if (photoDragState.mode === 'rotate') {
    const rad = Math.atan2(e.clientX - photoDragState.cx, -(e.clientY - photoDragState.cy));
    layer.rotation = Math.round(rad * (180 / Math.PI));
  }
  renderPhotoLayersOnCanvas();
  renderPhotoLayersSidebarList();
}
function onPhotoLayerPointerUp() {
  if (photoDragState) {
    photoDragState = null;
    window.removeEventListener('pointermove', onPhotoLayerPointerMove);
    window.removeEventListener('pointerup', onPhotoLayerPointerUp);
    pushHistoryState();
  }
}

// ============================================================
// REQUIREMENT 6: INDEPENDENTLY EDITABLE VECTOR DECORATIVE OBJECTS
// ============================================================
function renderDecorObjectsOnCanvas() {
  const container = document.getElementById('cardInteractiveDecorLayer');
  if (!container) return;
  container.innerHTML = '';
  if (state.showArtwork === false) return;

  const objs = Array.isArray(state.decorativeObjects) ? state.decorativeObjects : [];
  objs.forEach((obj) => {
    if (!obj) return;
    const wrap = document.createElement('div');
    wrap.className = 'studio-decor-motif' +
      (obj.id === activeDecorId ? ' is-selected' : '') +
      (obj.locked ? ' is-locked' : '');
    wrap.style.left = `${obj.x !== undefined ? obj.x : 50}%`;
    wrap.style.top = `${obj.y !== undefined ? obj.y : 20}%`;
    wrap.style.width = `${obj.size || 90}px`;
    wrap.style.height = `${obj.size || 90}px`;
    wrap.style.opacity = ((obj.opacity !== undefined ? obj.opacity : 95) / 100).toFixed(2);
    wrap.style.transform = `translate(-50%, -50%) rotate(${obj.rotation || 0}deg)`;
    wrap.setAttribute('data-decor-id', obj.id);
    wrap.onpointerdown = (ev) => onDecorObjPointerDown(ev, obj.id);

    const svgHtml = (typeof LuxeArt !== 'undefined' && typeof LuxeArt.standaloneMotifSvg === 'function')
      ? LuxeArt.standaloneMotifSvg(obj.type, state.accentColor || '#B8955A', state.headingColor || '#D4AF37')
      : '';
    wrap.innerHTML = svgHtml;

    const hBr = document.createElement('div');
    hBr.className = 'studio-decor-handle-br';
    hBr.onpointerdown = (ev) => onDecorObjResizeDown(ev, obj.id);
    wrap.appendChild(hBr);

    const hRot = document.createElement('div');
    hRot.className = 'studio-decor-handle-rot';
    hRot.textContent = '↻';
    hRot.onpointerdown = (ev) => onDecorObjRotateDown(ev, obj.id);
    wrap.appendChild(hRot);

    container.appendChild(wrap);
  });
}

function renderDecorObjectsSidebarList() {
  const listEl = document.getElementById('decorObjectsList');
  const badgeEl = document.getElementById('decorObjectsCountBadge');
  const inspector = document.getElementById('activeDecorInspector');
  if (!listEl) return;

  const objs = Array.isArray(state.decorativeObjects) ? state.decorativeObjects : [];
  if (badgeEl) badgeEl.textContent = `${objs.length} Object${objs.length === 1 ? '' : 's'}`;

  listEl.innerHTML = objs.map((obj, idx) => {
    const isSel = obj.id === activeDecorId;
    const label = (obj.type || 'motif').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    return `
      <div onclick="selectDecorativeObject('${obj.id}')" style="display:flex;align-items:center;justify-content:space-between;padding:5px 8px;border-radius:6px;cursor:pointer;background:${isSel ? 'rgba(184,149,90,0.16)' : 'var(--surface-2)'};border:1px solid ${isSel ? '#B8955A' : 'var(--border)'};font-size:0.71rem;">
        <span style="font-weight:700;color:var(--text-primary);">✿ ${label} #${idx + 1} ${obj.locked ? '🔒' : ''}</span>
        <span style="font-size:0.65rem;color:var(--text-muted);">${obj.size || 90}px &bull; ${obj.rotation || 0}°</span>
      </div>
    `;
  }).join('');

  const activeObj = objs.find(o => o.id === activeDecorId);
  if (activeObj && inspector) {
    inspector.style.display = 'block';
    const setV = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    const setT = (id, t) => { const el = document.getElementById(id); if (el) el.textContent = t; };
    setT('activeDecorTitle', (activeObj.type || 'Motif').replace(/_/g, ' ').toUpperCase());
    setV('cfgDecorSize', activeObj.size || 90);
    setT('valDecorSize', (activeObj.size || 90) + 'px');
    setV('cfgDecorRot', activeObj.rotation || 0);
    setT('valDecorRot', (activeObj.rotation || 0) + '°');
    setV('cfgDecorX', Math.round(activeObj.x || 50));
    setT('valDecorX', Math.round(activeObj.x || 50) + '%');
    setV('cfgDecorY', Math.round(activeObj.y || 20));
    setT('valDecorY', Math.round(activeObj.y || 20) + '%');
    const lockBtn = document.getElementById('btnToggleDecorLock');
    if (lockBtn) lockBtn.textContent = activeObj.locked ? '🔒 Unlock' : '🔓 Lock';
  } else if (inspector) {
    inspector.style.display = 'none';
  }
}

function addDecorativeObject() {
  const sel = document.getElementById('addDecorMotifSelect');
  const motifType = sel ? sel.value : 'ivory_bloom_cluster';
  if (!Array.isArray(state.decorativeObjects)) state.decorativeObjects = [];
  const newId = 'decor_' + Date.now();
  state.decorativeObjects.push({
    id: newId,
    type: motifType,
    x: 22 + (state.decorativeObjects.length * 18) % 60,
    y: 18 + (state.decorativeObjects.length * 14) % 55,
    size: 96,
    rotation: 0,
    opacity: 95,
    locked: false
  });
  activeDecorId = newId;
  setCardMode('studio');
  renderDecorObjectsOnCanvas();
  renderDecorObjectsSidebarList();
  pushHistoryState();
  showToast('Added editable vector motif to canvas!', 'success');
}

function selectDecorativeObject(id) {
  activeDecorId = id;
  renderDecorObjectsOnCanvas();
  renderDecorObjectsSidebarList();
}

function updateActiveDecorProp(prop, val) {
  if (!Array.isArray(state.decorativeObjects) || !activeDecorId) return;
  const obj = state.decorativeObjects.find(o => o.id === activeDecorId);
  if (!obj) return;
  obj[prop] = val;
  renderDecorObjectsOnCanvas();
  renderDecorObjectsSidebarList();
}

function toggleActiveDecorLock() {
  if (!Array.isArray(state.decorativeObjects) || !activeDecorId) return;
  const obj = state.decorativeObjects.find(o => o.id === activeDecorId);
  if (!obj) return;
  obj.locked = !obj.locked;
  renderDecorObjectsOnCanvas();
  renderDecorObjectsSidebarList();
  pushHistoryState();
}

function deleteActiveDecorObject() {
  if (!Array.isArray(state.decorativeObjects) || !activeDecorId) return;
  state.decorativeObjects = state.decorativeObjects.filter(o => o.id !== activeDecorId);
  activeDecorId = state.decorativeObjects.length > 0 ? state.decorativeObjects[0].id : null;
  renderDecorObjectsOnCanvas();
  renderDecorObjectsSidebarList();
  pushHistoryState();
  showToast('Decorative vector motif deleted.', 'info');
}

let decorDragState = null;
function onDecorObjPointerDown(e, id) {
  if (e.target.classList.contains('studio-decor-handle-br') || e.target.classList.contains('studio-decor-handle-rot')) return;
  e.preventDefault();
  e.stopPropagation();
  selectDecorativeObject(id);
  const obj = (state.decorativeObjects || []).find(o => o.id === id);
  if (!obj || obj.locked) return;
  const artboard = document.getElementById('cardArtboard');
  const rect = artboard.getBoundingClientRect();
  decorDragState = { mode: 'move', id, startX: e.clientX, startY: e.clientY, origX: obj.x, origY: obj.y, rect };
  window.addEventListener('pointermove', onDecorObjPointerMove);
  window.addEventListener('pointerup', onDecorObjPointerUp);
}
function onDecorObjResizeDown(e, id) {
  e.preventDefault();
  e.stopPropagation();
  const obj = (state.decorativeObjects || []).find(o => o.id === id);
  if (!obj || obj.locked) return;
  decorDragState = { mode: 'resize', id, startX: e.clientX, origSize: obj.size || 90 };
  window.addEventListener('pointermove', onDecorObjPointerMove);
  window.addEventListener('pointerup', onDecorObjPointerUp);
}
function onDecorObjRotateDown(e, id) {
  e.preventDefault();
  e.stopPropagation();
  const obj = (state.decorativeObjects || []).find(o => o.id === id);
  if (!obj || obj.locked) return;
  const el = document.querySelector(`[data-decor-id="${id}"]`);
  const r = el.getBoundingClientRect();
  decorDragState = { mode: 'rotate', id, cx: r.left + r.width / 2, cy: r.top + r.height / 2 };
  window.addEventListener('pointermove', onDecorObjPointerMove);
  window.addEventListener('pointerup', onDecorObjPointerUp);
}
function onDecorObjPointerMove(e) {
  if (!decorDragState) return;
  const obj = (state.decorativeObjects || []).find(o => o.id === decorDragState.id);
  if (!obj) return;
  if (decorDragState.mode === 'move') {
    const dx = ((e.clientX - decorDragState.startX) / decorDragState.rect.width) * 100;
    const dy = ((e.clientY - decorDragState.startY) / decorDragState.rect.height) * 100;
    obj.x = Math.max(2, Math.min(98, Math.round(decorDragState.origX + dx)));
    obj.y = Math.max(2, Math.min(98, Math.round(decorDragState.origY + dy)));
  } else if (decorDragState.mode === 'resize') {
    const dx = (e.clientX - decorDragState.startX) / (canvasZoom || 1);
    obj.size = Math.max(36, Math.min(240, Math.round(decorDragState.origSize + dx)));
  } else if (decorDragState.mode === 'rotate') {
    const rad = Math.atan2(e.clientX - decorDragState.cx, -(e.clientY - decorDragState.cy));
    obj.rotation = Math.round(rad * (180 / Math.PI));
  }
  renderDecorObjectsOnCanvas();
  renderDecorObjectsSidebarList();
}
function onDecorObjPointerUp() {
  if (decorDragState) {
    decorDragState = null;
    window.removeEventListener('pointermove', onDecorObjPointerMove);
    window.removeEventListener('pointerup', onDecorObjPointerUp);
    pushHistoryState();
  }
}

// ============================================================
// REQUIREMENT 5: STUDIO QR PLACEMENT & GUEST CATEGORY ENGINE
// ============================================================
function setStudioQrDock(dockMode) {
  state.studioQrDock = dockMode;
  syncStateToInputs();
  renderCanvas();
  pushHistoryState();
  showToast(`Studio QR placement set to ${dockMode.replace('-', ' ')}`, 'info');
}

function onGuestCategoryChange(val) {
  const custInp = document.getElementById('cfgCustomGuestCategory');
  if (val === 'CUSTOM') {
    if (custInp) {
      custInp.style.display = 'block';
      custInp.focus();
      state.guestCategory = custInp.value || 'HONOURED GUEST';
    }
  } else {
    if (custInp) custInp.style.display = 'none';
    state.guestCategory = val;
    state.badge = `${val} INVITATION PASS`;
    const badgeInp = document.getElementById('cfgBadge');
    if (badgeInp) badgeInp.value = state.badge;
  }
  renderCanvas();
  pushHistoryState();
}

let isStudioQrDragging = false;
let studioQrDragStart = { x: 0, y: 0, origX: 50, origY: 78 };
function onStudioQrPointerDown(e) {
  if (state.qrLocked || e.target.isContentEditable) return;
  e.preventDefault();
  e.stopPropagation();
  if (state.studioQrDock !== 'floating') {
    state.studioQrDock = 'floating';
  }
  isStudioQrDragging = true;
  studioQrDragStart = {
    x: e.clientX,
    y: e.clientY,
    origX: state.studioQrX !== undefined ? state.studioQrX : 50,
    origY: state.studioQrY !== undefined ? state.studioQrY : 78
  };
  window.addEventListener('pointermove', onStudioQrPointerMove);
  window.addEventListener('pointerup', onStudioQrPointerUp);
}
function onStudioQrPointerMove(e) {
  if (!isStudioQrDragging) return;
  const artboard = document.getElementById('cardArtboard');
  const rect = artboard.getBoundingClientRect();
  const dx = ((e.clientX - studioQrDragStart.x) / rect.width) * 100;
  const dy = ((e.clientY - studioQrDragStart.y) / rect.height) * 100;
  state.studioQrX = Math.max(8, Math.min(92, Math.round(studioQrDragStart.origX + dx)));
  state.studioQrY = Math.max(8, Math.min(92, Math.round(studioQrDragStart.origY + dy)));
  const qrWrap = document.getElementById('studioQrInteractiveWrap');
  if (qrWrap) {
    qrWrap.className = 'studio-qr-interactive-wrap dock-floating';
    qrWrap.style.left = `${state.studioQrX}%`;
    qrWrap.style.top = `${state.studioQrY}%`;
    qrWrap.style.transform = `translate(-50%, -50%) rotate(${state.studioQrRotation || 0}deg)`;
  }
  syncStateToInputs();
}
function onStudioQrPointerUp() {
  if (isStudioQrDragging) {
    isStudioQrDragging = false;
    window.removeEventListener('pointermove', onStudioQrPointerMove);
    window.removeEventListener('pointerup', onStudioQrPointerUp);
    pushHistoryState();
  }
}

function checkQrContrast() {
  const pill = document.getElementById('qrContrastStatus');
  if (!pill) return;
  const darkHex = state.qrDark || state.canvaQrDark || '#09090B';
  const lightHex = state.qrBgTransparent ? (state.bodyBg || '#FFFFFF') : (state.canvaQrLight || '#FFFFFF');
  const luma = (hex) => {
    if (!hex || hex[0] !== '#') return 128;
    const rgb = parseInt(hex.substring(1), 16);
    return 0.2126 * ((rgb >> 16) & 0xff) + 0.7152 * ((rgb >> 8) & 0xff) + 0.0722 * (rgb & 0xff);
  };
  const diff = Math.abs(luma(lightHex) - luma(darkHex));
  if (diff < 75) {
    pill.textContent = '⚠️ Low Contrast — Increase difference for reliable scanning';
    pill.style.background = 'rgba(245,158,11,0.16)';
    pill.style.color = '#D97706';
  } else {
    pill.textContent = '✓ High Contrast — Scannable';
    pill.style.background = 'rgba(16,185,129,0.14)';
    pill.style.color = '#059669';
  }
}

// ============================================================
// REQUIREMENT 3: INTELLIGENT TEXT FITTING & OVERFLOW PREVENTION
// ============================================================
function fitStudioTextIntelligently() {
  const titleEl = document.getElementById('cardTitle');
  const coupleEl = document.getElementById('cardCoupleScript');
  const quoteEl = document.getElementById('cardQuote');
  const venueEl = document.getElementById('cardVenue');
  const warnBanner = document.getElementById('textFitWarningBanner');

  let anyScaled = false;
  const baseTitleRem = 1.42 * ((state.titleScale || 100) / 100);
  if (titleEl && state.title) {
    const len = state.title.length;
    if (len > 42) {
      const factor = Math.max(0.68, 42 / len);
      titleEl.style.fontSize = `${(baseTitleRem * factor).toFixed(2)}rem`;
      anyScaled = true;
    } else {
      titleEl.style.fontSize = `${baseTitleRem.toFixed(2)}rem`;
    }
  }
  if (coupleEl && state.sampleCouple) {
    const len = state.sampleCouple.length;
    if (len > 28) {
      const factor = Math.max(0.72, 28 / len);
      coupleEl.style.fontSize = `${(2.05 * factor).toFixed(2)}rem`;
      anyScaled = true;
    } else {
      coupleEl.style.fontSize = '';
    }
  }
  if (quoteEl && state.quote) {
    const len = state.quote.length;
    if (len > 110) {
      quoteEl.style.fontSize = '0.66rem';
      anyScaled = true;
    } else {
      quoteEl.style.fontSize = '0.76rem';
    }
  }
  if (venueEl && state.venue && state.venue.length > 48) {
    venueEl.style.fontSize = '0.74rem';
    anyScaled = true;
  } else if (venueEl) {
    venueEl.style.fontSize = '';
  }

  if (warnBanner) {
    warnBanner.style.display = anyScaled ? 'flex' : 'none';
  }
}

function toggleFontFormat(fmt) {
  if (fmt === 'bold') state.fontBold = !state.fontBold;
  if (fmt === 'italic') state.fontItalic = !state.fontItalic;
  if (fmt === 'underline') state.fontUnderline = !state.fontUnderline;
  updateFormatButtonStyles();
  renderCanvas();
}

function setTextAlignment(align) {
  state.textAlign = align;
  updateFormatButtonStyles();
  renderCanvas();
}

function updateLetterSpacing(val) {
  state.letterSpacing = parseInt(val, 10);
  const lbl = document.getElementById('valLetterSpacing');
  if (lbl) lbl.textContent = val == 0 ? 'Normal' : `${val}px`;
}

function updateLineHeight(val) {
  state.lineHeight = parseInt(val, 10);
  const lbl = document.getElementById('valLineHeight');
  if (lbl) lbl.textContent = `${(val / 10).toFixed(1)}x`;
}

function updateFormatButtonStyles() {
  const btnB = document.getElementById('btnFontBold');
  const btnI = document.getElementById('btnFontItalic');
  const btnU = document.getElementById('btnFontUnderline');
  if (btnB) btnB.classList.toggle('active', !!state.fontBold);
  if (btnI) btnI.classList.toggle('active', !!state.fontItalic);
  if (btnU) btnU.classList.toggle('active', !!state.fontUnderline);

  const align = state.textAlign || 'center';
  const al = document.getElementById('btnAlignLeft');
  const ac = document.getElementById('btnAlignCenter');
  const ar = document.getElementById('btnAlignRight');
  if (al) al.classList.toggle('active', align === 'left');
  if (ac) ac.classList.toggle('active', align === 'center');
  if (ar) ar.classList.toggle('active', align === 'right');
}

function toggleQrTransparentBg(isTransparent) {
  state.qrBgTransparent = !!isTransparent;
  const lightInput = document.getElementById('cfgCanvaQrLight');
  if (lightInput) {
    lightInput.disabled = state.qrBgTransparent;
    lightInput.style.opacity = state.qrBgTransparent ? '0.4' : '1';
  }
  const hint = document.getElementById('qrBgTransparentHint');
  if (hint) {
    hint.style.display = state.qrBgTransparent ? 'block' : 'none';
  }
  renderCanvas();
  showToast(state.qrBgTransparent ? 'QR background removed (Transparent)' : 'QR background fill enabled', 'info');
}

function setQrBgPreset(isTransparent, color = '#FFFFFF') {
  state.qrBgTransparent = isTransparent;
  if (!isTransparent && color) {
    state.canvaQrLight = color;
    const lightInput = document.getElementById('cfgCanvaQrLight');
    if (lightInput) lightInput.value = color;
  }
  const chk = document.getElementById('cfgQrBgTransparent');
  if (chk) chk.checked = isTransparent;
  const lightInput = document.getElementById('cfgCanvaQrLight');
  if (lightInput) {
    lightInput.disabled = isTransparent;
    lightInput.style.opacity = isTransparent ? '0.4' : '1';
  }
  const hint = document.getElementById('qrBgTransparentHint');
  if (hint) hint.style.display = isTransparent ? 'block' : 'none';
  renderCanvas();
  showToast(isTransparent ? 'QR background removed (Transparent)' : `QR background set to ${color}`, 'info');
}

function setQrDarkPreset(color) {
  state.canvaQrDark = color;
  state.qrDark = color;
  const darkInput = document.getElementById('cfgCanvaQrDark');
  if (darkInput) darkInput.value = color;
  renderCanvas();
  showToast(`QR Barcode color set to ${color}`, 'info');
}

// Toggle Export Dropdown Menu
function toggleExportMenu(e) {
  if (e) e.stopPropagation();
  const menu = document.getElementById('exportDropdownMenu');
  if (!menu) return;
  menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'block' : 'none';
}
document.addEventListener('click', (e) => {
  const menu = document.getElementById('exportDropdownMenu');
  if (menu && menu.style.display === 'block' && !e.target.closest('.dropdown')) {
    menu.style.display = 'none';
  }
});

// Download High-Res Image (PNG / JPG Export)
function downloadCardImage(format = 'png') {
  selectQr(false);
  activePhotoLayerId = null;
  activeDecorId = null;
  renderPhotoLayersOnCanvas();
  renderDecorObjectsOnCanvas();

  const target = state.cardMode === 'canva' 
    ? document.getElementById('canvaCardArtboard') 
    : document.getElementById('cardArtboard');

  const fmt = (format && format.toLowerCase() === 'jpg') ? 'jpg' : 'png';
  showToast(`Generating ultra high-resolution ${fmt.toUpperCase()} invitation (3x HD)...`, 'info');

  setTimeout(() => {
    html2canvas(target, {
      scale: 3,
      useCORS: true,
      backgroundColor: fmt === 'jpg' ? (state.bodyBg || '#FFFFFF') : null
    }).then(canvas => {
      const link = document.createElement('a');
      link.download = `leula_luxury_invitation_${Date.now()}.${fmt}`;
      link.href = fmt === 'jpg' ? canvas.toDataURL('image/jpeg', 0.96) : canvas.toDataURL('image/png');
      link.click();
      showToast(`Invitation Card exported as ${fmt.toUpperCase()}!`, 'success');
    }).catch(err => {
      window.Toast.error('Export error: ' + err);
    });
  }, 100);
}

// Download Print-Ready PDF (jsPDF + 3x HD Canvas)
function downloadCardPdf() {
  selectQr(false);
  activePhotoLayerId = null;
  activeDecorId = null;
  renderPhotoLayersOnCanvas();
  renderDecorObjectsOnCanvas();

  const target = state.cardMode === 'canva' 
    ? document.getElementById('canvaCardArtboard') 
    : document.getElementById('cardArtboard');

  showToast('Generating print-ready PDF invitation pass...', 'info');

  setTimeout(() => {
    html2canvas(target, {
      scale: 3,
      useCORS: true,
      backgroundColor: state.bodyBg || '#FFFFFF'
    }).then(canvas => {
      const imgData = canvas.toDataURL('image/png');
      const isLandscape = state.orientation === 'landscape';
      const wPt = isLandscape ? 620 : 420;
      const hPt = isLandscape ? 400 : 640;

      if (window.jspdf && window.jspdf.jsPDF) {
        const pdf = new window.jspdf.jsPDF({
          orientation: isLandscape ? 'landscape' : 'portrait',
          unit: 'pt',
          format: [wPt, hPt]
        });
        pdf.addImage(imgData, 'PNG', 0, 0, wPt, hPt);
        pdf.save(`leula_luxury_invitation_${Date.now()}.pdf`);
        showToast('Print-ready PDF downloaded successfully!', 'success');
      } else {
        downloadCardImage('png');
      }
    }).catch(err => {
      window.Toast.error('PDF export error: ' + err);
    });
  }, 100);
}

function printCard() {
  selectQr(false);
  setTimeout(() => {
    window.print();
    selectQr(true);
  }, 100);
}

function submitSaveDesign() {
  pullInputsToState();
  document.getElementById('postDesignName').value = (state.cardMode === 'canva' ? 'Canva Custom Cloned ' : '') + state.title;
  document.getElementById('postOrientation').value = state.orientation;
  document.getElementById('postBgColor').value = state.bodyBg;
  document.getElementById('postCanvasData').value = JSON.stringify(state);
  document.getElementById('saveDesignForm').submit();
}

// ============================================================
// RESPONSIVE WORKSPACE & ZOOM ENGINE
// ============================================================
let canvasZoom = 1.0;

function switchWorkView(view) {
  const workspace = document.getElementById('designerWorkspace');
  const btnControls = document.getElementById('viewBtnControls');
  const btnPreview = document.getElementById('viewBtnPreview');
  const mobNavControls = document.getElementById('mobNavControls');
  const mobNavPreview = document.getElementById('mobNavPreview');

  if (view === 'controls') {
    if (workspace) {
      workspace.classList.remove('show-preview');
      workspace.classList.add('show-controls');
    }
    if (btnControls) btnControls.classList.add('active');
    if (btnPreview) btnPreview.classList.remove('active');
    if (mobNavControls) mobNavControls.classList.add('active');
    if (mobNavPreview) mobNavPreview.classList.remove('active');
  } else {
    if (workspace) {
      workspace.classList.remove('show-controls');
      workspace.classList.add('show-preview');
    }
    if (btnControls) btnControls.classList.remove('active');
    if (btnPreview) btnPreview.classList.add('active');
    if (mobNavControls) mobNavControls.classList.remove('active');
    if (mobNavPreview) mobNavPreview.classList.add('active');

    setTimeout(() => {
      zoomCanvasFit();
    }, 60);
  }
}

function setCanvasZoom(scale) {
  canvasZoom = Math.max(0.20, Math.min(2.0, parseFloat(scale.toFixed(2))));
  const wrapper = document.getElementById('artboardWrapper');
  if (wrapper) {
    wrapper.style.transform = `scale(${canvasZoom})`;
  }
  const display = document.getElementById('zoomLevelDisplay');
  if (display) {
    display.textContent = `${Math.round(canvasZoom * 100)}%`;
  }
}

function zoomCanvas(delta) {
  setCanvasZoom(canvasZoom + delta);
}

function zoomCanvasReset() {
  setCanvasZoom(1.0);
}

function zoomCanvasFit() {
  const stage = document.getElementById('previewStage');
  const artboard = state.cardMode === 'canva' 
    ? document.getElementById('canvaCardArtboard') 
    : document.getElementById('cardArtboard');

  if (!stage || !artboard) return;

  const stageWidth = stage.clientWidth - (window.innerWidth <= 768 ? 24 : 48);
  const isLandscape = state.orientation === 'landscape';
  const nativeWidth = isLandscape ? 620 : 420;

  if (stageWidth > 0 && nativeWidth > 0) {
    let idealScale = stageWidth / nativeWidth;
    if (idealScale > 1.0) idealScale = 1.0;
    idealScale = Math.min(1.0, idealScale * 0.98);
    setCanvasZoom(idealScale);
  }
}

// ============================================================
// Direct On-Canvas WYSIWYG Content Editing Engine (All 19 Fields)
// ============================================================
function setupInlineCanvasEditing() {
  const bindings = [
    { elId: 'cardBadge',                  field: 'badge',                  inputId: 'cfgBadge' },
    { elId: 'cardHostNames',              field: 'hostNames',              inputId: 'cfgHostNames' },
    { elId: 'cardCoupleScript',           field: 'sampleCouple',           inputId: 'cfgCouple' },
    { elId: 'cardCelebrantName',          field: 'celebrantName',          inputId: 'cfgCelebrantName' },
    { elId: 'cardTitle',                  field: 'title',                  inputId: 'cfgTitle' },
    { elId: 'cardSubtitle',               field: 'subtitle',               inputId: 'cfgSubtitle' },
    { elId: 'cardGuestOfHonour',          field: 'guestOfHonour',          inputId: 'cfgGuestOfHonour' },
    { elId: 'cardQuote',                  field: 'quote',                  inputId: 'cfgQuote' },
    { elId: 'cardGuest',                  field: 'guest',                  inputId: 'cfgGuest' },
    { elId: 'cardDate',                   field: 'date',                   inputId: 'cfgDate' },
    { elId: 'cardTime',                   field: 'time',                   inputId: 'cfgTime' },
    { elId: 'cardEndTime',                field: 'endTime',                inputId: 'cfgEndTime' },
    { elId: 'cardVenue',                  field: 'venue',                  inputId: 'cfgVenue' },
    { elId: 'cardFullAddress',            field: 'fullAddress',            inputId: 'cfgFullAddress' },
    { elId: 'cardSeat',                   field: 'seat',                   inputId: 'cfgSeat' },
    { elId: 'cardDressCodeText',          field: 'dressCode',              inputId: 'cfgDressCode' },
    { elId: 'cardDressCodeNote',          field: 'dressCodeNote',          inputId: 'cfgDressCodeNote' },
    { elId: 'cardRsvp',                   field: 'rsvp',                   inputId: 'cfgRsvp' },
    { elId: 'cardContactInfo',            field: 'contactInfo',            inputId: 'cfgContactInfo' },
    { elId: 'cardAdditionalInstructions', field: 'additionalInstructions', inputId: 'cfgAdditionalInstructions' },
    { elId: 'cardEventWebsite',           field: 'eventWebsite',           inputId: 'cfgEventWebsite' },
    { elId: 'cardSocialMedia',            field: 'socialMedia',            inputId: 'cfgSocialMedia' },
    { elId: 'cardFooter',                 field: 'footer',                 inputId: 'cfgFooter' },
    { elId: 'cardQrCategoryBadge',        field: 'guestCategory',          inputId: 'cfgCustomGuestCategory' },
    { elId: 'canvaBadgePill',             field: 'badge',                  inputId: 'cfgBadge' },
    { elId: 'canvaAttendeeText',          field: 'guest',                  inputId: 'cfgGuest' }
  ];

  bindings.forEach(({ elId, field, inputId }) => {
    const el = document.getElementById(elId);
    if (!el) return;

    el.setAttribute('contenteditable', 'true');
    el.setAttribute('spellcheck', 'false');
    el.classList.add('editable-canvas-text');

    el.addEventListener('input', () => {
      const val = el.innerText.trim();
      state[field] = val;
      const inp = document.getElementById(inputId);
      if (inp) inp.value = val;
      fitStudioTextIntelligently();
    });

    el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && elId !== 'cardFooter' && elId !== 'cardQuote') {
        e.preventDefault();
        el.blur();
      }
    });

    el.addEventListener('focus', () => {
      const hint = document.getElementById('canvasEditHint');
      if (hint) {
        hint.style.borderColor = '#FF7A00';
        hint.style.color = '#FF7A00';
        hint.innerHTML = `✏️ <strong>Editing "${field.toUpperCase()}" directly on card...</strong>`;
      }
    });

    el.addEventListener('blur', () => {
      const hint = document.getElementById('canvasEditHint');
      if (hint) {
        hint.style.borderColor = 'var(--border)';
        hint.style.color = 'var(--text-secondary)';
        hint.innerHTML = `<?= svgIcon('edit', 12) ?> <span>Click text, photos, dress code or QR on card to edit</span>`;
      }
      pushHistoryState();
    });
  });
}

// Window resize auto-fit listener for responsive adaptations
let resizeTimer = null;
window.addEventListener('resize', () => {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(() => {
    if (window.innerWidth <= 992) {
      zoomCanvasFit();
    }
  }, 100);
});

document.addEventListener('DOMContentLoaded', () => {
  if (typeof renderTemplatesLibrary === 'function') {
    renderTemplatesLibrary('all', '');
  }
  syncStateToInputs();
  setCardMode(state.cardMode || 'canva');
  selectQr(true);
  setupInlineCanvasEditing();
  pushHistoryState();
  if (window.innerWidth <= 992) {
    setTimeout(zoomCanvasFit, 100);
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
