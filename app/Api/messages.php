<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers.php';
global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        case 'fetch_unread_count':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            $count = $stmt->fetchColumn();
            echo json_encode(['success' => true, 'count' => (int) $count]);
            break;

        case 'fetch_conversations':
            require_once __DIR__ . '/../Models/UserModel.php';
            $userModel = new UserModel($pdo);

            // Fetch users the current user has chatted with, ordered by latest message
            $sql = "
                SELECT 
                    u.id, 
                    u.email, 
                    u.role,
                    u.avatar,
                    u.name,
                    u.full_name_report,
                    m.message as latest_message,
                    m.created_at as latest_message_time,
                    m.sender_id,
                    (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
                FROM users u
                JOIN (
                    SELECT 
                        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as contact_id,
                        MAX(id) as last_msg_id
                    FROM messages
                    WHERE sender_id = ? OR receiver_id = ?
                    GROUP BY contact_id
                ) as latest ON latest.contact_id = u.id
                JOIN messages m ON m.id = latest.last_msg_id
                ORDER BY m.created_at DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format names
            foreach ($conversations as &$conv) {
                $sessionName = $conv['name'] ?? ($conv['full_name_report'] ?? '');
                $info = $userModel->getDisplayInfo($conv['id'], $sessionName, $conv['email']);
                $conv['name'] = $info['displayName'];
                $conv['initials'] = $info['initials'];
            }
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            break;

        case 'fetch_chat':
            $contactId = $_GET['contact_id'] ?? 0;
            if (!$contactId) {
                echo json_encode(['error' => 'Invalid contact']);
                exit;
            }

            // Mark as read
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
            $stmt->execute([$contactId, $userId]);

            // Fetch messages
            $stmt = $pdo->prepare("
                SELECT id, sender_id, receiver_id, message, attachment, is_read, created_at 
                FROM messages 
                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at ASC
            ");
            $stmt->execute([$userId, $contactId, $contactId, $userId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        case 'send_message':
            $contactId = $_POST['contact_id'] ?? 0;
            $message = trim($_POST['message'] ?? '');
            $attachmentPath = null;

            // Handle file attachment
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['attachment']['tmp_name'];
                $fileName = $_FILES['attachment']['name'];
                $fileSize = $_FILES['attachment']['size'];

                // Validate size (25MB)
                if ($fileSize > 25 * 1024 * 1024) {
                    echo json_encode(['error' => 'File size exceeds 25MB limit.']);
                    exit;
                }

                $uploadDir = __DIR__ . '/../../public/uploads/chat_attachments/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = uniqid('chat_', true) . '.' . $fileExt;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destination)) {
                    $attachmentPath = 'public/uploads/chat_attachments/' . $newFileName;
                } else {
                    echo json_encode(['error' => 'Failed to upload attachment.']);
                    exit;
                }
            }

            if (!$contactId || (empty($message) && !$attachmentPath)) {
                echo json_encode(['error' => 'Invalid input']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, attachment) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$userId, $contactId, $message, $attachmentPath])) {
                $msgId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT id, sender_id, receiver_id, message, attachment, is_read, created_at FROM messages WHERE id = ?");
                $stmt->execute([$msgId]);
                $msg = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'message' => $msg]);
            } else {
                echo json_encode(['error' => 'Failed to send']);
            }
            break;

        case 'search_staff':
            require_once __DIR__ . '/../Models/UserModel.php';
            $userModel = new UserModel($pdo);
            $q = trim($_GET['q'] ?? '');
            $sql = "SELECT id, email, role, avatar, name, full_name_report FROM users WHERE role != 'patient' AND id != ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $allStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $staff = [];
            foreach ($allStaff as &$s) {
                $sessionName = $s['name'] ?? ($s['full_name_report'] ?? '');
                $info = $userModel->getDisplayInfo($s['id'], $sessionName, $s['email']);
                $s['name'] = $info['displayName'];
                $s['initials'] = $info['initials'];

                // Filter by display name if query exists
                if (!empty($q)) {
                    // Match display name (case-insensitive)
                    if (stripos($s['name'], $q) !== false) {
                        $staff[] = $s;
                    }
                } else {
                    $staff[] = $s;
                }
            }

            // Limit to 10 results
            $staff = array_slice($staff, 0, 10);

            echo json_encode(['success' => true, 'staff' => $staff]);
            break;

        case 'mark_read':
            $contactId = $_POST['contact_id'] ?? 0;
            if ($contactId) {
                $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
                $stmt->execute([$contactId, $userId]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Invalid contact']);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
