<?php
require 'config/database.php';
$stmt = $pdo->query('SELECT * FROM requests WHERE id = 54');
echo "Request 54:\n";
print_r($stmt->fetch(PDO::FETCH_ASSOC));
$stmt = $pdo->query('SELECT * FROM payments WHERE request_id = 54');
echo "\nPayments for Request 54:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
