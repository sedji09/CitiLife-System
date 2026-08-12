<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$htmlStart = '<!-- Pagination Controls -->';
$htmlEnd = '<!-- ESCALATE TO RADIOLOGIST MODAL -->';

$startPos = strpos($content, $htmlStart);
$endPos = strpos($content, $htmlEnd);

if ($startPos !== false && $endPos !== false) {
    $newHtml = <<<HTML
<!-- Pagination Controls -->
    <?php if (!empty(\$disputes) && count(\$disputes) > 3): ?>
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4" id="disputes-pagination">
        <!-- Record count -->
        <span id="disp-record-count" class="text-xs text-gray-500 font-medium">
            Showing <span id="disp-start">1</span> to <span id="disp-end">3</span> of <span class="font-semibold text-gray-800"><?= count(\$disputes) ?></span> record<?= count(\$disputes) !== 1 ? 's' : '' ?>
        </span>
        
        <!-- Pagination Controls -->
        <div class="flex items-center flex-wrap gap-1.5" id="disp-pagination-controls">
            <!-- Dynamic page buttons will be inserted here -->
        </div>
    </div>
    <?php endif; ?>
</div>
HTML;
    $content = substr_replace($content, $newHtml . "\n    ", $startPos, $endPos - $startPos);
}

$jsStart = '// Disputes Pagination Logic';
$jsEnd = '</script>';

$startPosJs = strpos($content, $jsStart);
$endPosJs = strrpos($content, $jsEnd);

if ($startPosJs !== false && $endPosJs !== false) {
    $newJs = <<<JS
// Disputes Pagination Logic
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.dispute-row');
    if (rows.length === 0) return;
    
    const itemsPerPage = 3;
    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / itemsPerPage);
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
});

JS;
    $content = substr_replace($content, $newJs, $startPosJs, $endPosJs - $startPosJs);
}

file_put_contents($file, $content);
echo "Updated pagination.";
