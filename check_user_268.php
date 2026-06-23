<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Models/CaseModel.php';

$caseModel = new CaseModel($pdo);
$patientUserId = $caseModel->getPatientUserId(268);
echo "Patient User ID for case 268: " . ($patientUserId ? $patientUserId : 'NONE');
