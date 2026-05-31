<?php
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
        $newName = trim($_POST['system_name'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        
        if (empty($newName) || empty($newEmail)) {
            echo json_encode(['success' => false, 'error' => 'Display name and username are required.']);
            exit;
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
            if ($avatarPath) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, avatar = ? WHERE id = ?");
                $success = $stmt->execute([$newName, $newEmail, $avatarPath, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $success = $stmt->execute([$newName, $newEmail, $userId]);
            }

            if ($success) {
                $_SESSION['name'] = $newName;
                $_SESSION['email'] = $newEmail;
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
                echo json_encode($response);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update database.']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error. The username might already be taken.']);
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
                // EXCLUSIVE: Update only report name, title, and signature
                $stmt = $pdo->prepare("UPDATE users SET full_name_report = ?, professional_title = ?, signature = ? WHERE id = ?");
                $success = $stmt->execute([$newName, $professionalTitle, $signaturePath, $userId]);
            } else {
                // EXCLUSIVE: Update only report name and title
                $stmt = $pdo->prepare("UPDATE users SET full_name_report = ?, professional_title = ? WHERE id = ?");
                $success = $stmt->execute([$newName, $professionalTitle, $userId]);
            }

            if ($success) {
                // DO NOT update $_SESSION['name'] here. Keep it for system name.
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
