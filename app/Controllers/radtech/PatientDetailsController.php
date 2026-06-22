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
        $branchId = $_SESSION['branch_id'] ?? 1;

        // 2. Handle Submit to Radiologist
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_radiologist'])) {
            try {
                $submitData = [
                    'exam_type' => $_POST['exam_type'] ?? '',
                    'priority' => $_POST['priority'] ?? '',
                    'clinical_information' => $_POST['clinical_information'] ?? '',
                    'report_template' => $_POST['exam_type'] ?? '',
                    'files' => $_FILES['xray_image'] ?? null,
                    'radtech_id' => $_SESSION['user_id'] ?? null,
                    'radiologist_id' => $_POST['radiologist_id'] ?? null
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
            $isReadOnly = in_array($caseDetails['status'], ['Pending', 'Under Reading', 'Report Ready', 'Completed'])
                && $caseDetails['image_status'] === 'Uploaded';

            $savedTemplate = $caseDetails['report_template'] ?? '';
        }

        return get_defined_vars();
    }
}
