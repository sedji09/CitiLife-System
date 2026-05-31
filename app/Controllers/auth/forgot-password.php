<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;
require_once basePath('app/Helpers/mailer_helper.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.role, p.first_name 
            FROM users u 
            LEFT JOIN patients p ON u.patient_id = p.id 
            WHERE u.email = ? LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Save to database
            $updateStmt = $pdo->prepare("UPDATE users SET reset_password_token = ?, reset_password_expires_at = ? WHERE id = ?");
            $updateStmt->execute([$token, $expiresAt, $user['id']]);

            // Construct Reset Link
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $resetLink = $protocol . $_SERVER['HTTP_HOST'] . '/' . PROJECT_DIR . '/reset-password?token=' . $token;

            // Send Email
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
                    <p style='color: #4b5563; font-size: 16px;'>We received a request to reset your password. Click the button below to proceed:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}' style='display: inline-block; padding: 14px 28px; background-color: #ef4444; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>Reset Password</a>
                    </div>
                    <p style='color: #6b7280; font-size: 14px;'>This link will expire in 30 minutes. If you did not request this, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                    <p style='color: #9ca3af; font-size: 12px; text-align: center;'>&copy; " . date('Y') . " CitiLife Diagnostic Center. All rights reserved.</p>
                </div>
            ";
            
            if (sendEmail($email, $displayName ?: 'User', 'Reset Your Password - CitiLife System', $emailBody)) {
                $success = "A password reset link has been sent to your email.";
            } else {
                $error = "Failed to send the reset email. Please try again later.";
            }
        } else {
            // For security, don't reveal if the email exists. Use the same success message or a generic one.
            $success = "If that email exists in our system, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CitiLife System</title>
    <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/tailwind/src/output.css">
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .bg-pattern {
            background-color: #f3f4f6;
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl overflow-hidden p-8">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4 border border-red-100">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Forgot Password?</h1>
            <p class="text-sm text-gray-500 mt-2">Enter your email and we'll send you a link to reset your password.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" id="email" required 
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all"
                    placeholder="name@example.com">
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                Send Reset Link
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-600">
                Remembered your password? 
                <a href="login" class="font-bold text-red-600 hover:underline">Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>
