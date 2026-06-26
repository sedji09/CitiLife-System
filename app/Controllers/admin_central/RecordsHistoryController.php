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
$source = $_GET['source'] ?? 'profile';
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

        return get_defined_vars();
    }
}
