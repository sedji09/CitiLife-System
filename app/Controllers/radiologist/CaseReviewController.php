<?php

namespace App\Controllers\radiologist;

class CaseReviewController
{
    public function handle()
    {
        global $pdo;


/**
 * CaseReviewController.php
 * Handles backend logic for the Radiologist Case Review and reporting interface.
 */

$caseModel = new \CaseModel($pdo);
$notificationModel = new \NotificationModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);

$caseId        = $_GET['id']        ?? 0;
$branchIdQuery = $_GET['branch_id'] ?? 0;
$radiologistId = $_SESSION['user_id'] ?? 1;

$successMsg = '';
$errorMsg   = '';
$isSubmitted = false;

// 1. Pre-fetch case details so branch_id is available for audit logging
$caseDetails = $caseModel->getCaseById($caseId);

if ($caseDetails && $caseDetails['radiologist_id'] != $radiologistId) {
    $caseDetails = false;
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    try {
        $submitData = [
            'clinical_information' => $_POST['clinical_information'] ?? '',
            'exam_reports_arr'     => json_decode($_POST['exam_reports'] ?? '{}', true) ?: []
        ];

        $result = $caseModel->submitRadiologistReport($caseId, $radiologistId, $submitData, $notificationModel);

        if ($result['success']) {
            $successMsg  = $result['message'] . " Radtech can now print and release the result.";
            $isSubmitted = true;

            // Build a meaningful audit log entry
            $patientName = trim(($caseDetails['first_name'] ?? '') . ' ' . ($caseDetails['last_name'] ?? '')) ?: 'Unknown Patient';
            $examList    = implode(', ', array_keys($submitData['exam_reports_arr']));
            $details     = "Patient: {$patientName} | Case #{$caseId} | Exams: {$examList}";

            $auditLogModel->addLog(
                $radiologistId,
                'Submitted Findings Report',
                'Findings & Reports',
                'Case',
                $caseId,
                $details,
                $caseDetails['branch_id'] ?? null
            );

            // Re-fetch to get updated status
            $caseDetails = $caseModel->getCaseById($caseId);
        } else {
            $errorMsg = $result['message'];
        }
    } catch (\Exception $e) {
        $errorMsg = "Failed to submit report: " . $e->getMessage();
    }
}

// 3. $caseDetails already fetched above (pre-fetched before POST handler)

if (!$caseDetails) {
    $caseNotFound = true;
} else {
    $caseNotFound = false;

    // 3. Patient History
    $patientHistory = $caseModel->getPatientHistory($caseDetails['patient_number'], $caseId);

    // 4. Update status to 'Under Reading' if Pending
    if ($caseDetails['status'] === 'Pending') {
        $caseModel->updateStatus($caseId, 'Under Reading');
        $caseDetails['status'] = 'Under Reading';
    }

    $fullName    = htmlspecialchars($caseDetails['first_name'] . ' ' . $caseDetails['last_name']);
    $isCompleted = ($isSubmitted || in_array($caseDetails['status'], ['Report Ready', 'Completed']));

    // ── Parse exam types ──────────────────────────────────────────────────────────
    $examTypeRaw  = $caseDetails['exam_type'] ?? '';
    $examTypes    = array_values(array_filter(array_map('trim', explode(',', $examTypeRaw))));
    if (empty($examTypes)) $examTypes = ['General'];

    // ── Parse saved per-exam reports ─────────────────────────────────────────────
    $savedReports = [];
    $rawFindings  = $caseDetails['findings'] ?? '';
    if ($rawFindings && $rawFindings[0] === '{') {
        $decoded = json_decode($rawFindings, true);
        if (is_array($decoded)) $savedReports = $decoded;
    }
    // Fallback: single-exam — put into first exam slot
    if (empty($savedReports) && count($examTypes) === 1 && $rawFindings) {
        $examKey = $examTypes[0];
        $prefix = "[{$examKey}] ";
        
        // Strip prefix if exists
        if (str_starts_with($rawFindings, $prefix)) {
            $rawFindings = substr($rawFindings, strlen($prefix));
        }
        $rawImpression = $caseDetails['impression'] ?? '';
        if (str_starts_with($rawImpression, $prefix)) {
            $rawImpression = substr($rawImpression, strlen($prefix));
        }

        $savedReports[$examKey] = [
            'findings'   => $rawFindings,
            'impression' => $rawImpression,
        ];
    }

    // ── Parse uploaded images (JSON array or legacy single path) ──────────────────
    $imagePaths = [];
    if (!empty($caseDetails['image_path'])) {
        $decoded = json_decode($caseDetails['image_path'], true);
        if (is_array($decoded)) {
            $imagePaths = $decoded;
        } else {
            $imagePaths = [$caseDetails['image_path']];
        }
    }
}

        return get_defined_vars();
    }
}
