<?php
require 'config/database.php';
try {
    $pdo->exec('ALTER TABLE feedbacks ADD COLUMN case_id INT NULL AFTER id');
    $pdo->exec('ALTER TABLE feedbacks ADD FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE SET NULL');
    echo 'Altered table successfully';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
