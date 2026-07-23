<?php
require 'config/database.php';
$stmt = $pdo->prepare("UPDATE cases SET status = 'Released', released = 1, is_amended = 1 WHERE id = 246");
$stmt->execute();
echo "Updated case 246 status to Released!";
