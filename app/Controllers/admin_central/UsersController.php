<?php

namespace App\Controllers\admin_central;

class UsersController
{
    public function handle()
    {
        global $pdo;



$userModel = new \UserModel($pdo);
$branchModel = new \BranchModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
$currentAdminId = $_SESSION['user_id'] ?? 0;
$currentBranchId = $_SESSION['branch_id'] ?? null;

// Fetch min password length setting
$minPassLength = 8; // Default
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'min_password_length'");
    $val = $stmt->fetchColumn();
    if ($val) $minPassLength = intval($val);
} catch (\Exception $e) {}

// One-time check for database column support for 'Inactive' status
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN status ENUM('Pending', 'Active', 'Rejected', 'Inactive') DEFAULT 'Active'");
} catch (\Exception $e) {
    // Silently fail if already updated or if user doesn't have permissions
}

$success = '';
$error = '';

// Handle AJAX/POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $inputRole = $_POST['role'] ?? '';
        $branchId = $_POST['branch_id'] ?? null;

        if (in_array($inputRole, ['it_admin', 'admin_central', 'radiologist'])) {
            $branchId = null;
        }

        if (empty($email) || empty($inputRole)) {
            $error = "All required fields must be filled out.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please provide a valid email address.";
        } else {
            if ($userModel->getUserByEmail($email)) {
                $error = "The email '" . htmlspecialchars($email) . "' is already registered.";
            } else {
                // Generate a cryptographically secure 7-day invitation/activation token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
                $tempPassword = bin2hex(random_bytes(16)); // Secure placeholder until user sets password

                if ($userModel->createStaffUser($email, $tempPassword, $inputRole, $branchId, $name, $token, $expiresAt, 'Pending')) {
                    $newUserId = $pdo->lastInsertId();
                    $success = "Staff account created! An activation link has been sent to " . htmlspecialchars($email) . ".";
                    $auditLogModel->addLog($currentAdminId, "Created $inputRole account (Pending Activation): $email", 'User Management', 'User', $newUserId, "Email: $email, Role: $inputRole, Branch: $branchId", $currentBranchId);
                    
                    require_once __DIR__ . '/../../Helpers/mailer_helper.php';
                    $subject = "Activate Your Staff Account - Citilife Diagnostic Center";
                    $roleName = ucwords(str_replace('_', ' ', $inputRole));
                    $greeting = !empty($name) ? htmlspecialchars($name) : "Staff Member";

                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $baseDir = PROJECT_DIR ? '/' . PROJECT_DIR : '';
                    $activationUrl = "{$scheme}://{$host}{$baseDir}/set-password?token=" . urlencode($token);

                    $details = [
                        'Role' => htmlspecialchars($roleName),
                        'Email / Username' => htmlspecialchars($email),
                    ];
                    if (!empty($branchId)) {
                        $branch = $branchModel->getBranchById($branchId);
                        if ($branch) {
                            $details['Assigned Branch'] = htmlspecialchars($branch['name']);
                        }
                    }
                    $details['Link Expiration'] = '7 Days';

                    $body = renderNotificationEmail(
                        $greeting,
                        "Welcome to Citilife Diagnostic Center",
                        "A new staff account has been provisioned for you with the role of <strong>{$roleName}</strong>. To get started, please click the button below to set your password and activate your account:",
                        $details,
                        "Activate Account & Set Password",
                        $activationUrl,
                        "This invitation link will expire in 7 days. If you did not expect this invitation, please contact your administrator.",
                        "#dc2626"
                    );
                    sendEmail($email, $greeting, $subject, $body);
                } else {
                    $error = "Failed to create user account.";
                }
            }
        }
    }

    if ($action === 'resend_invite') {
        $userId = $_POST['user_id'] ?? null;
        if ($userId) {
            $user = $userModel->getUserById($userId);
            if ($user && $user['status'] === 'Pending') {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

                $stmtUpdate = $pdo->prepare("UPDATE users SET reset_password_token = ?, reset_password_expires_at = ? WHERE id = ?");
                $stmtUpdate->execute([$token, $expiresAt, $userId]);

                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseDir = PROJECT_DIR ? '/' . PROJECT_DIR : '';
                $activationUrl = "{$scheme}://{$host}{$baseDir}/set-password?token=" . urlencode($token);

                require_once __DIR__ . '/../../Helpers/mailer_helper.php';
                $roleName = ucwords(str_replace('_', ' ', $user['role']));
                $greeting = !empty($user['name']) ? htmlspecialchars($user['name']) : "Staff Member";
                $subject = "Account Activation Reminder - Citilife Diagnostic Center";

                $details = [
                    'Role' => htmlspecialchars($roleName),
                    'Email / Username' => htmlspecialchars($user['email']),
                    'Link Expiration' => '7 Days'
                ];

                $body = renderNotificationEmail(
                    $greeting,
                    "Activate Your Citilife Staff Account",
                    "Here is your new activation link for your <strong>{$roleName}</strong> account. Please click the button below to set your password:",
                    $details,
                    "Activate Account & Set Password",
                    $activationUrl,
                    "This invitation link will expire in 7 days. If you have already activated your account, you can disregard this email.",
                    "#dc2626"
                );
                sendEmail($user['email'], $greeting, $subject, $body);

                $success = "A new activation link has been emailed to " . htmlspecialchars($user['email']) . ".";
                $auditLogModel->addLog($currentAdminId, "Resent activation invite: " . $user['email'], 'User Management', 'User', $userId, "Email: " . $user['email'], $currentBranchId);
            } else {
                $error = "User not found or account is already active.";
            }
        }
    }

    if ($action === 'delete') {
        $userId = $_POST['user_id'] ?? null;
        $currentAdminId = $_SESSION['user_id'] ?? 0;
        $targetUser = $userId ? $userModel->getUserById($userId) : null;

        if ($targetUser && $targetUser['role'] === 'admin_central') {
            $error = "Central Admin accounts cannot be deleted.";
        } else if ($userId == $currentAdminId) {
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
        $name = trim($_POST['name'] ?? '');
        $inputRole = $_POST['role'] ?? '';
        $branchId = $_POST['branch_id'] ?? null;

        if (in_array($inputRole, ['it_admin', 'admin_central', 'radiologist'])) {
            $branchId = null;
        }

        if ($userId && !empty($email) && !empty($inputRole)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please include an '@' in the email address. '" . htmlspecialchars($email) . "' is missing an '@'.";
            } else {
                $existing = $userModel->getUserByEmail($email);
                if ($existing && $existing['id'] != $userId) {
                    $error = "The email '" . htmlspecialchars($email) . "' is already taken by another account.";
                } else if ($password && strlen($password) < $minPassLength) {
                    $error = "The new password must be at least $minPassLength characters long.";
                } else if ($userModel->updateStaffUser($userId, $email, $inputRole, $branchId, $password, $name)) {
                    $success = "User account updated successfully!";
                    $details = "Updated user $email (Role: $inputRole)";
                    $auditLogModel->addLog($currentAdminId, "Updated staff account details", 'User Management', 'User', $userId, $details, $currentBranchId);
                } else {
                    $error = "Failed to update user account.";
                }
            }
        }
    }

    if ($action === 'toggle-status') {
        $userId = $_POST['user_id'] ?? null;
        $newStatus = $_POST['new_status'] ?? 'Active';
        $currentAdminId = $_SESSION['user_id'] ?? 0;

        $targetUser = $userId ? $userModel->getUserById($userId) : null;

        if ($targetUser && $targetUser['role'] === 'admin_central' && $newStatus === 'Inactive') {
            $error = "Central Admin accounts cannot be deactivated.";
        } else if ($userId == $currentAdminId && $newStatus === 'Inactive') {
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

        return get_defined_vars();
    }
}
