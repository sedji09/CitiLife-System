<?php
$file = 'c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists.view.php';
$content = file_get_contents($file);

$content = str_replace("getElementById('escalate-modal')", "getElementById('escalate-dispute-modal')", $content);
$content = str_replace("getElementById('resolve-modal')", "getElementById('resolve-dispute-modal')", $content);

file_put_contents($file, $content);
echo "Fixed JS modal IDs.\n";
