<?php
// (OPTIONAL) if naka-router/index.php ka na, session_start should be there.
// If not sure, safe version:
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$role = $_SESSION['role'] ?? 'radtech';
$userEmail = $_SESSION['email'] ?? 'user@example.com';
$userId = $_SESSION['user_id'] ?? 0;
$branchId = $_SESSION['branch_id'] ?? null;

// database, UserModel, and BranchModel are autoloaded via composer

$userModel = new \UserModel($pdo);
$branchModel = new \BranchModel($pdo);

// --- GLOBAL SESSION GUARD ---
// Check if user is still active in database
if ($userId > 0) {
  $currentUser = $userModel->getUserById($userId);
  if (!$currentUser || $currentUser['status'] === 'Inactive') {
    // Handle logout
    session_unset();
    session_destroy();
    $reason = (!$currentUser) ? 'deleted' : 'deactivated';
    header("Location: /" . PROJECT_DIR . "/login?error=" . $reason);
    exit();
  }
}
// ----------------------------
$branchNameDisplay = $branchModel->getBranchDisplayName($branchId);

// 2. Get User Display Info (Avatar, Display Name, Initials - Backend logic moved to Model)
$sessionName = $_SESSION['name'] ?? '';
$displayInfo = $userModel->getDisplayInfo($userId, $sessionName, $userEmail);

$userDisplayName = $displayInfo['displayName'];
$initials = $displayInfo['initials'];
$userAvatar = $displayInfo['avatar'];
$userSignature = $currentUser['signature'] ?? '';
$userProfessionalTitle = $currentUser['professional_title'] ?? '';
$userFullNameReport = $currentUser['full_name_report'] ?? '';

// AuthHelper is autoloaded via composer

$isPatient = ($role === 'patient');

$menus = require __DIR__ . '/../../config/menus.php';
$allRoleMenus = $menus[$role] ?? [];

// Dynamic RBAC Filter
$menuItems = [];
foreach ($allRoleMenus as $item) {
  $permKey = $item['perm_key'] ?? null;
  if (!$permKey || hasPermission($role, $permKey) > 0) {
    $menuItems[] = $item;
  }
}

$currentPath = $_SERVER['REQUEST_URI']; // includes query string
$basePath = "/" . PROJECT_DIR;

$logoPath = "/" . PROJECT_DIR . "/public/assets/img/logo/citilife-logo.png";
$appName = 'CitiLife';

$autoLogoutMinutes = 0;
try {
  $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
  while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['setting_key'] === 'system_name' && !empty($row['setting_value'])) {
      $appName = $row['setting_value'];
    }
    if ($row['setting_key'] === 'clinic_logo' && !empty($row['setting_value'])) {
      $logoPath = "/" . PROJECT_DIR . "/" . $row['setting_value'];
    }
    if ($row['setting_key'] === 'auto_logout_minutes' && !empty($row['setting_value'])) {
      $autoLogoutMinutes = intval($row['setting_value']);
    }
  }
} catch (Exception $e) {
  // Ignore if table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($appName) ?></title>
  <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/public/assets/css/style.css">
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
  </style>

  <!-- ===== THEME BOOTSTRAP: runs synchronously before first paint ===== -->
  <!-- This ensures the skeleton matches the user's saved theme immediately,  -->
  <!-- preventing the dark-mode white flash entirely.                          -->
  <script>
    (function () {
      try {
        var theme = localStorage.getItem('citilife_theme');
        if (theme === 'dark') {
          document.documentElement.classList.add('theme-dark');
          document.documentElement.style.colorScheme = 'dark';
        } else {
          document.documentElement.classList.remove('theme-dark');
          document.documentElement.style.colorScheme = 'light';
        }

        // Sync to body once it exists to ensure CSS selectors like `body.theme-dark` work
        document.addEventListener('DOMContentLoaded', function () {
          if (document.documentElement.classList.contains('theme-dark')) {
            document.body.classList.add('theme-dark');
          } else {
            document.body.classList.remove('theme-dark');
          }
        });

        // Sidebar Persistence Bootstrap
        var sidebarState = localStorage.getItem('citilife_sidebar_open');
        if (sidebarState === 'false') {
          document.documentElement.classList.add('sidebar-collapsed');
        }
      } catch (e) { }
    })();
  </script>
</head>

<body class="bg-stone-100 text-gray-900">

  <!-- ===== LOADING SKELETON (shown before Vue mounts) ===== -->
  <div id="app-loading">
    <!-- Sidebar skeleton -->
    <div class="skel-sidebar">
      <!-- Logo area -->
      <div
        style="display:flex;align-items:center;gap:8px;padding-bottom:16px;border-bottom:1px solid var(--skel-border);margin-bottom:12px;">
        <div class="skel-bar" style="width:40px;height:40px;border-radius:50%;flex-shrink:0;"></div>
        <div class="skel-bar" style="height:14px;flex:1;"></div>
      </div>
      <!-- Nav items -->
      <div class="skel-bar" style="height:36px;width:100%;"></div>
      <div class="skel-bar" style="height:36px;width:100%;"></div>
      <div class="skel-bar" style="height:36px;width:100%;"></div>
      <div class="skel-bar" style="height:36px;width:100%;"></div>
      <div class="skel-bar" style="height:36px;width:100%;"></div>
      <!-- Profile area at bottom -->
      <div
        style="margin-top:auto;padding-top:12px;border-top:1px solid var(--skel-border);display:flex;align-items:center;gap:10px;">
        <div class="skel-bar" style="width:36px;height:36px;border-radius:50%;flex-shrink:0;"></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
          <div class="skel-bar" style="height:12px;width:80%;"></div>
          <div class="skel-bar" style="height:10px;width:50%;"></div>
        </div>
      </div>
    </div>
    <!-- Main content skeleton -->
    <div class="skel-content">
      <!-- Topbar -->
      <div
        style="background:var(--skel-card-bg);border-radius:8px;padding:16px 24px;display:flex;justify-content:space-between;align-items:center;border:1px solid var(--skel-border);">
        <div style="display:flex;flex-direction:column;gap:6px;">
          <div class="skel-bar" style="height:16px;width:220px;"></div>
          <div class="skel-bar" style="height:12px;width:140px;"></div>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
          <div class="skel-bar" style="height:36px;width:180px;border-radius:8px;"></div>
          <div class="skel-bar" style="width:36px;height:36px;border-radius:50%;"></div>
          <div class="skel-bar" style="width:36px;height:36px;border-radius:50%;"></div>
        </div>
      </div>
      <!-- Content cards -->
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
        <div class="skel-bar" style="height:80px;border-radius:12px;"></div>
        <div class="skel-bar" style="height:80px;border-radius:12px;"></div>
        <div class="skel-bar" style="height:80px;border-radius:12px;"></div>
        <div class="skel-bar" style="height:80px;border-radius:12px;"></div>
      </div>
      <!-- Main block -->
      <div class="skel-bar" style="height:300px;border-radius:12px;"></div>
    </div>
  </div>

  <div id="app" v-cloak class="flex min-h-screen">

    <?php if ($isPatient): ?>
      <!-- ===== PATIENT MOBILE TOP NAVBAR ===== -->
      <header
        class="fixed top-0 left-0 right-0 z-50 border-b shadow-sm flex items-center justify-between px-4 h-14 md:hidden transition-colors duration-300"
        :class="mobileMenuOpen ? 'bg-transparent border-transparent shadow-none' : 'bg-white border-gray-100'">
        <div class="flex items-center gap-2" v-show="!mobileMenuOpen">
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-lg hover:bg-gray-100 transition">
            <i data-lucide="menu" class="w-5 h-5 text-gray-700"></i>
          </button>
          <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo" class="h-8 w-auto" />
          <span class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($appName) ?> Portal</span>
        </div>
        <div class="flex items-center gap-1" v-show="!mobileMenuOpen">
          <!-- Mobile notification bell for patient -->
          <div class="relative" ref="mobileNotifRef">
            <button @click.prevent="toggleNotificationMenu"
              class="relative p-2 rounded-lg hover:bg-gray-100 transition text-gray-600 hover:text-gray-900">
              <i data-lucide="bell" class="w-5 h-5"></i>
              <span v-if="notificationCount > 0"
                class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white leading-none">
                {{ notificationCount > 9 ? '9+' : notificationCount }}
              </span>
            </button>
            <!-- Mobile backdrop for notifications -->
            <div v-if="notificationMenuOpen" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[150] md:hidden"
              @click="notificationMenuOpen = false"></div>

            <!-- Dropdown reuses the same notificationMenuOpen state -->
            <div v-show="notificationMenuOpen" ref="mobileNotificationMenuRef"
              class="fixed md:absolute right-4 md:right-0 top-[60px] md:top-full left-4 md:left-auto w-auto md:w-80 rounded-2xl md:rounded-xl bg-white border border-gray-200 shadow-2xl z-[200] overflow-hidden"
              @click.stop>
              <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
                <span class="font-semibold text-sm text-gray-800">Notifications</span>
                <div class="flex items-center gap-2">
                  <span v-if="notificationCount > 0" class="text-xs text-gray-500">{{ notificationCount }} unread</span>
                  <button @click="notificationMenuOpen = false"
                    class="p-1 rounded-lg hover:bg-gray-200 transition text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                  </button>
                </div>
              </div>
              <div class="max-h-72 overflow-y-auto divide-y divide-gray-50">
                <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
                  No new notifications
                </div>
                <div v-for="notif in notifications" :key="notif.id" @click="markAsRead(notif.id, notif.link)"
                  class="px-4 py-3 hover:bg-red-50 active:bg-red-100 cursor-pointer transition-colors group border-b border-gray-50 last:border-0">
                  <div class="flex items-start gap-3">
                    <div
                      class="h-8 w-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 group-hover:bg-red-200">
                      <i data-lucide="bell" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-bold text-gray-900 group-hover:text-red-700 truncate">{{ notif.title }}</p>
                      <p class="text-xs text-gray-600 mt-0.5 leading-snug line-clamp-2">{{ notif.message }}</p>
                      <p class="text-[10px] text-gray-400 mt-1 font-medium uppercase tracking-wider">{{ notif.timeAgo }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Added "Mark all as read" for mobile -->
              <div v-if="notifications.length > 0" class="border-t border-gray-100 p-2 bg-gray-50">
                <button @click="markAllRead"
                  class="w-full rounded-md bg-red-600 text-white py-2.5 text-xs font-bold hover:bg-red-700 transition shadow-sm active:scale-[0.98]">
                  Mark all as read
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Mobile slide-out menu for patient -->
      <div v-if="mobileMenuOpen" id="mobile-overlay" class="fixed inset-0 bg-black/40 z-40 md:hidden"
        @click="mobileMenuOpen = false"></div>
      <div
        class="fixed top-0 left-0 h-full w-64 bg-white shadow-2xl z-50 flex flex-col transition-transform duration-300 md:hidden"
        :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <div class="flex items-center gap-2">
            <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo"
              class="h-7 w-auto" />
            <span class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($appName) ?> Portal</span>
          </div>
          <button @click="mobileMenuOpen = false" class="p-1 rounded-lg hover:bg-gray-100">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
          <a v-for="item in menuItems" :key="item.href" :href="basePath + item.href" @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition"
            :class="isActive(item.href) ? 'bg-red-50 text-red-700 font-semibold' : 'text-gray-700 hover:bg-gray-100'">
            <i :data-lucide="item.icon" class="w-5 h-5"
              :class="isActive(item.href) ? 'text-red-600' : 'text-gray-500'"></i>
            {{ item.label }}
          </a>
        </nav>
        <div class="p-4 border-t border-gray-100 relative" ref="mobileProfileMenuRef">
          <!-- Profile Button -->
          <button @click="mobileProfileMenuOpen = !mobileProfileMenuOpen"
            class="w-full flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-gray-100 transition">
            <div class="h-9 w-9 rounded-full bg-red-100 flex items-center justify-center shrink-0 overflow-hidden">
              <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
              <span v-else class="text-red-700 font-bold text-sm" v-text="userInitials"></span>
            </div>
            <div class="flex-1 text-left leading-tight min-w-0">
              <div class="text-sm font-semibold text-gray-900 truncate" v-text="userDisplayName"></div>
              <div class="text-[11px] text-gray-500 truncate" v-text="userEmail"></div>
            </div>
          </button>

          <!-- Profile Dropdown Menu (Mobile) -->
          <div v-show="mobileProfileMenuOpen"
            class="absolute bottom-full left-4 right-4 mb-2 z-50 rounded-2xl bg-gray-800 text-white shadow-2xl border border-white/10 overflow-hidden py-2">
            <div class="px-4 py-3 border-b border-white/10">
              <div class="text-sm font-semibold truncate" v-text="userDisplayName"></div>
              <div class="text-[10px] text-white/50 uppercase tracking-widest font-bold">Patient Account</div>
            </div>
            <div class="p-1.5 space-y-0.5">
              <button @click="openPersonalizationModal(); mobileProfileMenuOpen = false; mobileMenuOpen = false"
                class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm text-white hover:bg-white/10 rounded-lg transition">
                <i data-lucide="palette" class="w-4 h-4 opacity-70"></i>
                <span>Theme</span>
              </button>
              <button @click="openEditProfileModal(); mobileProfileMenuOpen = false; mobileMenuOpen = false"
                class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm text-white hover:bg-white/10 rounded-lg transition">
                <i data-lucide="user" class="w-4 h-4 opacity-70"></i>
                <span>Profile</span>
              </button>
              <button
                class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm text-white hover:bg-white/10 rounded-lg transition">
                <i data-lucide="settings" class="w-4 h-4 opacity-70"></i>
                <span>Settings</span>
              </button>
              <div class="my-1 border-t border-white/10"></div>
              <a href="/<?= PROJECT_DIR ?>/logout"
                class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm text-red-300 hover:text-red-200 hover:bg-white/10 rounded-lg transition">
                <i data-lucide="log-out" class="w-4 h-4 opacity-70"></i>
                <span>Log out</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Patient DESKTOP sidebar -->
      <aside
        class="hidden md:flex fixed left-0 top-0 bg-white border-r px-3 py-6 flex-col h-screen transition-all duration-200 z-50"
        :class="isOpen ? 'w-[275px]' : 'w-20'">
        <button
          class="text-gray-500 text-md absolute top-9 right-2 cursor-w-resize hover:text-red-700 transition sidebar-tooltip"
          @click="toggleSidebar" :data-tooltip="isOpen ? 'Close sidebar' : 'Open sidebar'">
          <!-- panel-left-close icon -->
          <svg v-if="isOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
            <line x1="9" x2="9" y1="3" y2="21" />
            <path d="m16 15-3-3 3-3" />
          </svg>
          <!-- panel-left-open icon -->
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
            <line x1="9" x2="9" y1="3" y2="21" />
            <path d="m14 9 3 3-3 3" />
          </svg>
        </button>
        <div class="mb-6 flex items-center border-b border-gray-200 pb-4">
          <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo"
            class="h-10 w-auto" />
          <span v-if="isOpen" class="text-sm font-semibold text-gray-600 ml-2 truncate"><?= htmlspecialchars($appName) ?>
            Portal</span>
        </div>
        <nav class="flex-1 space-y-1">
          <a v-for="item in menuItems" :key="item.href" :href="basePath + item.href"
            class="group relative flex items-center rounded-md cursor-pointer transition"
            :class="[isOpen ? 'gap-3 px-5 py-2 justify-start' : 'w-full px-0 py-3 justify-center has-tooltip sidebar-tooltip', isActive(item.href) ? 'bg-red-50 text-red-700 font-semibold' : 'text-gray-700 hover:bg-gray-100']"
            :data-tooltip="!isOpen ? item.label : ''">
            <span v-if="isActive(item.href)" class="absolute right-0 top-0 h-full w-1 bg-red-600"></span>
            <i :data-lucide="item.icon" class="w-5 h-5"
              :class="isActive(item.href) ? 'text-red-700' : 'text-gray-700'"></i>
            <span v-if="isOpen" class="text-sm truncate">{{ item.label }}</span>
          </a>
        </nav>
        <div class="pt-2 border-t border-gray-300 relative" ref="profileMenuRef">
          <button @click="profileMenuOpen = !profileMenuOpen"
            class="w-full flex items-center gap-3 rounded-xl px-2.5 py-3 transition"
            :class="profileMenuOpen ? 'bg-gray-100 ring-1 ring-gray-200' : 'hover:bg-gray-100'"
            :data-tooltip="!isOpen ? 'Profile' : ''" :class="!isOpen ? 'has-tooltip sidebar-tooltip' : ''">
            <div class="h-9 w-9 rounded-full bg-red-100 flex items-center justify-center shrink-0 overflow-hidden">
              <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
              <span v-else class="text-red-700 font-semibold text-sm" v-text="userInitials"></span>
            </div>
            <div v-if="isOpen" class="flex-1 text-left leading-tight min-w-0">
              <div class="text-sm font-semibold text-gray-900 dark-text-main" v-text="userDisplayName"></div>
              <div class="text-xs text-gray-500" v-text="userEmail"></div>
            </div>
          </button>
          <div v-if="profileMenuOpen"
            class="absolute z-50 w-64 rounded-2xl bg-gray-800 text-white shadow-xl border border-white/10 overflow-hidden"
            :class="isOpen ? 'left-0 bottom-full mb-2' : 'left-full ml-2 bottom-0'">
            <div class="px-4 py-3 border-b border-white/10 overflow-hidden">
              <div class="text-sm font-semibold truncate" v-text="userDisplayName"></div>
              <div class="text-xs text-white/60 truncate">Patient</div>
            </div>
            <div class="py-2">
              <button @click="openPersonalizationModal"
                class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
                <i data-lucide="palette" class="text-base opacity-90"></i>
                <span>Theme</span>
              </button>
              <button @click="openEditProfileModal"
                class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
                <i data-lucide="user" class="text-base opacity-90"></i>
                <span>Profile</span>
              </button>
              <button @click="role === 'radtech' ? openRadtechSettingsModal() : null"
                class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
                <i data-lucide="settings" class="text-base opacity-90"></i>
                <span>Settings</span>
              </button>
              <div class="my-2 border-t border-white/10"></div>
              <a href="/<?= PROJECT_DIR ?>/logout"
                class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-red-300 hover:text-red-200 hover:bg-white/10 transition">
                <i data-lucide="log-out" class="text-base opacity-90"></i>
                <span>Log out</span>
              </a>
            </div>
          </div>
        </div>
      </aside>

    <?php else: ?>
      <!-- ===== STAFF SIDEBAR (radtech/radiologist/admin) ===== -->
      <aside
        class="fixed left-0 top-0 bg-white border-r px-3 py-6 flex flex-col h-screen transition-all duration-200 z-50"
        :class="isOpen ? 'w-[275px]' : '-translate-x-full md:translate-x-0 md:w-20'"
        :style="{ width: isMobileMenuOpen ? '275px' : (isOpen ? '275px' : '80px'), transform: isMobileMenuOpen ? 'translateX(0)' : (isMobile ? 'translateX(-100%)' : 'translateX(0)') }">
        <button
          class="text-gray-500 text-md absolute top-9 right-2 cursor-w-resize hover:text-red-700 transition sidebar-tooltip"
          @click="toggleSidebar" :data-tooltip="isOpen ? 'Close sidebar' : 'Open sidebar'">
          <!-- panel-left-close icon -->
          <svg v-if="isOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
            <line x1="9" x2="9" y1="3" y2="21" />
            <path d="m16 15-3-3 3-3" />
          </svg>
          <!-- panel-left-open icon -->
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
            <line x1="9" x2="9" y1="3" y2="21" />
            <path d="m14 9 3 3-3 3" />
          </svg>
        </button>

        <!-- Header logo-->
        <div class="mb-6 flex items-center border-b border-gray-200 pb-4">
          <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo"
            class="h-10 w-auto" />
          <span v-if="isOpen" class="text-sm font-semibold text-gray-600 ml-2 truncate">
            <?= htmlspecialchars($appName) ?>
          </span>
        </div>

        <!-- Menu -->
        <nav class="flex-1 space-y-1">
          <a v-for="item in menuItems" :key="item.href" :href="basePath + item.href"
            class="group relative flex items-center rounded-md cursor-pointer transition" :class="[
                isOpen ? 'gap-3 px-5 py-2 justify-start' : 'w-full px-0 py-3 justify-center has-tooltip sidebar-tooltip',
                isActive(item.href)
                ? 'bg-red-50 text-red-700 font-semibold'
                : 'text-gray-700 hover:bg-gray-100'
            ]" :data-tooltip="!isOpen ? item.label : ''">
            <!-- Active right bar -->
            <span v-if="isActive(item.href)" class="absolute right-0 top-0 h-full w-1 bg-red-600"></span>

            <!-- Icon -->
            <i :data-lucide="item.icon" class="w-5 h-5"
              :class="isActive(item.href) ? 'text-red-700' : 'text-gray-700'"></i>

            <!-- Label -->
            <span v-if="isOpen" class="text-sm truncate">
              {{ item.label }}
            </span>
          </a>
        </nav>

        <!-- Bottom (Profile Button) -->
        <div class="pt-2 border-t border-gray-300 relative" ref="profileMenuRef">
          <button @click="profileMenuOpen = !profileMenuOpen"
            class="w-full flex items-center gap-3 rounded-xl px-2.5 py-3 transition"
            :class="[profileMenuOpen ? 'bg-gray-100 ring-1 ring-gray-200' : 'hover:bg-gray-100', !isOpen ? 'has-tooltip sidebar-tooltip' : '']"
            :data-tooltip="!isOpen ? 'Profile' : ''">
            <!-- Avatar -->
            <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center shrink-0 overflow-hidden">
              <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
              <span v-else class="text-blue-700 font-semibold text-sm" v-text="userInitials"></span>
            </div>

            <!-- Name and Role (visible when sidebar is open) -->
            <div v-if="isOpen" class="flex-1 text-left leading-tight min-w-0">
              <div class="text-sm font-semibold text-gray-900 dark-text-main" v-text="userDisplayName"></div>
              <div class="text-xs text-gray-500" v-text="userEmail"></div>
            </div>

          </button>

          <!-- Dropdown Menu -->
          <div v-if="profileMenuOpen"
            class="absolute z-50 w-64 rounded-2xl bg-gray-800 text-white shadow-xl border border-white/10 overflow-hidden"
            :class="isOpen ? 'left-0 bottom-full mb-2' : 'left-full ml-2 bottom-0'">
            <!-- Header -->
            <div class="px-4 py-3 border-b border-white/10 overflow-hidden">
              <div class="text-sm font-semibold truncate" v-text="userDisplayName"></div>
              <div class="text-xs text-white/60 truncate"><?= htmlspecialchars(ucfirst($role)) ?></div>
            </div>

            <!-- Menu Items -->
            <div class="py-2">
              <button @click="openPersonalizationModal"
                class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
                <i data-lucide="palette" class="text-base opacity-90"></i>
                <span>Theme</span>
              </button>
              <button @click="openEditProfileModal"
                class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
                <i data-lucide="user" class="text-base opacity-90"></i>
                <span>Profile</span>
              </button>
              <button @click="(role === 'radtech' || role === 'radiologist') ? openRadtechSettingsModal() : null"
                class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
                <i data-lucide="settings" class="text-base opacity-90"></i>
                <span>Settings</span>
              </button>
              <div class="my-2 border-t border-white/10"></div>
              <a href="/<?= PROJECT_DIR ?>/logout"
                class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-red-300 hover:text-red-200 hover:bg-white/10 transition">
                <i data-lucide="log-out" class="text-base opacity-90"></i>
                <span>Log out</span>
              </a>
            </div>
          </div>
        </div>
      </aside>
    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col transition-all duration-200"
      :style="isMobile ? { marginLeft: '0', paddingTop: '56px' } : { marginLeft: isOpen ? '275px' : '80px' }">
      <!-- Topbar -->
      <div class="bg-white border-b px-6 py-4 relative <?= $isPatient ? 'hidden md:block' : '' ?>">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h1 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($appName) ?></h1>
            <?php if ($role !== 'radiologist'): ?>
              <p class="text-sm text-gray-500"><?= $branchNameDisplay ?></p>
            <?php endif; ?>
          </div>

          <div class="flex items-center gap-3">
            <div
              class="hidden sm:flex items-center gap-2 rounded-lg bg-orange-50 px-3 py-2 text-sm font-medium text-orange-800">
              <i data-lucide="calendar" class="w-4 h-4 text-orange-600"></i>
              <span id="topbarDateTime" class="whitespace-nowrap"></span>
            </div>

            <div class="relative">
              <button @click.prevent="toggleNotificationMenu"
                class="relative rounded-full border border-gray-200 bg-white p-2 text-gray-700 hover:bg-gray-100 shadow-sm has-tooltip bottom-tooltip"
                :class="notificationCount > 0 ? 'ring-2 ring-red-200' : ''" type="button" aria-label="Notifications"
                data-tooltip="Notifications">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span v-if="notificationCount > 0"
                  class="absolute -top-1 -right-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">
                  {{ notificationCount }}
                </span>
              </button>

              <div v-if="notificationMenuOpen" ref="notificationMenuRef"
                class="absolute right-2 top-full mt-2 z-50 w-80 rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50">
                  <div class="text-sm font-bold text-gray-800">Notifications</div>
                  <button @click="closeNotificationMenu"
                    class="text-[10px] items-center flex gap-1 font-bold uppercase tracking-wider text-gray-400 hover:text-gray-600 transition">
                    <i data-lucide="x" class="w-3 h-3"></i> Close
                  </button>
                </div>
                <div class="max-h-72 overflow-auto custom-scrollbar">
                  <template v-if="notifications.length > 0">
                    <div v-for="item in notifications" :key="item.id" @click="markAsRead(item.id, item.link)"
                      class="flex gap-3 px-4 py-3 hover:bg-gray-100 active:bg-gray-200 cursor-pointer border-b last:border-0 border-gray-100 transition-colors group">
                      <div
                        class="h-8 w-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 transition-colors">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                      </div>
                      <div class="text-sm flex-1">
                        <div class="font-bold text-gray-900 transition-colors">{{ item.title }}
                        </div>
                        <div class="text-gray-600 text-xs mt-0.5 leading-snug line-clamp-2">{{ item.message }}</div>
                        <div class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-widest">{{ item.timeAgo
                          }}</div>
                      </div>
                    </div>
                  </template>
                  <div v-else class="py-12 flex flex-col items-center justify-center text-center px-6">
                    <div class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                      <i data-lucide="bell-off" class="w-8 h-8 text-gray-300"></i>
                    </div>
                    <div class="text-sm font-bold text-gray-800">No new notifications</div>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">We'll let you know when there's an update on
                      your cases or reports.</p>
                  </div>
                </div>
                <div v-if="notifications.length > 0" class="border-t px-4 py-3 bg-gray-50">
                  <button @click="markAllRead"
                    class="w-full rounded-md bg-red-600 text-white py-2 text-sm font-bold hover:bg-red-700 transition shadow-sm active:scale-[0.98]">
                    Mark all as read
                  </button>
                </div>
              </div>
            </div>

            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden">
              <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
              <span v-else class="text-blue-700 font-semibold text-sm" v-text="userInitials"></span>
            </div>
          </div>
        </div>
      </div>

      <main class="flex-1 overflow-y-auto patient-main-content" :class="isMobile ? 'p-4' : 'p-6'">
        <?php
        if (isset($contentView) && file_exists($contentView)) {
          require $contentView;
        } else {
          $fallback = __DIR__ . '/../pages/' . htmlspecialchars($role) . '/dashboard.php';
          if (file_exists($fallback)) {
            require $fallback;
          } else {
            $fallback2 = __DIR__ . '/../pages/radtech/dashboard.php';
            if (file_exists($fallback2))
              require $fallback2;
            else
              echo "<h2 class='text-xl font-semibold'>Page Missing</h2>";
          }
        }
        ?>
      </main>
      <!-- EDIT PROFILE MODAL -->
      <div v-cloak v-if="editProfileModalOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="editProfileModalOpen = false">
        <div
          class="bg-[#2a2b32] text-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col transform transition-all p-6 relative gap-5">
          <button @click="editProfileModalOpen = false"
            class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
          <h2 class="text-lg font-semibold mb-6">Edit profile</h2>

          <div class="flex justify-center mb-6 relative">
            <div
              class="h-24 w-24 rounded-full bg-gray-600 flex items-center justify-center text-3xl font-medium tracking-wide overflow-hidden border-2 border-[#1e1e24] shadow-inner">
              <template v-if="uploadPreview || userAvatar">
                <img :src="uploadPreview || userAvatar" class="w-full h-full object-cover">
              </template>
              <template v-else>{{ userInitials }}</template>
            </div>
            <button @click="$refs.avatarInput.click()"
              class="absolute bottom-0 right-[100px] h-8 w-8 rounded-full bg-[#1e1e24] border border-[#3e3f4b] flex items-center justify-center hover:bg-gray-700 transition">
              <i data-lucide="camera" class="w-4 h-4"></i>
            </button>
            <input type="file" ref="avatarInput" class="hidden" accept="image/*" @change="handleAvatarChange">
          </div>

          <div class="space-y-4">
            <div class="border border-gray-600 rounded-lg bg-[#1e1e24] p-3 focus-within:border-gray-400 transition">
              <label class="block text-xs text-gray-400 font-medium mb-1">Display name</label>
              <input type="text" v-model="editDisplayName"
                class="w-full bg-transparent border-none outline-none text-white text-sm" />
            </div>
            <div class="border border-gray-600 rounded-lg bg-[#1e1e24] p-3 focus-within:border-gray-400 transition">
              <label class="block text-xs text-gray-400 font-medium mb-1">Username / Email</label>
              <input type="email" v-model="editEmail"
                class="w-full bg-transparent border-none outline-none text-white text-sm" />
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-auto">
            <button @click="editProfileModalOpen = false"
              class="px-5 py-2 rounded-full border border-gray-600 hover:bg-gray-700 transition text-sm font-medium">Cancel</button>
            <button @click="saveProfile"
              class="px-5 py-2 rounded-full bg-white text-black hover:bg-gray-200 transition text-sm font-medium"
              :disabled="savingProfile">
              <span v-if="savingProfile">Saving...</span>
              <span v-else>Save</span>
            </button>
          </div>
        </div>
      </div>

      <!-- PERSONALIZATION MODAL -->
      <div v-cloak v-if="personalizationModalOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="personalizationModalOpen = false">
        <div
          class="bg-[#2a2b32] text-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col p-6 relative">
          <button @click="personalizationModalOpen = false"
            class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
          <h2 class="text-lg font-semibold mb-6 flex items-center gap-2"><i data-lucide="palette" class="w-5 h-5"></i>
            Theme</h2>

          <div class="space-y-4">
            <label class="block font-medium mb-2">Theme Preference</label>
            <div class="grid grid-cols-2 gap-3">
              <button @click="setTheme('light')"
                class="rounded-xl border border-gray-600 p-4 flex flex-col items-center gap-2 hover:bg-gray-700 transition"
                :class="!isDarkTheme ? 'ring-2 ring-red-500 bg-gray-700' : ''">
                <i data-lucide="sun" class="w-6 h-6"></i>
                <span class="text-sm font-semibold">Light Mode</span>
              </button>
              <button @click="setTheme('dark')"
                class="rounded-xl border border-gray-600 p-4 flex flex-col items-center gap-2 hover:bg-gray-700 transition"
                :class="isDarkTheme ? 'ring-2 ring-red-500 bg-gray-700' : ''">
                <i data-lucide="moon" class="w-6 h-6"></i>
                <span class="text-sm font-semibold">Dark Mode</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- RADTECH SETTINGS MODAL -->
      <div v-cloak v-if="radtechSettingsModalOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="radtechSettingsModalOpen = false">
        <div
          class="bg-[#2a2b32] text-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col p-6 relative gap-5">
          <button @click="radtechSettingsModalOpen = false"
            class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
          <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
            <i data-lucide="settings" class="w-5 h-5"></i> {{ role === 'radiologist' ? 'Radiologist' : 'RadTech' }}
            Settings
          </h2>

          <div class="space-y-4">
            <!-- Full Name for Report -->
            <div class="border border-gray-600 rounded-lg bg-[#1e1e24] p-3 focus-within:border-gray-400 transition">
              <label class="block text-xs text-gray-400 font-medium mb-1">Full Name for Printed Report</label>
              <input type="text" v-model="editFullName" placeholder="Enter your full name for reports..."
                class="w-full bg-transparent border-none outline-none text-white text-sm" />
            </div>

            <!-- Professional Title -->
            <div class="border border-gray-600 rounded-lg bg-[#1e1e24] p-3 focus-within:border-gray-400 transition">
              <label class="block text-xs text-gray-400 font-medium mb-1">Professional Titles (e.g. RXT, RRT)</label>
              <input type="text" v-model="editProfessionalTitle" placeholder="Enter your titles..."
                class="w-full bg-transparent border-none outline-none text-white text-sm" />
            </div>

            <!-- Signature Upload -->
            <div class="space-y-2">
              <label class="block text-xs text-gray-400 font-medium">Digital Signature</label>
              <div
                class="relative group cursor-pointer border-2 border-dashed border-gray-600 rounded-xl p-4 bg-[#1e1e24] hover:border-gray-400 transition"
                @click="$refs.signatureInput.click()">
                <div v-if="signaturePreview || userSignature" class="flex flex-col items-center gap-3">
                  <img :src="signaturePreview || userSignature" class="max-h-24 object-contain bg-white rounded-md p-1">
                  <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Click to change
                    signature</span>
                </div>
                <div v-else class="py-6 flex flex-col items-center gap-2 text-gray-500">
                  <i data-lucide="pen-tool" class="w-8 h-8 opacity-50"></i>
                  <span class="text-xs">Upload your signature image</span>
                </div>
                <input type="file" ref="signatureInput" class="hidden" accept="image/*" @change="handleSignatureChange">
              </div>
              <p class="text-[10px] text-gray-500 italic">This signature will be used in your diagnostic findings
                reports.</p>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <button @click="radtechSettingsModalOpen = false"
              class="px-5 py-2 rounded-full border border-gray-600 hover:bg-gray-700 transition text-sm font-medium">Cancel</button>
            <button @click="saveRadtechSettings"
              class="px-5 py-2 rounded-full bg-white text-black hover:bg-gray-200 transition text-sm font-medium"
              :disabled="savingRadtechSettings">
              <span v-if="savingRadtechSettings">Saving...</span>
              <span v-else>Save Settings</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Google Drive-Style Preview Modal (Cinematic v4.0) -->
  <div id="drive-preview-modal"
    class="hidden fixed inset-0 z-[1000] bg-[#0a0a0a] flex flex-col font-sans select-none overflow-hidden backdrop-blur-sm transition-all duration-300">

    <!-- Premium Red Top Bar -->
    <div
      class="drive-top-bar absolute top-0 left-0 right-0 flex items-center justify-between px-5 h-16 text-white z-[100] bg-red-600 shadow-[0_2px_20px_rgba(0,0,0,0.4)]">
      <div class="flex items-center gap-4">
        <button id="drive-close-btn"
          class="p-2.5 hover:bg-white/10 rounded-full transition-all active:scale-90 has-tooltip bottom-tooltip"
          data-tooltip="Exit (Esc)">
          <i data-lucide="arrow-left" class="w-6 h-6"></i>
        </button>
        <div class="flex flex-col">
          <div class="flex items-center gap-3">
            <span id="drive-filename" class="font-black text-xs uppercase tracking-tight opacity-95">--</span>
          </div>
        </div>
      </div>

      <!-- Action Cluster -->
      <div class="flex items-center gap-2">

        <!-- Zoom Controls -->
        <div class="flex items-center gap-4 bg-black/10 rounded-lg px-3 py-1.5 border border-white/5 shadow-inner">
          <button id="drive-zoom-out" class="p-1 hover:text-red-100 transition-colors has-tooltip bottom-tooltip"
            data-tooltip="Zoom Out">
            <i data-lucide="minus-circle" class="w-5 h-5"></i>
          </button>
          <span id="drive-zoom-val"
            class="text-[11px] font-black min-w-[3.5rem] text-center tracking-widest text-white/90">100%</span>
          <button id="drive-zoom-in" class="p-1 hover:text-red-100 transition-colors has-tooltip bottom-tooltip"
            data-tooltip="Zoom In">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
          </button>
          <div class="w-px h-4 bg-white/10 mx-2"></div>
          <span id="drive-page-info"
            class="text-[10px] font-black tracking-widest text-white/90 min-w-[3rem] text-center">1 / 1</span>
        </div>
      </div>
    </div>

    <!-- Main Interaction Area -->
    <div class="flex-1 relative flex items-center justify-center overflow-hidden h-full">

      <!-- Large Floating Side Arrows -->
      <button id="drive-prev-side"
        class="absolute left-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-black/40 hover:bg-black/60 text-white/80 hover:text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[110] group"
        title="Previous (Left Arrow)"
        style="position: absolute !important; left: 1.5rem !important; top: 50% !important; transform: translateY(-50%) !important; margin: 0 !important;">
        <i data-lucide="chevron-left"
          class="w-8 h-8 group-hover:-translate-x-0.5 transition-transform text-white shadow-sm"></i>
      </button>

      <button id="drive-next-side"
        class="absolute right-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-black/40 hover:bg-black/60 text-white/80 hover:text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[110] group"
        title="Next (Right Arrow)"
        style="position: absolute !important; right: 1.5rem !important; top: 50% !important; transform: translateY(-50%) !important; margin: 0 !important;">
        <i data-lucide="chevron-right"
          class="w-8 h-8 group-hover:translate-x-0.5 transition-transform text-white shadow-sm"></i>
      </button>

      <!-- The Content -->
      <div id="drive-content-wrapper"
        class="w-full h-full flex items-center justify-center transition-all duration-300">
        <!-- Content injected via JS -->
      </div> <!-- Bottom Thumbnail Strip (FLOATING OVERLAY) -->
      <div id="drive-thumb-strip"
        class="absolute bottom-6 left-1/2 -translate-x-1/2 h-16 bg-black/40 backdrop-blur-md rounded-2xl flex flex-row items-center px-4 gap-3 z-[110] transition-all duration-300 border border-white/10 scrollbar-hide overflow-x-auto max-w-[90%] shadow-2xl">
        <!-- Thumbnails injected via JS -->
      </div>

      <!-- Panning Overlay -->
      <div id="drive-panning-overlay"></div>

    </div>
  </div>

  <!-- ✅ Vue production local asset -->
  <script type="text/javascript" src="/<?= PROJECT_DIR ?>/public/assets/js/vue.global.prod.js"></script>

  <!-- ✅ Lucide production local asset -->
  <script type="text/javascript" src="/<?= PROJECT_DIR ?>/public/assets/js/lucide.min.js"></script>

  <!-- ✅ Inject PHP data -->
  <script>
    window.__APP__ = {
      role: <?= json_encode($role) ?>,
      menuItems: <?= json_encode($menuItems) ?>,
      currentPath: <?= json_encode($currentPath) ?>,
      basePath: <?= json_encode($basePath) ?>,
      userDisplayName: <?= json_encode($userDisplayName) ?>,
      userEmail: <?= json_encode($userEmail) ?>,
      userInitials: <?= json_encode($initials) ?>,
      userAvatar: <?= json_encode($userAvatar) ?>,
      userSignature: <?= json_encode($userSignature) ?>,
      userProfessionalTitle: <?= json_encode($userProfessionalTitle) ?>,
      userFullNameReport: <?= json_encode($userFullNameReport) ?>
    };
  </script>

  <!-- Dynamic Dark Theme -->
  <style>
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

    /* Red / Emergency */
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
  </style>

  <!-- ✅ Vue App -->
  <script>
    const { createApp, nextTick } = Vue;

    createApp({
      data() {
        return {
          isOpen: localStorage.getItem('citilife_sidebar_open') !== 'false',
          isMobile: window.innerWidth < 768,
          mobileMenuOpen: false,
          mobileProfileMenuOpen: false,
          profileMenuOpen: false,
          notificationMenuOpen: false,
          notificationCount: 0,
          notifications: [],
          menuItems: window.__APP__.menuItems,
          currentPath: window.__APP__.currentPath,
          basePath: window.__APP__.basePath,
          // New Profile Data
          userDisplayName: window.__APP__.userDisplayName,
          userEmail: window.__APP__.userEmail,
          userInitials: window.__APP__.userInitials,
          userAvatar: window.__APP__.userAvatar,
          editProfileModalOpen: false,
          personalizationModalOpen: false,
          editDisplayName: '',
          editEmail: '',
          uploadFile: null,
          uploadPreview: null,
          savingProfile: false,
          isDarkTheme: false,
          // RadTech Settings
          radtechSettingsModalOpen: false,
          userSignature: window.__APP__.userSignature,
          userProfessionalTitle: window.__APP__.userProfessionalTitle,
          userFullNameReport: window.__APP__.userFullNameReport,
          editFullName: window.__APP__.userFullNameReport || window.__APP__.userDisplayName,
          editProfessionalTitle: window.__APP__.userProfessionalTitle || '',
          signatureFile: null,
          signaturePreview: null,
          savingRadtechSettings: false,
          role: window.__APP__.role
        };
      },
      mounted() {
        // ===== DISMISS LOADING SKELETON =====
        const loader = document.getElementById('app-loading');
        if (loader) {
          loader.classList.add('fade-out');
          setTimeout(() => {
            loader.classList.add('hidden');
          }, 280);
        }
        // =====================================

        // Load theme from localStorage
        const storedTheme = localStorage.getItem('citilife_theme') || 'light';
        this.isDarkTheme = (storedTheme === 'dark');
        if (this.isDarkTheme) {
          document.documentElement.classList.add('theme-dark');
          document.body.classList.add('theme-dark');
          document.documentElement.style.colorScheme = 'dark';
        } else {
          document.documentElement.classList.remove('theme-dark');
          document.body.classList.remove('theme-dark');
          document.documentElement.style.colorScheme = 'light';
        }
        // Detect window resize for mobile
        window.addEventListener('resize', () => { this.isMobile = window.innerWidth < 768; });

        this.fetchNotifications();
        setInterval(() => this.fetchNotifications(), 5000); // 5s fetch interval

        // Close profile menu and notifications when clicking outside
        document.addEventListener("mousedown", (e) => {
          if (this.$refs.profileMenuRef && !this.$refs.profileMenuRef.contains(e.target)) {
            this.profileMenuOpen = false;
          }
          if (this.$refs.mobileProfileMenuRef && !this.$refs.mobileProfileMenuRef.contains(e.target)) {
            this.mobileProfileMenuOpen = false;
          }
          if (this.notificationMenuOpen) {
            const inDesktopNotif = this.$refs.notificationMenuRef && this.$refs.notificationMenuRef.contains(e.target);
            const inMobileNotif = this.$refs.mobileNotificationMenuRef && this.$refs.mobileNotificationMenuRef.contains(e.target);
            const isNotifButton = e.target.closest('[aria-label="Notifications"]') || e.target.closest('button[onclick*="toggleNotificationMenu"]');

            if (!inDesktopNotif && !inMobileNotif && !isNotifButton) {
              this.notificationMenuOpen = false;
            }
          }
        });

        // Close on Escape key
        document.addEventListener("keydown", (e) => {
          if (e.key === "Escape") {
            this.profileMenuOpen = false;
            this.mobileProfileMenuOpen = false;
            this.notificationMenuOpen = false;
            this.editProfileModalOpen = false;
            this.personalizationModalOpen = false;
          }
        });

        nextTick(() => this.renderIcons());
      },
      methods: {
        openEditProfileModal() {
          this.profileMenuOpen = false;
          this.editDisplayName = this.userDisplayName;
          this.editEmail = this.userEmail;
          this.uploadFile = null;
          this.uploadPreview = null;
          this.editProfileModalOpen = true;
          nextTick(() => this.renderIcons());
        },
        openPersonalizationModal() {
          this.profileMenuOpen = false;
          this.personalizationModalOpen = true;
          nextTick(() => this.renderIcons());
        },
        openRadtechSettingsModal() {
          this.profileMenuOpen = false;
          this.editFullName = this.userFullNameReport || this.userDisplayName;
          this.editProfessionalTitle = this.userProfessionalTitle || '';
          this.signatureFile = null;
          this.signaturePreview = null;
          this.radtechSettingsModalOpen = true;
          nextTick(() => this.renderIcons());
        },
        handleSignatureChange(e) {
          const file = e.target.files[0];
          if (file) {
            this.signatureFile = file;
            const reader = new window.FileReader();
            reader.onload = e => this.signaturePreview = e.target.result;
            reader.readAsDataURL(file);
          }
        },
        saveRadtechSettings() {
          if (!this.editFullName) {
            alert('Full Name is required.');
            return;
          }
          this.savingRadtechSettings = true;
          const formData = new window.FormData();
          formData.append('action', 'update_radtech_settings');
          formData.append('report_full_name', this.editFullName);
          formData.append('professional_title', this.editProfessionalTitle);
          if (this.signatureFile) {
            formData.append('signature', this.signatureFile);
          }

          fetch('/<?= PROJECT_DIR ?>/app/api/update_profile.php', {
            method: 'POST',
            body: formData
          })
            .then(res => res.json())
            .then(data => {
              this.savingRadtechSettings = false;
              if (data.success) {
                // UPDATE ONLY REPORT-SPECIFIC STATE
                this.userFullNameReport = data.full_name_report;
                this.userProfessionalTitle = data.professional_title;
                window.__APP__.userFullNameReport = data.full_name_report;
                window.__APP__.userProfessionalTitle = data.professional_title;

                if (data.signature) {
                  this.userSignature = data.signature;
                  window.__APP__.userSignature = data.signature;
                }
                this.radtechSettingsModalOpen = false;

                if (window.showSuccess) {
                  showSuccess('Report settings updated!');
                }
              } else {
                alert(data.error || 'Failed to update settings.');
              }
            })
            .catch(err => {
              console.error(err);
              this.savingRadtechSettings = false;
              alert('A network error occurred.');
            });
        },
        handleAvatarChange(e) {
          const file = e.target.files[0];
          if (file) {
            this.uploadFile = file;
            const reader = new window.FileReader();
            reader.onload = e => this.uploadPreview = e.target.result;
            reader.readAsDataURL(file);
          }
        },
        setTheme(themeName) {
          this.isDarkTheme = (themeName === 'dark');
          localStorage.setItem('citilife_theme', themeName);
          if (this.isDarkTheme) {
            document.documentElement.classList.add('theme-dark');
            document.body.classList.add('theme-dark');
            document.documentElement.style.colorScheme = 'dark';
          } else {
            document.documentElement.classList.remove('theme-dark');
            document.body.classList.remove('theme-dark');
            document.documentElement.style.colorScheme = 'light';
          }
        },
        saveProfile() {
          if (!this.editDisplayName || !this.editEmail) return;
          this.savingProfile = true;

          const formData = new window.FormData();
          formData.append('action', 'update_profile');
          formData.append('system_name', this.editDisplayName);
          formData.append('email', this.editEmail);
          if (this.uploadFile) {
            formData.append('avatar', this.uploadFile);
          }

          fetch('/<?= PROJECT_DIR ?>/app/api/update_profile.php', {
            method: 'POST',
            body: formData
          })
            .then(res => res.json())
            .then(data => {
              this.savingProfile = false;
              if (data.success) {
                this.userDisplayName = data.name;
                window.__APP__.userDisplayName = data.name;
                this.userEmail = data.email;
                window.__APP__.userEmail = data.email;
                this.userInitials = data.initials;
                if (data.avatar) {
                  this.userAvatar = data.avatar;
                  window.__APP__.userAvatar = data.avatar;
                }
                this.editProfileModalOpen = false;
              } else {
                alert(data.error || 'Failed to update profile.');
              }
            })
            .catch(err => {
              console.error(err);
              this.savingProfile = false;
              alert('A network error occurred.');
            });
        },
        toggleSidebar() {
          this.isOpen = !this.isOpen;
          localStorage.setItem('citilife_sidebar_open', this.isOpen);

          // Sync bootstrap class for skeleton/layout consistency
          if (this.isOpen) {
            document.documentElement.classList.remove('sidebar-collapsed');
          } else {
            document.documentElement.classList.add('sidebar-collapsed');
          }
        },
        toggleMobileMenu() {
          this.mobileMenuOpen = !this.mobileMenuOpen;
        },
        toggleNotificationMenu() {
          this.notificationMenuOpen = !this.notificationMenuOpen;
        },
        closeNotificationMenu() {
          this.notificationMenuOpen = false;
        },
        fetchNotifications() {
          fetch('/<?= PROJECT_DIR ?>/app/api/notifications.php')
            .then(res => res.json())
            .then(data => {
              if (!data.error) {
                this.notificationCount = data.unread_count;
                this.notifications = data.notifications;
                nextTick(() => this.renderIcons());
              }
            })
            .catch(err => console.error('Error fetching notifications:', err));
        },
        markAllRead() {
          fetch('/<?= PROJECT_DIR ?>/app/api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read' })
          }).then(() => {
            this.notificationCount = 0;
            this.notifications = [];
            this.notificationMenuOpen = false;
          });
        },
        markAsRead(id, link) {
          fetch('/<?= PROJECT_DIR ?>/app/api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', notification_id: id })
          }).then(() => {
            if (link && link !== '#') {
              window.location.href = link;
            } else {
              this.fetchNotifications();
            }
          });
        },
        isActive(href) {
          try {
            const currentUrl = new window.URL(window.location.href);
            // Extract current page: prefer ?page= query param, fallback to last path segment
            const _currentPageParam = currentUrl.searchParams.get('page');
            let _currentPathSeg = currentUrl.pathname.replace(/\/$/, '').split('/').pop();
            // Treat index.php as dashboard if no page param is set
            if (!_currentPageParam && (_currentPathSeg === 'index.php' || _currentPathSeg === 'CitiLife-System' || _currentPathSeg === '')) {
              _currentPathSeg = 'dashboard';
            }
            const currentPage = _currentPageParam || _currentPathSeg || 'dashboard';
            // Always use the session role — never use the URL `role` filter param
            // (the `role` query param is a *filter*, not the user's actual role)
            const currentRole = window.__APP__.role;
            const targetRole = window.__APP__.role;

            // Try parsing the target href
            const targetUrl = new window.URL(href, window.location.origin + (window.__APP__.basePath || ""));

            // Get page from query param; if not present, extract from the last path segment
            let targetPage = targetUrl.searchParams.get('page');
            if (!targetPage) {
              const pathSegments = targetUrl.pathname.replace(/\/$/, '').split('/');
              targetPage = pathSegments[pathSegments.length - 1] || 'dashboard';
            }

            // Special case associations: keep sidebar item active for sub-pages
            if (targetPage === 'patient-lists' && ['patient-lists', 'patient-approval', 'patient-details'].includes(currentPage)) {
              return true;
            }
            if (targetPage === 'xray-patient-records' && ['xray-patient-records', 'records-history'].includes(currentPage)) {
              return true;
            }
            if (targetPage === 'record-request' && ['record-request', 'view-record-request'].includes(currentPage)) {
              return true;
            }
            if (targetPage === 'branch-xray-cases' && ['branch-xray-cases', 'patient-details', 'records-history'].includes(currentPage)) {
              return true;
            }

            // Radiologist specific associations
            if (currentRole === 'radiologist' || currentRole === 'radtech' || currentRole === 'admin_central' || currentRole === 'branch_admin' || currentRole === 'it_admin') {
              if (targetPage === 'worklist' && ['worklist', 'patient-queue', 'case-review'].includes(currentPage)) {
                return true;
              }
              if (targetPage === 'patient-history' && ['patient-history', 'patient-records-history'].includes(currentPage)) {
                return true;
              }
            }

            // Default: match by page and role
            return currentPage === targetPage && currentRole === targetRole;
          } catch (e) {
            const base = window.__APP__.basePath || "";
            const fullHref = href.startsWith(base) ? href : (base + href);
            return this.currentPath === fullHref;
          }
        },
        renderIcons() {
          if (window.lucide) lucide.createIcons();
        }
      },
      updated() {
        nextTick(() => this.renderIcons());
      }
    }).mount("#app");

    // Real-time date and time for topbar
    function updateTopbarDateTime() {
      const now = new window.Date();

      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      const optionsTime = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };

      const dtElem = document.getElementById('topbarDateTime');
      if (!dtElem) return;

      const dateText = now.toLocaleDateString(undefined, options);
      const timeText = now.toLocaleTimeString(undefined, optionsTime);
      dtElem.textContent = `${dateText} at ${timeText}`;
    }

    updateTopbarDateTime();
    setInterval(updateTopbarDateTime, 1000);

    // AJAX Polling for Real-Time Updates
    if (document.querySelectorAll('.realtime-update').length > 0) {
      setInterval(() => {
        // Use the persistent currentPath from __APP__ instead of window.location.href 
        // to survive URL cleaning (Stealth Mode) used in Patient portal views.
        let baseUrl = window.location.origin + (window.__APP__.currentPath || window.location.pathname);
        let url = baseUrl + (baseUrl.includes('?') ? '&ajax_polling=1' : '?ajax_polling=1');

        fetch(url)
          .then(res => res.text())
          .then(html => {
            const doc = new window.DOMParser().parseFromString(html, 'text/html');
            document.querySelectorAll('.realtime-update').forEach(el => {
              if (el.id) {
                const newEl = doc.getElementById(el.id);
                if (newEl) el.innerHTML = newEl.innerHTML;
              }
            });
            // Re-initialize any lucide icons in the replaced content
            if (window.lucide) lucide.createIcons();
            // Notify pagination scripts to re-apply page filter
            document.dispatchEvent(new window.CustomEvent('realtime:updated'));
          })
          .catch(err => console.error('Polling error:', err));
      }, 3000); // 3 seconds interval
    }

    // Global Radiologist Activity Polling (survives AJAX replacements)
    function checkRadStatusGlobal() {
      const dot = document.getElementById('rad-activity-dot');
      if (!dot) return;

      const caseId = dot.getAttribute('data-case-id');
      if (!caseId) return;

      fetch(`/<?= PROJECT_DIR ?>/app/api/case_activity.php?action=status&case_id=${caseId}&_t=` + Date.now())
        .then(res => res.json())
        .then(data => {
          if (!data.success) return;
          const currentDot = document.getElementById('rad-activity-dot');
          if (!currentDot) return;

          currentDot.classList.remove('bg-gray-400', 'bg-green-500', 'bg-red-500');
          if (data.state === 'active') {
            currentDot.classList.add('bg-green-500');
          } else if (data.state === 'idle') {
            currentDot.classList.add('bg-gray-400');
          } else {
            currentDot.classList.add('bg-red-500');
          }
        }).catch(console.error);
    }

    setInterval(checkRadStatusGlobal, 3000);
    checkRadStatusGlobal();

    // --- AUTO-LOGOUT SECURITY POLICY ---
    (function () {
      const timeoutMinutes = <?= isset($autoLogoutMinutes) ? $autoLogoutMinutes : 0 ?>;
      if (timeoutMinutes <= 0) return;

      console.log(`Security: Inactivity monitor active (${timeoutMinutes}m). [Robust Timestamp Mode]`);

      let lastActivity = Date.now();
      let isWarningOpen = false;
      const warningThreshold = 60; // 60 seconds before logout

      // Timer tick every 1 second (even if throttled, calculation remains accurate)
      const tick = setInterval(() => {
        const now = Date.now();
        const idleSeconds = Math.floor((now - lastActivity) / 1000);
        const totalTimeout = timeoutMinutes * 60;
        const remaining = totalTimeout - idleSeconds;

        // Show warning if 1 minute left
        if (remaining <= warningThreshold && !isWarningOpen) {
          isWarningOpen = true;
          const modal = document.getElementById('sessionTimeoutModal');
          if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
          }
        }

        // Update countdown in modal
        if (isWarningOpen) {
          const countdownElem = document.getElementById('timeoutCountdown');
          if (countdownElem) countdownElem.textContent = remaining > 0 ? remaining : 0;
        }

        // Forced logout
        if (remaining <= 0) {
          window.location.href = `/<?= PROJECT_DIR ?>/logout?reason=timeout`;
        }
      }, 1000);

      // Events that reset the timer (only if modal is not open)
      const resetEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
      resetEvents.forEach(evt => {
        document.addEventListener(evt, () => {
          if (!isWarningOpen) lastActivity = Date.now();
        }, true);
      });

      // Robust Check mapping to window focus/visibility (Fix for Minimized/Background tabs)
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
          const now = Date.now();
          const idleSeconds = Math.floor((now - lastActivity) / 1000);
          const totalTimeout = timeoutMinutes * 60;

          if (idleSeconds >= totalTimeout) {
            window.location.href = `/<?= PROJECT_DIR ?>/logout?reason=timeout`;
          }
        }
      });

      // Modal Action: Stay Logged In
      window.resumeSession = function () {
        isWarningOpen = false;
        idleSeconds = 0;
        const modal = document.getElementById('sessionTimeoutModal');
        if (modal) {
          modal.classList.add('hidden');
          modal.classList.remove('flex');
        }
      };

      // Modal Action: Logout Now
      window.logoutNow = function () {
        window.location.href = `/<?= PROJECT_DIR ?>/logout?reason=manual`;
      };
    })();
  </script>
  <!-- Session Timeout Warning Modal -->
  <div id="sessionTimeoutModal"
    class="hidden fixed inset-0 items-center justify-center p-4 bg-gray-900/90 backdrop-blur-md animate-in fade-in duration-300"
    style="z-index: 2147483647 !important;">
    <div
      class="bg-white w-full max-w-sm rounded-[32px] p-8 shadow-2xl relative overflow-hidden flex flex-col items-center text-center space-y-6">
      <!-- Decoration -->
      <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-50 rounded-full opacity-50"></div>

      <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center text-red-600 shadow-inner">
        <i data-lucide="timer" class="w-8 h-8"></i>
      </div>

      <div class="space-y-2">
        <h3 class="text-xl font-black text-gray-900 tracking-tight">Security Alert</h3>
        <p class="text-sm text-gray-500 leading-relaxed">
          For your protection, your session will expire in <span id="timeoutCountdown"
            class="font-black text-red-600">60</span> seconds due to inactivity.
        </p>
      </div>

      <div class="flex flex-col gap-3 w-full pt-2">
        <button onclick="resumeSession()"
          class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-red-600/20 transition transform active:scale-95">
          Stay Logged In
        </button>
        <button onclick="logoutNow()"
          class="w-full py-4 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest transition">
          Logout Now
        </button>
      </div>
    </div>
  </div>

  <script>
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  </script>
</body>

</html>