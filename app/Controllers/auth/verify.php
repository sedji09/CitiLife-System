<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

$token = $_GET['token'] ?? '';
$message = '';
$is_success = false;

if (empty($token)) {
    $message = "Invalid or missing verification token.";
} else {
    // Check if token exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Update user: set is_email_verified to 1, status to Active, and clear the token
        $updateStmt = $pdo->prepare("UPDATE users SET is_email_verified = 1, status = 'Active', verification_token = NULL WHERE id = ?");
        if ($updateStmt->execute([$user['id']])) {
            $is_success = true;
            $message = "Your email has been successfully verified! You can now log in to your account.";
        } else {
            $message = "An error occurred while verifying your email. Please try again.";
        }
    } else {
        // Check if maybe already verified
        $message = "Invalid or expired verification token. Your email might already be verified.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - CitiLife System</title>
    <!-- Use generated Tailwind CSS -->
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
    <div
        class="glass-panel w-full max-w-md rounded-2xl shadow-2xl overflow-hidden p-8 text-center transform transition-all hover:scale-[1.01] duration-300">
        <div
            class="mx-auto w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mb-6 border border-blue-100 shadow-sm">
            <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/citilife-logo.png" alt="CitiLife Logo"
                class="h-12 w-12 object-contain"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <svg class="h-10 w-10 text-blue-600 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-2">Email Verification</h1>

        <?php if ($is_success): ?>
            <div
                class="mb-6 mt-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm flex items-start text-left">
                <svg class="w-6 h-6 mr-3 flex-shrink-0 text-green-600" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <a href="patient-login"
                class="mt-4 w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                Go to Patient Portal
            </a>
        <?php else: ?>
            <div
                class="mb-6 mt-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm flex items-start text-left">
                <svg class="w-6 h-6 mr-3 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <a href="patient-login"
                class="mt-4 w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-gray-600 hover:bg-gray-700 transition-colors duration-200">
                Back to Login
            </a>
        <?php endif; ?>
    </div>
</body>

</html>