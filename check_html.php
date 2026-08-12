<?php
$lines = file('c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists.view.php');
foreach ($lines as $i => $line) {
    if (strpos($line, '<div id="disputes-table-card"') !== false) {
        echo 'Line ' . $i . ":\n";
        for ($j = 0; $j < 15; $j++) {
            echo $lines[$i + $j];
        }
        break;
    }
}
