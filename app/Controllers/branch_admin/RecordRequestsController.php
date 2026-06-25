<?php

namespace App\Controllers\branch_admin;

class RecordRequestsController
{
    public function handle()
    {
        global $pdo;


/**
 * RecordRequestsController.php
 * Handles backend logic for Branch Admin's incoming record requests.
 */

$recordModel = new \RecordRequestModel($pdo);
$branchModel = new \BranchModel($pdo);
$notificationModel = new \NotificationModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);

$currentUserId = $_SESSION['user_id'] ?? 0;

$message = '';
$messageType = '';
$myBranchId = $_SESSION['branch_id'] ?? null;

// 1. Get my branch name
$myBranchName = '';
if ($myBranchId) {
    $b = $branchModel->getBranchById($myBranchId);
    $myBranchName = $b['name'] ?? '';
}

// 2. Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? '';

    try {
        $result = $recordModel->processRequestAction($requestId, $action, $myBranchName, $notificationModel);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            $logAction = ($action === 'Approve') ? "Information request approved" : "Information request rejected";
            $auditLogModel->addLog($currentUserId, $logAction, 'Record Requests', 'Request', $requestId, "Request ID: $requestId", $myBranchId);
        }
    } catch (\Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// 3. Fetch pending requests
$pendingRequests = $myBranchName ? $recordModel->getPendingRequestsForBranch($myBranchName) : [];

// 4. Fetch all branches for the filter
$branchesList = $branchModel->getAllBranches();

        return get_defined_vars();
    }
}
