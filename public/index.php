<?php

// 1. Session start
session_start();

// 2. Load global helpers (which defines basePath)
require_once __DIR__ . '/../helpers.php';

// 3. Define PROJECT_DIR dynamic constant for root routing compatibility
if (!defined('PROJECT_DIR')) {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/CitiLife-System/public/index.php';
    $parts = explode('/', $scriptPath);
    // Find the first segment after root, standardizing to project folder name
    define('PROJECT_DIR', (isset($parts[1]) && $parts[1] !== 'index.php') ? $parts[1] : 'CitiLife-System');
}

// 4. Load Composer Autoloader
require_once basePath('vendor/autoload.php');

// 5. Load Database configuration
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

$router->route($uri, $method);
