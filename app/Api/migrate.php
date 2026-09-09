<?php
require_once __DIR__ . '/../../config/database.php';
global $pdo;

$results = [];

// Ensure tables exist
$tableSchemas = [
    "notifications" => "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `role` varchar(50) DEFAULT NULL,
        `branch_id` int(11) DEFAULT NULL,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `link` varchar(255) DEFAULT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_user_read` (`user_id`,`is_read`),
        KEY `idx_role_branch` (`role`,`branch_id`,`is_read`),
        KEY `idx_title_read` (`title`,`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "messages" => "CREATE TABLE IF NOT EXISTS `messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sender_id` int(11) NOT NULL,
        `receiver_id` int(11) NOT NULL,
        `message` text DEFAULT NULL,
        `attachment` varchar(255) DEFAULT NULL,
        `is_read` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `sender_id` (`sender_id`),
        KEY `receiver_id` (`receiver_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
];

foreach ($tableSchemas as $tName => $createSql) {
    try {
        $pdo->exec($createSql);
        $results[] = "TABLE OK: {$tName}";
    } catch (\Throwable $e) {
        $results[] = "TABLE ERROR ({$tName}): " . $e->getMessage();
    }
}

// Column migrations
$columnMigrations = [
    "ALTER TABLE requests ADD COLUMN original_price DECIMAL(10,2) DEFAULT NULL",
    "ALTER TABLE requests ADD COLUMN philhealth_discount DECIMAL(10,2) DEFAULT 0.00",
    "ALTER TABLE requests ADD COLUMN amount_due DECIMAL(10,2) DEFAULT NULL",
    "ALTER TABLE xray_services ADD COLUMN is_philhealth_covered TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE xray_services ADD COLUMN philhealth_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    "ALTER TABLE payments ADD COLUMN original_amount DECIMAL(10,2) DEFAULT NULL AFTER request_id",
    "ALTER TABLE payments ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 AFTER original_amount",
    "ALTER TABLE requests MODIFY COLUMN philhealth_relation ENUM('Principal Member','Qualified Dependent') NULL DEFAULT NULL",
    "ALTER TABLE cases MODIFY COLUMN philhealth_relation ENUM('Principal Member','Qualified Dependent') NULL DEFAULT NULL",
];

foreach ($columnMigrations as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = "COLUMN OK: " . $sql;
    } catch (\Throwable $e) {
        $results[] = "COLUMN SKIP (exists or error): " . $e->getMessage();
    }
}

header('Content-Type: text/plain; charset=UTF-8');
echo implode("\n", $results);
exit;
