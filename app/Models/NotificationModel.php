<?php
class NotificationModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function add($title, $message, $link = null, $userId = null, $role = null, $branchId = null) {
        if ($link !== null) {
            $link = html_entity_decode($link, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (strpos($link, '//') === 0 && strpos($link, '://') === false) {
                $link = '/' . ltrim($link, '/');
            }
            if (stripos($title, 'Queue') !== false && strpos($link, 'patient-lists') !== false && strpos($link, 'tab=') === false) {
                $link = str_replace('patient-lists?', 'patient-lists?tab=queue&', $link);
                if (strpos($link, 'tab=queue') === false) {
                    $link .= (strpos($link, '?') !== false ? '&' : '?') . 'tab=queue';
                }
            }
        }
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, role, branch_id, title, message, link) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $role, $branchId, $title, $message, $link]);
    }
}
