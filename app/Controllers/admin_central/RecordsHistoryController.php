<?php

namespace App\Controllers\admin_central;

class RecordsHistoryController
{
    public function handle()
    {
        global $pdo;



$caseModel = new \CaseModel($pdo);
$patientModel = new \PatientModel($pdo);

$patientNumber = $_GET['patient_number'] ?? '';
$patientId = $_GET['id'] ?? null;
$source = $_GET['source'] ?? 'profile';
$patient = null;
$history = [];

if ($patientNumber) {
    // Get patient details by patient_number
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_number = ?");
    $stmt->execute([$patientNumber]);
    $patient = $stmt->fetch();
} elseif ($patientId) {
    // Get patient details by id
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    if ($patient) {
        $patientNumber = $patient['patient_number'];
    }
}

if ($patient && $patientNumber) {
    $history = $caseModel->getPatientHistory($patientNumber);
}

        return get_defined_vars();
    }
}
