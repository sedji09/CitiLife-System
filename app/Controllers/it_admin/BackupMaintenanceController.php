<?php

namespace App\Controllers\it_admin;

use App\Services\BackupService;

if (!function_exists('App\Controllers\it_admin\formatSize')) {
    function formatSize(int|float $bytes): string
    {
        return BackupService::formatSize($bytes);
    }
}

class BackupMaintenanceController
{
    public function handle()
    {
        global $pdo;

        /**
         * BackupMaintenanceController.php
         * IT Admin module for database backups, selective exports, database restoration, and trash/recycle bin.
         */
        $backupService = new BackupService($pdo);
        $adminId = $_SESSION['user_id'] ?? 0;

        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        $lastDeletedFile = $_SESSION['last_deleted_file'] ?? '';
        unset($_SESSION['success'], $_SESSION['error'], $_SESSION['last_deleted_file']);

        // 1. Handle Actions
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // Action: Run Automated Backup Now
            if ($action === 'trigger_auto_backup') {
                $result = $backupService->generateBackup('all', 'all', true, $adminId);
                $retention = $backupService->applyRetentionPolicy(60, 8);

                if ($result['success']) {
                    $_SESSION['success'] = "Automated backup completed: " . $result['filename'] . " (" . $result['filesize_formatted'] . "). " . $retention['message'];
                } else {
                    $_SESSION['error'] = "Automated backup failed: " . ($result['error'] ?? $result['message']);
                }

                redirect(url('backup-maintenance'));
            }

            // Action: Generate Backup (Full or Filtered)
            if ($action === 'generate_backup') {
                $tableFilter = trim($_POST['table_filter'] ?? 'all');
                $yearFilter = trim($_POST['year_filter'] ?? 'all');

                $result = $backupService->generateBackup($tableFilter, $yearFilter, false, $adminId);

                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                } else {
                    $_SESSION['error'] = "Backup failed: " . ($result['error'] ?? $result['message']);
                }

                redirect(url('backup-maintenance'));
            }

            // Action: Soft Delete Backup (Move to Trash / Recycle Bin)
            if ($action === 'delete_backup') {
                $file = basename($_POST['filename'] ?? '');
                if ($backupService->deleteBackup($file, $adminId)) {
                    $_SESSION['last_deleted_file'] = $file;
                    $_SESSION['success'] = "Backup moved to trash: $file";
                } else {
                    $_SESSION['error'] = "Failed to move backup to trash.";
                }
                redirect(url('backup-maintenance'));
            }

            // Action: Restore Deleted Backup File from Trash
            if ($action === 'restore_deleted_file') {
                $file = basename($_POST['filename'] ?? '');
                if ($backupService->restoreFromTrash($file, $adminId)) {
                    $_SESSION['success'] = "Backup restored to Backup History: $file";
                } else {
                    $_SESSION['error'] = "Failed to restore backup from trash.";
                }
                redirect(url('backup-maintenance'));
            }

            // Action: Purge Backup File Permanently
            if ($action === 'purge_file') {
                $file = basename($_POST['filename'] ?? '');
                if ($backupService->purgeFromTrash($file, $adminId)) {
                    $_SESSION['success'] = "Backup permanently deleted: $file";
                } else {
                    $_SESSION['error'] = "File not found in trash.";
                }
                redirect(url('backup-maintenance'));
            }

            // Action: Empty Trash
            if ($action === 'empty_trash') {
                $count = $backupService->emptyTrash($adminId);
                $_SESSION['success'] = "Trash emptied. $count backup files permanently removed.";
                redirect(url('backup-maintenance'));
            }

            // Action: Restore Database from Backup
            if ($action === 'restore_database') {
                $file = basename($_POST['filename'] ?? '');
                $res = $backupService->restoreDatabase($file, $adminId);
                if ($res['success']) {
                    $_SESSION['success'] = $res['message'];
                } else {
                    $_SESSION['error'] = $res['message'];
                }
                redirect(url('backup-maintenance'));
            }
        }

        // 2. Handle Secure Download
        if ($action === 'download_backup') {
            $file = basename($_GET['filename'] ?? '');
            $fullPath = realpath($backupService->getBackupDir() . $file);

            if ($fullPath && strpos($fullPath, realpath($backupService->getBackupDir())) === 0 && file_exists($fullPath)) {
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
                redirect(url('backup-maintenance'));
            }
        }

        // 3. Query Available Database Tables & Years
        $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $availableTables = $tablesStmt->fetchAll(\PDO::FETCH_COLUMN);
        sort($availableTables);

        $startYearQuery = "SELECT MIN(yr) FROM (
            SELECT MIN(YEAR(created_at)) as yr FROM cases WHERE created_at IS NOT NULL
            UNION SELECT MIN(YEAR(created_at)) as yr FROM audit_logs WHERE created_at IS NOT NULL
            UNION SELECT MIN(YEAR(created_at)) as yr FROM patients WHERE created_at IS NOT NULL
            UNION SELECT MIN(YEAR(created_at)) as yr FROM users WHERE created_at IS NOT NULL
        ) as t WHERE yr IS NOT NULL";
        try {
            $minYr = (int)$pdo->query($startYearQuery)->fetchColumn();
            $startYear = ($minYr && $minYr >= 2000) ? $minYr : 2026;
        } catch (\Exception $e) {
            $startYear = 2026;
        }

        $currentYear = (int)date('Y');
        $availableYears = [];
        for ($y = $currentYear; $y >= $startYear; $y--) {
            $availableYears[] = $y;
        }

        // 4. List Active Backups & Trash
        $backups = $backupService->listBackups();
        $trashBackups = $backupService->listTrash();
        $automationStatus = $backupService->getAutomationStatus();

        $envCronKey = getenv('CRON_SECRET_KEY');
        $cronSecretKey = !empty($_ENV['CRON_SECRET_KEY']) 
            ? (string)$_ENV['CRON_SECRET_KEY'] 
            : (!empty($_SERVER['CRON_SECRET_KEY']) 
                ? (string)$_SERVER['CRON_SECRET_KEY'] 
                : (($envCronKey !== false && $envCronKey !== '') ? (string)$envCronKey : 'citilife_secure_cron_2026'));
        $cronWebhookUrl = appBaseUrl() . url('api/cron/backup?key=' . urlencode($cronSecretKey));

        // Helper for view
        $formatSizeFn = [$this, 'formatSize'];

        return get_defined_vars();
    }

    /**
     * Format bytes into readable string (KB, MB, bytes)
     */
    public function formatSize(int|float $bytes): string
    {
        return BackupService::formatSize($bytes);
    }
}
