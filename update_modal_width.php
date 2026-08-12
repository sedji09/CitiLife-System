<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

// 1. Add ID to the inner container
$oldModal = <<<HTML
<!-- RESOLVE DISPUTE MODAL -->
<div id="resolve-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-4xl w-full p-6 shadow-xl space-y-4">
HTML;
$newModal = <<<HTML
<!-- RESOLVE DISPUTE MODAL -->
<div id="resolve-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-black/50 p-4">
    <div id="resolve-modal-inner" class="bg-white rounded-2xl max-w-4xl w-full p-6 shadow-xl space-y-4 transition-all duration-300">
HTML;
$content = str_replace($oldModal, $newModal, $content);

// 2. Add logic to toggle width based on comparison box
$oldJs = <<<JS
    if (oldFindings || newFindings || oldImpression || newImpression) {
        oldFindingsEl.innerHTML = formatText(oldFindings, oldImpression);
        newFindingsEl.innerHTML = formatText(newFindings, newImpression);
        compareContainer.classList.remove('hidden');
    } else {
        compareContainer.classList.add('hidden');
    }

    document.getElementById('resolve-modal').classList.remove('hidden');
JS;
$newJs = <<<JS
    const innerModal = document.getElementById('resolve-modal-inner');
    
    if (oldFindings || newFindings || oldImpression || newImpression) {
        oldFindingsEl.innerHTML = formatText(oldFindings, oldImpression);
        newFindingsEl.innerHTML = formatText(newFindings, newImpression);
        compareContainer.classList.remove('hidden');
        innerModal.classList.remove('max-w-md');
        innerModal.classList.add('max-w-4xl');
    } else {
        compareContainer.classList.add('hidden');
        innerModal.classList.remove('max-w-4xl');
        innerModal.classList.add('max-w-md');
    }

    document.getElementById('resolve-modal').classList.remove('hidden');
JS;
$content = str_replace($oldJs, $newJs, $content);

file_put_contents($file, $content);
echo "Updated Modal Width Logic.";
