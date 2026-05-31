<?php
require_once __DIR__ . '/../../Models/UserModel.php';
require_once __DIR__ . '/../../Models/PatientModel.php';
require_once __DIR__ . '/../../Models/CaseModel.php';

$userModel = new UserModel($pdo);
$patientModel = new PatientModel($pdo);
$caseModel = new CaseModel($pdo);

// Fetch Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn();
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$totalCases = $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn();

// System Information
$phpVersion = PHP_VERSION;
$mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn();

// Disk Usage (Simplified for Localhost)
$diskTotal = disk_total_space("C:");
$diskFree = disk_free_space("C:");
$diskUsed = $diskTotal - $diskFree;
$diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 2);

// Format bytes to human readable
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

$formattedDiskUsed = formatBytes($diskUsed);
$formattedDiskTotal = formatBytes($diskTotal);

// Simulate Uptime (since it's XAMPP/Windows)
// We can use a session start time or just a static "99.9%" status for aesthetic
$uptime = "Stable"; 
$serverTime = date('Y-m-d H:i:s');

// Maintenance Info
$latestBackup = "Never";
$backupDir = __DIR__ . '/../../../backups/';
if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (strpos($file, '.sql') !== false) {
            $latestBackup = date("F d, Y H:i", filemtime($backupDir . $file));
            break;
        }
    }
}
// Recent Audit Logs
$recentLogs = $pdo->query("
    SELECT al.*, u.name as user_name 
    FROM audit_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
