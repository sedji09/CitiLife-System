<?php
/**
 * SettingsController.php
 * Handles system settings for Admin Central
 */

// Initialize variables
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $systemName = trim($_POST['system_name'] ?? '');
        
        // Update System Name
        if (!empty($systemName)) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'system_name'");
            $stmt->execute([$systemName]);
        }
        
        // Handle Logo Upload
        if (isset($_FILES['clinic_logo']) && $_FILES['clinic_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/assets/img/logo/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileInfo = pathinfo($_FILES['clinic_logo']['name']);
            $extension = strtolower($fileInfo['extension']);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($extension, $allowedExtensions)) {
                // Generate a unique name
                $filename = 'logo_' . time() . '.' . $extension;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['clinic_logo']['tmp_name'], $targetFile)) {
                    // Save relative path to DB
                    $dbPath = "public/assets/img/logo/" . $filename;
                    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'clinic_logo'");
                    $stmt->execute([$dbPath]);
                } else {
                    $error = "Failed to upload logo.";
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
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
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dbSettings[$row['setting_key']] = $row['setting_value'];
}

$currentSystemName = $dbSettings['system_name'] ?? 'X-Ray Clinic Management System';
$currentLogo = $dbSettings['clinic_logo'] ?? '';

// End of Controller. View is loaded after this by index.php
