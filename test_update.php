<?php
require 'config/database.php';
$stmt = $pdo->prepare("UPDATE cases SET status = ? WHERE id = ?");
$stmt->execute(['Released', 246]);
echo "Rows updated: " . $stmt->rowCount() . "\n";

$check = $pdo->query("SELECT id, case_number, status FROM cases WHERE id = 246")->fetch(PDO::FETCH_ASSOC);
print_r($check);
