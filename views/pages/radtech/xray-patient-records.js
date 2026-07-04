
(function () {
    const ROWS_PER_PAGE = 8;
    let currentPage = parseInt(sessionStorage.getItem('CitiLife_radtechXray_page')) || 1;

    // ── Helpers ───────────────────────────────────────────────────────────────
    function getFilteredRows() {
        const search = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
        const sort   = document.getElementById('sort-date')?.value    || 'Sort by:';

        const tbody = document.getElementById('table-body');
        if (!tbody) return [];

        let rows = Array.from(tbody.querySelectorAll('tr.record-row'));

        // Sort
        if (sort === 'Newest Case' || sort === 'Oldest Case') {
            rows.sort((a, b) => {
                const dateA = new Date(a.dataset.date).getTime();
                const dateB = new Date(b.dataset.date).getTime();
                return sort === 'Newest Case' ? dateB - dateA : dateA - dateB;
            });
            rows.forEach(row => tbody.appendChild(row));
        }

        // Filter
        return rows.filter(row => {
            const name    = (row.dataset.name || '').toLowerCase();
            const id      = (row.dataset.id   || '').toLowerCase();
            const patient = (row.dataset.patient || '').toLowerCase();

            const matchSearch = !search || name.includes(search) || id.includes(search) || patient.includes(search);

            return matchSearch;
        });
    }

    function renderPage() {
        const tbody = document.getElementById('table-body');
        if (!tbody) return;

        const allRows      = Array.from(tbody.querySelectorAll('tr.record-row'));
        const filteredRows = getFilteredRows();
        const totalPages   = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));

        // Clamp current page
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1)          currentPage = 1;

        sessionStorage.setItem('CitiLife_radtechXray_page', currentPage);

        const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
        const endIdx   = startIdx + ROWS_PER_PAGE;

        // Build a Set for quick lookup of which rows are visible on this page
        const visibleSet = new Set(filteredRows.slice(startIdx, endIdx));

        allRows.forEach(row => {
            row.style.display = visibleSet.has(row) ? '' : 'none';
        });

        // Empty-state row
        let emptyMsg = document.getElementById('empty-msg-row');
        if (filteredRows.length === 0 && allRows.length > 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('tr');
                emptyMsg.id = 'empty-msg-row';
                emptyMsg.innerHTML = `<td colspan="6" class="text-center py-8 text-gray-500">No records match your filters.</td>`;
                tbody.appendChild(emptyMsg);
            } else {
                emptyMsg.style.display = '';
            }
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        // Update pagination UI
        updatePaginationUI(filteredRows.length, totalPages);
    }

    function updatePaginationUI(totalFiltered, totalPages) {
        const recordCountInfo = document.getElementById('xray-record-count');
        const container = document.getElementById('xray-pagination-controls');

        const startIdx = totalFiltered === 0 ? 0 : (currentPage - 1) * ROWS_PER_PAGE + 1;
        const endIdx   = Math.min(currentPage * ROWS_PER_PAGE, totalFiltered);

        if (recordCountInfo) {
            recordCountInfo.innerHTML = totalFiltered === 0
                ? 'No records'
                : `Showing <span class="font-semibold text-gray-800">${startIdx}</span> to <span class="font-semibold text-gray-800">${endIdx}</span> of <span class="font-semibold text-gray-800">${totalFiltered}</span> record${totalFiltered !== 1 ? 's' : ''}`;
        }

        if (!container) return;
        container.innerHTML = '';

        // Helper to create a button
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
                    renderPage();
                    const card = document.getElementById('xray-records-table-card');
                    if (card) {
                        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                };
            }
            return btn;
        }

        // Helper to create ellipsis
        function createEllipsis() {
            const span = document.createElement('span');
            span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
            span.innerText = '...';
            return span;
        }

        // First Button
        container.appendChild(createButton('&laquo; First', 1, currentPage <= 1));

        // Back Button
        container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage <= 1));

        // Page numbers
        if (totalPages <= 7) {
            // Show all pages
            for (let i = 1; i <= totalPages; i++) {
                container.appendChild(createButton(i, i, false, i == currentPage));
            }
        } else {
            // We have many pages
            if (currentPage <= 4) {
                // Near start: 1, 2, 3, 4, 5, ..., T
                for (let i = 1; i <= 5; i++) {
                    container.appendChild(createButton(i, i, false, i == currentPage));
                }
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, totalPages == currentPage));
            } else if (currentPage >= totalPages - 3) {
                // Near end: 1, ..., T-4, T-3, T-2, T-1, T
                container.appendChild(createButton(1, 1, false, 1 == currentPage));
                container.appendChild(createEllipsis());
                for (let i = totalPages - 4; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i == currentPage));
                }
            } else {
                // Middle: 1, ..., C-1, C, C+1, ..., T
                container.appendChild(createButton(1, 1, false, 1 == currentPage));
                container.appendChild(createEllipsis());
                
                container.appendChild(createButton(currentPage - 1, currentPage - 1, false, false));
                container.appendChild(createButton(currentPage, currentPage, false, true));
                container.appendChild(createButton(currentPage + 1, currentPage + 1, false, false));
                
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, false));
            }
        }

        // Next Button
        container.appendChild(createButton('Next &rsaquo;', currentPage + 1, currentPage >= totalPages));

        // Last Button
        container.appendChild(createButton('Last &raquo;', totalPages, currentPage >= totalPages));
    }

    function applyFilters() {
        currentPage = 1;   // reset to first page whenever filter changes
        renderPage();
    }

    // ── Event listeners ───────────────────────────────────────────────────────
    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'search-input') applyFilters();
    });

    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'sort-date') applyFilters();
    });

    // ── Re-apply pagination after realtime polling replaces tbody innerHTML ───
    document.addEventListener('realtime:updated', () => {
        renderPage();
    });

    // ── Init (DOM is already ready when this script loads) ────────────────────
    function init() {
        // Auto-set sort to "Newest Case" and render immediately
        const sortSelect = document.getElementById('sort-date');
        if (sortSelect && sortSelect.value === 'Sort by:') {
            sortSelect.value = 'Newest Case';
        }
        renderPage();
    }

    // Run immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
