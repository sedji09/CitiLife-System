<?php
$file = 'c:/xampp/htdocs/CitiLife-System/fix_patient_lists.php';
$content = file_get_contents($file);

$content = str_replace(
    "if (strpos(\$content, 'disputes-table-card') === false) {", 
    "if (strpos(\$content, '<div id=\"disputes-table-card\"') === false) {", 
    $content
);

file_put_contents($file, $content);
echo "Fixed strpos condition.\n";
