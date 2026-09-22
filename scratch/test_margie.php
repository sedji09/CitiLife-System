<?php
require 'config/database.php';
global $pdo;
$stmt = $pdo->query("SELECT c.id, c.status, c.released, c.approval_status, p.first_name, p.last_name, b.name as branch_name 
                     FROM cases c JOIN patients p ON c.patient_id = p.id JOIN branches b ON c.branch_id = b.id 
                     WHERE p.first_name LIKE '%Margie%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
