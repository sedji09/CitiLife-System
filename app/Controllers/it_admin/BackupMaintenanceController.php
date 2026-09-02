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
         * IT Admin module for database backups and system maintenance.
         */

        $auditLogModel = new \AuditLogModel($pdo);
        $backupDir = __DIR__ . '/../../../storage/backups/';
        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

        // Ensure directory exists
        if (!file_exists($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        // 1. Handle Actions
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'generate_backup') {
                $filename = 'citilife_db_' . date('Y-m-d_H-i-s') . '.sql';
                $fullPath = $backupDir . $filename;
                
                $backupSuccess = false;
                $lastError = '';

                // Attempt 1: Try mysqldump binary (with cross-platform detection)
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

                // Attempt 2: Pure PHP PDO Fallback (Universal for Railway, Linux containers, and shared hosting)
                if (!$backupSuccess) {
                    try {
                        $backupSuccess = $this->exportDatabaseViaPdo($pdo, $fullPath);
                    } catch (\Exception $e) {
                        $backupSuccess = false;
                        $lastError = $e->getMessage();
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                }

                if ($backupSuccess && file_exists($fullPath) && filesize($fullPath) > 100) {
                    $fileSizeFormatted = $this->formatSize(filesize($fullPath));
                    $adminId = $_SESSION['user_id'] ?? 0;
                    $auditLogModel->addLog($adminId, 'Generated DB Backup', 'System', 'Backup', 0, "Filename: $filename ($fileSizeFormatted)");
                    $_SESSION['success'] = "Backup generated successfully: $filename ($fileSizeFormatted)";
                } else {
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                    $_SESSION['error'] = "Backup failed: " . ($lastError ?: "Unable to export database snapshot.");
                }

                header("Location: ?page=backup-maintenance");
                exit();
            }

            if ($action === 'delete_backup') {
                $file = $_POST['filename'] ?? '';
                $fullPath = realpath($backupDir . $file);

                // Security check: ensure file is within backupDir
                if ($fullPath && strpos($fullPath, realpath($backupDir)) === 0 && file_exists($fullPath)) {
                    @unlink($fullPath);
                    $adminId = $_SESSION['user_id'] ?? 0;
                    $auditLogModel->addLog($adminId, 'Deleted DB Backup', 'System', 'Backup', 0, "Filename: $file");
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

        // 3. List existing backups (clean up any 0-byte or error-line corrupted files)
        $backups = [];
        $files = scandir($backupDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && strpos($file, '.sql') !== false) {
                $filePath = $backupDir . $file;
                $size = filesize($filePath);
                
                // If a file is smaller than 100 bytes and contains an execution error string, clean it up
                if ($size < 100) {
                    $content = @file_get_contents($filePath);
                    if (stripos($content, 'not found') !== false || stripos($content, 'sh: ') !== false || stripos($content, 'error') !== false) {
                        @unlink($filePath);
                        continue;
                    }
                }

                $backups[] = [
                    'name' => $file,
                    'size' => $size,
                    'date' => filemtime($filePath)
                ];
            }
        }

        // Sort by date descending
        usort($backups, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        // Helper for view
        $formatSizeFn = [$this, 'formatSize'];

        return get_defined_vars();
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
     * Pure PHP PDO Database Exporter (Universal fallback for Railway / Docker / Cloud)
     */
    private function exportDatabaseViaPdo(\PDO $pdo, string $destFile): bool
    {
        $fp = @fopen($destFile, 'wb');
        if (!$fp) {
            return false;
        }

        fwrite($fp, "-- CitiLife Medical Clinic Database Snapshot\n");
        fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($fp, "-- ------------------------------------------------------\n\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($fp, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($fp, "SET time_zone = \"+00:00\";\n");
        fwrite($fp, "SET NAMES utf8mb4;\n\n");

        $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = $tablesStmt->fetchAll(\PDO::FETCH_NUM);

        foreach ($tables as $tRow) {
            $tableName = $tRow[0];

            // 1. Structure
            fwrite($fp, "--\n-- Table structure for table `{$tableName}`\n--\n\n");
            fwrite($fp, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
            $createRow = $createStmt->fetch(\PDO::FETCH_NUM);
            if ($createRow && isset($createRow[1])) {
                fwrite($fp, $createRow[1] . ";\n\n");
            }

            // 2. Data
            fwrite($fp, "--\n-- Dumping data for table `{$tableName}`\n--\n\n");

            $colStmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
            $columns = array_map(function($c) { return "`" . str_replace("`", "``", $c['Field']) . "`"; }, $colStmt->fetchAll(\PDO::FETCH_ASSOC));
            $colList = implode(", ", $columns);

            $dataStmt = $pdo->query("SELECT * FROM `{$tableName}`");
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

        // Optional: Export Views if any
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

        fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
        fwrite($fp, "-- Backup completed: " . date('Y-m-d H:i:s') . "\n");

        fclose($fp);
        return true;
    }

    /**
     * Helper for human-readable size
     */
    public function formatSize(int|float $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
