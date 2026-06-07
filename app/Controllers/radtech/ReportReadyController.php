<?php
/**
 * ReportReadyController.php
 * Handles backend logic for the RadTech Report Ready tab.
 */

require_once __DIR__ . '/../../Models/CaseModel.php';
require_once __DIR__ . '/../../Models/NotificationModel.php';
require_once __DIR__ . '/../../Models/AuditLogModel.php';

$caseModel = new \CaseModel($pdo);
$notificationModel = new \NotificationModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
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

    // 2A. Release and Upload Photos via AJAX
    if ($_GET['action'] === 'release_and_upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $id = (int) ($_POST['id'] ?? 0);
        $images = json_decode($_POST['images'] ?? '[]', true);

        try {
            $caseData = $caseModel->getCaseById($id);
            if (!$caseData)
                throw new Exception("Case not found.");

            if ($caseData['released'] == 0) {
                // Save images
                if (!empty($images)) {
                    $uploadDir = __DIR__ . '/../../../public/uploads/reports';
                    if (!is_dir($uploadDir))
                        mkdir($uploadDir, 0777, true);

                    // Clean up any existing report pages for this case
                    $existingPhotos = glob($uploadDir . '/' . $caseData['case_number'] . '_page_*.jpg');
                    if ($existingPhotos) {
                        foreach ($existingPhotos as $photo) {
                            if (file_exists($photo)) {
                                unlink($photo);
                            }
                        }
                    }

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
                }
            }

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
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
                }
            }
            header("Location: /" . PROJECT_DIR . "/index.php?role=radtech&page=report-ready");
            exit;
        } catch (Exception $e) {
            $errorMsg = "Failed to release result: " . $e->getMessage();
        }
    }
}

// 3. Fetch Data
$branchId = $_SESSION['branch_id'] ?? 1;
$allPatients = $caseModel->getWorklist($branchId, null, null);

// Filter logic: Not Released + Approved + Status is Report Ready
$patients = array_filter($allPatients, function ($p) {
    return $p['released'] == 0
        && $p['approval_status'] === 'Approved'
        && $p['status'] === 'Report Ready';
});
