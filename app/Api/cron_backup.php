<?php

/**
 * CitiLife Automated Cron Backup API Endpoint
 * 
 * Invoked by external cron pingers (cron-job.org, Railway CRON, GitHub Actions)
 * Protected by secret key validation.
 */

header('Content-Type: application/json; charset=UTF-8');

// Load environment and framework helpers
if (file_exists(__DIR__ . '/../../env.php')) {
    require_once __DIR__ . '/../../env.php';
}
if (file_exists(__DIR__ . '/../../helpers.php')) {
    require_once __DIR__ . '/../../helpers.php';
}
if (file_exists(__DIR__ . '/../../config/database.php')) {
    require_once __DIR__ . '/../../config/database.php';
}

use App\Services\BackupService;

// Determine authorized key safely
$envCronKey = getenv('CRON_SECRET_KEY');
$configuredKey = !empty($_ENV['CRON_SECRET_KEY']) 
    ? (string)$_ENV['CRON_SECRET_KEY'] 
    : (!empty($_SERVER['CRON_SECRET_KEY']) 
        ? (string)$_SERVER['CRON_SECRET_KEY'] 
        : (($envCronKey !== false && $envCronKey !== '') ? (string)$envCronKey : 'citilife_secure_cron_2026'));

// Read provided key from GET, POST, or X-Cron-Key header
$providedKey = (string)($_GET['key'] 
    ?? $_POST['key'] 
    ?? $_SERVER['HTTP_X_CRON_KEY'] 
    ?? '');

if (empty($providedKey) || !hash_equals($configuredKey, $providedKey)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized: Invalid or missing cron secret key.',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    global $pdo;
    $backupService = new BackupService($pdo);

    // 1. Generate Automated Full Database Snapshot
    $backupResult = $backupService->generateBackup('all', 'all', true, 0);

    if (!$backupResult['success']) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $backupResult['error'] ?? 'Backup creation failed.',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // 2. Apply Retention Policy (keep last 8 backups, purge >60 days old)
    $retentionResult = $backupService->applyRetentionPolicy(60, 8);

    // 3. Get updated automation status
    $status = $backupService->getAutomationStatus();

    echo json_encode([
        'success' => true,
        'message' => 'Automated weekly backup executed successfully.',
        'timestamp' => date('Y-m-d H:i:s'),
        'backup' => [
            'filename' => $backupResult['filename'],
            'size' => $backupResult['filesize_formatted'],
            'size_bytes' => $backupResult['filesize']
        ],
        'retention' => [
            'checked_count' => $retentionResult['checked_count'],
            'purged_count' => $retentionResult['purged_count'],
            'purged_files' => $retentionResult['purged_files'] ?? []
        ],
        'next_scheduled_run' => $status['next_scheduled_run_formatted'],
        'total_active_backups' => $status['total_backups']
    ], JSON_PRETTY_PRINT);
    exit;

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}
