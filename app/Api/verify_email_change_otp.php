<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $submittedOtp = trim($inputData['otp'] ?? $_POST['otp'] ?? '');

    if (empty($submittedOtp)) {
        echo json_encode(['success' => false, 'error' => 'OTP is required.']);
        exit;
    }

    if (!isset($_SESSION['email_change_otp']) || !isset($_SESSION['email_change_otp_expires'])) {
        echo json_encode(['success' => false, 'error' => 'No OTP request found. Please request a new OTP.']);
        exit;
    }

    if (time() > $_SESSION['email_change_otp_expires']) {
        echo json_encode(['success' => false, 'error' => 'OTP has expired. Please request a new one.']);
        exit;
    }

    if ($submittedOtp === $_SESSION['email_change_otp']) {
        // Correct OTP
        $_SESSION['email_change_verified'] = true;
        
        // Optionally, clear the OTP to prevent reuse
        unset($_SESSION['email_change_otp']);
        unset($_SESSION['email_change_otp_expires']);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid OTP.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
