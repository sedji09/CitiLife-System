<?php
require_once __DIR__ . '/../config/database.php';
global $pdo;

echo "<h2>Cases in DB:</h2><pre>";
$stmt = $pdo->query("SELECT c.id, c.case_number, c.status, c.released, c.approval_status, p.first_name, p.last_name, b.name as branch_name 
                     FROM cases c 
                     JOIN patients p ON c.patient_id = p.id 
                     JOIN branches b ON c.branch_id = b.id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

echo "<h2>Requests in DB:</h2><pre>";
$stmt2 = $pdo->query("SELECT * FROM requests");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
