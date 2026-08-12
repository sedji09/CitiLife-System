<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$oldJs = <<<JS
        if (findings || impression) {
            if (findings) output += `<div class="whitespace-pre-line mb-3">` + findings + `</div>`;
            if (impression) output += `<div class="whitespace-pre-line">` + impression + `</div>`;
        }
        return output || 'N/A';
JS;

$newJs = <<<JS
        if (findings || impression) {
            if (findings) {
                output += `<div class="font-semibold text-gray-700 mb-1">FINDINGS:</div>`;
                output += `<div class="whitespace-pre-line mb-3">` + findings + `</div>`;
            }
            if (impression) {
                output += `<div class="font-semibold text-gray-700 mb-1">IMPRESSION:</div>`;
                output += `<div class="whitespace-pre-line">` + impression + `</div>`;
            }
        }
        return output || 'N/A';
JS;
$content = str_replace($oldJs, $newJs, $content);
file_put_contents($file, $content);
echo "Restored JS labels.";
