<?php
require_once __DIR__ . '/../../config/database.php';
global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!defined('PROJECT_DIR')) {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/CitiLife-System/app/api/update_profile.php';
    $parts = explode('/', $scriptPath);
    define('PROJECT_DIR', (isset($parts[1]) && $parts[1] !== 'index.php') ? $parts[1] : 'CitiLife-System');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'] ?? 'radtech';
        $currentEmail = $_SESSION['email'] ?? '';
        $newEmail = trim($_POST['email'] ?? $currentEmail);

        if ($newEmail !== $currentEmail) {
            if (empty($_SESSION['email_change_verified']) || $_SESSION['email_change_verified'] !== true) {
                echo json_encode(['success' => false, 'error' => 'Please verify your current email first before changing it.']);
                exit;
            }
        }

        if ($role === 'patient') {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $birthdate = trim($_POST['birthdate'] ?? '');
            $sex = $_POST['sex'] ?? 'Male';
            $contactNumber = trim($_POST['contact_number'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($firstName) || empty($lastName) || empty($birthdate) || empty($contactNumber)) {
                echo json_encode(['success' => false, 'error' => 'All personal details are required.']);
                exit;
            }

            // Validations
            $namePattern = "/^[a-zA-Z\s]+$/";
            if (!preg_match($namePattern, $firstName) || !preg_match($namePattern, $lastName)) {
                echo json_encode(['success' => false, 'error' => 'Invalid name. Please use letters only.']);
                exit;
            }

            $contactPattern = "/^09\d{9}$/";
            if (!preg_match($contactPattern, $contactNumber)) {
                echo json_encode(['success' => false, 'error' => 'Invalid contact number. Must be 11 digits starting with 09.']);
                exit;
            }

            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
                exit;
            }

            if (!empty($password)) {
                // complexity check
                if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                    echo json_encode(['success' => false, 'error' => 'Password does not meet complexity requirements.']);
                    exit;
                }
            }

            $newName = $firstName . ' ' . $lastName;
        } else {
            $newName = trim($_POST['system_name'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($newName)) {
                echo json_encode(['success' => false, 'error' => 'Display name is required.']);
                exit;
            }

            if (!empty($password)) {
                // complexity check for staff passwords changed via security tab
                if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                    echo json_encode(['success' => false, 'error' => 'Password does not meet complexity requirements.']);
                    exit;
                }
            }
        }

        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['avatar']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;
                
                if (move_uploaded_file($tmpPath, $destPath)) {
                    $avatarPath = '/' . PROJECT_DIR . '/public/uploads/avatars/' . $filename;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid image format.']);
                exit;
            }
        }

        try {
            $pdo->beginTransaction();

            // Check if email is already taken by another user
            $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmtEmail->execute([$newEmail, $userId]);
            if ($stmtEmail->fetch()) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'The email address is already in use.']);
                exit;
            }

            // Update users table
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                if ($avatarPath) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, avatar = ? WHERE id = ?");
                    $stmt->execute([$newName, $newEmail, $hashedPassword, $avatarPath, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$newName, $newEmail, $hashedPassword, $userId]);
                }
            } else {
                if ($avatarPath) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, avatar = ? WHERE id = ?");
                    $stmt->execute([$newName, $newEmail, $avatarPath, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$newName, $newEmail, $userId]);
                }
            }

            // Update patients table if patient role
            if ($role === 'patient') {
                $stmtUser = $pdo->prepare("SELECT patient_id FROM users WHERE id = ?");
                $stmtUser->execute([$userId]);
                $patientId = $stmtUser->fetchColumn();

                if ($patientId) {
                    $stmtPatient = $pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, birthdate = ?, sex = ?, contact_number = ? WHERE id = ?");
                    $stmtPatient->execute([$firstName, $lastName, $birthdate, $sex, $contactNumber, $patientId]);
                }
            }

            $pdo->commit();

            $_SESSION['name'] = $newName;
            $_SESSION['email'] = $newEmail;
            unset($_SESSION['email_change_verified']);
            if ($avatarPath) $_SESSION['avatar'] = $avatarPath;
            
            $nameParts = explode(' ', $newName);
            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
            
            $response = [
                'success' => true,
                'name' => htmlspecialchars($newName),
                'email' => htmlspecialchars($newEmail),
                'initials' => htmlspecialchars($initials)
            ];
            if ($avatarPath) {
                $response['avatar'] = htmlspecialchars($avatarPath);
            }
            if ($role === 'patient') {
                $response['first_name'] = htmlspecialchars($firstName);
                $response['last_name'] = htmlspecialchars($lastName);
                $response['birthdate'] = htmlspecialchars($birthdate);
                $response['sex'] = htmlspecialchars($sex);
                $response['contact_number'] = htmlspecialchars($contactNumber);
            }
            echo json_encode($response);
        } catch(Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    } else if ($action === 'update_radtech_settings') {
        $userId = $_SESSION['user_id'];
        $newName = trim($_POST['report_full_name'] ?? '');
        $professionalTitle = trim($_POST['professional_title'] ?? '');
        
        $signaturePath = null;
        if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['signature']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $uploadDir = __DIR__ . '/../../public/uploads/signatures/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $filename = 'sig_' . $userId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;
                
                if (move_uploaded_file($tmpPath, $destPath)) {
                    $signaturePath = '/' . PROJECT_DIR . '/public/uploads/signatures/' . $filename;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid image format for signature.']);
                exit;
            }
        }

        try {
            if ($signaturePath) {
                $stmt = $pdo->prepare("UPDATE users SET full_name_report = ?, professional_title = ?, signature = ? WHERE id = ?");
                $success = $stmt->execute([$newName, $professionalTitle, $signaturePath, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name_report = ?, professional_title = ? WHERE id = ?");
                $success = $stmt->execute([$newName, $professionalTitle, $userId]);
            }

            if ($success) {
                $response = [
                    'success' => true,
                    'full_name_report' => htmlspecialchars($newName),
                    'professional_title' => htmlspecialchars($professionalTitle)
                ];
                if ($signaturePath) {
                    $response['signature'] = htmlspecialchars($signaturePath);
                }
                echo json_encode($response);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update RadTech settings.']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
