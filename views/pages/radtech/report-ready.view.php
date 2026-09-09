<?php
/**
 * Report Ready (Patient List) View
 * Backend logic handled by ReportReadyController.php
 */
?>
<style>
    html.theme-dark .priority-badge,
    html.theme-dark .status-badge {
        background-color: transparent !important;
    }
</style>


<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient List</h2>
        <p class="text-sm text-gray-500 mt-1">Manage approvals, queue, and ready reports</p>
    </div>
</div>

<?php if ($successMsg): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: <?= json_encode($successMsg) ?>,
                    showConfirmButton: false,
                    timer: 2500,
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl' }
                });
            } else if (typeof toast === 'function') {
                toast(<?= json_encode($successMsg) ?>, 'success');
            }
        });
    </script>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mt-4 rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
        <p class="text-sm text-red-700 font-medium"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<!-- Navigation Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="flex gap-8">
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= ($_GET['page'] ?? 'patient-lists') === 'patient-lists' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-600 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Today's Queue
        </a>
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=report-ready"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= ($_GET['page'] ?? 'patient-lists') === 'report-ready' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Report Ready
        </a>
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-approval"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= ($_GET['page'] ?? 'patient-lists') === 'patient-approval' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Patient Requests
        </a>
    </nav>
</div>

<!-- Content -->
<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <input type="text" id="search-input" placeholder="Search by patient name or case number..."
            class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
        <select id="filter-priority"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All" selected>All</option>
            <option>Routine</option>
            <option>Urgent</option>
            <option>STAT</option>
        </select>
        <select id="sort-date"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option>Sort by:</option>
            <option>Newest Case</option>
            <option>Oldest Case</option>
        </select>
    </div>
</div>


<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden" id="report-ready-card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm ">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Patient No.</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[150px]">Exam Type</th>
                    <th class="text-left font-semibold px-3 py-3">Priority</th>
                    <th class="text-left font-semibold px-3 py-3">Status</th>
                    <th class="text-left font-semibold px-3 py-3 min-w-[100px]">Date</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white divide-y divide-gray-100 realtime-update">
                <?php if (count($patients) === 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">
                            No "Report Ready" cases found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $row):
                        $patFullName = formatFullName($row);
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors record-row"
                            data-id="<?= htmlspecialchars($row['case_number']) ?>"
                            data-case-id="<?= htmlspecialchars($row['id'] ?? '') ?>"
                            data-name="<?= htmlspecialchars($patFullName) ?>"
                            data-priority="<?= htmlspecialchars($row['priority']) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>">
                            <td class="py-3 px-3 font-medium whitespace-nowrap">
                                <?= htmlspecialchars($row['case_number']) ?>
                            </td>
                            <td class="py-3 px-3 font-medium whitespace-nowrap">
                                <?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?>
                            </td>
                            <td class="py-3 px-3 font-medium truncate max-w-[200px]"
                                title="<?= htmlspecialchars($patFullName) ?>">
                                <?= htmlspecialchars($patFullName) ?>
                            </td>
                            <td class="py-3 px-3 max-w-[180px]">
                                <?php
                                $exams = array_filter(array_map('trim', explode(',', $row['exam_type'])));
                                $firstExam = reset($exams);
                                $extraCount = count($exams) - 1;
                                ?>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-medium text-gray-800 truncate max-w-[100px]"
                                        title="<?= htmlspecialchars($row['exam_type']) ?>"><?= htmlspecialchars($firstExam) ?></span>
                                    <?php if ($extraCount > 0): ?>
                                        <span
                                            class="inline-flex items-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-xs font-semibold text-gray-600 cursor-default flex-shrink-0"
                                            title="<?= htmlspecialchars($row['exam_type']) ?>">+<?= $extraCount ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <?php
                                $pBorder = '1.5px solid #60a5fa';
                                $pBg = '#eff6ff';
                                $pColor = '#1d4ed8';
                                if ($row['priority'] === 'STAT') {
                                    $pBorder = '1.5px solid #f87171';
                                    $pBg = '#fef2f2';
                                    $pColor = '#b91c1c';
                                }
                                if ($row['priority'] === 'Urgent') {
                                    $pBorder = '1.5px solid #facc15';
                                    $pBg = '#fefce8';
                                    $pColor = '#a16207';
                                }
                                if ($row['priority'] === 'Priority') {
                                    $pBorder = '1.5px solid #fb923c';
                                    $pBg = '#fff7ed';
                                    $pColor = '#c2410c';
                                }
                                ?>
                                <span
                                    class="priority-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    style="border:<?= $pBorder ?>;background-color:<?= $pBg ?>;color:<?= $pColor ?>">
                                    <?= htmlspecialchars($row['priority']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-1">
                                    <span
                                        class="status-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                        style="border:1.5px solid #818cf8;background-color:#eef2ff;color:#4338ca">
                                        Report Ready
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-gray-500 text-xs whitespace-nowrap">
                                <?= date('M d, Y h:i A', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <!-- View button -->
                                    <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-details&id=<?= $row['id'] ?>&from=report-ready"
                                        class="p-1.5 rounded-md border border-blue-500 bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                        title="View Case">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    <?php if (empty($row['released']) || (int) $row['released'] === 0): ?>
                                        <!-- Re-edit button -->
                                        <button type="button" onclick="triggerReEdit(<?= $row['id'] ?>, this, event)"
                                            class="p-1.5 rounded-md border border-amber-500 bg-amber-100 text-amber-600 hover:bg-amber-600 hover:text-white hover:border-amber-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                            title="Allow Radiologist to Re-edit (Revert to Draft)">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Print Result -->
                                    <a href="javascript:void(0)"
                                        onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?page=print-report&id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                        class="p-1.5 rounded-md border border-green-500 bg-green-100 text-green-600 hover:bg-green-600 hover:text-white hover:border-green-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                        title="Print Report">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </a>

                                    <!-- Release -->
                                    <button type="button" onclick="releaseToPhoto(<?= $row['id'] ?>, this, event)"
                                        class="p-1.5 rounded-md border border-red-500 bg-red-100 text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                        title="Release Result">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4"
        id="report-ready-pagination-container">
        <!-- Record count -->
        <span id="report-ready-count" class="text-xs text-gray-500 font-medium"></span>

        <!-- Pagination Controls -->
        <div class="flex items-center flex-wrap gap-1.5" id="report-ready-pagination-controls">
            <!-- Dynamic page buttons inserted by JS -->
        </div>
    </div>
</div>

<script>
    const ROWS_PER_PAGE = 7;
    let currentPage = parseInt(sessionStorage.getItem('Citilife_reportReady_page')) || 1;

    document.addEventListener('input', (e) => {
        if (e.target && (e.target.id === 'search-input' || e.target.id === 'filter-priority' || e.target.id === 'sort-date')) {
            currentPage = 1;
            sessionStorage.setItem('Citilife_reportReady_page', 1);
            applyFilters();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target && (e.target.id === 'filter-priority' || e.target.id === 'sort-date')) {
            currentPage = 1;
            sessionStorage.setItem('Citilife_reportReady_page', 1);
            applyFilters();
        }
    });

    function applyFilters() {
        const search = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
        const priority = document.getElementById('filter-priority')?.value || 'Filter by Priority';
        const sort = document.getElementById('sort-date')?.value || 'Sort by:';

        const tbody = document.getElementById('table-body');
        if (!tbody) return;

        let rows = Array.from(tbody.querySelectorAll('tr.record-row'));

        // Sort
        if (sort === 'Newest Case' || sort === 'Oldest Case') {
            const priorityMap = { 'STAT': 3, 'Urgent': 2, 'Routine': 1 };

            rows.sort((a, b) => {
                const scoreA = priorityMap[a.dataset.priority] || 0;
                const scoreB = priorityMap[b.dataset.priority] || 0;
                if (scoreA !== scoreB) return scoreB - scoreA;
                const dateA = new Date(a.dataset.date).getTime();
                const dateB = new Date(b.dataset.date).getTime();
                return sort === 'Newest Case' ? dateB - dateA : dateA - dateB;
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        // Filter
        const matchedRows = rows.filter(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const id = (row.dataset.id || '').toLowerCase();
            const rowPriority = row.dataset.priority || '';
            const matchSearch = !search || name.includes(search) || id.includes(search) || rowPriority.toLowerCase().includes(search);
            const matchPriority = priority === 'Filter by Priority' || priority === 'All' || priority === rowPriority;

            return matchSearch && matchPriority;
        });

        const totalFiltered = matchedRows.length;
        const totalPages = Math.max(1, Math.ceil(totalFiltered / ROWS_PER_PAGE));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;
        sessionStorage.setItem('Citilife_reportReady_page', currentPage);

        const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
        const endIdx = startIdx + ROWS_PER_PAGE;

        // Show only current page slice
        rows.forEach(r => r.style.display = 'none');
        matchedRows.slice(startIdx, endIdx).forEach(r => r.style.display = '');

        let emptyMsg = document.getElementById('empty-msg-row');
        if (totalFiltered === 0 && rows.length > 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('tr');
                emptyMsg.id = 'empty-msg-row';
                emptyMsg.innerHTML = `<td colspan="10" class="text-center py-8 text-gray-500">No records match your filters.</td>`;
                tbody.appendChild(emptyMsg);
            } else {
                emptyMsg.style.display = '';
            }
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        updatePaginationUI(totalFiltered, totalPages);
    }

    function updatePaginationUI(totalFiltered, totalPages) {
        const recordCountInfo = document.getElementById('report-ready-count');
        const container = document.getElementById('report-ready-pagination-controls');

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
                    const card = document.getElementById('report-ready-card');
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

        container.appendChild(createButton('&laquo; First', 1, currentPage <= 1));
        container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage <= 1));

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

        container.appendChild(createButton('Next &rsaquo;', currentPage + 1, currentPage >= totalPages));
        container.appendChild(createButton('Last &raquo;', totalPages, currentPage >= totalPages));
    }

    function handleHighlight() {
        const params = new window.URLSearchParams(window.location.search);
        const highlightId = params.get('highlight') || params.get('highlight_case') || params.get('case_id');
        if (!highlightId) return;

        // Reset search/filter to ensure highlighted row is visible
        const searchInput = document.getElementById('search-input');
        const filterPriority = document.getElementById('filter-priority');
        if (searchInput) searchInput.value = '';
        if (filterPriority) filterPriority.value = 'All';

        const rows = Array.from(document.querySelectorAll('#table-body tr.record-row'));
        const hlLower = highlightId.trim().toLowerCase();
        let targetIndex = -1;

        rows.forEach((row, idx) => {
            if ((row.dataset.id || '').toLowerCase() === hlLower ||
                (row.dataset.caseId || '').toLowerCase() === hlLower) {
                targetIndex = idx;
            }
        });

        if (targetIndex !== -1) {
            currentPage = Math.floor(targetIndex / ROWS_PER_PAGE) + 1;
            sessionStorage.setItem('Citilife_reportReady_page', currentPage);
        }

        applyFilters();

        setTimeout(() => {
            let targetRow = null;
            rows.forEach(row => {
                if ((row.dataset.id || '').toLowerCase() === hlLower ||
                    (row.dataset.caseId || '').toLowerCase() === hlLower) {
                    targetRow = row;
                }
            });

            if (targetRow) {
                targetRow.style.display = '';

                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Flash highlight animation
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
                            }, 400);
                        }, 400);
                    }, 400);
                }, 200);

                // Remove existing banner if present
                const existingBanner = document.getElementById('highlight-banner');
                if (existingBanner) existingBanner.remove();

                // Info banner
                const banner = document.createElement('div');
                banner.id = 'highlight-banner';
                banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Case <strong>${highlightId}</strong> is highlighted below.</span></div>`;
                banner.style.cssText = 'margin-left:auto;padding:0.6rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
                const header = document.querySelector('h2');
                if (header && header.parentElement) {
                    header.parentElement.insertAdjacentElement('afterend', banner);
                }
                setTimeout(() => {
                    banner.style.transition = 'opacity 0.5s';
                    banner.style.opacity = '0';
                    setTimeout(() => banner.remove(), 500);
                }, 6000);

                // Clean up URL parameter cleanly
                try {
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete('highlight');
                    cleanUrl.searchParams.delete('highlight_case');
                    cleanUrl.searchParams.delete('case_id');
                    window.history.replaceState({}, document.title, cleanUrl.toString());
                } catch (e) { }
            }
        }, 150);
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            const sortSelect = document.getElementById('sort-date');
            if (sortSelect && (sortSelect.value === 'Sort by:' || !sortSelect.value)) {
                sortSelect.value = 'Newest Case';
            }
            applyFilters();
            handleHighlight();
        }, 100);
    });

    document.addEventListener('realtime:updated', () => {
        applyFilters();
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<div id="release-loading-overlay"
    class="fixed inset-0 z-[9999] bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm flex flex-col items-center justify-center hidden">
    <div id="release-spinner-container">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-red-600 border-t-transparent mb-4"></div>
    </div>
    <div id="release-success-icon" class="hidden mb-4">
        <div
            class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center shadow-lg shadow-green-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 stroke-[3]" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
    </div>
    <h3 id="release-title-text" class="text-xl font-bold text-gray-800 dark:text-white">Releasing Result</h3>
    <p id="release-status-text" class="text-gray-500 dark:text-gray-400 mt-2 text-center font-medium">Preparing the
        results...</p>
</div>

<script>
    async function triggerReEdit(caseId, btn, event = null) {
        if (event) event.preventDefault();

        let reason = '';
        if (typeof Swal !== 'undefined') {
            const { value: formValues, isConfirmed } = await Swal.fire({
                title: 'Allow Radiologist to Re-edit?',
                html: `
                    <div class="text-left text-sm text-gray-600 mb-3">
                        This will revert the report to draft status (Under Reading) so the radiologist can update findings and impressions.
                    </div>
                    <div class="text-left mb-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Notes <span class="text-red-500 font-bold">*</span>:
                        </label>
                        <textarea id="swal-reedit-reason" rows="3" class="w-full text-sm border border-gray-300 rounded-xl p-3 transition resize-none font-sans" style="outline: none !important; box-shadow: none !important;" placeholder="Enter notes for the radiologist on what needs to be changed..."></textarea>
                        <p id="swal-reedit-warn" class="hidden text-xs text-red-500 mt-1">Please provide a note for the radiologist.</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Allow Re-edit',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'rounded-2xl p-5 max-w-lg font-sans',
                    confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold shadow-sm',
                    cancelButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold'
                },
                didOpen: () => {
                    const ta = document.getElementById('swal-reedit-reason');
                    const warn = document.getElementById('swal-reedit-warn');
                    if (!ta) return;
                    ta.focus();
                    ta.addEventListener('focus', () => {
                        ta.style.borderColor = '#d97706';
                        if (warn) warn.classList.add('hidden');
                    });
                    ta.addEventListener('blur', () => {
                        if (!ta.value.trim()) {
                            ta.style.borderColor = '#ef4444';
                            if (warn) warn.classList.remove('hidden');
                        } else {
                            ta.style.borderColor = '#d1d5db';
                        }
                    });
                    ta.addEventListener('input', () => {
                        if (ta.value.trim()) {
                            ta.style.borderColor = '#d97706';
                            if (warn) warn.classList.add('hidden');
                        }
                    });
                },
                preConfirm: () => {
                    const ta = document.getElementById('swal-reedit-reason');
                    const warn = document.getElementById('swal-reedit-warn');
                    if (ta && !ta.value.trim()) {
                        ta.style.borderColor = '#ef4444';
                        if (warn) warn.classList.remove('hidden');
                        return false;
                    }
                    return ta ? ta.value.trim() : '';
                }
            });

            if (!isConfirmed) return;
            reason = formValues || '';
        } else {
            const promptResult = prompt('Allow Radiologist to Re-edit? Add a note (optional):');
            if (promptResult === null) return;
            reason = promptResult.trim();
        }

        const originalHTML = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }

        try {
            const baseDir = '<?= defined("PROJECT_DIR") && PROJECT_DIR ? "/" . PROJECT_DIR : "" ?>';
            const res = await fetch(`${baseDir}/radtech/re-edit-case`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `case_id=${encodeURIComponent(caseId)}&reason=${encodeURIComponent(reason)}`
            });

            const data = await res.json();
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Reverted to Draft',
                        text: data.message || 'Report reverted to draft successfully.',
                        timer: 1800,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl' }
                    });
                } else {
                    alert(data.message || 'Report reverted to draft successfully.');
                }
                window.location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Action Failed',
                        text: data.message || 'Could not revert report.',
                        customClass: { popup: 'rounded-2xl' }
                    });
                } else {
                    alert(data.message || 'Could not revert report.');
                }
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btn.innerHTML = originalHTML;
                }
            }
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'A network error occurred. Please try again.',
                    customClass: { popup: 'rounded-2xl' }
                });
            } else {
                alert('A network error occurred. Please try again.');
            }
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.innerHTML = originalHTML;
            }
        }
    }

    async function releaseToPhoto(caseId, btn, event = null) {
        if (event) event.preventDefault();
        const confirmed = await confirmAlert('Confirm Release', 'Would you like to confirm releasing this result and moving it to X-ray Patient Records?');
        if (!confirmed.isConfirmed) return;

        const baseDir = '<?= (defined("PROJECT_DIR") && PROJECT_DIR) ? "/" . PROJECT_DIR : "" ?>';
        const overlay = document.getElementById('release-loading-overlay');
        const statusText = document.getElementById('release-status-text');

        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        if (overlay) overlay.classList.remove('hidden');
        if (statusText) statusText.textContent = 'Initializing report snapshot...';

        try {
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.top = '-10000px';
            iframe.style.left = '-10000px';
            iframe.style.width = '814px';
            iframe.style.height = '1200px';
            iframe.style.border = 'none';

            iframe.src = `${baseDir}/print-report?id=${caseId}&no_shadow=1&snapshot=1`;
            document.body.appendChild(iframe);

            iframe.onload = async () => {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    await new Promise(r => setTimeout(r, 1000));
                    const pages = doc.querySelectorAll('.report-page');
                    if (!pages.length) throw new Error("No pages found to render.");
                    iframe.style.height = (doc.documentElement.scrollHeight + 200) + 'px';
                    await new Promise(r => setTimeout(r, 500));

                    let base64Images = [];
                    for (let i = 0; i < pages.length; i++) {
                        const page = pages[i];
                        if (statusText) statusText.textContent = `Processing page ${i + 1} of ${pages.length}...`;

                        const canvas = await html2canvas(page, {
                            scale: pages.length > 5 ? 1.5 : 2,
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            width: page.scrollWidth,
                            height: page.scrollHeight,
                            windowWidth: doc.documentElement.scrollWidth,
                            windowHeight: doc.documentElement.scrollHeight,
                            onclone: (clonedDoc) => {
                                clonedDoc.querySelectorAll('.page').forEach(el => {
                                    el.style.margin = '0';
                                    el.style.boxShadow = 'none';
                                    el.style.border = 'none';
                                    el.style.background = '#fff';
                                });
                            }
                        });
                        const imgData = canvas.toDataURL('image/jpeg', pages.length > 5 ? 0.8 : 0.9);
                        base64Images.push(imgData);
                    }

                    if (statusText) statusText.textContent = 'Uploading consolidated report...';

                    const formData = new FormData();
                    formData.append('id', caseId);
                    formData.append('images', JSON.stringify(base64Images));

                    const response = await fetch(`${baseDir}/report-ready?action=release_and_upload`, {
                        method: 'POST',
                        body: formData
                    });

                    const resText = await response.text();
                    let result;
                    try {
                        result = JSON.parse(resText);
                    } catch (jsonErr) {
                        console.error("Server response was not valid JSON:", resText);
                        throw new Error('Server returned invalid response format: ' + (resText ? resText.substring(0, 100) : 'Empty response'));
                    }

                    if (result.success) {
                        const spinner = document.getElementById('release-spinner-container');
                        const successIcon = document.getElementById('release-success-icon');
                        const titleEl = document.getElementById('release-title-text');
                        const statusTextEl = document.getElementById('release-status-text');

                        if (spinner) spinner.classList.add('hidden');
                        if (successIcon) successIcon.classList.remove('hidden');
                        if (titleEl) {
                            titleEl.textContent = 'Result Released!';
                            titleEl.className = 'text-xl font-bold text-green-600 dark:text-green-400';
                        }
                        if (statusTextEl) {
                            statusTextEl.textContent = 'Case moved to X-ray Patient Records.';
                            statusTextEl.className = 'text-gray-600 dark:text-gray-300 mt-2 text-center font-medium';
                        }

                        await new Promise(r => setTimeout(r, 1200));
                        window.location.reload();
                    } else {
                        throw new Error(result.message || 'Server rejected the upload.');
                    }
                } catch (err) {
                    console.error(err);
                    errorAlert('Generation Failed', err.message);
                    if (overlay) overlay.classList.add('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                } finally {
                    iframe.remove();
                }
            };
        } catch (e) {
            console.error(e);
            errorAlert('Error', 'An unexpected error occurred during processing.');
            if (overlay) overlay.classList.add('hidden');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
    try {
        sessionStorage.setItem('radtech_last_table_url', window.location.href);
    } catch (e) { }
</script>