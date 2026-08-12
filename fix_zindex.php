<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$oldEscalate = <<<JS
        fetch('/<?= PROJECT_DIR ?>/app/Api/disputes.php?action=escalate_to_radiologist', {
            method: 'POST', body: fd
        }).then(r=>r.json()).then(res=>{
            if(res.success){
                Swal.fire('Escalated', 'The ticket is now forwarded to the Radiologist.', 'success').then(()=>location.reload());
            }else{
                Swal.fire('Error', res.message || res.error, 'error');
            }
        });
JS;

$newEscalate = <<<JS
        fetch('/<?= PROJECT_DIR ?>/app/Api/disputes.php?action=escalate_to_radiologist', {
            method: 'POST', body: fd
        }).then(r=>r.json()).then(res=>{
            if(res.success){
                closeEscalateModal();
                Swal.fire('Escalated', 'The ticket is now forwarded to the Radiologist.', 'success').then(()=>location.reload());
            }else{
                Swal.fire('Error', res.message || res.error || 'An error occurred.', 'error');
            }
        });
JS;

$oldResolve = <<<JS
        fetch('/<?= PROJECT_DIR ?>/app/Api/disputes.php?action=resolve_dispute', {
            method: 'POST', body: fd
        }).then(r=>r.json()).then(res=>{
            if(res.success){
                Swal.fire('Resolved', 'The dispute ticket is now resolved.', 'success').then(()=>location.reload());
            }else{
                Swal.fire('Error', res.message || res.error, 'error');
            }
        });
JS;

$newResolve = <<<JS
        fetch('/<?= PROJECT_DIR ?>/app/Api/disputes.php?action=resolve_dispute', {
            method: 'POST', body: fd
        }).then(r=>r.json()).then(res=>{
            if(res.success){
                closeResolveModal();
                Swal.fire('Resolved', 'The dispute ticket is now resolved.', 'success').then(()=>location.reload());
            }else{
                Swal.fire('Error', res.message || res.error || 'An error occurred.', 'error');
            }
        });
JS;

// Try to replace the exact blocks. Since the code in the file uses res.error instead of res.message || res.error, I should use str_replace safely by matching the exact current string.
// Let's do a more robust string replace using preg_replace or just rewriting the two functions completely.

$scriptStart = "function submitEscalation(e) {";
$scriptEnd = "// Disputes Pagination Logic";

$start = strpos($content, $scriptStart);
$end = strpos($content, $scriptEnd);

if ($start !== false && $end !== false) {
    $newFunctions = <<<JS
function submitEscalation(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fetch('/<?= PROJECT_DIR ?>/app/Api/disputes.php?action=escalate_to_radiologist', {
        method: 'POST', body: fd
    }).then(r=>r.json()).then(res=>{
        if(res.success){
            closeEscalateModal();
            Swal.fire({
                title: 'Escalated', 
                text: 'The ticket is now forwarded to the Radiologist.', 
                icon: 'success',
                zIndex: 10000
            }).then(()=>location.reload());
        }else{
            Swal.fire({
                title: 'Error', 
                text: res.message || res.error || 'An error occurred', 
                icon: 'error',
                zIndex: 10000
            });
        }
    }).catch(() => {
        Swal.fire({title: 'Error', text: 'Network or Server Error', icon: 'error', zIndex: 10000});
    });
}

function submitResolution(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fetch('/<?= PROJECT_DIR ?>/app/Api/disputes.php?action=resolve_dispute', {
        method: 'POST', body: fd
    }).then(r=>r.json()).then(res=>{
        if(res.success){
            closeResolveModal();
            Swal.fire({
                title: 'Resolved', 
                text: 'The dispute ticket is now resolved.', 
                icon: 'success',
                zIndex: 10000
            }).then(()=>location.reload());
        }else{
            Swal.fire({
                title: 'Error', 
                text: res.message || res.error || 'An error occurred', 
                icon: 'error',
                zIndex: 10000
            });
        }
    }).catch(() => {
        Swal.fire({title: 'Error', text: 'Network or Server Error', icon: 'error', zIndex: 10000});
    });
}

JS;
    
    $content = substr_replace($content, $newFunctions, $start, $end - $start);
    file_put_contents($file, $content);
    echo "Fixed JS functions.";
} else {
    echo "Could not find JS bounds.";
}

