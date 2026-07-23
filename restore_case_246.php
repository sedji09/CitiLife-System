<?php
require 'config/database.php';
$pdo->exec("UPDATE cases SET status = 'Released', released = 1, is_amended = 1 WHERE id = 246");
echo "Case 246 status updated to Released!";
