<?php

namespace App\Controllers\it_admin;

if (!function_exists('App\Controllers\it_admin\formatSize')) {
    function formatSize(int|float $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
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

        $auditLogModel = new \AuditLogModel($pdo);
        $backupDir = __DIR__ . '/../../../storage/backups/';
        $trashDir = __DIR__ . '/../../../storage/backups/trash/';
        
        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        $lastDeletedFile = $_SESSION['last_deleted_file'] ?? '';
        unset($_SESSION['success'], $_SESSION['error'], $_SESSION['last_deleted_file']);

        // Ensure directories exist
        if (!file_exists($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        if (!file_exists($trashDir)) {
            @mkdir($trashDir, 0755, true);
        }

        // 1. Handle Actions
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // Action: Generate Backup (Full or Filtered)
            if ($action === 'generate_backup') {
                $tableFilter = trim($_POST['table_filter'] ?? 'all');
                $tableFilter = preg_replace('/[^a-zA-Z0-9_]/', '', $tableFilter);
                if (empty($tableFilter)) {
                    $tableFilter = 'all';
                }

                $yearFilter = trim($_POST['year_filter'] ?? 'all');
                if ($yearFilter !== 'all') {
                    $yearFilter = (int)$yearFilter;
                    if ($yearFilter < 1900 || $yearFilter > 2100) {
                        $yearFilter = 'all';
                    }
                }

                $timestamp = date('Y-m-d_H-i-s');
                if ($tableFilter === 'all' && $yearFilter === 'all') {
                    $filename = 'citilife_full_' . $timestamp . '.sql';
                } elseif ($tableFilter !== 'all' && $yearFilter === 'all') {
                    $filename = 'citilife_' . $tableFilter . '_all_years_' . $timestamp . '.sql';
                } elseif ($tableFilter === 'all' && $yearFilter !== 'all') {
                    $filename = 'citilife_all_tables_' . $yearFilter . '_' . $timestamp . '.sql';
                } else {
                    $filename = 'citilife_' . $tableFilter . '_' . $yearFilter . '_' . $timestamp . '.sql';
                }

                $fullPath = $backupDir . $filename;
                $backupSuccess = false;
                $lastError = '';

                // Attempt 1: For full, non-filtered backups, try mysqldump binary if available
                if ($tableFilter === 'all' && $yearFilter === 'all') {
                    $dbConfig = require __DIR__ . '/../../../config/db.php';
                    $mysqldumpBin = $this->findMysqldumpBinary();

                    if ($mysqldumpBin && function_exists('exec')) {
                        $host = $dbConfig['host'] ?? '127.0.0.1';
                        $port = $dbConfig['port'] ?? '3306';
                        $dbname = $dbConfig['dbname'] ?? 'citilife_db';
                        $username = $dbConfig['username'] ?? 'root';
                        $password = $dbConfig['password'] ?? '';

                        $passFlag = $password !== '' ? " -p" . escapeshellarg($password) : "";
                        $cmd = escapeshellcmd($mysqldumpBin) 
                            . " -h " . escapeshellarg($host) 
                            . " -P " . escapeshellarg($port) 
                            . " -u " . escapeshellarg($username) 
                            . $passFlag 
                            . " " . escapeshellarg($dbname) 
                            . " > " . escapeshellarg($fullPath) . " 2>&1";

                        @exec($cmd, $output, $returnVar);

                        if ($returnVar === 0 && file_exists($fullPath) && filesize($fullPath) > 200) {
                            $backupSuccess = true;
                        } else {
                            $lastError = !empty($output) ? implode("\n", $output) : 'mysqldump command failed';
                            if (file_exists($fullPath)) {
                                @unlink($fullPath);
                            }
                        }
                    }
                }

                // Attempt 2: Universal PHP PDO Exporter
                if (!$backupSuccess) {
                    try {
                        $backupSuccess = $this->exportDatabaseViaPdo($pdo, $fullPath, $tableFilter, $yearFilter);
                    } catch (\Exception $e) {
                        $backupSuccess = false;
                        $lastError = $e->getMessage();
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                }

                if ($backupSuccess && file_exists($fullPath) && filesize($fullPath) > 50) {
                    $fileSizeFormatted = $this->formatSize(filesize($fullPath));
                    $adminId = $_SESSION['user_id'] ?? 0;
                    $logDetails = "Filename: {$filename} ({$fileSizeFormatted}) | Table: {$tableFilter} | Year: {$yearFilter}";
                    $auditLogModel->addLog($adminId, 'Generated DB Backup', 'System', 'Backup', 0, $logDetails);
                    $_SESSION['success'] = "Backup generated successfully: {$filename} ({$fileSizeFormatted})";
                } else {
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                    $_SESSION['error'] = "Backup failed: " . ($lastError ?: "Unable to export database backup.");
                }

                redirect(url('backup-maintenance'));
            }

            // Action: Soft Delete Backup (Move to Trash / Recycle Bin)
            if ($action === 'delete_backup') {
                $file = basename($_POST['filename'] ?? '');
                $fullPath = realpath($backupDir . $file);

                if ($fullPath && strpos($fullPath, realpath($backupDir)) === 0 && file_exists($fullPath)) {
                    $trashPath = $trashDir . $file;
                    if (@rename($fullPath, $trashPath)) {
                        $adminId = $_SESSION['user_id'] ?? 0;
                        $auditLogModel->addLog($adminId, 'Moved DB Backup to Trash', 'System', 'Backup', 0, "Filename: $file");
                        $_SESSION['last_deleted_file'] = $file;
                        $_SESSION['success'] = "Backup moved to trash: $file";
                    } else {
                        $_SESSION['error'] = "Failed to move backup to trash.";
                    }
                } else {
                    $_SESSION['error'] = "Invalid file or access denied.";
                }
                redirect(url('backup-maintenance'));
            }

            // Action: Restore Deleted Backup File from Trash
            if ($action === 'restore_deleted_file') {
                $file = basename($_POST['filename'] ?? '');
                $trashPath = realpath($trashDir . $file);

                if ($trashPath && strpos($trashPath, realpath($trashDir)) === 0 && file_exists($trashPath)) {
                    $restorePath = $backupDir . $file;
                    if (@rename($trashPath, $restorePath)) {
                        $adminId = $_SESSION['user_id'] ?? 0;
                        $auditLogModel->addLog($adminId, 'Restored DB Backup from Trash', 'System', 'Backup', 0, "Filename: $file");
                        $_SESSION['success'] = "Backup restored to Backup History: $file";
                    } else {
                        $_SESSION['error'] = "Failed to restore backup from trash.";
                    }
                } else {
                    $_SESSION['error'] = "Backup file not found in trash.";
                }
                redirect(url('backup-maintenance'));
            }

            // Action: Purge Backup File Permanently
            if ($action === 'purge_file') {
                $file = basename($_POST['filename'] ?? '');
                $trashPath = realpath($trashDir . $file);

                if ($trashPath && strpos($trashPath, realpath($trashDir)) === 0 && file_exists($trashPath)) {
                    @unlink($trashPath);
                    $adminId = $_SESSION['user_id'] ?? 0;
                    $auditLogModel->addLog($adminId, 'Permanently Deleted Backup File', 'System', 'Backup', 0, "Filename: $file");
                    $_SESSION['success'] = "Backup permanently deleted: $file";
                } else {
                    $_SESSION['error'] = "File not found in trash.";
                }
                redirect(url('backup-maintenance'));
            }

            // Action: Empty Trash
            if ($action === 'empty_trash') {
                $count = 0;
                $tFiles = scandir($trashDir);
                foreach ($tFiles as $tf) {
                    if ($tf !== '.' && $tf !== '..' && str_ends_with($tf, '.sql')) {
                        @unlink($trashDir . $tf);
                        $count++;
                    }
                }
                $adminId = $_SESSION['user_id'] ?? 0;
                $auditLogModel->addLog($adminId, 'Emptied Backup Trash', 'System', 'Backup', 0, "Purged $count files");
                $_SESSION['success'] = "Trash emptied. $count backup files permanently removed.";
                redirect(url('backup-maintenance'));
            }

            // Action: Restore Database from Backup
            if ($action === 'restore_database') {
                $file = basename($_POST['filename'] ?? '');
                $fullPath = realpath($backupDir . $file);

                if ($fullPath && strpos($fullPath, realpath($backupDir)) === 0 && file_exists($fullPath)) {
                    try {
                        $this->importSqlFile($pdo, $fullPath);
                        $adminId = $_SESSION['user_id'] ?? 0;
                        $auditLogModel->addLog($adminId, 'Restored Database from Backup', 'System', 'Backup', 0, "Filename: $file");
                        $_SESSION['success'] = "Database successfully restored from backup: $file";
                    } catch (\Exception $e) {
                        $_SESSION['error'] = "Database restoration failed: " . $e->getMessage();
                    }
                } else {
                    $_SESSION['error'] = "Backup file not found.";
                }
                redirect(url('backup-maintenance'));
            }
        }

        // 2. Handle Secure Download
        if ($action === 'download_backup') {
            $file = basename($_GET['filename'] ?? '');
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

        // 4. List Active Backups in Snapshot History
        $backups = [];
        $files = scandir($backupDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && str_ends_with($file, '.sql')) {
                $filePath = $backupDir . $file;
                $size = filesize($filePath);
                
                // Clean up corrupted zero-byte files
                if ($size < 100) {
                    $content = @file_get_contents($filePath);
                    if (stripos($content, 'not found') !== false || stripos($content, 'sh: ') !== false || stripos($content, 'error') !== false) {
                        @unlink($filePath);
                        continue;
                    }
                }

                $meta = $this->parseBackupMetadata($file);

                $backups[] = [
                    'name' => $file,
                    'size' => $size,
                    'date' => filemtime($filePath),
                    'table' => $meta['table'],
                    'year' => $meta['year'],
                    'type' => $meta['type']
                ];
            }
        }

        usort($backups, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        // 5. List Deleted Backups in Trash / Recycle Bin
        $trashBackups = [];
        $tFiles = scandir($trashDir);
        foreach ($tFiles as $file) {
            if ($file !== '.' && $file !== '..' && str_ends_with($file, '.sql')) {
                $filePath = $trashDir . $file;
                $trashBackups[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'date' => filemtime($filePath)
                ];
            }
        }

        usort($trashBackups, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        // Helper for view
        $formatSizeFn = [$this, 'formatSize'];

        return get_defined_vars();
    }

    /**
     * Format bytes into readable string (KB, MB, bytes)
     */
    public function formatSize(int|float $bytes): string
    {
        return formatSize($bytes);
    }

    /**
     * Parse metadata from backup filenames
     */
    public function parseBackupMetadata(string $filename): array
    {
        $meta = [
            'table' => 'All Tables',
            'year' => 'All Years',
            'type' => 'Full Database'
        ];

        if (preg_match('/^citilife_([a-zA-Z0-9_-]+)_(all_years|all-years|[0-9]{4})_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename, $m)) {
            $tbl = $m[1];
            $yr = ($m[2] === 'all_years' || $m[2] === 'all-years') ? 'All Years' : $m[2];

            if ($tbl === 'all_tables' || $tbl === 'all-tables' || $tbl === 'all') {
                $meta['table'] = 'All Tables';
                $meta['year'] = $yr;
                $meta['type'] = ($yr === 'All Years') ? 'Full Database' : 'Year Filtered';
            } else {
                $meta['table'] = $tbl;
                $meta['year'] = $yr;
                $meta['type'] = ($yr === 'All Years') ? 'Table Filtered' : 'Table & Year Filtered';
            }
        } elseif (preg_match('/^citilife_full_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename) ||
                  preg_match('/^citilife_db_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
            $meta['table'] = 'All Tables';
            $meta['year'] = 'All Years';
            $meta['type'] = 'Full Database';
        }

        return $meta;
    }

    /**
     * Restore database by streaming SQL statements into PDO
     */
    private function importSqlFile(\PDO $pdo, string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new \Exception("SQL file not found.");
        }

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $fp = @fopen($filePath, 'r');
        if (!$fp) {
            throw new \Exception("Cannot open SQL file for reading.");
        }

        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $pdo->exec("SET NAMES utf8mb4;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

        $query = '';
        $inBlockComment = false;

        while (($line = fgets($fp)) !== false) {
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            if (str_starts_with($trimmed, '/*')) {
                if (!str_contains($trimmed, '*/')) {
                    $inBlockComment = true;
                    continue;
                }
                if (!str_ends_with($trimmed, ';')) {
                    continue;
                }
            }
            if ($inBlockComment) {
                if (str_contains($trimmed, '*/')) {
                    $inBlockComment = false;
                }
                continue;
            }

            $query .= $line;

            if (str_ends_with($trimmed, ';')) {
                try {
                    $stmt = $pdo->prepare($query);
                    if ($stmt) {
                        $stmt->execute();
                        $stmt->closeCursor();
                    }
                } catch (\PDOException $e) {
                    $errMsg = $e->getMessage();
                    if (!str_contains($errMsg, 'Unknown table') && !str_contains($errMsg, 'already exists')) {
                        // non-fatal
                    }
                }
                $query = '';
            }
        }

        if (!empty(trim($query))) {
            try {
                $stmt = $pdo->prepare($query);
                if ($stmt) {
                    $stmt->execute();
                    $stmt->closeCursor();
                }
            } catch (\Exception $e) {
                // non-fatal
            }
        }

        fclose($fp);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        return true;
    }

    /**
     * Locate mysqldump binary across Windows, Linux, and cloud environments
     */
    private function findMysqldumpBinary(): ?string
    {
        $candidates = [
            'mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB 10.5\\bin\\mysqldump.exe'
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Try 'which mysqldump' on Unix/Linux
        if (DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec')) {
            $which = trim((string) @shell_exec('which mysqldump 2>/dev/null'));
            if ($which && file_exists($which)) {
                return $which;
            }
        }

        return null;
    }

    /**
     * Pure PHP PDO Database Exporter
     * Universal solution supporting full exports, selective table exports, and year filtering.
     */
    private function exportDatabaseViaPdo(\PDO $pdo, string $destFile, string $tableFilter = 'all', string|int $yearFilter = 'all'): bool
    {
        $fp = @fopen($destFile, 'wb');
        if (!$fp) {
            return false;
        }

        $tableLabel = $tableFilter === 'all' ? 'All Tables' : "`{$tableFilter}`";
        $yearLabel = $yearFilter === 'all' ? 'All Years' : "Year {$yearFilter}";

        fwrite($fp, "-- ======================================================\n");
        fwrite($fp, "-- CitiLife Diagnostic Center - Database Snapshot\n");
        fwrite($fp, "-- Export Target: {$tableLabel}\n");
        fwrite($fp, "-- Year Filter: {$yearLabel}\n");
        fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($fp, "-- Shared Data Repository & Patient Status Tracking\n");
        fwrite($fp, "-- ======================================================\n\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($fp, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($fp, "SET time_zone = \"+00:00\";\n");
        fwrite($fp, "SET NAMES utf8mb4;\n\n");

        if ($tableFilter === 'all') {
            $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $tablesStmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $checkStmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $checkStmt->execute([$tableFilter]);
            if (!$checkStmt->fetchColumn()) {
                fclose($fp);
                throw new \Exception("Table '{$tableFilter}' does not exist.");
            }
            $tables = [$tableFilter];
        }

        foreach ($tables as $tableName) {
            // 1. Structure
            fwrite($fp, "--\n-- Table structure for table `{$tableName}`\n--\n\n");
            fwrite($fp, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
            $createRow = $createStmt->fetch(\PDO::FETCH_NUM);
            if ($createRow && isset($createRow[1])) {
                fwrite($fp, $createRow[1] . ";\n\n");
            }

            // 2. Inspect Date Columns for Year Filtering
            $colStmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
            $rawCols = $colStmt->fetchAll(\PDO::FETCH_ASSOC);
            $columns = array_map(function($c) { return "`" . str_replace("`", "``", $c['Field']) . "`"; }, $rawCols);
            $colNames = array_column($rawCols, 'Field');
            $colList = implode(", ", $columns);

            $dateCol = null;
            if ($yearFilter !== 'all') {
                $priorityDateCols = ['created_at', 'amended_at', 'date_completed', 'updated_at'];
                foreach ($priorityDateCols as $c) {
                    if (in_array($c, $colNames, true)) {
                        $dateCol = $c;
                        break;
                    }
                }
            }

            $filterSql = "";
            $queryParams = [];
            if ($yearFilter !== 'all' && $dateCol) {
                $filterSql = " WHERE YEAR(`{$dateCol}`) = ?";
                $queryParams = [(int)$yearFilter];
                fwrite($fp, "--\n-- Dumping data for table `{$tableName}` (Filtered by `{$dateCol}` year = {$yearFilter})\n--\n\n");
            } elseif ($yearFilter !== 'all' && !$dateCol && $tableFilter === 'all') {
                fwrite($fp, "--\n-- Dumping data for table `{$tableName}` (Reference Table: All records exported to maintain data integrity)\n--\n\n");
            } else {
                fwrite($fp, "--\n-- Dumping data for table `{$tableName}`\n--\n\n");
            }

            // 3. Query Data Rows
            $query = "SELECT * FROM `{$tableName}`" . $filterSql;
            if (!empty($queryParams)) {
                $dataStmt = $pdo->prepare($query);
                $dataStmt->execute($queryParams);
            } else {
                $dataStmt = $pdo->query($query);
            }

            $batch = [];
            $batchSize = 250;

            while ($row = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                $escapedVals = [];
                foreach ($row as $val) {
                    if (is_null($val)) {
                        $escapedVals[] = "NULL";
                    } elseif (is_int($val) || is_float($val)) {
                        $escapedVals[] = $val;
                    } else {
                        $escapedVals[] = $pdo->quote($val);
                    }
                }
                $batch[] = "(" . implode(", ", $escapedVals) . ")";

                if (count($batch) >= $batchSize) {
                    $sql = "INSERT INTO `{$tableName}` (" . $colList . ") VALUES\n" . implode(",\n", $batch) . ";\n";
                    fwrite($fp, $sql);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $sql = "INSERT INTO `{$tableName}` (" . $colList . ") VALUES\n" . implode(",\n", $batch) . ";\n\n";
                fwrite($fp, $sql);
            } else {
                fwrite($fp, "\n");
            }
        }

        // Export Views if full database export
        if ($tableFilter === 'all') {
            $viewsStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
            $views = $viewsStmt->fetchAll(\PDO::FETCH_NUM);
            foreach ($views as $vRow) {
                $viewName = $vRow[0];
                fwrite($fp, "--\n-- View structure for `{$viewName}`\n--\n\n");
                fwrite($fp, "DROP VIEW IF EXISTS `{$viewName}`;\n");
                $createViewStmt = $pdo->query("SHOW CREATE VIEW `{$viewName}`");
                $createViewRow = $createViewStmt->fetch(\PDO::FETCH_NUM);
                if ($createViewRow && isset($createViewRow[1])) {
                    fwrite($fp, $createViewRow[1] . ";\n\n");
                }
            }
        }

        fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
        fwrite($fp, "-- Snapshot completed: " . date('Y-m-d H:i:s') . "\n");

        fclose($fp);
        return true;
    }
}
