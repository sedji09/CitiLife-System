<?php
$file = 'c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists.view.php';
$content = file_get_contents($file);

// 1. Insert pagination HTML
$htmlTarget = "        </table>\n    </div>\n</div>\n\n<script>";
$htmlReplacement = <<<EOF
        </table>
        <!-- Pagination Controls -->
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between rounded-b-lg">
            <div class="text-sm text-gray-700">
                Showing <span id="page-start" class="font-medium">0</span> to <span id="page-end" class="font-medium">0</span> of <span id="page-total" class="font-medium">0</span> results
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="btn-prev" onclick="changePage(-1)" class="px-3 py-1 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <div id="page-numbers" class="flex items-center gap-1"></div>
                <button type="button" id="btn-next" onclick="changePage(1)" class="px-3 py-1 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    const pageSize = 10;
    let filteredRows = [];

    function renderPagination() {
        const total = filteredRows.length;
        const totalPages = Math.ceil(total / pageSize) || 1;
        
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * pageSize;
        const endIdx = Math.min(startIdx + pageSize, total);

        filteredRows.forEach((row, idx) => {
            if (idx >= startIdx && idx < endIdx) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('page-total').textContent = total;
        document.getElementById('page-start').textContent = total === 0 ? 0 : startIdx + 1;
        document.getElementById('page-end').textContent = endIdx;

        document.getElementById('btn-prev').disabled = currentPage === 1;
        document.getElementById('btn-next').disabled = currentPage === totalPages;

        const pageNumbers = document.getElementById('page-numbers');
        pageNumbers.innerHTML = '';
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `px-3 py-1 text-sm border rounded-md \${i === currentPage ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'}`;
            btn.textContent = i;
            btn.onclick = () => {
                currentPage = i;
                renderPagination();
            };
            pageNumbers.appendChild(btn);
        }
    }

    function changePage(delta) {
        currentPage += delta;
        renderPagination();
    }
EOF;

// Try with \r\n and \n
if (strpos($content, $htmlTarget) !== false) {
    $content = str_replace($htmlTarget, $htmlReplacement, $content);
} else {
    $htmlTarget2 = "        </table>\r\n    </div>\r\n</div>\r\n\r\n<script>";
    $content = str_replace($htmlTarget2, $htmlReplacement, $content);
}


// 2. Update applyFilters logic
$jsTarget = <<<EOF
        // Filter
        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const id = (row.dataset.id || '').toLowerCase();
            const patient = (row.dataset.patient || '').toLowerCase();
            const rowPriority = row.dataset.priority || '';
            const rowStatus = (row.dataset.status || '').toLowerCase();
            const isToday = row.dataset.isToday === 'true';

            const matchSearch = name.includes(search) || id.includes(search) || patient.includes(search) || rowPriority.toLowerCase().includes(search) || rowStatus.includes(search);
            const matchPriority = priority === 'Filter by Priority' || priority === 'All' || priority === rowPriority;

            let matchDate = true;
            if (dateFilter === 'Today') matchDate = isToday;
            if (dateFilter === 'Backlog') matchDate = !isToday;

            if (matchSearch && matchPriority && matchDate) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
EOF;

$jsReplacement = <<<EOF
        filteredRows = [];
        // Filter
        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const id = (row.dataset.id || '').toLowerCase();
            const patient = (row.dataset.patient || '').toLowerCase();
            const rowPriority = row.dataset.priority || '';
            const rowStatus = (row.dataset.status || '').toLowerCase();
            const isToday = row.dataset.isToday === 'true';

            const matchSearch = name.includes(search) || id.includes(search) || patient.includes(search) || rowPriority.toLowerCase().includes(search) || rowStatus.includes(search);
            const matchPriority = priority === 'Filter by Priority' || priority === 'All' || priority === rowPriority;

            let matchDate = true;
            if (dateFilter === 'Today') matchDate = isToday;
            if (dateFilter === 'Backlog') matchDate = !isToday;

            if (matchSearch && matchPriority && matchDate) {
                filteredRows.push(row);
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        currentPage = 1;
        renderPagination();
EOF;

// Normalize line endings to avoid failure
$jsTargetNormalized = preg_replace('/\r\n/', "\n", $jsTarget);
$jsTargetRegex = '/' . preg_quote($jsTargetNormalized, '/') . '/';
$jsTargetRegex = str_replace('\n', '\r?\n\s*', $jsTargetRegex); // Allow flexible whitespace/indenting

$content = preg_replace($jsTargetRegex, $jsReplacement, $content, 1, $count);
if ($count > 0) {
    file_put_contents($file, $content);
    echo "Successfully injected pagination.\n";
} else {
    echo "Could not inject applyFilters update.\n";
}
