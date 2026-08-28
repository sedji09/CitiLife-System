<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT * FROM requests ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
