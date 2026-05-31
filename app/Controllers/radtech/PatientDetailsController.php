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

// 2. Handle Submit to Radiologist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_radiologist'])) {
    try {
        $submitData = [
            'exam_type' => $_POST['exam_type'] ?? '',
            'priority' => $_POST['priority'] ?? '',
            'report_template' => $_POST['exam_type'] ?? '',
            'files' => $_FILES['xray_image'] ?? null,
            'radtech_id' => $_SESSION['user_id'] ?? null
        ];

        // Centralized logic handling validation, file uploads, DB updates and Notifications
        $result = $caseModel->processRadTechSubmission($caseId, $submitData, $notificationModel);
        
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
            header("Location: /" . PROJECT_DIR . "/index.php?role=radtech&page=patient-lists");
            exit;
        } else {
            $errorMsg = $result['message'];
        }
    } catch (Exception $e) {
        $errorMsg = "Error: " . $e->getMessage();
    }
}

// 3. Fetch Case & Patient Details
$caseDetails = $caseModel->getCaseById($caseId);

if (!$caseDetails || $caseDetails['branch_id'] != $branchId) {
    // We let the view handle the missing case message or redirect
    $caseNotFound = true;
} else {
    $caseNotFound = false;
    
    // 4. Page Logic (Read-only check)
    $isReadOnly = in_array($caseDetails['status'], ['Pending', 'Under Reading', 'Report Ready', 'Completed'])
        && $caseDetails['image_status'] === 'Uploaded';

    $savedTemplate = $caseDetails['report_template'] ?? '';
}
