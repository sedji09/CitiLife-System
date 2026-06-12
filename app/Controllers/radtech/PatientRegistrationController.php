<?php
/**
 * PatientRegistrationController.php
 * Handles backend logic for walk-in patient registration and returning patient lookups.
 */

require_once __DIR__ . '/../../Models/PatientModel.php';
require_once __DIR__ . '/../../Models/CaseModel.php';
require_once __DIR__ . '/../../Models/AuditLogModel.php';

$patientModel = new \PatientModel($pdo);
$caseModel = new \CaseModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
$currentUserId = $_SESSION['user_id'] ?? 0;

// --- 1. AJAX Endpoints ---
if (isset($_GET['ajax_search'])) {
    header('Content-Type: application/json');
    $query = trim($_GET['q'] ?? '');
    
    // Logic from former search-patient-ajax.php consolidated here
    if (strlen($query) < 2) {
        echo json_encode([]);
    } else {
        try {
            $results = $patientModel->searchPatients($query);
            echo json_encode($results);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    exit;
}

// --- 2. Form processing ---
$branchId = $_SESSION['branch_id'] ?? 1;
$success = false;
$error = '';
$generatedCaseNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $regData = [
            'form_mode'         => $_POST['form-mode'] ?? 'new-patient',
            'patient_id'        => $_POST['existing-patient-id'] ?? null,
            'first_name'        => trim($_POST['first-name'] ?? ''),
            'middle_name'       => trim($_POST['middle-name'] ?? ''),
            'last_name'         => trim($_POST['last-name'] ?? ''),
            'birthdate'         => $_POST['birthdate'] ?? '',
            'sex'               => $_POST['sex'] ?? 'Male',
            'contact_number'    => str_replace(['-', ' '], '', trim($_POST['contact'] ?? '')),
            'email'             => trim($_POST['email'] ?? ''),
            'home_address'      => trim($_POST['home_address'] ?? ''),
            'branch_id'         => $branchId,
            'exam_type'         => $_POST['exam-type'] ?? '',
            'priority'          => $_POST['priority'] ?? 'Routine',
            'philhealth_status' => $_POST['card'] ?? 'Without PhilHealth Card',
            'philhealth_id'     => trim($_POST['id-number'] ?? ''),
            'source'            => 'radtech',
            'approval_status'   => 'Approved'
        ];

        // Validation moved from View to Controller
        if (empty($regData['exam_type'])) {
            $error = "Please select at least one Exam Type.";
        } elseif ($regData['form_mode'] === 'new-patient' && (!$regData['first_name'] || !$regData['last_name'] || empty($regData['birthdate']))) {
            $error = "Please fill in all required patient fields.";
        } elseif ($regData['form_mode'] === 'new-patient' && !preg_match('/^[0-9]{11}$/', $regData['contact_number'])) {
            $error = "Contact Number must be exactly 11 digits.";
        } else {
            // --- IDEMPOTENCY CHECK ---
            // Prevent duplicate submissions within 60 seconds
            $submissionHash = md5(json_encode([
                'mode' => $regData['form_mode'],
                'pid'  => $regData['patient_id'],
                'fn'   => $regData['first_name'],
                'ln'   => $regData['last_name'],
                'exam' => $regData['exam_type']
            ]));

            if (isset($_SESSION['last_reg_hash']) && $_SESSION['last_reg_hash'] === $submissionHash && 
                (time() - ($_SESSION['last_reg_time'] ?? 0) < 60)) {
                
                // If it's the exact same submission within 60s, silently redirect to the success state 
                // if it exists, or just redirect back to prevent double processing.
                header("Location: index.php?role=radtech&page=patient-registration");
                exit;
            }

            $result = $patientModel->processRegistration($regData, $caseModel, null);
            
            // For returning patients, the POST might not have first/last name. Fetch them for the log.
            $logFirstName = $regData['first_name'];
            $logLastName = $regData['last_name'];
            if ($regData['form_mode'] === 'existing-patient' && $regData['patient_id']) {
                $p = $patientModel->getPatientById($regData['patient_id']);
                if ($p) {
                    $logFirstName = $p['first_name'];
                    $logLastName = $p['last_name'];
                }
            }

            $logAction = ($regData['form_mode'] === 'new-patient') ? "Registered new patient" : "Registered returning patient";
            $details = "Patient: {$logFirstName} {$logLastName}, Case Number: {$result['case_number']}";
            $auditLogModel->addLog($currentUserId, $logAction, 'Patient Records', 'Case', $result['case_id'] ?? null, $details, $branchId);

            // Store idempotency data
            $_SESSION['last_reg_hash'] = $submissionHash;
            $_SESSION['last_reg_time'] = time();

            // PRG Pattern: Store success in session and redirect
            $_SESSION['registration_success'] = [
                'case_number' => $result['case_number'],
                'patient_name' => "{$logFirstName} {$logLastName}",
                'message' => ($regData['form_mode'] === 'new-patient') ? "Patient registered successfully." : "Case created successfully."
            ];
            
            header("Location: index.php?role=radtech&page=patient-registration");
            exit;
        }
    } catch (Exception $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
