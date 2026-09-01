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

            // Security: ensure case belongs to this branch admin's branch
            if ((int) $caseData['branch_id'] !== (int) $branchId) {
                throw new \Exception("Unauthorized: case does not belong to your branch.");
            }

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

                // Check if case has an active dispute and mark as Resolved
                require_once __DIR__ . '/../../Models/ResultDisputeModel.php';
                $disputeMdl = new \ResultDisputeModel($pdo);
                $activeDispute = $disputeMdl->getActiveDisputeByCase($id);
                if ($activeDispute) {
                    $disputeMdl->updateDisputeStatus($activeDispute['id'], 'Resolved', 'branch_admin', 'Amended report released by Branch Admin.', $currentUserId);
                }

                $caseModel->releaseResult($id);
                $_SESSION['flash_success'] = "Result released. Case moved to X-ray Patient Records.";

                $branchId = $_SESSION['branch_id'] ?? 1;
                $patientName = $caseData['first_name'] . ' ' . $caseData['last_name'];
                $details = "Patient: $patientName, Case: {$caseData['case_number']}";
                $auditLogModel->addLog($currentUserId, "Released X-ray report", 'Patient Records', 'Case', $id, $details, $branchId);

                $patientUserId = $caseModel->getPatientUserId($id);
                if ($patientUserId) {
                    $notificationModel = new \NotificationModel($pdo);
                    $notifTitle = $activeDispute ? "Error Report Resolved" : "Report Released";
                    $notifMsg = $activeDispute 
                        ? "Your error report for Case {$caseData['case_number']} has been resolved and your updated report released." 
                        : "Your X-ray report for Case {$caseData['case_number']} has been released. You can now view it.";

                    $notificationModel->add(
                        $notifTitle,
                        $notifMsg,
                        "/" . PROJECT_DIR . "/case-status?case_id={$id}",
                        $patientUserId
                    );

                    // Send Email Notification
                    $patientUser = $userModel->getUserById($patientUserId);
                    if ($patientUser && !empty($patientUser['email'])) {
                        require_once __DIR__ . '/../../Helpers/mailer_helper.php';
                        $patientName = $caseData['first_name'] . ' ' . $caseData['last_name'];
                        $reportUrl = appBaseUrl() . "/" . PROJECT_DIR . "/case-status?case_id=" . $id;

                        if ($activeDispute) {
                            $subject = "Error Report Resolved - Citilife Diagnostic Center";
                            $body = renderNotificationEmail(
                                $patientName,
                                "Error Report Resolved - Case #{$caseData['case_number']}",
                                "We have successfully reviewed and resolved your error report for Case <strong>{$caseData['case_number']}</strong>. Your updated X-ray report is now released and ready for viewing in your patient portal.",
                                [
                                    'Case Number' => htmlspecialchars($caseData['case_number']),
                                    'Patient' => htmlspecialchars($patientName),
                                    'Status' => '<span style="color: #1a7f37; font-weight: 600;">Resolved &amp; Released</span>'
                                ],
                                "View Updated Report",
                                $reportUrl,
                                "You're receiving this notification because an error report for your case was resolved.",
                                "#1f883d"
                            );
                        } else {
                            $subject = "Your X-ray Examination is Complete - Citilife System";
                            $body = renderNotificationEmail(
                                $patientName,
                                "Your X-ray Report is Complete & Released",
                                "Good news! Your X-ray report for Case <strong>{$caseData['case_number']}</strong> has been released and is now ready for viewing.",
                                [
                                    'Case Number' => htmlspecialchars($caseData['case_number']),
                                    'Patient' => htmlspecialchars($patientName),
                                    'Status' => '<span style="color: #1a7f37; font-weight: 600;">Report Released</span>'
                                ],
                                "View Report",
                                $reportUrl,
                                "You're receiving this email because your diagnostic report has been released.",
                                "#dc2626"
                            );
                        }
                        sendEmail($patientUser['email'], $patientName, $subject, $body);
                    }
                }

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
