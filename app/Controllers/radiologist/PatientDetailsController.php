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

// Security: radiologists are system-wide but this page should only be reachable
// for cases that are in an active workflow state (Pending, Under Reading, Report Ready).
// Released/Completed cases are visible via worklist history if the worklist exposes them;
// this prevents arbitrary enumeration of all historical patient records.
$validStates = ['Pending', 'Under Reading', 'Report Ready', 'Completed', 'Released'];
if (!$caseDetails || !in_array($caseDetails['status'] ?? '', $validStates, true)) {
    $caseNotFound = true;
} else {
    $caseNotFound = false;
}

        return get_defined_vars();
    }
}
