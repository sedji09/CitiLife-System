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

        if ($branchId) {
            $feedbacks = $feedbackModel->getBranchFeedback($branchId);
            $stats = $feedbackModel->getFeedbackStats($branchId);
        }

        return get_defined_vars();
    }
}
