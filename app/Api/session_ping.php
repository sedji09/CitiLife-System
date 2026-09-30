<?php
/**
 * app/Api/session_ping.php
 * Session activity ping and renewal endpoint.
 * Renews LAST_ACTIVITY timestamp for active user sessions.
 */
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'unauthenticated',
        'message' => 'No active user session found'
    ]);
    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();

$timeoutMinutes = function_exists('getSystemSetting') ? (int) getSystemSetting('auto_logout_minutes', 30) : 30;
if ($timeoutMinutes <= 0) {
    $timeoutMinutes = 30;
}

echo json_encode([
    'status' => 'ok',
    'last_activity' => $_SESSION['LAST_ACTIVITY'],
    'timeout_minutes' => $timeoutMinutes,
    'user_id' => $_SESSION['user_id']
]);
exit;
