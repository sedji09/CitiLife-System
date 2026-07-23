<?php
require 'config/database.php';
$stmt = $pdo->query("SHOW COLUMNS FROM cases LIKE 'status'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
