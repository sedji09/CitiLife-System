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
  // Sync session and local variables with DB
  $_SESSION['email'] = $currentUser['email'];
  $_SESSION['name'] = $currentUser['name'];
  $_SESSION['role'] = $currentUser['role'];
  $userEmail = $currentUser['email'];
  $role = $currentUser['role'];
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
$userIsAvailable = $currentUser['is_available'] ?? 1;

// AuthHelper is autoloaded via composer

$isPatient = ($role === 'patient');

$userFirstName = '';
$userLastName = '';
$userBirthdate = '';
$userSex = 'Male';
$userContactNumber = '';
$userHomeAddress = '';

if ($isPatient) {
  $patientModel = new \PatientModel($pdo);
  $patientData = $patientModel->getPatientByUserId($userId);
  if ($patientData) {
    $userFirstName = $patientData['first_name'] ?? '';
    $userLastName = $patientData['last_name'] ?? '';
    $userBirthdate = $patientData['birthdate'] ?? '';
    $userSex = $patientData['sex'] ?? 'Male';
    $userContactNumber = $patientData['contact_number'] ?? '';
    $userHomeAddress = $patientData['home_address'] ?? '';
  }
}


$menusPath = __DIR__ . '/../../config/menus.php';
if (function_exists('opcache_invalidate')) {
  opcache_invalidate($menusPath, true);
}
$menus = require $menusPath;

$allRoleMenus = $menus[$role] ?? [];
$systemFeatures = $menus['system_feature_menus'] ?? [];

// Dynamic RBAC Filter
$menuItems = [];
$addedPerms = [];

// 1. Add all native menus that the user is allowed to see
foreach ($allRoleMenus as $item) {
  $permKey = $item['perm_key'] ?? null;
  if (!$permKey || hasPermission($role, $permKey) > 0) {
    $menuItems[] = $item;
    if ($permKey) $addedPerms[] = $permKey;
  }
}

// 2. Add extra dynamic menus from system features if they have permission but it's not their native menu
// IT Admin uses a fixed sidebar — skip injecting backup/audit extras here (available via dashboard quick actions)
$itAdminDynamicSkip = ['backup_mgmt', 'audit_logs', 'user_mgmt', 'branch_mgmt', 'system_security'];
if ($role !== 'patient') {
  foreach ($systemFeatures as $permKey => $item) {
    if ($role === 'it_admin' && in_array($permKey, $itAdminDynamicSkip, true)) {
      continue;
    }
    if (!in_array($permKey, $addedPerms) && hasPermission($role, $permKey) > 0) {
      $menuItems[] = $item;
    }
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
  <?php require __DIR__ . '/partials/head_assets.php'; ?>
</head>

<body class="bg-stone-100 text-gray-900">

  <?php require __DIR__ . '/partials/skeleton_loader.php'; ?>

  <div id="app" v-cloak class="flex min-h-screen">

    <?php require __DIR__ . '/partials/toasts.php'; ?>

    <?php if ($isPatient): ?>
      <?php require __DIR__ . '/partials/patient_sidebar.php'; ?>

    <?php else: ?>
      <?php require __DIR__ . '/partials/staff_sidebar.php'; ?>
    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col transition-all duration-200"
      :style="isMobile ? { marginLeft: '0', paddingTop: '56px' } : { marginLeft: isOpen ? '275px' : '80px' }">
      <?php require __DIR__ . '/partials/topbar.php'; ?>

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
      <?php require __DIR__ . '/partials/settings_modal.php'; ?>


      <?php require __DIR__ . '/partials/drive_preview_modal.php'; ?>

      <?php require __DIR__ . '/partials/chat_widget.php'; ?>

    </div> <!-- End main content wrapper -->
  </div> <!-- End #app Vue container -->

  <?php require __DIR__ . '/partials/data_privacy_modal.php'; ?>
  <?php require __DIR__ . '/partials/scripts.php'; ?>
</body>

</html>