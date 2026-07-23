<?php
require 'config/database.php';
$pdo->query("ALTER TABLE cases MODIFY COLUMN status ENUM('Pending','Under Reading','Report Ready','For Revision','Completed','Released','Rejected') DEFAULT 'Pending'");
echo "Added Released to ENUM status column!";
