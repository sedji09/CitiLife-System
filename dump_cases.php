<?php
require 'config/database.php';
$stmt = $pdo->query("SHOW CREATE TABLE cases");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'] . "\n";
