<?php
$sourcePath = 'views/pages/radtech/xray-patient-records.view.php';
$targetPath = 'views/pages/radtech/patient-lists.view.php';

$sourceContent = file_get_contents($sourcePath);

$startTable = strpos($sourceContent, '<!-- DISPUTES / ERROR REPORTS TABLE CARD');
$endTable = strpos($sourceContent, '<!-- ESCALATE TO RADIOLOGIST MODAL -->');
$tableHTML = substr($sourceContent, $startTable, $endTable - $startTable);

$startModals = strpos($sourceContent, '<!-- ESCALATE TO RADIOLOGIST MODAL -->');
$endModals = strpos($sourceContent, '<script>');
$modalsHTML = substr($sourceContent, $startModals, $endModals - $startModals);

$startJS = strpos($sourceContent, '<script>');
$endJS = strpos($sourceContent, '</script>', $startJS) + 9;
$jsHTML = substr($sourceContent, $startJS, $endJS - $startJS);

// Display "Under verification" text
$tableHTML = str_replace(
    "<?= htmlspecialchars(\$d['status']) ?>",
    "<?php \$dispStatus = \$d['status']; if (\$dispStatus === 'Pending RadTech Verification') \$dispStatus = 'Under verification'; if (\$dispStatus === 'Escalated to Radiologist') \$dispStatus = 'Radiologist Review'; echo htmlspecialchars(\$dispStatus); ?>",
    $tableHTML
);

// Control visibility via PHP instead of JS
$tableHTML = str_replace(
    'class="hidden rounded-xl',
    'class="<?= $currentTab === \'disputes\' ? \'\' : \'hidden\' ?> rounded-xl',
    $tableHTML
);

// Remove switchRadtechTab and DOMContentLoaded
$jsHTML = preg_replace('/function switchRadtechTab\(tab\) \{[\s\S]*?\}\n\}\n/m', '', $jsHTML);
$jsHTML = preg_replace('/document\.addEventListener\(\'DOMContentLoaded\', \(\) => \{.*?\n\s*\}\);\s*/s', '', $jsHTML);

$appendContent = "\n\n" . $tableHTML . "\n\n" . $modalsHTML . "\n\n" . $jsHTML;
file_put_contents($targetPath, $appendContent, FILE_APPEND);
echo "Appended successfully.";
