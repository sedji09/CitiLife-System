<?php
$file = 'c:/xampp/htdocs/CitiLife-System/fix_patient_lists.php';
$content = file_get_contents($file);

$content = str_replace(
    'preg_replace(\'/<script>/\', $disputesHtml . "\n<script>", $content, 1)', 
    'str_replace(\'<!-- Prominent Spinning Loader -->\', $disputesHtml . "\n<!-- Prominent Spinning Loader -->", $content)', 
    $content
);

$content = str_replace(
    'preg_replace(\'/<\/script>/\', $jsHtml . "\n</script>", $content, 1)', 
    'str_replace(\'<!-- Prominent Spinning Loader -->\', "<script>\n" . $jsHtml . "\n</script>\n<!-- Prominent Spinning Loader -->", $content)', 
    $content
);

file_put_contents($file, $content);
echo "Fixed fix_patient_lists.php\n";
