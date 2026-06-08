<?php
require_once __DIR__ . '/../../Models/PatientModel.php';
require_once __DIR__ . '/../../Models/BranchModel.php';
require_once __DIR__ . '/../../Models/AuditLogModel.php';

$patientModel = new PatientModel($pdo);
$branchModel = new BranchModel($pdo);
$auditLogModel = new AuditLogModel($pdo);
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentBranchId = $_SESSION['branch_id'] ?? null;

$success = '';
$error = '';

// Handle AJAX/POST actions (e.g., Edit Patient)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $id = $_POST['patient_id'] ?? null;
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'birthdate' => trim($_POST['birthdate'] ?? ''),
            'sex' => $_POST['sex'] ?? '',
            'contact_number' => trim($_POST['contact_number'] ?? '')
        ];

        if ($id && !empty($data['first_name']) && !empty($data['last_name'])) {
            $existingPatient = $patientModel->getPatientById($id);
            $patientBranchId = $existingPatient['branch_id'] ?? null;
            if ($patientModel->updatePatient($id, $data)) {
                $success = "Patient information updated successfully!";
                $details = "Updated patient: " . $data['first_name'] . " " . $data['last_name'];
                $auditLogModel->addLog($currentUserId, "Updated patient record", 'Patient Records', 'Patient', $id, $details, $patientBranchId ?? $currentBranchId);
            } else {
                $error = "Failed to update patient information.";
            }
        } else {
            $error = "Name and required fields cannot be empty.";
        }
    }
}

// Fetch all data
$patients = $patientModel->getAllPatientsWithBranches();
$branches = $branchModel->getAllBranches();
