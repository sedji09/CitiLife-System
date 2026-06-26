<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: /" . PROJECT_DIR . "/login");
    exit;
}

$error = '';
$success = '';
$email = $_SESSION['temp_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch user security status
    $stmtStatus = $pdo->prepare("SELECT otp_resend_count, last_otp_resend_at, otp_locked_until FROM users WHERE id = ?");
    $stmtStatus->execute([$_SESSION['temp_user_id']]);
    $userSecurity = $stmtStatus->fetch();

    if (isset($_POST['resend'])) {
        // 1. Check Lockout
        if ($userSecurity['otp_locked_until'] && strtotime($userSecurity['otp_locked_until']) > time()) {
            $lockTime = ceil((strtotime($userSecurity['otp_locked_until']) - time()) / 60);
            $error = "Too many resend attempts. Please wait {$lockTime} minutes.";
        } else {
            // 2. Check Cooldown (doubles every time: 60s, 120s, 240s...)
            $resendCount = $userSecurity['otp_resend_count'] ?? 0;
            $cooldown = 60 * pow(2, $resendCount); 
            
            $lastResend = $userSecurity['last_otp_resend_at'] ? strtotime($userSecurity['last_otp_resend_at']) : 0;
            if (time() - $lastResend < $cooldown) {
                 $remaining = $cooldown - (time() - $lastResend);
                 $error = "Please wait {$remaining} seconds before resending.";
            } else {
                // 3. Proceed with Resend
                $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                
                $newResendCount = $resendCount + 1;
                // Lockout if they reach 3 resends (Total 4 codes sent)
                $lockedUntil = ($newResendCount >= 3) ? date('Y-m-d H:i:s', strtotime('+10 minutes')) : null;
                
                $updateStmt = $pdo->prepare("UPDATE users SET otp_code = ?, token_expires_at = ?, otp_resend_count = ?, last_otp_resend_at = NOW(), otp_locked_until = ? WHERE id = ?");
                $updateStmt->execute([$otpCode, $expiresAt, $newResendCount, $lockedUntil, $_SESSION['temp_user_id']]);

                                $firstName = $_SESSION['temp_name'] ?: 'User';
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px;'>
                        <h2 style='color: #1f2937;'>CitiLife System - New Login Code</h2>
                        <p style='color: #4b5563; font-size: 16px;'>Hi {$firstName},</p>
                        <p style='color: #4b5563; font-size: 16px;'>Here is your new verification code:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <span style='display: inline-block; padding: 15px 30px; background-color: #f3f4f6; color: #1f2937; letter-spacing: 8px; border-radius: 8px; font-weight: bold; font-size: 32px;'>{$otpCode}</span>
                        </div>
                        <p style='color: #6b7280; font-size: 14px;'>This code will expire in 5 minutes.</p>
                        <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                        <p style='color: #9ca3af; font-size: 12px; text-align: center;'>&copy; " . date('Y') . " CitiLife Diagnostic Center. All rights reserved.</p>
                    </div>
                ";
                sendEmail($email, $firstName, 'Your New Login Code - CitiLife System', $emailBody);
                $success = "A new verification code has been sent to your email.";
                if ($lockedUntil) $success .= " <br><b>Note:</b> You have reached the resend limit. Please wait 10 minutes for next try.";
                
                // Refresh security status for UI
                $stmtStatus->execute([$_SESSION['temp_user_id']]);
                $userSecurity = $stmtStatus->fetch();
            }
        }
    } else {
        $code = implode('', $_POST['otp'] ?? []);
        if (strlen($code) === 6) {
            $stmt = $pdo->prepare("SELECT otp_code, token_expires_at FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['temp_user_id']]);
            $user = $stmt->fetch();

            if ($user && $user['otp_code'] === $code) {
                if (strtotime($user['token_expires_at']) > time()) {
                    // Valid OTP
                    $pdo->prepare("UPDATE users SET otp_code = NULL, token_expires_at = NULL, otp_resend_count = 0, last_otp_resend_at = NULL, otp_locked_until = NULL WHERE id = ?")->execute([$_SESSION['temp_user_id']]);
                    
                    // Convert temp session to real session
                    $_SESSION['user_id'] = $_SESSION['temp_user_id'];
                    $_SESSION['role'] = $_SESSION['temp_role'];
                    $_SESSION['email'] = $_SESSION['temp_email'];
                    $_SESSION['branch_id'] = $_SESSION['temp_branch_id'];
                    
                    if ($_SESSION['temp_portal'] === 'patient') {
                        $_SESSION['patient_id'] = $_SESSION['temp_patient_id'];
                        $_SESSION['name'] = $_SESSION['temp_name'];
                    } else {
                        $_SESSION['name'] = $_SESSION['temp_name'];
                        $_SESSION['avatar'] = $_SESSION['temp_avatar'];
                    }
                    
                    // Handle "Remember this device"
                    if (isset($_POST['remember'])) {
                        $deviceToken = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        $stmtStore = $pdo->prepare("INSERT INTO user_devices (user_id, device_token, expires_at) VALUES (?, ?, ?)");
                        $stmtStore->execute([$_SESSION['temp_user_id'], $deviceToken, $expires]);
                        
                        setcookie('remember_device', $deviceToken, time() + (86400 * 30), "/");
                    }

                    // Clear temp session
                    unset($_SESSION['temp_user_id'], $_SESSION['temp_role'], $_SESSION['temp_email'], $_SESSION['temp_branch_id'], $_SESSION['temp_patient_id'], $_SESSION['temp_name'], $_SESSION['temp_avatar'], $_SESSION['temp_portal']);
                    
                    require_once basePath('app/Models/AuditLogModel.php');
                    $auditLogModel = new \AuditLogModel($pdo);
                    $auditLogModel->addLog(
                        $_SESSION['user_id'],
                        'Patient Login',
                        'Authentication',
                        'Session',
                        $_SESSION['user_id'],
                        "Successful login via OTP",
                        $_SESSION['branch_id']
                    );

                    header("Location: /" . PROJECT_DIR . "/dashboard");
                    exit;
                } else {
                    $error = "The verification code has expired. Please request a new one.";
                }
            } else {
                $error = "Invalid verification code.";
            }
        } else {
            $error = "Please enter all 6 digits.";
        }
    }
}

// Mask email for display
$emailParts = explode("@", $email);
$maskedEmail = substr($emailParts[0], 0, 2) . str_repeat("*", max(strlen($emailParts[0]) - 3, 1)) . substr($emailParts[0], -1) . "@" . $emailParts[1];

// Get remaining OTP expiration time
$stmt = $pdo->prepare("SELECT token_expires_at FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['temp_user_id']]);
$dbUser = $stmt->fetch();
$remainingSeconds = 0;
if ($dbUser && $dbUser['token_expires_at']) {
    $expiresAt = strtotime($dbUser['token_expires_at']);
    $remainingSeconds = max(0, $expiresAt - time());
}

// Get resend security status for UI
$stmtStatus = $pdo->prepare("SELECT otp_resend_count, last_otp_resend_at, otp_locked_until FROM users WHERE id = ?");
$stmtStatus->execute([$_SESSION['temp_user_id']]);
$userSecurity = $stmtStatus->fetch();

$resendCount = $userSecurity['otp_resend_count'] ?? 0;
$cooldownSeconds = 60 * pow(2, $resendCount);
$lastResendAt = $userSecurity['last_otp_resend_at'] ? strtotime($userSecurity['last_otp_resend_at']) : 0;
$resendRemaining = max(0, $cooldownSeconds - (time() - $lastResendAt));

if ($userSecurity['otp_locked_until'] && strtotime($userSecurity['otp_locked_until']) > time()) {
    $resendRemaining = strtotime($userSecurity['otp_locked_until']) - time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Security - CitiLife System</title>
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
        /* Hide number input arrows */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl overflow-hidden p-8 text-center">
        
        <div class="mx-auto w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-6 border border-red-100">
            <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-2">2-Step Verification</h1>
        <p class="text-sm text-gray-500 mb-3">We sent a 6-digit login code to <br><span class="font-bold text-gray-800"><?= htmlspecialchars($maskedEmail) ?></span></p>

        <div class="mb-5 flex justify-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-50 text-red-700 border border-red-200 shadow-sm" id="timerBadge">
                <svg class="mr-1.5 h-4 w-4 text-red-500" id="timerIcon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Expires in: <span id="countdownTimer" class="ml-1 font-bold tracking-widest">05:00</span>
            </span>
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

        <form method="POST" action="">
            <div class="flex justify-center gap-2 mb-6" id="otp-container">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="number" name="otp[]" maxlength="1" class="w-12 h-14 text-center text-xl font-bold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white" required>
                <?php endfor; ?>
            </div>
            <div class="flex items-center justify-start mb-6">
                <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500 cursor-pointer">
                <label for="remember" class="ml-2 text-sm text-gray-600 cursor-pointer select-none">Remember this device for 30 days</label>
            </div>
            <button type="submit" id="verifyBtn" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                Verify Login
            </button>
        </form>

        <form method="POST" action="" class="mt-6">
            <input type="hidden" name="resend" value="1">
            <p class="text-sm text-gray-600">Didn't receive the code? 
                <button type="submit" id="resendBtn" class="font-bold text-red-600 hover:underline bg-transparent border-none cursor-pointer disabled:text-gray-400 disabled:no-underline disabled:cursor-not-allowed">Resend Code</button>
                <span id="resendTimerText" class="hidden text-xs text-gray-400 block mt-1"></span>
            </p>
        </form>
        
        <div class="mt-4 border-t pt-4">
            <a href="<?= $_SESSION['temp_portal'] === 'patient' ? 'patient-login' : 'login' ?>" class="text-sm text-gray-500 hover:text-gray-800">Cancel and Return to Login</a>
        </div>
    </div>

    <script>
        // OTP input behavior (auto-advance)
        const inputs = document.querySelectorAll('#otp-container input');
        
        inputs.forEach((input, index) => {
            // Handle paste
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                if (pastedData) {
                    [...pastedData].forEach((char, i) => {
                        if (inputs[i]) {
                            inputs[i].value = char;
                            if (inputs[i + 1]) inputs[i + 1].focus();
                        }
                    });
                }
            });

            // Handle typing
            input.addEventListener('input', function() {
                if (this.value.length > 1) {
                    this.value = this.value.slice(0, 1);
                }
                if (this.value !== '' && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';
                }
            });
        });

        // Countdown Timer Logic
        let remainingSeconds = <?= $remainingSeconds ?>;
        const timerDisplay = document.getElementById('countdownTimer');
        const timerBadge = document.getElementById('timerBadge');
        const timerIcon = document.getElementById('timerIcon');
        const submitBtn = document.getElementById('verifyBtn');

        function updateTimer() {
            if (remainingSeconds <= 0) {
                timerDisplay.textContent = "00:00";
                
                // Style as expired
                timerBadge.classList.replace('bg-red-50', 'bg-gray-100');
                timerBadge.classList.replace('text-red-700', 'text-gray-500');
                timerBadge.classList.replace('border-red-200', 'border-gray-200');
                timerIcon.classList.replace('text-red-500', 'text-gray-400');
                
                // Disable inputs
                submitBtn.disabled = true;
                submitBtn.classList.replace('bg-red-600', 'bg-gray-400');
                submitBtn.classList.replace('hover:bg-red-700', 'hover:bg-gray-400');
                submitBtn.classList.add('cursor-not-allowed');
                
                inputs.forEach(input => {
                    input.disabled = true;
                    input.classList.add('bg-gray-50', 'text-gray-400', 'cursor-not-allowed');
                });
                
                return;
            }

            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            timerDisplay.textContent = 
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            remainingSeconds--;
            setTimeout(updateTimer, 1000);
        }
        
        if (remainingSeconds >= 0) {
            updateTimer();
        }

        // Resend Cooldown Logic
        let resendRemaining = <?= $resendRemaining ?>;
        const resendBtn = document.getElementById('resendBtn');
        const resendTimerText = document.getElementById('resendTimerText');
        const isLocked = <?= ($userSecurity['otp_locked_until'] && strtotime($userSecurity['otp_locked_until']) > time()) ? 'true' : 'false' ?>;

        function updateResendTimer() {
            if (resendRemaining <= 0) {
                resendBtn.disabled = false;
                resendTimerText.classList.add('hidden');
                return;
            }

            resendBtn.disabled = true;
            resendTimerText.classList.remove('hidden');
            
            if (isLocked) {
                const mins = Math.floor(resendRemaining / 60);
                const secs = resendRemaining % 60;
                resendTimerText.textContent = `Too many attempts. Try ulit after ${mins}m ${secs}s`;
            } else {
                resendTimerText.textContent = `Wait ${resendRemaining}s bago mag-resend`;
            }

            resendRemaining--;
            setTimeout(updateResendTimer, 1000);
        }
        updateResendTimer();
    </script>
</body>
</html>
