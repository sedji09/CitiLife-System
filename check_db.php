<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT id, case_number, status, patient_id FROM cases ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query("SELECT * FROM result_disputes ORDER BY id DESC LIMIT 5");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
