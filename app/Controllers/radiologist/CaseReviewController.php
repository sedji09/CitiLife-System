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

$caseId = $_GET['id'] ?? 0;
$branchIdQuery = $_GET['branch_id'] ?? 0;
$radiologistId = $_SESSION['user_id'] ?? 1;

$successMsg = '';
$errorMsg = '';
$isSubmitted = false;

// 1. Pre-fetch case details so branch_id is available for audit logging
$caseDetails = $caseModel->getCaseById($caseId);

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_draft']) || isset($_POST['submit_final'])) {
        $isFinal = isset($_POST['submit_final']);
        try {
            $submitData = [
                'clinical_information' => $_POST['clinical_information'] ?? '',
                'exam_reports_arr' => json_decode($_POST['exam_reports'] ?? '{}', true) ?: []
            ];

            $result = $caseModel->submitRadiologistReport($caseId, $radiologistId, $submitData, $notificationModel, $isFinal);

            if ($result['success']) {
                $isSubmitted = true;
                $successMsg = $isFinal ? "Report successfully finalized." : "Draft successfully saved.";

                if ($isFinal) {
                    require_once __DIR__ . '/../../Helpers/pdf_generator_helper.php';
                    generateAndSaveReportPdf($caseId, $pdo);
                }

                // Build a meaningful audit log entry
                $patientName = trim(($caseDetails['first_name'] ?? '') . ' ' . ($caseDetails['last_name'] ?? '')) ?: 'Unknown Patient';
                $examList = implode(', ', array_keys($submitData['exam_reports_arr']));
                $details = "Patient: {$patientName} | Case #{$caseId} | Exams: {$examList}";

                $action = $isFinal ? 'Submitted Final Findings Report' : 'Saved Draft Findings Report';
                $auditLogModel->addLog(
                    $radiologistId,
                    $action,
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
            $errorMsg = "Failed to " . ($isFinal ? "submit final report: " : "save draft: ") . $e->getMessage();
        }
    } elseif (isset($_POST['revert_to_draft'])) {
        try {
            // we will implement revertToDraft in CaseModel
            $caseModel->revertToDraft($caseId);

            $patientName = trim(($caseDetails['first_name'] ?? '') . ' ' . ($caseDetails['last_name'] ?? '')) ?: 'Unknown Patient';
            
            $auditLogModel->addLog(
                $radiologistId,
                'Reverted Findings Report to Draft',
                'Report Correction',
                'Case',
                $caseId,
                "Patient: {$patientName} | Case #{$caseId} | Reverted to draft for editing",
                $caseDetails['branch_id'] ?? null
            );

            // Re-fetch to get updated status
            $caseDetails = $caseModel->getCaseById($caseId);
            $successMsg = "Report reverted to draft. You can now edit the findings.";
        } catch (\Exception $e) {
            $errorMsg = "Failed to revert report: " . $e->getMessage();
        }
    }
}

// 3. $caseDetails already fetched above (pre-fetched before POST handler)

if (!$caseDetails) {
    $caseNotFound = true;
} else {
    // Security: block write operations on already-released/completed cases unless
    // they have an active dispute workflow in progress.
    $terminalStatuses = ['Released', 'Completed'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($caseDetails['status'], $terminalStatuses, true)) {
        require_once __DIR__ . '/../../Models/ResultDisputeModel.php';
        $disputeCheck = new \ResultDisputeModel($pdo);
        $ongoingDispute = $disputeCheck->getActiveDisputeByCase($caseId);
        if (!$ongoingDispute) {
            $errorMsg = 'This case has already been released and cannot be modified.';
        }
    }

    $caseNotFound = false;

    // 3. Patient History (Completed/Report Ready only)
    $patientHistory = $caseModel->getPatientHistory($caseDetails['patient_number'], $caseId, null, 0, true);

    // 4. Update status to 'Under Reading' if Pending
    if ($caseDetails['status'] === 'Pending') {
        $caseModel->updateStatus($caseId, 'Under Reading');
        $caseDetails['status'] = 'Under Reading';
    }

    $fullName = htmlspecialchars($caseDetails['first_name'] . ' ' . $caseDetails['last_name']);
    $isCompleted = (($caseDetails['report_status'] ?? '') === 'Final' || in_array($caseDetails['status'], ['Report Ready', 'Completed', 'Released']));
    $isDraftLocked = (!$isCompleted && ($caseDetails['report_status'] ?? '') === 'Draft' && !empty($caseDetails['findings']));

    // ── Parse exam types ──────────────────────────────────────────────────────────
    $examTypeRaw = $caseDetails['exam_type'] ?? '';
    $examTypes = array_values(array_filter(array_map('trim', explode(',', $examTypeRaw))));
    if (empty($examTypes))
        $examTypes = ['General'];

    // ── Parse saved per-exam reports ─────────────────────────────────────────────
    $savedReports = [];
    $rawFindings = $caseDetails['findings'] ?? '';
    if ($rawFindings && $rawFindings[0] === '{') {
        $decoded = json_decode($rawFindings, true);
        if (is_array($decoded))
            $savedReports = $decoded;
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
            'findings' => $rawFindings,
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
