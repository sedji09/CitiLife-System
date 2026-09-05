<?php
/**
 * cases.php
 * API endpoint for Case Status & Tracking polling with forward-only validation.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/session.php';

if (!defined('PROJECT_DIR')) {
    $scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $parts = explode('/', str_replace('\\', '/', $scriptDir));
    define('PROJECT_DIR', (isset($parts[0]) && $parts[0] !== 'app' && $parts[0] !== 'index.php') ? $parts[0] : 'CitiLife-System');
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/CaseModel.php';
require_once __DIR__ . '/../Models/CaseStatusTransition.php';
require_once __DIR__ . '/../Models/ResultDisputeModel.php';

$caseId = intval($_GET['case_id'] ?? ($_POST['case_id'] ?? 0));
$patientId = $_SESSION['patient_id'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (!$caseId) {
    echo json_encode(['success' => false, 'message' => 'Missing case_id parameter.']);
    exit;
}

try {
    $caseModel = new CaseModel($pdo);
    $disputeModel = new ResultDisputeModel($pdo);

    $case = $caseModel->getCaseById($caseId);

    if (!$case) {
        echo json_encode(['success' => false, 'message' => 'Case not found.']);
        exit;
    }

    // Security check: if patient is logged in, ensure case belongs to them
    if ($patientId && (int) $case['patient_id'] !== (int) $patientId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized case access.']);
        exit;
    }

    $activeDispute = $disputeModel->getActiveDisputeByCase($caseId);
    $statusVal = $case['status'] ?? 'Pending';
    $statusTimestamp = $case['status_timestamp'] ?? $case['created_at'];
    $timestampUnix = strtotime($statusTimestamp) ?: time();

    // Query latest dispute to retain error workflow when resolved/edited
    $stmtD = $pdo->prepare("SELECT * FROM result_disputes WHERE case_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmtD->execute([$caseId]);
    $latestDispute = $stmtD->fetch(PDO::FETCH_ASSOC);

    // Determine if case is in error correction workflow
    $isErrorWorkflow = ($latestDispute !== null && !in_array($latestDispute['status'], ['Rejected']))
        || (int) ($case['is_amended'] ?? 0) === 1
        || CaseStatusTransition::isErrorWorkflow($statusVal);

    $errorStep = 2;
    $errorStepLabel = 'For RadTech Review';

    if ($latestDispute) {
        $dispStatus = $latestDispute['status'];
        if ($dispStatus === 'Resolved' || $dispStatus === 'Correction Completed') {
            $errorStep = 4;
            $errorStepLabel = 'Correction Completed';
            $statusVal = 'Correction Completed';
        } else {
            $errorStep = CaseStatusTransition::getErrorStep($dispStatus);
            $stepsList = CaseStatusTransition::getErrorStepsList();
            $errorStepLabel = $stepsList[$errorStep] ?? $dispStatus;
            $statusVal = $dispStatus;
        }
    } elseif ((int) ($case['is_amended'] ?? 0) === 1) {
        $errorStep = 4;
        $errorStepLabel = 'Correction Completed';
        $statusVal = 'Correction Completed';
    } elseif (CaseStatusTransition::isErrorWorkflow($statusVal)) {
        $errorStep = CaseStatusTransition::getErrorStep($statusVal);
        $stepsList = CaseStatusTransition::getErrorStepsList();
        $errorStepLabel = $stepsList[$errorStep] ?? $statusVal;
    }

    // Standard examination step (1..7)
    $examStep = 2;
    if ($statusVal === 'Rejected') {
        $examStep = 0;
    } elseif ($statusVal === 'Cancelled') {
        $examStep = 0;
    } elseif ($statusVal === 'Pending Payment') {
        $examStep = 2;
    } elseif ($statusVal === 'Payment Verifying') {
        $examStep = 2;
    } elseif ($statusVal === 'Payment Verified') {
        $examStep = 3;
    } elseif ($statusVal === 'Approved') {
        $examStep = 4;
    } elseif ($statusVal === 'X-ray Taken') {
        $examStep = 5;
    } elseif ($statusVal === 'Under Reading') {
        $examStep = 5;
    } elseif ($statusVal === 'Report Ready') {
        $examStep = 6;
    } elseif (in_array($statusVal, ['Released', 'Completed']) || (int) $case['released'] === 1) {
        $examStep = 7;
    }

    echo json_encode([
        'success'             => true,
        'case_id'             => (int) $case['id'],
        'case_number'         => $case['case_number'],
        'status'              => $statusVal,
        'released'            => (int) ($case['released'] ?? 0),
        'status_timestamp'    => $statusTimestamp,
        'timestamp_unix'      => $timestampUnix,
        'is_amended'          => (int) ($case['is_amended'] ?? 0),
        'workflow_type'       => $isErrorWorkflow ? 'error_correction' : 'examination',
        'exam_step'           => $examStep,
        'error_step'          => $errorStep,
        'error_step_label'    => $errorStepLabel,
        'active_dispute'      => $activeDispute ? [
            'id'          => (int) $activeDispute['id'],
            'category'    => $activeDispute['dispute_category'],
            'description' => $activeDispute['description'],
            'status'      => $activeDispute['status'],
            'created_at'  => $activeDispute['created_at'],
        ] : null,
        'date_completed'      => $case['date_completed'] ?? null,
    ]);
    exit;

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
