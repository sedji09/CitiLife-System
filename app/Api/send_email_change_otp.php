<?php
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

    $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px;'>
            <h2 style='color: #1f2937;'>CitiLife System - Email Change Request</h2>
            <p style='color: #4b5563; font-size: 16px;'>Hi {$userName},</p>
            <p style='color: #4b5563; font-size: 16px;'>We received a request to change your email address. Please use the OTP below to verify your identity:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <span style='display: inline-block; padding: 14px 28px; background-color: #f3f4f6; color: #111827; border: 1px solid #d1d5db; border-radius: 8px; font-weight: bold; font-size: 24px; letter-spacing: 4px;'>{$otp}</span>
            </div>
            <p style='color: #6b7280; font-size: 14px;'>This OTP will expire in 10 minutes. If you did not request an email change, please ignore this email and make sure your account is secure.</p>
            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
            <p style='color: #9ca3af; font-size: 12px; text-align: center;'>&copy; " . date('Y') . " CitiLife Diagnostic Center. All rights reserved.</p>
        </div>
    ";

    if (sendEmail($currentEmail, $userName, 'OTP for Email Change - CitiLife System', $emailBody)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send OTP email.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
