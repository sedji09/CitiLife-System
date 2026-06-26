<?php

namespace App\Controllers\it_admin;

class DashboardController
{
    public function handle()
    {
        global $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!function_exists('App\Controllers\it_admin\formatBytes')) {
            function formatBytes($bytes, $precision = 2) {
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                $bytes = max($bytes, 0);
                $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                $pow = min($pow, count($units) - 1);
                $bytes /= pow(1024, $pow);
                return round($bytes, $precision) . ' ' . $units[$pow];
            }
        }

        // User & branch metrics (all accounts in users table, including patients)
        $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active' AND last_activity >= NOW() - INTERVAL 15 MINUTE")->fetchColumn();
        $totalBranches = (int) $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();

        // Database environment (future-ready profile)
        $dbConfigPath = basePath('config/db.php');
        $dbConfig = file_exists($dbConfigPath) ? require $dbConfigPath : [];
        $dbHost = $dbConfig['host'] ?? 'localhost';
        $isCloudDb = !in_array($dbHost, ['localhost', '127.0.0.1'], true);

        $dbProfile = [
            'type'               => $isCloudDb ? 'Cloud' : 'Local',
            'status'             => 'Online',
            'connection_status'  => 'Connected',
            'cloud_provider'     => $isCloudDb ? ($dbConfig['provider'] ?? 'Cloud Provider') : null,
            'region'             => $isCloudDb ? ($dbConfig['region'] ?? null) : null,
            'endpoint'           => $isCloudDb ? $dbHost : null,
            'engine'             => null,
            'size'               => null,
            'storage_usage_pct'  => null,
            'last_backup'        => 'Never',
        ];

        try {
            $pdo->query('SELECT 1');
            $dbProfile['engine'] = explode('-', $pdo->query('SELECT VERSION()')->fetchColumn())[0];
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $dbSizeStmt = $pdo->prepare('SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = ?');
            $dbSizeStmt->execute([$dbName]);
            $dbSizeBytes = (int) $dbSizeStmt->fetchColumn();
            $dbProfile['size'] = formatBytes($dbSizeBytes);
        } catch (\Exception $e) {
            $dbProfile['status'] = 'Offline';
            $dbProfile['connection_status'] = 'Disconnected';
        }

        // Server storage
        $diskPath = 'C:';
        if (!@disk_total_space($diskPath)) {
            $diskPath = basePath('');
        }
        $diskTotal = @disk_total_space($diskPath) ?: 0;
        $diskFree = @disk_free_space($diskPath) ?: 0;
        $diskUsed = $diskTotal - $diskFree;
        $diskUsagePercentage = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;
        $formattedDiskTotal = formatBytes($diskTotal);
        $formattedDiskUsed = formatBytes($diskUsed);
        $dbProfile['storage_usage_pct'] = $diskUsagePercentage;

        // Last backup
        $backupDir = basePath('storage/backups/');
        $lastBackupTimestamp = null;
        $backupDirWritable = is_dir($backupDir) && is_writable($backupDir);
        if (is_dir($backupDir)) {
            $files = array_diff(scandir($backupDir, SCANDIR_SORT_DESCENDING) ?: [], ['.', '..']);
            foreach ($files as $file) {
                if (strpos($file, '.sql') !== false) {
                    $backupFile = $backupDir . $file;
                    $lastBackupTimestamp = filemtime($backupFile) ?: null;
                    $dbProfile['last_backup'] = date('M d, Y H:i', $lastBackupTimestamp);
                    break;
                }
            }
        }

        // Real system health checks
        $healthIssues = [];

        if ($dbProfile['status'] !== 'Online') {
            $healthIssues[] = 'Database connection failed';
        }
        if (!$backupDirWritable) {
            $healthIssues[] = 'Backup folder is missing or not writable';
        }
        if ($lastBackupTimestamp === null) {
            $healthIssues[] = 'No database backup on record';
        } elseif ((time() - $lastBackupTimestamp) > 7 * 86400) {
            $healthIssues[] = 'Latest backup is more than 7 days old';
        }
        if ($diskUsagePercentage >= 95) {
            $healthIssues[] = 'Storage critically full (' . $diskUsagePercentage . '% used)';
        } elseif ($diskUsagePercentage >= 85) {
            $healthIssues[] = 'Storage almost full (' . $diskUsagePercentage . '% used)';
        }

        if ($dbProfile['status'] === 'Offline') {
            $systemStatus = 'Offline';
            $serverStatus = 'Stopped';
            $systemStatusTone = 'rose';
        } elseif ($diskUsagePercentage >= 95 || !$backupDirWritable) {
            $systemStatus = 'Degraded';
            $serverStatus = 'Warning';
            $systemStatusTone = 'amber';
        } elseif (!empty($healthIssues)) {
            $systemStatus = 'Degraded';
            $serverStatus = 'Warning';
            $systemStatusTone = 'amber';
        } else {
            $systemStatus = 'Healthy';
            $serverStatus = 'Running';
            $systemStatusTone = 'emerald';
        }

        $storageStatusTone = $diskUsagePercentage >= 95 ? 'rose' : ($diskUsagePercentage >= 85 ? 'amber' : 'indigo');
        $dbStatusTone = $dbProfile['status'] === 'Online' ? 'emerald' : 'rose';

        // Security metrics
        try {
            $pdo->exec('ALTER TABLE users ADD COLUMN login_locked_until DATETIME DEFAULT NULL AFTER otp_locked_until');
        } catch (\Exception $e) {
        }

        $lockedAccounts = (int) $pdo->query("
            SELECT COUNT(*) FROM users
            WHERE status = 'Inactive'
               OR otp_locked_until > NOW()
               OR login_locked_until > NOW()
        ")->fetchColumn();
        $failedLoginCount = (int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE module = 'Authentication' AND (details LIKE '%failed%' OR details LIKE '%invalid%' OR action LIKE '%failed%') AND created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
        $expiredPasswordCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'patient' AND reset_password_token IS NOT NULL AND reset_password_expires_at < NOW()")->fetchColumn();
        $unauthorizedCount = (int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE (details LIKE '%unauthorized%' OR action LIKE '%unauthorized%') AND created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();

        // Recent IT system activities
        $recentActivities = $pdo->query("
            SELECT al.*, u.name as user_name, u.email as user_email
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.module IN ('User Management', 'Branch Management', 'Authentication', 'System', 'Security Settings', 'IT Admin', 'Backup')
               OR al.action LIKE '%Login%'
               OR al.action LIKE '%Logout%'
               OR al.action LIKE '%Backup%'
               OR al.action LIKE '%Security%'
               OR al.action LIKE '%Maintenance%'
            ORDER BY al.created_at DESC
            LIMIT 15
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // Security alert feed
        $securityAlerts = [];

        $recentFailedLogins = $pdo->query("
            SELECT al.*, u.name as user_name, u.email as user_email
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.module = 'Authentication'
              AND (al.details LIKE '%failed%' OR al.details LIKE '%invalid%' OR al.action LIKE '%failed%')
            ORDER BY al.created_at DESC
            LIMIT 5
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($recentFailedLogins as $fail) {
            $securityAlerts[] = [
                'type'    => 'Failed Login',
                'title'   => $fail['action'],
                'detail'  => $fail['details'] ?? '',
                'meta'    => $fail['user_email'] ?? ('User ID: ' . ($fail['user_id'] ?? 'Unknown')),
                'time'    => $fail['created_at'],
                'severity'=> 'high',
            ];
        }

        $lockedUsers = $pdo->query("
            SELECT id, email, name, otp_locked_until, login_locked_until, status
            FROM users
            WHERE status = 'Inactive'
               OR otp_locked_until > NOW()
               OR login_locked_until > NOW()
            ORDER BY COALESCE(login_locked_until, otp_locked_until) DESC
            LIMIT 5
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($lockedUsers as $user) {
            $lockDetail = 'Account deactivated';
            if ($user['status'] !== 'Inactive') {
                if (!empty($user['login_locked_until']) && strtotime($user['login_locked_until']) > time()) {
                    $lockDetail = 'Login lockout active (too many failed attempts)';
                } else {
                    $lockDetail = 'OTP lockout active';
                }
            }
            $securityAlerts[] = [
                'type'    => 'Locked Account',
                'title'   => $user['email'],
                'detail'  => $lockDetail,
                'meta'    => $user['name'] ?? 'Staff account',
                'time'    => $user['login_locked_until'] ?? $user['otp_locked_until'] ?? date('Y-m-d H:i:s'),
                'severity'=> 'medium',
            ];
        }

        if ($unauthorizedCount > 0) {
            $unauthorizedLogs = $pdo->query("
                SELECT al.*, u.email as user_email
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE (al.details LIKE '%unauthorized%' OR al.action LIKE '%unauthorized%')
                ORDER BY al.created_at DESC
                LIMIT 3
            ")->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($unauthorizedLogs as $log) {
                $securityAlerts[] = [
                    'type'    => 'Unauthorized Access',
                    'title'   => $log['action'],
                    'detail'  => $log['details'] ?? '',
                    'meta'    => $log['user_email'] ?? 'Unknown user',
                    'time'    => $log['created_at'],
                    'severity'=> 'high',
                ];
            }
        }

        if ($expiredPasswordCount > 0) {
            $securityAlerts[] = [
                'type'    => 'Expired Password Reset',
                'title'   => $expiredPasswordCount . ' pending reset link(s) expired',
                'detail'  => 'Password reset tokens have expired and require re-issuance.',
                'meta'    => 'User Management',
                'time'    => date('Y-m-d H:i:s'),
                'severity'=> 'low',
            ];
        }

        usort($securityAlerts, function ($a, $b) {
            return strtotime($b['time']) <=> strtotime($a['time']);
        });
        $securityAlerts = array_slice($securityAlerts, 0, 8);

        return get_defined_vars();
    }
}
