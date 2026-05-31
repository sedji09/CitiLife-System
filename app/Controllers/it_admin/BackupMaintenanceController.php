<?php
/**
 * BackupMaintenanceController.php
 * IT Admin module for database backups and system maintenance.
 */

require_once __DIR__ . '/../../Models/AuditLogModel.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';

$auditLogModel = new AuditLogModel($pdo);
$backupDir = __DIR__ . '/../../../storage/backups/';
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Ensure directory exists (migration already did this, but safe check)
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// 1. Handle Actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'generate_backup') {
        $filename = 'citilife_db_' . date('Y-m-d_H-i-s') . '.sql';
        $fullPath = $backupDir . $filename;
        
        // Use XAMPP mysqldump path
        $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';
        
        // Build command (using root with no password as per database.php)
        $command = "\"$mysqldumpPath\" -u root citilife_db > \"$fullPath\" 2>&1";
        
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $adminId = $_SESSION['user_id'] ?? 0;
            $auditLogModel->addLog($adminId, 'Generated DB Backup', 'IT Admin', 'Backup', 0, "Filename: $filename");
            $_SESSION['success'] = "Backup generated successfully: $filename";
        } else {
            $_SESSION['error'] = "Backup failed: " . implode("\n", $output);
        }
        header("Location: ?page=backup-maintenance");
        exit();
    }

    if ($action === 'delete_backup') {
        $file = $_POST['filename'] ?? '';
        $fullPath = realpath($backupDir . $file);

        // Security check: ensure file is within backupDir
        if ($fullPath && strpos($fullPath, realpath($backupDir)) === 0 && file_exists($fullPath)) {
            unlink($fullPath);
            $adminId = $_SESSION['user_id'] ?? 0;
            $auditLogModel->addLog($adminId, 'Deleted DB Backup', 'IT Admin', 'Backup', 0, "Filename: $file");
            $_SESSION['success'] = "Backup deleted: $file";
        } else {
            $_SESSION['error'] = "Invalid file or access denied.";
        }
        header("Location: ?page=backup-maintenance");
        exit();
    }
}

// 2. Handle Secure Download
if ($action === 'download_backup') {
    $file = $_GET['filename'] ?? '';
    $fullPath = realpath($backupDir . $file);

    if ($fullPath && strpos($fullPath, realpath($backupDir)) === 0 && file_exists($fullPath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit();
    } else {
        $_SESSION['error'] = "File not found.";
        header("Location: ?page=backup-maintenance");
        exit();
    }
}

// 3. List existing backups
$backups = [];
$files = scandir($backupDir);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..' && strpos($file, '.sql') !== false) {
        $backups[] = [
            'name' => $file,
            'size' => filesize($backupDir . $file),
            'date' => filemtime($backupDir . $file)
        ];
    }
}

// Sort by date descending
usort($backups, function($a, $b) {
    return $b['date'] - $a['date'];
});

// Helper for human-readable size
function formatSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}
