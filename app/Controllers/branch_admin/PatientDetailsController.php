<?php

namespace App\Controllers\branch_admin;

class PatientDetailsController
{
    public function handle()
    {
        global $pdo;


        /**
         * PatientDetailsController.php
         * Handles backend logic for patient details, image uploads, and submission to radiologist.
         */


        $caseModel = new \CaseModel($pdo);
        $notificationModel = new \NotificationModel($pdo);

        // 1. Ensure Schema
        $caseModel->ensureSchema();

        $refToken = $_GET['ref'] ?? $_GET['token'] ?? '';
        $rawId = $_GET['id'] ?? 0;
        $caseId = 0;
        if (!empty($refToken) && function_exists('verifyReportToken')) {
            $caseId = verifyReportToken($refToken);
        }
        if (!$caseId && $rawId) {
            $caseId = (int)$rawId;
        }

        if ($caseId > 0) {
            $_SESSION['active_branch_admin_case_id'] = $caseId;
            if (!empty($_GET['from'])) {
                $_SESSION['active_branch_admin_from'] = $_GET['from'];
            }
        } else {
            $caseId = (int)($_SESSION['active_branch_admin_case_id'] ?? 0);
        }

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

        return get_defined_vars();
    }
}
