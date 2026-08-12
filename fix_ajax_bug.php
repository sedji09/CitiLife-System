<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$jsStart = "// Disputes Pagination Logic";
$jsEnd = "</script>";

$start = strpos($content, $jsStart);
$end = strrpos($content, $jsEnd);

$newJs = <<<JS
// Disputes Pagination Logic
document.addEventListener('DOMContentLoaded', function() {
    let rows = document.querySelectorAll('.dispute-row');
    if (rows.length === 0) return;
    
    const itemsPerPage = 6;
    let currentPage = 1;
    let totalPages = Math.ceil(rows.length / itemsPerPage);
    const container = document.getElementById('disp-pagination-controls');
    const startSpan = document.getElementById('disp-start');
    const endSpan = document.getElementById('disp-end');

    function createButton(label, page, disabled, isActive = false) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.innerHTML = label;
        
        if (isActive) {
            btn.className = "px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600";
        } else {
            btn.className = "px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed shadow-sm";
        }
        
        if (disabled) {
            btn.disabled = true;
        } else {
            btn.onclick = () => {
                currentPage = page;
                updatePagination();
                const card = document.getElementById('disputes-table-card');
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            };
        }
        return btn;
    }

    function createEllipsis() {
        const span = document.createElement('span');
        span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
        span.innerHTML = "...";
        return span;
    }

    function updatePagination() {
        if (!container) return;
        container.innerHTML = '';
        
        const start = (currentPage - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, rows.length);
        
        rows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });
        
        if (startSpan) startSpan.innerText = start + 1;
        if (endSpan) endSpan.innerText = end;

        // Previous button
        container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage === 1));

        // Logic for numbered buttons
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) {
                container.appendChild(createButton(i, i, false, i === currentPage));
            }
        } else {
            container.appendChild(createButton(1, 1, false, 1 === currentPage));
            
            if (currentPage > 3) container.appendChild(createEllipsis());
            
            let startPage = Math.max(2, currentPage - 1);
            let endPage = Math.min(totalPages - 1, currentPage + 1);
            
            if (currentPage === 1) endPage = 3;
            if (currentPage === totalPages) startPage = totalPages - 2;
            
            for (let i = startPage; i <= endPage; i++) {
                container.appendChild(createButton(i, i, false, i === currentPage));
            }
            
            if (currentPage < totalPages - 2) container.appendChild(createEllipsis());
            
            container.appendChild(createButton(totalPages, totalPages, false, totalPages === currentPage));
        }

        // Next and Last buttons
        container.appendChild(createButton('Next &rsaquo;', currentPage + 1, currentPage === totalPages));
        container.appendChild(createButton('Last &raquo;', totalPages, currentPage === totalPages));
    }
    
    updatePagination();

    // Re-apply pagination when realtime-update alters the table body
    const tbody = document.getElementById('disputes-table-body');
    if (tbody) {
        const observer = new MutationObserver(() => {
            rows = document.querySelectorAll('.dispute-row');
            totalPages = Math.ceil(rows.length / itemsPerPage);
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            if (totalPages === 0) currentPage = 1;
            updatePagination();
        });
        observer.observe(tbody, { childList: true });
    }
});
JS;

$content = substr_replace($content, $newJs . "\n", $start, $end - $start);
file_put_contents($file, $content);
echo "Fixed realtime auto-refresh breaking pagination.";
