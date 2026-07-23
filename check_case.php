<?php
require 'config/database.php';
$stmt = $pdo->prepare("SELECT id, case_number, status, patient_id FROM cases WHERE id = 246");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
