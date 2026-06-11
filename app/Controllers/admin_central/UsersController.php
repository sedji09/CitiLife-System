<?php
require_once __DIR__ . '/../../Models/UserModel.php';
require_once __DIR__ . '/../../Models/BranchModel.php';
require_once __DIR__ . '/../../Models/AuditLogModel.php';

$userModel = new UserModel($pdo);
$branchModel = new BranchModel($pdo);
$auditLogModel = new AuditLogModel($pdo);
$currentAdminId = $_SESSION['user_id'] ?? 0;
$currentBranchId = $_SESSION['branch_id'] ?? null;

// Fetch min password length setting
$minPassLength = 8; // Default
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'min_password_length'");
    $val = $stmt->fetchColumn();
    if ($val) $minPassLength = intval($val);
} catch (Exception $e) {}

// One-time check for database column support for 'Inactive' status
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN status ENUM('Pending', 'Active', 'Rejected', 'Inactive') DEFAULT 'Active'");
} catch (Exception $e) {
    // Silently fail if already updated or if user doesn't have permissions
}

$success = '';
$error = '';

// Handle AJAX/POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $inputRole = $_POST['role'] ?? '';
        $branchId = $_POST['branch_id'] ?? null;

        if (empty($email) || empty($password) || empty($inputRole)) {
            $error = "All fields are required.";
        } else {
            if ($userModel->getUserByEmail($email)) {
                $error = "The email '" . htmlspecialchars($email) . "' is already registered.";
            } else if (strlen($password) < $minPassLength) {
                $error = "Password must be at least $minPassLength characters long.";
            } else if ($userModel->createStaffUser($email, $password, $inputRole, $branchId)) {
                $success = "User account created successfully!";
                $auditLogModel->addLog($currentAdminId, "Created $inputRole account: $email", 'User Management', 'User', $pdo->lastInsertId(), "Email: $email, Role: $inputRole, Branch: $branchId", $currentBranchId);
            } else {
                $error = "Failed to create user account.";
            }
        }
    }

    if ($action === 'delete') {
        $userId = $_POST['user_id'] ?? null;
        $currentAdminId = $_SESSION['user_id'] ?? 0;

        if ($userId == $currentAdminId) {
            $error = "You cannot delete your own account.";
        } else if ($userId && $userModel->deleteStaffUser($userId)) {
            $success = "User account deleted successfully.";
            $auditLogModel->addLog($currentAdminId, "Deleted staff user account", 'User Management', 'User', $userId, "Deleted user ID: $userId", $currentBranchId);
        } else {
            $error = "Failed to delete user account.";
        }
    }

    if ($action === 'update') {
        $userId = $_POST['user_id'] ?? null;
        $email = trim($_POST['email'] ?? '');
        $inputRole = $_POST['role'] ?? '';
        $branchId = $_POST['branch_id'] ?? null;
        $password = $_POST['password'] ?? null;

        if ($userId && !empty($email) && !empty($inputRole)) {
            $existing = $userModel->getUserByEmail($email);
            if ($existing && $existing['id'] != $userId) {
                $error = "The email '" . htmlspecialchars($email) . "' is already taken by another account.";
            } else if ($password && strlen($password) < $minPassLength) {
                $error = "The new password must be at least $minPassLength characters long.";
            } else if ($userModel->updateStaffUser($userId, $email, $inputRole, $branchId, $password)) {
                $success = "User account updated successfully!";
                $details = "Updated user $email (Role: $inputRole)";
                if ($password) $details .= " - Password reset performed.";
                $auditLogModel->addLog($currentAdminId, "Updated staff account details", 'User Management', 'User', $userId, $details, $currentBranchId);
            } else {
                $error = "Failed to update user account.";
            }
        }
    }

    if ($action === 'toggle-status') {
        $userId = $_POST['user_id'] ?? null;
        $newStatus = $_POST['new_status'] ?? 'Active';
        $currentAdminId = $_SESSION['user_id'] ?? 0;

        if ($userId == $currentAdminId && $newStatus === 'Inactive') {
            $error = "You cannot deactivate your own account.";
        } else if ($userId && $userModel->updateUserStatus($userId, $newStatus)) {
            $success = "User status updated to " . htmlspecialchars($newStatus) . "!";
            $auditLogModel->addLog($currentAdminId, "User status changed to $newStatus", 'User Management', 'User', $userId, "New status: $newStatus", $currentBranchId);
        } else {
            $error = "Failed to update user status.";
        }
    }
}

// Fetch all staff users
$users = $userModel->getAllStaffUsers();
$branches = $branchModel->getAllBranches();
