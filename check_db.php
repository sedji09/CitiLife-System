<?php
$dbConfig = require 'config/db.php';
require 'Framework/Database.php';
$db = new Framework\Database($dbConfig);
$stmt = $db->conn->query('SELECT id, proof_of_payment_path FROM payments ORDER BY id DESC LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
