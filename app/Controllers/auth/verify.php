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
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status, patient_id, is_email_verified) VALUES (?, ?, 'patient', 'Active', ?, 1)");
                    $stmt->execute([$email, $hashedPassword, $patientId]);
                    $userId = $pdo->lastInsertId();

                    $pdo->prepare("DELETE FROM account_verifications WHERE id = ?")->execute([$verification['id']]);

                    $pdo->commit();

                    $_SESSION['user_id'] = $userId;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'patient';

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
    <title>Complete Account Setup - CitiLife System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden p-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Password</h2>
            <p class="text-gray-500">Secure your portal account to view your records.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 flex items-start gap-3">
                <i class="fas fa-exclamation-circle mt-0.5"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($message) ?></p>
            </div>
            <div class="text-center">
                <a href="/<?= PROJECT_DIR ?>/patient-signup" class="text-blue-600 font-medium hover:underline">Return to Sign Up</a>
            </div>
        <?php else: ?>

            <?php if (!empty($error)): ?>
                <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                    <p class="text-sm font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-5" onsubmit="return validatePasswords()">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pl-10">
                        <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pl-10">
                        <i class="fas fa-check-circle absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <p id="match_indicator" class="text-xs font-semibold mt-1 hidden"></p>
                </div>

                <div class="text-xs text-gray-500 mt-2 space-y-1">
                    <p>Password requirements:</p>
                    <ul class="list-disc pl-5">
                        <li>At least 8 characters</li>
                        <li>Uppercase letter</li>
                        <li>Number</li>
                        <li>Special character</li>
                    </ul>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition-colors mt-6 shadow-md hover:shadow-lg focus:ring-4 focus:ring-blue-200">
                    Create Account
                </button>
            </form>

            <script>
                const pwd = document.getElementById('password');
                const confirmPwd = document.getElementById('confirm_password');
                const indicator = document.getElementById('match_indicator');

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

                function validatePasswords() {
                    if (pwd.value !== confirmPwd.value) {
                        alert("Passwords do not match.");
                        return false;
                    }
                    const regex = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
                    if (!regex.test(pwd.value)) {
                        alert("Password must be at least 8 characters and include an uppercase letter, number, and special character.");
                        return false;
                    }
                    return true;
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>