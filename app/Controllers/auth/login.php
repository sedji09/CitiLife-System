<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

// Ensure login lockout column exists
try {
    $pdo->exec('ALTER TABLE users ADD COLUMN login_locked_until DATETIME DEFAULT NULL AFTER otp_locked_until');
} catch (\Exception $e) {
}

if (!function_exists('persistStaffLoginLock')) {
    function persistStaffLoginLock($pdo, $email, $lockedUntilTimestamp)
    {
        if (empty($email) || $lockedUntilTimestamp <= time()) {
            return;
        }
        $stmt = $pdo->prepare("UPDATE users SET login_locked_until = ? WHERE email = ? AND role != 'patient'");
        $stmt->execute([date('Y-m-d H:i:s', $lockedUntilTimestamp), $email]);
    }
}

if (!function_exists('clearStaffLoginLock')) {
    function clearStaffLoginLock($pdo, $userId)
    {
        $stmt = $pdo->prepare('UPDATE users SET login_locked_until = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    }
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['role'])) {
    header("Location: /" . PROJECT_DIR . "/dashboard");
    exit;
}

$error = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'deactivated') {
        $error = "Your account has been deactivated. Please contact the administrator.";
    } elseif ($_GET['error'] === 'deleted') {
        $error = "Your account has been deleted. Please contact the administrator.";
    } elseif ($_GET['error'] === 'branch_deactivated') {
        $error = "Your branch has been deactivated. Please contact the administrator.";
    }
}

$warning = '';
$is_locked = false;
$lock_message = '';

if (!isset($_SESSION['staff_login_attempts'])) {
    $_SESSION['staff_login_attempts'] = ['attempts' => 0, 'locked_until' => 0];
}

$attempts = &$_SESSION['staff_login_attempts'];
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
            // Prepare statement to fetch user by email, prioritizing staff roles
            $stmt = $pdo->prepare('
                SELECT * FROM users 
                WHERE email = :email 
                ORDER BY CASE WHEN role != \'patient\' THEN 1 ELSE 2 END 
                LIMIT 1
            ');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Check if user exists and verify password
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'Inactive') {
                    $error = "Your account has been deactivated. Please contact the administrator.";
                } elseif ($user['role'] === 'patient') {
                    $error = "This portal is for Staff and Administrators only. Please use the Patient Portal.";
                } else {
                    // Check if user's branch has been deactivated
                    $isBranchInactive = false;
                    if (!empty($user['branch_id']) && !in_array($user['role'], ['admin_central', 'it_admin', 'patient'])) {
                        $stmtBranch = $pdo->prepare("SELECT status, name FROM branches WHERE id = ?");
                        $stmtBranch->execute([$user['branch_id']]);
                        $branchRow = $stmtBranch->fetch();
                        if ($branchRow && ($branchRow['status'] ?? '') === 'Inactive') {
                            $isBranchInactive = true;
                            $error = "The " . htmlspecialchars($branchRow['name']) . " branch has been deactivated. Staff login is disabled.";
                        }
                    }

                    if (!$isBranchInactive) {
                        // Check if device is remembered (Skip OTP if valid token exists)
                        $rememberToken = $_COOKIE['remember_device'] ?? null;
                        if ($rememberToken) {
                            $stmtDevice = $pdo->prepare("SELECT id FROM user_devices WHERE user_id = ? AND device_token = ? AND expires_at > NOW() LIMIT 1");
                            $stmtDevice->execute([$user['id'], $rememberToken]);
                            if ($stmtDevice->fetch()) {
                                // Device remembered, skip OTP and start full session
                                unset($_SESSION['staff_login_attempts']);
                                clearStaffLoginLock($pdo, $user['id']);
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['role'] = $user['role'];
                                $_SESSION['email'] = $user['email'];
                                $_SESSION['name'] = $user['name'] ?? '';
                                $_SESSION['avatar'] = $user['avatar'] ?? null;
                                $_SESSION['branch_id'] = $user['branch_id'];

                                require_once basePath('app/Models/AuditLogModel.php');
                                $auditLogModel = new \AuditLogModel($pdo);
                                $auditLogModel->addLog(
                                    $user['id'],
                                    'Staff Login',
                                    'Authentication',
                                    'Session',
                                    $user['id'],
                                    "Successful login via remembered device",
                                    $user['branch_id']
                                );

                                header("Location: /" . PROJECT_DIR . "/dashboard");
                                exit;
                            }
                        }

                        // BYPASS OTP: Set full session variables immediately and proceed to dashboard
                        unset($_SESSION['staff_login_attempts']);
                        clearStaffLoginLock($pdo, $user['id']);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['name'] = $user['name'] ?? '';
                        $_SESSION['avatar'] = $user['avatar'] ?? null;
                        $_SESSION['branch_id'] = $user['branch_id'];

                        header("Location: /" . PROJECT_DIR . "/dashboard");
                        exit;
                    }
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

                if ($attempts['attempts'] >= 5) {
                    persistStaffLoginLock($pdo, $email, $attempts['locked_until']);
                    header("Location: /" . PROJECT_DIR . "/login");
                    exit;
                }

                $error = 'Invalid email or password.';
                if ($attempts['attempts'] >= 3) {
                    $warning = "Warning: Multiple failed attempts. Account will be locked after 5 fails.";
                }

                require_once basePath('app/Models/AuditLogModel.php');
                $auditLogModel = new \AuditLogModel($pdo);
                $failedUserId = $user ? $user['id'] : 0;
                $auditLogModel->addLog(
                    $failedUserId,
                    'Failed Staff Login',
                    'Authentication',
                    'Session',
                    $failedUserId,
                    "Invalid email or password (" . substr($email, 0, 50) . ")"
                );
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
    <title>Login - <?= htmlspecialchars(getSystemName()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="/<?= PROJECT_DIR ?>/public/assets/js/security.js?v=<?= time() ?>"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f3f4f6;
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 24px 24px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .modal-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            width: 100%;
            max-width: 440px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
            transform: translateY(0);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal-header {
            text-align: center;
            padding: 36px 32px 0;
        }

        .modal-logo-wrapper {
            width: 72px;
            height: 72px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            border: 1px solid #fecaca;
        }

        .modal-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .modal-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .modal-header p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 28px;
        }

        .modal-form {
            padding: 0 32px 36px;
        }

        .modal-alert-error {
            background: #fef2f2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #fecaca;
            line-height: 1.4;
        }

        .modal-alert-warning {
            background: #fffbeb;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #fde68a;
            line-height: 1.4;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #94a3b8;
            pointer-events: none;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 15px;
            color: #0f172a;
            background: #fff;
            transition: all 0.2s;
            outline: none;
            box-sizing: border-box;
        }

        .input-wrapper input:focus {
            border-color: #e20f10;
            box-shadow: 0 0 0 3px rgba(226, 15, 16, 0.1);
        }

        .modal-forgot {
            text-align: right;
            margin-bottom: 24px;
        }

        .modal-forgot a {
            font-size: 13px;
            font-weight: 600;
            color: #e20f10;
            text-decoration: none;
            transition: color 0.15s;
        }

        .modal-forgot a:hover {
            text-decoration: underline;
        }

        .modal-submit-btn {
            width: 100%;
            padding: 14px;
            background: #e20f10;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(226, 15, 16, 0.25);
        }

        .modal-submit-btn:hover:not(:disabled) {
            background: #c10d0d;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(226, 15, 16, 0.35);
        }

        .modal-submit-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .login-footer {
            margin-top: 20px;
            text-align: center;
        }

        .login-footer a {
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.15s;
        }

        .login-footer a:hover {
            color: #0f172a;
        }

        .login-footer p {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
        }

        @media (max-width: 480px) {
            .modal-card { max-width: 100%; border-radius: 20px; }
            .modal-header { padding: 28px 24px 0; }
            .modal-form { padding: 0 24px 28px; }
            .modal-logo-wrapper { width: 60px; height: 60px; }
            .modal-logo { width: 32px; height: 32px; }
            .modal-header h2 { font-size: 24px; }
        }
    </style>
</head>

<body>

    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-logo-wrapper">
                <img src="<?= getSystemLogoUrl() ?>" alt="<?= htmlspecialchars(getSystemName()) ?> Logo" class="modal-logo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <svg class="modal-logo" style="display:none; color: #dc2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h2>Staff Portal</h2>
            <p>Welcome internal staff! Please enter your details.</p>
        </div>

        <form id="loginForm" name="loginForm" method="POST" action="/<?= PROJECT_DIR ?>/login" autocomplete="on" class="modal-form">
            <?php if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'): ?>
                <div class="modal-alert-error">
                    Session expired due to inactivity. Please log in again.
                </div>
            <?php endif; ?>

            <?php if ($is_locked): ?>
                <div class="modal-alert-error">
                    <strong style="display: block; font-size: 15px; margin-bottom: 4px;">Access Locked</strong>
                    Too many failed attempts. Please try again after <strong id="lockTimer" data-remaining="<?= $remaining ?>"><?= $time_str ?></strong>.
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="modal-alert-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ($warning): ?>
                    <div class="modal-alert-warning">
                        <?= htmlspecialchars($warning) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="input-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="Please enter your email">
                </div>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-wrapper" style="position: relative;">
                    <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••" style="padding-right: 42px;">
                    <button type="button" onclick="toggleModalPassword('password', this)" tabindex="-1" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; cursor: pointer; color: #9ca3af;">
                        <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path class="eye-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path class="eye-slash-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="modal-forgot">
                <a href="/<?= PROJECT_DIR ?>/forgot-password?portal=staff">Forgot your password?</a>
            </div>

            <button type="submit" class="modal-submit-btn" <?= $is_locked ? 'disabled' : '' ?>>Log in</button>
        </form>
    </div>

    <div class="login-footer">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(getSystemName()) ?>. All rights reserved.</p>
    </div>

    <script>
        sessionStorage.clear();

        function toggleModalPassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const svgPath = btn.querySelector('svg path.eye-path');
            const svgPathStrikethrough = btn.querySelector('svg path.eye-slash-path');
            
            if (input.type === 'password') {
                input.type = 'text';
                svgPath.setAttribute('d', 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21');
                svgPathStrikethrough.setAttribute('d', '');
            } else {
                input.type = 'password';
                svgPath.setAttribute('d', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z');
                svgPathStrikethrough.setAttribute('d', 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z');
            }
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