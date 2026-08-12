<?php
require 'config/database.php';
$pdo->query("UPDATE result_disputes SET old_findings = 'No active infiltrates.', old_impression = 'Normal Chest' WHERE status = 'Pending RadTech Verification'");
echo "updated dummy data";
