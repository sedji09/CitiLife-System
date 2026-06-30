<?php
require 'config/database.php';

$stmt = $pdo->query('DESCRIBE system_settings');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query('SELECT * FROM system_settings');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
