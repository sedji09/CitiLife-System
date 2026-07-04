<?php

namespace App\Controllers\it_admin;

class SecuritySettingsController
{
    public function handle()
    {
        global $pdo;


/**
 * SecuritySettingsController.php
 * IT Admin module for managing system security policies.
 */


$auditLogModel = new \AuditLogModel($pdo);
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// 1. Fetch current settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE category = 'Security'");
while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 2. Default fallbacks
$minPassLength = $settings['min_password_length'] ?? 8;
$autoLogoutMins = $settings['auto_logout_minutes'] ?? 30;

// 3. Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_security') {
    $newMinPass = intval($_POST['min_password_length'] ?? 8);
    $newAutoLogout = intval($_POST['auto_logout_minutes'] ?? 30);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$newMinPass, 'min_password_length']);
        $stmt->execute([$newAutoLogout, 'auto_logout_minutes']);

        // Log the change
        $adminId = $_SESSION['user_id'] ?? 0;
        $details = "Updated Security Policies: Min Password Length = $newMinPass, Auto-Logout = $newAutoLogout minutes.";
        $auditLogModel->addLog($adminId, 'Update Security Settings', 'Security Settings', 'System Settings', 0, $details);

        $pdo->commit();
        $_SESSION['success'] = "Security policies updated successfully.";
        header("Location: ?page=security-settings");
        exit();
    } catch (\Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to update settings: " . $e->getMessage();
        header("Location: ?page=security-settings");
        exit();
    }
}

        return get_defined_vars();
    }
}
