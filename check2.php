<?php
require 'config/database.php';
$stmt1 = $pdo->query('SHOW COLUMNS FROM users');
print_r($stmt1->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $pdo->query('SHOW COLUMNS FROM patients');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
