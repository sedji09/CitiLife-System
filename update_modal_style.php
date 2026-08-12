<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

// 1. Update Modal HTML Layout
$oldHtml = <<<HTML
<div id="resolve-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-[600px] w-full p-6 shadow-xl space-y-4">
HTML;
$newHtml = <<<HTML
<div id="resolve-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-4xl w-full p-6 shadow-xl space-y-4">
HTML;
$content = str_replace($oldHtml, $newHtml, $content);

$oldCompare = <<<HTML
        <!-- Findings Comparison Box (Hidden by default) -->
        <div id="resolve-modal-findings-compare" class="hidden p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-3 max-h-[40vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <div class="text-[11px] font-bold text-gray-600 uppercase tracking-wider">Old Result</div>
                    <div id="resolve-old-findings" class="text-xs text-gray-800 bg-white p-2.5 rounded-lg border border-gray-200 shadow-sm whitespace-pre-line min-h-[60px]"></div>
                </div>
                <div class="space-y-1">
                    <div class="text-[11px] font-bold text-green-700 uppercase tracking-wider">New Amended Result</div>
                    <div id="resolve-new-findings" class="text-xs text-green-900 bg-green-50 p-2.5 rounded-lg border border-green-200 shadow-sm whitespace-pre-line min-h-[60px]"></div>
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
                    <div id="resolve-old-findings" class="text-xs text-gray-800 bg-white p-3 rounded-lg border border-gray-200 shadow-sm min-h-[60px] max-h-[45vh] overflow-y-auto"></div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs font-bold text-green-700 uppercase tracking-wider">New Amended Result</div>
                    <div id="resolve-new-findings" class="text-xs text-green-900 bg-green-50 p-3 rounded-lg border border-green-200 shadow-sm min-h-[60px] max-h-[45vh] overflow-y-auto"></div>
                </div>
            </div>
        </div>
HTML;
$content = str_replace($oldCompare, $newCompare, $content);


// 2. Update JS Logic to use HTML tags and formatting
$oldJs = <<<JS
    function formatText(findings, impression) {
        let output = "";
        try {
            // Try to parse as JSON (for multiple exams)
            const parsed = JSON.parse(findings);
            if (typeof parsed === 'object' && parsed !== null) {
                for (const [examName, data] of Object.entries(parsed)) {
                    output += "\\n=== " + examName.toUpperCase() + " ===\\n";
                    output += "FINDINGS:\\n" + (data.findings || 'N/A') + "\\n\\n";
                    output += "IMPRESSION:\\n" + (data.impression || 'N/A') + "\\n\\n";
                }
                return output.trim();
            }
        } catch (e) {
            // Not JSON, just standard text
        }
        
        if (findings) output += "**FINDINGS:**\\n" + findings + "\\n\\n";
        if (impression) output += "**IMPRESSION:**\\n" + impression;
        return output.trim();
    }

    if (oldFindings || newFindings || oldImpression || newImpression) {
        oldFindingsEl.innerText = formatText(oldFindings, oldImpression) || 'N/A';
        newFindingsEl.innerText = formatText(newFindings, newImpression) || 'N/A';
        compareContainer.classList.remove('hidden');
    } else {
JS;

$newJs = <<<JS
    function formatText(findings, impression) {
        let output = "";
        try {
            // Try to parse as JSON (for multiple exams)
            const parsed = JSON.parse(findings);
            if (typeof parsed === 'object' && parsed !== null) {
                for (const [examName, data] of Object.entries(parsed)) {
                    output += `<div class="mb-4">`;
                    output += `<div class="font-bold text-indigo-700 uppercase mb-2 border-b pb-1">` + examName + `</div>`;
                    output += `<div class="font-semibold text-gray-700 mb-1">FINDINGS:</div>`;
                    output += `<div class="whitespace-pre-line mb-3">` + (data.findings || 'N/A') + `</div>`;
                    output += `<div class="font-semibold text-gray-700 mb-1">IMPRESSION:</div>`;
                    output += `<div class="whitespace-pre-line">` + (data.impression || 'N/A') + `</div>`;
                    output += `</div>`;
                }
                return output;
            }
        } catch (e) {
            // Not JSON, just standard text
        }
        
        if (findings) {
            output += `<div class="font-semibold text-gray-700 mb-1">FINDINGS:</div>`;
            output += `<div class="whitespace-pre-line mb-3">` + findings + `</div>`;
        }
        if (impression) {
            output += `<div class="font-semibold text-gray-700 mb-1">IMPRESSION:</div>`;
            output += `<div class="whitespace-pre-line">` + impression + `</div>`;
        }
        return output || 'N/A';
    }

    if (oldFindings || newFindings || oldImpression || newImpression) {
        oldFindingsEl.innerHTML = formatText(oldFindings, oldImpression);
        newFindingsEl.innerHTML = formatText(newFindings, newImpression);
        compareContainer.classList.remove('hidden');
    } else {
JS;
$content = str_replace($oldJs, $newJs, $content);
file_put_contents($file, $content);
echo "Updated Modal styling.";
