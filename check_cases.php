<?php
require 'config/database.php';
$stmt = $pdo->query('SHOW COLUMNS FROM cases');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
