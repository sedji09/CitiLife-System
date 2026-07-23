<?php
require 'config/database.php';

$stmt = $pdo->query("SELECT id, status, dispute_category, HEX(status) FROM result_disputes ORDER BY id DESC LIMIT 5");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";


