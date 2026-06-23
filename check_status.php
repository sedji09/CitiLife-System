<?php
require_once __DIR__ . '/config/database.php';
$stmt = $pdo->query("SELECT id, case_number, patient_id, status, released, approval_status FROM cases ORDER BY id DESC LIMIT 5");
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cases as $case) {
    echo "Case ID: {$case['id']} - Case Number: {$case['case_number']} - Status: {$case['status']} - Released: {$case['released']} - Approval: {$case['approval_status']}\n";
}
