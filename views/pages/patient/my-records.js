
(function () {
    // Prevent duplicate initialization if script is somehow re-run
    if (window.__RECORDS_INIT_DONE__) return;
    window.__RECORDS_INIT_DONE__ = true;

    const ROWS_PER_PAGE = 10;
    let currentPage = 1;

    // ── Helpers ───────────────────────────────────────────────────────────────
    function getFilteredRows() {
        const searchInput = document.getElementById('record-search-input');
        const branchSelect = document.getElementById('record-branch-filter');
        const sortSelect = document.getElementById('record-sort-date');

        const search = (searchInput?.value || '').toLowerCase().trim();
        const branch = (branchSelect?.value || 'all branches').toLowerCase().trim();
        const sort = sortSelect?.value || 'Newest Case';

        const tableBody = document.querySelector('#desktop-table-body');
        const cardContainer = document.querySelector('#mobile-cards-container');
        
        if (!tableBody && !cardContainer) return { desktop: [], mobile: [] };

        let desktopRows = tableBody ? Array.from(tableBody.querySelectorAll('tr.record-row')) : [];
        let mobileCards = cardContainer ? Array.from(cardContainer.querySelectorAll('.record-card')) : [];

        function filterItem(item) {
            const id = (item.dataset.id || '').toLowerCase();
            const exam = (item.dataset.exam || '').toLowerCase();
            const itemBranch = (item.dataset.branch || '').toLowerCase().trim();

            const matchSearch = !search || id.includes(search) || exam.includes(search);
            const isAllBranch = branch === 'all branches' || branch === 'all' || branch === '';
            const matchBranch = isAllBranch || itemBranch === branch;

            return matchSearch && matchBranch;
        }

        // Sort items by date
        function sortByDate(a, b) {
            // Robust parsing: "2024-04-12 13:46:19" -> "2024-04-12T13:46:19"
            const dateA = new Date((a.dataset.date || '').replace(' ', 'T')).getTime();
            const dateB = new Date((b.dataset.date || '').replace(' ', 'T')).getTime();
            
            if (isNaN(dateA) || isNaN(dateB)) return 0;
            return sort === 'Newest Case' ? dateB - dateA : dateA - dateB;
        }

        if (tableBody) {
            desktopRows.sort(sortByDate);
            desktopRows.forEach(row => tableBody.appendChild(row));
        }
        if (cardContainer) {
            mobileCards.sort(sortByDate);
            mobileCards.forEach(card => cardContainer.appendChild(card));
        }

        const filteredDesktop = desktopRows.filter(filterItem);
        const filteredMobile = mobileCards.filter(filterItem);

        return { desktop: filteredDesktop, mobile: filteredMobile };
    }

    function renderPage() {
        const tableBody = document.querySelector('#desktop-table-body');
        const cardContainer = document.querySelector('#mobile-cards-container');
        
        const { desktop: filteredDesktop, mobile: filteredMobile } = getFilteredRows();
        
        const totalItems = Math.max(filteredDesktop.length, filteredMobile.length);
        const totalPages = Math.max(1, Math.ceil(totalItems / ROWS_PER_PAGE));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
        const endIdx = startIdx + ROWS_PER_PAGE;

        const visibleDesktop = new Set(filteredDesktop.slice(startIdx, endIdx));
        const visibleMobile = new Set(filteredMobile.slice(startIdx, endIdx));

        if (tableBody) {
            const allRows = Array.from(tableBody.querySelectorAll('tr.record-row'));
            allRows.forEach(row => row.style.display = visibleDesktop.has(row) ? '' : 'none');
            
            let emptyMsg = document.getElementById('desktop-empty-msg');
            if (filteredDesktop.length === 0 && allRows.length > 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('tr');
                    emptyMsg.id = 'desktop-empty-msg';
                    emptyMsg.innerHTML = `<td colspan="6" class="text-center py-10 text-gray-500">No records match your filters.</td>`;
                    tableBody.appendChild(emptyMsg);
                } else {
                    emptyMsg.style.display = '';
                }
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
        }

        if (cardContainer) {
            const allCards = Array.from(cardContainer.querySelectorAll('.record-card'));
            allCards.forEach(card => card.style.display = visibleMobile.has(card) ? '' : 'none');
            
            let emptyMobileMsg = document.getElementById('mobile-empty-msg');
            if (filteredMobile.length === 0 && allCards.length > 0) {
                if (!emptyMobileMsg) {
                    emptyMobileMsg = document.createElement('div');
                    emptyMobileMsg.id = 'mobile-empty-msg';
                    emptyMobileMsg.className = 'text-center py-10 text-gray-500';
                    emptyMobileMsg.innerHTML = `No records match your filters.`;
                    cardContainer.appendChild(emptyMobileMsg);
                } else {
                    emptyMobileMsg.style.display = '';
                }
            } else if (emptyMobileMsg) {
                emptyMobileMsg.style.display = 'none';
            }
        }

        updatePaginationUI(totalItems, totalPages);
    }

    function updatePaginationUI(totalItems, totalPages) {
        const prevBtns = document.querySelectorAll('#record-prev-btn, #record-prev-btn-mob');
        const nextBtns = document.querySelectorAll('#record-next-btn, #record-next-btn-mob');
        const pageInfos = document.querySelectorAll('#record-page-info, #record-page-info-mob');
        const countInfos = document.querySelectorAll('#record-count-info, #record-count-info-mob');

        const startDisplay = totalItems === 0 ? 0 : (currentPage - 1) * ROWS_PER_PAGE + 1;
        const endDisplay = Math.min(currentPage * ROWS_PER_PAGE, totalItems);

        pageInfos.forEach(el => el.textContent = `Page ${currentPage} of ${totalPages}`);
        countInfos.forEach(el => el.textContent = totalItems === 0 
            ? 'No records' 
            : `Showing ${startDisplay}–${endDisplay} of ${totalItems} records`);

        prevBtns.forEach(btn => {
            btn.disabled = currentPage <= 1;
        });
        nextBtns.forEach(btn => {
            btn.disabled = currentPage >= totalPages;
        });
    }

    function applyFilters() {
        currentPage = 1;
        renderPage();
    }

    // ── Interaction ───────────────────────────────────────────────────────────
    document.addEventListener('input', (e) => {
        if (e.target.id === 'record-search-input') applyFilters();
    });

    document.addEventListener('change', (e) => {
        if (e.target.id === 'record-branch-filter' || e.target.id === 'record-sort-date') applyFilters();
    });

    function init() {
        // Shared pagination event handling
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;

            if (btn.id === 'record-prev-btn' || btn.id === 'record-prev-btn-mob') {
                if (currentPage > 1) {
                    currentPage--;
                    renderPage();
                }
            } else if (btn.id === 'record-next-btn' || btn.id === 'record-next-btn-mob') {
                const { desktop } = getFilteredRows();
                const totalPages = Math.ceil(desktop.length / ROWS_PER_PAGE);
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPage();
                }
            }
        });

        renderPage();
    }

    // Handle initial load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Handle realtime:updated events from global layout (though the script is now outside)
    document.addEventListener('realtime:updated', () => { 
        renderPage(); 
    });
})();
