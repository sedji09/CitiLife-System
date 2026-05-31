<?php
require_once __DIR__ . '/../../../config/database.php';

require_once __DIR__ . '/../../../models/BranchModel.php';
require_once __DIR__ . '/../../../models/CaseModel.php';

$branchModel = new \BranchModel($pdo);
$caseModel = new \CaseModel($pdo);

// Fetch all branches
$branchesList = $branchModel->getAllBranches();

// Fetch Completed cases (Standardized via Model)
$records = $caseModel->getWorklist(null, null, 'Completed');
?>

<!-- Header -->
<div class="flex items-center justify-between">
    <div class="ml-5">
        <h2 class="text-2xl font-bold text-gray-900">Patient History Repository</h2>
        <p class="text-sm text-gray-500 mt-1">Access and view previously completed radiological reports</p>
    </div>
</div>

<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <input type="text" id="search-input" placeholder="Search by Case No. or Patient Name..."
            class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">

        <select id="filter-branch"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
            <option value="All">All Branches</option>
            <?php foreach ($branchesList as $branch): ?>
                <option value="<?= htmlspecialchars($branch['name']) ?>">
                    <?= htmlspecialchars($branch['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="sort-date"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
            <option>Sort by:</option>
            <option>Newest Report</option>
            <option>Oldest Report</option>
        </select>
    </div>
</div>

<style>
    /* Medyo dark na grey hover for Laptop/Desktop */
    .record-row {
        transition: all 0.2s ease !important;
    }

    .record-row:hover {
        background-color: #f1f5f9 !important;
    }

    body.theme-dark .record-row:hover {
        background-color: #2d3748 !important;
        /* Slightly lighter than background */
    }
</style>

<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
    <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-5 py-3">Case No.</th>
                    <th class="text-left font-semibold px-5 py-3">Patient Name</th>
                    <th class="text-left font-semibold px-5 py-3">Branch</th>
                    <th class="text-left font-semibold px-5 py-3">Exam Type</th>
                    <th class="text-left font-semibold px-5 py-3">Date Completed</th>
                    <th class="text-left font-semibold px-5 py-3">Action</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white divide-y divide-gray-100 realtime-update">
                <?php if (count($records) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-500">
                            No completed reports found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row): ?>
                        <tr class="transition-colors record-row" data-id="<?= htmlspecialchars($row['case_number']) ?>"
                            data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"
                            data-branch="<?= htmlspecialchars($row['branch_name']) ?>"
                            data-date="<?= $row['date_completed'] ? htmlspecialchars($row['date_completed']) : htmlspecialchars($row['created_at']) ?>">
                            <td class="py-3 px-5">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($row['case_number']) ?></div>
                            </td>
                            <td class="py-3 px-5">
                                <div class="font-medium"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                </div>
                            </td>
                            <td class="py-3 px-5">
                                <span
                                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                                    <?= htmlspecialchars($row['branch_name']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-5">
                                <div class="flex items-center gap-2 max-w-[220px]">
                                    <?php
                                    $exams = explode(',', $row['exam_type']);
                                    $count = count($exams);
                                    ?>
                                    <span class="truncate font-medium text-gray-700"
                                        title="<?= htmlspecialchars($row['exam_type']) ?>">
                                        <?= htmlspecialchars(trim($exams[0])) ?>
                                    </span>
                                    <?php if ($count > 1): ?>
                                        <span class="exam-badge shrink-0 text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                                            +<?= $count - 1 ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-5">
                                <div class="text-sm text-gray-500">
                                    <?= $row['date_completed'] ? date('M d, Y', strtotime($row['date_completed'])) : date('M d, Y', strtotime($row['created_at'])) ?>
                                </div>
                            </td>
                            <td class="py-3 px-5">
                                <a href="/<?= PROJECT_DIR ?>/index.php?role=radiologist&page=patient-records-history&id=<?= $row['id'] ?>"
                                    class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-sm transition">
                                    <i data-lucide="eye" class="w-4 h-4 mr-1"></i> View Report
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination footer -->
    <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3">
        <!-- Record count -->
        <span id="history-record-count" class="text-xs text-gray-500"></span>

        <!-- Prev / Page info / Next -->
        <div class="flex items-center gap-3">
            <button id="history-prev-btn"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed"
                disabled>
                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Previous
            </button>

            <span id="history-page-info" class="text-xs font-medium text-gray-600 min-w-[90px] text-center">Page 1 of
                1</span>

            <button id="history-next-btn"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed"
                disabled>
                Next <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const ROWS_PER_PAGE = 8;
        let currentPage = 1;

        function getFilteredRows() {
            const search = (document.getElementById('search-input')?.value || '').toLowerCase();
            const branch = document.getElementById('filter-branch')?.value || 'All Branches';
            const sort = document.getElementById('sort-date')?.value || 'Sort by:';

            const tbody = document.getElementById('table-body');
            if (!tbody) return [];

            let rows = Array.from(tbody.querySelectorAll('tr.record-row'));

            // Sort
            if (sort === 'Newest Report' || sort === 'Oldest Report') {
                rows.sort((a, b) => {
                    const dateA = new Date(a.dataset.date).getTime();
                    const dateB = new Date(b.dataset.date).getTime();
                    return sort === 'Newest Report' ? dateB - dateA : dateA - dateB;
                });
                rows.forEach(row => tbody.appendChild(row));
            }

            // Filter
            return rows.filter(row => {
                const name = (row.dataset.name || '').toLowerCase();
                const id = (row.dataset.id || '').toLowerCase();
                const rowBranch = row.dataset.branch || '';

                const matchSearch = name.includes(search) || id.includes(search);
                const matchBranch = branch === 'All Branches' || branch === 'All' || branch === rowBranch;

                return matchSearch && matchBranch;
            });
        }

        function renderPage() {
            const tbody = document.getElementById('table-body');
            if (!tbody) return;

            const allRows = Array.from(tbody.querySelectorAll('tr.record-row'));
            const filteredRows = getFilteredRows();
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));

            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
            const endIdx = startIdx + ROWS_PER_PAGE;

            const visibleSet = new Set(filteredRows.slice(startIdx, endIdx));

            allRows.forEach(row => {
                row.style.display = visibleSet.has(row) ? '' : 'none';
            });

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

            updatePaginationUI(filteredRows.length, totalPages);
        }

        function updatePaginationUI(totalFiltered, totalPages) {
            const prevBtn = document.getElementById('history-prev-btn');
            const nextBtn = document.getElementById('history-next-btn');
            const pageInfo = document.getElementById('history-page-info');
            const recordCountInfo = document.getElementById('history-record-count');

            if (!prevBtn || !nextBtn || !pageInfo) return;

            const startIdx = totalFiltered === 0 ? 0 : (currentPage - 1) * ROWS_PER_PAGE + 1;
            const endIdx = Math.min(currentPage * ROWS_PER_PAGE, totalFiltered);

            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;

            if (recordCountInfo) {
                recordCountInfo.textContent = totalFiltered === 0
                    ? 'No records'
                    : `Showing ${startIdx}–${endIdx} of ${totalFiltered} record${totalFiltered !== 1 ? 's' : ''}`;
            }

            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;

            prevBtn.classList.toggle('opacity-40', currentPage <= 1);
            prevBtn.classList.toggle('cursor-not-allowed', currentPage <= 1);
            nextBtn.classList.toggle('opacity-40', currentPage >= totalPages);
            nextBtn.classList.toggle('cursor-not-allowed', currentPage >= totalPages);
        }

        function applyFilters() {
            currentPage = 1;
            renderPage();
        }

        document.addEventListener('input', (e) => {
            if (e.target && e.target.id === 'search-input') applyFilters();
        });

        document.addEventListener('change', (e) => {
            if (e.target && (e.target.id === 'filter-branch' || e.target.id === 'sort-date')) applyFilters();
        });

        // Re-apply pagination after realtime polling replaces tbody innerHTML
        document.addEventListener('realtime:updated', () => {
            renderPage();
        });

        // Pager nav
        document.addEventListener('DOMContentLoaded', () => {
            const prevBtn = document.getElementById('history-prev-btn');
            const nextBtn = document.getElementById('history-next-btn');

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

            const sortSelect = document.getElementById('sort-date');
            if (sortSelect && sortSelect.value === 'Sort by:') {
                sortSelect.value = 'Newest Report';
            }
            renderPage();

            // Highlight row from notification
            setTimeout(() => {
                const params = new window.URLSearchParams(window.location.search);
                const highlightId = params.get('highlight');
                if (highlightId) {
                    const rows = document.querySelectorAll('#table-body tr.record-row');
                    let targetRow = null;
                    // find row index in filtered rows
                    const filteredRows = getFilteredRows();
                    let targetIndex = -1;
                    for (let i = 0; i < filteredRows.length; i++) {
                        if ((filteredRows[i].dataset.id || '').toLowerCase() === highlightId.toLowerCase()) {
                            targetRow = filteredRows[i];
                            targetIndex = i;
                            break;
                        }
                    }

                    if (targetRow && targetIndex !== -1) {
                        // Navigate to the correct page so the row becomes visible
                        const targetPage = Math.floor(targetIndex / ROWS_PER_PAGE) + 1;
                        if (currentPage !== targetPage) {
                            currentPage = targetPage;
                            renderPage();
                        }

                        const tableWrapper = targetRow.closest('.overflow-y-auto');
                        if (tableWrapper) {
                            tableWrapper.scrollTo({ top: targetRow.offsetTop - tableWrapper.offsetTop - 40, behavior: 'smooth' });
                        } else {
                            targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        targetRow.style.transition = 'background-color 0.4s ease';
                        targetRow.style.backgroundColor = '#fef08a';
                        setTimeout(() => {
                            targetRow.style.backgroundColor = '#fde047';
                            setTimeout(() => {
                                targetRow.style.backgroundColor = '#fef08a';
                                setTimeout(() => {
                                    targetRow.style.backgroundColor = '#fde047';
                                    setTimeout(() => {
                                        targetRow.style.transition = 'background-color 1.5s ease';
                                        targetRow.style.backgroundColor = '';
                                    }, 300);
                                }, 300);
                            }, 300);
                        }, 200);
                        const banner = document.createElement('div');
                        banner.innerHTML = '<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Navigated from notification \u2014 Case <strong>' + highlightId + '</strong> is highlighted below.</span></div>';
                        banner.style.cssText = 'margin-top:1rem;padding:0.75rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
                        const headerEl = document.querySelector('h2');
                        if (headerEl && headerEl.parentElement) {
                            headerEl.parentElement.insertAdjacentElement('afterend', banner);
                        }
                        setTimeout(() => {
                            banner.style.transition = 'opacity 0.5s';
                            banner.style.opacity = '0';
                            setTimeout(() => banner.remove(), 500);
                        }, 6000);
                    }
                }
            }, 150);
        });
    })();
</script>