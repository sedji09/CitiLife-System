<?php

namespace App\Controllers\radtech;

use Exception;
use CaseModel;
use NotificationModel;

class PatientDetailsController
{
    /**
     * Handles backend logic for patient details, image uploads, and submission to radiologist.
     *
     * @return array
     */
    public function handle()
    {
        global $pdo;

        $caseModel = new CaseModel($pdo);
        $notificationModel = new NotificationModel($pdo);

        // 1. Ensure Schema
        $caseModel->ensureSchema();

        $caseId = $_GET['id'] ?? 0;
        $errorMsg = '';
        $successMsg = '';
        $branchId = $_SESSION['branch_id'] ?? 1;

        if (!empty($_SESSION['flash_success'])) {
            $successMsg = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        // 2. Handle Submit to Radiologist
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_radiologist'])) {
            try {
                $correctionType = $_POST['correction_type'] ?? 'reupload';
                $radtechDisputeNotes = trim($_POST['radtech_dispute_notes'] ?? '');
                $keepExistingImage = in_array($correctionType, ['typo', 'reread']);

                $submitData = [
                    'exam_type' => $_POST['exam_type'] ?? '',
                    'priority' => $_POST['priority'] ?? '',
                    'clinical_information' => $_POST['clinical_information'] ?? '',
                    'report_template' => $_POST['exam_type'] ?? '',
                    'files' => $_FILES['xray_image'] ?? null,
                    'keep_existing_image' => $keepExistingImage,
                    'radtech_id' => $_SESSION['user_id'] ?? null,
                    'radiologist_id' => $_POST['radiologist_id'] ?? null
                ];

                // Centralized logic handling validation, file uploads, DB updates and Notifications
                $result = $caseModel->processRadTechSubmission($caseId, $submitData, $notificationModel);

                if ($result['success']) {
                    // Check if case was under dispute, update dispute status to Escalated to Radiologist
                    require_once __DIR__ . '/../../Models/ResultDisputeModel.php';
                    $disputeMdl = new \ResultDisputeModel($pdo);
                    $actDispute = $disputeMdl->getActiveDisputeByCase($caseId);
                    if ($actDispute) {
                        $typePrefix = ($correctionType === 'typo') ? '[Typographical Error]' : (($correctionType === 'reread') ? '[Re-reading Request]' : '[New Image Re-uploaded]');
                        $fullNotes = trim($typePrefix . ($radtechDisputeNotes ? ' ' . $radtechDisputeNotes : ''));
                        if (empty($fullNotes)) {
                            $fullNotes = 'RadTech updated and escalated case for Radiologist amendment.';
                        }
                        $disputeMdl->escalateToRadiologist($actDispute['id'], $fullNotes);
                        
                        $caseDetailsForNotif = $caseModel->getCaseById($caseId);
                        if ($caseDetailsForNotif) {
                            $notificationModel->add(
                                "Patient Error Report Escalated",
                                "Case {$caseDetailsForNotif['case_number']} escalated by RadTech ({$typePrefix}) for Radiologist review. " . ($radtechDisputeNotes ? "Notes: {$radtechDisputeNotes}" : ""),
                                "/" . PROJECT_DIR . "/index.php?role=radiologist&page=worklist&tab=disputes&highlight_dispute_case=" . urlencode($caseDetailsForNotif['case_number']),
                                $caseDetailsForNotif['radiologist_id'] ?? null,
                                'radiologist'
                            );
                        }
                    }

                    $_SESSION['flash_success'] = $result['message'];
                    $redirectUrl = "/" . PROJECT_DIR . "/index.php?page=patient-details&id=" . $caseId;
                    if (isset($_GET['from']) && $_GET['from'] === 'disputes') {
                        $redirectUrl .= "&from=disputes";
                    }
                    header("Location: " . $redirectUrl);
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
            $radiologistsList = [];
        } else {
            $caseNotFound = false;

            // Fetch Radiologists with active case count
            $stmtRad = $pdo->prepare("
                SELECT 
                    u.id, 
                    COALESCE(NULLIF(u.full_name_report, ''), NULLIF(u.name, ''), SUBSTRING_INDEX(u.email, '@', 1)) AS radiologist_name,
                    COUNT(c.id) AS active_case_count,
                    u.is_available
                FROM users u
                LEFT JOIN cases c ON u.id = c.radiologist_id AND c.status IN ('Pending', 'Under Reading')
                WHERE u.role = 'radiologist' AND u.status = 'Active'
                GROUP BY u.id
            ");
            $stmtRad->execute();
            $radiologistsList = $stmtRad->fetchAll();

            // 4. Page Logic (Read-only check)
            // If case has an active dispute, allow editing/re-uploading
            require_once __DIR__ . '/../../Models/ResultDisputeModel.php';
            $disputeMdl = new \ResultDisputeModel($pdo);
            $activeDispute = $disputeMdl->getActiveDisputeByCase($caseId);

            $isReadOnly = in_array($caseDetails['status'], ['Pending', 'Under Reading', 'Report Ready', 'Completed'])
                && $caseDetails['image_status'] === 'Uploaded'
                && (!$activeDispute || $activeDispute['status'] !== 'Pending RadTech Review');

            $savedTemplate = $caseDetails['report_template'] ?? '';
        }

        return get_defined_vars();
    }
}
