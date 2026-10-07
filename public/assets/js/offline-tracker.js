/**
 * CitiLife Global Offline & Network Status Toast Notification
 * Accurately detects real-time internet connectivity drops and restorations across all pages.
 * Adapts seamlessly between White (Light) and Dark themes.
 */
(function () {
  'use strict';

  if (window.__CITILIFE_OFFLINE_TRACKER__) return;
  window.__CITILIFE_OFFLINE_TRACKER__ = true;

  let hasBeenOffline = !navigator.onLine;
  let isCurrentlyOffline = !navigator.onLine;
  let toastEl = null;
  let hideTimeout = null;
  let pingInterval = null;
  let isChecking = false;

  function injectStyles() {
    if (document.getElementById('citilife-offline-styles')) return;

    const style = document.createElement('style');
    style.id = 'citilife-offline-styles';
    style.textContent = `
      @keyframes clPing {
        75%, 100% {
          transform: scale(2);
          opacity: 0;
        }
      }
      @keyframes clSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }

      /* Base Toast Container */
      #citilife-offline-toast {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translate(-50%, -160%);
        opacity: 0;
        z-index: 99999999;
        min-width: 310px;
        max-width: min(440px, calc(100vw - 32px));
        border-radius: 9999px;
        padding: 9px 16px 9px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease, background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        pointer-events: none;
        user-select: none;
        box-sizing: border-box;
      }

      #citilife-offline-toast.cl-visible {
        transform: translate(-50%, 0);
        opacity: 1;
        pointer-events: auto;
      }

      /* ========================================================= */
      /* 1. DEFAULT LIGHT THEME (Clean Crisp White Pill)           */
      /* ========================================================= */
      #citilife-offline-toast.cl-state-offline {
        background: rgba(255, 255, 255, 0.97);
        border: 1px solid rgba(239, 68, 68, 0.35);
        box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.12), 0 0 15px rgba(239, 68, 68, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04);
      }

      #citilife-offline-toast.cl-state-online {
        background: rgba(255, 255, 255, 0.97);
        border: 1px solid rgba(16, 185, 129, 0.4);
        box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.12), 0 0 15px rgba(16, 185, 129, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.04);
      }

      .cl-toast-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
      }

      .cl-toast-icon-wrap {
        position: relative;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background 0.3s ease, color 0.3s ease;
      }

      .cl-state-offline .cl-toast-icon-wrap {
        background: #fef2f2;
        color: #dc2626;
      }

      .cl-state-online .cl-toast-icon-wrap {
        background: #ecfdf5;
        color: #059669;
      }

      .cl-pulse-dot {
        position: absolute;
        top: 0px;
        right: 0px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
      }

      .cl-state-offline .cl-pulse-dot {
        background: #ef4444;
      }

      .cl-state-online .cl-pulse-dot {
        background: #10b981;
      }

      .cl-pulse-ring {
        position: absolute;
        top: 0px;
        right: 0px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: clPing 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
      }

      .cl-state-offline .cl-pulse-ring {
        background: rgba(239, 68, 68, 0.6);
      }

      .cl-state-online .cl-pulse-ring {
        background: rgba(16, 185, 129, 0.6);
      }

      .cl-toast-text-group {
        display: flex;
        flex-direction: column;
        min-width: 0;
      }

      .cl-toast-title {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        letter-spacing: -0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .cl-toast-subtitle {
        font-size: 11px;
        font-weight: 500;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .cl-state-offline .cl-toast-subtitle {
        color: #64748b;
      }

      .cl-state-online .cl-toast-subtitle {
        color: #059669;
      }

      .cl-toast-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
      }

      .cl-toast-btn-retry {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        font-size: 11px;
        font-weight: 600;
        padding: 5px 11px;
        border-radius: 9999px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        outline: none;
      }

      .cl-toast-btn-retry:hover {
        background: #e2e8f0;
        color: #0f172a;
      }

      .cl-toast-btn-retry:active {
        transform: scale(0.96);
      }

      .cl-toast-btn-close {
        background: transparent;
        border: none;
        color: #94a3b8;
        padding: 4px;
        border-radius: 50%;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        outline: none;
      }

      .cl-toast-btn-close:hover {
        color: #334155;
        background: #f1f5f9;
      }

      /* ========================================================= */
      /* 2. DARK MODE THEME OVERRIDES (Inside Portal Dark Mode)    */
      /* ========================================================= */
      body.theme-dark #citilife-offline-toast.cl-state-offline,
      html.dark #citilife-offline-toast.cl-state-offline,
      body.dark #citilife-offline-toast.cl-state-offline,
      html[data-theme="dark"] #citilife-offline-toast.cl-state-offline,
      body[data-theme="dark"] #citilife-offline-toast.cl-state-offline {
        background: rgba(17, 24, 39, 0.96) !important;
        border: 1px solid rgba(239, 68, 68, 0.5) !important;
        box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.45), 0 0 20px rgba(239, 68, 68, 0.25) !important;
      }

      body.theme-dark #citilife-offline-toast.cl-state-online,
      html.dark #citilife-offline-toast.cl-state-online,
      body.dark #citilife-offline-toast.cl-state-online,
      html[data-theme="dark"] #citilife-offline-toast.cl-state-online,
      body[data-theme="dark"] #citilife-offline-toast.cl-state-online {
        background: rgba(6, 78, 59, 0.96) !important;
        border: 1px solid rgba(16, 185, 129, 0.55) !important;
        box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.45), 0 0 20px rgba(16, 185, 129, 0.3) !important;
      }

      body.theme-dark #citilife-offline-toast .cl-toast-title,
      html.dark #citilife-offline-toast .cl-toast-title,
      body.dark #citilife-offline-toast .cl-toast-title,
      html[data-theme="dark"] #citilife-offline-toast .cl-toast-title,
      body[data-theme="dark"] #citilife-offline-toast .cl-toast-title {
        color: #ffffff !important;
      }

      body.theme-dark #citilife-offline-toast.cl-state-offline .cl-toast-subtitle,
      html.dark #citilife-offline-toast.cl-state-offline .cl-toast-subtitle,
      body.dark #citilife-offline-toast.cl-state-offline .cl-toast-subtitle,
      html[data-theme="dark"] #citilife-offline-toast.cl-state-offline .cl-toast-subtitle,
      body[data-theme="dark"] #citilife-offline-toast.cl-state-offline .cl-toast-subtitle {
        color: #94a3b8 !important;
      }

      body.theme-dark #citilife-offline-toast.cl-state-online .cl-toast-subtitle,
      html.dark #citilife-offline-toast.cl-state-online .cl-toast-subtitle,
      body.dark #citilife-offline-toast.cl-state-online .cl-toast-subtitle,
      html[data-theme="dark"] #citilife-offline-toast.cl-state-online .cl-toast-subtitle,
      body[data-theme="dark"] #citilife-offline-toast.cl-state-online .cl-toast-subtitle {
        color: #a7f3d0 !important;
      }

      body.theme-dark #citilife-offline-toast.cl-state-offline .cl-toast-icon-wrap,
      html.dark #citilife-offline-toast.cl-state-offline .cl-toast-icon-wrap,
      body.dark #citilife-offline-toast.cl-state-offline .cl-toast-icon-wrap,
      html[data-theme="dark"] #citilife-offline-toast.cl-state-offline .cl-toast-icon-wrap,
      body[data-theme="dark"] #citilife-offline-toast.cl-state-offline .cl-toast-icon-wrap {
        background: rgba(239, 68, 68, 0.18) !important;
        color: #f87171 !important;
      }

      body.theme-dark #citilife-offline-toast.cl-state-online .cl-toast-icon-wrap,
      html.dark #citilife-offline-toast.cl-state-online .cl-toast-icon-wrap,
      body.dark #citilife-offline-toast.cl-state-online .cl-toast-icon-wrap,
      html[data-theme="dark"] #citilife-offline-toast.cl-state-online .cl-toast-icon-wrap,
      body[data-theme="dark"] #citilife-offline-toast.cl-state-online .cl-toast-icon-wrap {
        background: rgba(16, 185, 129, 0.2) !important;
        color: #34d399 !important;
      }

      body.theme-dark #citilife-offline-toast.cl-state-offline .cl-pulse-ring,
      html.dark #citilife-offline-toast.cl-state-offline .cl-pulse-ring,
      body.dark #citilife-offline-toast.cl-state-offline .cl-pulse-ring,
      html[data-theme="dark"] #citilife-offline-toast.cl-state-offline .cl-pulse-ring,
      body[data-theme="dark"] #citilife-offline-toast.cl-state-offline .cl-pulse-ring {
        background: rgba(239, 68, 68, 0.8) !important;
      }

      body.theme-dark #citilife-offline-toast.cl-state-online .cl-pulse-ring,
      html.dark #citilife-offline-toast.cl-state-online .cl-pulse-ring,
      body.dark #citilife-offline-toast.cl-state-online .cl-pulse-ring,
      html[data-theme="dark"] #citilife-offline-toast.cl-state-online .cl-pulse-ring,
      body[data-theme="dark"] #citilife-offline-toast.cl-state-online .cl-pulse-ring {
        background: rgba(16, 185, 129, 0.8) !important;
      }

      body.theme-dark #citilife-offline-toast .cl-toast-btn-retry,
      html.dark #citilife-offline-toast .cl-toast-btn-retry,
      body.dark #citilife-offline-toast .cl-toast-btn-retry,
      html[data-theme="dark"] #citilife-offline-toast .cl-toast-btn-retry,
      body[data-theme="dark"] #citilife-offline-toast .cl-toast-btn-retry {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #f1f5f9 !important;
      }

      body.theme-dark #citilife-offline-toast .cl-toast-btn-retry:hover,
      html.dark #citilife-offline-toast .cl-toast-btn-retry:hover,
      body.dark #citilife-offline-toast .cl-toast-btn-retry:hover,
      html[data-theme="dark"] #citilife-offline-toast .cl-toast-btn-retry:hover,
      body[data-theme="dark"] #citilife-offline-toast .cl-toast-btn-retry:hover {
        background: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
      }

      body.theme-dark #citilife-offline-toast .cl-toast-btn-close,
      html.dark #citilife-offline-toast .cl-toast-btn-close,
      body.dark #citilife-offline-toast .cl-toast-btn-close,
      html[data-theme="dark"] #citilife-offline-toast .cl-toast-btn-close,
      body[data-theme="dark"] #citilife-offline-toast .cl-toast-btn-close {
        color: #94a3b8 !important;
      }

      body.theme-dark #citilife-offline-toast .cl-toast-btn-close:hover,
      html.dark #citilife-offline-toast .cl-toast-btn-close:hover,
      body.dark #citilife-offline-toast .cl-toast-btn-close:hover,
      html[data-theme="dark"] #citilife-offline-toast .cl-toast-btn-close:hover,
      body[data-theme="dark"] #citilife-offline-toast .cl-toast-btn-close:hover {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.1) !important;
      }

      .cl-spin-icon {
        animation: clSpin 1s linear infinite;
      }
    `;
    document.head.appendChild(style);
  }

  function createToastElement() {
    if (toastEl) return toastEl;

    injectStyles();

    toastEl = document.createElement('div');
    toastEl.id = 'citilife-offline-toast';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');

    toastEl.innerHTML = `
      <div class="cl-toast-left">
        <div class="cl-toast-icon-wrap" id="cl-toast-icon-wrap">
          <div class="cl-pulse-ring"></div>
          <div class="cl-pulse-dot"></div>
          <div id="cl-toast-icon-container"></div>
        </div>
        <div class="cl-toast-text-group">
          <span class="cl-toast-title" id="cl-toast-title">You are offline</span>
          <span class="cl-toast-subtitle" id="cl-toast-subtitle">No internet connection detected</span>
        </div>
      </div>
      <div class="cl-toast-actions" id="cl-toast-actions">
        <button type="button" class="cl-toast-btn-retry" id="cl-toast-retry-btn" title="Check connection">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" id="cl-toast-retry-icon">
            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
          </svg>
          <span id="cl-toast-retry-text">Retry</span>
        </button>
        <button type="button" class="cl-toast-btn-close" id="cl-toast-close-btn" aria-label="Dismiss">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    `;

    document.body.appendChild(toastEl);

    const retryBtn = document.getElementById('cl-toast-retry-btn');
    if (retryBtn) {
      retryBtn.addEventListener('click', function (e) {
        e.preventDefault();
        checkRealInternetConnection(true);
      });
    }

    const closeBtn = document.getElementById('cl-toast-close-btn');
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        hideToast();
      });
    }

    return toastEl;
  }

  const ICONS = {
    offline: `
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="1" y1="1" x2="23" y2="23"></line>
        <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>
        <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>
        <path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path>
        <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>
        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
        <line x1="12" y1="20" x2="12.01" y2="20"></line>
      </svg>
    `,
    online: `
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
        <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
        <line x1="12" y1="20" x2="12.01" y2="20"></line>
      </svg>
    `
  };

  function showToast(state, customTitle, customSubtitle) {
    if (!document.body) {
      window.addEventListener('DOMContentLoaded', () => showToast(state, customTitle, customSubtitle));
      return;
    }

    const toast = createToastElement();
    if (hideTimeout) {
      clearTimeout(hideTimeout);
      hideTimeout = null;
    }

    const titleEl = document.getElementById('cl-toast-title');
    const subtitleEl = document.getElementById('cl-toast-subtitle');
    const iconContainer = document.getElementById('cl-toast-icon-container');
    const actionsEl = document.getElementById('cl-toast-actions');
    const retryBtn = document.getElementById('cl-toast-retry-btn');
    const retryIcon = document.getElementById('cl-toast-retry-icon');
    const retryText = document.getElementById('cl-toast-retry-text');

    // Trigger reflow to restart transition if coming from hidden
    void toast.offsetWidth;

    if (state === 'offline') {
      toast.className = 'cl-visible cl-state-offline';
      if (titleEl) titleEl.textContent = customTitle || 'You are offline';
      if (subtitleEl) subtitleEl.textContent = customSubtitle || 'No internet connection detected';
      if (iconContainer) iconContainer.innerHTML = ICONS.offline;
      if (actionsEl) actionsEl.style.display = 'flex';
      if (retryBtn) retryBtn.style.display = 'inline-flex';
      if (retryIcon) retryIcon.classList.remove('cl-spin-icon');
      if (retryText) retryText.textContent = 'Retry';

      startPingInterval();
    } else if (state === 'online') {
      toast.className = 'cl-visible cl-state-online';
      if (titleEl) titleEl.textContent = customTitle || 'Back online!';
      if (subtitleEl) subtitleEl.textContent = customSubtitle || 'Internet connection restored';
      if (iconContainer) iconContainer.innerHTML = ICONS.online;
      if (actionsEl) actionsEl.style.display = 'flex';
      if (retryBtn) retryBtn.style.display = 'none';

      stopPingInterval();

      hideTimeout = setTimeout(function () {
        hideToast();
      }, 3500);
    }
  }

  function hideToast() {
    if (!toastEl) return;
    toastEl.classList.remove('cl-visible');
    if (hideTimeout) {
      clearTimeout(hideTimeout);
      hideTimeout = null;
    }
  }

  /**
   * Ping an external public endpoint using mode: 'no-cors'
   * If WiFi/Internet is off, external fetch will immediately fail/throw exception.
   * Localhost loopback is never used because localhost requests always succeed even without internet.
   */
  async function checkRealInternetConnection(manual) {
    if (isChecking) return;
    isChecking = true;

    if (manual) {
      // Hide the toast immediately so user gets tactile feedback
      hideToast();
      // Brief natural delay to allow smooth hide animation
      await new Promise(r => setTimeout(r, 450));
    }

    // If browser itself reports offline, definitely offline
    if (!navigator.onLine) {
      isChecking = false;
      handleOffline();
      return;
    }

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 3500);

      // Probe actual public WAN endpoint (Cloudflare / Google favicon)
      const probeUrl = 'https://1.1.1.1/favicon.ico?_cl=' + Date.now();
      await fetch(probeUrl, {
        method: 'HEAD',
        mode: 'no-cors',
        cache: 'no-store',
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      // If we reach here, external network request succeeded
      handleOnline();
    } catch (err) {
      // Failed to reach the internet
      handleOffline();
    } finally {
      isChecking = false;
    }
  }

  function handleOffline() {
    hasBeenOffline = true;
    isCurrentlyOffline = true;
    showToast('offline');
  }

  function handleOnline() {
    // Only show "Back online!" if the user was previously offline during this session
    if (hasBeenOffline && isCurrentlyOffline) {
      isCurrentlyOffline = false;
      showToast('online');
    } else {
      isCurrentlyOffline = false;
      hideToast();
    }
  }

  function startPingInterval() {
    if (pingInterval) return;
    pingInterval = setInterval(function () {
      if (isCurrentlyOffline) {
        checkRealInternetConnection(false);
      } else {
        stopPingInterval();
      }
    }, 6000);
  }

  function stopPingInterval() {
    if (pingInterval) {
      clearInterval(pingInterval);
      pingInterval = null;
    }
  }

  // Browser Network Events
  window.addEventListener('offline', function () {
    handleOffline();
  });

  window.addEventListener('online', function () {
    checkRealInternetConnection(false);
  });

  // Initial check on load
  function init() {
    if (!navigator.onLine) {
      handleOffline();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.CitiLifeOfflineTracker = {
    showOffline: handleOffline,
    showOnline: handleOnline,
    checkNow: checkRealInternetConnection,
    hide: hideToast,
    isOffline: function () { return isCurrentlyOffline; }
  };

})();
