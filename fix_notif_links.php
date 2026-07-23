<?php
require 'config/database.php';

$stmt = $pdo->prepare("UPDATE notifications SET link = CONCAT('index.php?role=radtech&page=xray-patient-records&dispute_id=', SUBSTRING_INDEX(link, '=', -1)) WHERE link LIKE '%xray-patient-records%'");
$stmt->execute();
echo "Updated notification links!";
