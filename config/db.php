<?php
/**
 * Database configuration.
 * For cloud migration, add optional keys: provider, region
 * (host will serve as the database endpoint when not localhost).
 */

return [
    'host'     => $_ENV['MYSQLHOST'] ?? $_SERVER['MYSQLHOST'] ?? 'localhost',
    'port'     => $_ENV['MYSQLPORT'] ?? $_SERVER['MYSQLPORT'] ?? '3306',
    'dbname'   => $_ENV['MYSQLDATABASE'] ?? $_SERVER['MYSQLDATABASE'] ?? 'citilife_db',
    'username' => $_ENV['MYSQLUSER'] ?? $_SERVER['MYSQLUSER'] ?? 'root',
    'password' => $_ENV['MYSQLPASSWORD'] ?? $_SERVER['MYSQLPASSWORD'] ?? '',
    // 'provider' => 'AWS RDS',  // Cloud only
    // 'region'   => 'ap-southeast-1',  // Cloud only
];
