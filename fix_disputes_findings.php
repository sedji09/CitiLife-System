<?php
$pdo = require 'c:\\xampp\\htdocs\\CitiLife-System\\config\\database.php';
$stmt = $pdo->query("SELECT id, description, dispute_category FROM result_disputes");
$rows = $stmt->fetchAll();
$count = 0;

foreach ($rows as $row) {
    $desc = trim($row['description']);
    $originalDesc = $desc;
    $cat = $row['dispute_category'];

    // If it's just raw text without the new prefixes
    if (strpos($desc, 'Wrong Patient Info:') === false && 
        strpos($desc, 'Findings Note:') === false && 
        strpos($desc, 'Info correction:') === false) {
        
        if ($cat === 'findings_error' || $cat === 'exam_details_error' || $cat === 'other') {
            $desc = ltrim($desc, '•*- ');
            $desc = "Findings Note:\n  • " . $desc;
        }
    }

    if ($desc !== $originalDesc) {
        $upd = $pdo->prepare("UPDATE result_disputes SET description = ? WHERE id = ?");
        $upd->execute([$desc, $row['id']]);
        $count++;
    }
}

echo "$count remaining unformatted records updated.\n";
?>
