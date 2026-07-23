<?php
require 'config/database.php';
$pdo->query("UPDATE cases SET status = 'Released', released = 1, is_amended = 1 WHERE id = 246");
echo "Updated case 246 directly!";
