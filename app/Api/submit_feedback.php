<?php
session_start();
header('Content-Type: application/json');

if (!defined('PROJECT_DIR')) {
    $parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    define('PROJECT_DIR', (isset($parts[0]) && $parts[0] !== 'app' && $parts[0] !== 'index.php') ? $parts[0] : 'CitiLife-System');
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/FeedbackModel.php';
require_once __DIR__ . '/../Models/PatientModel.php';
require_once __DIR__ . '/../Models/NotificationModel.php';
require_once __DIR__ . '/../Models/AuditLogModel.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$patientModel = new \PatientModel($pdo);
$patientData = $patientModel->getPatientByUserId($userId);
$patientId = $patientData['id'] ?? null;
$branchId = $patientData['branch_id'] ?? null;

if (!$patientId) {
    echo json_encode(['success' => false, 'error' => 'You must have a completed patient profile to submit feedback.']);
    exit;
}

$caseId = $_POST['case_id'] ?? null;
$rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int) $_POST['rating'] : 0;
$comments = trim($_POST['comments'] ?? '');

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Please provide a star rating to let us know how we did!']);
    exit;
}

if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'Invalid case specified.']);
    exit;
}

$feedbackModel = new \FeedbackModel($pdo);
$notificationModel = new \NotificationModel($pdo);
$feedbackModel->ensureSchema();

// Check if case already has feedback
$feedbackCaseIds = $feedbackModel->getPatientFeedbackCaseIds($patientId);
if (in_array($caseId, $feedbackCaseIds)) {
    echo json_encode(['success' => false, 'error' => 'You have already submitted feedback for this case.']);
    exit;
}

try {
    $feedbackModel->submitFeedback($caseId, $patientId, $userId, $branchId, $rating, $comments);

    $patientName = $patientData['first_name'] . ' ' . $patientData['last_name'];
    
    // Add Audit Log
    $auditLogModel = new \AuditLogModel($pdo);
    $actionMsg = "Submitted feedback for case #{$caseId}";
    $auditLogModel->addLog(
        $userId,
        $actionMsg,
        'Patient Feedback',
        'feedback',
        $caseId, // entity_id
        "Comments: " . ($comments ?: 'No comments provided.'),
        $branchId
    );

    // Notify Branch Admin
    if ($branchId) {
        $patientName = $patientData['first_name'] . ' ' . $patientData['last_name'];
        $notificationModel->add(
            "New Patient Feedback",
            "Patient $patientName submitted a {$rating}-star rating.",
            "/" . PROJECT_DIR . "/index.php?role=branch_admin&page=feedback",
            null,
            'branch_admin',
            $branchId
        );
    }
    
    // Notify Central Admin
    $notificationModel->add(
        "New Patient Feedback",
        "A new {$rating}-star rating was submitted by a patient.",
        "/" . PROJECT_DIR . "/index.php?role=admin_central&page=feedback",
        null,
        'admin_central'
    );

    echo json_encode(['success' => true]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An error occurred while submitting your feedback. Please try again later.']);
}
