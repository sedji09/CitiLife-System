<?php

namespace App\Controllers\branch_admin;

class BranchXrayCasesController
{
    public function handle()
    {
        global $pdo;


/**
 * BranchXrayCasesController.php
 * Handles backend logic for the Branch Admin's X-ray Cases (Tabbed: Today's Queue & Patient Records).
 */


require_once __DIR__ . '/../../Models/UserModel.php';
$caseModel = new \CaseModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
$userModel = new \UserModel($pdo);

$currentUserId = $_SESSION['user_id'] ?? 0;

// 1. Ensure Schema
$caseModel->ensureSchema();

$successMsg = '';
$errorMsg = '';

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

                $branchId = $_SESSION['branch_id'] ?? 1;
                $patientName = $caseData['first_name'] . ' ' . $caseData['last_name'];
                $details = "Patient: $patientName, Case: {$caseData['case_number']}";
                $auditLogModel->addLog($currentUserId, "Released X-ray report", 'Patient Records', 'Case', $id, $details, $branchId);

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Result is already released.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

$branchId = $_SESSION['branch_id'] ?? 1;

// 3. Fetch Data for both tabs
// Tab 1: Active Queue (Includes Today and Backlogs)
$allQueue = $caseModel->getWorklist($branchId, null, null);
$statusFilter = $_GET['status'] ?? null;
$todayQueue = array_filter($allQueue, function($p) use ($statusFilter) {
    if ($p['released'] != 0 || $p['status'] === 'Rejected') {
        return false;
    }
    if ($statusFilter && $p['status'] !== $statusFilter) {
        return false;
    }
    return true;
});

// Tab 2: Patient Records (Released)
$releasedRecords = $caseModel->getReleasedRecords($branchId);

// 3. Tab State
$currentTab = $_GET['tab'] ?? 'queue';
if (!in_array($currentTab, ['queue', 'records'])) {
    $currentTab = 'queue';
}

        return get_defined_vars();
    }
}
