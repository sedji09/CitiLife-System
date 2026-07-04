<?php
// active_users_count.php
// Expected to be included from public/index.php through routes.php, so $pdo is available.
global $pdo;

if (!isset($pdo) || $pdo === null) {
    if (isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    } else {
        $dbConfig = require __DIR__ . '/../../config/db.php';
        $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    }
}

if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection missing']);
    exit;
}

try {
    $count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active' AND last_activity >= NOW() - INTERVAL 15 MINUTE")->fetchColumn();
    
    header('Content-Type: application/json');
    echo json_encode(['count' => $count]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
exit;
