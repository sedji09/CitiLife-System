<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$oldCode = <<<JS
    if (oldFindings || newFindings || oldImpression || newImpression) {
        let oldHtml = "";
        let newHtml = "";
        
        if (oldFindings) {
            oldHtml += "**FINDINGS:**\\n" + oldFindings + "\\n\\n";
            newHtml += "**FINDINGS:**\\n" + newFindings + "\\n\\n";
        }
        if (oldImpression) {
            oldHtml += "**IMPRESSION:**\\n" + oldImpression;
            newHtml += "**IMPRESSION:**\\n" + newImpression;
        }
        
        oldFindingsEl.innerText = oldHtml.trim() || 'N/A';
        newFindingsEl.innerText = newHtml.trim() || 'N/A';
        compareContainer.classList.remove('hidden');
    } else {
JS;

$newCode = <<<JS
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

$content = str_replace($oldCode, $newCode, $content);
file_put_contents($file, $content);
echo "Done";
