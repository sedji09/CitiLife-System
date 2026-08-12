<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$content = str_replace('action=escalate_dispute', 'action=escalate_to_radiologist', $content);

file_put_contents($file, $content);
echo "Fixed action url.";
