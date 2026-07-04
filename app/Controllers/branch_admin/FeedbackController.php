<?php

namespace App\Controllers\branch_admin;

class FeedbackController
{
    public function handle()
    {
        global $pdo;

        require_once __DIR__ . '/../../Models/FeedbackModel.php';

        $feedbackModel = new \FeedbackModel($pdo);

        // Ensure table exists
        $feedbackModel->ensureSchema();

        $branchId = $_SESSION['branch_id'] ?? null;

        $feedbacks = [];
        $stats = null;

        $limit = 5;
        $page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($page_num < 1) $page_num = 1;
        $offset = ($page_num - 1) * $limit;

        if ($branchId) {
            $feedbacks = $feedbackModel->getBranchFeedback($branchId, $limit, $offset);
            $totalFeedbacks = $feedbackModel->countBranchFeedback($branchId);
            $totalPages = ceil($totalFeedbacks / $limit);
            $stats = $feedbackModel->getFeedbackStats($branchId);
        } else {
            $totalPages = 1;
        }

        require_once __DIR__ . '/../../Models/AuditLogModel.php';
        $auditLogModel = new \AuditLogModel($pdo);
        $auditLogModel->addLog(
            $_SESSION['user_id'] ?? null,
            'Viewed Patient Feedbacks',
            'Patient Feedback',
            'Feedback',
            null,
            "Viewed feedback dashboard",
            $branchId
        );

        return get_defined_vars();
    }
}
