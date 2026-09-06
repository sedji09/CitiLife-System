<?php

namespace App\Controllers\radtech;

class ReEditController
{
    public function handle()
    {
        global $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        // Check authentication & role
        $userRole = $_SESSION['role'] ?? '';
        $userId   = $_SESSION['user_id'] ?? 0;
        $branchId = $_SESSION['branch_id'] ?? null;

        if (!$userId || !in_array($userRole, ['radtech', 'branch_admin', 'admin_central'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Only RadTech staff can grant re-edit access.']);
            exit;
        }

        // Get Case ID and Reason from POST or JSON
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true) ?: [];
        $caseId   = (int) ($_POST['case_id'] ?? $_POST['id'] ?? $jsonData['case_id'] ?? $jsonData['id'] ?? 0);
        $reason   = trim($_POST['reason'] ?? $jsonData['reason'] ?? '');

        if (!$caseId) {
            echo json_encode(['success' => false, 'message' => 'Invalid or missing case ID.']);
            exit;
        }

        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a reason for requesting a re-edit.']);
            exit;
        }

        require_once __DIR__ . '/../../Models/CaseModel.php';
        require_once __DIR__ . '/../../Models/AuditLogModel.php';
        require_once __DIR__ . '/../../Models/NotificationModel.php';

        $caseModel         = new \CaseModel($pdo);
        $auditLogModel     = new \AuditLogModel($pdo);
        $notificationModel = new \NotificationModel($pdo);

        try {
            $case = $caseModel->getCaseById($caseId);
            if (!$case) {
                echo json_encode(['success' => false, 'message' => 'Case not found.']);
                exit;
            }

            // Security: RadTech can only manage cases from their branch
            if ($userRole === 'radtech' && !empty($branchId) && !empty($case['branch_id']) && (int)$case['branch_id'] !== (int)$branchId) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized. This case does not belong to your branch.']);
                exit;
            }

            // Validate status: case should be Report Ready and not released
            if ($case['status'] !== 'Report Ready' || (!empty($case['released']) && (int)$case['released'] === 1)) {
                echo json_encode(['success' => false, 'message' => 'Only unreleased cases with "Report Ready" status can be reverted to draft.']);
                exit;
            }

            // Revert case to draft / Under Reading with reason
            $caseModel->revertToDraft($caseId, $reason);

            $patientName = trim(($case['first_name'] ?? '') . ' ' . ($case['last_name'] ?? '')) ?: 'Unknown Patient';
            $caseNumber  = $case['case_number'] ?? "#{$caseId}";

            // Audit Log
            $auditLogModel->addLog(
                $userId,
                'Reverted Case to Draft for Re-Edit',
                'Report Re-edit',
                'Case',
                $caseId,
                "Patient: {$patientName} | Case #{$caseNumber} | Reason: {$reason}",
                $case['branch_id'] ?? $branchId
            );

            // Notify Radiologist (assigned or all radiologists)
            $projectPrefix = defined('PROJECT_DIR') && PROJECT_DIR ? '/' . PROJECT_DIR : '';
            $link = "{$projectPrefix}/index.php?role=radiologist&page=worklist&highlight=" . urlencode($caseNumber);
            $assignedRadId = !empty($case['radiologist_id']) ? (int) $case['radiologist_id'] : null;

            $notificationModel->add(
                'Case Returned for Revision',
                "RadTech returned Case #{$caseNumber} ({$patientName}) for revision. Notes: \"{$reason}\"",
                $link,
                $assignedRadId,
                'radiologist',
                null
            );

            // Auto-dismiss previous "Report Ready" / "Revised Report Submitted" notifications for this case
            try {
                $stmtDismiss = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE title IN ('Report Ready', 'Edited Report Ready', 'Revised Report Submitted') AND link LIKE ? AND is_read = 0");
                $stmtDismiss->execute(["%{$caseNumber}%"]);
            } catch (\Exception $e) {}

            echo json_encode([
                'success' => true,
                'message' => 'Case successfully reverted to draft. The radiologist can now edit the report.',
                'case_id' => $caseId
            ]);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to revert case: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}
