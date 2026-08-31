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

    // Check Owner Usage (ANY record)
    $sqlOwnerReq = "SELECT created_at FROM requests WHERE philhealth_id = ? AND philhealth_relation = 'Principal Member' AND status != 'Cancelled' AND status != 'Rejected'";
    $sqlOwnerCase = "SELECT created_at FROM cases WHERE philhealth_id = ? AND philhealth_relation = 'Principal Member' AND status != 'Rejected'";
    $stmtOwner = $pdo->prepare("SELECT created_at FROM ($sqlOwnerReq UNION ALL $sqlOwnerCase) AS combined ORDER BY created_at DESC LIMIT 1");
    $stmtOwner->execute([$philhealth_id, $philhealth_id]);
    $ownerUsedDate = $stmtOwner->fetchColumn();

    // Check Owner Usage (OTHER records)
    $sqlOwnerReqOther = $sqlOwnerReq;
    $sqlOwnerCaseOther = $sqlOwnerCase;
    if ($exclude_request_id) {
        $sqlOwnerReqOther .= " AND id != " . (int)$exclude_request_id;
        $sqlOwnerCaseOther .= " AND (request_id IS NULL OR request_id != " . (int)$exclude_request_id . ")";
    }
    if ($exclude_case_id) {
        $sqlOwnerCaseOther .= " AND id != " . (int)$exclude_case_id;
    }
    $stmtOwnerOther = $pdo->prepare("SELECT created_at FROM ($sqlOwnerReqOther UNION ALL $sqlOwnerCaseOther) AS combined ORDER BY created_at DESC LIMIT 1");
    $stmtOwnerOther->execute([$philhealth_id, $philhealth_id]);
    $ownerUsedByOther = (bool) $stmtOwnerOther->fetchColumn();
    
    // Check Family Member Usage (ANY record)
    $sqlFamilyReq = "SELECT created_at FROM requests WHERE philhealth_id = ? AND philhealth_relation = 'Qualified Dependent' AND status != 'Cancelled' AND status != 'Rejected'";
    $sqlFamilyCase = "SELECT created_at FROM cases WHERE philhealth_id = ? AND philhealth_relation = 'Qualified Dependent' AND status != 'Rejected'";
    $stmtFamily = $pdo->prepare("SELECT created_at FROM ($sqlFamilyReq UNION ALL $sqlFamilyCase) AS combined ORDER BY created_at DESC LIMIT 1");
    $stmtFamily->execute([$philhealth_id, $philhealth_id]);
    $familyUsedDate = $stmtFamily->fetchColumn();

    // Check Family Member Usage (OTHER records)
    $sqlFamilyReqOther = $sqlFamilyReq;
    $sqlFamilyCaseOther = $sqlFamilyCase;
    if ($exclude_request_id) {
        $sqlFamilyReqOther .= " AND id != " . (int)$exclude_request_id;
        $sqlFamilyCaseOther .= " AND (request_id IS NULL OR request_id != " . (int)$exclude_request_id . ")";
    }
    if ($exclude_case_id) {
        $sqlFamilyCaseOther .= " AND id != " . (int)$exclude_case_id;
    }
    $stmtFamilyOther = $pdo->prepare("SELECT created_at FROM ($sqlFamilyReqOther UNION ALL $sqlFamilyCaseOther) AS combined ORDER BY created_at DESC LIMIT 1");
    $stmtFamilyOther->execute([$philhealth_id, $philhealth_id]);
    $familyUsedByOther = (bool) $stmtFamilyOther->fetchColumn();

    echo json_encode([
        'success' => true,
        'owner_used' => (bool)$ownerUsedDate,
        'owner_used_by_other' => $ownerUsedByOther,
        'owner_used_date' => $ownerUsedDate ? date('M d, Y', strtotime($ownerUsedDate)) : null,
        'family_used' => (bool)$familyUsedDate,
        'family_used_by_other' => $familyUsedByOther,
        'family_used_date' => $familyUsedDate ? date('M d, Y', strtotime($familyUsedDate)) : null
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
