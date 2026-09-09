<?php
require_once __DIR__ . '/../config/session.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Language: en');

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
    }
} catch (Exception $e) {
    die("Database initialization failed: " . $e->getMessage());
}

// 7. Load Router and routes
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



