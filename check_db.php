<?php
$dbConfig = require 'config/db.php';
require 'Framework/Database.php';
$db = new Framework\Database($dbConfig);
$stmt = $db->conn->query('DESCRIBE users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
