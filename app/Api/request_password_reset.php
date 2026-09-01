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
        $projDir = defined('PROJECT_DIR') ? PROJECT_DIR : 'Citilife-System';
        $resetLink = $protocol . $_SERVER['HTTP_HOST'] . '/' . $projDir . '/reset-password?token=' . $token;

        $firstName = 'User';
        if ($user['role'] === 'patient' && !empty($user['first_name'])) {
            $firstName = $user['first_name'];
        } else if (!empty($user['name'])) {
            $firstName = explode(' ', $user['name'])[0];
        }

        $displayName = ($user['role'] === 'patient') ? $user['first_name'] : $user['name'];
        $emailBody = renderActionEmail(
            $firstName,
            "Reset your password, <strong>" . htmlspecialchars($firstName) . "</strong>",
            "We received a request to change the password for your Citilife account. Click the button below to proceed:",
            "Reset Password",
            $resetLink,
            "This reset link is valid for <strong>30 minutes</strong> and can only be used once.",
            "<strong>Security Notice:</strong> If you did not request this password change, please ignore this email or secure your account.",
            "You're receiving this email because a password reset was requested for your account.",
            "#dc2626"
        );
        
        if (sendEmail($email, $displayName ?: 'User', 'Change Your Password - Citilife System', $emailBody)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send the reset email. Please try again later.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found or email mismatch.']);
    }
}
