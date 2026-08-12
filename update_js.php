<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$js = <<<JS
// Disputes Pagination Logic
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.dispute-row');
    if (rows.length === 0) return;
    
    const itemsPerPage = 3;
    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / itemsPerPage);
    
    const prevBtn = document.getElementById('disp-prev');
    const nextBtn = document.getElementById('disp-next');
    const startSpan = document.getElementById('disp-start');
    const endSpan = document.getElementById('disp-end');
    
    function showPage(page) {
        if (rows.length === 0) return;
        const start = (page - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, rows.length);
        
        rows.forEach((row, idx) => {
            if (idx >= start && idx < end) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        if (startSpan) startSpan.innerText = start + 1;
        if (endSpan) endSpan.innerText = end;
        
        if (prevBtn) prevBtn.disabled = page === 1;
        if (nextBtn) nextBtn.disabled = page === totalPages;
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                showPage(currentPage);
            }
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                showPage(currentPage);
            }
        });
    }
    
    showPage(1);
});
JS;

$content = str_replace('// Real-time polling', $js . "\n\n// Real-time polling", $content);
file_put_contents($file, $content);
echo "Added JS pagination";
