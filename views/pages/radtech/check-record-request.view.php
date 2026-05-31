<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/CaseModel.php';
require_once __DIR__ . '/../../../models/BranchModel.php';

header('Content-Type: application/json');

$caseModel = new \CaseModel($pdo);
$branchModel = new \BranchModel($pdo);

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$patientNo     = trim($_POST['patient_no']     ?? '');   
$patientName   = trim($_POST['patient_name']   ?? '');
$examType      = trim($_POST['exam_type']      ?? '');
$requestBranch = trim($_POST['request_branch'] ?? '');

if (!$patientNo || !$patientName || !$examType || !$requestBranch) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // 1. Resolve target branch (Backend logic)
    $targetBranch = $branchModel->getBranchByName($requestBranch);
    $targetBranchId = $targetBranch['id'] ?? null;

    // 2. Search for cases (Backend logic)
    $casesFound = [];
    if ($targetBranchId) {
        $casesFound = $caseModel->searchCasesInBranch($targetBranchId, $patientNo, $patientName, $examType);
    }

    echo json_encode([
        'success'        => true,
        'cases'          => $casesFound,
        'branch'         => $requestBranch,
        'targetBranchId' => $targetBranchId,
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
