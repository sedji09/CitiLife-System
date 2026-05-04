<?php
session_start();

require_once __DIR__ . '/app/config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['role'])) {
    header("Location: /" . PROJECT_DIR . "/dashboard");
    exit;
}

$error = '';
$warning = '';
$is_locked = false;
$lock_message = '';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = ['attempts' => 0, 'locked_until' => 0];
}

$attempts = &$_SESSION['login_attempts'];
$currentTime = time();

if ($attempts['locked_until'] > $currentTime) {
    $is_locked = true;
    $remaining = $attempts['locked_until'] - $currentTime;
    $time_str = $remaining > 60 ? ceil($remaining / 60) . " minutes" : $remaining . " seconds";
    $lock_message = "Too many failed attempts. Please try again after $time_str.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // VALIDATION using filter_var();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Prepare statement to fetch user by email along with patient name
            $stmt = $pdo->prepare('
            SELECT u.*, p.first_name, p.last_name 
            FROM users u 
            LEFT JOIN patients p ON u.patient_id = p.id 
            WHERE u.email = :email LIMIT 1
        ');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Check if user exists and verify password
            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] !== 'patient') {
                    $error = 'This is the Patient Portal. Staff must log in at the Staff Portal.';
                } else if (isset($user['is_email_verified']) && $user['is_email_verified'] == 0) {
                    $error = 'Please verify your email address first. Check your inbox for the verification link.';
                } else if (isset($user['status']) && $user['status'] === 'Inactive') {
                    $error = 'Your account has been deactivated. Please contact the clinic.';
                } else {
                    // Check if device is remembered (Skip OTP if valid token exists)
                    $rememberToken = $_COOKIE['remember_device'] ?? null;
                    if ($rememberToken) {
                        $stmtDevice = $pdo->prepare("SELECT id FROM user_devices WHERE user_id = ? AND device_token = ? AND expires_at > NOW() LIMIT 1");
                        $stmtDevice->execute([$user['id'], $rememberToken]);
                        if ($stmtDevice->fetch()) {
                            // Device remembered, skip OTP and start full session
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['patient_id'] = $user['patient_id'];
                            $_SESSION['name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
                            $_SESSION['branch_id'] = $user['branch_id'];

                            header("Location: /" . PROJECT_DIR . "/dashboard");
                            exit;
                        }
                    }

                    // Generate OTP
                    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    $updateStmt = $pdo->prepare("UPDATE users SET otp_code = ?, token_expires_at = ? WHERE id = ?");
                    $updateStmt->execute([$otpCode, $expiresAt, $user['id']]);
                    
                    // Send email
                    require_once __DIR__ . '/app/helpers/mailer_helper.php';
                    $firstName = $user['first_name'] ?? 'Patient';
                    $emailBody = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px;'>
                            <h2 style='color: #1f2937;'>CitiLife System - Login Verification</h2>
                            <p style='color: #4b5563; font-size: 16px;'>Hi {$firstName},</p>
                            <p style='color: #4b5563; font-size: 16px;'>Please use the following OTP to complete your login:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <span style='display: inline-block; padding: 15px 30px; background-color: #f3f4f6; color: #1f2937; letter-spacing: 8px; border-radius: 8px; font-weight: bold; font-size: 32px;'>{$otpCode}</span>
                            </div>
                            <p style='color: #6b7280; font-size: 14px;'>This code will expire in 5 minutes.</p>
                            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                            <p style='color: #9ca3af; font-size: 12px; text-align: center;'>&copy; " . date('Y') . " CitiLife Diagnostic Center. All rights reserved.</p>
                        </div>
                    ";
                    sendEmail($user['email'], $firstName, 'Login Verification Code - CitiLife System', $emailBody);

                    // Password is correct, start temporary session for OTP
                    unset($_SESSION['login_attempts']);
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['temp_role'] = $user['role'];
                    $_SESSION['temp_email'] = $user['email'];
                    $_SESSION['temp_branch_id'] = $user['branch_id'];
                    $_SESSION['temp_patient_id'] = $user['patient_id'];
                    $_SESSION['temp_name'] = !empty($user['first_name']) ? $user['first_name'] . ' ' . $user['last_name'] : '';
                    $_SESSION['temp_portal'] = 'patient';

                    header("Location: otp-login.php");
                    exit;
                }
            } else {
                $attempts['attempts']++;
                if ($attempts['attempts'] >= 8) {
                    $attempts['locked_until'] = time() + 900; // 15 minutes
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } elseif ($attempts['attempts'] == 7) {
                    $attempts['locked_until'] = time() + 300; // 5 minutes
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } elseif ($attempts['attempts'] == 6) {
                    $attempts['locked_until'] = time() + 60; // 1 minute
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } elseif ($attempts['attempts'] == 5) {
                    $attempts['locked_until'] = time() + 30; // 30 seconds
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                    if ($attempts['attempts'] >= 3) {
                        $warning = "Warning: Multiple failed attempts. Account will be locked after 5 fails.";
                    }
                }
            }
        }
    } else {
        $error = 'Please enter both email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login - CitiLife System</title>
    <!-- Use generated Tailwind CSS -->
    <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/tailwind/src/output.css">
    <script src="/<?= PROJECT_DIR ?>/public/assets/js/security.js?v=<?= time() ?>"></script>
    <style>
        /* Custom styles for a more premium look */
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

<body class="bg-pattern min-h-screen flex items-center justify-center p-4 sm:p-6 md:p-8">

    <div
        class="glass-panel w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all hover:scale-[1.01] duration-300">
        <div class="p-5 sm:p-8">
            <div class="text-center mb-5 sm:mb-8">
                <!-- Fallback to a styled text if logo image is missing -->
                <div
                    class="mx-auto w-16 h-16 sm:w-20 sm:h-20 bg-blue-50 rounded-full flex items-center justify-center mb-4 border border-blue-100 shadow-sm">
                    <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/citilife-logo.png" alt="CitiLife Logo"
                        class="h-10 w-10 sm:h-12 sm:w-12 object-contain"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <svg class="h-8 w-8 sm:h-10 sm:w-10 text-blue-600 hidden" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Patient Portal</h1>
                <p class="text-sm text-gray-500 mt-2">Welcome! Access your X-ray records.</p>
            </div>

            <?php if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-center gap-3 animate-pulse">
                    <div
                        class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-red-700 leading-tight">
                        Session expired due to inactivity. <span class="block text-[10px] font-normal opacity-70">Please log
                            in again.</span>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($is_locked): ?>
                <div class="mb-6 p-5 rounded-[20px] bg-red-50 text-red-700 flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-full mb-2">
                        <svg class="w-6 h-6 mr-2 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                clip-rule="evenodd" />
                        </svg>
                        <h3 class="font-bold text-red-800 text-[16px]">Access Locked</h3>
                    </div>
                    <p class="text-[14px] text-red-700 px-2">Too many failed attempts. Please try again after <strong
                            id="lockTimer" data-remaining="<?= $remaining ?>"><?= $time_str ?></strong>.</p>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-700 text-sm flex items-start">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($warning): ?>
                    <div
                        class="mb-6 p-4 rounded-lg bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 text-sm flex items-start">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span><?= htmlspecialchars($warning) ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4 sm:space-y-6">
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                        <input id="email" name="email" type="email" required autocomplete="username"
                            class="pl-10 appearance-none block w-full px-3 py-2.5 sm:py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm"
                            placeholder="Please enter your email">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            class="pl-10 pr-10 appearance-none block w-full px-3 py-2.5 sm:py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword('password', this)" tabindex="-1"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end">
                    <a href="forgot-password.php" class="text-sm font-medium text-red-600 hover:text-red-500 hover:underline">
                        Forgot your password?
                    </a>
                </div>

                <div class="pt-1 sm:pt-2">
                    <button type="submit" <?= $is_locked ? 'disabled' : '' ?>
                        class="w-full flex justify-center py-2.5 sm:py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white <?= $is_locked ? 'bg-gray-400 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700' ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                        Sign In as Patient
                    </button>
                </div>

                <div class="text-center mt-4 border-t pt-4">
                    <p class="text-sm text-gray-600 mb-2">
                        Don't have an account? <a href="patient-signup.php"
                            class="font-bold text-red-600 hover:text-red-500 hover:underline">Sign up here</a>
                    </p>
                    <p class="text-xs text-gray-500">
                        Are you a staff member? <a href="login.php"
                            class="font-medium text-gray-700 hover:text-gray-900 border-b border-gray-300 hover:border-gray-900">Go
                            to Staff Portal</a>
                    </p>
                </div>
            </form>
        </div>
        <div class="px-6 py-4 sm:px-8 bg-gray-50 border-t border-gray-100 flex justify-center">
            <p class="text-xs text-gray-400">&copy; <?= date('Y') ?> CitiLife X-ray System.</p>
        </div>
    </div>
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            btn.innerHTML = isPassword ?
                '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>' :
                '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>';
        }

        const lockTimer = document.getElementById('lockTimer');
        if (lockTimer) {
            let remaining = parseInt(lockTimer.getAttribute('data-remaining'), 10);
            const interval = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(interval);
                    window.location.reload();
                } else {
                    let text = '';
                    if (remaining > 60) {
                        text = Math.ceil(remaining / 60) + ' minutes';
                    } else {
                        text = remaining + ' seconds';
                    }
                    lockTimer.textContent = text;
                }
            }, 1000);
        }
    </script>
</body>

</html>