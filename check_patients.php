<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Models/CaseModel.php';
require_once __DIR__ . '/app/Models/UserModel.php';

$caseModel = new CaseModel($pdo);
$userModel = new UserModel($pdo);

// Get a few recent completed or report ready cases
$stmt = $pdo->query("SELECT id, case_number, patient_id, status FROM cases ORDER BY id DESC LIMIT 5");
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cases as $case) {
    $patientUserId = $caseModel->getPatientUserId($case['id']);
    echo "Case ID: {$case['id']} - Case Number: {$case['case_number']} - Status: {$case['status']}\n";
    if ($patientUserId) {
        $patientUser = $userModel->getUserById($patientUserId);
        echo "  Patient User ID: {$patientUserId}\n";
        if ($patientUser) {
            echo "  Patient Email: " . ($patientUser['email'] ?? 'EMPTY') . "\n";
        } else {
            echo "  Patient User not found in DB.\n";
        }
    } else {
        echo "  No Patient User ID linked to this case.\n";
    }
    echo "--------------------------\n";
}
