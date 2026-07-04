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
    $patientNumber = trim($_POST['patient_number'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $sex = $_POST['sex'] ?? 'Male';
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $homeAddress = trim($_POST['home_address'] ?? '');
    $branchId = $_POST['branch_id'] ?? '';
    if ($branchId === '') $branchId = null;

    $email = trim($_POST['email'] ?? '');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    $namePattern = "/^[a-zA-Z\s]+$/";
    $isNameValid = preg_match($namePattern, $firstName) && preg_match($namePattern, $lastName);
    $isEmailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
    $contactPattern = "/^09\d{9}$/";
    $isContactValid = empty($contactNumber) ? true : preg_match($contactPattern, $contactNumber);

    if (empty($patientNumber) || empty($firstName) || empty($lastName) || empty($birthdate) || empty($email) || empty($branchId)) {
        $error = 'Please fill out all required fields.';
    } elseif (!$isNameValid) {
        $error = 'Invalid Name. Please use letters only.';
    } elseif (!empty($contactNumber) && !$isContactValid) {
        $error = 'Invalid Contact Number. It must be 11 digits and start with 09.';
    } elseif (!$isEmailValid) {
        $error = 'Invalid Email format.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT id FROM patients 
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
                
                $stmtUser = $pdo->prepare("SELECT id FROM users WHERE patient_id = ? LIMIT 1");
                $stmtUser->execute([$patientId]);
                if ($stmtUser->fetch()) {
                    $error = 'This patient record is already linked to an active account. Please log in.';
                } else {
                    $updateStmt = $pdo->prepare("UPDATE patients SET sex = ?, contact_number = ?, home_address = ?, branch_id = ? WHERE id = ?");
                    $updateStmt->execute([$sex, $contactNumber, $homeAddress, $branchId, $patientId]);

                    $pdo->prepare("DELETE FROM account_verifications WHERE patient_id = ?")->execute([$patientId]);

                    $verificationToken = bin2hex(random_bytes(32));
                    $insertStmt = $pdo->prepare("
                        INSERT INTO account_verifications (token, patient_id, email, expires_at) 
                        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
                    ");
                    $insertStmt->execute([$verificationToken, $patientId, $email]);

                                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $verifyLink = $protocol . $_SERVER['HTTP_HOST'] . '/' . PROJECT_DIR . '/verify?token=' . $verificationToken;

                    $emailBody = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px;'>
                            <h2 style='color: #1f2937;'>Welcome to CitiLife System!</h2>
                            <p style='color: #4b5563; font-size: 16px;'>Hi {$firstName},</p>
                            <p style='color: #4b5563; font-size: 16px;'>Thank you for registering. Please click the button below to verify your email address. You will be able to create your password and access your records afterwards.</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$verifyLink}' style='display: inline-block; padding: 12px 24px; background-color: #dc2626; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>Verify Email Address</a>
                            </div>
                            <p style='color: #6b7280; font-size: 14px;'>If the button doesn't work, copy and paste this link into your browser:<br><a href='{$verifyLink}' style='color: #2563eb;'>{$verifyLink}</a></p>
                        </div>
                    ";
                    sendEmail($email, $firstName . ' ' . $lastName, 'Verify your Email Address - CitiLife System', $emailBody);

                    $pdo->commit();
                    
                    require_once basePath('app/Models/AuditLogModel.php');
                    $auditLogModel = new \AuditLogModel($pdo);
                    $auditLogModel->addLog(
                        null,
                        'Patient Registration',
                        'Patient Portal',
                        'Patient',
                        $patientId,
                        "Patient initiated account registration",
                        $branchId
                    );
                    
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker.min.css">
    <script src="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/js/datepicker-full.min.js"></script>
    <script src="/<?= PROJECT_DIR ?>/public/assets/vendor/sweetalert2/sweetalert2.all.min.js?v=<?= time() ?>"></script>
    <script src="/<?= PROJECT_DIR ?>/public/assets/js/alerts.js?v=<?= time() ?>"></script>
    <script src="/<?= PROJECT_DIR ?>/public/assets/js/security.js?v=<?= time() ?>"></script>
    
    <!-- Load Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* Global override for Vanilla JS Datepicker to make selected date RED */
        html body .datepicker-cell.selected,
        html body .datepicker-cell.selected:hover,
        html body .datepicker-cell.selected.focused,
        html body .datepicker-picker .datepicker-cell.selected,
        html body .datepicker-picker .datepicker-cell.selected:hover,
        html body .datepicker-picker .datepicker-cell.selected.focused {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            border-color: #dc2626 !important;
        }

        /* Remove the default TEAL background from 'today' and make it clean */
        html body .datepicker-cell.today:not(.selected),
        html body .datepicker-picker .datepicker-cell.today:not(.selected) {
            background-color: #f3f4f6 !important; /* light grey instead of teal */
            color: #111827 !important;
            font-weight: 600 !important;
            border: 1px solid #d1d5db !important;
        }

        html body .datepicker-cell.today.focused:not(.selected),
        html body .datepicker-picker .datepicker-cell.today.focused:not(.selected) {
            background-color: #e5e7eb !important;
        }
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
                        
                    <!-- Patient ID -->
                    <div class="col-span-2">
                        <label for="d_patient_number" class="block text-sm font-semibold text-gray-700 mb-1">Patient ID *</label>
                        <input id="d_patient_number" name="patient_number" type="text" required
                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="e.g. PAT-GAP-2026-001" value="<?= htmlspecialchars($patientNumber ?? '') ?>">
                        <p class="text-xs text-gray-500 mt-1">Found on your clinic receipt or given by staff.</p>
                    </div>

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
                            <label for="d_birthdate" class="block text-sm font-semibold text-gray-700 mb-1">Birthdate
                                *</label>
                            <div class="relative">
                                <input id="d_birthdate" name="birthdate" type="text" required readonly placeholder="Select birthdate"
                                    class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    value="<?= htmlspecialchars($birthdate ?? '') ?>">
                                <i data-lucide="calendar" class="absolute left-3 top-2.5 w-4 h-4 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Sex -->
                        <div class="relative">
                            <label for="d_sex" class="block text-sm font-semibold text-gray-700 mb-1">Sex *</label>
                            <select id="d_sex" name="sex" required
                                class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">
                                <option value="Male" <?= (($sex ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (($sex ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 top-6 flex items-center px-3 text-gray-500">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <!-- Home Address -->
                        <div class="col-span-2">
                            <label for="d_home_address" class="block text-sm font-semibold text-gray-700 mb-1">Home
                                Address</label>
                            <input id="d_home_address" name="home_address" type="text"
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="123 Main St, Brgy, City" value="<?= htmlspecialchars($homeAddress ?? '') ?>">
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label for="d_contact_number" class="block text-sm font-semibold text-gray-700 mb-1">Contact
                                Number</label>
                            <input id="d_contact_number" name="contact_number" type="text" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Ex: 09123456789" value="<?= htmlspecialchars($contactNumber ?? '') ?>"
                                pattern="09[0-9]{9}" maxlength="11" minlength="11"
                                title="Contact number must be 11 digits and start with 09"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>

                        <!-- Branch -->
                        <div class="relative">
                            <label for="d_branch_id" class="block text-sm font-semibold text-gray-700 mb-1">Preferred
                                Validating Branch *</label>
                            <select id="d_branch_id" name="branch_id" required
                                class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">
                                <option value="" disabled <?= empty($branchId) ? 'selected' : '' ?> hidden>Select Branch
                                </option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= $branch['id'] ?>" <?= (($branchId ?? '') == $branch['id']) ? 'selected' : '' ?>><?= htmlspecialchars($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 top-6 flex items-center px-3 text-gray-500">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
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

                        </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Verify Identity
                        </button>
                    </div>

                    <div class="text-center mt-4 border-t pt-4">
                        <p class="text-sm text-gray-600">
                            Already registered? <a href="patient-login"
                                class="font-bold text-red-600 hover:text-red-500 hover:underline">Log in to Patient
                            </a>
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

                            
                            <div class="relative mb-4 mt-2">
                                <input type="text" id="m_patient_number" name="patient_number" required
                                    class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-3 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                    placeholder=" " value="<?= htmlspecialchars($patientNumber ?? '') ?>" />
                                <label for="m_patient_number"
                                    class="absolute top-2 left-3 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Patient ID (e.g. PAT-GAP-2026-001)</label>
                            </div>

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
                                <input type="text" id="m_birthdate" name="birthdate" required readonly
                                    class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 pr-10 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                    placeholder=" " value="<?= htmlspecialchars($birthdate ?? '') ?>" />
                                <i data-lucide="calendar" class="absolute right-4 top-4 w-5 h-5 text-gray-400 pointer-events-none"></i>
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

                        <!-- Step 4: Home Address -->
                        <div class="step" id="step4">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">What's your home address?</h2>
                            <p class="text-[15px] text-gray-800 mb-6">Enter your complete home address.</p>

                            <div class="relative mb-6">
                                <input type="text" id="m_home_address" name="home_address"
                                    class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                    placeholder=" " value="<?= htmlspecialchars($homeAddress ?? '') ?>" />
                                <label for="m_home_address"
                                    class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Home
                                    Address</label>
                            </div>
                            <button type="button" onclick="nextStep(4)"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition">Next</button>
                        </div>

                        <!-- Step 5: Mobile Number -->
                        <div class="step" id="step5">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">What's your mobile number?</h2>
                            <p class="text-[15px] text-gray-800 mb-6">Enter the mobile number where you can be contacted. No
                                one will see this on your profile.</p>

                            <div class="relative mb-6">
                                <input type="text" id="m_contact_number" name="contact_number" required
                                    class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                    placeholder=" " value="<?= htmlspecialchars($contactNumber ?? '') ?>"
                                    pattern="09[0-9]{9}" maxlength="11" minlength="11"
                                    title="Contact number must be 11 digits and start with 09"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                                <label for="m_contact_number"
                                    class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Mobile
                                    number</label>
                            </div>
                            <button type="button" onclick="nextStep(5)"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition">Next</button>
                        </div>

                        <!-- Step 6: Validating Clinic -->
                        <div class="step" id="step6">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">Where's your preferred clinic?
                            </h2>
                            <p class="text-[15px] text-gray-800 mb-6">Select the branch nearest to you for account
                                validation.</p>

                            <div class="relative mb-6" id="m_branch_dropdown_container">
                                <!-- Hidden input for actual value -->
                                <input type="hidden" id="m_branch_id" name="branch_id"
                                    value="<?= htmlspecialchars($branchId ?? '') ?>">

                                <!-- Readonly input for display and validation -->
                                <?php
                                $selectedBranchName = '';
                                if (!empty($branchId)) {
                                    foreach ($branches as $b) {
                                        if ($b['id'] == $branchId)
                                            $selectedBranchName = $b['name'];
                                    }
                                }
                                ?>
                                <input type="text" id="m_branch_display" readonly
                                    class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pr-10 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all cursor-pointer caret-transparent"
                                    placeholder=" " value="<?= htmlspecialchars($selectedBranchName) ?>" />

                                <label for="m_branch_display"
                                    class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all cursor-pointer">Validating
                                    Branch</label>

                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-500">
                                    <svg id="m_branch_icon" class="h-5 w-5 transition-transform duration-200"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <!-- Dropdown options list -->
                                <div id="m_branch_options"
                                    class="absolute z-50 mt-2 w-full rounded-xl bg-white shadow-[0_4px_20px_-4px_rgba(0,0,0,0.1)] border border-gray-100 opacity-0 invisible transition-all duration-200 transform origin-top scale-95 overflow-hidden">
                                    <ul class="max-h-60 overflow-auto py-2 text-[15px] text-gray-700">
                                        <?php foreach ($branches as $branch): ?>
                                            <li class="cursor-pointer select-none py-2.5 px-4 hover:bg-red-50 hover:text-red-700 font-medium transition-colors flex items-center justify-between group"
                                                data-value="<?= $branch['id'] ?>">
                                                <?= htmlspecialchars($branch['name']) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <button type="button" onclick="nextStep(6)"
                                class="w-full rounded-full bg-red-600 py-3.5 text-[15px] font-bold text-white hover:bg-red-700 transition">Next</button>
                        </div>

                        <!-- Step 7: Account Details -->
                        <div class="step" id="step7">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2 tracking-tight">Set up your account</h2>
                            

                            <div class="space-y-4 mb-6">
                                <div class="relative">
                                    <input type="email" id="m_email" name="email" required autocomplete="username"
                                        class="peer block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 pb-2 pt-6 text-[15px] font-medium text-gray-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-none transition-all"
                                        placeholder=" " value="<?= htmlspecialchars($email ?? '') ?>" />
                                    <label for="m_email"
                                        class="absolute top-2 left-4 z-10 origin-[0] -translate-y-0 scale-75 transform text-[15px] text-gray-500 duration-300 peer-placeholder-shown:translate-y-2 peer-placeholder-shown:scale-100 peer-focus:-translate-y-0 peer-focus:scale-[0.8] peer-focus:text-blue-600 pointer-events-none transition-all">Email
                                        Address</label>
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
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }

            const datepickerOptions = {
                autohide: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            };
            
            if (document.getElementById('d_birthdate')) {
                new Datepicker(document.getElementById('d_birthdate'), datepickerOptions);
            }
            if (document.getElementById('m_birthdate')) {
                new Datepicker(document.getElementById('m_birthdate'), datepickerOptions);
            }
        });

        let currentStep = 1;
        const totalSteps = 7;

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
                const branchHidden = document.getElementById('m_branch_id');
                if (branchHidden && !branchHidden.value) {
                    toast("Please select your preferred clinic.", "error");
                    isValid = false;
                }
            }

            if (step === 7) {
                // Email is validated natively by checkValidity() loop above
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
                        if (validateStep(7)) {
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

        
            // Custom Dropdown Init for Mobile Branch
            const branchDisplay = document.getElementById('m_branch_display');
            const branchHidden = document.getElementById('m_branch_id');
            const branchOptions = document.getElementById('m_branch_options');
            const branchIcon = document.getElementById('m_branch_icon');
            const container = document.getElementById('m_branch_dropdown_container');

            if (branchDisplay && branchOptions) {
                function toggleDropdown() {
                    const isClosed = branchOptions.classList.contains('invisible');
                    if (isClosed) {
                        branchOptions.classList.remove('invisible', 'opacity-0', 'scale-95', 'pointer-events-none');
                        branchOptions.classList.add('opacity-100', 'scale-100');
                        branchIcon.classList.add('rotate-180');
                    } else {
                        closeDropdown();
                    }
                }

                function closeDropdown() {
                    branchOptions.classList.add('invisible', 'opacity-0', 'scale-95', 'pointer-events-none');
                    branchOptions.classList.remove('opacity-100', 'scale-100');
                    branchIcon.classList.remove('rotate-180');
                }

                branchDisplay.addEventListener('click', toggleDropdown);

                const items = branchOptions.querySelectorAll('li');
                items.forEach(item => {
                    item.addEventListener('click', function (e) {
                        e.stopPropagation();
                        branchHidden.value = this.getAttribute('data-value');
                        branchDisplay.value = this.textContent.trim();
                        closeDropdown();
                        // Trigger input event for validation/styling updates
                        branchDisplay.dispatchEvent(new Event('input'));
                    });
                });

                document.addEventListener('click', function (e) {
                    if (!container.contains(e.target)) {
                        closeDropdown();
                    }
                });
            }
    </script>
</body>

</html>
