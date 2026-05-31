<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/CaseModel.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientModel = new PatientModel($pdo);
    $caseModel = new CaseModel($pdo);

    $caseId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
    $sex = isset($_POST['sex']) ? $_POST['sex'] : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $philhealth = isset($_POST['philhealth']) ? $_POST['philhealth'] : '';
    $philhealthId = isset($_POST['philhealth_id']) ? trim($_POST['philhealth_id']) : '';

    // Extract first and last name
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    if ($caseId && $firstName && $age && $sex && $contact) {
        try {
            $branchId = $_SESSION['branch_id'] ?? 1;
            $case = $caseModel->getCaseById($caseId);

            if ($case && $case['branch_id'] == $branchId) {
                // 1. Update patient info
                $patientData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'age' => $age,
                    'sex' => $sex,
                    'contact_number' => $contact
                ];
                $patientModel->updatePatient($case['patient_id'], $patientData);

                // 2. Update case PhilHealth info
                $caseModel->updateCasePhilHealth($caseId, $philhealth, $philhealthId);

                header('Location: /' . PROJECT_DIR . '/index.php?role=radtech&page=patient-approval&success=1');
                exit;
            }
        } catch (Exception $e) {
            header('Location: /' . PROJECT_DIR . '/index.php?role=radtech&page=patient-approval&error=1');
            exit;
        }
    }
}

header('Location: /' . PROJECT_DIR . '/index.php?role=radtech&page=patient-approval');
exit;
?>
