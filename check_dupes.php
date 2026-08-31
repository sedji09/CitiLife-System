<?php
require_once __DIR__ . '/config/database.php';
global $pdo;
$stmt = $pdo->query("SELECT id, patient_id, philhealth_relation, status FROM requests WHERE philhealth_id = '11-111111111-1'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $pdo->query("SELECT id, request_id, philhealth_relation, status FROM cases WHERE philhealth_id = '11-111111111-1'");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
