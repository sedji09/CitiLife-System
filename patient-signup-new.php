<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

if (isset($_SESSION['role'])) {
    header("Location: /" . PROJECT_DIR . "/dashboard");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientNumber = trim($_POST['patient_number'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $isEmailValid = filter_var($email, FILTER_VALIDATE_EMAIL);

    if (empty($patientNumber) || empty($firstName) || empty($lastName) || empty($birthdate) || empty($email)) {
        $error = 'Please fill out all fields.';
    } elseif (!$isEmailValid) {
        $error = 'Invalid Email format.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Lookup Patient
            $stmt = $pdo->prepare("
                SELECT id 
                FROM patients 
                WHERE LOWER(patient_number) = LOWER(?) 
                  AND LOWER(TRIM(first_name)) = LOWER(TRIM(?))
                  AND LOWER(TRIM(last_name)) = LOWER(TRIM(?))
                  AND birthdate = ?
                LIMIT 1
            ");
            $stmt->execute([$patientNumber, $firstName, $lastName, $birthdate]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$patient) {
                $error = 'We could not find a matching patient record. Please check your Patient ID and details.';
            } else {
                $patientId = $patient['id'];

                // 2. Check if already linked
                $stmtUser = $pdo->prepare("SELECT id FROM users WHERE patient_id = ? LIMIT 1");
                $stmtUser->execute([$patientId]);
                if ($stmtUser->fetch()) {
                    $error = 'This patient record is already linked to an active account. Please log in.';
                } else {
                    // 3. Create Verification Intent
                    $pdo->prepare("DELETE FROM account_verifications WHERE patient_id = ?")->execute([$patientId]);

                    $verificationToken = bin2hex(random_bytes(32));

                    $insertStmt = $pdo->prepare("
                        INSERT INTO account_verifications (token, patient_id, email, expires_at) 
                        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
                    ");
                    $insertStmt->execute([$verificationToken, $patientId, $email]);

                    // 4. Send Verification Email
                    require_once basePath('app/Helpers/mailer_helper.php');
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $verifyLink = $protocol . $_SERVER['HTTP_HOST'] . '/' . PROJECT_DIR . '/verify?token=' . $verificationToken;

                    $emailBody = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px;'>
                            <h2 style='color: #1f2937;'>Welcome to CitiLife System!</h2>
                            <p style='color: #4b5563; font-size: 16px;'>Hi {$firstName},</p>
                            <p style='color: #4b5563; font-size: 16px;'>Please click the button below to verify your email address. You will be able to create your password and access your records afterwards.</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$verifyLink}' style='display: inline-block; padding: 12px 24px; background-color: #dc2626; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>Verify Email Address</a>
                            </div>
                            <p style='color: #6b7280; font-size: 14px;'>If the button doesn't work, copy and paste this link into your browser:<br><a href='{$verifyLink}' style='color: #2563eb;'>{$verifyLink}</a></p>
                            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                            <p style='color: #9ca3af; font-size: 12px; text-align: center;'>&copy; " . date('Y') . " CitiLife Diagnostic Center. All rights reserved.</p>
                        </div>
                    ";
                    sendEmail($email, $firstName . ' ' . $lastName, 'Verify your Email Address - CitiLife System', $emailBody);

                    $pdo->commit();
                    $success = 'We found your record! Please check your email for the verification link to create your password.';
                }
            }
            if (!empty($error) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Patient Registration - CitiLife System</title>
    <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/tailwind/src/output.css">
    <style>
        .glass-panel { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .bg-pattern { background-color: #f0f2f5; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 24px 24px; }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4">

    <?php if ($success): ?>
        <div class="glass-panel w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden p-8">
            <div class="mb-6 p-4 rounded-xl bg-green-50 text-green-700 text-sm flex items-start border border-green-100">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <div class="text-center mt-6">
                <a href="patient-login" class="inline-flex justify-center w-full py-3.5 px-6 border border-transparent rounded-full shadow-sm text-[15px] font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                    Back to Login
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-8">
                <div class="text-center mb-8">
                    <div class="mx-auto w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mb-4 border border-blue-100 shadow-sm">
                        <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/citilife-logo.png" alt="CitiLife Logo" class="h-12 w-12 object-contain" onerror="this.style.display='none';">
                    </div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Register Account</h1>
                    <p class="text-sm text-gray-500 mt-2">Link your existing clinic records to a new online account.</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-700 text-sm flex items-start">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <div>
                        <label for="patient_number" class="block text-sm font-semibold text-gray-700 mb-1">Patient ID *</label>
                        <input id="patient_number" name="patient_number" type="text" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g. PAT-GAP-2026-001" value="<?= htmlspecialchars($patientNumber ?? '') ?>">
                        <p class="text-xs text-gray-500 mt-1">Found on your clinic receipt or given by staff.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-1">First Name *</label>
                            <input id="first_name" name="first_name" type="text" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 sm:text-sm" value="<?= htmlspecialchars($firstName ?? '') ?>">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-1">Last Name *</label>
                            <input id="last_name" name="last_name" type="text" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 sm:text-sm" value="<?= htmlspecialchars($lastName ?? '') ?>">
                        </div>
                    </div>

                    <div>
                        <label for="birthdate" class="block text-sm font-semibold text-gray-700 mb-1">Birthdate *</label>
                        <input id="birthdate" name="birthdate" type="date" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 sm:text-sm" value="<?= htmlspecialchars($birthdate ?? '') ?>">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address *</label>
                        <input id="email" name="email" type="email" required autocomplete="username" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:text-sm" placeholder="you@example.com" value="<?= htmlspecialchars($email ?? '') ?>">
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Verify Identity
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center border-t pt-4">
                    <p class="text-sm text-gray-600">
                        Already have an account? <a href="patient-login" class="font-bold text-red-600 hover:text-red-500 hover:underline">Log in here</a>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>