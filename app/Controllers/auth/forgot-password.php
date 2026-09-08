<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

require_once basePath('app/Helpers/mailer_helper.php');

$error = '';
$success = '';

$portal = $_GET['portal'] ?? ($_POST['portal'] ?? '');
if (empty($portal) && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'login') !== false && strpos($_SERVER['HTTP_REFERER'], '?login=1') === false) {
    $portal = 'staff';
}
$isStaffPortal = ($portal === 'staff');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists, prioritizing appropriate role based on portal
        $orderBy = $isStaffPortal 
            ? "CASE WHEN u.role != 'patient' THEN 1 ELSE 2 END" 
            : "CASE WHEN u.role = 'patient' THEN 1 ELSE 2 END";

        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.role, p.first_name 
            FROM users u 
            LEFT JOIN patients p ON u.patient_id = p.id 
            WHERE u.email = ? 
            ORDER BY {$orderBy} 
            LIMIT 1
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
            $resetPath = 'reset-password?token=' . $token . ($isStaffPortal ? '&portal=staff' : '');
            $resetLink = appBaseUrl() . url($resetPath);

            // Send Email
            $firstName = 'User';
            if ($user['role'] === 'patient' && !empty($user['first_name'])) {
                $firstName = $user['first_name'];
            } elseif (!empty($user['name'])) {
                $firstName = explode(' ', $user['name'])[0];
            }

            $displayName = ($user['role'] === 'patient') ? $user['first_name'] : $user['name'];
            $emailBody = renderActionEmail(
                $firstName,
                "Reset your password, <strong>" . htmlspecialchars($firstName) . "</strong>",
                "We received a request to reset the password for your Citilife " . ($isStaffPortal ? "staff " : "") . "account. Click the button below to choose a new password:",
                "Reset Password",
                $resetLink,
                "This reset link is valid for <strong>30 minutes</strong> and can only be used once.",
                "<strong>Security Notice:</strong> If you did not request a password reset, please ignore this email or contact your system administrator.",
                "You're receiving this email because a password reset was requested for your account.",
                "#dc2626"
            );
            
            if (sendEmail($email, $displayName ?: 'User', 'Reset Your Password - Citilife System', $emailBody)) {
                $success = "A password reset link has been sent to your email.";
            } else {
                $error = "Failed to send the reset email. Please try again later.";
            }
        } else {
            // For security, show generic success message
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
    <title><?= $isStaffPortal ? 'Staff Password Reset' : 'Forgot Password' ?> - <?= htmlspecialchars(getSystemName()) ?></title>
    <link rel="stylesheet" href="<?= url('tailwind/src/output.css') ?>">
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
    <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl overflow-hidden p-8 transform transition-all duration-300">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4 border border-red-100 shadow-sm">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                <?= $isStaffPortal ? 'Staff Password Reset' : 'Forgot Password?' ?>
            </h1>
            <p class="text-sm text-gray-500 mt-2">
                <?= $isStaffPortal 
                    ? "Enter your staff email and we'll send you a link to reset your password." 
                    : "Enter your email and we'll send you a link to reset your password." ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-start gap-2">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm flex items-start gap-2">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <?php if ($portal): ?>
                <input type="hidden" name="portal" value="<?= htmlspecialchars($portal) ?>">
            <?php endif; ?>
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
                    <?= $isStaffPortal ? 'Staff Email Address' : 'Email Address' ?>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                    <input type="email" name="email" id="email" required 
                        class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all text-sm"
                        placeholder="<?= $isStaffPortal ? 'staff@example.com' : 'name@example.com' ?>">
                </div>
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                Send Reset Link
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-600">
                Remembered your password? 
                <a href="<?= $isStaffPortal ? url('login') : url('?login=1') ?>" 
                   class="font-bold text-red-600 hover:underline">
                    <?= $isStaffPortal ? 'Back to Staff Login' : 'Back to Login' ?>
                </a>
            </p>
        </div>
    </div>
</body>
</html>
