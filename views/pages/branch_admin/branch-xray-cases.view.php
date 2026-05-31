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
        <p class="text-sm text-gray-500 mt-1">Monitor today's patient queue and view completed branch records</p>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="flex">
        <a href="?role=branch_admin&page=branch-xray-cases&tab=queue"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 <?= $currentTab === 'queue' ? 'text-red-600 border-b-2 border-red-600 active-tab' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Today's Queue
            <?php if (count($todayQueue) > 0): ?>
                <span
                    class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 border border-red-100">
                    <?= count($todayQueue) ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="?role=branch_admin&page=branch-xray-cases&tab=records"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 <?= $currentTab === 'records' ? 'text-red-600 border-b-2 border-red-600 active-tab' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Patient Records
        </a>
    </nav>
</div>

<!-- Search & Filters -->
<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <div class="relative flex-1 group" style="position: relative; flex: 1 1 0%;">
            <div style="position: absolute; inset-y: 0; left: 0; padding-left: 1rem; display: flex; align-items: center; pointer-events: none; height: 100%; top: 0;">
                <i data-lucide="search" class="text-gray-400 group-hover:text-red-500 transition-colors" style="width: 1.1rem; height: 1.1rem;"></i>
            </div>
            <input type="text" id="search-input" placeholder="Search by patient name or case number..."
                style="padding-left: 2.75rem !important;"
                class="block w-full pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
        </div>
        <select id="filter-exam"
            class="w-48 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer shadow-sm">
            <option value="All">All Exam Types</option>
            <option>Chest PA</option>
            <option>Abdominal X-ray</option>
            <option>Extremity X-ray</option>
            <option>Skull X-ray</option>
            <option>Lumbar Spine</option>
            <option>Pelvis</option>
        </select>
        <select id="sort-date"
            class="w-48 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer shadow-sm">
            <option>Newest Case</option>
            <option>Oldest Case</option>
        </select>
    </div>
</div>

<!-- Table View -->
<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
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
                                    <?= ($currentTab === 'queue') ? "active patients in today's queue" : "released patient records" ?>
                                    at the moment.
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr class="hover:bg-white/10 transition-colors record-row cursor-pointer" 
                            data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"
                            data-case="<?= htmlspecialchars($row['case_number']) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>">
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
                                    if ($row['priority'] === 'Emergency')
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
                                <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                <span
                                    class="text-[10px] block opacity-70"><?= date('h:i A', strtotime($row['created_at'])) ?></span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if ($currentTab === 'queue'): ?>
                                        <a href="?role=branch_admin&page=patient-details&id=<?= $row['id'] ?>"
                                            class="p-1.5 rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                            title="View Case Detals">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <?php if ($row['status'] === 'Report Ready'): ?>
                                            <a href="javascript:void(0)" 
                                                onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this preliminary report?', 'app/views/pages/radtech/print-report.php?id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                                class="p-1.5 rounded-lg border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                                                title="Print Preliminary Report">
                                                <i data-lucide="printer" class="w-4 h-4"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="?role=branch_admin&page=records-history&id=<?= $row['id'] ?>"
                                            class="p-1.5 rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                            title="View Record Details">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <a href="javascript:void(0)" 
                                            onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', 'app/views/pages/radtech/print-report.php?id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                            class="p-1.5 rounded-lg border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                                            title="Print Report">
                                            <i data-lucide="printer" class="w-4 h-4"></i>
                                        </a>
                                        <a href="javascript:void(0)" 
                                            onclick="confirmAction('Confirm Download', 'Would you like to save this report as PDF?', 'app/views/pages/radtech/print-report.php?id=<?= $row['id'] ?>&download=true', 'Yes, Download', true, event)"
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
    <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">
        <!-- Record count -->
        <span id="xray-record-count" class="text-xs font-medium text-gray-500">
            Showing 0-0 of 0 records
        </span>

        <!-- Prev / Page info / Next -->
        <div class="flex items-center gap-4">
            <button id="xray-prev-btn"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                <i data-lucide="chevron-left" class="w-4 h-4"></i> Previous
            </button>

            <span id="xray-page-info" class="text-xs font-bold text-gray-700 min-w-[80px] text-center">
                Page 1 of 1
            </span>

            <button id="xray-next-btn"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                Next <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ROWS_PER_PAGE = 8;
        let currentPage = 1;

        const searchInput = document.getElementById('search-input');
        const filterExam = document.getElementById('filter-exam');
        const sortDate = document.getElementById('sort-date');
        const tableBody = document.getElementById('table-body');

        const prevBtn = document.getElementById('xray-prev-btn');
        const nextBtn = document.getElementById('xray-next-btn');
        const pageInfo = document.getElementById('xray-page-info');
        const recordCountInfo = document.getElementById('xray-record-count');

        function getFilteredRows() {
            const searchTerm = searchInput.value.toLowerCase();
            const examFilter = filterExam.value;
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
                const exam = row.getAttribute('data-exam');

                const matchesSearch = name.includes(searchTerm) || caseNo.includes(searchTerm);
                const matchesExam = examFilter === 'All' || exam.includes(examFilter);

                return matchesSearch && matchesExam;
            });
        }

        function renderPage() {
            const filteredRows = getFilteredRows();
            const rows = Array.from(tableBody.querySelectorAll('tr.record-row'));
            const totalFiltered = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(totalFiltered / ROWS_PER_PAGE));

            // Clamp current page
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

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
            const displayStart = totalFiltered === 0 ? 0 : startIdx + 1;
            const displayEnd = Math.min(endIdx, totalFiltered);

            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            recordCountInfo.textContent = `Showing ${displayStart}–${displayEnd} of ${totalFiltered} records`;

            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
        }

        function applyFilters() {
            currentPage = 1;
            renderPage();
        }

        // Event Listeners
        searchInput.addEventListener('input', applyFilters);
        filterExam.addEventListener('change', applyFilters);
        sortDate.addEventListener('change', applyFilters);

        document.addEventListener('realtime:updated', () => {
            renderPage();
        });

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage();
            }
        });

        nextBtn.addEventListener('click', () => {
            const filteredRows = getFilteredRows();
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));
            if (currentPage < totalPages) {
                currentPage++;
                renderPage();
            }
        });

        // Initial Render
        renderPage();

        // Refresh Icons
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>