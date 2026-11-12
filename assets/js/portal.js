/**
 * Leula Events Management Portal — Main JavaScript Architecture
 * Direct adaptation of Neilos Portal JS Engine
 */

document.addEventListener('DOMContentLoaded', () => {

  // 1. Sidebar Management (Desktop Collapse & Mobile Drawer)
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggleBtn = document.getElementById('sidebarToggle');

  function isMobile() {
    return window.innerWidth <= 1024;
  }

  function openSidebarDrawer() {
    if (!sidebar) return;
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebarDrawer() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  function toggleSidebar() {
    if (isMobile()) {
      sidebar.classList.contains('open') ? closeSidebarDrawer() : openSidebarDrawer();
    } else {
      const isCollapsed = sidebar.classList.toggle('collapsed');
      try {
        localStorage.setItem('leula_sidebar_collapsed', isCollapsed ? '1' : '0');
      } catch (e) {}
    }
  }

  if (toggleBtn) {
    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleSidebar();
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebarDrawer);
  }

  // Restore collapsed sidebar on desktop
  try {
    if (localStorage.getItem('leula_sidebar_collapsed') === '1' && window.innerWidth > 1024) {
      sidebar?.classList.add('collapsed');
    }
  } catch (e) {}

  // 2. Modals System (Neilos Standard)
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modalId = btn.dataset.modalOpen;
      const targetModal = document.getElementById(modalId);
      if (targetModal) {
        targetModal.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  function closeModal(backdrop) {
    backdrop?.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('[data-modal-close], .modal-backdrop').forEach(el => {
    el.addEventListener('click', (e) => {
      if (e.target === el) {
        closeModal(el.closest('.modal-backdrop') || el);
      }
    });
  });

  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => e.stopPropagation());
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop.open').forEach(bd => closeModal(bd));
      if (sidebar?.classList.contains('open')) closeSidebarDrawer();
    }
  });

  // 3. Tab Navigation Engine
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.tabGroup || btn.closest('.tabs')?.dataset.group || 'default';
      const target = btn.dataset.tab;

      // Deactivate siblings
      btn.closest('.tabs').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      // Toggle tab panels
      document.querySelectorAll(`[data-tab-panel][data-tab-group="${group}"]`).forEach(p => {
        p.classList.remove('active');
      });
      const activePanel = document.querySelector(`[data-tab-panel="${target}"][data-tab-group="${group}"]`);
      if (activePanel) {
        activePanel.classList.add('active');
      }
    });
  });

  // 4. Alert Countdown & Progress Bar (Neilos Feature)
  document.querySelectorAll('.alert').forEach(alert => {
    let seconds = 15;
    const progressBar = document.createElement('div');
    progressBar.style.cssText = 'position:absolute;bottom:0;left:0;height:3.5px;width:100%;background:currentColor;opacity:0.35;transition:width 1s linear;';
    alert.appendChild(progressBar);

    const timer = setInterval(() => {
      seconds--;
      if (seconds >= 0) {
        progressBar.style.width = `${(seconds / 15) * 100}%`;
      }
      if (seconds <= 0) {
        clearInterval(timer);
        alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease, max-height 0.4s ease, padding 0.4s ease, margin 0.4s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        setTimeout(() => {
          alert.style.maxHeight = '0';
          alert.style.padding = '0';
          alert.style.margin = '0';
          alert.style.overflow = 'hidden';
          setTimeout(() => alert.remove(), 400);
        }, 400);
      }
    }, 1000);
  });

  // 5. Table Live Filter Search
  document.querySelectorAll('input[data-table-search]').forEach(input => {
    input.addEventListener('input', () => {
      const targetTableSelector = input.dataset.tableSearch;
      const term = input.value.toLowerCase().trim();
      const rows = document.querySelectorAll(`${targetTableSelector} tbody tr:not(.empty-row)`);

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
      });
    });
  });

});

// 6. Global Web Audio Sound Chime Synthesizer (for QR verification)
window.playChime = function(type) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);

    if (type === 'success') {
      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
      osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.35);
    } else {
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(220, ctx.currentTime);
      osc.frequency.setValueAtTime(164.81, ctx.currentTime + 0.12);
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.45);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.45);
    }
  } catch (e) {
    console.warn('AudioContext not allowed or supported', e);
  }
};

// 7. Clipboard Copy Helper with Toast
window.copyToClipboard = function(text, label = 'Copied!') {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text);
  } else {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
  }
  showToast(label, 'success');
};

// 8. Toast Notification Helper
window.showToast = function(message, type = 'success') {
  let toastContainer = document.getElementById('toastContainer');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toastContainer';
    toastContainer.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const bg = type === 'success' ? '#059669' : '#DC2626';
  toast.style.cssText = `background:${bg};color:#fff;padding:12px 20px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.18);font-size:0.875rem;font-weight:600;display:flex;align-items:center;gap:10px;animation:fadeUp 0.3s ease;pointer-events:all;`;
  toast.innerHTML = `<span>${message}</span>`;
  toastContainer.appendChild(toast);

  setTimeout(() => {
    toast.style.transition = 'all 0.3s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
};

