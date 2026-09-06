<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$philhealth_id = $_GET['philhealth_id'] ?? '';
$exclude_request_id = (int)($_GET['exclude_request_id'] ?? 0);
$exclude_case_id = (int)($_GET['exclude_case_id'] ?? 0);
$patient_id = (int)($_GET['patient_id'] ?? 0);

if (empty($philhealth_id)) {
    echo json_encode(['success' => false, 'message' => 'PhilHealth ID is required.']);
    exit;
}

try {
    global $pdo;

    // Resolve current patient_id if not provided directly
    if (!$patient_id && $exclude_request_id) {
        $stmtFindPat = $pdo->prepare("SELECT patient_id FROM requests WHERE id = ?");
        $stmtFindPat->execute([$exclude_request_id]);
        $patient_id = (int)$stmtFindPat->fetchColumn();
    }
    if (!$patient_id && $exclude_case_id) {
        $stmtFindPat = $pdo->prepare("SELECT patient_id FROM cases WHERE id = ?");
        $stmtFindPat->execute([$exclude_case_id]);
        $patient_id = (int)$stmtFindPat->fetchColumn();
    }

    $curPatientName = '';
    if ($patient_id) {
        $stmtCur = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM patients WHERE id = ?");
        $stmtCur->execute([$patient_id]);
        $curPatientName = trim((string)$stmtCur->fetchColumn());
    }

    // ── Check Principal Member Usage (excluding current editing record) ───────────
    $sqlOwnerReq = "SELECT r.patient_id, r.created_at, CONCAT(p.first_name, ' ', p.last_name) AS pat_name 
                    FROM requests r 
                    JOIN patients p ON r.patient_id = p.id 
                    WHERE r.philhealth_id = ? AND r.philhealth_relation = 'Principal Member' AND r.status != 'Cancelled' AND r.status != 'Rejected'";
    if ($exclude_request_id) {
        $sqlOwnerReq .= " AND r.id != " . (int)$exclude_request_id;
    }

    $sqlOwnerCase = "SELECT c.patient_id, c.created_at, CONCAT(p.first_name, ' ', p.last_name) AS pat_name 
                     FROM cases c 
                     JOIN patients p ON c.patient_id = p.id 
                     WHERE c.philhealth_id = ? AND c.philhealth_relation = 'Principal Member' AND c.status != 'Rejected'";
    if ($exclude_request_id) {
        $sqlOwnerCase .= " AND (c.request_id IS NULL OR c.request_id != " . (int)$exclude_request_id . ")";
    }
    if ($exclude_case_id) {
        $sqlOwnerCase .= " AND c.id != " . (int)$exclude_case_id;
    }

    $stmtOwner = $pdo->prepare("SELECT patient_id, created_at, pat_name FROM ($sqlOwnerReq UNION ALL $sqlOwnerCase) AS combined ORDER BY created_at DESC LIMIT 1");
    $stmtOwner->execute([$philhealth_id, $philhealth_id]);
    $ownerRecord = $stmtOwner->fetch(PDO::FETCH_ASSOC);

    $ownerUsed = (bool)$ownerRecord;
    $ownerUsedDate = $ownerRecord ? date('M d, Y', strtotime($ownerRecord['created_at'])) : null;
    $ownerPatientId = $ownerRecord ? (int)$ownerRecord['patient_id'] : 0;
    $ownerPatientName = $ownerRecord ? trim($ownerRecord['pat_name']) : '';

    // ── Check Qualified Dependent Usage (excluding current editing record) ────────
    $sqlFamilyReq = "SELECT r.patient_id, r.created_at, CONCAT(p.first_name, ' ', p.last_name) AS pat_name 
                     FROM requests r 
                     JOIN patients p ON r.patient_id = p.id 
                     WHERE r.philhealth_id = ? AND r.philhealth_relation = 'Qualified Dependent' AND r.status != 'Cancelled' AND r.status != 'Rejected'";
    if ($exclude_request_id) {
        $sqlFamilyReq .= " AND r.id != " . (int)$exclude_request_id;
    }

    $sqlFamilyCase = "SELECT c.patient_id, c.created_at, CONCAT(p.first_name, ' ', p.last_name) AS pat_name 
                      FROM cases c 
                      JOIN patients p ON c.patient_id = p.id 
                      WHERE c.philhealth_id = ? AND c.philhealth_relation = 'Qualified Dependent' AND c.status != 'Rejected'";
    if ($exclude_request_id) {
        $sqlFamilyCase .= " AND (c.request_id IS NULL OR c.request_id != " . (int)$exclude_request_id . ")";
    }
    if ($exclude_case_id) {
        $sqlFamilyCase .= " AND c.id != " . (int)$exclude_case_id;
    }

    $stmtFamily = $pdo->prepare("SELECT patient_id, created_at, pat_name FROM ($sqlFamilyReq UNION ALL $sqlFamilyCase) AS combined ORDER BY created_at DESC LIMIT 1");
    $stmtFamily->execute([$philhealth_id, $philhealth_id]);
    $familyRecord = $stmtFamily->fetch(PDO::FETCH_ASSOC);

    $familyUsed = (bool)$familyRecord;
    $familyUsedDate = $familyRecord ? date('M d, Y', strtotime($familyRecord['created_at'])) : null;
    $familyPatientId = $familyRecord ? (int)$familyRecord['patient_id'] : 0;
    $familyPatientName = $familyRecord ? trim($familyRecord['pat_name']) : '';

    // ── Identity Matching Rule: Cardholder Cannot Be Their Own Dependent ─────────
    $isCurrentPatientOwner = false;
    $familyBlockedForOwner = false;
    $familyBlockReason = '';

    if ($ownerUsed) {
        if ($patient_id && $ownerPatientId && $patient_id === $ownerPatientId) {
            $isCurrentPatientOwner = true;
        } elseif ($curPatientName !== '' && $ownerPatientName !== '' && strcasecmp($curPatientName, $ownerPatientName) === 0) {
            $isCurrentPatientOwner = true;
        }

        if ($isCurrentPatientOwner) {
            $familyBlockedForOwner = true;
            $familyBlockReason = "The patient (" . ($ownerPatientName ?: 'this patient') . ") is registered as the Principal Member of this PhilHealth ID. The cardholder cannot be their own Qualified Dependent.";
        }
    }

    echo json_encode([
        'success'                  => true,
        'owner_used'               => $ownerUsed,
        'owner_used_by_other'      => $ownerUsed,
        'owner_used_date'          => $ownerUsedDate,
        'owner_patient_name'       => $ownerPatientName,
        'family_used'              => $familyUsed,
        'family_used_by_other'     => $familyUsed,
        'family_used_date'         => $familyUsedDate,
        'family_patient_name'      => $familyPatientName,
        'current_patient_id'       => $patient_id,
        'current_patient_name'     => $curPatientName,
        'is_same_as_owner'         => $isCurrentPatientOwner,
        'family_blocked_for_owner' => $familyBlockedForOwner,
        'family_block_reason'      => $familyBlockReason
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
