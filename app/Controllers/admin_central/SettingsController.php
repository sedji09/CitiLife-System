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

    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $systemName = trim($_POST['system_name'] ?? '');
        
        // Update System Name
        if (!empty($systemName)) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'system_name'");
            $stmt->execute([$systemName]);
        }

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

        return get_defined_vars();
    }
}
