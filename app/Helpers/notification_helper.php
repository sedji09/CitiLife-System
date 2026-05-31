<?php
// Function to add a notification to the database
function addNotification($pdo, $title, $message, $link = null, $user_id = null, $role = null, $branch_id = null) {
    // Ensure the link is stored as plain URL (never HTML-encoded)
    if ($link !== null) {
        $link = html_entity_decode($link, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, role, branch_id, title, message, link) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $role, $branch_id, $title, $message, $link]);
}
?>
