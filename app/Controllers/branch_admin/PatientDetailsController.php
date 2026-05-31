<?php
/**
 * PatientDetailsController.php
 * Handles backend logic for patient details, image uploads, and submission to radiologist.
 */

require_once __DIR__ . '/../../Models/CaseModel.php';
require_once __DIR__ . '/../../Models/NotificationModel.php';

$caseModel = new \CaseModel($pdo);
$notificationModel = new \NotificationModel($pdo);

// 1. Ensure Schema
$caseModel->ensureSchema();

$caseId = $_GET['id'] ?? 0;
$errorMsg = '';
$branchId = $_SESSION['branch_id'] ?? 1;

// Branch Admins have strictly read-only access to patient details
$isReadOnly = true;

// 3. Fetch Case & Patient Details
$caseDetails = $caseModel->getCaseById($caseId);

if (!$caseDetails || $caseDetails['branch_id'] != $branchId) {
    // We let the view handle the missing case message or redirect
    $caseNotFound = true;
} else {
    $caseNotFound = false;
    
    $savedTemplate = $caseDetails['report_template'] ?? '';
}
