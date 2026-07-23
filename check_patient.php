<?php
require 'config/database.php';
$stmt = $pdo->prepare("SELECT p.id, p.patient_number, p.email, p.first_name, p.last_name FROM patients p WHERE p.patient_number = 'PAT2026-GAP-00010' OR p.id = 122");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->prepare("SELECT id, case_number, patient_id, status FROM cases WHERE case_number = 'GAP2026-00195'");
$stmt2->execute();
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
