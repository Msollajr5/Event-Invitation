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
<link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Bebas+Neue&family=Cinzel:wght@600;700;800;900&family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Great+Vibes&family=Inter:wght@400;600;700;800&family=Marcellus&family=Montserrat:wght@400;600;700;800;900&family=Outfit:wght@400;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,400&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

<!-- High-Resolution Export Engine, Vector QR & jsPDF Document Engine -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="assets/js/qrcode.min.js"></script>
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

        </div>
      </div>

      <!-- TAB 2: 50 PREMIUM TEMPLATES LIBRARY (Studio Mode) -->
      <div class="tab-panel" data-tab-panel="tabTemplates" data-tab-group="studio">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <div>
            <div style="font-size:0.88rem;font-weight:800;color:var(--text-primary);letter-spacing:-0.2px;">
              50 Premium Invitation Templates
            </div>
            <div style="font-size:0.72rem;color:var(--text-muted);">
              Professional Canva-grade designs across 8 event categories
            </div>
          </div>
          <span style="font-size:0.72rem;background:linear-gradient(135deg,#FF7A00,#E96800);color:#FFFFFF;font-weight:800;padding:3px 10px;border-radius:99px;box-shadow:0 2px 8px rgba(255,122,0,0.3);">
            50 Templates
          </span>
        </div>

        <!-- Search Bar -->
        <div class="form-group" style="margin-bottom:10px;position:relative;">
          <input type="text" id="tplSearchInput" class="form-control" placeholder="🔍 Search 50 templates (e.g. wedding, birthday, royal, kanga)..." style="padding-left:12px;font-size:0.8rem;" oninput="onTemplateSearchInput(this.value)">
        </div>

        <!-- Category Filter Pills Bar -->
        <div class="tpl-filter-bar">
          <button type="button" class="tpl-filter-pill active" data-cat="all" onclick="filterTemplatesByCategory('all')">
            ✨ All (50)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="wedding" onclick="filterTemplatesByCategory('wedding')">
            💍 Wedding (10)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="birthday" onclick="filterTemplatesByCategory('birthday')">
            🎂 Birthday (8)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="corporate" onclick="filterTemplatesByCategory('corporate')">
            💼 Corporate (6)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="graduation" onclick="filterTemplatesByCategory('graduation')">
            🎓 Graduation (5)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="religious" onclick="filterTemplatesByCategory('religious')">
            ⛪ Religious (5)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="celebration" onclick="filterTemplatesByCategory('celebration')">
            🎉 Celebrations (6)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="special" onclick="filterTemplatesByCategory('special')">
            👑 Special (5)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="african" onclick="filterTemplatesByCategory('african')">
            🌍 African &amp; Swahili (5)
          </button>
          <button type="button" class="tpl-filter-pill" data-cat="favorites" onclick="filterTemplatesByCategory('favorites')">
            ⭐ Favorites
          </button>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <span id="tplResultsCount" style="font-size:0.72rem;font-weight:700;color:var(--text-muted);">
            50 templates available
          </span>
          <span style="font-size:0.7rem;color:#FF7A00;font-weight:600;">
            1-Click Instant Apply
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
        <div class="form-group" style="margin-bottom:12px;">
          <label class="form-label" style="font-size:0.75rem;display:flex;justify-content:space-between;align-items:center;">
            <span>Pass Badge / Tier Tag</span>
            <span style="color:var(--text-muted);font-weight:normal;font-size:0.7rem;">Quick Select:</span>
          </label>
          <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('👑 VVIP PASS')" style="padding:2px 7px;font-size:0.7rem;">👑 VVIP</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('⭐ VIP PASS')" style="padding:2px 7px;font-size:0.7rem;">⭐ VIP</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('🎟️ REGULAR PASS')" style="padding:2px 7px;font-size:0.7rem;">🎟️ Regular</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('🏷️ GENERAL ADMISSION')" style="padding:2px 7px;font-size:0.7rem;">🏷️ General</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('💼 STAFF / CREW')" style="padding:2px 7px;font-size:0.7rem;">💼 Staff</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickTier('✨ SPECIAL GUEST')" style="padding:2px 7px;font-size:0.7rem;">✨ Special</button>
          </div>
          <input type="text" id="cfgBadge" class="form-control" value="VIP DELEGATE PASS" oninput="renderCanvas()">
        </div>

        <div class="form-group">
          <label class="form-label">Event Headline Title</label>
          <input type="text" id="cfgTitle" class="form-control" value="<?= e($event['title'] ?? 'Annual Gala & Tech Awards') ?>" oninput="renderCanvas()">
        </div>

        <div class="form-group">
          <label class="form-label">Event Subtitle / Edition</label>
          <input type="text" id="cfgSubtitle" class="form-control" value="Celebrating Technological Leadership &amp; Excellence" oninput="renderCanvas()">
        </div>

        <div class="form-group">
          <label class="form-label">Sample Attendee Name</label>
          <input type="text" id="cfgGuest" class="form-control" value="Dr. Reginald Massawe" oninput="renderCanvas()">
        </div>

        <div class="form-group">
          <label class="form-label">Welcome Note / Cordial Quote</label>
          <input type="text" id="cfgQuote" class="form-control" value="Honored to request the pleasure of your company" oninput="renderCanvas()">
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Date Schedule</label>
            <input type="text" id="cfgDate" class="form-control" value="<?= date('l, M d, Y', strtotime($event['start_date'] ?? 'now')) ?>" oninput="renderCanvas()">
          </div>
          <div class="form-group">
            <label class="form-label">Time Schedule</label>
            <input type="text" id="cfgTime" class="form-control" value="<?= e($event['start_time'] ?? '18:00') ?>" oninput="renderCanvas()">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Venue Location Name</label>
          <input type="text" id="cfgVenue" class="form-control" value="<?= e($event['venue_name'] ?? 'Serena Grand Ballroom') ?>" oninput="renderCanvas()">
        </div>

        <div class="form-group">
          <label class="form-label">Seating / Gate Access Details</label>
          <input type="text" id="cfgSeat" class="form-control" value="Table 04 &bull; VIP North Gate 1" oninput="renderCanvas()">
        </div>

        <div class="form-group">
          <label class="form-label">Dress Code / Host Footer Note</label>
          <input type="text" id="cfgFooter" class="form-control" value="Black Tie Required &bull; Non-Transferable Digital QR Ticket" oninput="renderCanvas()">
        </div>
      </div>

      <!-- TAB 4: TYPOGRAPHY & COLORS -->
      <div class="tab-panel" data-tab-panel="tabStyling" data-tab-group="studio">
        <div class="form-group">
          <label class="form-label">Headline Font Family</label>
          <select id="cfgFont" class="form-control" onchange="renderCanvas()">
            <option value="'Sora', sans-serif">Sora (Modern Futuristic Sans)</option>
            <option value="'Playfair Display', serif">Playfair Display (Luxury Editorial Serif)</option>
            <option value="'Cinzel', serif">Cinzel (Regal Imperial Roman)</option>
            <option value="'Great Vibes', cursive">Great Vibes (Romantic Calligraphy Script)</option>
            <option value="'Alex Brush', cursive">Alex Brush (Wedding Calligraphy)</option>
            <option value="'Cormorant Garamond', serif">Cormorant Garamond (Classical Elegance)</option>
            <option value="'Marcellus', serif">Marcellus (Antiquity Roman)</option>
            <option value="'Montserrat', sans-serif">Montserrat (Geometric Modern)</option>
            <option value="'Outfit', sans-serif">Outfit (Clean Luxury)</option>
            <option value="'Plus Jakarta Sans', sans-serif">Plus Jakarta Sans (Corporate Executive)</option>
            <option value="'Space Grotesk', sans-serif">Space Grotesk (Tech Monospace)</option>
            <option value="'Inter', sans-serif">Inter (Clean Enterprise)</option>
            <option value="'Bebas Neue', sans-serif">Bebas Neue (Bold Display)</option>
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

        <!-- Cover Photo Upload & Status Header -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#FF7A00,#E96800);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(255,122,0,0.25);">
              <?= svgIcon('palette', 18) ?>
            </div>
            <div>
              <div style="font-weight:700;color:var(--text-primary);font-size:0.86rem;">Cover Photo &amp; Artboard Layout</div>
              <div style="font-size:0.72rem;color:var(--text-muted);">5 distinct layouts, drag &amp; drop, zoom, opacity &amp; contrast</div>
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
        <span id="canvasEditHint" style="font-size:0.72rem;color:var(--text-secondary);background:var(--surface);border:1px solid var(--border);border-radius:99px;padding:3px 10px;display:inline-flex;align-items:center;gap:5px;">
          <?= svgIcon('edit', 12) ?> <span>Click text on card to edit directly</span>
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
            <div id="cardLogoContainer" style="display:none;margin-bottom:10px;">
              <img id="cardLogoImg" src="" style="max-height:44px;max-width:150px;object-fit:contain;filter:drop-shadow(0 2px 6px rgba(0,0,0,0.25));">
            </div>
            <div id="cardBadge" class="card-badge editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit badge directly">VIP DELEGATE PASS</div>
            <h2 id="cardTitle" class="card-title-text editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit event title directly"><?= e($event['title'] ?? 'Annual Gala & Awards') ?></h2>
            <div id="cardSubtitle" class="card-subtitle-text editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit subtitle directly">Celebrating Technological Leadership</div>
          </div>
        </div>

        <div id="artboardBody" class="artboard-body">
          <div id="cardSealWrapper" style="display:flex;justify-content:center;margin-bottom:4px;">
            <span id="cardSealBadge" class="seal-pill">⭐ OFFICIAL ACCREDITED PASS</span>
          </div>
          <div id="cardQuote" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-size:0.76rem;font-style:italic;color:var(--text-secondary);margin:4px 0 6px;" title="Click to edit invitation quote directly">
            Honored to request the pleasure of your company
          </div>
          <div class="card-attendee-label">ADMIT ATTENDEE BEARER</div>
          <h3 id="cardGuest" class="card-guest-name editable-canvas-text" contenteditable="true" spellcheck="false" title="Click to edit guest name directly">Dr. Reginald Massawe</h3>

          <div class="card-qr-frame">
            <div id="cardQrBox"></div>
          </div>

          <div id="cardRefCode" class="card-ref-code">INV-REG-8821 &bull; TOKEN-VIP-01</div>

          <div id="cardMetaBox" class="card-meta-box">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
              <span id="cardDate" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-weight:700;" title="Click to edit date directly">Saturday, Nov 21, 2026</span>
              <span id="cardTime" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="color:var(--text-muted);" title="Click to edit time directly">18:00 EAT</span>
            </div>
            <div id="cardVenue" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-weight:600;margin-bottom:4px;" title="Click to edit venue directly">Serena Grand Ballroom</div>
            <div id="cardSeat" class="editable-canvas-text" contenteditable="true" spellcheck="false" style="font-size:0.78rem;color:#FF7A00;font-weight:700;" title="Click to edit table/seat directly">Table 04 &bull; VIP North Gate 1</div>
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
</style>

<script>
// ============================================================
// Direct Manipulation & Canvas State Engine
// ============================================================
let currentOrientation = 'portrait';
let uploadedLogoData = null;
let isQrSelected = false;

// Direct Manipulation Interaction State
let dragMode = null; // 'DRAGGING' | 'RESIZING' | 'ROTATING' | null
let dragStartPointer = { x: 0, y: 0 };
let dragStartCoords = { x: 50, y: 78, size: 28, rotation: 0 };
let activeResizeHandle = null;
let qrCenterScreen = { x: 0, y: 0 };

// Main Design State
let state = {
  cardMode: 'canva', // 'canva' (poster clone) vs 'studio' (template)
  template: 'orange_gala',
  orientation: 'portrait',
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

  // Content for badges & Studio
  badge: 'VIP DELEGATE PASS',
  title: '<?= addslashes($event['title'] ?? 'Annual Gala & Awards 2026') ?>',
  subtitle: 'Celebrating Technological Leadership',
  guest: 'Dr. Reginald Massawe',
  quote: 'Honored to request the pleasure of your company',
  date: '<?= date('l, M d, Y', strtotime($event['start_date'] ?? 'now')) ?>',
  time: '<?= addslashes($event['start_time'] ?? '18:00') ?>',
  venue: '<?= addslashes($event['venue_name'] ?? 'Serena Grand Ballroom') ?>',
  seat: 'Table 04 • VIP North Gate 1',
  footer: 'Black Tie Required • Non-Transferable Digital QR Ticket',
  fontFamily: "'Sora', sans-serif",
  titleScale: 100,
  guestScale: 100,
  headerColor1: '#FF7A00',
  headerColor2: '#E96800',
  headingColor: '#FFFFFF',
  bodyBg: '#FFFFFF',
  textColor: '#09090B',
  accentColor: '#FF7A00',
  borderColor: '#FF7A00',
  borderFrame: 'none',
  seal: 'ribbon',
  texture: 'none',
  qrSize: 140,
  qrDark: '#09090B',
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
  // If clicking stage outside of QR element, deselect
  if (!e.target.closest('#canvaQrElement') && !e.target.closest('.tabs') && !e.target.closest('.card')) {
    selectQr(false);
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
  const preset = TEMPLATE_PRESETS[templateId];
  if (!preset) return;

  state.template = templateId;
  Object.assign(state, preset);

  // Sync inputs FIRST so the inputs hold the new preset values
  syncStateToInputs();

  // Switch to Studio mode so user immediately sees the editorial template rendered
  setCardMode('studio');

  // Update Active Template Banner
  const banner = document.getElementById('activeTemplateBanner');
  const nameEl = document.getElementById('activeTemplateName');
  if (banner) banner.style.display = 'flex';
  if (nameEl) nameEl.textContent = preset.name;

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
  showToast(`Applied "${preset.name}" preset template!`, 'success');
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
  const body = document.getElementById('artboardBody');
  const title = document.getElementById('cardTitle');
  const guest = document.getElementById('cardGuest');
  const quote = document.getElementById('cardQuote');
  const seal = document.getElementById('cardSealBadge');
  const metaBox = document.getElementById('cardMetaBox');
  const textureOverlay = document.getElementById('cardTextureOverlay');
  const badgeEl = document.getElementById('cardBadge');
  const subtitleEl = document.getElementById('cardSubtitle');
  const dateEl = document.getElementById('cardDate');
  const timeEl = document.getElementById('cardTime');
  const venueEl = document.getElementById('cardVenue');
  const seatEl = document.getElementById('cardSeat');
  const footerEl = document.getElementById('cardFooter');
  const refCodeEl = document.getElementById('cardRefCode');

  // Text values (only update DOM text if user is not actively editing that specific element)
  if (badgeEl && document.activeElement !== badgeEl) badgeEl.textContent = state.badge || 'VIP DELEGATE PASS';
  if (title && document.activeElement !== title) title.textContent = state.title || '';
  if (subtitleEl && document.activeElement !== subtitleEl) subtitleEl.textContent = state.subtitle || '';
  if (guest && document.activeElement !== guest) guest.textContent = state.guest || '';
  if (quote && document.activeElement !== quote) quote.textContent = state.quote || '';
  if (dateEl && document.activeElement !== dateEl) dateEl.textContent = state.date || '';
  if (timeEl && document.activeElement !== timeEl) timeEl.textContent = state.time || '';
  if (venueEl && document.activeElement !== venueEl) venueEl.textContent = state.venue || '';
  if (seatEl && document.activeElement !== seatEl) seatEl.textContent = state.seat || '';
  if (footerEl && document.activeElement !== footerEl) footerEl.textContent = state.footer || '';

  // Typography & Scales
  title.style.fontSize = `${(1.45 * (state.titleScale / 100)).toFixed(2)}rem`;
  guest.style.fontSize = `${(1.45 * (state.guestScale / 100)).toFixed(2)}rem`;
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
  title.style.textAlign = state.textAlign || 'center';
  title.style.letterSpacing = `${state.letterSpacing || 0}px`;
  title.style.lineHeight = `${((state.lineHeight || 12) / 10).toFixed(1)}`;

  guest.style.fontWeight = state.fontBold ? '800' : '700';
  guest.style.fontStyle = state.fontItalic ? 'italic' : 'normal';
  guest.style.letterSpacing = `${state.letterSpacing || 0}px`;
  guest.style.textAlign = state.textAlign || 'center';

  if (state.borderColor) {
    artboard.style.borderColor = state.borderColor;
  }

  const isFullCover = (state.coverImgData && state.coverLayout === 'full');

  if (isFullCover) {
    header.style.background = 'transparent';
    header.style.backgroundImage = 'none';
    body.style.background = 'transparent';
    body.style.backgroundImage = 'none';
    artboard.style.background = 'transparent';
    artboard.classList.add('cover-layout-full', 'card-cover-full-active');
  } else {
    header.style.background = `linear-gradient(135deg, ${state.headerColor1}, ${state.headerColor2})`;
    body.style.background = state.bodyBg;
    artboard.style.background = '';
    artboard.classList.remove('cover-layout-full', 'card-cover-full-active');
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

  // Reset all layout classes
  artboard.classList.remove('artboard-half-split', 'split-side-right', 'split-vertical', 'split-side-bottom', 'full-cover-glass', 'full-cover-shadow', 'cover-layout-full', 'card-cover-full-active');

  if (state.coverImgData) {
    const filterStyle = `brightness(${state.coverBrightness || 100}%) contrast(${state.coverContrast || 100}%)`;
    const alphaOverlay = (((state.coverOverlay !== undefined ? state.coverOverlay : 40) / 100)).toFixed(2);
    const imgOpacity = ((state.coverOpacity !== undefined ? state.coverOpacity : 100) / 100).toFixed(2);
    const fitStyle = state.coverCropFit || 'cover';
    const scale = (state.coverScale || 100) / 100;
    const posX = state.coverPositionX !== undefined ? state.coverPositionX : 50;
    const posY = state.coverPositionY !== undefined ? state.coverPositionY : 50;

    // 1. Full Card Layout
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
    }
    // 2. Top Section Layout
    else if (state.coverLayout === 'top') {
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
    }
    // 3. Bottom Section Layout
    else if (state.coverLayout === 'bottom') {
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
    }
    // 4. Half Card Split Layout
    else if (state.coverLayout === 'half') {
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
    }
    // 5. Custom Position Layout
    else if (state.coverLayout === 'custom') {
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

  artboard.className = (state.orientation === 'landscape' ? 'artboard-landscape' : 'artboard-portrait');
  if (state.borderFrame === 'gold_single') artboard.classList.add('frame-gold-single');
  else if (state.borderFrame === 'gold_double') artboard.classList.add('frame-gold-double');
  else if (state.borderFrame === 'glass_glow') artboard.classList.add('frame-glass-glow');
  else if (state.borderFrame === 'deco_corners') artboard.classList.add('frame-deco-corners');

  const isDark = isDarkColor(state.bodyBg);
  if (isDark) {
    metaBox.style.background = 'rgba(255,255,255,0.06)';
    metaBox.style.borderColor = 'rgba(255,255,255,0.12)';
    metaBox.style.color = state.textColor;
    quote.style.color = 'rgba(255,255,255,0.75)';
    if (footerEl) footerEl.style.color = 'rgba(255,255,255,0.6)';
    if (refCodeEl) refCodeEl.style.color = 'rgba(255,255,255,0.75)';
  } else {
    metaBox.style.background = '#F9FAFB';
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

  const qrBox = document.getElementById("cardQrBox");
  qrBox.innerHTML = "";
  const lightColor = state.qrBgTransparent ? "rgba(0,0,0,0)" : (state.canvaQrLight || "#FFFFFF");
  const qrFrame = document.querySelector('.card-qr-frame');
  if (qrFrame) {
    if (state.qrBgTransparent) {
      qrFrame.style.background = 'transparent';
      qrFrame.style.border = 'none';
      qrFrame.style.boxShadow = 'none';
      qrFrame.style.padding = '0';
    } else {
      qrFrame.style.background = state.canvaQrLight || '#FFFFFF';
      qrFrame.style.border = '1.5px solid rgba(0,0,0,0.08)';
      qrFrame.style.boxShadow = '0 8px 24px rgba(0,0,0,0.12)';
      qrFrame.style.padding = '10px';
    }
  }
  new QRCode(qrBox, {
    text: "<?= APP_URL ?>/pass.php?token=DEMO-TOKEN-VIP-REG-01",
    width: state.qrSize,
    height: state.qrSize,
    colorDark: state.qrDark,
    colorLight: lightColor,
    correctLevel: QRCode.CorrectLevel.H
  });
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
  document.getElementById('cfgBadge').value = state.badge || 'VIP DELEGATE PASS';
  document.getElementById('cfgTitle').value = state.title || '';
  document.getElementById('cfgSubtitle').value = state.subtitle || '';
  document.getElementById('cfgGuest').value = state.guest || '';
  document.getElementById('cfgQuote').value = state.quote || '';
  document.getElementById('cfgDate').value = state.date || '';
  document.getElementById('cfgTime').value = state.time || '';
  document.getElementById('cfgVenue').value = state.venue || '';
  document.getElementById('cfgSeat').value = state.seat || '';
  document.getElementById('cfgFooter').value = state.footer || '';
  document.getElementById('cfgFont').value = state.fontFamily || "'Sora', sans-serif";
  document.getElementById('cfgTitleScale').value = state.titleScale || 100;
  document.getElementById('cfgGuestScale').value = state.guestScale || 100;
  updateScales();
  document.getElementById('cfgHeaderColor1').value = state.headerColor1 || '#FF7A00';
  document.getElementById('cfgHeaderColor2').value = state.headerColor2 || '#E96800';
  if (document.getElementById('cfgHeadingColor')) {
    document.getElementById('cfgHeadingColor').value = state.headingColor || '#FFFFFF';
  }
  document.getElementById('cfgBodyBg').value = state.bodyBg || '#FFFFFF';
  document.getElementById('cfgTextColor').value = state.textColor || '#09090B';
  if (document.getElementById('cfgBorderColor')) {
    document.getElementById('cfgBorderColor').value = state.borderColor || '#E4E4E7';
  }
  document.getElementById('cfgAccentColor').value = state.accentColor || '#FF7A00';
  document.getElementById('cfgBorderFrame').value = state.borderFrame || 'none';
  document.getElementById('cfgSeal').value = state.seal || 'ribbon';
  document.getElementById('cfgTexture').value = state.texture || 'none';

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
  document.getElementById('cfgQrBadgeStyle').value = state.qrBadgeStyle || 'badge-glass';
  document.getElementById('cfgCanvaQrDark').value = state.canvaQrDark || '#09090B';
  document.getElementById('cfgCanvaQrLight').value = state.canvaQrLight || '#FFFFFF';
  document.getElementById('cfgQrBgTransparent').checked = !!state.qrBgTransparent;
  document.getElementById('cfgCanvaQrLight').disabled = !!state.qrBgTransparent;
  document.getElementById('cfgCanvaQrLight').style.opacity = state.qrBgTransparent ? '0.4' : '1';
  document.getElementById('qrBgTransparentHint').style.display = state.qrBgTransparent ? 'block' : 'none';
  document.getElementById('cfgBadgeTextColor').value = state.badgeTextColor || (state.qrBadgeStyle === 'badge-dark' ? '#FFFFFF' : '#09090B');
  document.getElementById('cfgQrPadding').value = state.qrPadding || 10;
  document.getElementById('valQrPadding').textContent = `${state.qrPadding || 10}px`;
  document.getElementById('cfgQrRadius').value = state.qrRadius || 18;
  document.getElementById('valQrRadius').textContent = `${state.qrRadius || 18}px`;
  document.getElementById('cfgQrLocked').checked = !!state.qrLocked;
  document.getElementById('valQrLockedText').textContent = state.qrLocked ? '🔒 Locked' : 'Unlocked';
  document.getElementById('cfgShowAttendee').checked = (state.showAttendee !== false);
  document.getElementById('cfgShowRef').checked = (state.showRef !== false);
  document.getElementById('cfgShowTier').checked = (state.showTier !== false);

  if (state.canvaBgData) {
    document.getElementById('canvaBgThumb').src = state.canvaBgData;
    document.getElementById('canvaBgPreviewWrapper').style.display = 'flex';
  }

  // Cover photo controls sync
  syncCoverControls();
}

function pullInputsToState() {
  state.badge = document.getElementById('cfgBadge').value;
  state.title = document.getElementById('cfgTitle').value;
  state.subtitle = document.getElementById('cfgSubtitle').value;
  state.guest = document.getElementById('cfgGuest').value;
  state.quote = document.getElementById('cfgQuote').value;
  state.date = document.getElementById('cfgDate').value;
  state.time = document.getElementById('cfgTime').value;
  state.venue = document.getElementById('cfgVenue').value;
  state.seat = document.getElementById('cfgSeat').value;
  state.footer = document.getElementById('cfgFooter').value;
  state.fontFamily = document.getElementById('cfgFont').value;
  state.titleScale = parseInt(document.getElementById('cfgTitleScale').value, 10);
  state.guestScale = parseInt(document.getElementById('cfgGuestScale').value, 10);
  state.headerColor1 = document.getElementById('cfgHeaderColor1').value;
  state.headerColor2 = document.getElementById('cfgHeaderColor2').value;
  if (document.getElementById('cfgHeadingColor')) {
    state.headingColor = document.getElementById('cfgHeadingColor').value;
  }
  state.bodyBg = document.getElementById('cfgBodyBg').value;
  state.textColor = document.getElementById('cfgTextColor').value;
  if (document.getElementById('cfgBorderColor')) {
    state.borderColor = document.getElementById('cfgBorderColor').value;
  }
  state.accentColor = document.getElementById('cfgAccentColor').value;
  state.borderFrame = document.getElementById('cfgBorderFrame').value;
  state.seal = document.getElementById('cfgSeal').value;
  state.texture = document.getElementById('cfgTexture').value;
  if (document.getElementById('cfgLetterSpacing')) {
    state.letterSpacing = parseInt(document.getElementById('cfgLetterSpacing').value, 10);
  }
  if (document.getElementById('cfgLineHeight')) {
    state.lineHeight = parseInt(document.getElementById('cfgLineHeight').value, 10);
  }

  // Cover photo 5-layout inputs pull
  if (document.getElementById('cfgCoverHeight')) {
    state.coverHeight = parseInt(document.getElementById('cfgCoverHeight').value, 10);
  }
  if (document.getElementById('cfgCoverPositionY')) {
    state.coverPositionY = parseInt(document.getElementById('cfgCoverPositionY').value, 10);
  }
  if (document.getElementById('cfgCoverPositionX')) {
    state.coverPositionX = parseInt(document.getElementById('cfgCoverPositionX').value, 10);
  }
  if (document.getElementById('cfgCoverScale')) {
    state.coverScale = parseInt(document.getElementById('cfgCoverScale').value, 10);
  }
  if (document.getElementById('cfgCoverOverlay')) {
    state.coverOverlay = parseInt(document.getElementById('cfgCoverOverlay').value, 10);
  }
  if (document.getElementById('cfgCoverBrightness')) {
    state.coverBrightness = parseInt(document.getElementById('cfgCoverBrightness').value, 10);
  }
  if (document.getElementById('cfgCoverContrast')) {
    state.coverContrast = parseInt(document.getElementById('cfgCoverContrast').value, 10);
  }
  if (document.getElementById('cfgCoverOpacity')) {
    state.coverOpacity = parseInt(document.getElementById('cfgCoverOpacity').value, 10);
  }
  if (document.getElementById('cfgCoverHalfRatio')) {
    state.coverHalfRatio = parseInt(document.getElementById('cfgCoverHalfRatio').value, 10);
  }
  if (document.getElementById('cfgCustomX')) {
    state.coverCustomX = parseFloat(document.getElementById('cfgCustomX').value);
  }
  if (document.getElementById('cfgCustomY')) {
    state.coverCustomY = parseFloat(document.getElementById('cfgCustomY').value);
  }
  if (document.getElementById('cfgCustomW')) {
    state.coverCustomW = parseInt(document.getElementById('cfgCustomW').value, 10);
  }
  if (document.getElementById('cfgCustomH')) {
    state.coverCustomH = parseInt(document.getElementById('cfgCustomH').value, 10);
  }
  if (document.getElementById('cfgCustomRot')) {
    state.coverCustomRotation = parseInt(document.getElementById('cfgCustomRot').value, 10);
  }

  // Canva QR Inputs
  state.qrXPercent = parseFloat(document.getElementById('cfgQrX').value);
  state.qrYPercent = parseFloat(document.getElementById('cfgQrY').value);
  state.qrSizePercent = parseFloat(document.getElementById('cfgQrSizeRange').value);
  state.qrRotation = parseInt(document.getElementById('cfgQrRotation').value, 10);
  state.qrBadgeStyle = document.getElementById('cfgQrBadgeStyle').value;
  state.canvaQrDark = document.getElementById('cfgCanvaQrDark').value;
  state.canvaQrLight = document.getElementById('cfgCanvaQrLight').value;
  state.qrBgTransparent = document.getElementById('cfgQrBgTransparent').checked;
  state.badgeTextColor = document.getElementById('cfgBadgeTextColor').value;
  state.qrPadding = parseInt(document.getElementById('cfgQrPadding').value, 10);
  state.qrRadius = parseInt(document.getElementById('cfgQrRadius').value, 10);
  state.qrLocked = document.getElementById('cfgQrLocked').checked;
  state.showAttendee = document.getElementById('cfgShowAttendee').checked;
  state.showRef = document.getElementById('cfgShowRef').checked;
  state.showTier = document.getElementById('cfgShowTier').checked;
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
  const darkInput = document.getElementById('cfgCanvaQrDark');
  if (darkInput) darkInput.value = color;
  renderCanvas();
  showToast(`QR Barcode color set to ${color}`, 'info');
}

// Download High-Res Image (Canva Export Feature)
function downloadCardImage() {
  selectQr(false); // Hide selection frame for clean export

  const target = state.cardMode === 'canva' 
    ? document.getElementById('canvaCardArtboard') 
    : document.getElementById('cardArtboard');

  showToast('Generating ultra high-resolution card image (3x HD)...', 'info');

  setTimeout(() => {
    html2canvas(target, {
      scale: 3, // 3x ultra-sharp resolution for printing
      useCORS: true,
      backgroundColor: null
    }).then(canvas => {
      const link = document.createElement('a');
      link.download = `canva_invitation_pass_${Date.now()}.png`;
      link.href = canvas.toDataURL('image/png');
      link.click();
      showToast('Invitation Card downloaded successfully (PNG)!', 'success');
      selectQr(true);
    }).catch(err => {
      window.Toast.error('Export error: ' + err);
      selectQr(true);
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

    // Auto-fit canvas to viewport when switching to preview on mobile/tablet
    setTimeout(() => {
      zoomCanvasFit();
    }, 60);
  }
}

function setCanvasZoom(scale) {
  // Clamp zoom between 20% and 200%
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

  // Available stage width minus horizontal padding
  const stageWidth = stage.clientWidth - (window.innerWidth <= 768 ? 24 : 48);
  const isLandscape = state.orientation === 'landscape';
  const nativeWidth = isLandscape ? 620 : 420;

  if (stageWidth > 0 && nativeWidth > 0) {
    let idealScale = stageWidth / nativeWidth;
    // Don't upscale past 1.0 automatically on large screens
    if (idealScale > 1.0) idealScale = 1.0;
    // Buffer
    idealScale = Math.min(1.0, idealScale * 0.98);
    setCanvasZoom(idealScale);
  }
}

// ============================================================
// Direct On-Canvas WYSIWYG Content Editing Engine
// ============================================================
function setupInlineCanvasEditing() {
  const bindings = [
    { elId: 'cardBadge',         field: 'badge',    inputId: 'cfgBadge' },
    { elId: 'cardTitle',         field: 'title',    inputId: 'cfgTitle' },
    { elId: 'cardSubtitle',      field: 'subtitle', inputId: 'cfgSubtitle' },
    { elId: 'cardQuote',         field: 'quote',    inputId: 'cfgQuote' },
    { elId: 'cardGuest',         field: 'guest',    inputId: 'cfgGuest' },
    { elId: 'cardDate',          field: 'date',     inputId: 'cfgDate' },
    { elId: 'cardTime',          field: 'time',     inputId: 'cfgTime' },
    { elId: 'cardVenue',         field: 'venue',    inputId: 'cfgVenue' },
    { elId: 'cardSeat',          field: 'seat',     inputId: 'cfgSeat' },
    { elId: 'cardFooter',        field: 'footer',   inputId: 'cfgFooter' },
    { elId: 'canvaBadgePill',    field: 'badge',    inputId: 'cfgBadge' },
    { elId: 'canvaAttendeeText', field: 'guest',    inputId: 'cfgGuest' }
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
        hint.innerHTML = `<?= svgIcon('edit', 12) ?> <span>Click text on card to edit directly</span>`;
      }
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
  syncStateToInputs();
  setCardMode(state.cardMode || 'canva');
  selectQr(true);
  setupInlineCanvasEditing();
  if (window.innerWidth <= 992) {
    setTimeout(zoomCanvasFit, 100);
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
