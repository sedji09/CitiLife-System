<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (empty($token)) {
    header("Location: /" . PROJECT_DIR . "/forgot-password");
    exit;
}

// Verify token
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.role, p.first_name 
    FROM users u 
    LEFT JOIN patients p ON u.patient_id = p.id 
    WHERE u.reset_password_token = ? AND u.reset_password_expires_at > NOW() 
    LIMIT 1
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if ($user) {
    $validToken = true;
} else {
    $error = "The reset link is invalid or has expired. Please request a new one.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else if (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } else if (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } else if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must contain at least one special character.";
    } else if ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_password_token = NULL, reset_password_expires_at = NULL WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $user['id']]);

        $success = "Your password has been reset successfully. You can now log in.";
        $validToken = false; // Hide form after success
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CitiLife System</title>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <?php
            $greetName = 'User';
            if (isset($user['role'])) {
                if ($user['role'] === 'patient' && !empty($user['first_name'])) {
                    $greetName = $user['first_name'];
                } else if (!empty($user['name'])) {
                    $greetName = explode(' ', $user['name'])[0];
                }
            }
            ?>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Reset Password</h1>
            <p class="text-sm text-gray-500 mt-2">Hi <?= htmlspecialchars($greetName) ?>, please enter your new password below.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php if (!$validToken): ?>
                <a href="forgot-password" class="block text-center text-sm font-bold text-red-600 hover:underline mt-4">Request new link</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
            <a href="patient-login" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 text-center">
                Go to Login
            </a>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" name="password" id="password" required minlength="8"
                            class="w-full pl-10 pr-10 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword('password', this)" tabindex="-1" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </button>
                    </div>
                    <!-- DESKTOP CHECKER -->
                    <div class="mt-2 text-left bg-gray-50 rounded-lg p-3 border border-gray-100 hidden"
                        id="d_pw_checker">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-xs font-semibold text-gray-500">Password Strength:</span>
                            <span class="text-xs font-bold pw-label text-red-500">Weak</span>
                        </div>
                        <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden mb-3">
                            <div class="h-full bg-red-500 transition-all duration-300 pw-bar" style="width: 0%">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs font-medium text-gray-600">
                            <div class="flex items-center gap-1.5 pw-req-length text-red-600">
                                <svg class="w-3.5 h-3.5 icon-x" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg class="w-3.5 h-3.5 icon-check hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>8+ characters</span>
                            </div>
                            <div class="flex items-center gap-1.5 pw-req-upper text-red-600">
                                <svg class="w-3.5 h-3.5 icon-x" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg class="w-3.5 h-3.5 icon-check hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Uppercase letter</span>
                            </div>
                            <div class="flex items-center gap-1.5 pw-req-number text-red-600">
                                <svg class="w-3.5 h-3.5 icon-x" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg class="w-3.5 h-3.5 icon-check hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>At least one number</span>
                            </div>
                            <div class="flex items-center gap-1.5 pw-req-special text-red-600">
                                <svg class="w-3.5 h-3.5 icon-x" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg class="w-3.5 h-3.5 icon-check hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Special Character</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-1">Confirm New Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                            class="w-full pl-10 pr-10 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword('confirm_password', this)" tabindex="-1" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </button>
                    </div>
                    <div id="match_indicator" class="text-xs font-semibold mt-1.5 hidden"></div>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                    Update Password
                </button>
            </form>
        <?php endif; ?>
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

        // Initialize password strengths
        function initPwChecker(inputId, checkerId) {
            const input = document.getElementById(inputId);
            const checker = document.getElementById(checkerId);
            if (!input || !checker) return;

            const reqs = [
                { class: '.pw-req-length', regex: /.{8,}/ },
                { class: '.pw-req-upper', regex: /[A-Z]/ },
                { class: '.pw-req-number', regex: /[0-9]/ },
                { class: '.pw-req-special', regex: /[^A-Za-z0-9]/ }
            ];

            input.addEventListener('focus', function () {
                checker.classList.remove('hidden');
            });

            input.addEventListener('input', function (e) {
                const val = e.target.value;
                let passed = 0;

                reqs.forEach(req => {
                    const el = checker.querySelector(req.class);
                    const iconX = el.querySelector('.icon-x');
                    const iconCheck = el.querySelector('.icon-check');
                    if (req.regex.test(val)) {
                        passed++;
                        el.classList.remove('text-red-600');
                        el.classList.add('text-green-600');
                        iconX.classList.add('hidden');
                        iconCheck.classList.remove('hidden');
                    } else {
                        el.classList.remove('text-green-600');
                        el.classList.add('text-red-600');
                        iconX.classList.remove('hidden');
                        iconCheck.classList.add('hidden');
                    }
                });

                const bar = checker.querySelector('.pw-bar');
                const label = checker.querySelector('.pw-label');
                const percent = (passed / reqs.length) * 100;

                bar.style.width = percent + '%';
                bar.className = 'h-full transition-all duration-300 pw-bar ';
                label.className = 'text-xs font-bold pw-label ';

                if (val.length === 0) {
                    label.textContent = '';
                    bar.style.backgroundColor = 'transparent';
                } else if (passed <= 1) {
                    bar.style.backgroundColor = '#ef4444'; // red-500
                    label.style.color = '#ef4444';
                    label.textContent = 'Weak';
                } else if (passed <= 3) {
                    bar.style.backgroundColor = '#eab308'; // yellow-500
                    label.style.color = '#eab308';
                    label.textContent = 'Medium';
                } else {
                    bar.style.backgroundColor = '#22c55e'; // green-500
                    label.style.color = '#22c55e';
                    label.textContent = 'Strong';
                }
            });
        }

        // Initialize password match checker
        function initMatchChecker(pwdId, confirmId, indicatorId) {
            const pwd = document.getElementById(pwdId);
            const confirmPwd = document.getElementById(confirmId);
            const indicator = document.getElementById(indicatorId);

            if (!pwd || !confirmPwd || !indicator) return;

            function checkMatch() {
                const val1 = pwd.value;
                const val2 = confirmPwd.value;

                if (val2.length === 0) {
                    indicator.classList.add('hidden');
                    return;
                }

                indicator.classList.remove('hidden');
                if (val1 === val2) {
                    indicator.textContent = 'Passwords match';
                    indicator.style.color = '#22c55e'; // green-500
                } else {
                    indicator.textContent = 'Passwords do not match';
                    indicator.style.color = '#ef4444'; // red-500
                }
            }

            pwd.addEventListener('input', checkMatch);
            confirmPwd.addEventListener('input', checkMatch);
        }

        document.addEventListener('DOMContentLoaded', function () {
            initPwChecker('password', 'd_pw_checker');
            initMatchChecker('password', 'confirm_password', 'match_indicator');
        });
    </script>
</body>
</html>
