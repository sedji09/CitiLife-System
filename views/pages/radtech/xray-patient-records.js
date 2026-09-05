(function () {
    const ROWS_PER_PAGE = 8;
    let currentPages = {
        completed: parseInt(sessionStorage.getItem('Citilife_radtechXray_page_completed')) || 1,
        disputes: parseInt(sessionStorage.getItem('Citilife_radtechXray_page_disputes')) || 1
    };

    // ── Helpers ───────────────────────────────────────────────────────────────
    function getFilteredRows(type) {
        const search = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
        const sort   = document.getElementById('sort-date')?.value    || 'Sort by:';

        const tbodyId = type === 'completed' ? 'table-body' : 'disputes-table-body';
        const rowClass = type === 'completed' ? 'tr.record-row' : 'tr.dispute-row';
        const tbody = document.getElementById(tbodyId);
        
        if (!tbody) return [];

        let rows = Array.from(tbody.querySelectorAll(rowClass));

        // Sort
        if (sort === 'Newest Case' || sort === 'Oldest Case') {
            rows.sort((a, b) => {
                const dateA = new Date(a.dataset.date || 0).getTime();
                const dateB = new Date(b.dataset.date || 0).getTime();
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

    function renderPage(type) {
        const tbodyId = type === 'completed' ? 'table-body' : 'disputes-table-body';
        const rowClass = type === 'completed' ? 'tr.record-row' : 'tr.dispute-row';
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;

        const allRows      = Array.from(tbody.querySelectorAll(rowClass));
        const filteredRows = getFilteredRows(type);
        const totalPages   = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));

        // Clamp current page
        if (currentPages[type] > totalPages) currentPages[type] = totalPages;
        if (currentPages[type] < 1)          currentPages[type] = 1;

        sessionStorage.setItem(`Citilife_radtechXray_page_${type}`, currentPages[type]);

        const startIdx = (currentPages[type] - 1) * ROWS_PER_PAGE;
        const endIdx   = startIdx + ROWS_PER_PAGE;

        // Build a Set for quick lookup of which rows are visible on this page
        const visibleSet = new Set(filteredRows.slice(startIdx, endIdx));

        allRows.forEach(row => {
            row.style.display = visibleSet.has(row) ? '' : 'none';
        });

        // Empty-state row
        let emptyMsgId = type === 'completed' ? 'empty-msg-row' : 'empty-msg-row-disputes';
        let emptyMsg = document.getElementById(emptyMsgId);
        if (filteredRows.length === 0 && allRows.length > 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('tr');
                emptyMsg.id = emptyMsgId;
                emptyMsg.innerHTML = `<td colspan="7" class="text-center py-8 text-gray-500">No records match your filters.</td>`;
                tbody.appendChild(emptyMsg);
            } else {
                emptyMsg.style.display = '';
            }
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        // Update pagination UI
        updatePaginationUI(type, filteredRows.length, totalPages);
    }

    function updatePaginationUI(type, totalFiltered, totalPages) {
        const countId = type === 'completed' ? 'xray-record-count' : 'disputes-record-count';
        const containerId = type === 'completed' ? 'xray-pagination-controls' : 'disputes-pagination-controls';
        
        const recordCountInfo = document.getElementById(countId);
        const container = document.getElementById(containerId);

        const startIdx = totalFiltered === 0 ? 0 : (currentPages[type] - 1) * ROWS_PER_PAGE + 1;
        const endIdx   = Math.min(currentPages[type] * ROWS_PER_PAGE, totalFiltered);

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
                    currentPages[type] = page;
                    renderPage(type);
                    const cardId = type === 'completed' ? 'xray-records-table-card' : 'disputes-table-card';
                    const card = document.getElementById(cardId);
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

        const curr = currentPages[type];

        // First Button
        container.appendChild(createButton('&laquo; First', 1, curr <= 1));

        // Back Button
        container.appendChild(createButton('&lsaquo; Back', curr - 1, curr <= 1));

        // Page numbers
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) {
                container.appendChild(createButton(i, i, false, i == curr));
            }
        } else {
            if (curr <= 4) {
                for (let i = 1; i <= 5; i++) {
                    container.appendChild(createButton(i, i, false, i == curr));
                }
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, totalPages == curr));
            } else if (curr >= totalPages - 3) {
                container.appendChild(createButton(1, 1, false, 1 == curr));
                container.appendChild(createEllipsis());
                for (let i = totalPages - 4; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i == curr));
                }
            } else {
                container.appendChild(createButton(1, 1, false, 1 == curr));
                container.appendChild(createEllipsis());
                
                container.appendChild(createButton(curr - 1, curr - 1, false, false));
                container.appendChild(createButton(curr, curr, false, true));
                container.appendChild(createButton(curr + 1, curr + 1, false, false));
                
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, false));
            }
        }

        // Next Button
        container.appendChild(createButton('Next &rsaquo;', curr + 1, curr >= totalPages));

        // Last Button
        container.appendChild(createButton('Last &raquo;', totalPages, curr >= totalPages));
    }

    function applyFilters() {
        currentPages.completed = 1;
        currentPages.disputes = 1;
        renderPage('completed');
        renderPage('disputes');
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
        renderPage('completed');
        renderPage('disputes');
    });

    // ── Init (DOM is already ready when this script loads) ────────────────────
    function init() {
        // Auto-set sort to "Newest Case" and render immediately
        const sortSelect = document.getElementById('sort-date');
        if (sortSelect && sortSelect.value === 'Sort by:') {
            sortSelect.value = 'Newest Case';
        }
        renderPage('completed');
        renderPage('disputes');
    }

    // Run immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
