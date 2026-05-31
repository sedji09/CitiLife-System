<?php
/**
 * RecordRequestController.php
 * Handles backend logic for the RadTech Record Request page.
 */

require_once __DIR__ . '/../../Models/RecordRequestModel.php';
require_once __DIR__ . '/../../Models/BranchModel.php';
require_once __DIR__ . '/../../Models/NotificationModel.php';
require_once __DIR__ . '/../../Models/AuditLogModel.php';

$recordModel = new \RecordRequestModel($pdo);
$branchModel = new \BranchModel($pdo);
$notificationModel = new \NotificationModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
$currentUserId = $_SESSION['user_id'] ?? 0;

$branchId = $_SESSION['branch_id'] ?? null;
$successMsg = '';
$errorMsg   = '';

// 1. Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    try {
        $data = [
            'patient_no'     => trim($_POST['patient_no'] ?? ''),
            'patient_name'   => trim($_POST['patient_name'] ?? ''),
            'exam_type'      => trim($_POST['exam_type'] ?? ''),
            'request_branch' => $_POST['request_branch'] ?? '',
            'reason'         => trim($_POST['reason'] ?? ''),
            'branch_id'      => $branchId
        ];
    
        if ($recordModel->processRequestSubmission($data, $branchModel, $notificationModel)) {
            $successMsg = "Record request submitted successfully!";
            $details = "Request for: {$data['patient_name']} (#{$data['patient_no']}), Branch: {$data['request_branch']}";
            $auditLogModel->addLog($currentUserId, "Submitted record request", 'Record Requests', 'Request', $pdo->lastInsertId(), $details, $branchId);
        } else {
            $errorMsg = "Failed to submit request.";
        }
    } catch (Exception $e) {
        $errorMsg = "Error: " . $e->getMessage();
    }
}

// 2. Fetch data
$allBranches = $branchModel->getAllBranches();
$requests    = $branchId ? $recordModel->getRequestsByBranch($branchId) : [];

// 3. Prepare Stats
$totalRequests = count($requests);
$pendingRequests = count(array_filter($requests, fn($r) => $r['status'] === 'Pending'));
$approvedRequests = count(array_filter($requests, fn($r) => $r['status'] === 'Approved'));

// Variables $requests, $totalRequests, $pendingRequests, $approvedRequests, $successMsg, $errorMsg 
// will be available in the view file because this controller is included before the view.
