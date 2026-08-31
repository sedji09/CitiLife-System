<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT philhealth_relation FROM requests WHERE philhealth_id='11-111111111-1'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
