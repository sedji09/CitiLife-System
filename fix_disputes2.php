<?php
$pdo = require 'c:\\xampp\\htdocs\\CitiLife-System\\config\\database.php';
$stmt = $pdo->query("SELECT id, description FROM result_disputes WHERE description LIKE 'Requested Info Correction:%'");
$rows = $stmt->fetchAll();
$count = 0;
foreach ($rows as $row) {
    $desc = $row['description'];
    // Format: Requested Info Correction: Incorrect First Name, Incorrect Last Name...
    $desc = str_replace('Requested Info Correction:', "Info correction:\n  •", $desc);
    $desc = str_replace(', ', "\n  • ", $desc);
    $desc = str_replace('Incorrect ', '', $desc); // Optional, makes it cleaner just like the new format
    
    $upd = $pdo->prepare("UPDATE result_disputes SET description = ? WHERE id = ?");
    $upd->execute([trim($desc), $row['id']]);
    $count++;
}
echo "$count rows updated.";
?>
