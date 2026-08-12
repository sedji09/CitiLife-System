<?php
require 'config/database.php';
$stmt = $pdo->prepare("UPDATE notifications SET link = REPLACE(link, '?role=radtech&page=xray-patient-records&dispute_id=', '?role=radtech&page=patient-lists&tab=disputes&dispute_id=') WHERE link LIKE '%dispute_id=%'");
$stmt->execute();
echo $stmt->rowCount() . ' rows updated.';
