// ── Filters, Sorting & Pagination ────────────────────────────────────────────
const ROWS_PER_PAGE = 7;
let currentPage = parseInt(sessionStorage.getItem('Citilife_radtechRecordRequests_page')) || 1;

document.addEventListener('input', (e) => {
    if (e.target && (e.target.id === 'search-input' || e.target.id === 'filter-branch' || e.target.id === 'sort-date')) {
        currentPage = 1;
        sessionStorage.setItem('Citilife_radtechRecordRequests_page', 1);
        applyFilters();
    }
});

document.addEventListener('change', (e) => {
    if (e.target && (e.target.id === 'filter-branch' || e.target.id === 'sort-date')) {
        currentPage = 1;
        sessionStorage.setItem('Citilife_radtechRecordRequests_page', 1);
        applyFilters();
    }
});

function applyFilters() {
    const search = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
    const branch = document.getElementById('filter-branch')?.value || 'All Branches';
    const sort = document.getElementById('sort-date')?.value || 'Newest Request';

    const tbody = document.getElementById('table-body');
    if (!tbody) return;

    let rows = Array.from(tbody.querySelectorAll('tr.record-row'));

    // Sort
    if (sort === 'Newest Request' || sort === 'Oldest Request') {
        rows.sort((a, b) => {
            const dateA = new Date(a.dataset.date || 0).getTime();
            const dateB = new Date(b.dataset.date || 0).getTime();
            return sort === 'Newest Request' ? dateB - dateA : dateA - dateB;
        });
        rows.forEach(row => tbody.appendChild(row));
    }

    // Filter
    const matchedRows = rows.filter(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const id = (row.dataset.id || '').toLowerCase();
        const rowBranch = row.dataset.branch || '';

        const matchSearch = !search || name.includes(search) || id.includes(search);
        const matchBranch = branch === 'All Branches' || branch === 'Filter by Branch' || branch === 'All' || branch === rowBranch;

        return matchSearch && matchBranch;
    });

    const totalFiltered = matchedRows.length;
    const totalPages = Math.max(1, Math.ceil(totalFiltered / ROWS_PER_PAGE));

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    sessionStorage.setItem('Citilife_radtechRecordRequests_page', currentPage);

    const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
    const endIdx = startIdx + ROWS_PER_PAGE;

    // Show only the current page slice
    rows.forEach(r => r.style.display = 'none');
    matchedRows.slice(startIdx, endIdx).forEach(r => r.style.display = '');

    // Empty state
    let emptyMsg = document.getElementById('empty-msg-row');
    if (totalFiltered === 0 && rows.length > 0) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('tr');
            emptyMsg.id = 'empty-msg-row';
            emptyMsg.innerHTML = `<td colspan="8" class="text-center py-8 text-gray-500">No requests match your filters.</td>`;
            tbody.appendChild(emptyMsg);
        } else {
            emptyMsg.style.display = '';
        }
    } else if (emptyMsg) {
        emptyMsg.style.display = 'none';
    }

    // Update pagination UI
    updatePaginationUI(totalFiltered, totalPages);
}

function updatePaginationUI(totalFiltered, totalPages) {
    const recordCountInfo = document.getElementById('record-request-count');
    const container = document.getElementById('record-request-pagination-controls');

    const startNum = totalFiltered === 0 ? 0 : (currentPage - 1) * ROWS_PER_PAGE + 1;
    const endNum = Math.min(currentPage * ROWS_PER_PAGE, totalFiltered);

    if (recordCountInfo) {
        recordCountInfo.innerHTML = totalFiltered === 0
            ? 'No records'
            : `Showing <span class="font-semibold text-gray-800">${startNum}</span> to <span class="font-semibold text-gray-800">${endNum}</span> of <span class="font-semibold text-gray-800">${totalFiltered}</span> record${totalFiltered !== 1 ? 's' : ''}`;
    }

    if (!container) return;
    container.innerHTML = '';

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
                applyFilters();
                const card = document.getElementById('record-requests-card');
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
        span.innerText = '...';
        return span;
    }

    // First Button
    container.appendChild(createButton('&laquo; First', 1, currentPage <= 1));

    // Back Button
    container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage <= 1));

    // Numbered page buttons
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) {
            container.appendChild(createButton(i, i, false, i === currentPage));
        }
    } else {
        if (currentPage <= 4) {
            for (let i = 1; i <= 5; i++) {
                container.appendChild(createButton(i, i, false, i === currentPage));
            }
            container.appendChild(createEllipsis());
            container.appendChild(createButton(totalPages, totalPages, false, totalPages === currentPage));
        } else if (currentPage >= totalPages - 3) {
            container.appendChild(createButton(1, 1, false, 1 === currentPage));
            container.appendChild(createEllipsis());
            for (let i = totalPages - 4; i <= totalPages; i++) {
                container.appendChild(createButton(i, i, false, i === currentPage));
            }
        } else {
            container.appendChild(createButton(1, 1, false, 1 === currentPage));
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

// Initial sorting on load
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const sortSelect = document.getElementById('sort-date');
        if (sortSelect && (sortSelect.value === 'Sort by:' || !sortSelect.value)) {
            sortSelect.value = 'Newest Request';
        }
        applyFilters();
    }, 100);
});

// Re-apply filters when real-time polling updates the table content
document.addEventListener('realtime:updated', () => {
    applyFilters();
});

// ── Record Request Modal Logic ──────────────────────────────────────────────
function initRequestModal() {
    const btnSearch = document.getElementById('btn-search-cases');
    const btnBack = document.getElementById('btn-back-search');

    // Step Elements
    const step1 = document.getElementById('step-1-search');
    const step2 = document.getElementById('step-2-details');
    const resultsContainer = document.getElementById('search-results-container');
    const resultsList = document.getElementById('search-results-list');

    // Inputs
    const searchName = document.getElementById('search_patient_name');
    const searchBranch = document.getElementById('search_request_branch');

    btnSearch?.addEventListener('click', async () => {
        const pName = searchName.value.trim();
        const branch = searchBranch.value;

        if (window.FormValidator) {
            const isValid = window.FormValidator.validate('#step-1-search');
            if (!isValid) return;
        } else if (!pName || !branch) {
            toast("Please enter a patient name and select a branch.", "error");
            if (!pName) searchName.focus(); else searchBranch.focus();
            return;
        }

        // Loading state
        const originalHTML = btnSearch.innerHTML;
        btnSearch.disabled = true;
        btnSearch.innerHTML = `<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Search Records`;

        try {
            const res = await fetch(`${window.__APP__.basePath}/app/Api/search_branch_cases.php?patient_name=${encodeURIComponent(pName)}&branch=${encodeURIComponent(branch)}`);
            const data = await res.json();

            btnSearch.disabled = false;
            btnSearch.innerHTML = originalHTML;

            if (data.success) {
                renderResults(data.data, branch);
            } else {
                errorAlert("Search Failed", data.error || "Unknown error");
            }
        } catch (e) {
            btnSearch.disabled = false;
            btnSearch.innerHTML = originalHTML;
            errorAlert("Connection Error", "Could not search records. Please try again later.");
        }
    });

    function renderResults(records, branchName) {
        resultsContainer.classList.remove('hidden');
        resultsList.innerHTML = '';

        if (records.length === 0) {
            resultsList.innerHTML = `<div class="p-4 text-center text-sm text-gray-500 border border-dashed border-gray-300 rounded-lg">No matching records found in ${escapeHtml(branchName)}.</div>`;
            return;
        }

        records.forEach(rn => {
            const div = document.createElement('div');
            div.className = "flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-red-300 hover:bg-red-50 cursor-pointer transition group";

            div.innerHTML = `
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-900 group-hover:text-red-700 transition">${escapeHtml(rn.full_name)}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-medium text-gray-500">Case No: <span class="text-gray-700">${escapeHtml(rn.case_number)}</span></span>
                        <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                        <span class="text-xs font-semibold text-gray-600 truncate flex items-center gap-1"><i data-lucide="file-scan" class="w-3 h-3 text-gray-400"></i> ${escapeHtml(rn.exam_type)}</span>
                        <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                        <span class="text-[10px] font-bold text-gray-400 flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> ${formatDate(rn.created_at)}</span>
                    </div>
                </div>
                <div class="flex-shrink-0 ml-4">
                    <button class="text-xs font-bold text-red-600 bg-red-100 px-3 py-1.5 rounded hover:bg-red-200 transition">Select</button>
                </div>
            `;

            div.addEventListener('click', () => selectRecord(rn, branchName));
            resultsList.appendChild(div);
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function selectRecord(record, branchName) {
        // Hide Step 1, Show Step 2
        step1.classList.add('hidden');
        step2.classList.remove('hidden');

        // Populate Hidden form elements for submission
        document.getElementById('modal_patient_no').value = record.case_number; // The old system uses case_number inside patient_no logic occasionally, but case_number is the tracking ID
        document.getElementById('modal_patient_name').value = record.full_name;
        document.getElementById('modal_exam_type').value = record.exam_type;
        document.getElementById('modal_request_branch').value = branchName;

        // Populate display targets
        document.getElementById('display_selected_name').textContent = record.full_name;
        document.getElementById('display_selected_patientno').textContent = record.patient_number || record.patient_no || 'N/A';
        document.getElementById('display_selected_caseno').textContent = record.case_number;
        document.getElementById('display_selected_exam').textContent = record.exam_type;
        document.getElementById('display_selected_date').textContent = formatDate(record.created_at);
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    btnBack?.addEventListener('click', () => {
        step2.classList.add('hidden');
        step1.classList.remove('hidden');
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

window.resetRequestModal = function () {
    // Reset back to base
    document.getElementById('recordRequestForm')?.reset();
    document.getElementById('step-2-details')?.classList.add('hidden');
    document.getElementById('step-1-search')?.classList.remove('hidden');
    document.getElementById('search-results-container')?.classList.add('hidden');
    document.getElementById('search-results-list').innerHTML = '';
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initRequestModal();
});
