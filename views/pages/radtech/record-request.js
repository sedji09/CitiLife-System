// ── Filters & Sorting ────────────────────────────────────────────────────────
document.addEventListener('input', (e) => {
    if (e.target && (e.target.id === 'search-input' || e.target.id === 'filter-branch' || e.target.id === 'sort-date')) {
        applyFilters();
    }
});

document.addEventListener('change', (e) => {
    if (e.target && (e.target.id === 'filter-branch' || e.target.id === 'sort-date')) {
        applyFilters();
    }
});

function applyFilters() {
    const search = (document.getElementById('search-input')?.value || '').toLowerCase();
    const branch = document.getElementById('filter-branch')?.value || 'Filter by Branch';
    const sort = document.getElementById('sort-date')?.value || 'Sort by:';

    const tbody = document.getElementById('table-body');
    if (!tbody) return;

    let rows = Array.from(tbody.querySelectorAll('tr.record-row'));
    let visibleCount = 0;

    // Sort
    if (sort === 'Newest Request' || sort === 'Oldest Request') {
        rows.sort((a, b) => {
            const dateA = new Date(a.dataset.date).getTime();
            const dateB = new Date(b.dataset.date).getTime();
            return sort === 'Newest Request' ? dateB - dateA : dateA - dateB;
        });
        rows.forEach(row => tbody.appendChild(row));
    }

    // Filter
    rows.forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const id = (row.dataset.id || '').toLowerCase();
        const rowBranch = row.dataset.branch || '';

        const matchSearch = name.includes(search) || id.includes(search);
        const matchBranch = branch === 'Filter by Branch' || branch === 'All' || branch === rowBranch;

        row.style.display = (matchSearch && matchBranch) ? '' : 'none';
        if (matchSearch && matchBranch) visibleCount++;
    });

    let emptyMsg = document.getElementById('empty-msg-row');
    if (visibleCount === 0 && rows.length > 0) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('tr');
            emptyMsg.id = 'empty-msg-row';
            emptyMsg.innerHTML = `<td colspan="10" class="text-center py-8 text-gray-500">No requests match your filters.</td>`;
            tbody.appendChild(emptyMsg);
        } else {
            emptyMsg.style.display = '';
        }
    } else if (emptyMsg) {
        emptyMsg.style.display = 'none';
    }
}

// Initial sorting on load
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const sortSelect = document.getElementById('sort-date');
        if (sortSelect && sortSelect.value === 'Sort by:') {
            sortSelect.value = 'Newest Request';
            applyFilters();
        }
    }, 100);
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

        if (!pName || !branch) {
            toast("Please enter a patient name and select a branch.", "error");
            return;
        }

        // Loading state
        const originalHTML = btnSearch.innerHTML;
        btnSearch.disabled = true;
        btnSearch.innerHTML = `<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Searching...`;

        try {
            const res = await fetch(`${window.__APP__.basePath}/app/api/search_branch_cases.php?patient_name=${encodeURIComponent(pName)}&branch=${encodeURIComponent(branch)}`);
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
