<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../../helpers.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/session.php';
    global $pdo;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
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
                $displayNameInput = trim($_POST['display_name'] ?? '');
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $birthdate = trim($_POST['birthdate'] ?? '');
                $sex = $_POST['sex'] ?? 'Male';
                $contactNumber = trim($_POST['contact_number'] ?? '');
                $homeAddress = trim($_POST['home_address'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($firstName) || empty($lastName) || empty($birthdate) || empty($contactNumber)) {
                    echo json_encode(['success' => false, 'error' => 'All personal details are required.']);
                    exit;
                }

                // If patient provided a custom display name, use it; otherwise default to first name
                $newName = !empty($displayNameInput) ? $displayNameInput : $firstName;
            } else {
                $newName = trim($_POST['system_name'] ?? ($_POST['display_name'] ?? ''));
                $password = $_POST['password'] ?? '';

                if (empty($newName)) {
                    echo json_encode(['success' => false, 'error' => 'Display name is required.']);
                    exit;
                }
            }

            $avatarPath = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['avatar']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['avatar']['error'] === UPLOAD_ERR_FORM_SIZE) {
                    echo json_encode(['success' => false, 'error' => 'The uploaded photo is too large. Please select an image under 5MB.']);
                    exit;
                }
                if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'error' => 'Image upload failed with error code: ' . $_FILES['avatar']['error']]);
                    exit;
                }

                $tmpPath = $_FILES['avatar']['tmp_name'];
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($ext, $allowed)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP.']);
                    exit;
                }

                $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
                if (!file_exists($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                }

                $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;

                if (!@move_uploaded_file($tmpPath, $destPath)) {
                    if (!@copy($tmpPath, $destPath)) {
                        echo json_encode(['success' => false, 'error' => 'Failed to save avatar image on server. Please check folder permissions.']);
                        exit;
                    }
                }
                clearstatcache(true, $destPath);

                $avatarPath = '/public/uploads/avatars/' . $filename;
            }

            try {
                $pdo->beginTransaction();

                $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                $stmtEmail->execute([$newEmail, $userId]);
                if ($stmtEmail->fetch()) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'error' => 'The email address is already in use.']);
                    exit;
                }

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

                if ($role === 'patient') {
                    $stmtUser = $pdo->prepare("SELECT patient_id FROM users WHERE id = ?");
                    $stmtUser->execute([$userId]);
                    $patientId = $stmtUser->fetchColumn();

                    if ($patientId) {
                        $stmtPatient = $pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, birthdate = ?, sex = ?, contact_number = ?, home_address = ? WHERE id = ?");
                        $stmtPatient->execute([$firstName, $lastName, $birthdate, $sex, $contactNumber, $homeAddress, $patientId]);
                    }
                }

                $pdo->commit();

                $_SESSION['name'] = $newName;
                $_SESSION['email'] = $newEmail;
                unset($_SESSION['email_change_verified']);
                if ($avatarPath)
                    $_SESSION['avatar'] = $avatarPath;

                $nameParts = explode(' ', $newName);
                $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

                $response = [
                    'success' => true,
                    'name' => htmlspecialchars($newName),
                    'email' => htmlspecialchars($newEmail),
                    'initials' => htmlspecialchars($initials)
                ];
                if ($avatarPath) {
                    $avatarUrl = function_exists('getAvatarUrl') ? getAvatarUrl($filename) : (function_exists('url') ? url('public/uploads/avatars/' . $filename) : '/public/uploads/avatars/' . $filename);
                    $_SESSION['avatar'] = $avatarUrl;
                    $response['avatar'] = $avatarUrl;
                }
                if ($role === 'patient') {
                    $response['first_name'] = htmlspecialchars($firstName);
                    $response['last_name'] = htmlspecialchars($lastName);
                    $response['birthdate'] = htmlspecialchars($birthdate);
                    $response['sex'] = htmlspecialchars($sex);
                    $response['contact_number'] = htmlspecialchars($contactNumber);
                    $response['home_address'] = htmlspecialchars($homeAddress);
                }
                echo json_encode($response);
            } catch (PDOException $e) {
                if ($pdo->inTransaction())
                    $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
        } else if ($action === 'update_radtech_settings') {
            $userId = $_SESSION['user_id'];
            $newName = trim($_POST['report_full_name'] ?? '');
            $professionalTitle = trim($_POST['professional_title'] ?? '');
            $isAvailable = isset($_POST['is_available']) ? (int) $_POST['is_available'] : 1;

            $signaturePath = null;
            if (isset($_FILES['signature']) && $_FILES['signature']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['signature']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['signature']['error'] === UPLOAD_ERR_FORM_SIZE) {
                    echo json_encode(['success' => false, 'error' => 'The uploaded signature is too large. Please select an image under 5MB.']);
                    exit;
                }
                if ($_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'error' => 'Signature upload failed with error code: ' . $_FILES['signature']['error']]);
                    exit;
                }

                $tmpPath = $_FILES['signature']['tmp_name'];
                $ext = strtolower(pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($ext, $allowed)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid image format for signature. Allowed formats: JPG, PNG, GIF, WEBP.']);
                    exit;
                }

                $uploadDir = __DIR__ . '/../../public/uploads/signatures/';
                if (!file_exists($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                }

                $filename = 'sig_' . $userId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;

                if (!@move_uploaded_file($tmpPath, $destPath)) {
                    if (!@copy($tmpPath, $destPath)) {
                        echo json_encode(['success' => false, 'error' => 'Failed to save signature image on server. Please check folder permissions.']);
                        exit;
                    }
                }

                $signaturePath = '/public/uploads/signatures/' . $filename;
            }

            try {
                if ($signaturePath) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name_report = ?, professional_title = ?, signature = ?, is_available = ? WHERE id = ?");
                    $success = $stmt->execute([$newName, $professionalTitle, $signaturePath, $isAvailable, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name_report = ?, professional_title = ?, is_available = ? WHERE id = ?");
                    $success = $stmt->execute([$newName, $professionalTitle, $isAvailable, $userId]);
                }

                if ($success) {
                    $response = [
                        'success' => true,
                        'full_name_report' => htmlspecialchars($newName),
                        'professional_title' => htmlspecialchars($professionalTitle),
                        'is_available' => $isAvailable === 1
                    ];
                    if ($signaturePath) {
                        $sigUrl = function_exists('getSignatureUrl') ? getSignatureUrl($filename) : (function_exists('url') ? url('public/uploads/signatures/' . $filename) : '/public/uploads/signatures/' . $filename);
                        $_SESSION['signature'] = $sigUrl;
                        $response['signature'] = htmlspecialchars($sigUrl);
                    }
                    echo json_encode($response);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update RadTech settings.']);
                    exit;
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
                exit;
            }
        } else {
            // Action is empty or invalid. This usually happens if the uploaded file exceeds PHP's post_max_size.
            echo json_encode(['success' => false, 'error' => 'Invalid request or uploaded image is too large (exceeds server limit). Please choose a smaller image file (under 2MB).']);
        }
    }

} catch (\Throwable $th) {
    echo json_encode(['success' => false, 'error' => 'Server Error: ' . $th->getMessage() . ' in ' . $th->getFile() . ' on line ' . $th->getLine()]);
}
