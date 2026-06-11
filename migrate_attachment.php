<?php
require 'config/database.php';
try {
    global $pdo;
    $pdo->exec('ALTER TABLE messages ADD COLUMN attachment VARCHAR(255) DEFAULT NULL');
    // Also modify the `message` column to allow NULL, since an attachment might be sent without text.
    $pdo->exec('ALTER TABLE messages MODIFY message TEXT NULL');
    echo 'Success';
} catch(Exception $e) {
    echo $e->getMessage();
}
