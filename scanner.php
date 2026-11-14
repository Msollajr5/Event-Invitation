<?php
$pageTitle = 'QR Gate Verification Station';
$currentPage = 'scanner';
require_once __DIR__ . '/config/db.php';
require_auth();

$userEvents = get_user_events();
$eventId = (int)($_GET['event_id'] ?? get_active_event_id() ?? 1);
$gateId = (int)($_GET['gate_id'] ?? 1);

// Get gates for this event
$gates = db_fetch_all("SELECT id, name, code, allowed_categories FROM gates WHERE event_id = :id ORDER BY id ASC", ['id' => $eventId]);
if (empty($gates)) {
    $gates = [['id' => 1, 'name' => 'Main Gate 1', 'code' => 'GATE-1', 'allowed_categories' => 'all']];
}

$currentGate = null;
foreach ($gates as $g) {
    if ((int)$g['id'] === $gateId) {
        $currentGate = $g;
        break;
    }
}
if (!$currentGate) {
    $currentGate = $gates[0];
    $gateId = (int)$currentGate['id'];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Scanner Viewport Header -->
<div class="page-header" style="margin-bottom:20px;">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
      <span class="badge badge-success" style="padding:4px 10px;display:inline-flex;align-items:center;gap:5px;">
        <span style="width:6px;height:6px;border-radius:50%;background:#10b981;box-shadow:0 0 8px #10b981;"></span>
        ONLINE STATION
      </span>
      <span style="font-size:0.8rem;color:var(--text-muted);">&bull; Anti-Passback Active &bull; Category-Gated</span>
    </div>
    <h1 class="page-title">Gate Verification Terminal</h1>
    <p class="page-subtitle">Real-time camera QR scanning, category access verification, and manual guest search</p>
  </div>

  <div class="page-actions">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <div style="display:flex;align-items:center;gap:6px;background:var(--card-bg, #fff);border:1px solid var(--border-color);border-radius:8px;padding:4px 10px;">
        <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Gate:</span>
        <select id="gateSelect" class="form-control" style="border:none;background:transparent;padding:4px;font-size:0.85rem;font-weight:600;min-width:160px;cursor:pointer;" onchange="updateGateSelection(this.value)">
          <?php foreach ($gates as $g): ?>
            <option value="<?= $g['id'] ?>" <?= ((int)$g['id'] === $gateId) ? 'selected' : '' ?> data-allowed="<?= e($g['allowed_categories'] ?? 'all') ?>">
              <?= e($g['name']) ?> (<?= strtoupper($g['allowed_categories'] ?? 'ALL') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="gateCategoryBadge" class="badge badge-info" style="font-size:0.75rem;padding:6px 12px;border-radius:20px;">
        Allowed: <?= strtoupper(e($currentGate['allowed_categories'] ?? 'ALL')) ?>
      </div>
    </div>
  </div>
</div>

<div class="scanner-container" style="display:grid;grid-template-columns:1.2fr 1fr;gap:24px;">
  <!-- Left Side: Live Scanner Box & Camera Controls -->
  <div>
    <div class="card" style="padding:0;overflow:hidden;background:#09090B;border:1px solid #27272A;border-radius:14px;box-shadow:0 10px 25px rgba(0,0,0,0.3);">
      <!-- Camera Bar -->
      <div style="padding:12px 18px;background:#18181B;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #27272A;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="display:flex;align-items:center;gap:6px;color:#FFFFFF;font-size:0.86rem;font-weight:700;">
            <span id="cameraStatusDot" style="width:8px;height:8px;border-radius:50%;background:#71717A;display:inline-block;"></span>
            <span id="cameraStatusText">Camera Inactive</span>
          </div>

          <!-- Camera Selector Dropdown -->
          <select id="cameraSourceSelect" style="display:none;background:#27272A;color:#FFFFFF;border:1px solid #3F3F46;border-radius:6px;padding:4px 8px;font-size:0.78rem;outline:none;cursor:pointer;max-width:180px;">
            <option value="">Detecting cameras...</option>
          </select>
        </div>

        <div style="display:flex;gap:8px;">
          <button id="switchFacingBtn" type="button" class="btn btn-secondary btn-sm" style="display:none;background:#27272A;color:#FFFFFF;border-color:#3F3F46;padding:5px 10px;" title="Switch Front/Rear Camera">
            <?= svgIcon('refresh', 13) ?>
            <span style="font-size:0.78rem;">Flip</span>
          </button>
          <button id="toggleCameraBtn" type="button" class="btn btn-primary btn-sm" style="padding:6px 16px;">
            Start Camera
          </button>
        </div>
      </div>

      <!-- Video Feed & Laser Scan Viewport -->
      <div class="scanner-box" id="scannerViewport" style="position:relative;min-height:360px;background:#000000;display:flex;align-items:center;justify-content:center;overflow:hidden;">
        <div id="reader" style="width:100%;height:100%;min-height:360px;"></div>
        <div class="scanner-laser" id="scannerLaser" style="display:none;"></div>
        
        <div id="cameraStandby" style="text-align:center;padding:48px 24px;color:#A1A1AA;z-index:2;">
          <div style="width:72px;height:72px;border-radius:50%;background:rgba(255,122,0,0.12);color:#FF7A00;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 20px rgba(255,122,0,0.2);">
            <?= svgIcon('qr', 38) ?>
          </div>
          <div style="font-size:1.15rem;font-weight:700;color:#FFFFFF;margin-bottom:6px;">High-Speed QR Scanner</div>
          <p style="font-size:0.85rem;max-width:320px;margin:0 auto 20px;color:#9CA3AF;">
            Click <strong>Start Camera</strong> to verify guest QR passes in real time with hardware autofocus.
          </p>
          <button type="button" onclick="startCamera()" class="btn btn-primary" style="margin:0 auto;">
            <?= svgIcon('eye', 16) ?> Enable Camera
          </button>
        </div>

        <!-- Camera Error Notification inside viewport -->
        <div id="cameraErrorBanner" style="display:none;position:absolute;bottom:12px;left:12px;right:12px;background:rgba(220,38,38,0.92);color:#FFF;padding:10px 14px;border-radius:8px;font-size:0.82rem;font-weight:600;z-index:10;backdrop-filter:blur(6px);">
          <div style="display:flex;align-items:center;gap:8px;">
            <?= svgIcon('alert', 16) ?>
            <span id="cameraErrorMessage" style="flex:1;">Camera access permission needed</span>
            <button type="button" onclick="document.getElementById('cameraErrorBanner').style.display='none'" style="background:transparent;border:none;color:#FFF;cursor:pointer;padding:2px 6px;">&times;</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Manual Keypad & Guest Name/Phone Search -->
    <div class="card" style="margin-top:20px;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
          <h3 class="card-title">Manual Guest & Token Lookup</h3>
          <p style="font-size:0.8rem;color:var(--text-muted);margin:2px 0 0;">Search by QR token, reference code, guest name or phone</p>
        </div>
        <span class="badge badge-secondary" style="font-size:0.75rem;">Instant Verification</span>
      </div>
      <div class="card-body">
        <form id="manualVerifyForm" onsubmit="event.preventDefault(); submitManualVerify();" style="position:relative;">
          <div style="display:flex;gap:10px;">
            <div style="flex:1;position:relative;">
              <input type="text" id="manualTokenInput" class="form-control" placeholder="Type token, ref code, or search guest name / phone..." style="font-size:0.92rem;font-weight:600;padding-right:32px;" autocomplete="off" oninput="handleManualSearchInput(this.value)">
              <div id="manualSearchSpinner" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);width:16px;height:16px;border:2px solid rgba(255,122,0,0.3);border-top-color:#FF7A00;border-radius:50%;animation:spin 0.6s linear infinite;"></div>
            </div>
            <button type="submit" class="btn btn-primary" style="padding:10px 22px;">
              <?= svgIcon('check', 16) ?> Verify
            </button>
          </div>

          <!-- Live Autocomplete Dropdown -->
          <div id="guestSearchResults" style="display:none;position:absolute;top:100%;left:0;right:80px;background:var(--card-bg, #fff);border:1px solid var(--border-color);border-radius:10px;margin-top:4px;box-shadow:0 12px 28px rgba(0,0,0,0.18);z-index:50;max-height:240px;overflow-y:auto;">
          </div>
        </form>

        <!-- Quick 1-Click Test Badges -->
        <div style="margin-top:14px;padding-top:12px;border-top:1px dashed var(--border-color);">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Fast Tests:</span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('DEMO-TOKEN-VIP-REG-01')">
              Dr. Reginald (VIP)
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('DEMO-TOKEN-VIP-AMI-02')">
              Hon. Amina (VVIP)
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('DEMO-TOKEN-VIP-JOS-03')">
              Eng. Joseph (VIP)
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('DEMO-TOKEN-REG-BAR-04')">
              Baraka Mushi (Regular)
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('NON-EXISTENT-PASS-KEY')">
              Invalid Token
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Side: Real-time Verification Result Card & Station Audit Log -->
  <div>
    <!-- Result Card -->
    <div class="card" id="resultCard" style="min-height:360px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:28px 24px;border-radius:14px;transition:all 0.3s ease;">
      <div id="resultStandby">
        <div style="width:76px;height:76px;border-radius:50%;background:rgba(255,122,0,0.06);border:2px dashed rgba(255,122,0,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:#FF7A00;">
          <?= svgIcon('qr', 38) ?>
        </div>
        <h4 style="font-weight:800;color:var(--text-primary);margin-bottom:6px;font-size:1.15rem;">Ready to Scan</h4>
        <p style="font-size:0.86rem;color:var(--text-secondary);max-width:280px;margin:0 auto;line-height:1.5;">
          Point the camera at an attendee's QR pass or select a guest from the manual search.
        </p>
      </div>

      <div id="resultContent" style="display:none;width:100%;">
        <!-- Injected dynamically via JS -->
      </div>
    </div>

    <!-- Station Audit History -->
    <div class="card" style="margin-top:20px;border-radius:14px;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
          <h3 class="card-title">Live Station Activity</h3>
          <p style="font-size:0.78rem;color:var(--text-muted);margin:0;">Recent scan verification history</p>
        </div>
        <span class="badge badge-secondary" id="sessionCountBadge">0 scans</span>
      </div>
      <div class="card-body" style="padding:8px 12px;max-height:260px;overflow-y:auto;" id="sessionLogList">
        <p style="text-align:center;color:var(--text-muted);font-size:0.82rem;padding:24px 0;" id="emptyLogMsg">
          No scans recorded in this verification session yet.
        </p>
      </div>
    </div>
  </div>
</div>

<!-- html5-qrcode Library -->
<script src="assets/js/html5-qrcode.min.js"></script>

<script>
let html5QrCode = null;
let isCameraActive = false;
let sessionScanCount = 0;
let isVerifying = false; // Mutex lock preventing concurrent duplicate scanning
let searchDebounceTimer = null;
let currentCameraId = null;
let currentFacingMode = "environment"; // default to rear camera

function updateGateSelection(gateId) {
  const select = document.getElementById('gateSelect');
  const opt = select.options[select.selectedIndex];
  const allowed = opt.getAttribute('data-allowed') || 'all';
  document.getElementById('gateCategoryBadge').textContent = 'Allowed: ' + allowed.toUpperCase();
}

// Camera device enumeration and population
async function discoverCameras() {
  const cameraSelect = document.getElementById('cameraSourceSelect');
  const flipBtn = document.getElementById('switchFacingBtn');

  try {
    const devices = await Html5Qrcode.getCameras();
    if (devices && devices.length > 0) {
      cameraSelect.innerHTML = '';
      devices.forEach((dev, idx) => {
        const option = document.createElement('option');
        option.value = dev.id;
        option.text = dev.label || `Camera ${idx + 1}`;
        cameraSelect.appendChild(option);
      });

      currentCameraId = devices[0].id;
      cameraSelect.style.display = 'inline-block';
      if (devices.length > 1) {
        flipBtn.style.display = 'inline-flex';
      }

      cameraSelect.onchange = function() {
        currentCameraId = this.value;
        if (isCameraActive) {
          stopCamera().then(() => startCamera());
        }
      };
    }
  } catch (err) {
    console.log("Camera discovery note:", err);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  discoverCameras();
});

function toggleCamera() {
  if (isCameraActive) {
    stopCamera();
  } else {
    startCamera();
  }
}

async function startCamera() {
  const laser = document.getElementById('scannerLaser');
  const standby = document.getElementById('cameraStandby');
  const btn = document.getElementById('toggleCameraBtn');
  const statusDot = document.getElementById('cameraStatusDot');
  const statusText = document.getElementById('cameraStatusText');
  const errBanner = document.getElementById('cameraErrorBanner');

  errBanner.style.display = 'none';

  if (!html5QrCode) {
    html5QrCode = new Html5Qrcode("reader");
  }

  const config = {
    fps: 12,
    qrbox: { width: 260, height: 260 },
    aspectRatio: 1.0
  };

  const cameraConstraint = currentCameraId ? currentCameraId : { facingMode: currentFacingMode };

  try {
    await html5QrCode.start(cameraConstraint, config, onScanSuccess);
    isCameraActive = true;
    standby.style.display = 'none';
    laser.style.display = 'block';
    btn.textContent = 'Stop Camera';
    btn.classList.replace('btn-primary', 'btn-danger');
    statusDot.style.background = '#059669';
    statusDot.style.boxShadow = '0 0 8px #059669';
    statusText.textContent = 'Camera Active (Scanning)';
  } catch (err) {
    isCameraActive = false;
    document.getElementById('cameraErrorMessage').textContent = "Camera Error: " + (err.message || err);
    errBanner.style.display = 'block';
    if (window.Toast) {
      window.Toast.warning("Unable to start camera. Please ensure camera permissions are allowed, or use manual guest lookup below.");
    }
  }
}

async function stopCamera() {
  if (html5QrCode && isCameraActive) {
    try {
      await html5QrCode.stop();
    } catch (e) {}
  }
  isCameraActive = false;
  document.getElementById('cameraStandby').style.display = 'block';
  document.getElementById('scannerLaser').style.display = 'none';
  const btn = document.getElementById('toggleCameraBtn');
  btn.textContent = 'Start Camera';
  btn.classList.replace('btn-danger', 'btn-primary');
  const statusDot = document.getElementById('cameraStatusDot');
  statusDot.style.background = '#71717A';
  statusDot.style.boxShadow = 'none';
  document.getElementById('cameraStatusText').textContent = 'Camera Inactive';
}

document.getElementById('toggleCameraBtn').addEventListener('click', toggleCamera);

// Camera Flip Switch
document.getElementById('switchFacingBtn').addEventListener('click', async () => {
  const cameraSelect = document.getElementById('cameraSourceSelect');
  if (cameraSelect.options.length > 1) {
    const nextIdx = (cameraSelect.selectedIndex + 1) % cameraSelect.options.length;
    cameraSelect.selectedIndex = nextIdx;
    currentCameraId = cameraSelect.value;
    if (isCameraActive) {
      await stopCamera();
      await startCamera();
    }
  } else {
    currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
    if (isCameraActive) {
      await stopCamera();
      await startCamera();
    }
  }
});

// Stop camera gracefully when leaving the page to free hardware
window.addEventListener('beforeunload', () => {
  if (isCameraActive && html5QrCode) {
    try { html5QrCode.stop(); } catch (e) {}
  }
});
window.addEventListener('pagehide', () => {
  if (isCameraActive && html5QrCode) {
    try { html5QrCode.stop(); } catch (e) {}
  }
});

function onScanSuccess(decodedText) {
  // Concurrency guard: debounce rapid scans
  if (isVerifying) return;

  let token = (decodedText || '').trim();
  if (token.includes('token=')) {
    try {
      const url = new URL(token);
      token = url.searchParams.get('token') || token;
    } catch (e) {}
  }
  verifyPassToken(token);
}

function submitManualVerify() {
  const val = document.getElementById('manualTokenInput').value.trim();
  if (!val) {
    if (window.Toast) window.Toast.warning('Please enter a pass token, reference code, or guest name.');
    return;
  }
  document.getElementById('guestSearchResults').style.display = 'none';
  verifyPassToken(val);
}

function verifyPassToken(token) {
  if (!token || isVerifying) return;
  isVerifying = true;

  const eventId = <?= (int)$eventId ?>;
  const gateId = document.getElementById('gateSelect').value;

  const resultStandby = document.getElementById('resultStandby');
  const resultContent = document.getElementById('resultContent');
  const resultCard = document.getElementById('resultCard');

  resultStandby.style.display = 'none';
  resultContent.style.display = 'block';
  resultCard.style.borderColor = 'var(--border-color)';
  resultContent.innerHTML = `
    <div style="padding:48px 0;">
      <div style="margin:0 auto 14px;width:38px;height:38px;border:3px solid rgba(255,122,0,0.2);border-top-color:#FF7A00;border-radius:50%;animation:spin 0.7s linear infinite;"></div>
      <div style="font-weight:700;color:var(--text-primary);font-size:0.95rem;">Verifying Pass in Registry...</div>
      <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;">Checking anti-passback and gate clearance</div>
    </div>
  `;

  fetch(`api/verify.php?token=${encodeURIComponent(token)}&event_id=${eventId}&gate_id=${gateId}`)
    .then(res => res.json())
    .then(data => {
      renderVerificationResult(data, token);
    })
    .catch(err => {
      resultContent.innerHTML = `
        <div style="color:var(--danger);font-weight:700;padding:24px 0;">
          <div style="font-size:1.1rem;margin-bottom:6px;">Network Connection Error</div>
          <p style="font-size:0.84rem;color:var(--text-secondary);">Could not reach the verification service. Please try again.</p>
        </div>
      `;
    })
    .finally(() => {
      // Debounce window to unlock scanner for next pass
      setTimeout(() => {
        isVerifying = false;
      }, 1200);
    });
}

function renderVerificationResult(data, token) {
  const resultContent = document.getElementById('resultContent');
  const resultCard = document.getElementById('resultCard');
  sessionScanCount++;
  document.getElementById('sessionCountBadge').textContent = `${sessionScanCount} scans`;
  document.getElementById('emptyLogMsg')?.remove();

  const d = data.data || {};

  if (data.status === 'VALID') {
    if (window.playChime) window.playChime('success');
    resultCard.style.borderColor = 'rgba(5,150,105,0.4)';

    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(5,150,105,0.12);color:#059669;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(5,150,105,0.35);">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <span class="badge badge-success" style="font-size:0.82rem;padding:5px 16px;margin-bottom:8px;font-weight:700;">ACCESS GRANTED</span>
      <h2 style="font-size:1.55rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">${escapeHtml(d.guest_name)}</h2>
      
      <div style="display:inline-flex;align-items:center;gap:6px;margin-bottom:14px;">
        <span class="badge" style="background:${d.category_color || '#3b82f6'};color:#fff;font-weight:700;font-size:0.78rem;padding:3px 10px;">${escapeHtml(d.guest_category)}</span>
        <span style="font-family:monospace;font-size:0.85rem;color:var(--text-muted);font-weight:600;">${escapeHtml(d.reference_code)}</span>
      </div>

      <div style="background:var(--surface-3, rgba(255,255,255,0.04));border:1px solid var(--border-color);border-radius:10px;padding:14px 18px;text-align:left;font-size:0.85rem;margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
          <span style="color:var(--text-muted);">Admitted Attendees:</span>
          <strong>${d.checked_in_attendees || 1} Person(s) (of ${d.permitted_attendees || 1} allowed)</strong>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
          <span style="color:var(--text-muted);">Entrance Gate:</span>
          <strong>${escapeHtml(d.entrance_gate)}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-muted);">Verified At:</span>
          <strong>${escapeHtml(d.checked_in_at)}</strong>
        </div>
      </div>
    `;

    appendSessionLog(d.guest_name, 'APPROVED', '#059669', d.guest_category);
    if (window.Toast) window.Toast.success(`Approved: ${d.guest_name} (${d.guest_category})`);
  } 
  else if (data.status === 'DUPLICATE') {
    if (window.playChime) window.playChime('error');
    resultCard.style.borderColor = 'rgba(220,38,38,0.4)';

    const safeGuestName = escapeHtml(d.guest_name || 'Pass Bearer');
    const checkinId = d.check_in_id || 0;
    const invId = d.invitation_id || 0;

    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(220,38,38,0.12);color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(220,38,38,0.35);">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
      <span class="badge badge-danger" style="font-size:0.82rem;padding:5px 16px;margin-bottom:8px;font-weight:700;">DUPLICATE ENTRY</span>
      <h2 style="font-size:1.45rem;font-weight:800;color:#DC2626;margin-bottom:4px;">ANTI-PASSBACK ALERT</h2>
      <div style="font-size:1.05rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;">${safeGuestName}</div>

      <div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.25);border-radius:10px;padding:14px 18px;text-align:left;font-size:0.85rem;margin-bottom:14px;">
        <div style="color:#DC2626;font-weight:700;margin-bottom:6px;">⚠️ Ticket already checked in:</div>
        <div>First Check-in: <strong>${escapeHtml(d.first_checked_in_at || 'Earlier')}</strong></div>
        <div>Gate Used: <strong>${escapeHtml(d.first_gate || 'Station')}</strong></div>
      </div>

      <button type="button" class="btn btn-secondary btn-sm" onclick="reversePassFromScanner(${checkinId}, ${invId}, '${safeGuestName.replace(/'/g, "\\'")}', '${token.replace(/'/g, "\\'")}')" style="color:var(--danger);border-color:rgba(220,38,38,0.35);width:100%;justify-content:center;font-size:0.84rem;padding:10px 14px;">
        <?= svgIcon('refresh', 14) ?>
        <span>Reverse Previous Check-In (Authorize Re-entry)</span>
      </button>
    `;

    appendSessionLog(d.guest_name || 'Duplicate Pass', 'DUPLICATE', '#DC2626', d.guest_category);
    if (window.Toast) window.Toast.error(`Duplicate Pass: Already checked in at ${d.first_checked_in_at}`);
  }
  else if (data.status === 'UNAUTHORIZED_CATEGORY') {
    if (window.playChime) window.playChime('error');
    resultCard.style.borderColor = 'rgba(245,158,11,0.4)';

    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(245,158,11,0.12);color:#F59E0B;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(245,158,11,0.35);">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      </div>
      <span class="badge badge-warning" style="font-size:0.82rem;padding:5px 16px;margin-bottom:8px;font-weight:700;">GATE RESTRICTION</span>
      <h2 style="font-size:1.4rem;font-weight:800;color:#F59E0B;margin-bottom:4px;">UNAUTHORIZED CATEGORY</h2>
      <div style="font-size:1.05rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;">${escapeHtml(d.guest_name)}</div>

      <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);border-radius:10px;padding:14px 18px;text-align:left;font-size:0.85rem;margin-bottom:14px;">
        <div style="margin-bottom:6px;">Guest Category: <strong style="color:${d.category_color || '#F59E0B'};">${escapeHtml(d.guest_category)}</strong></div>
        <div style="margin-bottom:6px;">Current Gate: <strong>${escapeHtml(d.entrance_gate)}</strong></div>
        <div style="color:#B45309;font-weight:700;">Permitted at this gate: ${escapeHtml(d.allowed_categories)}</div>
      </div>
      <p style="font-size:0.82rem;color:var(--text-secondary);margin:0;">Please direct this attendee to their designated entrance gate.</p>
    `;

    appendSessionLog(d.guest_name, 'WRONG GATE', '#F59E0B', d.guest_category);
    if (window.Toast) window.Toast.warning(`Gate Restricted: ${d.guest_category} not allowed at ${d.entrance_gate}`);
  }
  else if (data.status === 'WRONG_EVENT') {
    if (window.playChime) window.playChime('error');
    resultCard.style.borderColor = 'rgba(245,158,11,0.4)';

    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(245,158,11,0.12);color:#F59E0B;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <?= svgIcon('alert', 40) ?>
      </div>
      <span class="badge badge-warning" style="font-size:0.82rem;padding:5px 16px;margin-bottom:8px;font-weight:700;">CROSS-EVENT REJECTION</span>
      <h3 style="font-weight:800;color:var(--text-primary);margin-bottom:4px;">WRONG EVENT PASS</h3>
      <div style="font-size:1.05rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;">${escapeHtml(d.guest_name)}</div>

      <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);border-radius:10px;padding:14px 18px;text-align:left;font-size:0.85rem;margin-bottom:14px;">
        <div style="color:#B45309;font-weight:700;margin-bottom:6px;">This pass is valid ONLY for:</div>
        <div style="font-size:0.95rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">${escapeHtml(d.event_title)}</div>
        <div style="font-size:0.82rem;color:var(--text-muted);">${escapeHtml(d.venue_name || '')} &bull; ${escapeHtml(d.event_date || '')}</div>
      </div>
    `;

    appendSessionLog(d.guest_name || 'Cross-Event Pass', 'WRONG EVENT', '#F59E0B');
    if (window.Toast) window.Toast.warning(`Wrong Event: Ticket belongs to '${d.event_title}'`);
  }
  else if (data.status === 'REVOKED') {
    if (window.playChime) window.playChime('error');
    resultCard.style.borderColor = 'rgba(220,38,38,0.4)';

    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(220,38,38,0.12);color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(220,38,38,0.35);">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      </div>
      <span class="badge badge-danger" style="font-size:0.82rem;padding:5px 16px;margin-bottom:8px;font-weight:700;">PASS CANCELLED</span>
      <h2 style="font-size:1.45rem;font-weight:800;color:#DC2626;margin-bottom:4px;">REVOKED INVITATION</h2>
      <div style="font-size:1.05rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;">${escapeHtml(d.guest_name)}</div>

      <div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.25);border-radius:10px;padding:14px 18px;text-align:left;font-size:0.85rem;margin-bottom:14px;">
        <div style="color:#DC2626;font-weight:700;margin-bottom:4px;">Access Prohibited:</div>
        <div>This digital pass was explicitly revoked or cancelled by event management.</div>
        <div style="margin-top:6px;font-size:0.8rem;color:var(--text-muted);">Ref: ${escapeHtml(d.reference_code)}</div>
      </div>
    `;

    appendSessionLog(d.guest_name, 'REVOKED', '#DC2626');
    if (window.Toast) window.Toast.error(`Revoked Pass: Entry denied for ${d.guest_name}`);
  }
  else {
    // INVALID
    if (window.playChime) window.playChime('error');
    resultCard.style.borderColor = 'rgba(220,38,38,0.4)';

    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(220,38,38,0.12);color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      </div>
      <span class="badge badge-danger" style="font-size:0.82rem;padding:5px 16px;margin-bottom:8px;font-weight:700;">INVALID PASS</span>
      <h3 style="font-weight:800;color:var(--text-primary);margin-bottom:4px;">Unrecognized QR Code</h3>
      <p style="font-size:0.86rem;color:var(--text-secondary);max-width:320px;margin:0 auto 14px;">${escapeHtml(data.message || 'Token not found in system database.')}</p>
      <div style="font-family:monospace;font-size:0.78rem;background:var(--surface-3);padding:6px 12px;border-radius:6px;color:var(--text-muted);">${escapeHtml(token.substring(0, 32))}</div>
    `;

    appendSessionLog(token.substring(0, 16), 'INVALID', '#DC2626');
    if (window.Toast) window.Toast.error("Invalid QR code or token not recognized.");
  }

  document.getElementById('manualTokenInput').value = '';
}

// Live Autocomplete Guest Lookup
function handleManualSearchInput(val) {
  clearTimeout(searchDebounceTimer);
  const dropdown = document.getElementById('guestSearchResults');
  const spinner = document.getElementById('manualSearchSpinner');

  const trimmed = val.trim();
  if (trimmed.length < 2) {
    dropdown.style.display = 'none';
    spinner.style.display = 'none';
    return;
  }

  spinner.style.display = 'block';
  searchDebounceTimer = setTimeout(() => {
    fetch(`api/search_guest.php?event_id=<?= (int)$eventId ?>&q=${encodeURIComponent(trimmed)}`)
      .then(r => r.json())
      .then(res => {
        spinner.style.display = 'none';
        const items = res.results || [];
        if (items.length === 0) {
          dropdown.innerHTML = `<div style="padding:12px 14px;color:var(--text-muted);font-size:0.82rem;">No matching guests found. Press Verify to search token directly.</div>`;
          dropdown.style.display = 'block';
          return;
        }

        dropdown.innerHTML = items.map(g => `
          <div onclick="selectSearchedGuest('${escapeHtml(g.qr_token || g.reference_code || g.phone)}')" style="padding:10px 14px;border-bottom:1px solid var(--border-color);cursor:pointer;display:flex;justify-content:space-between;align-items:center;" onmouseover="this.style.background='var(--surface-3, #f4f4f5)'" onmouseout="this.style.background='transparent'">
            <div>
              <div style="font-weight:700;font-size:0.88rem;color:var(--text-primary);">${escapeHtml(g.full_name)}</div>
              <div style="font-size:0.75rem;color:var(--text-muted);">${escapeHtml(g.phone || '')} &bull; ${escapeHtml(g.reference_code || '')}</div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
              <span class="badge" style="background:${g.category_color || '#3b82f6'};color:#fff;font-size:0.7rem;padding:2px 8px;">${escapeHtml(g.category_name || 'Regular')}</span>
              ${g.is_checked_in ? '<span class="badge badge-warning" style="font-size:0.7rem;">Checked-In</span>' : '<span class="badge badge-success" style="font-size:0.7rem;">Ready</span>'}
            </div>
          </div>
        `).join('');
        dropdown.style.display = 'block';
      })
      .catch(() => {
        spinner.style.display = 'none';
      });
  }, 250);
}

function selectSearchedGuest(tokenOrRef) {
  document.getElementById('guestSearchResults').style.display = 'none';
  document.getElementById('manualTokenInput').value = tokenOrRef;
  verifyPassToken(tokenOrRef);
}

// Close search dropdown on click outside
document.addEventListener('click', (e) => {
  const form = document.getElementById('manualVerifyForm');
  if (form && !form.contains(e.target)) {
    document.getElementById('guestSearchResults').style.display = 'none';
  }
});

async function reversePassFromScanner(checkinId, invitationId, guestName, token) {
  const result = await window.showConfirmDialog({
    title: 'REVERSE CHECK-IN FOR RE-ENTRY',
    subtitle: 'Authorized Gate Officer Action',
    message: `Reverse check-in for <strong>${guestName}</strong>?<br><br>This will clear the previous admission and permit re-entry.`,
    confirmText: 'Reverse Check-in',
    cancelText: 'Keep As Duplicate',
    type: 'danger',
    input: true,
    inputLabel: 'Reason for Reversal (Required):',
    inputPlaceholder: 'e.g. Scanned wrong pass earlier, guest stepped out...',
    inputRequired: true
  });

  if (!result || !result.confirmed) return;

  const reason = (result.value || '').trim();
  if (!reason) {
    if (window.Toast) window.Toast.warning('A reason is required to reverse check-in.');
    return;
  }

  try {
    const fd = new URLSearchParams();
    if (checkinId) fd.append('check_in_id', checkinId);
    if (invitationId) fd.append('invitation_id', invitationId);
    fd.append('reason', reason);

    const res = await fetch('api/reverse_checkin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    });
    const data = await res.json();

    if (data.success) {
      if (window.Toast) window.Toast.success(data.message || 'Check-in reversed successfully.');
      if (token) {
        verifyPassToken(token);
      }
    } else {
      if (window.Toast) window.Toast.error(data.message || 'Failed to reverse check-in.');
    }
  } catch (err) {
    if (window.Toast) window.Toast.error('Network error communicating with check-in reversal API.');
  }
}

function appendSessionLog(title, status, color, category) {
  const list = document.getElementById('sessionLogList');
  const now = new Date().toLocaleTimeString();
  const item = document.createElement('div');
  item.style.cssText = 'display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-bottom:1px solid var(--border-color);font-size:0.82rem;';
  item.innerHTML = `
    <div>
      <div style="font-weight:700;color:var(--text-primary);">${escapeHtml(title)}</div>
      <div style="font-size:0.72rem;color:var(--text-muted);">${now} ${category ? '&bull; ' + escapeHtml(category) : ''}</div>
    </div>
    <span class="badge" style="background:${color}18;color:${color};border:1px solid ${color}40;font-weight:700;font-size:0.74rem;">${status}</span>
  `;
  list.insertBefore(item, list.firstChild);
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>

<style>
@keyframes spin {
  100% { transform: rotate(360deg); }
}
@media (max-width: 900px) {
  .scanner-container {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
