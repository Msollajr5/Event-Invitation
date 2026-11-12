/**
 * Leula Events — Professional Liquid Glass Toast & Confirmation Dialog Engine
 * Eliminates browser-native alert(), confirm(), and prompt() across the entire application.
 */

(function () {
  'use strict';

  // Ensure Toast Container Exists in DOM
  function getToastContainer() {
    let container = document.getElementById('leulaToastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'leulaToastContainer';
      container.className = 'leula-toast-container';
      container.setAttribute('aria-live', 'polite');
      container.setAttribute('aria-atomic', 'true');
      document.body.appendChild(container);
    }
    return container;
  }

  // SVG Icons for Notification Types
  const ICONS = {
    success: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>`,
    error:   `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
    warning: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
    info:    `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`
  };

  const DEFAULT_TITLES = {
    success: 'Success',
    error:   'Action Failed',
    warning: 'Attention',
    info:    'Information'
  };

  /**
   * Display an Application Toast Notification
   * @param {string} message - Toast message text
   * @param {'success'|'error'|'warning'|'info'} type - Semantic notification level
   * @param {string|null} title - Optional custom title
   * @param {number} duration - Auto-dismiss timeout in ms (default 4500ms)
   */
  function showToast(message, type = 'info', title = null, duration = 4500) {
    if (!message) return;
    const normalizedType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    const container = getToastContainer();

    const toast = document.createElement('div');
    toast.className = `leula-toast ${normalizedType}`;
    toast.setAttribute('role', 'alert');

    const displayTitle = title || DEFAULT_TITLES[normalizedType];
    const iconSvg = ICONS[normalizedType] || ICONS.info;

    toast.innerHTML = `
      <div class="leula-toast-icon">${iconSvg}</div>
      <div class="leula-toast-content">
        <div class="leula-toast-title">${escapeHtml(displayTitle)}</div>
        <div class="leula-toast-msg">${escapeHtml(message)}</div>
      </div>
      <button type="button" class="leula-toast-close" aria-label="Close notification">&times;</button>
    `;

    // Close button handler
    const closeBtn = toast.querySelector('.leula-toast-close');
    let timerId = null;

    function dismissToast() {
      if (timerId) clearTimeout(timerId);
      toast.classList.add('hiding');
      toast.addEventListener('animationend', () => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, { once: true });
    }

    closeBtn.addEventListener('click', dismissToast);

    // Auto-dismiss timer
    if (duration > 0) {
      timerId = setTimeout(dismissToast, duration);
      // Pause timer on hover
      toast.addEventListener('mouseenter', () => {
        if (timerId) clearTimeout(timerId);
      });
      toast.addEventListener('mouseleave', () => {
        timerId = setTimeout(dismissToast, 2000);
      });
    }

    container.appendChild(toast);
    return toast;
  }

  /**
   * Display an Application-Level Confirmation Dialog
   * Replaces native window.confirm() and prompt() with a Promise-based modal.
   * @param {Object} options
   * @returns {Promise<boolean|{confirmed: boolean, value?: string}>}
   */
  function showConfirmDialog(options = {}) {
    return new Promise((resolve) => {
      const {
        title = 'Confirm Action',
        subtitle = 'Please verify before proceeding',
        message = 'Are you sure you want to proceed?',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        type = 'warning', // 'primary', 'danger', 'warning', 'success'
        input = false,     // if true, shows a text input field (replaces prompt())
        inputLabel = 'Reason (Required):',
        inputPlaceholder = 'Enter details...',
        inputRequired = false,
        inputDefault = ''
      } = options;

      const backdrop = document.createElement('div');
      backdrop.className = 'leula-dialog-backdrop';

      const iconSvg = ICONS[type] || ICONS.warning;
      const confirmBtnClass = (type === 'danger') ? 'confirm-danger' : 'confirm-primary';

      const inputHtml = input ? `
        <div class="leula-dialog-input-wrap">
          <label class="leula-dialog-input-label">${escapeHtml(inputLabel)}</label>
          <input type="text" class="leula-dialog-input" id="leulaDialogInput" 
                 placeholder="${escapeHtml(inputPlaceholder)}" 
                 value="${escapeHtml(inputDefault)}" />
          <div id="leulaDialogInputError" style="display:none;color:#DC2626;font-size:0.75rem;margin-top:4px;font-weight:600;"></div>
        </div>
      ` : '';

      backdrop.innerHTML = `
        <div class="leula-dialog-card" role="dialog" aria-modal="true">
          <div class="leula-dialog-header">
            <div class="leula-dialog-icon ${type}">${iconSvg}</div>
            <div>
              <h3 class="leula-dialog-title">${escapeHtml(title)}</h3>
              <p class="leula-dialog-subtitle">${escapeHtml(subtitle)}</p>
            </div>
          </div>
          <div class="leula-dialog-body">
            <div>${message}</div>
            ${inputHtml}
          </div>
          <div class="leula-dialog-footer">
            <button type="button" class="leula-dialog-btn cancel" id="leulaDialogCancelBtn">${escapeHtml(cancelText)}</button>
            <button type="button" class="leula-dialog-btn ${confirmBtnClass}" id="leulaDialogConfirmBtn">${escapeHtml(confirmText)}</button>
          </div>
        </div>
      `;

      function cleanUp() {
        document.removeEventListener('keydown', onKeyDown);
        if (backdrop.parentNode) {
          backdrop.parentNode.removeChild(backdrop);
        }
      }

      function onCancel() {
        cleanUp();
        if (input) {
          resolve({ confirmed: false, value: null });
        } else {
          resolve(false);
        }
      }

      function onConfirm() {
        if (input) {
          const inputEl = backdrop.querySelector('#leulaDialogInput');
          const errEl = backdrop.querySelector('#leulaDialogInputError');
          const val = inputEl ? inputEl.value.trim() : '';

          if (inputRequired && !val) {
            if (errEl) {
              errEl.textContent = 'This field is required before proceeding.';
              errEl.style.display = 'block';
            }
            if (inputEl) inputEl.focus();
            return;
          }

          cleanUp();
          resolve({ confirmed: true, value: val });
        } else {
          cleanUp();
          resolve(true);
        }
      }

      function onKeyDown(e) {
        if (e.key === 'Escape') {
          e.preventDefault();
          onCancel();
        } else if (e.key === 'Enter' && (!input || e.target.id === 'leulaDialogInput')) {
          e.preventDefault();
          onConfirm();
        }
      }

      backdrop.querySelector('#leulaDialogCancelBtn').addEventListener('click', onCancel);
      backdrop.querySelector('#leulaDialogConfirmBtn').addEventListener('click', onConfirm);
      backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) onCancel();
      });
      document.addEventListener('keydown', onKeyDown);

      document.body.appendChild(backdrop);

      // Auto-focus input or confirm button
      setTimeout(() => {
        const inp = backdrop.querySelector('#leulaDialogInput');
        if (inp) {
          inp.focus();
        } else {
          const btn = backdrop.querySelector('#leulaDialogConfirmBtn');
          if (btn) btn.focus();
        }
      }, 50);
    });
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
  }

  // Intercept and eliminate native browser alert(), confirm(), and prompt()
  window.alert = function (msg) {
    showToast(String(msg), 'info');
  };

  window.prompt = function (message, defaultVal) {
    showToast(String(message), 'warning');
    return defaultVal || null;
  };

  // Expose to window
  window.showToast = showToast;
  window.showConfirmDialog = showConfirmDialog;
  window.Toast = {
    success: (msg, title, duration) => showToast(msg, 'success', title, duration),
    error:   (msg, title, duration) => showToast(msg, 'error', title, duration),
    warning: (msg, title, duration) => showToast(msg, 'warning', title, duration),
    info:    (msg, title, duration) => showToast(msg, 'info', title, duration)
  };
  window.ConfirmDialog = showConfirmDialog;

})();
