<?php
require 'config/database.php';
$stmt = $pdo->query('SELECT id, email, name, role FROM users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
