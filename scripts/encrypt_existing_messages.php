<?php
/**
 * One-time migration script: Encrypt existing plain text messages in the database
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

global $pdo;

echo "=== CitiLife Message Encryption Migration ===\n";

try {
    $stmt = $pdo->query("SELECT id, message FROM messages WHERE message IS NOT NULL AND message != ''");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($messages);
    $encryptedCount = 0;
    $skippedCount = 0;

    $updateStmt = $pdo->prepare("UPDATE messages SET message = ? WHERE id = ?");

    $pdo->beginTransaction();

    foreach ($messages as $msg) {
        $id = $msg['id'];
        $rawMessage = $msg['message'];

        // If already encrypted, skip
        if (substr($rawMessage, 0, 4) === 'ENC:') {
            $skippedCount++;
            continue;
        }

        $encryptedMessage = encryptMessage($rawMessage);
        $updateStmt->execute([$encryptedMessage, $id]);
        $encryptedCount++;
    }

    $pdo->commit();

    echo "Total messages scanned: {$total}\n";
    echo "Messages newly encrypted: {$encryptedCount}\n";
    echo "Messages already encrypted (skipped): {$skippedCount}\n";
    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
