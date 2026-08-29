<?php
$html = file_get_contents('rendered.html');
preg_match_all('/<([a-zA-Z0-9\-]+)([^>]+)>/', $html, $matches);
foreach ($matches[2] as $idx => $attrs) {
    preg_match_all('/([a-zA-Z0-9\-\@\:]+)=/', $attrs, $attrNames);
    $counts = array_count_values($attrNames[1]);
    foreach ($counts as $name => $count) {
        if ($count > 1) {
            echo "Duplicate attribute '$name' on tag <" . $matches[1][$idx] . ">\n";
        }
    }
}
