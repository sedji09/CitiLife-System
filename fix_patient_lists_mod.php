<?php
$file = 'c:/xampp/htdocs/CitiLife-System/fix_patient_lists.php';
$content = file_get_contents($file);

// Replace the javascript IDs so it doesn't crash!
$content = str_replace("document.getElementById('escalate-modal')", "document.getElementById('escalate-dispute-modal')", $content);
$content = str_replace("document.getElementById('resolve-modal')", "document.getElementById('resolve-dispute-modal')", $content);

file_put_contents($file, $content);
echo "Fixed fix_patient_lists.php Javascript Modal IDs.\n";
