<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$philhealth_id = $_GET['philhealth_id'] ?? '';
// Also optionally accept an exclusion ID (request ID or case ID) to exclude the current record being edited
$exclude_request_id = $_GET['exclude_request_id'] ?? 0;
$exclude_case_id = $_GET['exclude_case_id'] ?? 0;

if (empty($philhealth_id)) {
    echo json_encode(['success' => false, 'message' => 'PhilHealth ID is required.']);
    exit;
}

try {
    global $pdo;

    // Check Owner Usage
    $sqlOwnerReq = "SELECT created_at FROM requests WHERE philhealth_id = :id AND philhealth_relation = 'Owner' AND status != 'Cancelled' AND status != 'Rejected'";
    $sqlOwnerCase = "SELECT created_at FROM cases WHERE philhealth_id = :id AND philhealth_relation = 'Owner' AND status != 'Rejected'";
    
    if ($exclude_request_id) {
        $sqlOwnerReq .= " AND id != " . (int)$exclude_request_id;
    }
    if ($exclude_case_id) {
        $sqlOwnerCase .= " AND id != " . (int)$exclude_case_id;
    }

    $stmtOwner = $pdo->prepare("SELECT created_at FROM ($sqlOwnerReq UNION ALL $sqlOwnerCase) AS combined ORDER BY created_at DESC LIMIT 1");
    $stmtOwner->execute([':id' => $philhealth_id]);
    $ownerUsedDate = $stmtOwner->fetchColumn();
    
    // Check Family Member Usage
    $sqlFamilyReq = "SELECT created_at FROM requests WHERE philhealth_id = :id AND philhealth_relation = 'Family Member' AND status != 'Cancelled' AND status != 'Rejected'";
    $sqlFamilyCase = "SELECT created_at FROM cases WHERE philhealth_id = :id AND philhealth_relation = 'Family Member' AND status != 'Rejected'";

    if ($exclude_request_id) {
        $sqlFamilyReq .= " AND id != " . (int)$exclude_request_id;
    }
    if ($exclude_case_id) {
        $sqlFamilyCase .= " AND id != " . (int)$exclude_case_id;
    }

    $stmtFamily = $pdo->prepare("SELECT created_at FROM ($sqlFamilyReq UNION ALL $sqlFamilyCase) AS combined ORDER BY created_at DESC LIMIT 1");
    $stmtFamily->execute([':id' => $philhealth_id]);
    $familyUsedDate = $stmtFamily->fetchColumn();

    $ownerUsed = (bool) $ownerUsedDate;
    $familyUsed = (bool) $familyUsedDate;

    echo json_encode([
        'success' => true,
        'owner_used' => $ownerUsed,
        'owner_used_date' => $ownerUsedDate ? date('M d, Y', strtotime($ownerUsedDate)) : null,
        'family_used' => $familyUsed,
        'family_used_date' => $familyUsedDate ? date('M d, Y', strtotime($familyUsedDate)) : null
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
