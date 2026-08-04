<?php
require __DIR__ . '/config/database.php';
$stmt = $pdo->query("SELECT * FROM branches WHERE name = 'Gapan'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
