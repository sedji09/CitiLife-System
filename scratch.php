<?php
$dbConfig = require 'config/db.php';
$pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active' AND last_activity >= NOW() - INTERVAL 15 MINUTE");
echo "Count: " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->query("SELECT id, email, last_activity, NOW() as current_db_time FROM users WHERE status = 'Active' AND last_activity >= NOW() - INTERVAL 15 MINUTE");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
