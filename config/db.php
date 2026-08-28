<?php
/**
 * Database configuration.
 * For cloud migration, add optional keys: provider, region
 * (host will serve as the database endpoint when not localhost).
 */

return [
    'host'     => getenv('MYSQLHOST') ?: 'localhost',
    'port'     => getenv('MYSQLPORT') ?: '3306',
    'dbname'   => getenv('MYSQLDATABASE') ?: 'citilife_db',
    'username' => getenv('MYSQLUSER') ?: 'root',
    'password' => getenv('MYSQLPASSWORD') ?: '',
    // 'provider' => 'AWS RDS',  // Cloud only
    // 'region'   => 'ap-southeast-1',  // Cloud only
];
