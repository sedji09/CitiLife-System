<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../Helpers/mailer_helper.php';

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $currentEmail = $_SESSION['email'];
        $userName = $_SESSION['name'] ?? 'User';

        // Generate a 6-digit OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        
        // Store in session
        $_SESSION['email_change_otp'] = $otp;
        $_SESSION['email_change_otp_expires'] = time() + (10 * 60); // 10 minutes expiry
        $_SESSION['email_change_verified'] = false; // Reset verification status

        $emailBody = renderOtpEmail(
            $userName,
            $otp,
            'email change verification',
            10,
            "Confirm your email address change, <strong>" . htmlspecialchars($userName) . "</strong>",
            "You're receiving this email because a request to update your email address was initiated in your Citilife account."
        );

        if (sendEmail($currentEmail, $userName, 'OTP for Email Change - Citilife System', $emailBody)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send OTP email.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    }
} catch (\Throwable $th) {
    echo json_encode(['success' => false, 'error' => 'Server Error: ' . $th->getMessage() . ' in ' . $th->getFile() . ' on line ' . $th->getLine()]);
}
