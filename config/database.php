<?php
/**
 * Compatibility Bridge for legacy views
 */
global $pdo;

if (!isset($pdo)) {
    $dbConfig = require __DIR__ . '/db.php';
    try {
        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
            $dbConfig['username'],
            $dbConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        die("Database connection failed in bridge: " . $e->getMessage());
    }
}
return $pdo;
