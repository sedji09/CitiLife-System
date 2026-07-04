<?php
require_once __DIR__ . '/../../config/database.php';
global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$patientId = $_SESSION['patient_id'] ?? null;
if (!$patientId) {
    // try to get from userId
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $patientId = $stmt->fetchColumn();
    }
}

if (!$patientId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'] ?? 0;

if (!$caseId) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request ID']);
    exit;
}

try {
    // Ensure request belongs to patient and is still pending approval
    $stmt = $pdo->prepare("SELECT id, status FROM requests WHERE id = ? AND patient_id = ?");
    $stmt->execute([$caseId, $patientId]);
    $case = $stmt->fetch();

    if (!$case) {
        echo json_encode(['success' => false, 'message' => 'Request not found or unauthorized']);
        exit;
    }

    if ($case['status'] !== 'Pending Approval') {
        echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
        exit;
    }

    // Fetch details before deleting
    $stmtDetails = $pdo->prepare("SELECT request_number, branch_id FROM requests WHERE id = ?");
    $stmtDetails->execute([$caseId]);
    $requestDetails = $stmtDetails->fetch();
    $branchId = $requestDetails['branch_id'] ?? null;
    $caseNumber = $requestDetails['request_number'] ?? 'Unknown';

    // Delete the request
    $stmtDel = $pdo->prepare("DELETE FROM requests WHERE id = ?");
    $stmtDel->execute([$caseId]);

    require_once __DIR__ . '/../Models/AuditLogModel.php';
    $auditLogModel = new \AuditLogModel($pdo);
    $userId = $_SESSION['user_id'] ?? null;
    
    if ($userId) {
        $auditLogModel->addLog(
            $userId,
            'Cancelled X-Ray Request',
            'Portal X-Ray Request',
            'Patient',
            $patientId,
            "Patient cancelled their X-ray request ($caseNumber)",
            $branchId
        );
    }

    echo json_encode(['success' => true, 'message' => 'Request cancelled successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
