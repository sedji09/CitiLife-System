<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Ensure user is logged in (auth protection)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

global $pdo;
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/database.php';
}
require_once __DIR__ . '/../../app/Helpers/mailer_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Email is required.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.role, p.first_name 
        FROM users u 
        LEFT JOIN patients p ON u.patient_id = p.id 
        WHERE u.email = ? AND u.id = ? LIMIT 1
    ");
    $stmt->execute([$email, $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $updateStmt = $pdo->prepare("UPDATE users SET reset_password_token = ?, reset_password_expires_at = ? WHERE id = ?");
        $updateStmt->execute([$token, $expiresAt, $user['id']]);

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        // Assuming PROJECT_DIR is defined globally, else fallback
        $projDir = defined('PROJECT_DIR') ? PROJECT_DIR : 'CitiLife-System';
        $resetLink = $protocol . $_SERVER['HTTP_HOST'] . '/' . $projDir . '/reset-password?token=' . $token;

        $firstName = 'User';
        if ($user['role'] === 'patient' && !empty($user['first_name'])) {
            $firstName = $user['first_name'];
        } else if (!empty($user['name'])) {
            $firstName = explode(' ', $user['name'])[0];
        }

        $displayName = ($user['role'] === 'patient') ? $user['first_name'] : $user['name'];
        $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px;'>
                <h2 style='color: #1f2937;'>CitiLife System - Password Reset</h2>
                <p style='color: #4b5563; font-size: 16px;'>Hi {$firstName},</p>
                <p style='color: #4b5563; font-size: 16px;'>We received a request to change your password. Click the button below to proceed:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetLink}' style='display: inline-block; padding: 14px 28px; background-color: #ef4444; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>Reset Password</a>
                </div>
                <p style='color: #6b7280; font-size: 14px;'>This link will expire in 30 minutes. If you did not request this, please ignore this email.</p>
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                <p style='color: #9ca3af; font-size: 12px; text-align: center;'>&copy; " . date('Y') . " CitiLife Diagnostic Center. All rights reserved.</p>
            </div>
        ";
        
        if (sendEmail($email, $displayName ?: 'User', 'Change Your Password - CitiLife System', $emailBody)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send the reset email. Please try again later.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found or email mismatch.']);
    }
}
