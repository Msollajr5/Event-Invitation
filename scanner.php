<?php
$pageTitle = 'QR Gate Verification Station';
$currentPage = 'scanner';
require_once __DIR__ . '/config/db.php';
require_auth();

$eventId = (int)($_GET['event_id'] ?? 1);
$gateId = (int)($_GET['gate_id'] ?? 1);

$allEvents = db_fetch_all("SELECT id, title FROM events ORDER BY start_date DESC");
$gates = db_fetch_all("SELECT id, name FROM gates WHERE event_id = :id ORDER BY id ASC", ['id' => $eventId]);
if (empty($gates)) {
    $gates = [['id' => 1, 'name' => 'Main Gate 1']];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Scanner Viewport Header -->
<div class="page-header">
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
      <span class="badge badge-success" style="padding:4px 10px;">ONLINE STATION</span>
      <span style="font-size:0.8rem;color:var(--text-muted);">&bull; Anti-Passback Enabled</span>
    </div>
    <h1 class="page-title">Gate Verification Terminal</h1>
    <p class="page-subtitle">Scan attendee digital QR codes or verify manual ticket reference keys</p>
  </div>

  <div class="page-actions">
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <select id="eventSelect" class="form-control" style="width:auto;min-width:200px;" onchange="location.href='scanner.php?event_id='+this.value">
        <?php foreach ($allEvents as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $eventId ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
        <?php endforeach; ?>
      </select>

      <select id="gateSelect" class="form-control" style="width:auto;min-width:140px;">
        <?php foreach ($gates as $g): ?>
          <option value="<?= $g['id'] ?>" <?= $g['id'] == $gateId ? 'selected' : '' ?>><?= e($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<div class="scanner-container">
  <!-- Left Side: Live Scanner Box & Quick Simulator -->
  <div>
    <div class="card" style="padding:0;overflow:hidden;background:#09090B;border:none;">
      <div style="padding:14px 20px;background:#18181B;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #27272A;">
        <div style="display:flex;align-items:center;gap:8px;color:#FFFFFF;font-size:0.88rem;font-weight:700;">
          <span style="width:8px;height:8px;border-radius:50%;background:#059669;display:inline-block;box-shadow:0 0 8px #059669;"></span>
          High-Speed Camera Scanner
        </div>
        <button id="toggleCameraBtn" class="btn btn-secondary btn-sm" style="background:#27272A;color:#FFFFFF;border-color:#3F3F46;">
          Start Camera
        </button>
      </div>

      <!-- Video Feed & Laser Scan Viewport -->
      <div class="scanner-box" id="scannerViewport">
        <div id="reader" style="width:100%;height:100%;"></div>
        <div class="scanner-laser" id="scannerLaser" style="display:none;"></div>
        <div id="cameraStandby" style="text-align:center;padding:40px 20px;color:#A1A1AA;">
          <div style="font-size:3rem;margin-bottom:12px;color:#FF7A00;">
            <?= svgIcon('qr', 54) ?>
          </div>
          <div style="font-size:1.1rem;font-weight:700;color:#FFFFFF;margin-bottom:6px;">Camera Standby Mode</div>
          <p style="font-size:0.85rem;max-width:320px;margin:0 auto 16px;">Click "Start Camera" above or type/scan a token below to verify instantly.</p>
        </div>
      </div>
    </div>

    <!-- Manual Keypad & Barcode Reader Input -->
    <div class="card" style="margin-top:20px;">
      <div class="card-header">
        <h3 class="card-title">Manual Token or Barcode Input</h3>
      </div>
      <div class="card-body">
        <form id="manualVerifyForm" onsubmit="event.preventDefault(); verifyPassToken(document.getElementById('manualTokenInput').value.trim());">
          <div style="display:flex;gap:10px;">
            <input type="text" id="manualTokenInput" class="form-control" placeholder="Scan or type token (e.g. DEMO-TOKEN-VIP-REG-01)..." style="font-size:0.95rem;font-family:monospace;font-weight:600;" autofocus>
            <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
              <?= svgIcon('check', 16) ?> Verify
            </button>
          </div>
        </form>

        <!-- Quick 1-Click Demo Tokens for Instant Simulation -->
        <div style="margin-top:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Quick Tests:</span>
          <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('DEMO-TOKEN-VIP-REG-01')">
            Reginald (VIP)
          </button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('DEMO-TOKEN-VVIP-AMI-02')">
            Amina (VVIP)
          </button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('DEMO-TOKEN-STF-MIC-07')">
            Michael (Staff)
          </button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('TKN-0114CB820450')">
            Erick Msola
          </button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="verifyPassToken('INVALID-SAMPLE-TOKEN')">
            Fake Token
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Side: Real-time Verification Result Card & Session History -->
  <div>
    <!-- Result Card -->
    <div class="card" id="resultCard" style="min-height:340px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:32px 24px;">
      <div id="resultStandby">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--surface-3);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:var(--text-muted);">
          <?= svgIcon('qr', 36) ?>
        </div>
        <h4 style="font-weight:700;color:var(--text-primary);margin-bottom:6px;">Ready to Scan</h4>
        <p style="font-size:0.85rem;color:var(--text-secondary);max-width:280px;margin:0 auto;">
          Align attendee's digital pass under the camera or submit a pass token.
        </p>
      </div>

      <div id="resultContent" style="display:none;width:100%;">
        <!-- Injected dynamically via JS -->
      </div>
    </div>

    <!-- Station Audit History -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Session Verifications</h3>
        <span class="badge badge-secondary" id="sessionCountBadge">0 scans</span>
      </div>
      <div class="card-body" style="padding:10px 14px;max-height:280px;overflow-y:auto;" id="sessionLogList">
        <p style="text-align:center;color:var(--text-muted);font-size:0.82rem;padding:20px 0;" id="emptyLogMsg">
          No scans in this session yet.
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

function toggleCamera() {
  if (isCameraActive) {
    stopCamera();
  } else {
    startCamera();
  }
}

function startCamera() {
  const laser = document.getElementById('scannerLaser');
  const standby = document.getElementById('cameraStandby');
  const btn = document.getElementById('toggleCameraBtn');

  html5QrCode = new Html5Qrcode("reader");
  const config = { fps: 10, qrbox: { width: 250, height: 250 } };

  html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
    .then(() => {
      isCameraActive = true;
      standby.style.display = 'none';
      laser.style.display = 'block';
      btn.textContent = 'Stop Camera';
      btn.classList.replace('btn-secondary', 'btn-danger');
    })
    .catch(err => {
      window.Toast.warning("Unable to open camera: " + err + ". Please use manual token verification below.");
    });
}

function stopCamera() {
  if (html5QrCode) {
    html5QrCode.stop().then(() => {
      isCameraActive = false;
      document.getElementById('cameraStandby').style.display = 'block';
      document.getElementById('scannerLaser').style.display = 'none';
      const btn = document.getElementById('toggleCameraBtn');
      btn.textContent = 'Start Camera';
      btn.classList.replace('btn-danger', 'btn-secondary');
    });
  }
}

document.getElementById('toggleCameraBtn').addEventListener('click', toggleCamera);

function onScanSuccess(decodedText) {
  // Extract token from full URL or bare string
  let token = decodedText.trim();
  if (token.includes('token=')) {
    const url = new URL(token);
    token = url.searchParams.get('token') || token;
  }
  verifyPassToken(token);
}

function verifyPassToken(token) {
  if (!token) return;

  const eventId = document.getElementById('eventSelect').value;
  const gateId = document.getElementById('gateSelect').value;

  const resultStandby = document.getElementById('resultStandby');
  const resultContent = document.getElementById('resultContent');

  resultStandby.style.display = 'none';
  resultContent.style.display = 'block';
  resultContent.innerHTML = '<div style="padding:40px 0;"><div class="spinner" style="margin:0 auto 12px;width:36px;height:36px;border:3px solid rgba(255,122,0,0.2);border-top-color:#FF7A00;border-radius:50%;animation:spin 0.8s linear infinite;"></div><div style="font-weight:600;color:var(--text-secondary);">Verifying Pass in Registry...</div></div>';

  fetch(`api/verify.php?token=${encodeURIComponent(token)}&event_id=${eventId}&gate_id=${gateId}`)
    .then(res => res.json())
    .then(data => {
      renderVerificationResult(data, token);
    })
    .catch(err => {
      resultContent.innerHTML = `<div style="color:var(--danger);font-weight:700;">Network communication error with verification API</div>`;
    });
}

function renderVerificationResult(data, token) {
  const resultContent = document.getElementById('resultContent');
  sessionScanCount++;
  document.getElementById('sessionCountBadge').textContent = `${sessionScanCount} scans`;
  document.getElementById('emptyLogMsg')?.remove();

  if (data.status === 'VALID') {
    window.playChime('success');
    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(5,150,105,0.12);color:#059669;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(5,150,105,0.35);">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <span class="badge badge-success" style="font-size:0.8rem;padding:5px 14px;margin-bottom:8px;">ACCESS GRANTED</span>
      <h2 style="font-size:1.6rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">${data.data.guest_name}</h2>
      <div style="font-size:0.9rem;font-weight:600;color:#FF7A00;margin-bottom:14px;">${data.data.guest_category} &bull; ${data.data.reference_code}</div>

      <div style="background:var(--surface-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 18px;text-align:left;font-size:0.85rem;margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
          <span style="color:var(--text-muted);">Attendees Allowed:</span>
          <strong>${data.data.permitted_attendees} Person(s)</strong>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
          <span style="color:var(--text-muted);">Entrance Gate:</span>
          <strong>${data.data.entrance_gate}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-muted);">Verified At:</span>
          <strong>${data.data.checked_in_at}</strong>
        </div>
      </div>
    `;

    appendSessionLog(data.data.guest_name, 'APPROVED', '#059669');
  } 
  else if (data.status === 'DUPLICATE') {
    window.playChime('error');
    const safeGuestName = (data.data?.guest_name || 'Pass Bearer').replace(/'/g, "\\'");
    const safeToken = (token || '').replace(/'/g, "\\'");
    const checkinId = data.data?.check_in_id || 0;
    const invId = data.data?.invitation_id || 0;

    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(220,38,38,0.12);color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(220,38,38,0.35);">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
      <span class="badge badge-danger" style="font-size:0.8rem;padding:5px 14px;margin-bottom:8px;">DUPLICATE DETECTED</span>
      <h2 style="font-size:1.4rem;font-weight:800;color:#DC2626;margin-bottom:4px;">ANTI-PASSBACK ALERT</h2>
      <div style="font-size:0.95rem;font-weight:700;color:var(--text-primary);margin-bottom:14px;">${data.data?.guest_name || 'Pass Bearer'}</div>

      <div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.25);border-radius:var(--radius-sm);padding:14px 18px;text-align:left;font-size:0.85rem;margin-bottom:14px;">
        <div style="color:#DC2626;font-weight:700;margin-bottom:6px;">⚠️ Ticket already checked in previously:</div>
        <div>First Check-in: <strong>${data.data?.first_checked_in_at}</strong></div>
        <div>First Gate: <strong>${data.data?.first_gate}</strong></div>
      </div>

      <button type="button" class="btn btn-secondary btn-sm" onclick="reversePassFromScanner(${checkinId}, ${invId}, '${safeGuestName}', '${safeToken}')" style="color:var(--danger);border-color:rgba(220,38,38,0.3);width:100%;justify-content:center;font-size:0.82rem;padding:9px 14px;margin-top:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        <span>Reverse Previous Check-In (Authorize Re-entry)</span>
      </button>
    `;

    appendSessionLog(data.data?.guest_name || 'Duplicate Pass', 'DUPLICATE', '#DC2626');
  }
  else {
    window.playChime('error');
    resultContent.innerHTML = `
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(220,38,38,0.12);color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      </div>
      <span class="badge badge-danger" style="font-size:0.8rem;padding:5px 14px;margin-bottom:8px;">INVALID TICKET</span>
      <h3 style="font-weight:800;color:var(--text-primary);margin-bottom:4px;">Unrecognized Pass Token</h3>
      <p style="font-size:0.85rem;color:var(--text-secondary);">${data.message}</p>
    `;

    appendSessionLog(token.substring(0, 16), 'INVALID', '#DC2626');
  }

  document.getElementById('manualTokenInput').value = '';
}

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
    window.Toast.warning('A reason is required to reverse check-in.');
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
      window.Toast.success(data.message || 'Check-in reversed successfully.');
      // Auto re-verify to grant access
      if (token) {
        verifyPassToken(token);
      }
    } else {
      window.Toast.error(data.message || 'Failed to reverse check-in.');
    }
  } catch (err) {
    window.Toast.error('Network error communicating with check-in reversal API.');
  }
}

function appendSessionLog(title, status, color) {
  const list = document.getElementById('sessionLogList');
  const now = new Date().toLocaleTimeString();
  const item = document.createElement('div');
  item.style.cssText = 'display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-bottom:1px solid var(--border);font-size:0.82rem;';
  item.innerHTML = `
    <div>
      <div style="font-weight:600;color:var(--text-primary);">${title}</div>
      <div style="font-size:0.72rem;color:var(--text-muted);">${now}</div>
    </div>
    <span class="badge" style="background:${color}15;color:${color};border:1px solid ${color}35;">${status}</span>
  `;
  list.insertBefore(item, list.firstChild);
}
</script>

<style>
@keyframes spin {
  100% { transform: rotate(360deg); }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
