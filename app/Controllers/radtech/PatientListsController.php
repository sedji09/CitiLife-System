<?php

namespace App\Controllers\radtech;

class PatientListsController
{
    public function handle()
    {
        global $pdo;


/**
 * PatientListsController.php
 * Handles backend logic for the RadTech Today's Queue (Patient List).
 */

require_once __DIR__ . '/../../Models/UserModel.php';
$caseModel = new \CaseModel($pdo);
$notificationModel = new \NotificationModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
$userModel = new \UserModel($pdo);

$currentUserId = $_SESSION['user_id'] ?? 0;

// 1. Ensure Schema
$caseModel->ensureSchema();

$successMsg = '';
$errorMsg = '';

// Handle Flash messages (inherited from redirects)
if (!empty($_SESSION['flash_success'])) {
    $successMsg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// 2. Handle Actions
if (isset($_GET['action'])) {

    if ($_GET['action'] === 'release_and_upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $id = (int) ($_POST['id'] ?? 0);
        $images = json_decode($_POST['images'] ?? '[]', true);

        try {
            $caseData = $caseModel->getCaseById($id);
            if (!$caseData)
                throw new \Exception("Case not found.");

            if ($caseData['released'] == 0) {
                // Save images
                if (!empty($images)) {
                    $uploadDir = __DIR__ . '/../../../public/uploads/reports';
                    if (!is_dir($uploadDir))
                        mkdir($uploadDir, 0777, true);

                    foreach ($images as $index => $base64) {
                        list($type, $data) = explode(';', $base64);
                        list(, $data) = explode(',', $data);
                        $data = base64_decode($data);

                        $pageNum = $index + 1;
                        $filename = $uploadDir . '/' . $caseData['case_number'] . '_page_' . $pageNum . '.jpg';
                        file_put_contents($filename, $data);
                    }
                }

                $caseModel->releaseResult($id);
                $_SESSION['flash_success'] = "Result released. Case moved to X-ray Patient Records.";

                // Log the action
                $branchId = $_SESSION['branch_id'] ?? 1;
                $patientName = $caseData['first_name'] . ' ' . $caseData['last_name'];
                $details = "Patient: $patientName, Case: {$caseData['case_number']}";
                $auditLogModel->addLog($currentUserId, "Released X-ray report", 'Patient Records', 'Case', $id, $details, $branchId);

                $patientUserId = $caseModel->getPatientUserId($id);
                if ($patientUserId) {
                    $notificationModel->add(
                        "Report Released",
                        "Your X-ray report for Case {$caseData['case_number']} has been released. You can now download it.",
                        "/" . PROJECT_DIR . "/my-records?highlight_case={$id}",
                        $patientUserId
                    );

                    // Send Email Notification
                    $patientUser = $userModel->getUserById($patientUserId);
                    if ($patientUser && !empty($patientUser['email'])) {
                        $patientName = $caseData['first_name'] . ' ' . $caseData['last_name'];
                        $subject = "Your X-ray Report is Ready - CitiLife System";
                        $loginUrl = "http://" . $_SERVER['HTTP_HOST'] . "/" . PROJECT_DIR . "/patient-login.php";
                        $body = "
                            <div style='font-family: Arial, sans-serif; color: #333;'>
                                <h2>Hello {$patientName},</h2>
                                <p>Good news! Your X-ray report for Case <strong>{$caseData['case_number']}</strong> has been released and is now ready for viewing or downloading.</p>
                                <p>You can access it by logging into your patient portal:</p>
                                <p><a href='{$loginUrl}' style='display: inline-block; padding: 10px 15px; background-color: #ff0000d3; color: #fff; text-decoration: none; border-radius: 5px;'>Log in to Patient Portal</a></p>
                                <br>
                                <p>Thank you for choosing CitiLife.</p>
                            </div>
                        ";
                        sendEmail($patientUser['email'], $patientName, $subject, $body);
                    }
                }
            }

            echo json_encode(['success' => true]);
            exit;
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // 2B. Standard Release fallback
    if ($_GET['action'] === 'release' && isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        try {
            $caseData = $caseModel->getCaseById($id);

            if ($caseData && $caseData['released'] == 0) {
                $caseModel->releaseResult($id);
                $_SESSION['flash_success'] = "Result released. Case moved to X-ray Patient Records.";

                // Log the action
                $branchId = $_SESSION['branch_id'] ?? 1;
                $patientName = $caseData['first_name'] . ' ' . $caseData['last_name'];
                $details = "Patient: $patientName, Case: {$caseData['case_number']}";
                $auditLogModel->addLog($currentUserId, "Released X-ray report", 'Patient Records', 'Case', $id, $details, $branchId);

                $patientUserId = $caseModel->getPatientUserId($id);
                if ($patientUserId) {
                    $notificationModel->add(
                        "Report Released",
                        "Your X-ray report for Case {$caseData['case_number']} has been released. You can now download it.",
                        "/" . PROJECT_DIR . "/my-records?highlight_case={$id}",
                        $patientUserId
                    );

                    // Send Email Notification
                    $patientUser = $userModel->getUserById($patientUserId);
                    if ($patientUser && !empty($patientUser['email'])) {
                        $patientName = $caseData['first_name'] . ' ' . $caseData['last_name'];
                        $subject = "Your X-ray Report is Ready - CitiLife System";
                        $loginUrl = "http://" . $_SERVER['HTTP_HOST'] . "/" . PROJECT_DIR . "/patient-login.php";
                        $body = "
                            <div style='font-family: Arial, sans-serif; color: #333;'>
                                <h2>Hello {$patientName},</h2>
                                <p>Good news! Your X-ray report for Case <strong>{$caseData['case_number']}</strong> has been released and is now ready for viewing or downloading.</p>
                                <p>You can access it by logging into your patient portal:</p>
                                <p><a href='{$loginUrl}' style='display: inline-block; padding: 10px 15px; background-color: #ff0000d3; color: #fff; text-decoration: none; border-radius: 5px;'>Log in to Patient Portal</a></p>
                                <br>
                                <p>Thank you for choosing CitiLife.</p>
                            </div>
                        ";
                        sendEmail($patientUser['email'], $patientName, $subject, $body);
                    }
                }
            }
            header("Location: /" . PROJECT_DIR . "/index.php?role=radtech&page=patient-lists");
            exit;
        } catch (\Exception $e) {
            $errorMsg = "Failed to release result: " . $e->getMessage();
        }
    }
}

// 3. Fetch and Filter Data
$branchId = $_SESSION['branch_id'] ?? 1;
$allPatients = $caseModel->getWorklist($branchId, null, null);

// Filter logic from original view: Today + Not Released + Approved
$patients = array_filter($allPatients, function ($p) {
    $isToday = date('Y-m-d', strtotime($p['created_at'])) === date('Y-m-d');
    return $p['released'] == 0
        && $p['status'] !== 'Rejected'
        && ($isToday || $p['status'] === 'Report Ready');
});

        return get_defined_vars();
    }
}
