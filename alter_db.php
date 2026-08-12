<?php
require 'config/database.php';
$pdo->exec("ALTER TABLE result_disputes ADD COLUMN old_findings TEXT NULL AFTER description, ADD COLUMN old_impression TEXT NULL AFTER old_findings;");
echo "Done.";
