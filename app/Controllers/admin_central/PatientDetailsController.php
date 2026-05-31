<?php
require_once __DIR__ . '/../../Models/PatientModel.php';
require_once __DIR__ . '/../../Models/CaseModel.php';

$patientModel = new PatientModel($pdo);
$caseModel = new CaseModel($pdo);

$patientId = $_GET['id'] ?? 0;
$patient = $patientModel->getPatientById($patientId);

if (!$patient) {
    // Fallback if accessed by ID that doesn't exist
    header("Location: /" . PROJECT_DIR . "/index.php?page=patient-records");
    exit;
}

// Fetch latest case just for context if any
$latestCase = $caseModel->getLatestCaseByPatient($patientId);
