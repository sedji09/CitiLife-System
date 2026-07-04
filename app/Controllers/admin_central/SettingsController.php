<?php
namespace App\Controllers\admin_central;

class SettingsController
{
    public function handle()
    {
        global $pdo;

// Handles logic for the Settings Page
// Accessible by admin_central

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for post_max_size exceeded
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "The uploaded file is too large. Please upload a smaller logo (under " . ini_get('upload_max_filesize') . ").";
    }

    // Helper to save setting
    $saveSetting = function($key, $val, $category = 'General') use ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$val, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, category) VALUES (?, ?, ?)");
            $stmt->execute([$key, $val, $category]);
        }
    };

    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $systemName = trim($_POST['system_name'] ?? '');
        
        // Update System Name
        if (!empty($systemName)) {
            $saveSetting('system_name', $systemName);
        }

        // Handle System Status Settings
        $systemStatus = $_POST['system_status'] ?? 'open';
        $closedBranches = $_POST['closed_branches'] ?? [];
        $closedMessage = trim($_POST['closed_message'] ?? '');

        // If 'all' is in the array, simplify it to just 'all'
        if (in_array('all', $closedBranches)) {
            $closedBranchesStr = 'all';
        } else {
            $closedBranchesStr = implode(',', $closedBranches);
        }

        $saveSetting('system_status', $systemStatus);
        $saveSetting('closed_branches', $closedBranchesStr);
        $saveSetting('closed_message', $closedMessage);

        // Handle Logo Upload
        if (isset($_FILES['clinic_logo'])) {
            if ($_FILES['clinic_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../public/assets/img/logo/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileInfo = pathinfo($_FILES['clinic_logo']['name']);
                $extension = strtolower($fileInfo['extension']);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($extension, $allowedExtensions)) {
                    $filename = 'logo_' . time() . '.' . $extension;
                    $targetFile = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['clinic_logo']['tmp_name'], $targetFile)) {
                        $dbPath = "public/assets/img/logo/" . $filename;
                        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'clinic_logo'");
                        $stmt->execute([$dbPath]);
                    } else {
                        $error = "Failed to upload logo to server.";
                    }
                } else {
                    $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                }
            } elseif ($_FILES['clinic_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                // If there's an error other than NO_FILE, show it
                if ($_FILES['clinic_logo']['error'] === UPLOAD_ERR_INI_SIZE) {
                    $error = "The uploaded logo exceeds the maximum allowed file size (" . ini_get('upload_max_filesize') . ").";
                } else {
                    $error = "Error uploading logo (Code: " . $_FILES['clinic_logo']['error'] . ").";
                }
            }
        }
        
        if (empty($error)) {
            $success = "Settings updated successfully.";
            
            // Log this action
            require_once __DIR__ . '/../../Models/AuditLogModel.php';
            $auditLogModel = new \AuditLogModel($pdo);
            
            $logBranches = 'None';
            if (!empty($closedBranches)) {
                if (in_array('all', $closedBranches)) {
                    $logBranches = 'All Branches';
                } else {
                    $placeholders = implode(',', array_fill(0, count($closedBranches), '?'));
                    $stmt = $pdo->prepare("SELECT name FROM branches WHERE id IN ($placeholders)");
                    $stmt->execute($closedBranches);
                    $names = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    if ($names) {
                        $logBranches = implode(', ', $names);
                    } else {
                        $logBranches = $closedBranchesStr;
                    }
                }
            }
            
            $auditLogModel->addLog(
                $_SESSION['user_id'] ?? null,
                'Updated Global Settings',
                'Settings',
                'System Settings',
                null,
                "Updated system state to '$systemStatus'. Closed branches: $logBranches.",
                $_SESSION['branch_id'] ?? null
            );
        }
    }
}

// Fetch current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$dbSettings = [];
while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
    $dbSettings[$row['setting_key']] = $row['setting_value'];
}

$currentSystemName = $dbSettings['system_name'] ?? 'X-Ray Clinic Management System';
$currentLogo = $dbSettings['clinic_logo'] ?? '';
$systemStatus = $dbSettings['system_status'] ?? 'open';
$closedBranchesStr = $dbSettings['closed_branches'] ?? '';
$closedBranchesArr = ($closedBranchesStr === 'all' || $closedBranchesStr === '') ? [$closedBranchesStr] : explode(',', $closedBranchesStr);
$closedMessage = $dbSettings['closed_message'] ?? '';

// Fetch branches for settings view
$branchModel = new \BranchModel($pdo);
$branches = $branchModel->getAllBranches();

        return get_defined_vars();
    }
}
