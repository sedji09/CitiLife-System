<?php

namespace App\Controllers\admin_central;

class FeedbackController
{
    public function handle()
    {
        global $pdo;

        require_once __DIR__ . '/../../Models/FeedbackModel.php';
        require_once __DIR__ . '/../../Models/BranchModel.php';

        $feedbackModel = new \FeedbackModel($pdo);
        $branchModel = new \BranchModel($pdo);

        // Ensure table exists
        $feedbackModel->ensureSchema();

        $branches = $branchModel->getAllBranches();
        
        $filterBranchId = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;

        $feedbacks = [];
        $stats = null;

        $limit = 5;
        $page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($page_num < 1) $page_num = 1;
        $offset = ($page_num - 1) * $limit;

        if ($filterBranchId) {
            $feedbacks = $feedbackModel->getBranchFeedback($filterBranchId, $limit, $offset);
            $totalFeedbacks = $feedbackModel->countBranchFeedback($filterBranchId);
            $stats = $feedbackModel->getFeedbackStats($filterBranchId);
        } else {
            $feedbacks = $feedbackModel->getAllFeedback($limit, $offset);
            $totalFeedbacks = $feedbackModel->countAllFeedback();
            $stats = $feedbackModel->getFeedbackStats();
        }
        $totalPages = ceil($totalFeedbacks / $limit);

        require_once __DIR__ . '/../../Models/AuditLogModel.php';
        $auditLogModel = new \AuditLogModel($pdo);
        $auditLogModel->addLog(
            $_SESSION['user_id'] ?? null,
            'Viewed Patient Feedbacks',
            'Patient Feedback',
            'Feedback',
            null,
            $filterBranchId ? "Viewed feedback for branch ID: $filterBranchId" : "Viewed all feedback",
            null
        );

        return get_defined_vars();
    }
}
