<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

// Fix 1: Change max-h-[45vh] to a standard max-h-72 or max-h-80
// And max-w-4xl is good, but maybe max-w-3xl is better so it's not too wide.
$oldCompare = <<<HTML
        <!-- Findings Comparison Box (Hidden by default) -->
        <div id="resolve-modal-findings-compare" class="hidden p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-3">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Old Result</div>
                    <div id="resolve-old-findings" class="text-xs text-gray-800 bg-white p-3 rounded-lg border border-gray-200 shadow-sm min-h-[60px] max-h-[45vh] overflow-y-auto"></div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs font-bold text-green-700 uppercase tracking-wider">New Amended Result</div>
                    <div id="resolve-new-findings" class="text-xs text-green-900 bg-green-50 p-3 rounded-lg border border-green-200 shadow-sm min-h-[60px] max-h-[45vh] overflow-y-auto"></div>
                </div>
            </div>
        </div>
HTML;

$newCompare = <<<HTML
        <!-- Findings Comparison Box (Hidden by default) -->
        <div id="resolve-modal-findings-compare" class="hidden p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-3">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Old Result</div>
                    <div id="resolve-old-findings" class="text-xs text-gray-800 bg-white p-3 rounded-lg border border-gray-200 shadow-sm min-h-[60px] max-h-72 overflow-y-auto"></div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs font-bold text-green-700 uppercase tracking-wider">New Amended Result</div>
                    <div id="resolve-new-findings" class="text-xs text-green-900 bg-green-50 p-3 rounded-lg border border-green-200 shadow-sm min-h-[60px] max-h-72 overflow-y-auto"></div>
                </div>
            </div>
        </div>
HTML;
$content = str_replace($oldCompare, $newCompare, $content);


// Fix 2: Remove the duplicated "FINDINGS:" text for non-JSON content
$oldJs = <<<JS
        if (findings) {
            output += `<div class="font-semibold text-gray-700 mb-1">FINDINGS:</div>`;
            output += `<div class="whitespace-pre-line mb-3">` + findings + `</div>`;
        }
        if (impression) {
            output += `<div class="font-semibold text-gray-700 mb-1">IMPRESSION:</div>`;
            output += `<div class="whitespace-pre-line">` + impression + `</div>`;
        }
        return output || 'N/A';
JS;

$newJs = <<<JS
        if (findings || impression) {
            if (findings) output += `<div class="whitespace-pre-line mb-3">` + findings + `</div>`;
            if (impression) output += `<div class="whitespace-pre-line">` + impression + `</div>`;
        }
        return output || 'N/A';
JS;
$content = str_replace($oldJs, $newJs, $content);

file_put_contents($file, $content);
echo "Fixed modal height and text duplicates.";
