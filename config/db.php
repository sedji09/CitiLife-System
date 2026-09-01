<?php
/**
 * Database configuration.
 */

if (file_exists(__DIR__ . '/../env.php')) {
    require_once __DIR__ . '/../env.php';
}

$host = $_ENV['MYSQLHOST'] ?? $_SERVER['MYSQLHOST'] ?? getenv('MYSQLHOST') 
    ?? $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';

$port = $_ENV['MYSQLPORT'] ?? $_SERVER['MYSQLPORT'] ?? getenv('MYSQLPORT') 
    ?? $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';

$dbname = $_ENV['MYSQLDATABASE'] ?? $_SERVER['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE') 
    ?? $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'citilife_db';

$username = $_ENV['MYSQLUSER'] ?? $_SERVER['MYSQLUSER'] ?? getenv('MYSQLUSER') 
    ?? $_ENV['DB_USERNAME'] ?? $_SERVER['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';

$password = $_ENV['MYSQLPASSWORD'] ?? $_SERVER['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') 
    ?? $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

$mysqlUrl = $_ENV['MYSQL_URL'] ?? $_SERVER['MYSQL_URL'] ?? getenv('MYSQL_URL') 
    ?? $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: '';

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
