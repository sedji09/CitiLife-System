<?php
require_once __DIR__ . '/../config/session.php';
// Environment-Aware Error Handling (Production Safe)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

if ($isLocalhost) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    // Production / Railway: Hide fatal code details from public and log safely to server log
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

header('Content-Language: en');

// Global Security & OWASP Defense-in-Depth Headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
header('Cross-Origin-Resource-Policy: same-origin');

// Strict-Transport-Security (HSTS) - Enabled on HTTPS and Railway/Proxies
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Content Security Policy (Optimized for Mozilla Observatory + Full compatibility)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: blob: https:; connect-src 'self' https://challenges.cloudflare.com; frame-src 'self' https://challenges.cloudflare.com; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self';");

// Remove PHP Version Information Leak
if (function_exists('header_remove')) {
    header_remove('X-Powered-By');
}

require_once __DIR__ . '/../helpers.php';

if (file_exists(__DIR__ . '/../env.php')) {
    require_once __DIR__ . '/../env.php';
}

// Define PROJECT_DIR dynamic constant for root routing compatibility
if (!defined('PROJECT_DIR')) {
    $folderName = basename(dirname(__DIR__));
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    $ignoredFolderNames = ['app', 'html', 'public', 'www', 'var', 'srv'];

    if (
        !in_array(strtolower($folderName), $ignoredFolderNames) && (
            (!empty($scriptName) && stripos($scriptName, '/' . $folderName) === 0) ||
            (!empty($requestUri) && stripos($requestUri, '/' . $folderName) === 0)
        )
    ) {
        define('PROJECT_DIR', $folderName);
    } else {
        define('PROJECT_DIR', '');
    }
}

// Load Composer Autoloader first
require_once basePath('vendor/autoload.php');

// Custom autoloader for Models (avoiding Composer classmap reload)
spl_autoload_register(function ($class_name) {
    $file = basePath('app/Models/' . $class_name . '.php');
    if (file_exists($file)) {
        require_once $file;
    }
});
// Load Database configuration
$dbConfig = require basePath('config/db.php');

// 6. Bootstrap Database using our Framework Database wrapper
use Framework\Database;
use Framework\Router;

try {
    $database = new Database($dbConfig);
    // Expose global PDO instance for models and backward compatibility
    $pdo = $database->conn;

    // Update last activity for real-time tracking
    if (isset($_SESSION['user_id'])) {
        $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$_SESSION['user_id']]);

        // Enforce Session Inactivity Timeout
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $isLogoutRoute = (strpos($currentUri, 'logout') !== false);
        $isPingRoute = (strpos($currentUri, 'session_ping') !== false || strpos($currentUri, 'session-ping') !== false);

        if (!$isLogoutRoute) {
            $timeoutMinutes = function_exists('getSystemSetting') ? (int) getSystemSetting('auto_logout_minutes', 30) : 30;
            if ($timeoutMinutes <= 0) {
                $timeoutMinutes = 30;
            }
            $timeoutSeconds = $timeoutMinutes * 60;

            if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeoutSeconds)) {
                $isPatient = (($_SESSION['role'] ?? '') === 'patient');
                $_SESSION = [];
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_destroy();
                }

                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
                    || (strpos($currentUri, '/app/api/') !== false)
                    || (strpos($currentUri, '/app/Api/') !== false);

                $redirectUrl = url($isPatient ? 'patient-login?error=inactivity' : 'login?error=inactivity');

                if ($isAjax) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode([
                        'status' => 'session_expired',
                        'message' => 'Session expired due to inactivity',
                        'redirect' => $redirectUrl
                    ]);
                    exit;
                }

                redirect($redirectUrl);
            }

            // Only update LAST_ACTIVITY on non-background requests or when explicitly renewed
            $_SESSION['LAST_ACTIVITY'] = time();
        }
    }
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    http_response_code(503);
    if (file_exists(basePath('views/errors/503.view.php'))) {
        loadView('errors/503');
    } else {
        die("Service temporarily unavailable. Please try again shortly.");
    }
    exit;
}

// 7. Secure Authenticated File Streamer (Phase 5 - Medical PHI Security)
$reqUri = $_SERVER['REQUEST_URI'] ?? '';
if (
    isset($_GET['secure_file']) || 
    (isset($_GET['file']) && (strpos($_GET['file'], 'uploads/cases') !== false || strpos($_GET['file'], 'uploads/reports') !== false || strpos($_GET['file'], 'uploads/signatures') !== false)) ||
    preg_match('#/uploads/(cases|reports|signatures|receipts)/#i', $reqUri)
) {
    require_once basePath('app/Controllers/ImageController.php');
    $imageController = new \App\Controllers\ImageController();
    $imageController->view();
    exit;
}

// 8. Load Router and routes
$router = new Router();
require_once basePath('routes.php');

// 8. Match and execute the current request route
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($uri, '//') === 0 && strpos($uri, '://') === false) {
    $uri = '/' . ltrim($uri, '/');
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

ob_start();

try {
    $router->route($uri, $method);
} catch (\Throwable $e) {
    // Log server error for troubleshooting
    error_log($e->getMessage());
    ob_clean();

    // Load 500 error view on failure
    $router->error(500);
}

$output = ob_get_clean();

$isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;

// If running in production (e.g., Railway), fix hardcoded XAMPP paths to prevent 404 on CSS/JS and links
if (!$isLocalhost) {
    if (!empty(PROJECT_DIR)) {
        $output = str_replace('/' . PROJECT_DIR . '/', '/', $output);
    }
    $output = str_ireplace('/CitiLife-System/', '/', $output);
}

echo $output;



