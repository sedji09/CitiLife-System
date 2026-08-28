<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
