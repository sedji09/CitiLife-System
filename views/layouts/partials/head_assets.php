  <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/public/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/style.css') ?>">
  <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/public/assets/css/drive-preview.css?v=<?= time() ?>">
  <script src="/<?= PROJECT_DIR ?>/public/assets/js/drive-preview.js?v=<?= time() ?>"></script>

  <!-- Premium Alerts & Dialogs (SweetAlert2) -->
  <link rel="stylesheet"
    href="/<?= PROJECT_DIR ?>/public/assets/vendor/sweetalert2/sweetalert2.min.css?v=<?= time() ?>">
  <script src="/<?= PROJECT_DIR ?>/public/assets/vendor/sweetalert2/sweetalert2.all.min.js?v=<?= time() ?>"></script>
  <script src="/<?= PROJECT_DIR ?>/public/assets/js/alerts.js?v=<?= time() ?>"></script>
  <script src="/<?= PROJECT_DIR ?>/public/assets/js/security.js?v=<?= time() ?>"></script>

  <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/tailwind/src/output.css">
  <style>
    /* ===== Settings Modal Responsive Styles ===== */
    .settings-modal-shell {
      background-color: var(--modal-bg, #fff);
      border-radius: 16px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 760px;
      height: 600px;
      max-height: 90vh;
      display: flex;
      flex-direction: row;
      overflow: hidden;
      position: relative;
      border: 1px solid var(--modal-border, #e5e7eb);
    }

    .settings-modal-sidebar {
      width: 210px;
      min-width: 210px;
      background-color: var(--modal-bg-alt, #f9fafb);
      border-right: 1px solid var(--modal-border, #e5e7eb);
      padding: 24px 10px;
      display: flex;
      flex-direction: column;
      gap: 2px;
      overflow: hidden;
    }

    .settings-modal-content {
      flex: 1;
      overflow-y: auto;
      padding: 32px 36px;
      display: flex;
      flex-direction: column;
      background-color: var(--modal-bg, #fff);
    }

    @media (max-width: 640px) {
      .settings-modal-shell {
        flex-direction: column !important;
        height: 85vh !important;
        max-height: 85vh !important;
      }
      .settings-modal-sidebar {
        width: 100% !important;
        min-width: 100% !important;
        border-right: none !important;
        border-bottom: 1px solid var(--modal-border, #e5e7eb) !important;
        flex-direction: row !important;
        padding: 10px 16px !important;
        gap: 6px !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        white-space: nowrap !important;
        align-items: center !important;
        height: auto !important;
        flex-shrink: 0 !important;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
      }
      .settings-modal-sidebar::-webkit-scrollbar {
        display: none;
      }
      .settings-modal-sidebar p,
      .settings-modal-sidebar div {
        display: none !important;
      }
      .settings-tab-btn {
        width: auto !important;
        padding: 6px 14px !important;
        flex-shrink: 0 !important;
        border-radius: 20px !important;
        background-color: var(--modal-bg-alt, #f3f4f6) !important;
        border: 1px solid var(--modal-border, #e5e7eb) !important;
        color: var(--modal-text-muted, #6b7280) !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        gap: 6px !important;
      }
      .settings-tab-btn i[data-lucide] {
        width: 14px !important;
        height: 14px !important;
      }
      .settings-tab-btn.active {
        background-color: var(--modal-text, #111827) !important;
        color: var(--modal-bg, #fff) !important;
        border-color: var(--modal-text, #111827) !important;
        font-weight: 600 !important;
      }
      .settings-modal-content {
        padding: 20px 18px !important;
      }
      .settings-grid-cols-2 {
        grid-template-columns: 1fr !important;
        gap: 8px !important;
      }
      .settings-grid-cols-3 {
        grid-template-columns: 1fr 1fr 1fr !important;
        gap: 8px !important;
      }
      .settings-grid-cols-3 button {
        padding: 8px 4px !important;
        border-radius: 10px !important;
      }
      .settings-grid-cols-3 button span {
        font-size: 10px !important;
      }
      .settings-grid-cols-3 button div {
        width: 32px !important;
        height: 24px !important;
      }
      .settings-flex-row {
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 12px !important;
      }
      .settings-profile-photo-row {
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
      }
      .settings-mobile-header {
        display: flex !important;
        align-items: center;
        height: 52px;
        padding: 0 48px 0 18px;
        border-bottom: 1px solid var(--modal-border-light, #f3f4f6);
        background-color: var(--modal-bg, #fff);
        flex-shrink: 0;
      }
    }

    .settings-mobile-header {
      display: none;
    }

    /* Base Grid & Flex Styles for Settings Modal */
    .settings-grid-cols-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    .settings-grid-cols-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 12px;
    }
    .settings-flex-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .settings-profile-photo-row {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    /* Desktop sidebar tab button */
    .settings-tab-btn {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      width: 100%;
      text-align: left;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 500;
      color: var(--modal-text-muted, #6b7280);
      background: none;
      transition: background 0.15s, color 0.15s;
    }
    .settings-tab-btn:hover {
      background: var(--modal-border-light, #f3f4f6);
    }
    .settings-tab-btn.active {
      background: #fef2f2;
      color: #b91c1c;
      font-weight: 600;
    }


    /* ===== CSS Custom Properties for Settings Modal & Theme-aware Components ===== */
    :root {
      --modal-bg: #ffffff;
      --modal-bg-alt: #f9fafb;
      --modal-bg-light: #fef9f9;
      --modal-border: #e5e7eb;
      --modal-border-light: #f3f4f6;
      --modal-border-dark: #d1d5db;
      --modal-text: #111827;
      --modal-text-dark: #1f2937;
      --modal-text-muted: #6b7280;
      --modal-text-light: #9ca3af;
    }

    body.theme-dark {
      --modal-bg: #1f2937;
      --modal-bg-alt: #111827;
      --modal-bg-light: #1a1a2e;
      --modal-border: #374151;
      --modal-border-light: #2d3748;
      --modal-border-dark: #4b5563;
      --modal-text: #f9fafb;
      --modal-text-dark: #e5e7eb;
      --modal-text-muted: #9ca3af;
      --modal-text-light: #6b7280;
    }

    /* Enhanced Global Dark Theme Overrides */
    body.theme-dark {
      background-color: #111827 !important;
      /* Tailwind gray-900 */
      color: #e5e7eb !important;
      /* Tailwind gray-200 */
      color-scheme: dark !important;
    }

    /* Base backgrounds */
    body.theme-dark .bg-white {
      background-color: #1f2937 !important;
      /* Tailwind gray-800 */
      border-color: #374151 !important;
      /* Tailwind gray-700 */
      color: #f3f4f6 !important;
      /* Tailwind gray-100 */
    }

    body.theme-dark .bg-gray-50,
    body.theme-dark .bg-gray-100,
    body.theme-dark .bg-stone-50,
    body.theme-dark .bg-stone-100 {
      background-color: #111827 !important;
    }

    body.theme-dark .hover\:bg-gray-100:hover {
      background-color: #111827 !important;
    }

    body.theme-dark .active\:bg-gray-200:active {
      background-color: #030712 !important;
    }

    body.theme-dark .bg-gray-200 {
      background-color: #374151 !important;
    }

    /* Divided elements (tables, lists) */
    body.theme-dark .divide-y> :not([hidden])~ :not([hidden]),
    body.theme-dark .divide-gray-50> :not([hidden])~ :not([hidden]),
    body.theme-dark .divide-gray-100> :not([hidden])~ :not([hidden]),
    body.theme-dark .divide-gray-200> :not([hidden])~ :not([hidden]) {
      border-color: #374151 !important;
    }

    /* Texts */
    body.theme-dark .text-gray-900,
    body.theme-dark .text-gray-800,
    body.theme-dark .text-black {
      color: #f9fafb !important;
    }

    body.theme-dark .text-gray-700,
    body.theme-dark .text-gray-600 {
      color: #d1d5db !important;
    }

    body.theme-dark .text-gray-500,
    body.theme-dark .text-gray-400 {
      color: #9ca3af !important;
    }

    /* Hovers, Focus & Active States */
    body.theme-dark .hover\:bg-gray-50:hover,
    body.theme-dark .hover\:bg-gray-100:hover,
    body.theme-dark .hover\:bg-white:hover {
      background-color: #374151 !important;
    }

    body.theme-dark .active\:bg-gray-100:active,
    body.theme-dark .active\:bg-gray-200:active {
      background-color: #4b5563 !important;
    }

    body.theme-dark .hover\:text-gray-900:hover {
      color: #ffffff !important;
    }

    body.theme-dark .hover\:text-gray-700:hover {
      color: #e5e7eb !important;
    }

    /* Inputs, Selects, Textareas */
    body.theme-dark input[type="text"],
    body.theme-dark input[type="email"],
    body.theme-dark input[type="password"],
    body.theme-dark input[type="number"],
    body.theme-dark input[type="date"],
    body.theme-dark input[type="search"],
    body.theme-dark textarea,
    body.theme-dark select {
      background-color: #1f2937 !important;
      color: #f9fafb !important;
      border-color: #374151 !important;
    }

    body.theme-dark input::placeholder,
    body.theme-dark textarea::placeholder {
      color: #6b7280 !important;
    }

    body.theme-dark input:focus,
    body.theme-dark textarea:focus,
    body.theme-dark select:focus {
      border-color: #ef4444 !important;
      outline: none;
      --tw-ring-color: #ef4444 !important;
    }

    /* Fix Date Picker Icon Visibility */
    input[type="date"] {
      color-scheme: light;
    }

    body.theme-dark input[type="date"] {
      color-scheme: dark !important;
    }

    input[type="date"]::-webkit-calendar-picker-indicator {
      cursor: pointer;
      opacity: 0.6;
      transition: 0.2s;
    }

    input[type="date"]::-webkit-calendar-picker-indicator:hover {
      opacity: 1;
    }

    /* Fix table colors if any */
    body.theme-dark table th {
      background-color: #111827 !important;
      color: #d1d5db !important;
    }

    body.theme-dark table td {
      border-color: #374151 !important;
    }

    /* Protect trademark red accents */
    body.theme-dark .bg-red-50 {
      background-color: rgba(220, 38, 38, 0.15) !important;
    }

    body.theme-dark .text-red-900,
    body.theme-dark .text-red-800,
    body.theme-dark .text-red-700,
    body.theme-dark .text-red-600,
    body.theme-dark .text-red-500 {
      color: #fca5a5 !important;
    }

    body.theme-dark .hover\:text-red-700:hover {
      color: #fecaca !important;
    }

    body.theme-dark .hover\:text-red-800:hover {
      color: #ef4444 !important;
      /* Duller/darker red on hover as requested */
    }

    body.theme-dark .bg-red-100 {
      background-color: rgba(220, 38, 38, 0.25) !important;
    }

    body.theme-dark .hover\:bg-red-50:hover {
      background-color: rgba(220, 38, 38, 0.25) !important;
    }

    body.theme-dark .active\:bg-red-100:active {
      background-color: rgba(220, 38, 38, 0.35) !important;
    }

    /* Other colored badges or backgrounds */
    body.theme-dark .bg-orange-50 {
      background-color: rgba(249, 115, 22, 0.15) !important;
    }

    body.theme-dark .bg-orange-100 {
      background-color: rgba(249, 115, 22, 0.25) !important;
    }

    body.theme-dark .text-orange-900,
    body.theme-dark .text-orange-800,
    body.theme-dark .text-orange-700,
    body.theme-dark .text-orange-600,
    body.theme-dark .text-orange-500 {
      color: #fdba74 !important;
    }

    body.theme-dark .hover\:text-orange-700:hover {
      color: #fed7aa !important;
      /* Lighter on hover */
    }

    body.theme-dark .bg-green-50 {
      background-color: rgba(34, 197, 94, 0.15) !important;
    }

    body.theme-dark .bg-green-100 {
      background-color: rgba(34, 197, 94, 0.25) !important;
    }

    body.theme-dark .text-green-900,
    body.theme-dark .text-green-800,
    body.theme-dark .text-green-700,
    body.theme-dark .text-green-600,
    body.theme-dark .text-green-500 {
      color: #86efac !important;
    }

    body.theme-dark .hover\:text-green-700:hover {
      color: #bbf7d0 !important;
      /* Lighter on hover */
    }

    body.theme-dark .bg-blue-50 {
      background-color: rgba(59, 130, 246, 0.15) !important;
    }

    body.theme-dark .bg-blue-100 {
      background-color: rgba(59, 130, 246, 0.25) !important;
    }

    body.theme-dark .text-blue-900,
    body.theme-dark .text-blue-800,
    body.theme-dark .text-blue-700,
    body.theme-dark .text-blue-600,
    body.theme-dark .text-blue-500 {
      color: #93c5fd !important;
    }

    body.theme-dark .hover\:text-blue-700:hover {
      color: #bfdbfe !important;
      /* Lighter on hover */
    }

    body.theme-dark .bg-yellow-50 {
      background-color: rgba(234, 179, 8, 0.15) !important;
    }

    body.theme-dark .bg-yellow-100 {
      background-color: rgba(234, 179, 8, 0.25) !important;
    }

    body.theme-dark .text-yellow-900,
    body.theme-dark .text-yellow-800,
    body.theme-dark .text-yellow-700,
    body.theme-dark .text-yellow-600,
    body.theme-dark .text-yellow-500 {
      color: #fde047 !important;
      /* Corrected: Vibrant yellow instead of reddish tint */
    }

    body.theme-dark .hover\:text-yellow-700:hover {
      color: #fef08a !important;
      /* Lighter on hover */
    }

    /* Indigo / Report Ready */
    body.theme-dark .bg-indigo-50 {
      background-color: rgba(99, 102, 241, 0.15) !important;
    }

    body.theme-dark .bg-indigo-100 {
      background-color: rgba(99, 102, 241, 0.25) !important;
    }

    body.theme-dark .text-indigo-900,
    body.theme-dark .text-indigo-800,
    body.theme-dark .text-indigo-700,
    body.theme-dark .text-indigo-600,
    body.theme-dark .text-indigo-500 {
      color: #c7d2fe !important;
      /* Lighter indigo for dark mode */
    }

    body.theme-dark .border-indigo-400,
    body.theme-dark .border-indigo-300 {
      border-color: #6366f1 !important;
    }

    /* Red / STAT */
    body.theme-dark .bg-red-50 {
      background-color: rgba(239, 68, 68, 0.15) !important;
    }

    body.theme-dark .text-red-900,
    body.theme-dark .text-red-800,
    body.theme-dark .text-red-700,
    body.theme-dark .text-red-600,
    body.theme-dark .text-red-500 {
      color: #fca5a5 !important;
    }

    body.theme-dark .border-red-400,
    body.theme-dark .border-red-300 {
      border-color: #dc2626 !important;
    }

    /* Orange / Priority */
    body.theme-dark .bg-orange-50 {
      background-color: rgba(249, 115, 22, 0.15) !important;
    }

    body.theme-dark .text-orange-900,
    body.theme-dark .text-orange-800,
    body.theme-dark .text-orange-700,
    body.theme-dark .text-orange-600,
    body.theme-dark .text-orange-500 {
      color: #fdba74 !important;
    }

    body.theme-dark .border-orange-400,
    body.theme-dark .border-orange-300 {
      border-color: #ea580c !important;
    }

    /* Specific nav fixes */
    body.theme-dark .ring-gray-200 {
      --tw-ring-color: #374151 !important;
    }

    /* Shadows */
    body.theme-dark .shadow-sm,
    body.theme-dark .shadow,
    body.theme-dark .shadow-md,
    body.theme-dark .shadow-lg {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -2px rgba(0, 0, 0, 0.5) !important;
    }

    /* Exam Count Badge Theme logic */
    .exam-badge {
      background-color: #f1f5f9;
      color: #475569;
      border: 1.5px solid #e2e8f0;
    }

    body.theme-dark .exam-badge {
      background-color: #1e293b !important;
      color: #93c5fd !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    /* Diagnostic Viewers Theme Consistency */
    body.theme-dark #findings-viewer-container,
    body.theme-dark #xray-viewer-container,
    body.theme-dark #dicom-viewer {
      background-color: #030303 !important;
      /* Deep black for cinematic feel */
      border-color: rgba(255, 255, 255, 0.05) !important;
    }

    body.theme-dark .diagnostic-card-header {
      background-color: #1a1a1a !important;
      border-bottom-color: rgba(255, 255, 255, 0.05) !important;
    }

    body.theme-dark .diagnostic-card-body {
      background-color: #0a0a0a !important;
    }

    body.theme-dark #findings-report-name,
    body.theme-dark #xray-filename {
      color: rgba(255, 255, 255, 0.9) !important;
    }

    /* Toast slide-in animation and keyframes */
    @keyframes toastSlideIn {
      from {
        opacity: 0;
        transform: translateX(120%);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .toast-item {
      animation: toastSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Toast Dark Theme styling */
    body.theme-dark .toast-item {
      background: rgba(31, 41, 55, 0.85) !important;
      border-color: rgba(255, 255, 255, 0.08) !important;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5) !important;
    }

    body.theme-dark .toast-title {
      color: #f3f4f6 !important;
    }

    body.theme-dark .toast-msg {
      color: #9ca3af !important;
    }

    /* Prevent Vue FOUC flashes */
    [v-cloak] {
      display: none !important;
    }

    /* Custom scrollbar for premium feel (Standard width like reference) */
    .custom-scrollbar::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
      background: #f8fafc;
      border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 10px;
      border: 2px solid #f8fafc;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    body.theme-dark .custom-scrollbar::-webkit-scrollbar-track {
      background: #1e293b;
    }

    body.theme-dark .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #475569;
      border: 2px solid #1e293b;
    }

    body.theme-dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #64748b;
    }

    /* ===== CSS Variables for theme-aware skeleton ===== */
    :root {
      --skel-bg: #f5f5f4;
      --skel-sidebar: #ffffff;
      --skel-border: #e5e7eb;
      --skel-bar-from: #e5e7eb;
      --skel-bar-mid: #f3f4f6;
      --skel-card-bg: #ffffff;
    }

    body.theme-dark,
    html.theme-dark body,
    html.theme-dark {
      --skel-bg: #111827;
      --skel-sidebar: #1f2937;
      --skel-border: #374151;
      --skel-bar-from: #374151;
      --skel-bar-mid: #4b5563;
      --skel-card-bg: #1f2937;
    }

    /* Smooth page loading skeleton */
    #app-loading {
      position: fixed;
      inset: 0;
      z-index: 9999;
      display: flex;
      background: var(--skel-bg);
      transition: opacity 0.25s ease;
    }

    #app-loading.fade-out {
      opacity: 0;
      pointer-events: none;
    }

    #app-loading.hidden {
      display: none;
    }

    /* Sidebar skeleton */
    .skel-sidebar {
      width: 275px;
      min-height: 100vh;
      background: var(--skel-sidebar);
      border-right: 1px solid var(--skel-border);
      padding: 24px 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    /* Content skeleton */
    .skel-content {
      flex: 1;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .skel-bar {
      background: linear-gradient(90deg, var(--skel-bar-from) 25%, var(--skel-bar-mid) 50%, var(--skel-bar-from) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.4s infinite;
      border-radius: 8px;
    }

    .skel-card {
      background: var(--skel-card-bg);
      border: 1px solid var(--skel-border);
    }

    @keyframes shimmer {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }

    /* Smooth sidebar transition */
    #mobile-overlay {
      transition: opacity 0.2s;
    }

    /* Persisted Sidebar Skeleton Matching */
    .sidebar-collapsed .skel-sidebar {
      width: 80px !important;
    }

    .sidebar-collapsed .skel-sidebar .skel-bar:nth-child(2),
    /* Logo text */
    .sidebar-collapsed .skel-sidebar .skel-bar:nth-child(8)

    /* Profile info area */
      {
      display: none !important;
    }

    .sidebar-collapsed .skel-content {
      margin-left: 0 !important;
    }

    /* ===== Premium Custom Tooltips ===== */
    .has-tooltip {
      position: relative;
    }

    /* Styles apply to any element with data-tooltip */
    [data-tooltip]::after {
      content: attr(data-tooltip);
      position: absolute;
      padding: 6px 12px;
      background: rgba(17, 24, 39, 0.9);
      backdrop-filter: blur(4px);
      color: white;
      font-size: 11px;
      font-weight: 500;
      border-radius: 8px;
      white-space: nowrap;
      opacity: 0;
      visibility: hidden;
      transition: all 0.2s ease-in-out;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      z-index: 9999;
      pointer-events: none;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Left side (Sidebar collapsed) */
    .sidebar-tooltip::after {
      left: 100%;
      top: 50%;
      margin-left: 12px;
      transform: translateY(-50%) translateX(-10px);
    }

    /* Top side (for elements near bottom) */
    .top-tooltip::after {
      bottom: 100%;
      left: 50%;
      margin-bottom: 10px;
      transform: translateX(-50%) translateY(10px);
    }

    /* Bottom side (Topbar) */
    .bottom-tooltip::after {
      top: 100%;
      left: 50%;
      margin-top: 10px;
      transform: translateX(-50%) translateY(-10px);
    }

    .sidebar-tooltip:hover::after,
    .top-tooltip:hover::after,
    .bottom-tooltip:hover::after {
      opacity: 1;
      visibility: visible;
      transform: translate(0, 0);
      /* Simplified transform for stability */
    }

    /* Positioning for stability */
    .sidebar-tooltip::after {
      left: 100%;
      top: 50%;
      margin-left: 10px;
      transform: translateY(-50%);
    }

    .top-tooltip::after {
      bottom: 100%;
      left: 50%;
      margin-bottom: 10px;
      transform: translateX(-50%);
    }

    .bottom-tooltip::after {
      top: 100%;
      left: 50%;
      margin-top: 10px;
      transform: translateX(-50%);
    }

    [data-tooltip=""]::after {
      display: none !important;
    }

    /* Custom Expired Alert Modal styles */
    .custom-alert-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        margin: 0 !important;
    }

    .custom-alert-overlay.show {
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .custom-alert-box {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 32px 24px;
        width: 90%;
        max-width: 420px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        text-align: center;
        font-family: 'Inter', sans-serif;
        transform: translateY(20px) scale(0.95);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .custom-alert-overlay.show .custom-alert-box {
        transform: translateY(0) scale(1) !important;
    }

    .custom-alert-icon-container {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #fee2e2;
        color: #dc2626;
        margin-bottom: 20px;
    }

    .custom-alert-title {
        color: #1e293b;
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 12px 0;
    }

    .custom-alert-text {
        color: #64748b;
        font-size: 14px;
        margin: 0 0 24px 0;
        line-height: 1.6;
    }

    .custom-alert-btn {
        background: #dc2626;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        width: 100%;
        outline: none;
    }

    .custom-alert-btn:hover {
        background: #b91c1c;
    }

    body.theme-dark .custom-alert-box {
        background: #1e293b;
        border-color: #374151;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    body.theme-dark .custom-alert-title {
        color: #f8fafc;
    }

    body.theme-dark .custom-alert-text {
        color: #94a3b8;
    }

    body.theme-dark .custom-alert-icon-container {
        background: rgba(220, 38, 38, 0.15);
    }

    .custom-alert-buttons-container {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        width: 100%;
    }

    .custom-alert-btn-secondary {
        background: #f3f4f6;
        color: #4b5563;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
        width: 100%;
        outline: none;
    }

    .custom-alert-btn-secondary:hover {
        background: #e5e7eb;
        color: #1f2937;
    }

    body.theme-dark .custom-alert-btn-secondary {
        background: #374151;
        color: #d1d5db;
        border-color: #4b5563;
    }

    body.theme-dark .custom-alert-btn-secondary:hover {
        background: #4b5563;
        color: #ffffff;
    }
    /* Global override for Vanilla JS Datepicker to make selected date RED */
    html body .datepicker-cell.selected,
    html body .datepicker-cell.selected:hover,
    html body .datepicker-cell.selected.focused,
    html body .datepicker-picker .datepicker-cell.selected,
    html body .datepicker-picker .datepicker-cell.selected:hover,
    html body .datepicker-picker .datepicker-cell.selected.focused {
        background-color: #dc2626 !important;
        color: #ffffff !important;
        border-color: #dc2626 !important;
    }

    /* Remove the default TEAL background from 'today' and make it clean */
    html body .datepicker-cell.today:not(.selected),
    html body .datepicker-picker .datepicker-cell.today:not(.selected) {
        background-color: #f3f4f6 !important; /* light grey instead of teal */
        color: #111827 !important;
        font-weight: 600 !important;
        border: 1px solid #d1d5db !important;
    }

    html body .datepicker-cell.today.focused:not(.selected),
    html body .datepicker-picker .datepicker-cell.today.focused:not(.selected) {
        background-color: #e5e7eb !important;
    }
  </style>

  <!-- ===== THEME BOOTSTRAP: runs synchronously before first paint ===== -->
  <!-- Supports System / Dark / Light. Runs before first paint to avoid flash. -->
  <script>
    (function () {
      try {
        var theme = localStorage.getItem('citilife_theme') || 'system';
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var shouldBeDark = (theme === 'dark') || (theme === 'system' && prefersDark);
        console.log("Theme Bootstrap - theme:", theme, "shouldBeDark:", shouldBeDark);

        function applyTheme(dark) {
          console.log("Theme Bootstrap - applyTheme:", dark);
          if (dark) {
            document.documentElement.classList.add('theme-dark', 'dark');
            document.documentElement.style.colorScheme = 'dark';
          } else {
            document.documentElement.classList.remove('theme-dark', 'dark');
            document.documentElement.style.colorScheme = 'light';
          }
        }

        applyTheme(shouldBeDark);

        // Sync to body once it exists
        document.addEventListener('DOMContentLoaded', function () {
          if (document.documentElement.classList.contains('theme-dark')) {
            document.body.classList.add('theme-dark', 'dark');
          } else {
            document.body.classList.remove('theme-dark', 'dark');
          }
        });

        // Watch OS-level preference changes (only affects 'system' mode)
        if (window.matchMedia) {
          window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
            var currentTheme = localStorage.getItem('citilife_theme') || 'system';
            if (currentTheme === 'system') {
              applyTheme(e.matches);
              if (e.matches) {
                document.body && document.body.classList.add('theme-dark');
              } else {
                document.body && document.body.classList.remove('theme-dark');
              }
            }
          });
        }

        // Sidebar Persistence Bootstrap
        var sidebarState = localStorage.getItem('citilife_sidebar_open');
        if (sidebarState === 'false') {
          document.documentElement.classList.add('sidebar-collapsed');
        }
      } catch (e) { }
    })();
  </script>
