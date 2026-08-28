<?php
/**
 * Database configuration.
 * For cloud migration, add optional keys: provider, region
 * (host will serve as the database endpoint when not localhost).
 */

// Ensure env.php is loaded even if the script was accessed directly without going through index.php
if (file_exists(__DIR__ . '/../env.php')) {
    require_once __DIR__ . '/../env.php';
}

$host = $_ENV['MYSQLHOST'] ?? $_SERVER['MYSQLHOST'] ?? getenv('MYSQLHOST') ?: 'localhost';
$port = $_ENV['MYSQLPORT'] ?? $_SERVER['MYSQLPORT'] ?? getenv('MYSQLPORT') ?: '3306';
$dbname = $_ENV['MYSQLDATABASE'] ?? $_SERVER['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE') ?: 'citilife_db';
$username = $_ENV['MYSQLUSER'] ?? $_SERVER['MYSQLUSER'] ?? getenv('MYSQLUSER') ?: 'root';
$password = $_ENV['MYSQLPASSWORD'] ?? $_SERVER['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?: '';

$mysqlUrl = $_ENV['MYSQL_URL'] ?? $_SERVER['MYSQL_URL'] ?? getenv('MYSQL_URL') ?: '';
if ($mysqlUrl) {
    $parsed = parse_url($mysqlUrl);
    $host = $parsed['host'] ?? $host;
    $port = $parsed['port'] ?? $port;
    $dbname = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $dbname;
    $username = $parsed['user'] ?? $username;
    $password = $parsed['pass'] ?? $password;
}

return [
    'host'     => $host,
    'port'     => $port,
    'dbname'   => $dbname,
    'username' => $username,
    'password' => $password,
];
