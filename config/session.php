<?php
/**
 * config/session.php
 * Centralized Session Bootstrapper.
 * Ensures consistent session storage between main application routes and direct API calls,
 * particularly on Railway and production environments where uploads/sessions is persistent.
 */
if (session_status() === PHP_SESSION_NONE) {
    $isRailway = getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST') || isset($_ENV['MYSQLHOST']);
    if ($isRailway) {
        $baseDir = dirname(__DIR__);
        $sessionPath = $baseDir . '/public/uploads/sessions';
        if (!file_exists($sessionPath)) {
            @mkdir($sessionPath, 0777, true);
            @file_put_contents($sessionPath . '/.htaccess', 'Require all denied');
        }
        session_save_path($sessionPath);
    }
    session_start();
}
