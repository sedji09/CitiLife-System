<?php
require 'config/database.php';

// Update all "Edited Report Ready" notifications to remove the tab=disputes parameter
// unless it actually has a dispute_id
$stmt = $pdo->query("SELECT id, link FROM notifications WHERE title = 'Edited Report Ready'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $link = $row['link'];
    // If it has dispute_id, keep it (though actually the bug always added tab=disputes even without dispute_id)
    if (strpos($link, 'tab=disputes') !== false && strpos($link, 'dispute_id=') === false) {
        // Remove tab=disputes&
        $newLink = str_replace('tab=disputes&', '', $link);
        // Remove &tab=disputes
        $newLink = str_replace('&tab=disputes', '', $newLink);
        
        echo "Updating ID " . $row['id'] . " from " . $link . " to " . $newLink . "\n";
        
        $updateStmt = $pdo->prepare("UPDATE notifications SET link = ? WHERE id = ?");
        $updateStmt->execute([$newLink, $row['id']]);
    }
}
echo "Done.";
