<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$message = '';
$is_success = false;

if (empty($token)) {
    $message = "Invalid or missing verification token.";
} else {
    // Check token
    $stmt = $pdo->prepare("SELECT * FROM account_verifications WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification) {
        $message = "Invalid or expired verification token. Please sign up again.";
    } else {
        $patientId = $verification['patient_id'];
        $email = $verification['email'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($password) || empty($confirmPassword)) {
                $error = "Please fill in all fields.";
            } elseif ($password !== $confirmPassword) {
                $error = "Passwords do not match.";
            } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                $error = "Password must be at least 8 characters and include uppercase, number, and special character.";
            } else {
                try {
                    $pdo->beginTransaction();

                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Fetch patient details to properly populate user attributes
                    $stmtP = $pdo->prepare("SELECT first_name, middle_name, last_name, branch_id FROM patients WHERE id = ?");
                    $stmtP->execute([$patientId]);
                    $pInfo = $stmtP->fetch(PDO::FETCH_ASSOC);
                    $firstName = trim($pInfo['first_name'] ?? '');
                    $defaultDisplayName = !empty($firstName) ? $firstName : trim(($pInfo['first_name'] ?? '') . ' ' . ($pInfo['last_name'] ?? ''));
                    $branchId = $pInfo['branch_id'] ?? null;

                    // Check if a user record already exists for this patient or email
                    $stmtCheck = $pdo->prepare("SELECT id, name FROM users WHERE patient_id = ? OR email = ? LIMIT 1");
                    $stmtCheck->execute([$patientId, $email]);
                    $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if ($existingUser) {
                        $userId = $existingUser['id'];
                        $userName = !empty($existingUser['name']) ? $existingUser['name'] : $defaultDisplayName;
                        $stmtUpdate = $pdo->prepare("UPDATE users SET password = ?, role = 'patient', status = 'Active', patient_id = ?, is_email_verified = 1, name = ?, branch_id = ? WHERE id = ?");
                        $stmtUpdate->execute([$hashedPassword, $patientId, $userName, $branchId, $userId]);
                    } else {
                        $userName = $defaultDisplayName;
                        $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status, patient_id, is_email_verified, name, branch_id) VALUES (?, ?, 'patient', 'Active', ?, 1, ?, ?)");
                        $stmt->execute([$email, $hashedPassword, $patientId, $userName, $branchId]);
                        $userId = $pdo->lastInsertId();
                    }

                    $pdo->prepare("DELETE FROM account_verifications WHERE token = ?")->execute([$token]);

                    $pdo->commit();

                    $_SESSION['user_id'] = $userId;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'patient';
                    $_SESSION['patient_id'] = $patientId;
                    $_SESSION['name'] = $userName;
                    $_SESSION['branch_id'] = $branchId;

                    require_once basePath('app/Models/AuditLogModel.php');
                    $auditLogModel = new \AuditLogModel($pdo);
                    try {
                        $auditLogModel->addLog(
                            $userId,
                            'Patient Account Created',
                            'Patient Portal',
                            'User',
                            $userId,
                            "Patient verified email and created portal password",
                            $branchId
                        );
                    } catch (\Throwable $logEx) {
                        // Audit log is best-effort
                    }

                    header("Location: /" . PROJECT_DIR . "/dashboard");
                    exit;

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = "An error occurred: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Password - <?= htmlspecialchars(getSystemName()) ?></title>
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

        /* Password strength indicator animation */
        .strength-bar {
            transition: width 0.3s ease, background-color 0.3s ease;
        }
    </style>
</head>

<body class="bg-pattern min-h-screen flex items-center justify-center p-4 sm:p-6 md:p-8">

    <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all hover:scale-[1.01] duration-300">
        <div class="p-5 sm:p-8">
            <div class="text-center mb-5 sm:mb-8">
                <!-- Citilife Logo -->
                <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 bg-red-50 rounded-full flex items-center justify-center mb-4 border border-red-100 shadow-sm">
                    <img src="<?= getSystemLogoUrl() ?>" alt="<?= htmlspecialchars(getSystemName()) ?> Logo"
                        class="h-10 w-10 sm:h-12 sm:w-12 object-contain"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <svg class="h-8 w-8 sm:h-10 sm:w-10 text-red-600 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Create Password</h1>
                <p class="text-sm text-gray-500 mt-2">Secure your portal account to view your records.</p>
            </div>

            <?php if (!empty($message)): ?>
                <!-- Error / Invalid Token State -->
                <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-700 text-sm flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <div class="text-center">
                    <a href="/<?= PROJECT_DIR ?>/?signup=1"
                        class="text-sm font-semibold text-red-600 hover:text-red-700 hover:underline transition-colors">
                        ← Return to Sign Up
                    </a>
                </div>
            <?php else: ?>

                <?php if (!empty($error)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-700 text-sm flex items-start">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-4 sm:space-y-6" onsubmit="return validatePasswords()">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="pl-10 pr-10 appearance-none block w-full px-3 py-2.5 sm:py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors sm:text-sm"
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

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                class="pl-10 pr-10 appearance-none block w-full px-3 py-2.5 sm:py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors sm:text-sm"
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword('confirm_password', this)" tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p id="match_indicator" class="text-xs font-semibold mt-1 hidden"></p>
                    </div>

                    <!-- Password Requirements -->
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 mb-1.5">Password requirements:</p>
                        <ul class="space-y-1">
                            <li id="req-length" class="text-xs text-gray-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                At least 8 characters
                            </li>
                            <li id="req-upper" class="text-xs text-gray-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Uppercase letter
                            </li>
                            <li id="req-number" class="text-xs text-gray-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Number
                            </li>
                            <li id="req-special" class="text-xs text-gray-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Special character
                            </li>
                        </ul>
                    </div>

                    <!-- Submit -->
                    <div class="pt-1 sm:pt-2">
                        <button type="submit"
                            class="w-full flex justify-center py-2.5 sm:py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Create Account
                        </button>
                    </div>
                </form>

            <?php endif; ?>
        </div>

        <div class="px-6 py-4 sm:px-8 bg-gray-50 border-t border-gray-100 flex justify-center">
            <p class="text-xs text-gray-400">&copy; <?= date('Y') ?> Citilife Diagnostic Center. All rights reserved.</p>
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

        const pwd = document.getElementById('password');
        const confirmPwd = document.getElementById('confirm_password');
        const indicator = document.getElementById('match_indicator');

        const reqs = {
            length:  { el: document.getElementById('req-length'),  test: v => v.length >= 8 },
            upper:   { el: document.getElementById('req-upper'),   test: v => /[A-Z]/.test(v) },
            number:  { el: document.getElementById('req-number'),  test: v => /[0-9]/.test(v) },
            special: { el: document.getElementById('req-special'), test: v => /[^A-Za-z0-9]/.test(v) },
        };

        function updateRequirements(val) {
            Object.values(reqs).forEach(({ el, test }) => {
                const passed = test(val);
                el.classList.toggle('text-green-600', passed);
                el.classList.toggle('text-gray-400', !passed);
            });
        }

        function checkMatch() {
            if (!confirmPwd.value.length) { indicator.classList.add('hidden'); return; }
            indicator.classList.remove('hidden');
            const match = pwd.value === confirmPwd.value;
            indicator.textContent = match ? 'Passwords match ✓' : 'Passwords do not match';
            indicator.style.color = match ? '#16a34a' : '#ef4444';
        }

        if (pwd) {
            pwd.addEventListener('input', () => { updateRequirements(pwd.value); checkMatch(); });
            confirmPwd.addEventListener('input', checkMatch);
        }

        function validatePasswords() {
            if (!pwd || !confirmPwd) return true;
            if (pwd.value !== confirmPwd.value) {
                alert('Passwords do not match.');
                return false;
            }
            const regex = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
            if (!regex.test(pwd.value)) {
                alert('Password must be at least 8 characters and include an uppercase letter, number, and special character.');
                return false;
            }
            return true;
        }
    </script>
</body>

</html>