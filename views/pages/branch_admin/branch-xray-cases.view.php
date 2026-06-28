<?php
/**
 * Branch X-ray Cases View (Branch Admin)
 * Backend logic handled by BranchXrayCasesController.php
 */
?>

<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Branch X-ray Cases</h2>
        <p class="text-sm text-gray-500 mt-1">Monitor active patient queue and view completed branch records</p>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="flex">
        <a href="/<?= PROJECT_DIR ?>/index.php?page=branch-xray-cases&tab=queue"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 <?= $currentTab === 'queue' ? 'text-red-600 border-b-2 border-red-600 active-tab' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Active Queue
            <?php if (count($todayQueue) > 0): ?>
                <span
                    class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 border border-red-100">
                    <?= count($todayQueue) ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="/<?= PROJECT_DIR ?>/index.php?page=branch-xray-cases&tab=records"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 <?= $currentTab === 'records' ? 'text-red-600 border-b-2 border-red-600 active-tab' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Patient Records
        </a>
    </nav>
</div>

<!-- Search & Filters -->
<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <input type="text" id="search-input" placeholder="Search patient records (Name or ID)..."
            class="w-80 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">

        <select id="filter-date"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All">All Dates</option>
            <option value="Today" selected>Today's Cases</option>
            <option value="Backlog">Backlogs</option>
        </select>

        <select id="sort-date"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option>Newest Case</option>
            <option>Oldest Case</option>
        </select>
    </div>
</div>

<!-- Table View -->
<div id="xray-cases-table-card" class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-4 py-3.5 whitespace-nowrap">Case No.</th>
                    <th class="text-left font-semibold px-4 py-3.5 whitespace-nowrap">Patient ID</th>
                    <th class="text-left font-semibold px-4 py-3.5">Patient Name</th>
                    <th class="text-left font-semibold px-4 py-3.5">Exam Type</th>
                    <?php if ($currentTab === 'queue'): ?>
                        <th class="text-left font-semibold px-4 py-3.5">Priority</th>
                        <th class="text-left font-semibold px-4 py-3.5">Status</th>
                    <?php endif; ?>
                    <th class="text-left font-semibold px-4 py-3.5">Date</th>
                    <th class="text-left font-semibold px-4 py-3.5 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 divide-y divide-gray-100 realtime-update">
                <?php
                $data = ($currentTab === 'queue') ? $todayQueue : $releasedRecords;
                if (empty($data)):
                    ?>
                    <tr>
                        <td colspan="<?= ($currentTab === 'queue') ? 8 : 6 ?>" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <i data-lucide="<?= ($currentTab === 'queue') ? 'clipboard-list' : 'folder-open' ?>"
                                    class="w-12 h-12 mb-3 opacity-20"></i>
                                <p class="text-base font-medium">No records found</p>
                                <p class="text-sm">There are no
                                    <?= ($currentTab === 'queue') ? "active patients in the queue" : "released patient records" ?>
                                    at the moment.
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <?php $isToday = (date('Y-m-d', strtotime($row['created_at'])) === date('Y-m-d')); ?>
                        <tr class="hover:bg-white/10 transition-colors record-row cursor-pointer"
                            data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"
                            data-case="<?= htmlspecialchars($row['case_number']) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-priority="<?= htmlspecialchars($row['priority'] ?? '') ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>"
                            data-is-today="<?= $isToday ? 'true' : 'false' ?>">
                            <td class="py-3 px-4 font-medium text-gray-900"><?= htmlspecialchars($row['case_number']) ?></td>
                            <td class="py-3 px-4 text-gray-500"><?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?></td>
                            <td class="py-3 px-4">
                                <span
                                    class="font-medium text-gray-800"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <?php
                                $exams = array_filter(array_map('trim', explode(',', $row['exam_type'])));
                                $firstExam = reset($exams);
                                $extraCount = count($exams) - 1;
                                ?>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        <?= htmlspecialchars($firstExam) ?>
                                    </span>
                                    <?php if ($extraCount > 0): ?>
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                            +<?= $extraCount ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php if ($currentTab === 'queue'): ?>
                                <td class="py-3 px-4">
                                    <?php
                                    $pColor = 'blue';
                                    if ($row['priority'] === 'STAT')
                                        $pColor = 'red';
                                    elseif ($row['priority'] === 'Urgent')
                                        $pColor = 'yellow';
                                    elseif ($row['priority'] === 'Priority')
                                        $pColor = 'orange';
                                    ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-<?= $pColor ?>-200 bg-<?= $pColor ?>-50 px-2.5 py-1 text-xs font-semibold text-<?= $pColor ?>-700 shadow-sm">
                                        <?= htmlspecialchars($row['priority']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php
                                    $displayStatus = ($row['approval_status'] === 'Rejected' || $row['status'] === 'Rejected') ? 'Rejected' : $row['status'];
                                    $sColor = 'yellow';
                                    if ($displayStatus === 'Report Ready')
                                        $sColor = 'indigo';
                                    elseif ($displayStatus === 'Under Reading')
                                        $sColor = 'blue';
                                    elseif ($displayStatus === 'Completed')
                                        $sColor = 'green';
                                    elseif ($displayStatus === 'Rejected')
                                        $sColor = 'red';
                                    ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-<?= $sColor ?>-200 bg-<?= $sColor ?>-50 px-2.5 py-1 text-xs font-semibold text-<?= $sColor ?>-700 shadow-sm">
                                        <?= htmlspecialchars($displayStatus ?: 'Pending') ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                            <td class="py-3 px-4 text-gray-500 whitespace-nowrap">
                                <div class="flex flex-col gap-1 items-start">
                                    <span><?= date('M d, Y', strtotime($row['created_at'])) ?> <span class="opacity-70 ml-1"><?= date('h:i A', strtotime($row['created_at'])) ?></span></span>
                                    <?php if ($currentTab === 'queue' && !$isToday): ?>
                                        <span class="inline-block rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 border border-red-200" title="This case was carried over from a previous day">BACKLOG</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if ($currentTab === 'queue'): ?>
                                        <a href="/<?= PROJECT_DIR ?>/index.php?page=patient-details&id=<?= $row['id'] ?>"
                                            class="p-1.5 rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                            title="View Case Detals">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <?php if ($row['status'] === 'Report Ready'): ?>
                                            <a href="javascript:void(0)"
                                                onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this preliminary report?', '/<?= PROJECT_DIR ?>/index.php?page=print-report&id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                                class="p-1.5 rounded-lg border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                                                title="Print Preliminary Report">
                                                <i data-lucide="printer" class="w-4 h-4"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="/<?= PROJECT_DIR ?>/index.php?page=patient-details&id=<?= $row['id'] ?>"
                                            class="p-1.5 rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                            title="View Record Details">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <a href="javascript:void(0)"
                                            onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '/<?= PROJECT_DIR ?>/index.php?page=print-report&id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                            class="p-1.5 rounded-lg border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                                            title="Print Report">
                                            <i data-lucide="printer" class="w-4 h-4"></i>
                                        </a>
                                        <a href="javascript:void(0)"
                                            onclick="confirmAction('Confirm Download', 'Would you like to save this report as PDF?', '/<?= PROJECT_DIR ?>/index.php?page=print-report&id=<?= $row['id'] ?>&download=true', 'Yes, Download', true, event)"
                                            class="p-1.5 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                                            title="Download PDF">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
        <!-- Record count -->
        <span id="xray-record-count" class="text-xs text-gray-500 font-medium"></span>

        <!-- Pagination Controls -->
        <div class="flex items-center flex-wrap gap-1.5" id="xray-pagination-controls">
            <!-- Dynamic page buttons will be inserted here -->
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ROWS_PER_PAGE = 6;
        let currentPage = parseInt(sessionStorage.getItem('CitiLife_branchXray_page_<?= $currentTab ?>')) || 1;

        const searchInput = document.getElementById('search-input');
        const sortDate = document.getElementById('sort-date');
        const filterDate = document.getElementById('filter-date');
        const filterPriority = document.getElementById('filter-priority');
        const tableBody = document.getElementById('table-body');


        function getFilteredRows() {
            const searchTerm = searchInput.value.toLowerCase();
            const priorityFilter = filterPriority ? filterPriority.value : null;
            const sortOrder = sortDate.value;
            const rows = Array.from(tableBody.querySelectorAll('tr.record-row'));

            // Sorting
            rows.sort((a, b) => {
                const dateA = new Date(a.getAttribute('data-date'));
                const dateB = new Date(b.getAttribute('data-date'));
                return sortOrder === 'Newest Case' ? dateB - dateA : dateA - dateB;
            });

            // Re-append sorted rows to DOM (hidden/visible managed by pagination)
            rows.forEach(row => tableBody.appendChild(row));

            // Filtering
            return rows.filter(row => {
                const name = row.getAttribute('data-name').toLowerCase();
                const caseNo = row.getAttribute('data-case').toLowerCase();
                const priority = row.getAttribute('data-priority');
                const isToday = row.getAttribute('data-is-today') === 'true';

                const matchesSearch = name.includes(searchTerm) || caseNo.includes(searchTerm);
                const matchesPriority = priorityFilter ? (priorityFilter === 'All' || priority === priorityFilter) : true;
                
                let matchesDate = true;
                if (filterDate && filterDate.value === 'Today') matchesDate = isToday;
                if (filterDate && filterDate.value === 'Backlog') matchesDate = !isToday;

                return matchesSearch && matchesPriority && matchesDate;
            });
        }

        function renderPaginationControls(totalPages) {
            const container = document.getElementById('xray-pagination-controls');
            if (!container) return;
            container.innerHTML = '';

            // Helper to create a button
            function createButton(label, page, disabled, isActive = false) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.innerHTML = label;
                
                if (isActive) {
                    btn.className = "px-3 py-1.5 rounded-lg bg-black text-xs font-bold text-white shadow-sm border border-black";
                } else {
                    btn.className = "px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed shadow-sm";
                }
                
                if (disabled) {
                    btn.disabled = true;
                } else {
                    btn.onclick = () => {
                        currentPage = page;
                        renderPage();
                        const card = document.getElementById('xray-cases-table-card');
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
            container.appendChild(createButton('&laquo; First', 1, currentPage === 1));

            // Back Button
            container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage === 1));

            // Page numbers
            if (totalPages <= 7) {
                // Show all pages
                for (let i = 1; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i === currentPage));
                }
            } else {
                // We have many pages
                if (currentPage <= 4) {
                    // Near start: 1, 2, 3, 4, 5, ..., T
                    for (let i = 1; i <= 5; i++) {
                        container.appendChild(createButton(i, i, false, i === currentPage));
                    }
                    container.appendChild(createEllipsis());
                    container.appendChild(createButton(totalPages, totalPages, false, totalPages === currentPage));
                } else if (currentPage >= totalPages - 3) {
                    // Near end: 1, ..., T-4, T-3, T-2, T-1, T
                    container.appendChild(createButton(1, 1, false, 1 === currentPage));
                    container.appendChild(createEllipsis());
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        container.appendChild(createButton(i, i, false, i === currentPage));
                    }
                } else {
                    // Middle: 1, ..., C-1, C, C+1, ..., T
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
            container.appendChild(createButton('Next &rsaquo;', currentPage + 1, currentPage === totalPages));

            // Last Button
            container.appendChild(createButton('Last &raquo;', totalPages, currentPage === totalPages));
        }

        function renderPage() {
            const filteredRows = getFilteredRows();
            const rows = Array.from(tableBody.querySelectorAll('tr.record-row'));
            const totalFiltered = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(totalFiltered / ROWS_PER_PAGE));

            // Clamp current page
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            sessionStorage.setItem('CitiLife_branchXray_page_<?= $currentTab ?>', currentPage);

            const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
            const endIdx = startIdx + ROWS_PER_PAGE;

            // Hide all, then show specific page
            rows.forEach(r => r.style.display = 'none');

            const visibleRows = filteredRows.slice(startIdx, endIdx);
            visibleRows.forEach(row => row.style.display = '');

            // Handle empty state
            let emptyState = document.getElementById('filtered-empty-state');
            if (totalFiltered === 0 && rows.length > 0) {
                if (!emptyState) {
                    emptyState = document.createElement('tr');
                    emptyState.id = 'filtered-empty-state';
                    emptyState.innerHTML = `<td colspan="10" class="text-center py-12 text-gray-500">No records match your filters.</td>`;
                    tableBody.appendChild(emptyState);
                }
                emptyState.style.display = '';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }

            // Update Pagination UI
            const recordCountInfo = document.getElementById('xray-record-count');
            const displayStart = totalFiltered === 0 ? 0 : startIdx + 1;
            const displayEnd = Math.min(endIdx, totalFiltered);

            if (recordCountInfo) {
                recordCountInfo.innerHTML = totalFiltered === 0
                    ? 'No records'
                    : `Showing <span class="font-semibold text-gray-800">${displayStart}</span> to <span class="font-semibold text-gray-800">${displayEnd}</span> of <span class="font-semibold text-gray-800">${totalFiltered}</span> records`;
            }

            renderPaginationControls(totalPages);
        }

        function applyFilters() {
            currentPage = 1;
            renderPage();
        }

        // Event Listeners
        searchInput.addEventListener('input', applyFilters);
        if (filterPriority) filterPriority.addEventListener('change', applyFilters);
        sortDate.addEventListener('change', applyFilters);
        if (filterDate) filterDate.addEventListener('change', applyFilters);

        document.addEventListener('realtime:updated', () => {
            renderPage();
        });

        // Initial Render
        renderPage();

        // Refresh Icons
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>