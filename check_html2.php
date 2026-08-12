<?php
$lines = file('c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists.view.php');
for ($j = 760; $j < 790; $j++) {
    echo "Line $j: " . $lines[$j];
}
