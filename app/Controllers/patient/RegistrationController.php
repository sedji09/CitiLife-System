<?php

namespace App\Controllers\patient;

class RegistrationController
{
    public function handle()
    {
        global $pdo;


        /**
         * RegistrationController.php
         * Handles backend logic for patient-initiated registration and examination requests.
         */

        $branchModel = new \BranchModel($pdo);
        $patientModel = new \PatientModel($pdo);
        $caseModel = new \CaseModel($pdo);
        $notificationModel = new \NotificationModel($pdo);
        require_once basePath('app/Models/AuditLogModel.php');
        $auditLogModel = new \AuditLogModel($pdo);
        $userId = $_SESSION['user_id'] ?? null;
        $error = '';

        $currentHour = (int) date('G');
        $isClinicOpen = ($currentHour >= 8 && $currentHour < 21);

        // 1. Fetch data
        $branches = $branchModel->getAllBranches();
        $linkedPatient = $patientModel->getPatientByUserId($userId);
        $linkedPatientId = $linkedPatient['id'] ?? null;

        // Fetch System Closing Settings
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('system_status', 'closed_branches', 'closed_message')");
        $dbSettings = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $dbSettings[$row['setting_key']] = $row['setting_value'];
        }

        $systemStatus = $dbSettings['system_status'] ?? 'open';
        $closedBranchesStr = $dbSettings['closed_branches'] ?? '';
        $closedBranchesArr = ($closedBranchesStr === 'all' || $closedBranchesStr === '') ? [$closedBranchesStr] : explode(',', $closedBranchesStr);
        $closedMessage = $dbSettings['closed_message'] ?? 'The system is temporarily closed.';

        // Helper to check if a branch is closed
        $isBranchClosed = function($branchId) use ($systemStatus, $closedBranchesArr) {
            if ($systemStatus !== 'closed') return false;
            if (in_array('all', $closedBranchesArr)) return true;
            if (in_array((string)$branchId, $closedBranchesArr)) return true;
            return false;
        };

        // 2. Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$isClinicOpen) {
                $error = 'The clinic is currently closed. Online requests are only accepted between 8:00 AM and 9:00 PM.';
            } else {
                $formAction = $_POST['form_action'] ?? 'register';

                try {
                    if ($formAction === 'register') {
                        $fullName = trim($_POST['full_name'] ?? '');
                        $nameParts = explode(' ', $fullName, 2);

                        $regData = [
                            'form_mode' => 'new-patient',
                            'user_id' => $userId,
                            'first_name' => $nameParts[0] ?? '',
                            'last_name' => $nameParts[1] ?? '',
                            'birthdate' => $_POST['birthdate'] ?? '',
                            'sex' => $_POST['sex'] ?? 'Male',
                            'contact_number' => trim($_POST['contact'] ?? ''),
                            'branch_id' => (int) ($_POST['branch_id'] ?? 0),
                            'exam_type' => trim($_POST['exam_type'] ?? ''),
                            'philhealth_status' => $_POST['philhealth_status'] ?? 'Without PhilHealth Card',
                            'source' => 'portal'
                        ];

                        if (!$fullName || !$regData['sex'] || empty($regData['birthdate']) || !$regData['branch_id']) {
                            $error = 'Please fill in all required fields.';
                        } elseif ($isBranchClosed($regData['branch_id'])) {
                            $error = 'The selected branch is currently closed for online requests. ' . $closedMessage;
                        } else {
                            $result = $patientModel->processRegistration($regData, $caseModel, $notificationModel);
                            if (isset($result['case_id'])) {
                                $_SESSION['active_status_case_id'] = $result['case_id'];
                                $auditLogModel->addLog(
                                    $userId,
                                    'Submitted X-Ray Request',
                                    'Patient Portal',
                                    'Patient',
                                    $result['patient_id'] ?? $userId,
                                    "Patient requested a new X-ray via portal (New Patient)",
                                    $regData['branch_id']
                                );
                            }
                            header("Location: /" . PROJECT_DIR . "/index.php?role=patient&page=xray-status&registered=1");
                            exit;
                        }

                    } elseif ($formAction === 'request_xray') {
                        $regData = [
                            'form_mode' => 'existing-patient',
                            'patient_id' => $linkedPatientId,
                            'branch_id' => (int) ($_POST['branch_id'] ?? 0),
                            'philhealth_status' => $_POST['philhealth_status'] ?? 'Without PhilHealth Card',
                            'source' => 'portal',
                            'exam_type' => 'To be determined',
                            'priority' => 'Routine'
                        ];

                        if (!$regData['patient_id']) {
                            $error = 'Patient profile not found. Please contact the clinic to link your account before requesting an X-ray.';
                        } elseif (!$regData['branch_id']) {
                            $error = 'Please select a branch.';
                        } elseif ($isBranchClosed($regData['branch_id'])) {
                            $error = 'The selected branch is currently closed for online requests. ' . $closedMessage;
                        } else {
                            $result = $patientModel->processRegistration($regData, $caseModel, $notificationModel);
                            if (isset($result['case_id'])) {
                                $_SESSION['active_status_case_id'] = $result['case_id'];
                                $auditLogModel->addLog(
                                    $userId,
                                    'Submitted X-Ray Request',
                                    'Patient Portal',
                                    'Patient',
                                    $result['patient_id'] ?? $userId,
                                    "Patient requested a new X-ray via portal",
                                    $regData['branch_id']
                                );
                            }
                            header("Location: /" . PROJECT_DIR . "/index.php?role=patient&page=xray-status&registered=1");
                            exit;
                        }
                    }
                } catch (\Exception $e) {
                    $error = 'Failed to process request: ' . $e->getMessage();
                }
            }
        }

        return get_defined_vars();
    }
}
