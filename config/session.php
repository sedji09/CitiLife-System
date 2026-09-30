<?php
/**
 * config/session.php
 * Centralized Session Bootstrapper.
 * Ensures consistent session storage between main application routes and direct API calls,
 * particularly on Railway and production environments where uploads/sessions is persistent.
 */
if (session_status() === PHP_SESSION_NONE) {
    // Detect HTTPS (including reverse proxies like Cloudflare/Railway/Nginx)
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    // Secure cookie settings before session_start
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $isSecure, true);
    }

    $isRailway = getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST') || isset($_ENV['MYSQLHOST']);
    if ($isRailway) {
        $baseDir = dirname(__DIR__);
        $sessionPath = $baseDir . '/public/uploads/sessions';
        if (!file_exists($sessionPath)) {
            @mkdir($sessionPath, 0777, true);
            @file_put_contents($sessionPath . '/.htaccess', 'Require all denied');
        }
        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }
    }
    @session_start();
}
