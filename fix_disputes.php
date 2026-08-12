<?php
$pdo = require 'c:\\xampp\\htdocs\\CitiLife-System\\config\\database.php';
$stmt = $pdo->query("UPDATE result_disputes SET description = REPLACE(description, 'Needs updating (Patient claims their profile settings have been updated for):', 'Info correction:')");
echo $stmt->rowCount() . ' rows updated in result_disputes.';
?>
