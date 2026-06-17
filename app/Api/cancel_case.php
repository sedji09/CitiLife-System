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
    echo json_encode(['success' => false, 'message' => 'Invalid Case ID']);
    exit;
}

try {
    // Ensure case belongs to patient and is still pending
    $stmt = $pdo->prepare("SELECT id, status, approval_status FROM cases WHERE id = ? AND patient_id = ?");
    $stmt->execute([$caseId, $patientId]);
    $case = $stmt->fetch();

    if (!$case) {
        echo json_encode(['success' => false, 'message' => 'Case not found or unauthorized']);
        exit;
    }

    if ($case['status'] !== 'Pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
        exit;
    }

    if (isset($case['approval_status']) && $case['approval_status'] !== 'Pending') {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel a request that has already been processed']);
        exit;
    }

    // Delete the case
    $stmtDel = $pdo->prepare("DELETE FROM cases WHERE id = ?");
    $stmtDel->execute([$caseId]);

    echo json_encode(['success' => true, 'message' => 'Case cancelled successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
