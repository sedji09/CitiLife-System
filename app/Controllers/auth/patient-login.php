<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

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
                    if (!function_exists('sendEmail')) {
                        require_once basePath('app/Helpers/mailer_helper.php');
                    }
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

                    header("Location: /" . PROJECT_DIR . "/otp-login");
                    exit;
                }
            } else {
                $attempts['attempts']++;
                if ($attempts['attempts'] >= 8) {
                    $attempts['locked_until'] = time() + 900; // 15 minutes
                    header("Location: /" . PROJECT_DIR . "/patient-login");
                    exit;
                } elseif ($attempts['attempts'] == 7) {
                    $attempts['locked_until'] = time() + 300; // 5 minutes
                    header("Location: /" . PROJECT_DIR . "/patient-login");
                    exit;
                } elseif ($attempts['attempts'] == 6) {
                    $attempts['locked_until'] = time() + 60; // 1 minute
                    header("Location: /" . PROJECT_DIR . "/patient-login");
                    exit;
                } elseif ($attempts['attempts'] == 5) {
                    $attempts['locked_until'] = time() + 30; // 30 seconds
                    header("Location: /" . PROJECT_DIR . "/patient-login");
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                    if ($attempts['attempts'] >= 3) {
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
    } else {
        $error = 'Please enter both email and password.';
    }
}

// Redirect back to landing page popup on any GET request or if there are errors
$params = ['login' => 1];
if (!empty($error)) $params['error'] = $error;
if (!empty($warning)) $params['warning'] = $warning;
if ($is_locked) $params['locked'] = $lock_message;

$qs = http_build_query($params);
header("Location: /" . PROJECT_DIR . "/?" . $qs);
exit;
?>