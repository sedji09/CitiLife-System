<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers.php';
global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId   = $_SESSION['user_id'] ?? null;
if ($userId == 0 && isset($_SESSION['email'])) {
    // Self-healing session logic: If they had ID=0 before the schema fix, grab their actual new unique ID
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['email']]);
    $newId = $stmtUser->fetchColumn();
    if ($newId) {
        $userId = $newId;
        $_SESSION['user_id'] = $newId; // update session
    }
}

if (!defined('PROJECT_DIR')) {
    $parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    define('PROJECT_DIR', (isset($parts[0]) && $parts[0] !== 'app' && $parts[0] !== 'index.php') ? $parts[0] : 'CitiLife-System');
}

// Auto-insert overdue case notifications (Pending or Under Reading for 3+ hours)
try {
    $insertQuery = "
        INSERT INTO notifications (user_id, role, branch_id, title, message, link, is_read, created_at)
        SELECT 
            c.radiologist_id AS user_id,
            'radiologist' AS role,
            NULL AS branch_id,
            'Overdue Case Alert' AS title,
            CONCAT('Case ', c.case_number, ' has been pending/under reading for over 3 hours.') AS message,
            CONCAT('/', :projectDir, '/index.php?role=radiologist&page=case-review&id=', c.id, '&branch_id=', IFNULL(c.branch_id, '')) AS link,
            0 AS is_read,
            NOW() AS created_at
        FROM cases c
        WHERE c.status IN ('Pending', 'Under Reading')
          AND c.image_status = 'Uploaded'
          AND TIMESTAMPDIFF(HOUR, c.created_at, NOW()) >= 3
          AND NOT EXISTS (
              SELECT 1 FROM notifications n
              WHERE n.title = 'Overdue Case Alert'
                AND n.link LIKE CONCAT('%page=case-review&id=', c.id, '%')
          )
    ";
    $stmtInsert = $pdo->prepare($insertQuery);
    $stmtInsert->execute(['projectDir' => PROJECT_DIR]);

    // Auto-dismiss overdue notifications for cases that are no longer pending or under reading
    $updateQuery = "
        UPDATE notifications n
        JOIN cases c ON n.link LIKE CONCAT('%page=case-review&id=', c.id, '%')
        SET n.is_read = 1
        WHERE n.title = 'Overdue Case Alert'
          AND n.is_read = 0
          AND c.status NOT IN ('Pending', 'Under Reading')
    ";
    $pdo->exec($updateQuery);
} catch (Exception $e) {
    error_log("Error in overdue case notifier: " . $e->getMessage());
}

$role     = $_SESSION['role'];
$branchId = $_SESSION['branch_id'] ?? null;

// Handle POST request to mark notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['action']) && $input['action'] === 'mark_read') {
        $notifId = $input['notification_id'] ?? null;
        
        // Base sql
        $sql = "UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR (user_id IS NULL AND role = ? AND (branch_id IS NULL OR branch_id = ?)))";
        $params = [$userId, $role, $branchId];

        if ($notifId) {
            $sql .= " AND id = ?";
            $params[] = $notifId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true]);
        exit;
    }
}

// Fetch unread notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE is_read = 0 
      AND (user_id = ? OR (user_id IS NULL AND role = ? AND (branch_id IS NULL OR branch_id = ?)))
    ORDER BY created_at DESC, id DESC 
    LIMIT 20
");
$stmt->execute([$userId, $role, $branchId]);
$notifications = $stmt->fetchAll();

// Auto-repair: fix any old notifications with HTML-encoded & in links
$pdo->exec("UPDATE notifications SET link = REPLACE(link, '&amp;', '&') WHERE link LIKE '%&amp;%'");

// Fetch total unread count
$stmtCount = $pdo->prepare("
    SELECT COUNT(*) FROM notifications 
    WHERE is_read = 0 
      AND (user_id = ? OR (user_id IS NULL AND role = ? AND (branch_id IS NULL OR branch_id = ?)))
");
$stmtCount->execute([$userId, $role, $branchId]);
$unreadCount = $stmtCount->fetchColumn();

// Format timeago
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 60) return "Just now";
        if ($diff < 3600) return floor($diff / 60) . "m ago";
        if ($diff < 86400) return floor($diff / 3600) . "h ago";
        return floor($diff / 86400) . "d ago";
    }
}

$formattedNotifications = [];
foreach ($notifications as $n) {
    $formattedNotifications[] = [
        'id'      => $n['id'],
        'title'   => $n['title'],
        'timeAgo' => timeAgo($n['created_at']),
        'message' => $n['message'],
        'link'    => $n['link'] ? $n['link'] : '#'
    ];
}

echo json_encode([
    'unread_count'  => $unreadCount,
    'notifications' => $formattedNotifications
]);
