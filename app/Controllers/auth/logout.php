<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? null;

$userId = $_SESSION['user_id'] ?? null;
$branchId = $_SESSION['branch_id'] ?? null;

if ($userId) {
    // Clear last_activity immediately so they don't appear in Active Users
    $stmt = $pdo->prepare("UPDATE users SET last_activity = NULL WHERE id = ?");
    $stmt->execute([$userId]);

    require_once basePath('app/Models/AuditLogModel.php');
    $auditLogModel = new \AuditLogModel($pdo);
    $auditLogModel->addLog(
        $userId,
        $role === 'patient' ? 'Patient Logout' : 'Staff Logout',
        'Authentication',
        'Session',
        $userId,
        "User logged out",
        $branchId
    );
}

// Destroy all session data
$_SESSION = [];
session_destroy();

// Optional: delete the session cookie for extra safety
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to appropriate login page based on role
$reason = $_GET['reason'] ?? '';
$redirect = "/" . PROJECT_DIR . ($role === 'patient' ? "/patient-login" : "/login");
if ($reason) {
    $redirect .= "?reason=" . urlencode($reason);
}
header("Location: " . $redirect);
exit;
