<?php
require_once __DIR__ . '/../../Models/CaseModel.php';
require_once __DIR__ . '/../../Models/PatientModel.php';

$caseModel = new CaseModel($pdo);
$patientModel = new PatientModel($pdo);

$patientNumber = $_GET['patient_number'] ?? '';
$patient = null;
$history = [];

if ($patientNumber) {
    // We need a way to get patient details by patient_number
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_number = ?");
    $stmt->execute([$patientNumber]);
    $patient = $stmt->fetch();

    if ($patient) {
        $history = $caseModel->getPatientHistory($patientNumber);
    }
}
