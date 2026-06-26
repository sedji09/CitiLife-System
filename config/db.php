<?php
/**
 * Database configuration.
 * For cloud migration, add optional keys: provider, region
 * (host will serve as the database endpoint when not localhost).
 */

return [
    'host'     => 'localhost',
    'port'     => '3306',
    'dbname'   => 'citilife_db',
    'username' => 'root',
    'password' => '',
    // 'provider' => 'AWS RDS',  // Cloud only
    // 'region'   => 'ap-southeast-1',  // Cloud only
];
