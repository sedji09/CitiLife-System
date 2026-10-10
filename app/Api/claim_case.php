<?php
/**
 * API Endpoint: claim_case.php
 * Handles marking completed cases as Claimed or Unclaimed by RadTech / Staff.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../Models/CaseModel.php';
require_once __DIR__ . '/../Models/AuditLogModel.php';
require_once __DIR__ . '/../Models/NotificationModel.php';

global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = $_SESSION['role'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;
$branchId = $_SESSION['branch_id'] ?? null;

// Only authorized staff (RadTech, Branch Admin, Central Admin) can update claim status
if (!$currentUserId || !in_array($userRole, ['radtech', 'branch_admin', 'admin_central', 'it_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$caseId = (int) ($data['case_id'] ?? 0);
$action = trim($data['action'] ?? 'mark_claimed'); // 'mark_claimed' or 'mark_unclaimed'
$claimedBy = trim($data['claimed_by'] ?? '');
$relationship = trim($data['claimed_relationship'] ?? '');
$idPresented = trim($data['claimed_id_presented'] ?? '');
$notes = trim($data['claimed_notes'] ?? '');

if ($caseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Case ID.']);
    exit;
}

try {
    $caseModel = new \CaseModel($pdo);
    $auditLogModel = new \AuditLogModel($pdo);
    $notifModel = new \NotificationModel($pdo);

    $caseData = $caseModel->getCaseById($caseId);
    if (!$caseData) {
        echo json_encode(['success' => false, 'message' => 'Case not found.']);
        exit;
    }

    // Branch isolation check: RadTech and Branch Admin can only manage their branch cases
    if (in_array($userRole, ['radtech', 'branch_admin']) && (int) ($caseData['branch_id'] ?? 0) !== (int) $branchId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Case belongs to another branch.']);
        exit;
    }

    if ($action === 'mark_unclaimed') {
        $ok = $caseModel->markAsUnclaimed($caseId);
        if ($ok) {
            $auditLogModel->addLog(
                $currentUserId,
                'Reverted X-Ray Claim Status to Unclaimed',
                'Patient Records',
                'Case',
                $caseId,
                "Hardcopy result for Case {$caseData['case_number']} marked as Unclaimed by " . ($_SESSION['name'] ?? 'Staff'),
                $caseData['branch_id']
            );

            echo json_encode([
                'success' => true,
                'message' => "Case {$caseData['case_number']} marked as Unclaimed.",
                'is_claimed' => 0
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update claim status.']);
            exit;
        }
    }

    // Default action: mark_claimed
    if (empty($claimedBy)) {
        // Default to patient's full name if left blank
        $claimedBy = formatFullName($caseData);
    }

    $ok = $caseModel->markAsClaimed($caseId, $claimedBy, $relationship, $idPresented, $notes, $currentUserId);
    if ($ok) {
        // Audit log
        $details = "Hardcopy X-ray result/film for Case {$caseData['case_number']} claimed by {$claimedBy}";
        if (!empty($relationship)) {
            $details .= " ({$relationship})";
        }
        if (!empty($idPresented)) {
            $details .= " [ID: {$idPresented}]";
        }

        $auditLogModel->addLog(
            $currentUserId,
            'Marked X-Ray Result as Claimed',
            'Patient Records',
            'Case',
            $caseId,
            $details,
            $caseData['branch_id']
        );

        // Send patient notification
        $patientUserId = $caseModel->getPatientUserId($caseId);
        if ($patientUserId) {
            $notifTitle = "Examination Result Claimed";
            $notifMessage = "Your official findings and X-ray film for Case {$caseData['case_number']} have been claimed by {$claimedBy}.";
            $notifModel->add($notifTitle, $notifMessage, 'my-records?tab=completed', $patientUserId, 'patient', $caseData['branch_id']);
        }

        echo json_encode([
            'success' => true,
            'message' => "Hardcopy result for Case {$caseData['case_number']} has been marked as Claimed!",
            'is_claimed' => 1,
            'claimed_at' => date('M d, Y h:i A'),
            'claimed_by' => $claimedBy,
            'claimed_relationship' => $relationship
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update claim status in database.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    exit;
}
