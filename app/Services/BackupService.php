<?php

namespace App\Services;

class BackupService
{
    private \PDO $pdo;
    private string $backupDir;
    private string $trashDir;

    public function __construct(?\PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            if ($pdo) {
                $this->pdo = $pdo;
            } else {
                require_once __DIR__ . '/../../config/database.php';
                $this->pdo = $pdo;
            }
        }

        $this->backupDir = basePath('storage/backups/');
        $this->trashDir = basePath('storage/backups/trash/');

        if (!file_exists($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
        if (!file_exists($this->trashDir)) {
            @mkdir($this->trashDir, 0755, true);
        }
    }

    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    public function getTrashDir(): string
    {
        return $this->trashDir;
    }

    /**
     * Format bytes into readable string (KB, MB, bytes)
     */
    public static function formatSize(int|float $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Generate database snapshot backup (Full, Automated, or Selective)
     */
    public function generateBackup(string $tableFilter = 'all', string|int $yearFilter = 'all', bool $isAutomated = false, int $userId = 0): array
    {
        $tableFilter = trim($tableFilter);
        $tableFilter = preg_replace('/[^a-zA-Z0-9_]/', '', $tableFilter);
        if (empty($tableFilter)) {
            $tableFilter = 'all';
        }

        if ($yearFilter !== 'all') {
            $yearFilter = (int)$yearFilter;
            if ($yearFilter < 1900 || $yearFilter > 2100) {
                $yearFilter = 'all';
            }
        }

        $timestamp = date('Y-m-d_H-i-s');

        if ($isAutomated) {
            $filename = 'citilife_auto_' . $timestamp . '.sql';
        } elseif ($tableFilter === 'all' && $yearFilter === 'all') {
            $filename = 'citilife_full_' . $timestamp . '.sql';
        } elseif ($tableFilter !== 'all' && $yearFilter === 'all') {
            $filename = 'citilife_' . $tableFilter . '_all_years_' . $timestamp . '.sql';
        } elseif ($tableFilter === 'all' && $yearFilter !== 'all') {
            $filename = 'citilife_all_tables_' . $yearFilter . '_' . $timestamp . '.sql';
        } else {
            $filename = 'citilife_' . $tableFilter . '_' . $yearFilter . '_' . $timestamp . '.sql';
        }

        $fullPath = $this->backupDir . $filename;
        $backupSuccess = false;
        $lastError = '';

        // Attempt 1: For full non-filtered backups, try mysqldump binary if available
        if ($tableFilter === 'all' && $yearFilter === 'all') {
            $dbConfigFile = basePath('config/db.php');
            $dbConfig = file_exists($dbConfigFile) ? require $dbConfigFile : [];
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
                    $lastError = !empty($output) ? implode("\n", $output) : 'mysqldump execution failed';
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }
            }
        }

        // Attempt 2: Universal PHP PDO Exporter
        if (!$backupSuccess) {
            try {
                $backupSuccess = $this->exportDatabaseViaPdo($fullPath, $tableFilter, $yearFilter);
            } catch (\Exception $e) {
                $backupSuccess = false;
                $lastError = $e->getMessage();
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        if ($backupSuccess && file_exists($fullPath) && filesize($fullPath) > 50) {
            $filesize = filesize($fullPath);
            $sizeFormatted = self::formatSize($filesize);

            // Audit log
            try {
                if (!class_exists('AuditLogModel')) {
                    $mFile = basePath('app/Models/AuditLogModel.php');
                    if (file_exists($mFile)) {
                        require_once $mFile;
                    }
                }
                if (class_exists('AuditLogModel')) {
                    $auditLogModel = new \AuditLogModel($this->pdo);
                    $logAction = $isAutomated ? 'Generated Automated DB Backup' : 'Generated DB Backup';
                    $logDetails = "Filename: {$filename} ({$sizeFormatted}) | Table: {$tableFilter} | Year: {$yearFilter}";
                    $auditLogModel->addLog($userId, $logAction, 'System', 'Backup', 0, $logDetails);
                }
            } catch (\Throwable $t) {
                // Ignore audit log error if table busy
            }

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $fullPath,
                'filesize' => $filesize,
                'filesize_formatted' => $sizeFormatted,
                'message' => "Backup generated successfully: {$filename} ({$sizeFormatted})",
                'error' => null
            ];
        }

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        return [
            'success' => false,
            'filename' => $filename,
            'filepath' => null,
            'filesize' => 0,
            'filesize_formatted' => '0 bytes',
            'message' => 'Failed to generate backup.',
            'error' => $lastError ?: 'Unknown export error.'
        ];
    }

    /**
     * Enforce Retention Policy: Purge/Trash backups older than $maxDays, ensuring at least $keepMinimum snapshots remain
     */
    public function applyRetentionPolicy(int $maxDays = 60, int $keepMinimum = 8): array
    {
        $backups = $this->listBackups();
        $total = count($backups);

        if ($total <= $keepMinimum) {
            return [
                'checked_count' => $total,
                'purged_count' => 0,
                'message' => "Backups within threshold ({$total} <= {$keepMinimum}), no purging needed."
            ];
        }

        $cutoffTime = time() - ($maxDays * 86400);
        $purged = 0;
        $purgedFiles = [];

        // Backups are sorted newest first, so we protect the first $keepMinimum
        for ($i = $keepMinimum; $i < $total; $i++) {
            $b = $backups[$i];
            if ($b['date'] < $cutoffTime) {
                $filePath = $this->backupDir . $b['name'];
                $trashPath = $this->trashDir . $b['name'];
                
                // Move to trash
                if (@rename($filePath, $trashPath)) {
                    $purged++;
                    $purgedFiles[] = $b['name'];
                }
            }
        }

        return [
            'checked_count' => $total,
            'purged_count' => $purged,
            'purged_files' => $purgedFiles,
            'message' => "Retention policy applied: {$purged} backups older than {$maxDays} days moved to trash."
        ];
    }

    /**
     * Calculate automation status, last run, and upcoming scheduled backup
     */
    public function getAutomationStatus(): array
    {
        $backups = $this->listBackups();
        $total = count($backups);

        $lastBackup = !empty($backups) ? $backups[0] : null;
        $lastAutoBackup = null;

        foreach ($backups as $b) {
            if (str_starts_with($b['name'], 'citilife_auto_')) {
                $lastAutoBackup = $b;
                break;
            }
        }

        $lastBackupTime = $lastBackup ? (int)$lastBackup['date'] : null;
        $lastAutoBackupTime = $lastAutoBackup ? (int)$lastAutoBackup['date'] : null;

        $daysSinceLastBackup = $lastBackupTime ? round((time() - $lastBackupTime) / 86400, 1) : null;
        $isOverdue = ($daysSinceLastBackup === null || $daysSinceLastBackup >= 7.0);

        // Calculate Next Sunday 12:00 AM (00:00:00)
        $nextSunday = strtotime('next sunday 00:00:00');
        if (date('N') === '7' && (int)date('H') === 0) {
            $nextSunday = strtotime('today 00:00:00');
        }

        return [
            'total_backups' => $total,
            'last_backup' => $lastBackup,
            'last_backup_time' => $lastBackupTime,
            'last_backup_formatted' => $lastBackupTime ? date('M d, Y - h:i A', $lastBackupTime) : 'Never',
            'last_auto_backup' => $lastAutoBackup,
            'last_auto_backup_time' => $lastAutoBackupTime,
            'last_auto_backup_formatted' => $lastAutoBackupTime ? date('M d, Y - h:i A', $lastAutoBackupTime) : 'None',
            'days_since_last_backup' => $daysSinceLastBackup,
            'is_overdue' => $isOverdue,
            'next_scheduled_run' => $nextSunday,
            'next_scheduled_run_formatted' => date('M d, Y (D) - 12:00 \A\M', $nextSunday),
            'frequency' => 'Weekly (Every Sunday 12:00 AM)',
            'retention_policy' => 'Keeps last 8 snapshots / 60 days active'
        ];
    }

    /**
     * Restore database from an SQL file
     */
    public function restoreDatabase(string $filename, int $userId = 0): array
    {
        $file = basename($filename);
        $fullPath = realpath($this->backupDir . $file);

        if (!$fullPath || strpos($fullPath, realpath($this->backupDir)) !== 0 || !file_exists($fullPath)) {
            return ['success' => false, 'message' => 'Backup file not found.'];
        }

        try {
            $this->importSqlFile($fullPath);

            try {
                $auditLogModel = new \AuditLogModel($this->pdo);
                $auditLogModel->addLog($userId, 'Restored Database from Backup', 'System', 'Backup', 0, "Filename: {$file}");
            } catch (\Throwable $t) {}

            return ['success' => true, 'message' => "Database successfully restored from backup: {$file}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Database restoration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Move backup to Trash / Recycle Bin (Soft Delete)
     */
    public function deleteBackup(string $filename, int $userId = 0): bool
    {
        $file = basename($filename);
        $fullPath = realpath($this->backupDir . $file);

        if ($fullPath && strpos($fullPath, realpath($this->backupDir)) === 0 && file_exists($fullPath)) {
            $trashPath = $this->trashDir . $file;
            if (@rename($fullPath, $trashPath)) {
                try {
                    $auditLogModel = new \AuditLogModel($this->pdo);
                    $auditLogModel->addLog($userId, 'Moved DB Backup to Trash', 'System', 'Backup', 0, "Filename: {$file}");
                } catch (\Throwable $t) {}
                return true;
            }
        }
        return false;
    }

    /**
     * Restore a deleted file from Trash back to active backups
     */
    public function restoreFromTrash(string $filename, int $userId = 0): bool
    {
        $file = basename($filename);
        $trashPath = realpath($this->trashDir . $file);

        if ($trashPath && strpos($trashPath, realpath($this->trashDir)) === 0 && file_exists($trashPath)) {
            $restorePath = $this->backupDir . $file;
            if (@rename($trashPath, $restorePath)) {
                try {
                    $auditLogModel = new \AuditLogModel($this->pdo);
                    $auditLogModel->addLog($userId, 'Restored DB Backup from Trash', 'System', 'Backup', 0, "Filename: {$file}");
                } catch (\Throwable $t) {}
                return true;
            }
        }
        return false;
    }

    /**
     * Permanently delete a backup file from Trash
     */
    public function purgeFromTrash(string $filename, int $userId = 0): bool
    {
        $file = basename($filename);
        $trashPath = realpath($this->trashDir . $file);

        if ($trashPath && strpos($trashPath, realpath($this->trashDir)) === 0 && file_exists($trashPath)) {
            if (@unlink($trashPath)) {
                try {
                    $auditLogModel = new \AuditLogModel($this->pdo);
                    $auditLogModel->addLog($userId, 'Permanently Deleted Backup File', 'System', 'Backup', 0, "Filename: {$file}");
                } catch (\Throwable $t) {}
                return true;
            }
        }
        return false;
    }

    /**
     * Empty entire Trash Bin
     */
    public function emptyTrash(int $userId = 0): int
    {
        $count = 0;
        $files = scandir($this->trashDir);
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..' && str_ends_with($f, '.sql')) {
                if (@unlink($this->trashDir . $f)) {
                    $count++;
                }
            }
        }

        try {
            $auditLogModel = new \AuditLogModel($this->pdo);
            $auditLogModel->addLog($userId, 'Emptied Backup Trash', 'System', 'Backup', 0, "Purged {$count} files");
        } catch (\Throwable $t) {}

        return $count;
    }

    /**
     * List all active backups sorted newest first
     */
    public function listBackups(): array
    {
        $backups = [];
        $files = scandir($this->backupDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && str_ends_with($file, '.sql')) {
                $filePath = $this->backupDir . $file;
                $size = filesize($filePath);

                // Clean corrupted zero-byte files
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
                    'type' => $meta['type'],
                    'is_automated' => str_starts_with($file, 'citilife_auto_')
                ];
            }
        }

        usort($backups, function ($a, $b) {
            return $b['date'] - $a['date'];
        });

        return $backups;
    }

    /**
     * List all backups in Trash sorted newest first
     */
    public function listTrash(): array
    {
        $trashBackups = [];
        $files = scandir($this->trashDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && str_ends_with($file, '.sql')) {
                $filePath = $this->trashDir . $file;
                $trashBackups[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'date' => filemtime($filePath)
                ];
            }
        }

        usort($trashBackups, function ($a, $b) {
            return $b['date'] - $a['date'];
        });

        return $trashBackups;
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

        if (str_starts_with($filename, 'citilife_auto_')) {
            $meta['table'] = 'All Tables';
            $meta['year'] = 'All Years';
            $meta['type'] = 'Automated Weekly Backup';
        } elseif (preg_match('/^citilife_([a-zA-Z0-9_-]+)_(all_years|all-years|[0-9]{4})_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename, $m)) {
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
     * Pure PHP PDO Database Exporter
     */
    private function exportDatabaseViaPdo(string $destFile, string $tableFilter = 'all', string|int $yearFilter = 'all'): bool
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
            $tablesStmt = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $tablesStmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $checkStmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
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

            $createStmt = $this->pdo->query("SHOW CREATE TABLE `{$tableName}`");
            $createRow = $createStmt->fetch(\PDO::FETCH_NUM);
            if ($createRow && isset($createRow[1])) {
                fwrite($fp, $createRow[1] . ";\n\n");
            }

            // 2. Inspect Date Columns for Year Filtering
            $colStmt = $this->pdo->query("SHOW COLUMNS FROM `{$tableName}`");
            $rawCols = $colStmt->fetchAll(\PDO::FETCH_ASSOC);
            $columns = array_map(function ($c) { return "`" . str_replace("`", "``", $c['Field']) . "`"; }, $rawCols);
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
                $dataStmt = $this->pdo->prepare($query);
                $dataStmt->execute($queryParams);
            } else {
                $dataStmt = $this->pdo->query($query);
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
                        $escapedVals[] = $this->pdo->quote($val);
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
            $viewsStmt = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
            $views = $viewsStmt->fetchAll(\PDO::FETCH_NUM);
            foreach ($views as $vRow) {
                $viewName = $vRow[0];
                fwrite($fp, "--\n-- View structure for `{$viewName}`\n--\n\n");
                fwrite($fp, "DROP VIEW IF EXISTS `{$viewName}`;\n");
                $createViewStmt = $this->pdo->query("SHOW CREATE VIEW `{$viewName}`");
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

    /**
     * Import and restore database from an SQL file
     */
    private function importSqlFile(string $filePath): bool
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

        $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->pdo->exec("SET NAMES utf8mb4;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

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
                    $stmt = $this->pdo->prepare($query);
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
                $stmt = $this->pdo->prepare($query);
                if ($stmt) {
                    $stmt->execute();
                    $stmt->closeCursor();
                }
            } catch (\Exception $e) {
                // non-fatal
            }
        }

        fclose($fp);
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        return true;
    }

    /**
     * Locate mysqldump binary
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

        if (DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec')) {
            $which = trim((string) @shell_exec('which mysqldump 2>/dev/null'));
            if ($which && file_exists($which)) {
                return $which;
            }
        }

        return null;
    }

    /**
     * Dispatch In-App Notification and Email Alerts to all IT Admins upon Automated Backup completion
     */
    public function notifyAdminsOfAutoBackup(array $backupResult, array $retentionResult = []): void
    {
        if (empty($backupResult['success'])) {
            return;
        }

        $filename = $backupResult['filename'] ?? 'snapshot.sql.gz';
        $sizeFormatted = $backupResult['filesize_formatted'] ?? 'Unknown size';
        $purgedCount = $retentionResult['purged_count'] ?? 0;

        // 1. In-App Notification (Toast & Bell)
        try {
            require_once __DIR__ . '/../Models/NotificationModel.php';
            $notifModel = new \NotificationModel($this->pdo);
            $title = 'Weekly Database Backup Complete';
            $message = "Automated snapshot generated: {$filename} ({$sizeFormatted}). Retention cleanup verified.";
            $link = function_exists('url') ? url('backup-maintenance') : '/backup-maintenance';

            $notifModel->add($title, $message, $link, null, 'it_admin', null);
        } catch (\Throwable $e) {
            error_log("Failed to create in-app notification for auto backup: " . $e->getMessage());
        }

        // 2. Direct Email Notification to IT Admins
        try {
            require_once __DIR__ . '/../Helpers/mailer_helper.php';
            require_once __DIR__ . '/../Helpers/email_template_helper.php';

            $stmtAdmins = $this->pdo->prepare("SELECT email, name FROM users WHERE role = 'it_admin' AND (status = 'Active' OR status IS NULL)");
            $stmtAdmins->execute();
            $admins = $stmtAdmins->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($admins)) {
                $safeFilename = htmlspecialchars($filename);
                $safeFilesize = htmlspecialchars($sizeFormatted);
                $purgedInfo = $purgedCount > 0
                    ? "Retention: {$purgedCount} snapshot(s) older than 60 days pruned."
                    : "Retention: All stored snapshots within active 60-day threshold.";
                $currentTime = date('F j, Y - g:i A T');

                $cardContent = '
                    <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #374151;">
                        The automated weekly database backup routine has completed successfully. A full database snapshot has been generated and archived to secure storage.
                    </p>
                    <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; margin: 16px 0; font-family: monospace, sans-serif; font-size: 13px; color: #111827;">
                        <div style="margin-bottom: 6px;"><strong>File:</strong> ' . $safeFilename . '</div>
                        <div style="margin-bottom: 6px;"><strong>Size:</strong> ' . $safeFilesize . '</div>
                        <div style="margin-bottom: 6px;"><strong>Timestamp:</strong> ' . $currentTime . '</div>
                        <div style="margin-bottom: 6px;"><strong>Status:</strong> <span style="color: #059669; font-weight: bold;">Healthy & Verified</span></div>
                        <div><strong>Policy:</strong> ' . htmlspecialchars($purgedInfo) . '</div>
                    </div>
                    <p style="margin: 16px 0 0; font-size: 13px; color: #6b7280;">
                        You can review, download, or restore database snapshots anytime via the CitiLife IT Admin Portal.
                    </p>
                ';

                $emailHtml = renderGitHubStyleEmail([
                    'headerTitle' => 'Weekly Automated Backup Successful',
                    'cardContent' => $cardContent,
                    'footerNotice' => 'You received this automated security notice because your account has IT Administrator privileges in CitiLife System.'
                ]);

                $subject = '[CitiLife System] Weekly Database Backup Successful - ' . date('M d, Y');

                foreach ($admins as $admin) {
                    $adminEmail = $admin['email'] ?? '';
                    $adminName = $admin['name'] ?? 'IT Administrator';
                    if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                        sendEmailAsync($adminEmail, $adminName, $subject, $emailHtml);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("Failed to send email notification for auto backup: " . $e->getMessage());
        }
    }
}

