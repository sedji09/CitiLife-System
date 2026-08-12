<?php
$source = 'c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists_recovered.view.php';
$target = 'c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists.view.php';

$content = file_get_contents($source);

// Remove the garbage script block
$garbage = <<<HTML
<script>
// Real-time polling for RadTech Patient Error Reports
setInterval(() => {
    // Only poll if tab is disputes and modals are not shown
    const isDisputesTab = new URLSearchParams(window.location.search).get('tab') === 'disputes';
    const escalateModal = document.getElementById('escalate-dispute-modal');
    const resolveModal = document.getElementById('resolve-dispute-modal');
    
    if (isDisputesTab && 
        (!escalateModal || !escalateModal.classList.contains('show')) && 
        (!resolveModal || !resolveModal.classList.contains('show'))) {
        
        fetch(window.location.href)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newDisputes = doc.getElementById('disputes-table-card');
                const oldDisputes = document.getElementById('disputes-table-card');
                if (newDisputes && oldDisputes) {
                    oldDisputes.innerHTML = newDisputes.innerHTML;
                    if (window.lucide) lucide.createIcons({ root: oldDisputes });
                }
            })
            .catch(err => console.error('Dispute polling error:', err));
    }
}, 5000);
</script>
HTML;

$content = str_replace($garbage, "", $content);

file_put_contents($target, $content);
echo "Cleaned patient-lists.view.php created from recovered.\n";
