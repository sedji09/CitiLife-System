<?php
session_start();

require_once __DIR__ . '/../helpers.php';

if (file_exists(__DIR__ . '/../env.php')) {
    require_once __DIR__ . '/../env.php';
}

// Dine-define ang PROJECT_DIR dynamic constant para sa root routing compatibility
if (!defined('PROJECT_DIR')) {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/CitiLife-System/public/index.php';
    $parts = explode('/', $scriptPath);
    // Find the first segment after root, standardizing to project folder name
    define('PROJECT_DIR', (isset($parts[1]) && $parts[1] !== 'index.php') ? $parts[1] : 'CitiLife-System');
}

// I-load muna ang Composer Autoloader
require_once basePath('vendor/autoload.php');

// Custom autoloader para sa Models (para hindi na kailangan ng Composer classmap)
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
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

ob_start();

try {
    $router->route($uri, $method);
} catch (\Throwable $e) {
    // I-log ang totoong error sa server para ma-check mo later kung bakit nag-error
    error_log($e->getMessage());
    
    // I-load ang 500 error view kapag may pumalyang code
    $router->error(500);
}

$output = ob_get_clean();

// Kung tumatakbo sa Railway, ayusin ang mga hardcoded XAMPP paths para hindi maging 404 ang CSS/JS at links
if (getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST') || isset($_ENV['MYSQLHOST'])) {
    // Remove the XAMPP project folder from the path for Railway
    $output = str_replace('/' . PROJECT_DIR . '/', '/', $output);
}

echo $output;
