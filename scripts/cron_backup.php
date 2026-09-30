<?php

/**
 * CLI Backup Runner
 * 
 * Usage:
 * php scripts/cron_backup.php
 * php scripts/cron_backup.php --force
 */

if (php_sapi_name() !== 'cli') {
    echo "This script can only be run via CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\BackupService;

echo "=========================================================\n";
echo "CitiLife Automated Database Backup CLI\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "=========================================================\n\n";

try {
    global $pdo;
    $service = new BackupService($pdo);

    echo "[1/3] Generating full database snapshot...\n";
    $result = $service->generateBackup('all', 'all', true, 0);

    if ($result['success']) {
        echo "  [SUCCESS] Backup created: " . $result['filename'] . " (" . $result['filesize_formatted'] . ")\n";
    } else {
        echo "  [FAILED] Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }

    echo "\n[2/3] Applying retention policy (60 days max, min 8 backups)...\n";
    $retention = $service->applyRetentionPolicy(60, 8);
    echo "  [INFO] " . $retention['message'] . "\n";

    echo "\n[3/4] Dispatching in-app notifications and email alerts...\n";
    $service->notifyAdminsOfAutoBackup($result, $retention);
    echo "  [INFO] Notification alerts queued for IT Administrators.\n";

    echo "\n[4/4] Checking backup status...\n";
    $status = $service->getAutomationStatus();
    echo "  Total Backups Stored: " . $status['total_backups'] . "\n";
    echo "  Next Scheduled Run:   " . $status['next_scheduled_run_formatted'] . "\n\n";

    echo "=========================================================\n";
    echo "Backup completed successfully.\n";
    echo "=========================================================\n";
    exit(0);

} catch (\Throwable $e) {
    echo "\n[CRITICAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
