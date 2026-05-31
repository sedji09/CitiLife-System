<?php
class NotificationModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function add($title, $message, $link = null, $userId = null, $role = null, $branchId = null) {
        if ($link !== null) {
            $link = html_entity_decode($link, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, role, branch_id, title, message, link) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $role, $branchId, $title, $message, $link]);
    }
}
