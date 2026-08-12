<?php
$pdo = require 'c:\\xampp\\htdocs\\CitiLife-System\\config\\database.php';
$stmt = $pdo->query("SELECT id, description FROM result_disputes");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    echo "ID: " . $row['id'] . "\n" . "Desc: " . $row['description'] . "\n---\n";
}
?>
