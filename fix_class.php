<?php
$f='c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php'; 
file_put_contents($f, str_replace('w-4.5 h-4.5', 'w-5 h-5', file_get_contents($f)));
