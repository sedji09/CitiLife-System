<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

// Capture and preserve redirect parameter
$redirectUrl = $_POST['redirect'] ?? $_GET['redirect'] ?? ($_SESSION['redirect_url'] ?? null);
if (!empty($redirectUrl)) {
    $_SESSION['redirect_url'] = $redirectUrl;
}

// If already logged in, redirect to intended target or dashboard
if (isset($_SESSION['role'])) {
    $target = url('dashboard');
    if ($_SESSION['role'] === 'patient' && !empty($redirectUrl)) {
        unset($_SESSION['redirect_url']);
        $target = $redirectUrl;
    }
    
    if (isset($_GET['iframe']) && $_GET['iframe'] == 1) {
        echo "<script>window.parent.location.href = '" . $target . "';</script>";
        exit;
    }
    
    redirect($target);
}

$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'inactivity') {
    $error = "Your session expired due to inactivity. Please log in again.";
}
$warning = '';
$is_locked = false;
$lock_message = '';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = ['attempts' => 0, 'locked_until' => 0];
}

$attempts = &$_SESSION['login_attempts'];
$currentTime = time();

$clientIp = function_exists('getClientIp') ? getClientIp() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$ipLimit = function_exists('checkRateLimit') ? checkRateLimit('login_patient_ip', $clientIp, 5, 900) : ['allowed' => true, 'remaining_seconds' => 0, 'attempts' => 0];

// Require CAPTCHA adaptively if 2 or more failed attempts
$requireCaptcha = ($attempts['attempts'] >= 2 || ($ipLimit['attempts'] ?? 0) >= 2);

if (!$ipLimit['allowed']) {
    $is_locked = true;
    $remaining = $ipLimit['remaining_seconds'];
    $time_str = $remaining > 60 ? ceil($remaining / 60) . " minutes" : $remaining . " seconds";
    $lock_message = "Too many failed login attempts. Please try again after $time_str.";
} elseif ($attempts['locked_until'] > $currentTime) {
    $is_locked = true;
    $remaining = $attempts['locked_until'] - $currentTime;
    $time_str = $remaining > 60 ? ceil($remaining / 60) . " minutes" : $remaining . " seconds";
    $lock_message = "Too many failed login attempts. Please try again after $time_str.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $turnstileToken = $_POST['cf-turnstile-response'] ?? '';

    // Check account-specific rate limit
    $emailLimit = function_exists('checkRateLimit') ? checkRateLimit('login_patient_email', $email, 5, 900) : ['allowed' => true, 'remaining_seconds' => 0, 'attempts' => 0];

    // Anti-bot validations
    if (!verifyHoneypot('website_hp')) {
        $error = "Automated login detected. Request blocked.";
    } elseif ($requireCaptcha && !verifyTurnstile($turnstileToken)) {
        $error = "Security verification required. Please complete the verification challenge.";
    } elseif (!$emailLimit['allowed']) {
        $is_locked = true;
        $remaining = $emailLimit['remaining_seconds'];
        $time_str = $remaining > 60 ? ceil($remaining / 60) . " minutes" : $remaining . " seconds";
        $lock_message = "This account is temporarily locked due to multiple failed login attempts. Please try again after $time_str.";
    } elseif (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
            // Prepare statement to fetch user by email along with patient name, prioritizing patient role
            $stmt = $pdo->prepare('
            SELECT u.*, p.first_name, p.last_name 
            FROM users u 
            LEFT JOIN patients p ON u.patient_id = p.id 
            WHERE u.email = :email 
            ORDER BY CASE WHEN u.role = \'patient\' THEN 1 ELSE 2 END 
            LIMIT 1
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

                    // Clear rate limits on successful auth
                    if (function_exists('clearRateLimit')) {
                        clearRateLimit('login_patient_ip', $clientIp);
                        clearRateLimit('login_patient_email', $email);
                    }

                    // Check if device is remembered (Skip OTP if valid token exists)
                    $rememberToken = $_COOKIE['remember_device'] ?? null;
                    if ($rememberToken) {
                        $stmtDevice = $pdo->prepare("SELECT id FROM user_devices WHERE user_id = ? AND device_token = ? AND expires_at > NOW() LIMIT 1");
                        $stmtDevice->execute([$user['id'], $rememberToken]);
                        if ($stmtDevice->fetch()) {
                            // Device remembered, skip OTP and start full session
                            session_regenerate_id(true);
                            $patientDisplayName = !empty($user['name']) ? $user['name'] : (!empty($user['first_name']) ? $user['first_name'] : trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['patient_id'] = $user['patient_id'];
                            $_SESSION['name'] = $patientDisplayName;
                            $_SESSION['branch_id'] = $user['branch_id'];
                            $_SESSION['LAST_ACTIVITY'] = time();

                            require_once basePath('app/Models/AuditLogModel.php');
                            $auditLogModel = new \AuditLogModel($pdo);
                            $auditLogModel->addLog(
                                $user['id'],
                                'Patient Login',
                                'Patient Portal',
                                'Session',
                                $user['id'],
                                "Successful login via remembered device",
                                $user['branch_id']
                            );

                            $dest = $redirectUrl ?: url('dashboard');
                            unset($_SESSION['redirect_url']);
                            redirect($dest);
                        }
                    }

                    // Generate OTP
                    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                    $updateStmt = $pdo->prepare("UPDATE users SET otp_code = ?, token_expires_at = ? WHERE id = ?");
                    $updateStmt->execute([$otpCode, $expiresAt, $user['id']]);

                    // Send email
                    $firstName = $user['first_name'] ?? 'Patient';
                    if (!function_exists('sendEmail')) {
                        require_once basePath('app/Helpers/mailer_helper.php');
                    }
                    $emailBody = renderOtpEmail($firstName, $otpCode, 'login verification', 5);
                    sendEmail($user['email'], $firstName, 'Login Verification Code - Citilife System', $emailBody);

                    // Password is correct, start temporary session for OTP
                    unset($_SESSION['login_attempts']);
                    $patientDisplayName = !empty($user['name']) ? $user['name'] : (!empty($user['first_name']) ? $user['first_name'] : trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['temp_role'] = $user['role'];
                    $_SESSION['temp_email'] = $user['email'];
                    $_SESSION['temp_branch_id'] = $user['branch_id'];
                    $_SESSION['temp_patient_id'] = $user['patient_id'];
                    $_SESSION['temp_name'] = $patientDisplayName;
                    $_SESSION['temp_portal'] = 'patient';
                    $_SESSION['temp_redirect_url'] = $redirectUrl;

                    redirect(url('otp-login'));
                }
            } else {
                $attempts['attempts']++;
                if ($attempts['attempts'] >= 8) {
                    $attempts['locked_until'] = time() + 900; // 15 minutes
                } elseif ($attempts['attempts'] == 7) {
                    $attempts['locked_until'] = time() + 300; // 5 minutes
                } elseif ($attempts['attempts'] == 6) {
                    $attempts['locked_until'] = time() + 60; // 1 minute
                } elseif ($attempts['attempts'] == 5) {
                    $attempts['locked_until'] = time() + 30; // 30 seconds
                }

                // Record IP and Email failed attempt
                $rateResIp = function_exists('recordFailedAttempt') ? recordFailedAttempt('login_patient_ip', $clientIp, 5, 900) : ['allowed' => true];
                $rateResEmail = function_exists('recordFailedAttempt') ? recordFailedAttempt('login_patient_email', $email, 5, 900) : ['allowed' => true];

                if ($attempts['attempts'] >= 5 || !$rateResIp['allowed'] || !$rateResEmail['allowed']) {
                    redirect(url('patient-login' . (!empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '')));
                }

                $error = 'Invalid email or password.';
                if ($attempts['attempts'] >= 3 || ($rateResIp['attempts'] ?? 0) >= 3) {
                    $warning = "Warning: Multiple failed attempts. Account will be locked after 5 fails.";
                }

                // Log the failed attempt
                require_once basePath('app/Models/AuditLogModel.php');
                $auditLogModel = new \AuditLogModel($pdo);
                $failedUserId = $user ? $user['id'] : 0;
                $auditLogModel->addLog(
                    $failedUserId,
                    'Failed Patient Login',
                    'Patient Portal',
                    'Session',
                    $failedUserId,
                    "Invalid email or password (" . substr($email, 0, 50) . ")"
                );
            }
        }
    }

$params = ['login' => 1];
if (!empty($error)) $params['error'] = $error;
if (!empty($warning)) $params['warning'] = $warning;
if ($is_locked) {
    $params['locked'] = $lock_message;
    $params['locked_seconds'] = $remaining ?? 900;
}
if (!empty($redirectUrl)) $params['redirect'] = $redirectUrl;

$qs = http_build_query($params);
redirect(url('?' . $qs));
?>