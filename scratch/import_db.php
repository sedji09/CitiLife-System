<?php
$host = 'altaria.proxy.rlwy.net';
$port = '55543';
$dbname = 'railway';
$user = 'root';
$pass = 'VyiltAwMoNyKNYqcHdYlSvaNZkqMXnSo';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
try {
    echo "Connecting to Railway MySQL...\n";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    
    $sqlFile = __DIR__ . '/../storage/backups/citilife_db_2026-09-01_13-55-51.sql';
    echo "Reading SQL file: $sqlFile\n";
    $sql = file_get_contents($sqlFile);
    
    echo "Executing SQL import...\n";
    $pdo->exec($sql);
    
    echo "SUCCESS! Database imported perfectly to Railway!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
