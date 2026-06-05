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

// Helper: generate next case number
function generateCaseNumber($pdo, $branchId)
{
    if (!$branchId)
        return 'CX-' . date('Y') . '-0000';
    $stmtB = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
    $stmtB->execute([$branchId]);
    $branchName = $stmtB->fetchColumn() ?: 'General';

    $year = date('Y');
    $stmtLast = $pdo->prepare("SELECT case_number FROM cases WHERE case_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmtLast->execute(["CX-{$year}-%"]);
    $lastCase = $stmtLast->fetchColumn();

    if ($lastCase && preg_match('/CX-' . $year . '-(\d+)/', $lastCase, $m)) {
        $caseIndex = (int) $m[1] + 1;
    } else {
        $caseIndex = 1;
    }

    return 'CX-' . $year . '-' . str_pad($caseIndex, 4, '0', STR_PAD_LEFT);
}

// Fetch branches for dropdown
$branches = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM branches ORDER BY name ASC");
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore error
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $sex = $_POST['sex'] ?? 'Male';
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $branchId = $_POST['branch_id'] ?? '';
    if ($branchId === '')
        $branchId = null;

    $email = trim($_POST['email'] ?? '');
    // SANITATION using filter_var();
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // VALIDATION using preg_match();
    $namePattern = "/^[a-zA-Z\s]+$/";
    $isNameValid = preg_match($namePattern, $firstName) && preg_match($namePattern, $lastName);

    // VALIDATION using filter_var();
    $isEmailValid = filter_var($email, FILTER_VALIDATE_EMAIL);

    // VALIDATION for contact number
    $contactPattern = "/^09\d{9}$/";
    $isContactValid = preg_match($contactPattern, $contactNumber);

    if (empty($firstName) || empty($lastName) || empty($birthdate) || empty($email) || empty($password) || empty($branchId)) {
        $error = 'Please fill out all required fields.';
    } elseif (!$isNameValid) {
        $error = 'Invalid Name. Please use letters only.';
    } elseif (!$isContactValid) {
        $error = 'Invalid Contact Number. It must be 11 digits and start with 09.';
    } elseif (!$isEmailValid) {
        $error = 'Invalid Email format.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = 'Email is already registered. Please log in.';
        } else {
            try {
                $pdo->beginTransaction();

                 // Generate patient number
                require_once basePath('app/Helpers/patient_helper.php');
                $patientNumber = generatePatientNumber($pdo, $branchId);

                // Insert into patients table
                $stmt = $pdo->prepare("INSERT INTO patients (patient_number, first_name, last_name, birthdate, sex, contact_number, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$patientNumber, $firstName, $lastName, $birthdate, $sex, $contactNumber, $branchId]);
                $patientId = $pdo->lastInsertId();

                // Generate verification token
                $verificationToken = bin2hex(random_bytes(32));

                // Insert into users table
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status, patient_id, is_email_verified, verification_token) VALUES (?, ?, 'patient', 'Pending', ?, 0, ?)");
                $stmt->execute([$email, $hashedPassword, $patientId, $verificationToken]);

                // Notify Branch Admin
                $notifTitle = "New Patient Registration";
                $notifMsg = "Patient " . $firstName . " " . $lastName . " has registered a new account.";
                $notifStmt = $pdo->prepare("INSERT INTO notifications (role, branch_id, title, message, link) VALUES ('branch_admin', ?, ?, ?, '/" . PROJECT_DIR . "/patients')");
                $notifStmt->execute([$branchId, $notifTitle, $notifMsg]);

                // Send Verification Email
                require_once basePath('app/Helpers/mailer_helper.php');
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $verifyLink = $protocol . $_SERVER['HTTP_HOST'] . '/' . PROJECT_DIR . '/verify?token=' . $verificationToken;
                
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px;'>
                        <h2 style='color: #1f2937;'>Welcome to CitiLife System!</h2>
                        <p style='color: #4b5563; font-size: 16px;'>Hi {$firstName},</p>
                        <p style='color: #4b5563; font-size: 16px;'>Thank you for registering. Please click the button below to verify your email address. This is required before you can log in to your account.</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$verifyLink}' style='display: inline-block; padding: 12px 24px; background-color: #dc2626; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>Verify Email Address</a>
                        </div>
                        <p style='color: #6b7280; font-size: 14px;'>If the button doesn't work, you can copy and paste this link into your browser:<br><a href='{$verifyLink}' style='color: #2563eb;'>{$verifyLink}</a></p>
                        <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                        <p style='color: #9ca3af; font-size: 12px; text-align: center;'>&copy; " . date('Y') . " CitiLife Diagnostic Center. All rights reserved.</p>
                    </div>
                ";
                sendEmail($email, $firstName . ' ' . $lastName, 'Verify your Email Address - CitiLife System', $emailBody);

                $pdo->commit();
                $success = 'Registration successful! Please check your email to verify your account.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'An error occurred during registration: ' . $e->getMessage();
            }
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
    <script src="/<?= PROJECT_DIR ?>/public/assets/vendor/sweetalert2/sweetalert2.all.min.js?v=<?= time() ?>"></script>
    <script src="/<?= PROJECT_DIR ?>/public/assets/js/alerts.js?v=<?= time() ?>"></script>
    <script src="/<?= PROJECT_DIR ?>/public/assets/js/security.js?v=<?= time() ?>"></script>
    <style>
        .step {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .step.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .bg-pattern {
            background-color: #f0f2f5;
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* Mobile specific native styles to simulate mobile app flow */
        @media (max-width: 640px) {
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                background-image: none !important;
            }

            .glass-panel {
                background: #ffffff !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .bg-pattern {
                background: #ffffff !important;
            }

            /* Hide desktop form fully on mobile */
            #desktopFormContainer {
                display: none !important;
            }
        }

        /* Desktop specific visibility */
        @media (min-width: 641px) {

            /* Hide mobile form on desktop */
            #mobileFormContainer {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-pattern min-h-screen flex items-start sm:items-center justify-center sm:p-6 md:p-8">

    <?php if ($success): ?>
        <div class="glass-panel w-full max-w-lg sm:rounded-2xl sm:shadow-2xl overflow-hidden p-6 sm:p-8">
            <div class="mb-6 p-4 rounded-xl bg-green-50 text-green-700 text-sm flex items-start border border-green-100">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <div class="text-center mt-6">
                <a href="patient-login"
                    class="inline-flex justify-center w-full py-3.5 px-6 border border-transparent rounded-full shadow-sm text-[15px] font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                    Log in to Patient Portal
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- ============================================== -->
        <!-- DESKTOP FORM (SINGLE PAGE GRID LAYOUT)         -->
        <!-- ============================================== -->
        <div id="desktopFormContainer"
            class="glass-panel w-full max-w-2xl sm:rounded-2xl sm:shadow-2xl overflow-hidden hidden sm:block">
            <div class="p-8">
                <div class="text-center mb-8">
                    <div
                        class="mx-auto w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mb-4 border border-blue-100 shadow-sm">
                        <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/citilife-logo.png" alt="CitiLife Logo"
                            class="h-12 w-12 object-contain"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <svg class="h-10 w-10 text-blue-600 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Create an Account</h1>
                    <p class="text-sm text-gray-500 mt-2">Register to access your X-ray records and appointments.</p>
                </div>

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

                <form method="POST" action="" class="space-y-6" novalidate>
                    <!-- Identifies which form was sent (optional check) -->
                    <input type="hidden" name="form_type" value="desktop">

                    <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                        <!-- First Name -->
                        <div>
                            <label for="d_first_name" class="block text-sm font-semibold text-gray-700 mb-1">First Name
                                *</label>
                            <input id="d_first_name" name="first_name" type="text" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                value="<?= htmlspecialchars($firstName ?? '') ?>">
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="d_last_name" class="block text-sm font-semibold text-gray-700 mb-1">Last Name
                                *</label>
                            <input id="d_last_name" name="last_name" type="text" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                value="<?= htmlspecialchars($lastName ?? '') ?>">
                        </div>

                        <!-- Birthdate -->
                        <div>
                            <label for="d_birthdate" class="block text-sm font-semibold text-gray-700 mb-1">Birthdate *</label>
                            <input id="d_birthdate" name="birthdate" type="date" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                value="<?= htmlspecialchars($birthdate ?? '') ?>">
                        </div>

                        <!-- Sex -->
                        <div>
                            <label for="d_sex" class="block text-sm font-semibold text-gray-700 mb-1">Sex *</label>
                            <select id="d_sex" name="sex" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">
                                <option value="Male" <?= (($sex ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (($sex ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label for="d_contact_number" class="block text-sm font-semibold text-gray-700 mb-1">Contact
                                Number</label>
                            <input id="d_contact_number" name="contact_number" type="text" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Ex: 09123456789" value="<?= htmlspecialchars($contactNumber ?? '') ?>"
                                pattern="09[0-9]{9}" maxlength="11" minlength="11" title="Contact number must be 11 digits and start with 09"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>

                        <!-- Branch -->
                        <div>
                            <label for="d_branch_id" class="block text-sm font-semibold text-gray-700 mb-1">Preferred
                                Validating Branch *</label>
                            <select id="d_branch_id" name="branch_id" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">
                                <option value="" disabled <?= empty($branchId) ? 'selected' : '' ?> hidden>Select Branch
                                </option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= $branch['id'] ?>" <?= (($branchId ?? '') == $branch['id']) ? 'selected' : '' ?>><?= htmlspecialchars($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr class="border-gray-200">

                    <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                        <!-- Email -->
                        <div class="col-span-2">
                            <label for="d_email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address
                                *</label>
                            <input id="d_email" name="email" type="email" required autocomplete="username"
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="you@example.com" value="<?= htmlspecialchars($email ?? '') ?>">
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="d_password" class="block text-sm font-semibold text-gray-700 mb-1">Password
                                *</label>
                            <div class="relative">
                                <input id="d_password" name="password" type="password" required autocomplete="new-password"
                                    class="pr-10 appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <button type="button" onclick="togglePassword('d_password', this)" tabindex="-1"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
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
                                        <svg class="w-3.5 h-3.5 icon-x" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg class="w-3.5 h-3.5 icon-check hidden" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>8+ characters</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 pw-req-upper text-red-600">
                                        <svg class="w-3.5 h-3.5 icon-x" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg class="w-3.5 h-3.5 icon-check hidden" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Uppercase letter</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 pw-req-number text-red-600">
                                        <svg class="w-3.5 h-3.5 icon-x" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg class="w-3.5 h-3.5 icon-check hidden" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>At least one number</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 pw-req-special text-red-600">
                                        <svg class="w-3.5 h-3.5 icon-x" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg class="w-3.5 h-3.5 icon-check hidden" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Special Character</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="d_confirm_password" class="block text-sm font-semibold text-gray-700 mb-1">Confirm
                                Password *</label>
                            <div class="relative">
                                <input id="d_confirm_password" name="confirm_password" type="password" required
                                    autocomplete="new-password"
                                    class="pr-10 appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <button type="button" onclick="togglePassword('d_confirm_password', this)" tabindex="-1"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p id="d_match_indicator" class="mt-1.5 text-xs font-semibold hidden"></p>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Create Account
                        </button>
                    </div>

                    <div class="text-center mt-4 border-t pt-4">
                        <p class="text-sm text-gray-600">
                            Already registered? <a href="patient-login"
                                class="font-bold text-red-600 hover:text-red-500 hover:underline">Log in to Patient
                                Portal</a>
                        </p>
                    </div>
                </form>
            </div>
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex justify-center">
                <p class="text-xs text-gray-400">&copy; <?= date('Y') ?> CitiLife X-ray System.</p>
            </div>
        </div>


        <!-- ============================================== -->
        <!-- MOBILE FORM (MULTI-STEP WIZARD)                -->
        <!-- ============================================== -->
        <div id="mobileFormContainer" class="glass-panel w-full overflow-hidden flex flex-col relative min-h-screen">
            <div class="px-6 py-6 flex-grow flex flex-col relative">

                <!-- Navbar Back Button and Title -->
                <div id="navContainer" class="flex items-center mb-6 min-h-[32px]">
                    <button type="button" id="backBtn" onclick="prevStep()"
                        class="font-bold text-gray-600 hover:text-black mr-3 p-1 -ml-1 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <h1 class="text-[22px] font-bold text-gray-900 tracking-tight">Create an Account</h1>
                </div>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-50 text-red-700 text-sm flex items-start border border-red-100">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form id="signupFormMobile" method="POST" action="" class="flex-grow flex flex-col justify-between"
                    onkeydown="return event.key != 'Enter';" novalidate>
                    <!-- Identifies which form was sent -->
                    <input type="hidden" name="form_type" value="mobile">

                    <div id="steps-container">
                        <!-- Step 1: Name -->
                        <div class="step active" id="step1">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight mt-2">What's your name?</h2>
                            <p class="text-[15px] text-gray-800 mb-6">Enter the name</p>

                            <div class="grid grid-cols-2 gap-3 mb-6">
                                <div class="relative">
                                    <input type="text" id="m_first_name" name="first_name" required
                                        class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-3 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                        placeholder=" " value="<?= htmlspecialchars($firstName ?? '') ?>" />
                                    <label for="m_first_name"
                                        class="absolute top-2 left-3 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">First
                                        name</label>
                                </div>
                                <div class="relative">
                                    <input type="text" id="m_last_name" name="last_name" required
                                        class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-3 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                        placeholder=" " value="<?= htmlspecialchars($lastName ?? '') ?>" />
                                    <label for="m_last_name"
                                        class="absolute top-2 left-3 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Last
                                        name</label>
                                </div>
                            </div>
                            <button type="button" onclick="nextStep(1)"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition">Next</button>
                        </div>

                        <!-- Step 2: Birthdate -->
                        <div class="step" id="step2">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">When is your birthday?</h2>
                            <p class="text-[15px] text-gray-800 mb-6">Enter your birthdate.</p>

                            <div class="relative mb-6">
                                <input type="date" id="m_birthdate" name="birthdate" required
                                    class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                    placeholder=" " value="<?= htmlspecialchars($birthdate ?? '') ?>" />
                                <label for="m_birthdate"
                                    class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Birthdate</label>
                            </div>
                            <button type="button" onclick="nextStep(2)"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition">Next</button>
                        </div>

                        <!-- Step 3: Sex -->
                        <div class="step" id="step3">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">What's your sex?</h2>
                            <p class="text-[15px] text-gray-800 mb-6">Please select your biological sex for accurate medical
                                records.</p>

                            <div class="rounded-xl border border-gray-300 bg-white mb-6 overflow-hidden">
                                <label
                                    class="flex cursor-pointer items-center justify-between border-b border-gray-200 p-4 hover:bg-gray-50">
                                    <span class="text-[16px] font-medium text-gray-900">Female</span>
                                    <input type="radio" name="sex" id="m_sex_f" value="Female"
                                        class="h-6 w-6 border-gray-300 text-blue-600 focus:ring-blue-500" <?= (($sex ?? '') === 'Female') ? 'checked' : '' ?>>
                                </label>
                                <label class="flex cursor-pointer items-center justify-between p-4 hover:bg-gray-50">
                                    <span class="text-[16px] font-medium text-gray-900">Male</span>
                                    <input type="radio" name="sex" id="m_sex_m" value="Male"
                                        class="h-6 w-6 border-gray-300 text-blue-600 focus:ring-blue-500" <?= (($sex ?? '') === 'Male') ? 'checked' : '' ?> required>
                                </label>
                            </div>
                            <button type="button" onclick="nextStep(3)"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition">Next</button>
                        </div>

                        <!-- Step 4: Mobile Number -->
                        <div class="step" id="step4">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">What's your mobile number?</h2>
                            <p class="text-[15px] text-gray-800 mb-6">Enter the mobile number where you can be contacted. No
                                one will see this on your profile.</p>

                            <div class="relative mb-6">
                                <input type="text" id="m_contact_number" name="contact_number" required
                                    class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                    placeholder=" " value="<?= htmlspecialchars($contactNumber ?? '') ?>"
                                    pattern="09[0-9]{9}" maxlength="11" minlength="11" title="Contact number must be 11 digits and start with 09"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                                <label for="m_contact_number"
                                    class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Mobile
                                    number</label>
                            </div>
                            <button type="button" onclick="nextStep(4)"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition">Next</button>
                        </div>

                        <!-- Step 5: Validating Clinic -->
                        <div class="step" id="step5">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">Where's your preferred clinic?
                            </h2>
                            <p class="text-[15px] text-gray-800 mb-6">Select the branch nearest to you for account
                                validation.</p>

                            <div class="relative mb-6">
                                <select id="m_branch_id" name="branch_id" required
                                    class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all">
                                    <option value="" disabled <?= empty($branchId) ? 'selected' : '' ?> hidden></option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= $branch['id'] ?>" <?= (($branchId ?? '') == $branch['id']) ? 'selected' : '' ?>><?= htmlspecialchars($branch['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="m_branch_id"
                                    class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Validating
                                    Branch</label>
                            </div>
                            <button type="button" onclick="nextStep(5)"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition">Next</button>
                        </div>

                        <!-- Step 6: Account Details -->
                        <div class="step" id="step6">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">Set up your account</h2>
                            <p class="text-[15px] text-gray-800 mb-6">Create a password to securely log in to your portal.
                            </p>

                            <div class="space-y-4 mb-6">
                                <div class="relative">
                                    <input type="email" id="m_email" name="email" required autocomplete="username"
                                        class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                        placeholder=" " value="<?= htmlspecialchars($email ?? '') ?>" />
                                    <label for="m_email"
                                        class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Email
                                        Address</label>
                                </div>
                                <div class="relative">
                                    <input type="password" id="m_password" name="password" required
                                        autocomplete="new-password"
                                        class="peer pr-12 block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                        placeholder=" " />
                                    <label for="m_password"
                                        class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Password</label>
                                    <button type="button" onclick="togglePassword('m_password', this)" tabindex="-1"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- MOBILE CHECKER -->
                                <div class="text-left bg-gray-50 rounded-lg p-3 border border-gray-100 hidden relative -mt-3"
                                    id="m_pw_checker">
                                    <div class="flex justify-between items-center mb-1.5">
                                        <span class="text-xs font-semibold text-gray-500">Password Strength:</span>
                                        <span class="text-xs font-bold pw-label text-red-500">Weak</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden mb-3">
                                        <div class="h-full bg-red-500 transition-all duration-300 pw-bar" style="width: 0%">
                                        </div>
                                    </div>
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[13px] font-medium text-gray-600">
                                        <div class="flex items-center gap-1.5 pw-req-length text-red-600">
                                            <svg class="w-3.5 h-3.5 icon-x shrink-0" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <svg class="w-3.5 h-3.5 icon-check shrink-0 hidden" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>8+ characters</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 pw-req-upper text-red-600">
                                            <svg class="w-3.5 h-3.5 icon-x shrink-0" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <svg class="w-3.5 h-3.5 icon-check shrink-0 hidden" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Uppercase letter</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 pw-req-number text-red-600">
                                            <svg class="w-3.5 h-3.5 icon-x shrink-0" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <svg class="w-3.5 h-3.5 icon-check shrink-0 hidden" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>At least one number</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 pw-req-special text-red-600">
                                            <svg class="w-3.5 h-3.5 icon-x shrink-0" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <svg class="w-3.5 h-3.5 icon-check shrink-0 hidden" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Special Character</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative">
                                    <input type="password" id="m_confirm_password" name="confirm_password" required
                                        autocomplete="new-password"
                                        class="peer pr-12 block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                        placeholder=" " />
                                    <label for="m_confirm_password"
                                        class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Confirm
                                        Password</label>
                                    <button type="button" onclick="togglePassword('m_confirm_password', this)" tabindex="-1"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                                <p id="m_match_indicator" class="text-xs font-semibold px-4 hidden"></p>
                            </div>
                            <button type="submit"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition shadow-md">Sign
                                Up</button>
                        </div>
                    </div>

                    <!-- Global footer link placed at the bottom natively -->
                    <div class="mt-auto pt-8 text-center pb-2">
                        <a href="patient-login" class="font-bold text-red-600 hover:text-red-800 text-[15px]">Already
                            have an account?</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        let currentStep = 1;
        const totalSteps = 6;

        function showStep(step) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            // Safety check for mobile elements
            const stepEl = document.getElementById('step' + step);
            if (stepEl) stepEl.classList.add('active');

            // Back button is now strictly visible at all steps
        }

        function nextStep(step) {
            if (!validateStep(step)) return;
            if (step < totalSteps) {
                currentStep = step + 1;
                showStep(currentStep);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            } else {
                window.location.href = 'patient-login';
            }
        }

        function validateStep(step) {
            const stepEl = document.getElementById('step' + step);
            if (!stepEl) return true;

            const inputs = stepEl.querySelectorAll('input:required, select:required');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.reportValidity();
                    isValid = false;
                }
            });

            if (step === 3) {
                const sexSelected = stepEl.querySelector('input[name="sex"]:checked');
                if (!sexSelected) {
                    isValid = false;
                }
            }

            if (step === 6) {
                const pw = document.getElementById('m_password').value;
                const cpw = document.getElementById('m_confirm_password').value;
                if (pw !== cpw) {
                    toast("Passwords do not match.", "error");
                    isValid = false;
                }
            }

            return isValid;
        }

        // Handle Enter key for seamless steps on Mobile only
        const mobileForm = document.getElementById('signupFormMobile');
        if (mobileForm) {
            mobileForm.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentStep < totalSteps) {
                        nextStep(currentStep);
                    } else {
                        if (validateStep(6)) {
                            this.submit();
                        }
                    }
                }
            });
        }

        // Initialize state if returning from an error on Mobile
        <?php if ($error && !empty($_POST)): ?>
            <?php if (isset($_POST['form_type']) && $_POST['form_type'] === 'mobile'): ?>
                currentStep = 1;
                showStep(currentStep);
            <?php endif; ?>
        <?php endif; ?>

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
            initPwChecker('d_password', 'd_pw_checker');
            initPwChecker('m_password', 'm_pw_checker');
            initMatchChecker('d_password', 'd_confirm_password', 'd_match_indicator');
            initMatchChecker('m_password', 'm_confirm_password', 'm_match_indicator');
        });
    </script>
</body>

</html>