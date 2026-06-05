<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Models/PatientModel.php';
require_once __DIR__ . '/app/Models/CaseModel.php';
require_once __DIR__ . '/app/Models/NotificationModel.php';

$patientModel = new \PatientModel($pdo);
$caseModel = new \CaseModel($pdo);
$notificationModel = new \NotificationModel($pdo);

// Find a patient
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'patient' AND patient_id IS NOT NULL LIMIT 1");
$user = $stmt->fetch();
if (!$user) {
    die("No patient user found.\n");
}

echo "User: " . $user['email'] . "\n";
echo "Patient ID: " . $user['patient_id'] . "\n";

$regData = [
    'form_mode' => 'existing-patient',
    'patient_id' => $user['patient_id'],
    'branch_id' => 1,
    'philhealth_status' => 'Without PhilHealth Card',
    'source' => 'portal',
    'exam_type' => 'To be determined',
    'priority' => 'Routine'
];

try {
    $result = $patientModel->processRegistration($regData, $caseModel, $notificationModel);
    echo "Success: ";
    print_r($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
