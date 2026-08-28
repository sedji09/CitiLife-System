<?php
session_start();
if (!defined('PROJECT_DIR')) {
    define('PROJECT_DIR', 'CitiLife-System');
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/AuditLogModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$patientId = $_SESSION['patient_id'] ?? 0;
$userId = $_SESSION['user_id'] ?? 0;
if (!$patientId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$caseId = $_POST['case_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$paymentMethod = $_POST['payment_method'] ?? 'Cash';
$referenceNumber = $_POST['reference_number'] ?? null;

// Check if post_max_size was exceeded (empty POST but Content-Length > 0)
if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo json_encode(['success' => false, 'message' => 'The uploaded file is too large. Please select a smaller image.']);
    exit;
}

$auditLogModel = new \AuditLogModel($pdo);

// Check if case belongs to patient
$stmt = $pdo->prepare("SELECT id, branch_id FROM requests WHERE id = ? AND patient_id = ? AND status = 'Pending Payment'");
$stmt->execute([$caseId, $patientId]);
$request = $stmt->fetch();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Invalid request or already paid.']);
    exit;
}

$branchId = $request['branch_id'];
$proofPath = null;

if ($paymentMethod === 'GCash') {
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'message' => 'Please upload a proof of payment.']);
        exit;
    }
    
    if ($_FILES['payment_proof']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['payment_proof']['error'] === UPLOAD_ERR_FORM_SIZE) {
        echo json_encode(['success' => false, 'message' => 'The uploaded image is too large. Please select a smaller image (max 2MB).']);
        exit;
    }
    
    if ($_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'An error occurred during file upload.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../../public/uploads/receipts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileExt = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($fileExt, $allowedExts)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload an image (JPG, PNG).']);
        exit;
    }
    
    $newFilename = 'receipt_' . $caseId . '_' . time() . '.' . $fileExt;
    $dest = $uploadDir . $newFilename;
    
    if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $dest)) {
        $proofPath = '/public/uploads/receipts/' . $newFilename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file.']);
        exit;
    }
}

try {
    $pdo->beginTransaction();
    
    // Insert into payments
    $stmt = $pdo->prepare("INSERT INTO payments (request_id, amount, payment_method, reference_number, proof_of_payment_path, status) VALUES (?, ?, ?, ?, ?, 'Pending Verification')");
    $stmt->execute([$caseId, $amount, $paymentMethod, $referenceNumber, $proofPath]);
    
    // Update request status to Payment Verifying
    $stmt = $pdo->prepare("UPDATE requests SET status = 'Payment Verifying' WHERE id = ?");
    $stmt->execute([$caseId]);
    
    $auditLogModel->addLog($userId, "Submitted Payment", 'X-ray Status', 'Payment', $pdo->lastInsertId(), "Submitted $paymentMethod payment for case #$caseId", $branchId);
    
    $pdo->commit();
    
    // Return success
    $_SESSION['active_status_case_id'] = $caseId;
    echo json_encode(['success' => true, 'message' => 'Payment submitted successfully.']);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error processing payment: ' . $e->getMessage()]);
    exit;
}
