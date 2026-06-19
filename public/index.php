<?php
session_start();

require_once __DIR__ . '/../helpers.php';

// Dine-define ang PROJECT_DIR dynamic constant para sa root routing compatibility
if (!defined('PROJECT_DIR')) {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/CitiLife-System/public/index.php';
    $parts = explode('/', $scriptPath);
    // Find the first segment after root, standardizing to project folder name
    define('PROJECT_DIR', (isset($parts[1]) && $parts[1] !== 'index.php') ? $parts[1] : 'CitiLife-System');
}

// I-load muna ang Composer Autoloader
require_once basePath('vendor/autoload.php');

// Load Database configuration
$dbConfig = require basePath('config/db.php');

// 6. Bootstrap Database using our Framework Database wrapper
use Framework\Database;
use Framework\Router;

try {
    $database = new Database($dbConfig);
    // Expose global PDO instance for models and backward compatibility
    $pdo = $database->conn;
} catch (Exception $e) {
    die("Database initialization failed: " . $e->getMessage());
}

// 7. Load Router and routes
$router = new Router();
require_once basePath('routes.php');

// 8. Match and execute the current request route
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $router->route($uri, $method);
} catch (\Throwable $e) {
    // I-log ang totoong error sa server para ma-check mo later kung bakit nag-error
    error_log($e->getMessage());
    
    // I-load ang 500 error view kapag may pumalyang code
    $router->error(500);
}
