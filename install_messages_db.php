<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo->beginTransaction();

    // 1. Create messages table
    $sql = "
    CREATE TABLE IF NOT EXISTS `messages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `sender_id` int(11) NOT NULL,
      `receiver_id` int(11) NOT NULL,
      `message` text NOT NULL,
      `is_read` tinyint(1) NOT NULL DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `sender_id` (`sender_id`),
      KEY `receiver_id` (`receiver_id`),
      CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    $pdo->exec($sql);
    echo "Messages table created successfully.\n";

    // 2. Add 'messages' permission to role_permissions for staff roles
    // First, check if role_permissions exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'role_permissions'");
    if ($stmt->rowCount() > 0) {
        $roles = ['radtech', 'radiologist', 'admin_central', 'branch_admin', 'it_admin'];
        $insertStmt = $pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role`, `perm_key`, `access_level`) VALUES (?, 'messages', 1)");
        foreach ($roles as $role) {
            $insertStmt->execute([$role]);
        }
        echo "Permissions added successfully.\n";
    } else {
        echo "Note: role_permissions table does not exist. Skipping permission insertion.\n";
    }

    $pdo->commit();
    echo "Database setup complete.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
