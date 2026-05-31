
(function () {
    const ROWS_PER_PAGE = 8;
    let currentPage = 1;

    // ── Helpers ───────────────────────────────────────────────────────────────
    function getFilteredRows() {
        const search = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
        const exam   = document.getElementById('filter-exam')?.value  || 'Filter by Exam Type';
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
            const rowExam =  row.dataset.exam  || '';

            const matchSearch = !search || name.includes(search) || id.includes(search);
            const matchExam   = exam === 'Filter by Exam Type' || exam === 'All' || exam === rowExam;

            return matchSearch && matchExam;
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
        const prevBtn         = document.getElementById('xray-prev-btn');
        const nextBtn         = document.getElementById('xray-next-btn');
        const pageInfo        = document.getElementById('xray-page-info');
        const recordCountInfo = document.getElementById('xray-record-count');

        if (!prevBtn || !nextBtn || !pageInfo) return;

        const startIdx = totalFiltered === 0 ? 0 : (currentPage - 1) * ROWS_PER_PAGE + 1;
        const endIdx   = Math.min(currentPage * ROWS_PER_PAGE, totalFiltered);

        pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;

        if (recordCountInfo) {
            recordCountInfo.textContent = totalFiltered === 0
                ? 'No records'
                : `Showing ${startIdx}–${endIdx} of ${totalFiltered} record${totalFiltered !== 1 ? 's' : ''}`;
        }

        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;

        prevBtn.classList.toggle('opacity-40',  currentPage <= 1);
        prevBtn.classList.toggle('cursor-not-allowed', currentPage <= 1);
        nextBtn.classList.toggle('opacity-40',  currentPage >= totalPages);
        nextBtn.classList.toggle('cursor-not-allowed', currentPage >= totalPages);
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
        if (e.target && (e.target.id === 'filter-exam' || e.target.id === 'sort-date')) applyFilters();
    });

    // ── Re-apply pagination after realtime polling replaces tbody innerHTML ───
    document.addEventListener('realtime:updated', () => {
        renderPage();
    });

    // ── Init (DOM is already ready when this script loads) ────────────────────
    function init() {
        // Wire navigation buttons
        const prevBtn = document.getElementById('xray-prev-btn');
        const nextBtn = document.getElementById('xray-next-btn');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) { currentPage--; renderPage(); }
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                const totalPages = Math.max(1, Math.ceil(getFilteredRows().length / ROWS_PER_PAGE));
                if (currentPage < totalPages) { currentPage++; renderPage(); }
            });
        }

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
