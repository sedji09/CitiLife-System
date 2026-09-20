<?php
// scripts/import_db.php

$host = 'altaria.proxy.rlwy.net';
$port = 39185;
$user = 'root';
$dbname = 'railway';

echo "===========================================\n";
echo "   CitiLife System - Railway DB Importer   \n";
echo "===========================================\n";
echo "Host:     $host\n";
echo "Port:     $port\n";
echo "Database: $dbname\n";
echo "User:     $user\n";
echo "-------------------------------------------\n";

$password = $argv[1] ?? null;

if (!$password) {
    echo "Enter Railway MySQL Password: ";
    $handle = fopen("php://stdin", "r");
    $password = trim(fgets($handle));
    fclose($handle);
}

if (empty($password)) {
    die("Error: Password cannot be empty.\n");
}

echo "\nConnecting to Railway MySQL...\n";
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo " Connected successfully!\n";
} catch (PDOException $e) {
    die(" Connection failed: " . $e->getMessage() . "\n");
}

$sqlFile = null;
$backupDir = __DIR__ . '/../storage/backups/';
if (is_dir($backupDir)) {
    $files = glob($backupDir . '*.sql');
    if (!empty($files)) {
        rsort($files);
        $sqlFile = $files[0];
    }
}
if (!$sqlFile || !file_exists($sqlFile)) {
    $sqlFile = __DIR__ . '/../schema_only.sql';
}

if (!file_exists($sqlFile)) {
    die("Error: No SQL file found in storage/backups/ or root.\n");
}

echo "Reading SQL file: " . basename($sqlFile) . "...\n";
$raw = file_get_contents($sqlFile);

// If file is UTF-16LE, convert to clean UTF-8
if (substr($raw, 0, 2) === "\xFF\xFE") {
    echo "Converting UTF-16LE to clean UTF-8...\n";
    $raw = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
}

// Strip UTF-8 BOM if present
$raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

$pdo->exec("SET NAMES utf8mb4;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

echo "Importing statements into Railway...\n";

$lines = explode("\n", $raw);
$query = '';
$count = 0;

foreach ($lines as $line) {
    $trimmed = trim($line);

    // Skip empty lines or pure single-line comment lines
    if ($trimmed === '' || str_starts_with($trimmed, '--') || (str_starts_with($trimmed, '/*') && str_ends_with($trimmed, '*/;'))) {
        continue;
    }

    $query .= $line . "\n";

    // When line ends with semicolon, it marks the end of a statement
    if (str_ends_with($trimmed, ';')) {
        try {
            $pdo->exec($query);
            $count++;
            if ($count % 25 === 0) {
                echo "  Imported $count statements...\r";
            }
        } catch (PDOException $e) {
            $errMsg = $e->getMessage();
            // Don't halt on non-fatal drops
            if (str_contains($errMsg, 'Unknown table') || str_contains($errMsg, 'already exists')) {
                // ignore
            } else {
                echo "\nNotice: " . substr(trim($query), 0, 70) . "... -> " . $errMsg . "\n";
            }
        }
        $query = '';
    }
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "\n\n===========================================\n";
echo " SUCCESS! Database import completed ($count statements)!\n";
echo " All tables & data are now active in Railway.\n";
echo "===========================================\n";
