<?php
/**
 * RegistrationController.php
 * Handles backend logic for patient-initiated registration and examination requests.
 */

require_once __DIR__ . '/../../Models/BranchModel.php';
require_once __DIR__ . '/../../Models/PatientModel.php';
require_once __DIR__ . '/../../Models/CaseModel.php';
require_once __DIR__ . '/../../Models/NotificationModel.php';

$branchModel = new \BranchModel($pdo);
$patientModel = new \PatientModel($pdo);
$caseModel = new \CaseModel($pdo);
$notificationModel = new \NotificationModel($pdo);

$userId = $_SESSION['user_id'] ?? null;
$error = '';

// 1. Fetch data
$branches = $branchModel->getAllBranches();
$linkedPatient = $patientModel->getPatientByUserId($userId);
$linkedPatientId = $linkedPatient['id'] ?? null;

// 2. Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? 'register';

    try {
        if ($formAction === 'register') {
            $fullName = trim($_POST['full_name'] ?? '');
            $nameParts = explode(' ', $fullName, 2);
            
            $regData = [
                'form_mode'         => 'new-patient',
                'user_id'           => $userId,
                'first_name'        => $nameParts[0] ?? '',
                'last_name'         => $nameParts[1] ?? '',
                'birthdate'         => $_POST['birthdate'] ?? '',
                'sex'               => $_POST['sex'] ?? 'Male',
                'contact_number'    => trim($_POST['contact'] ?? ''),
                'branch_id'         => (int) ($_POST['branch_id'] ?? 0),
                'exam_type'         => trim($_POST['exam_type'] ?? ''),
                'philhealth_status' => $_POST['philhealth_status'] ?? 'Without PhilHealth Card',
                'source'            => 'portal'
            ];

            if (!$fullName || !$regData['sex'] || empty($regData['birthdate']) || !$regData['branch_id']) {
                $error = 'Please fill in all required fields.';
            } else {
                $result = $patientModel->processRegistration($regData, $caseModel, $notificationModel);
                if (isset($result['case_id'])) {
                    $_SESSION['active_status_case_id'] = $result['case_id'];
                }
                header("Location: /" . PROJECT_DIR . "/index.php?role=patient&page=xray-status&registered=1");
                exit;
            }

        } elseif ($formAction === 'request_xray') {
            $regData = [
                'form_mode'         => 'existing-patient',
                'patient_id'        => $linkedPatientId,
                'branch_id'         => (int) ($_POST['branch_id'] ?? 0),
                'philhealth_status' => $_POST['philhealth_status'] ?? 'Without PhilHealth Card',
                'source'            => 'portal',
                'exam_type'         => 'To be determined',
                'priority'          => 'Routine'
            ];

            if (!$regData['patient_id']) {
                $error = 'Patient profile not found. Please contact the clinic to link your account before requesting an X-ray.';
            } elseif (!$regData['branch_id']) {
                $error = 'Please select a branch.';
            } else {
                $result = $patientModel->processRegistration($regData, $caseModel, $notificationModel);
                if (isset($result['case_id'])) {
                    $_SESSION['active_status_case_id'] = $result['case_id'];
                }
                header("Location: /" . PROJECT_DIR . "/index.php?role=patient&page=xray-status&registered=1");
                exit;
            }
        }
    } catch (Exception $e) {
        $error = 'Failed to process request: ' . $e->getMessage();
    }
}
