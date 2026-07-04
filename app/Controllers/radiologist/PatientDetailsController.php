<?php

namespace App\Controllers\radiologist;

class PatientDetailsController
{
    public function handle()
    {
        global $pdo;


/**
 * PatientDetailsController.php
 * Handles backend logic for patient details view for radiologist.
 */


$caseModel = new \CaseModel($pdo);

// 1. Ensure Schema
$caseModel->ensureSchema();

$caseId = $_GET['id'] ?? 0;
$errorMsg = '';

// Radiologists have strictly read-only access to patient details
$isReadOnly = true;

// 3. Fetch Case & Patient Details
$caseDetails = $caseModel->getCaseById($caseId);

if (!$caseDetails) {
    // We let the view handle the missing case message or redirect
    $caseNotFound = true;
} else {
    $caseNotFound = false;
}

        return get_defined_vars();
    }
}
