<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';
$disputeModel = new \ResultDisputeModel($pdo);
$branchId = $_SESSION['branch_id'] ?? 1;
$disputes = $disputeModel->getDisputesForClinic($branchId, 'radtech');
$pendingDisputeCount = count(array_filter($disputes, function($d) { 
    return in_array($d['status'], ['Pending RadTech Review', 'Pending RadTech Verification']); 
}));
$currentTab = $_GET['tab'] ?? 'completed';

/**
 * Patient Queue (Patient List) View
 * Backend logic handled by PatientListsController.php
 */
?>
<style>
    html.theme-dark .priority-badge,
    html.theme-dark .status-badge {
        background-color: transparent !important;
    }
    div:where(.swal2-container),
    .swal2-container {
        z-index: 999999 !important;
    }
    .custom-gray-scroll {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    .custom-gray-scroll::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-gray-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-gray-scroll::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 9999px;
    }
    .custom-gray-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
    }
</style>


<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient List</h2>
        <p class="text-sm text-gray-500 mt-1">Manage approvals and active examination queue</p>
    </div>
</div>

<?php if ($successMsg): ?>
    <div id="flash-success-alert"
        class="mt-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 flex items-center gap-3 shadow-sm transition-all">
        <i data-lucide="check-circle-2" class="w-5 h-5 text-green-500 shrink-0"></i>
        <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($successMsg) ?></p>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('flash-success-alert');
            if (el) {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.5s ease';
                setTimeout(() => el.remove(), 500);
            }
        }, 5000);
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
    <nav class="flex gap-3">
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= (($_GET['page'] ?? 'patient-lists') === 'patient-lists' && $currentTab !== 'disputes') ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-600 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Patient Queue
        </a>
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-approval"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= ($_GET['page'] ?? 'patient-lists') === 'patient-approval' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Pending Approval
        </a>
    
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists&tab=disputes"
           class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= $currentTab === 'disputes' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Patient Error Reports
            <?php if ($pendingDisputeCount > 0): ?>
                <span id="radtech-disputes-tab-badge" class="ml-1 tab-circle-badge bg-red-100 text-red-700 border border-red-200" style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;" title="<?= $pendingDisputeCount ?>">
                    <?= $pendingDisputeCount > 99 ? '99+' : $pendingDisputeCount ?>
                </span>
            <?php endif; ?>
        </a>
</nav>
</div>

<!-- Content -->
<div class="mt-6 flex flex-col gap-4 <?= $currentTab === 'disputes' ? 'hidden' : '' ?>">
    <div class="flex gap-4 items-center">
        <?php $defaultSearch = $_GET['search'] ?? ''; ?>
        <input type="text" id="search-input" placeholder="Search by patient name or case number..." value="<?= htmlspecialchars($defaultSearch) ?>"
            class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
        <?php $defaultPriorityFilter = $_GET['filterPriority'] ?? 'All'; ?>
        <select id="filter-priority"
            class="w-40 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All" <?= $defaultPriorityFilter === 'All' ? 'selected' : '' ?>>All Priorities</option>
            <option <?= $defaultPriorityFilter === 'Routine' ? 'selected' : '' ?>>Routine</option>
            <option <?= $defaultPriorityFilter === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
            <option <?= $defaultPriorityFilter === 'STAT' ? 'selected' : '' ?>>STAT</option>
        </select>
        <?php $defaultDateFilter = $_GET['filterDate'] ?? (isset($_GET['highlight']) ? 'All' : 'Today'); ?>
        <select id="filter-date"
            class="w-40 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All" <?= $defaultDateFilter === 'All' ? 'selected' : '' ?>>All Dates</option>
            <option value="Today" <?= $defaultDateFilter === 'Today' ? 'selected' : '' ?>>Today's Cases</option>
            <option value="Backlog" <?= $defaultDateFilter === 'Backlog' ? 'selected' : '' ?>>Backlogs</option>
        </select>
        <select id="sort-date"
            class="w-40 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option>Newest Case</option>
            <option>Oldest Case</option>
        </select>
    </div>
</div>


<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden <?= $currentTab === 'disputes' ? 'hidden' : '' ?>">
    <div class="overflow-x-auto">
        <table class="w-full text-sm ">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Patient No.</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[150px]">Exam Type</th>
                    <th class="text-left font-semibold px-3 py-3">Priority</th>
                    <th class="text-left font-semibold px-3 py-3">Image</th>
                    <th class="text-left font-semibold px-3 py-3">Status</th>
                    <th class="text-left font-semibold px-3 py-3 min-w-[100px]">Date</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white divide-y divide-gray-100 realtime-update">
                <?php if (count($patients) === 0): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-500">
                            No active patients found in the Queue.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $row): ?>
                        <?php
                        $isReportReady = ($row['status'] === 'Report Ready');
                        $isToday = (date('Y-m-d', strtotime($row['created_at'])) === date('Y-m-d'));
                        
                        $displayStatus = ($row['approval_status'] === 'Rejected' || $row['status'] === 'Rejected') ? 'Rejected' : $row['status'];
                        $isOverdue = (time() - strtotime($row['created_at'])) >= 3 * 3600;
                        if ($displayStatus === 'Pending' && $isOverdue) {
                            $displayStatus = 'Overdue';
                        }

                        $initialDisplay = '';
                        if ($defaultDateFilter === 'Today' && !$isToday) $initialDisplay = 'display: none;';
                        if ($defaultDateFilter === 'Backlog' && $isToday) $initialDisplay = 'display: none;';
                        if ($defaultPriorityFilter !== 'All' && $defaultPriorityFilter !== $row['priority']) $initialDisplay = 'display: none;';
                        
                        $sLower = strtolower($defaultSearch);
                        if ($sLower !== '') {
                            $nMatch = strpos(strtolower($row['first_name'] . ' ' . $row['last_name']), $sLower) !== false;
                            $cMatch = strpos(strtolower($row['case_number']), $sLower) !== false;
                            $pMatch = strpos(strtolower($row['patient_number'] ?? ''), $sLower) !== false;
                            if (!$nMatch && !$cMatch && !$pMatch) {
                                $initialDisplay = 'display: none;';
                            }
                        }
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors record-row"
                            style="<?= $initialDisplay ?>"
                            data-id="<?= htmlspecialchars($row['case_number']) ?>"
                            data-patient="<?= htmlspecialchars($row['patient_number'] ?? '') ?>"
                            data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"
                            data-priority="<?= htmlspecialchars($row['priority']) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>"
                            data-status="<?= htmlspecialchars($displayStatus ?: 'Pending') ?>"
                            data-is-today="<?= $isToday ? 'true' : 'false' ?>">
                            <td class="py-3 px-3 font-medium whitespace-nowrap"><?= htmlspecialchars($row['case_number']) ?>
                            </td>
                            <td class="py-3 px-3 font-medium whitespace-nowrap">
                                <?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?>
                            </td>
                            <td class="py-3 px-3 font-medium truncate max-w-[200px]"
                                title="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>">
                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
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
                            <td class="py-3 px-3 text-gray-500">
                                <?php if ($row['image_status'] === 'Uploaded'): ?>
                                    <div class="flex items-center gap-1 text-green-500">
                                        <i data-lucide="check" class="w-4 h-4"></i> Uploaded
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3">
                                <?php
                                // $displayStatus logic moved up to row attributes for searchability
                                $sBorder = '1.5px solid #facc15';
                                $sBg = '#fefce8';
                                $sColor = '#a16207';
                                if ($displayStatus === 'Report Ready') {
                                    $sBorder = '1.5px solid #818cf8';
                                    $sBg = '#eef2ff';
                                    $sColor = '#4338ca';
                                }
                                if ($displayStatus === 'Under Reading') {
                                    $sBorder = '1.5px solid #60a5fa';
                                    $sBg = '#eff6ff';
                                    $sColor = '#1d4ed8';
                                }
                                if ($displayStatus === 'Completed') {
                                    $sBorder = '1.5px solid #4ade80';
                                    $sBg = '#f0fdf4';
                                    $sColor = '#15803d';
                                }
                                if ($displayStatus === 'Rejected' || $displayStatus === 'Overdue') {
                                    $sBorder = '1.5px solid #f87171';
                                    $sBg = '#fef2f2';
                                    $sColor = '#b91c1c';
                                }
                                ?>
                                <span
                                    class="status-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    style="border:<?= $sBorder ?>;background-color:<?= $sBg ?>;color:<?= $sColor ?>">
                                    <?= htmlspecialchars($displayStatus ?: 'Pending') ?>
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-500 text-xs whitespace-nowrap">
                                <div class="flex flex-col gap-1 items-start">
                                    <span><?= date('M d, Y', strtotime($row['created_at'])) ?> <br> <span
                                            class="opacity-70"><?= date('h:i A', strtotime($row['created_at'])) ?></span></span>
                                    <?php if (!$isToday): ?>
                                        <span
                                            class="inline-block rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 border border-red-200"
                                            title="This case was carried over from a previous day">BACKLOG</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <!-- View button always active -->
                                    <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-details&id=<?= $row['id'] ?>"
                                        class="text-sm font-medium text-blue-500 hover:text-blue-700 transition"
                                        title="View Case">
                                        <i data-lucide="eye"
                                            class="w-6 h-6 mr-1 bg-blue-100 px-1 py-1 rounded-md border border-blue-500"></i>
                                    </a>

                                    <?php if ($isReportReady): ?>
                                        <!-- Print Result — active when Report Ready -->
                                        <a href="javascript:void(0)"
                                            onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?page=print-report&id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                            class="text-green-500 hover:text-green-700 transition" title="Print Report">
                                            <i data-lucide="printer"
                                                class="w-6 h-6 mr-1 bg-green-100 px-1 py-1 rounded-md border border-green-500"></i>
                                        </a>

                                        <!-- Release — active when Report Ready -->
                                        <button type="button" onclick="releaseToPhoto(<?= $row['id'] ?>, this, event)"
                                            class="text-sm font-medium text-red-500 hover:text-red-700 transition"
                                            title="Release Result">
                                            <i data-lucide="send"
                                                class="w-6 h-6 mr-1 bg-red-100 px-1 py-1 rounded-md border border-red-500"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Print — disabled -->
                                        <button class="text-gray-400 cursor-not-allowed"
                                            title="Print Report (Disabled until Radiologist submits report)" disabled>
                                            <i data-lucide="printer"
                                                class="w-6 h-6 mr-1 bg-gray-100 px-1 py-1 rounded-md border border-gray-300"></i>
                                        </button>

                                        <!-- Release — disabled -->
                                        <span class="text-gray-400 cursor-not-allowed"
                                            title="Release (Disabled until Radiologist submits report)">
                                            <i data-lucide="send"
                                                class="w-6 h-6 mr-1 bg-gray-100 px-1 py-1 rounded-md border border-gray-300"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Controls for Main Table -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4" id="main-pagination-container" style="display: flex;">
        <span id="main-record-count" class="text-xs text-gray-500 font-medium">
            Showing <span id="main-start">0</span> to <span id="main-end">0</span> of <span id="main-total" class="font-semibold text-gray-800">0</span> records
        </span>
        <div class="flex items-center flex-wrap gap-1.5" id="main-pagination-controls">
        </div>
    </div>
</div>

<script>
    function getQueueInputs() {
        return {
            searchInput: document.getElementById('search-input'),
            filterPriority: document.getElementById('filter-priority'),
            filterDate: document.getElementById('filter-date'),
            sortDate: document.getElementById('sort-date'),
            tbody: document.getElementById('table-body')
        };
    }

    function saveQueueState() {
        const { searchInput, filterPriority, filterDate, sortDate } = getQueueInputs();
        if (searchInput) sessionStorage.setItem('Citilife_radtechQueue_search', searchInput.value);
        if (filterPriority) sessionStorage.setItem('Citilife_radtechQueue_priority', filterPriority.value);
        if (filterDate) sessionStorage.setItem('Citilife_radtechQueue_date', filterDate.value);
        if (sortDate) sessionStorage.setItem('Citilife_radtechQueue_sort', sortDate.value);
        sessionStorage.setItem('Citilife_radtechQueue_page', currentMainPage);
    }

    function restoreFiltersFromSession() {
        const { searchInput, filterPriority, filterDate, sortDate } = getQueueInputs();
        const urlParams = new window.URLSearchParams(window.location.search);

        // Priority filter
        if (urlParams.has('filterPriority')) {
            if (filterPriority) filterPriority.value = urlParams.get('filterPriority');
        } else if (filterPriority) {
            const savedPriority = sessionStorage.getItem('Citilife_radtechQueue_priority');
            if (savedPriority) filterPriority.value = savedPriority;
        }

        // Date filter
        if (urlParams.has('filterDate')) {
            if (filterDate) filterDate.value = urlParams.get('filterDate');
        } else if (urlParams.has('highlight')) {
            if (filterDate) filterDate.value = 'All';
        } else if (filterDate) {
            const savedDate = sessionStorage.getItem('Citilife_radtechQueue_date');
            if (savedDate) filterDate.value = savedDate;
        }

        // Search input
        if (urlParams.has('search')) {
            if (searchInput) searchInput.value = urlParams.get('search');
        } else if (searchInput) {
            const savedSearch = sessionStorage.getItem('Citilife_radtechQueue_search');
            if (savedSearch !== null) searchInput.value = savedSearch;
        }

        // Sort date
        if (sortDate) {
            const savedSort = sessionStorage.getItem('Citilife_radtechQueue_sort');
            if (savedSort) sortDate.value = savedSort;
        }

        // Page
        if (!urlParams.has('highlight')) {
            const savedPage = parseInt(sessionStorage.getItem('Citilife_radtechQueue_page'));
            if (savedPage && savedPage > 0) {
                currentMainPage = savedPage;
            }
        }
    }

    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'search-input') {
            currentMainPage = 1;
            saveQueueState();
            applyFilters();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target && (e.target.id === 'filter-priority' || e.target.id === 'filter-date' || e.target.id === 'sort-date')) {
            currentMainPage = 1;
            saveQueueState();
            applyFilters();
        }
    });

    function applyFilters(targetTbody = null) {
        const { searchInput, filterPriority, filterDate, sortDate, tbody: defaultTbody } = getQueueInputs();
        const tbody = targetTbody || defaultTbody;
        if (!tbody) return;

        const search = (searchInput?.value || '').toLowerCase().trim();
        const priority = filterPriority?.value || '<?= htmlspecialchars($defaultPriorityFilter) ?>';
        const dateFilter = filterDate?.value || '<?= htmlspecialchars($defaultDateFilter) ?>';
        const sort = sortDate?.value || 'Newest Case';

        let rows = Array.from(tbody.querySelectorAll('tr.record-row'));
        let visibleCount = 0;

        // Sort
        if (sort === 'Newest Case' || sort === 'Oldest Case') {
            rows.sort((a, b) => {
                // Priority Weight: STAT > Urgent/Priority > Routine
                const getPriorityWeight = (prio) => {
                    const p = (prio || '').toUpperCase();
                    if (p === 'STAT') return 3;
                    if (p === 'URGENT' || p === 'PRIORITY') return 2;
                    return 1;
                };

                const weightA = getPriorityWeight(a.dataset.priority);
                const weightB = getPriorityWeight(b.dataset.priority);

                // Sort by priority first (highest weight first)
                if (weightA !== weightB) {
                    return weightB - weightA;
                }

                // If same priority, sort by date
                const dateA = new Date(a.dataset.date).getTime();
                const dateB = new Date(b.dataset.date).getTime();
                return sort === 'Newest Case' ? dateB - dateA : dateA - dateB;
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        // Filter
        let matchedRows = [];
        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const id = (row.dataset.id || '').toLowerCase();
            const patient = (row.dataset.patient || '').toLowerCase();
            const rowPriority = row.dataset.priority || '';
            const rowStatus = (row.dataset.status || '').toLowerCase();
            const isToday = row.dataset.isToday === 'true';

            const matchSearch = !search || name.includes(search) || id.includes(search) || patient.includes(search) || rowPriority.toLowerCase().includes(search) || rowStatus.includes(search);
            const matchPriority = priority === 'Filter by Priority' || priority === 'All' || priority === rowPriority;

            let matchDate = true;
            if (dateFilter === 'Today') matchDate = isToday;
            if (dateFilter === 'Backlog') matchDate = !isToday;

            if (matchSearch && matchPriority && matchDate) {
                matchedRows.push(row);
                row.setAttribute('data-matched', 'true');
                visibleCount++;
            } else {
                row.style.display = 'none';
                row.removeAttribute('data-matched');
            }
        });

        let emptyMsg = document.getElementById('empty-msg-row');
        if (visibleCount === 0 && rows.length > 0) {
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
        
        paginateMain(matchedRows, tbody);
    }

    // Main Pagination Logic
    let currentMainPage = 1;
    const mainItemsPerPage = 7;
    
    function paginateMain(matchedRows, targetTbody = null) {
        const { tbody: defaultTbody } = getQueueInputs();
        const tbody = targetTbody || defaultTbody;
        if (!tbody) return;

        const totalRecords = matchedRows.length;
        let totalPages = Math.ceil(totalRecords / mainItemsPerPage);
        
        if (currentMainPage > totalPages && totalPages > 0) currentMainPage = totalPages;
        if (totalPages === 0) currentMainPage = 1;

        const start = (currentMainPage - 1) * mainItemsPerPage;
        const end = Math.min(start + mainItemsPerPage, totalRecords);

        // Hide all matched rows first, then only show the ones in the current page
        matchedRows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });

        renderMainPaginationControls(totalPages, totalRecords, start, end);
    }

    function renderMainPaginationControls(totalPages, totalRecords, startIdx, endIdx) {
        const container = document.getElementById('main-pagination-container');
        const controls = document.getElementById('main-pagination-controls');
        const startSpan = document.getElementById('main-start');
        const endSpan = document.getElementById('main-end');
        const totalSpan = document.getElementById('main-total');
        
        if (!container || !controls) return;
        
        container.style.display = 'flex';
        controls.innerHTML = '';
        
        startSpan.innerText = totalRecords > 0 ? startIdx + 1 : 0;
        endSpan.innerText = endIdx;
        totalSpan.innerText = totalRecords;
        
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
                if (!isActive) btn.classList.add('opacity-40', 'cursor-not-allowed');
            } else {
                btn.onclick = () => {
                    currentMainPage = page;
                    saveQueueState();
                    applyFilters();
                };
            }
            return btn;
        }
        
        controls.appendChild(createButton('&lsaquo; Prev', currentMainPage - 1, currentMainPage === 1));
        
        function createEllipsis() {
            const span = document.createElement('span');
            span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
            span.innerHTML = "...";
            return span;
        }

        if (totalPages <= 5) {
            for (let i = 1; i <= totalPages; i++) {
                controls.appendChild(createButton(i, i, false, i === currentMainPage));
            }
        } else {
            controls.appendChild(createButton(1, 1, false, 1 === currentMainPage));
            if (currentMainPage > 3) controls.appendChild(createEllipsis());
            
            let startPage = Math.max(2, currentMainPage - 1);
            let endPage = Math.min(totalPages - 1, currentMainPage + 1);
            
            if (currentMainPage === 1) endPage = 3;
            if (currentMainPage === totalPages) startPage = totalPages - 2;
            
            for (let i = startPage; i <= endPage; i++) {
                controls.appendChild(createButton(i, i, false, i === currentMainPage));
            }
            
            if (currentMainPage < totalPages - 2) controls.appendChild(createEllipsis());
            controls.appendChild(createButton(totalPages, totalPages, false, totalPages === currentMainPage));
        }
        
        controls.appendChild(createButton('Next &rsaquo;', currentMainPage + 1, currentMainPage >= totalPages));
    }

    function initPatientQueue() {
        restoreFiltersFromSession();
        applyFilters();

        // ── Highlight row from notification ───────────────────────────────
        const params = new window.URLSearchParams(window.location.search);
        const highlightId = params.get('highlight');
        if (highlightId) {
            // Clean up the URL right away to prevent background polling from re-triggering highlight
            try {
                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('highlight');
                window.history.replaceState({}, document.title, cleanUrl.toString());
                if (window.__APP__) {
                    window.__APP__.currentPath = cleanUrl.pathname + cleanUrl.search;
                }
            } catch (e) {}

            const rows = document.querySelectorAll('#table-body tr.record-row');
            let targetRow = null;
            
            rows.forEach(row => {
                if ((row.dataset.id || '').toLowerCase() === highlightId.toLowerCase()) {
                    targetRow = row;
                }
            });
            
            if (targetRow) {
                const matchedRows = Array.from(rows).filter(r => r.hasAttribute('data-matched'));
                const targetIndex = matchedRows.indexOf(targetRow);
                if (targetIndex !== -1) {
                    currentMainPage = Math.floor(targetIndex / mainItemsPerPage) + 1;
                    saveQueueState();
                    paginateMain(matchedRows);
                }
                
                const tableWrapper = targetRow.closest('.overflow-y-auto');
                if (tableWrapper) {
                    const rowTop = targetRow.offsetTop - tableWrapper.offsetTop;
                    tableWrapper.scrollTo({ top: rowTop - 40, behavior: 'smooth' });
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

                // Remove existing banner if present
                const existingBanner = document.getElementById('highlight-banner');
                if (existingBanner) existingBanner.remove();

                const banner = document.createElement('div');
                banner.id = 'highlight-banner';
                banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Case <strong>${highlightId}</strong> is highlighted below.</span></div>`;
                banner.style.cssText = 'margin-left:auto;padding:0.75rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
                const header = document.querySelector('h2');
                if (header && header.parentElement) {
                    header.parentElement.insertAdjacentElement('afterend', banner);
                }
                setTimeout(() => {
                    banner.style.transition = 'opacity 0.5s';
                    banner.style.opacity = '0';
                    setTimeout(() => banner.remove(), 500);
                }, 6000);
            }
        }
    }

    // Initial trigger
    initPatientQueue();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initPatientQueue, 20);
        });
    } else {
        setTimeout(initPatientQueue, 20);
    }

    window.addEventListener('load', () => {
        setTimeout(initPatientQueue, 50);
    });

    window.addEventListener('pageshow', () => {
        initPatientQueue();
    });

    // Re-apply filters when real-time polling updates the table content
    document.addEventListener('realtime:updated', () => {
        applyFilters();
    });
</script>

<!-- Add html2canvas library for PDF-to-Image rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>



<!-- Prominent Spinning Loader -->
<div id="release-loading-overlay"
    class="fixed inset-0 z-[9999] bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm flex flex-col items-center justify-center hidden">
    <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-red-600 mb-4"></div>
    <h3 class="text-xl font-bold text-gray-800 dark:text-white">Releasing Result</h3>
    <p id="release-status-text" class="text-gray-500 dark:text-gray-400 mt-2 text-center">Preparing the results...
    </p>
</div>

<script>
    async function releaseToPhoto(caseId, btn, event = null) {
        if (event) event.preventDefault();
        const confirmed = await confirmAlert('Confirm Release', 'Would you like to confirm releasing this result and moving it to X-ray Patient Records?');
        if (!confirmed.isConfirmed) return;

        const baseDir = '/<?= PROJECT_DIR ?>';
        const overlay = document.getElementById('release-loading-overlay');
        const statusText = document.getElementById('release-status-text');

        // Disable button to prevent double clicks
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        // Show refined overlay
        if (overlay) overlay.classList.remove('hidden');
        if (statusText) statusText.textContent = 'Initializing report snapshot...';

        try {
            // 1. Create a hidden iframe to render the perfect print layout
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.top = '-10000px';
            iframe.style.left = '-10000px';
            iframe.style.width = '814px'; // Fixed A4 width for predictable rendering
            iframe.style.height = '1200px';
            iframe.style.border = 'none';

            // Use snapshot=1 to skip auto-print in the iframe
            iframe.src = `${baseDir}/index.php?page=print-report&id=${caseId}&no_shadow=1&snapshot=1`;
            document.body.appendChild(iframe);

            iframe.onload = async () => {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;

                    // Wait briefly for fonts/images
                    await new Promise(r => setTimeout(r, 1000));

                    const pages = doc.querySelectorAll('.report-page');
                    if (!pages.length) throw new Error("No pages found to render.");

                    // CRITICAL FIX: Expand iframe height so it is tall enough to fit ALL pages 
                    // without any internal scrollbars. This prevents html2canvas from clipping!
                    iframe.style.height = (doc.documentElement.scrollHeight + 200) + 'px';

                    // Wait another 500ms after expanding to ensure browser has repainted
                    await new Promise(r => setTimeout(r, 500));

                    let base64Images = [];
                    for (let i = 0; i < pages.length; i++) {
                        const page = pages[i];
                        if (statusText) statusText.textContent = `Processing page ${i + 1} of ${pages.length}...`;

                        const canvas = await html2canvas(page, {
                            scale: pages.length > 5 ? 1.5 : 2, // Slightly lower scale for very large reports to prevent memory issues
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            width: page.scrollWidth,
                            height: page.scrollHeight,
                            windowWidth: doc.documentElement.scrollWidth,
                            windowHeight: doc.documentElement.scrollHeight
                        });

                        // Convert Canvas to JPEG (JPEG is smaller than PNG for documents)
                        const imgData = canvas.toDataURL('image/jpeg', pages.length > 5 ? 0.8 : 0.9);
                        base64Images.push(imgData);
                    }

                    if (statusText) statusText.textContent = 'Uploading consolidated report...';

                    // Submit images to backend
                    const formData = new FormData();
                    formData.append('id', caseId);
                    formData.append('images', JSON.stringify(base64Images));

                    const response = await fetch(`${baseDir}/patient-lists?action=release_and_upload`, {
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
                        // Success! Refresh page to show success flash message
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
</script>

<div id="disputes-table-card" class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden <?= $currentTab !== 'disputes' ? 'hidden' : '' ?>">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-700">
                <tr>
                    <th class="text-left font-semibold px-4 py-3">Case #</th>
                    <th class="text-left font-semibold px-4 py-3">Patient</th>
                    <th class="text-left font-semibold px-4 py-3">Correction Requested</th>
                    <th class="text-left font-semibold px-4 py-3">Status</th>
                    <th class="text-left font-semibold px-4 py-3">Date</th>
                    <th class="text-left font-semibold px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 realtime-update" id="disputes-table-body">
                <?php if (empty($disputes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-500">No patient error reports found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($disputes as $d): ?>
                        <tr class="hover:bg-gray-50 transition-colors dispute-table-row" data-id="<?= htmlspecialchars($d['case_number']) ?>" data-dispute-id="<?= $d['id'] ?>" data-case="<?= htmlspecialchars($d['case_number']) ?>">
                            <td class="py-3 px-4 font-medium"><?= htmlspecialchars($d['case_number']) ?></td>
                            <td class="py-3 px-4">
                                <div class="font-medium"><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($d['patient_number'] ?? '') ?></div>
                            </td>
                            <td class="py-3 px-4 max-w-[220px] align-middle">
                                <?php
                                $fullText = $d['description'] ?? '';
                                $cat = $d['dispute_category'] ?? '';
                                $catLabel = 'Correction Request';
                                $catBadgeClass = 'text-amber-700 bg-amber-50 border-amber-200';
                                $catIcon = 'alert-circle';

                                if ($cat === 'findings_error') {
                                    $catLabel = 'Findings Error';
                                    $catBadgeClass = 'text-purple-700 bg-purple-50 border-purple-200';
                                    $catIcon = 'file-text';
                                } elseif ($cat === 'demographic_error') {
                                    $catLabel = 'Patient Info Error';
                                    $catBadgeClass = 'text-sky-700 bg-sky-50 border-sky-200';
                                    $catIcon = 'user';
                                } elseif ($cat === 'both_error') {
                                    $catLabel = 'Findings & Info';
                                    $catBadgeClass = 'text-rose-700 bg-rose-50 border-rose-200';
                                    $catIcon = 'alert-triangle';
                                } elseif ($cat === 'exam_details_error') {
                                    $catLabel = 'Exam Details Error';
                                    $catBadgeClass = 'text-amber-700 bg-amber-50 border-amber-200';
                                    $catIcon = 'clipboard';
                                } else {
                                    $descLower = strtolower($fullText);
                                    if (strpos($descLower, 'findings') !== false && strpos($descLower, 'patient info') !== false) {
                                        $catLabel = 'Findings & Info';
                                        $catBadgeClass = 'text-rose-700 bg-rose-50 border-rose-200';
                                    } elseif (strpos($descLower, 'findings') !== false) {
                                        $catLabel = 'Findings Error';
                                        $catBadgeClass = 'text-purple-700 bg-purple-50 border-purple-200';
                                        $catIcon = 'file-text';
                                    } elseif (strpos($descLower, 'patient info') !== false || strpos($descLower, 'name') !== false) {
                                        $catLabel = 'Patient Info Error';
                                        $catBadgeClass = 'text-sky-700 bg-sky-50 border-sky-200';
                                        $catIcon = 'user';
                                    }
                                }

                                $disputePayload = [
                                    'id' => $d['id'],
                                    'case_number' => $d['case_number'],
                                    'patient_name' => $d['first_name'] . ' ' . $d['last_name'],
                                    'patient_number' => $d['patient_number'] ?? '',
                                    'description' => $fullText,
                                    'status' => $d['status'],
                                    'created_at' => date('M d, Y h:i A', strtotime($d['created_at']))
                                ];
                                $jsonPayload = htmlspecialchars(json_encode($disputePayload), ENT_QUOTES, 'UTF-8');
                                ?>
                                <div class="flex flex-col items-start gap-1">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-md border <?= $catBadgeClass ?> shadow-2xs">
                                        <i data-lucide="<?= $catIcon ?>" class="w-3 h-3"></i>
                                        <?= htmlspecialchars($catLabel) ?>
                                    </span>
                                    <button type="button" 
                                            onclick='openDisputeDetailsModal(<?= $jsonPayload ?>)'
                                            class="text-xs text-amber-700 hover:text-amber-900 font-semibold hover:underline cursor-pointer select-none">
                                        See error
                                    </button>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <?php $currStatus = $d['status']; ?>
                                <?php if ($currStatus === 'Pending RadTech Review'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Patient Reviewing
                                    </span>
                                <?php elseif ($currStatus === 'Escalated to Radiologist'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-purple-700 bg-purple-50 border border-purple-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Waiting for Radiologist
                                    </span>
                                <?php elseif ($currStatus === 'Pending RadTech Verification'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="shield-check" class="w-3 h-3"></i> Ready for Release
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i> <?= htmlspecialchars($currStatus) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500 whitespace-nowrap">
                                <?= date('M d, Y h:i A', strtotime($d['created_at'])) ?>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <?php
                                    $cat = $d['dispute_category'] ?? '';
                                    $currStatus = $d['status'] ?? '';
                                    $demoFixed = (int)($d['demographics_fixed'] ?? 0);
                                    $radAmended = (int)($d['radiologist_amended'] ?? 0);
                                    $isPendingReview = ($currStatus === 'Pending RadTech Review');
                                    $isPendingVerification = ($currStatus === 'Pending RadTech Verification');
                                    $isEscalated = ($currStatus === 'Escalated to Radiologist');
                                    $isResolved = ($currStatus === 'Resolved');

                                    // Classify Category:
                                    $isDemographicOnly = ($cat === 'demographic_error');
                                    $isFindingsOnly = in_array($cat, ['findings_error', 'exam_details_error']);
                                    $isBoth = ($cat === 'both_error');

                                    if (!$isDemographicOnly && !$isFindingsOnly && !$isBoth) {
                                        $descLower = strtolower($d['description'] ?? '');
                                        $hasF = strpos($descLower, 'findings') !== false || strpos($descLower, 'impression') !== false || strpos($descLower, 'reading') !== false || strpos($descLower, 'image') !== false;
                                        $hasD = strpos($descLower, 'patient info') !== false || strpos($descLower, 'name') !== false || strpos($descLower, 'age') !== false || strpos($descLower, 'sex') !== false;
                                        if ($hasF && $hasD) $isBoth = true;
                                        elseif ($hasF) $isFindingsOnly = true;
                                        elseif ($hasD) $isDemographicOnly = true;
                                        else $isBoth = true;
                                    }

                                    // Button 1 (Re-upload): shows if $isBoth || $isFindingsOnly
                                    $showReuploadBtn = $isBoth || $isFindingsOnly;

                                    // Button 2 (Fix Demographics): shows if $isBoth || $isDemographicOnly
                                    $showFixInfoBtn = $isBoth || $isDemographicOnly;

                                    // Button 3 (Final Verify): active when required steps are completed
                                    $isVerifyActive = false;
                                    if ($isDemographicOnly) {
                                        $isVerifyActive = (bool)$demoFixed;
                                    } elseif ($isFindingsOnly) {
                                        $isVerifyActive = $isPendingVerification;
                                    } elseif ($isBoth) {
                                        $isVerifyActive = $isPendingVerification && (bool)$demoFixed;
                                    }

                                    // Payloads for Modals
                                    $dispPayload = [
                                        'id' => $d['id'],
                                        'case_number' => $d['case_number'],
                                        'patient_number' => $d['patient_number'] ?? '',
                                        'description' => $d['description'],
                                        'first_name' => $d['first_name'] ?? '',
                                        'last_name' => $d['last_name'] ?? '',
                                        'middle_name' => $d['middle_name'] ?? '',
                                        'age' => $d['age'] ?? '',
                                        'sex' => $d['sex'] ?? '',
                                        'user_account_name' => $d['user_account_name'] ?? '',
                                        'category' => $d['dispute_category'] ?? ''
                                    ];
                                    $dispJson = htmlspecialchars(json_encode($dispPayload), ENT_QUOTES, 'UTF-8');

                                    $verifPayload = [
                                        'id' => $d['id'],
                                        'case_number' => $d['case_number'],
                                        'patient_number' => $d['patient_number'] ?? '',
                                        'description' => $d['description'],
                                        'category' => $d['dispute_category'] ?? '',
                                        'first_name' => $d['first_name'] ?? '',
                                        'last_name' => $d['last_name'] ?? '',
                                        'middle_name' => $d['middle_name'] ?? '',
                                        'age' => $d['age'] ?? '',
                                        'sex' => $d['sex'] ?? '',
                                        'user_account_name' => $d['user_account_name'] ?? '',
                                        'radtech_notes' => $d['radtech_notes'] ?? '',
                                        'resolution_notes' => $d['resolution_notes'] ?? '',
                                        'old_findings' => $d['old_findings'] ?? '',
                                        'findings' => $d['findings'] ?? '',
                                        'old_impression' => $d['old_impression'] ?? '',
                                        'impression' => $d['impression'] ?? '',
                                        'exam_type' => $d['exam_type'] ?? ''
                                    ];
                                    $verifJson = htmlspecialchars(json_encode($verifPayload), ENT_QUOTES, 'UTF-8');
                                ?>

                                <?php if ($isResolved): ?>
                                    <span class="text-xs text-gray-400 italic font-normal select-none">No action needed</span>
                                <?php else: ?>
                                    <div class="flex items-center justify-start gap-1.5">
                                        <!-- Button 1: Re-upload / Escalate -->
                                        <?php if ($showReuploadBtn): ?>
                                            <?php if ($isPendingReview && !$radAmended): ?>
                                                <button type="button" onclick="confirmReupload(<?= $d['case_id'] ?>)"
                                                        class="text-blue-500 hover:text-blue-700 transition cursor-pointer" title="Re-upload &amp; Escalate to Radiologist">
                                                    <i data-lucide="image-up" class="w-6 h-6 bg-blue-100 text-blue-600 p-1 rounded-md border border-blue-400 hover:bg-blue-200 transition"></i>
                                                </button>
                                            <?php elseif ($isEscalated): ?>
                                                <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?page=patient-details&id=<?= $d['case_id'] ?>&from=disputes"
                                                   class="text-blue-500 hover:text-blue-700 transition cursor-pointer" title="View Uploaded Details (Escalated to Radiologist)">
                                                    <i data-lucide="image" class="w-6 h-6 bg-blue-50 text-blue-500 p-1 rounded-md border border-blue-300 hover:bg-blue-100 transition"></i>
                                                </a>
                                            <?php else: ?>
                                                <button type="button" disabled
                                                        class="opacity-40 cursor-not-allowed select-none" title="Already amended by Radiologist">
                                                    <i data-lucide="image-up" class="w-6 h-6 bg-gray-100 text-gray-400 p-1 rounded-md border border-gray-300"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- Button 2: Fix Information (Demographics) -->
                                        <?php if ($showFixInfoBtn): ?>
                                            <?php if (!$demoFixed): ?>
                                                <button type="button" onclick='openFixDemographicsModal(<?= $dispJson ?>)'
                                                        class="text-green-500 hover:text-green-700 transition cursor-pointer" title="Fix Patient Demographics">
                                                    <i data-lucide="clipboard-check" class="w-6 h-6 bg-green-100 text-green-600 p-1 rounded-md border border-green-400 hover:bg-green-200 transition"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" disabled
                                                        class="opacity-40 cursor-not-allowed select-none" title="Patient demographics already updated">
                                                    <i data-lucide="clipboard-check" class="w-6 h-6 bg-gray-100 text-gray-400 p-1 rounded-md border border-gray-300"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- Button 3: Verify & Release -->
                                        <?php if ($isVerifyActive): ?>
                                            <button type="button" onclick='openVerifyReleaseModal(<?= $verifJson ?>)'
                                                    class="text-green-600 hover:text-green-800 transition cursor-pointer" title="Final Verify &amp; Release Amended Report">
                                                <i data-lucide="check-circle-2" class="w-6 h-6 bg-green-100 text-green-700 p-1 rounded-md border border-green-500 hover:bg-green-200 transition"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" disabled
                                                    class="opacity-40 cursor-not-allowed select-none" title="Verify &amp; Release (Available after required corrections are completed)">
                                                <i data-lucide="check-circle-2" class="w-6 h-6 bg-gray-100 text-gray-400 p-1 rounded-md border border-gray-300"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Controls for Disputes -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4" id="disputes-pagination-container" style="display: flex;">
        <span id="disputes-record-count" class="text-xs text-gray-500 font-medium">
            Showing <span id="disputes-start">0</span> to <span id="disputes-end">0</span> of <span id="disputes-total" class="font-semibold text-gray-800">0</span> records
        </span>
        <div class="flex items-center flex-wrap gap-1.5" id="disputes-pagination-controls">
        </div>
    </div>
</div>

<script>
    // Disputes Pagination Logic
    let currentDisputesPage = 1;
    const disputesItemsPerPage = 7;

    function paginateDisputes(targetTbody = null) {
        const tbody = targetTbody || document.getElementById('disputes-table-body');
        if (!tbody) return;

        let rows = Array.from(tbody.querySelectorAll('tr:not(#disputes-empty-msg-row)'));
        
        // Exclude the "No patient error reports found." row if it exists
        if (rows.length === 1 && rows[0].querySelector('td[colspan="6"]')) {
            return;
        }

        const totalRecords = rows.length;
        let totalPages = Math.ceil(totalRecords / disputesItemsPerPage);
        
        if (currentDisputesPage > totalPages && totalPages > 0) currentDisputesPage = totalPages;
        if (totalPages === 0) currentDisputesPage = 1;

        const start = (currentDisputesPage - 1) * disputesItemsPerPage;
        const end = Math.min(start + disputesItemsPerPage, totalRecords);

        rows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });

        renderDisputesPaginationControls(totalPages, totalRecords, start, end);
    }

    function renderDisputesPaginationControls(totalPages, totalRecords, startIdx, endIdx) {
        const container = document.getElementById('disputes-pagination-container');
        const controls = document.getElementById('disputes-pagination-controls');
        const startSpan = document.getElementById('disputes-start');
        const endSpan = document.getElementById('disputes-end');
        const totalSpan = document.getElementById('disputes-total');
        
        if (!container || !controls) return;
        
        container.style.display = 'flex';
        controls.innerHTML = '';
        
        startSpan.innerText = totalRecords > 0 ? startIdx + 1 : 0;
        endSpan.innerText = endIdx;
        totalSpan.innerText = totalRecords;
        
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
                    currentDisputesPage = page;
                    paginateDisputes();
                    const tableContainer = document.querySelector('#disputes-table-card .overflow-x-auto');
                    if (tableContainer) tableContainer.scrollTo({ top: 0, behavior: 'smooth' });
                };
            }
            return btn;
        }
        
        function createEllipsis() {
            const span = document.createElement('span');
            span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
            span.innerHTML = "...";
            return span;
        }
        
        controls.appendChild(createButton('&lsaquo; Back', currentDisputesPage - 1, currentDisputesPage === 1));
        
        if (totalPages <= 5) {
            for (let i = 1; i <= totalPages; i++) {
                controls.appendChild(createButton(i, i, false, i === currentDisputesPage));
            }
        } else {
            controls.appendChild(createButton(1, 1, false, 1 === currentDisputesPage));
            if (currentDisputesPage > 3) controls.appendChild(createEllipsis());
            
            let startPage = Math.max(2, currentDisputesPage - 1);
            let endPage = Math.min(totalPages - 1, currentDisputesPage + 1);
            
            if (currentDisputesPage === 1) endPage = 3;
            if (currentDisputesPage === totalPages) startPage = totalPages - 2;
            
            for (let i = startPage; i <= endPage; i++) {
                controls.appendChild(createButton(i, i, false, i === currentDisputesPage));
            }
            
            if (currentDisputesPage < totalPages - 2) controls.appendChild(createEllipsis());
            controls.appendChild(createButton(totalPages, totalPages, false, totalPages === currentDisputesPage));
        }
        
        controls.appendChild(createButton('Next &rsaquo;', currentDisputesPage + 1, currentDisputesPage >= totalPages));
    }

    function handleDisputesHighlight() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('tab') !== 'disputes') return;

        const targetVal = params.get('dispute_id') || params.get('highlight_dispute_id') || params.get('highlight_case') || params.get('highlight');
        if (!targetVal) return;

        const rows = document.querySelectorAll('#disputes-table-body tr.dispute-table-row');
        let targetRow = null;
        rows.forEach(r => {
            if (
                (r.dataset.disputeId || '').toLowerCase() === targetVal.toLowerCase() ||
                (r.dataset.id || '').toLowerCase() === targetVal.toLowerCase() ||
                (r.dataset.case || '').toLowerCase() === targetVal.toLowerCase() ||
                r.innerText.toLowerCase().includes(targetVal.toLowerCase())
            ) {
                targetRow = r;
            }
        });

        if (targetRow) {
            const rowIndex = Array.from(rows).indexOf(targetRow);
            currentDisputesPage = Math.floor(rowIndex / disputesItemsPerPage) + 1;
            paginateDisputes();

            setTimeout(() => {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetRow.classList.add('transition-all', 'duration-300', 'ring-2', 'ring-amber-400');
                targetRow.style.backgroundColor = '#fef08a';

                let flashCount = 0;
                const flashInterval = setInterval(() => {
                    flashCount++;
                    targetRow.style.backgroundColor = (flashCount % 2 === 1) ? '#fde047' : '#fef08a';
                    if (flashCount >= 6) {
                        clearInterval(flashInterval);
                        setTimeout(() => {
                            targetRow.style.transition = 'background-color 2s ease, box-shadow 2s ease';
                            targetRow.style.backgroundColor = '';
                            targetRow.classList.remove('ring-2', 'ring-amber-400');
                        }, 1200);
                    }
                }, 250);

                try {
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete('dispute_id');
                    cleanUrl.searchParams.delete('highlight_dispute_id');
                    cleanUrl.searchParams.delete('highlight_case');
                    cleanUrl.searchParams.delete('highlight');
                    cleanUrl.searchParams.delete('is_new');
                    window.history.replaceState({}, document.title, cleanUrl.toString());
                } catch(e) {}
            }, 150);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            paginateDisputes();
            handleDisputesHighlight();
        }, 100);
    });

    function formatDisputeDescriptionHtml(desc, scope = 'all') {
        if (!desc) return '<span class="text-xs text-gray-400 italic">No description provided.</span>';

        const clean = desc.replace(/\r\n/g, '\n').trim();
        let findings = '';
        let demographics = '';
        let other = '';

        const findingsMatch = clean.match(/Findings Note:\s*([\s\S]*?)(?=(Wrong Patient Info:|Exam Details Note:|Demographics Note:|$))/i);
        if (findingsMatch && findingsMatch[1].trim()) {
            findings = findingsMatch[1].trim().replace(/^•\s*/gm, '').trim();
        }

        const demoMatch = clean.match(/(?:Wrong Patient Info:|Demographics Note:)\s*([\s\S]*?)(?=(Findings Note:|Exam Details Note:|$))/i);
        if (demoMatch && demoMatch[1].trim()) {
            demographics = demoMatch[1].trim().replace(/^•\s*/gm, '').trim();
        }

        if (!findings && !demographics) {
            other = clean;
        }

        let html = '<div class="space-y-1.5">';

        if (findings && (scope === 'all' || scope === 'findings')) {
            html += `
                <div class="flex items-start gap-2 text-xs py-0.5">
                    <span class="font-bold text-purple-800 shrink-0 uppercase text-[10px] bg-purple-100 border border-purple-200 px-1.5 py-0.5 rounded">Findings</span>
                    <span class="text-gray-800 font-medium leading-relaxed">${findings}</span>
                </div>`;
        }

        if (demographics && (scope === 'all' || scope === 'demographics')) {
            const badges = demographics.split(/,|\n/).map(s => s.trim().replace(/^•\s*/, '')).filter(Boolean);
            html += `
                <div class="flex items-center gap-2 text-xs py-0.5">
                    <span class="font-bold text-blue-800 shrink-0 uppercase text-[10px] bg-blue-100 border border-blue-200 px-1.5 py-0.5 rounded">Patient Info</span>
                    <div class="flex flex-wrap gap-1">
                        ${badges.map(b => `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-800 border border-blue-200">${b}</span>`).join('')}
                    </div>
                </div>`;
        }

        if (other && (!findings || scope !== 'demographics') && (!demographics || scope !== 'findings')) {
            html += `
                <div class="text-xs text-gray-800 font-medium whitespace-pre-line leading-relaxed py-0.5">
                    ${other}
                </div>`;
        }

        html += '</div>';
        return html;
    }

    function openDisputeDetailsModal(data) {
        if (!data) return;
        const subEl = document.getElementById('ddm-subtitle');
        const textEl = document.getElementById('ddm-full-text');

        if (subEl) subEl.innerText = `Case #${data.case_number}` + (data.patient_name ? ` • ${data.patient_name}` : '');
        if (textEl) textEl.innerHTML = formatDisputeDescriptionHtml(data.description);

        const modal = document.getElementById('dispute-details-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function closeDisputeDetailsModal() {
        const modal = document.getElementById('dispute-details-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    document.addEventListener('realtime:beforeUpdate', (e) => {
        const newEl = e.detail.newEl;
        if (newEl && newEl.id === 'disputes-table-body') {
            paginateDisputes(newEl);
        }
        if (newEl && newEl.id === 'table-body') {
            applyFilters(newEl);
        }
    });
</script>

<!-- DISPUTE / CORRECTION DETAILS MODAL -->
<div id="dispute-details-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-xs p-4" onclick="if(event.target === this) closeDisputeDetailsModal()">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-start justify-between border-b border-gray-100 pb-3.5">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Correction Request Details</h3>
                <p id="ddm-subtitle" class="text-xs text-gray-500 mt-0.5"></p>
            </div>
            <button onclick="closeDisputeDetailsModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Full Statement Content -->
        <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                Correction Requested / Notes:
            </label>
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl max-h-96 overflow-y-auto">
                <div id="ddm-full-text" class="text-xs text-gray-800 font-medium whitespace-pre-wrap leading-relaxed"></div>
            </div>
        </div>
    </div>
</div>

<!-- ESCALATE TO RADIOLOGIST MODAL -->
<div id="escalate-dispute-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="font-bold text-gray-900 text-base">Escalate to Radiologist</h3>
            <button onclick="closeEscalateModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <p class="text-xs text-gray-600">This medical interpretation error will be forwarded to the Radiologist to review the image and issue an Amended Report.</p>
        <form id="escalate-form" onsubmit="submitEscalation(event)" class="space-y-3">
            <input type="hidden" id="escalate-dispute-id" name="dispute_id">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Internal Notes for Radiologist *</label>
                <textarea id="escalate-notes" name="radtech_notes" required rows="3" placeholder="Example: Please re-examine the lung fields based on patient's feedback..."
                          class="w-full text-xs rounded-xl border border-gray-300 p-3 outline-none focus:ring-2 focus:ring-red-500"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEscalateModal()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow">Send to Radiologist</button>
            </div>
        </form>
    </div>
</div>

<!-- FIX DEMOGRAPHICS MODAL (Side-by-Side Old vs New Name Comparison) -->
<div id="fix-demographics-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl space-y-4 max-h-[95vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-5 h-5 text-green-600"></i>
                    Fix Patient Demographics
                </h3>
                <p id="fix-modal-subtitle" class="text-xs text-gray-500 mt-0.5 font-medium"></p>
            </div>
            <button onclick="closeFixDemographicsModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Patient Requested Corrections Box -->
        <div class="px-4 py-3 bg-amber-50/80 border border-amber-200 rounded-xl space-y-1">
            <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600"></i>
                Reported Incorrect Info:
            </div>
            <div id="fix-patient-statement" class="text-xs text-amber-950 font-medium pl-5 leading-relaxed"></div>
        </div>

        <!-- Side-by-Side Old vs New Demographics Comparison Box -->
        <div class="space-y-2.5">
            <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="user-check" class="w-4 h-4 text-gray-600"></i>
                Demographics Verification &amp; Comparison:
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Left Box: Old Record -->
                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-2xs space-y-3 max-h-64 overflow-y-auto custom-gray-scroll">
                    <div class="pb-2 border-b border-gray-100">
                        <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Old Record (Report)</span>
                    </div>

                    <div id="row-fix-old-first-name">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">First Name:</div>
                        <div id="fix-old-first-name" class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-fix-old-last-name">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Last Name:</div>
                        <div id="fix-old-last-name" class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-fix-old-age" class="hidden">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Age:</div>
                        <div id="fix-old-age" class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-fix-old-sex" class="hidden">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Sex / Gender:</div>
                        <div id="fix-old-sex" class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">—</div>
                    </div>
                </div>

                <!-- Right Box: New / Updated Info -->
                <div class="bg-green-50/40 border border-green-300 rounded-xl p-4 shadow-2xs space-y-3 max-h-64 overflow-y-auto custom-gray-scroll">
                    <div class="pb-2 border-b border-green-100">
                        <span class="text-[11px] font-bold text-green-700 uppercase tracking-wider">New / Updated Info</span>
                    </div>

                    <div id="row-fix-new-first-name">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">First Name:</div>
                        <div id="fix-new-first-name" class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-fix-new-last-name">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Last Name:</div>
                        <div id="fix-new-last-name" class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-fix-new-age" class="hidden">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Age:</div>
                        <div id="fix-new-age" class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-fix-new-sex" class="hidden">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Sex / Gender:</div>
                        <div id="fix-new-sex" class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">—</div>
                    </div>
                </div>
            </div>

            <!-- Success Alert Banner -->
            <div id="fix-demo-alert" class="hidden p-3 rounded-lg bg-green-50 border border-green-200 text-xs text-green-800 font-semibold flex items-center gap-2 shadow-2xs">
                <i data-lucide="check-circle" class="w-4 h-4 text-green-600 shrink-0"></i>
                <span>Patient record successfully updated with new information. You can now confirm and resolve this ticket.</span>
            </div>
        </div>

        <input type="hidden" id="fix-demo-dispute-id">

        <!-- Footer Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200">
            <button type="button" onclick="closeFixDemographicsModal()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl cursor-pointer transition">
                Cancel
            </button>

            <!-- Apply & Fix Record Button -->
            <button type="button" id="btn-apply-fix-demo" onclick="applyFixDemographics()"
                    class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer">
                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                Apply &amp; Fix Record
            </button>
        </div>
    </div>
</div>

<!-- VERIFY & RELEASE MODAL (Comprehensive Amendments Overview) -->
<div id="verify-release-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl space-y-4 max-h-[95vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-5 h-5 text-green-600"></i>
                    Final Verify &amp; Release Report
                </h3>
                <p id="verify-modal-subtitle" class="text-xs text-gray-500 mt-0.5 font-medium"></p>
            </div>
            <button onclick="closeVerifyReleaseModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Section 1: Patient's Reported Issue -->
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl space-y-1.5">
            <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600"></i>
                Patient's Reported Issue:
            </div>
            <div id="verify-patient-statement" class="text-xs text-amber-950 font-medium whitespace-pre-line leading-relaxed pl-1"></div>
        </div>

        <!-- Section 3: Side-by-Side Demographics Comparison (Shown if patient info error reported) -->
        <div id="verify-demographics-container" class="space-y-2.5 hidden">
            <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="user-check" class="w-4 h-4 text-gray-600"></i>
                Demographics Verification &amp; Comparison:
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Left Box: Old Record -->
                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-2xs space-y-3 max-h-64 overflow-y-auto custom-gray-scroll">
                    <div class="pb-2 border-b border-gray-100">
                        <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Old Record (Report)</span>
                    </div>

                    <div id="row-ver-old-first-name">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">First Name:</div>
                        <div id="ver-old-first-name" class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-ver-old-last-name">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Last Name:</div>
                        <div id="ver-old-last-name" class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-ver-old-age" class="hidden">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Age:</div>
                        <div id="ver-old-age" class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-ver-old-sex" class="hidden">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Sex / Gender:</div>
                        <div id="ver-old-sex" class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">—</div>
                    </div>
                </div>

                <!-- Right Box: New / Updated Info -->
                <div class="bg-green-50/40 border border-green-300 rounded-xl p-4 shadow-2xs space-y-3 max-h-64 overflow-y-auto custom-gray-scroll">
                    <div class="pb-2 border-b border-green-100">
                        <span class="text-[11px] font-bold text-green-700 uppercase tracking-wider">New / Updated Info</span>
                    </div>

                    <div id="row-ver-new-first-name">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">First Name:</div>
                        <div id="ver-new-first-name" class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-ver-new-last-name">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Last Name:</div>
                        <div id="ver-new-last-name" class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-ver-new-age" class="hidden">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Age:</div>
                        <div id="ver-new-age" class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">—</div>
                    </div>

                    <div id="row-ver-new-sex" class="hidden">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Sex / Gender:</div>
                        <div id="ver-new-sex" class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">—</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Side-by-Side Findings Comparison Box -->
        <div id="verify-findings-container" class="space-y-2.5 hidden">
            <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-4 h-4 text-purple-600"></i>
                Amended Findings &amp; Impression Comparison:
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                        Old Result
                    </div>
                    <div id="verify-old-findings" class="text-xs text-gray-800 bg-white p-4 rounded-xl border border-gray-200 shadow-2xs max-h-56 overflow-y-auto overscroll-contain leading-relaxed custom-gray-scroll"></div>
                </div>
                <div class="space-y-1.5">
                    <div class="text-[11px] font-bold text-green-700 uppercase tracking-wider">
                        New Amended Result
                    </div>
                    <div id="verify-new-findings" class="text-xs text-green-950 bg-green-50/50 p-4 rounded-xl border border-green-300 shadow-2xs max-h-56 overflow-y-auto overscroll-contain leading-relaxed custom-gray-scroll"></div>
                </div>
            </div>
        </div>

        <input type="hidden" id="verify-release-dispute-id">

        <!-- Footer Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200">
            <button type="button" onclick="closeVerifyReleaseModal()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl cursor-pointer transition">
                Cancel
            </button>

            <button type="button" onclick="submitAmendedRelease()"
                    class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-xs transition cursor-pointer">
                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                Confirm &amp; Release Amended Report
            </button>
        </div>
    </div>
</div>

<script>
let currentFixData = null;
let currentVerifyData = null;

function openEscalateModal(id, caseNo) {
    document.getElementById('escalate-dispute-id').value = id;
    document.getElementById('escalate-notes').value = '';
    document.getElementById('escalate-dispute-modal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}
function closeEscalateModal() {
    document.getElementById('escalate-dispute-modal').classList.add('hidden');
}

// ── FIX DEMOGRAPHICS MODAL LOGIC ──
function openFixDemographicsModal(data) {
    currentFixData = data;
    document.getElementById('fix-demo-dispute-id').value = data.id || '';
    document.getElementById('fix-modal-subtitle').innerText = 'Case #: ' + (data.case_number || 'N/A') + ' | Patient #: ' + (data.patient_number || 'N/A');

    // Statement: Render clean bulleted text directly below the heading
    const descText = data.description || '';
    const statementEl = document.getElementById('fix-patient-statement');
    
    let demoNote = '';
    const clean = descText.replace(/\r\n/g, '\n').trim();
    const demoMatch = clean.match(/(?:Wrong Patient Info:|Demographics Note:)\s*([\s\S]*?)(?=(Findings Note:|Exam Details Note:|$))/i);
    if (demoMatch && demoMatch[1].trim()) {
        demoNote = demoMatch[1].trim().replace(/^•\s*/gm, '').trim();
    }
    if (!demoNote) {
        demoNote = clean;
    }
    const items = demoNote.split(/,|\n/).map(s => s.trim().replace(/^•\s*/, '')).filter(Boolean);
    if (items.length > 0) {
        statementEl.innerHTML = `• ${items.join(', ')}`;
    } else {
        statementEl.innerHTML = `<span class="text-xs text-amber-800 italic">No specific fields reported</span>`;
    }

    // Populate Side-by-Side Old vs New
    const newFn = data.first_name || '';
    const newLn = data.last_name || '';
    const newMn = data.middle_name || '';
    const ageVal = data.age ? `${data.age} yrs old` : 'N/A';
    const sexVal = data.sex || 'N/A';

    // Old name fallback
    let oldFn = newFn;
    let oldLn = newLn;
    let oldMn = newMn;
    if (data.user_account_name && data.user_account_name !== `${newFn} ${newLn}`.trim()) {
        oldFn = data.user_account_name.split(' ')[0] || newFn;
    }

    function safeSetText(id, text) {
        const el = document.getElementById(id);
        if (el) el.innerText = text;
    }

    safeSetText('fix-old-first-name', oldFn || 'N/A');
    safeSetText('fix-old-last-name', oldLn || 'N/A');
    safeSetText('fix-old-age', ageVal);
    safeSetText('fix-old-sex', sexVal);

    safeSetText('fix-new-first-name', newFn || 'N/A');
    safeSetText('fix-new-last-name', newLn || 'N/A');
    safeSetText('fix-new-age', ageVal);
    safeSetText('fix-new-sex', sexVal);

    // Filter which rows to display based on what the patient reported in the ticket
    const descLower = descText.toLowerCase();
    const hasFirstName = descLower.includes('first name');
    const hasLastName = descLower.includes('last name');
    const hasAge = descLower.includes('age');
    const hasSex = descLower.includes('sex') || descLower.includes('gender');

    // Dynamic field filtering based on patient description
    const hasAnySpecific = hasFirstName || hasLastName || hasAge || hasSex;

    function setRowVisibility(id, visible) {
        const el = document.getElementById(id);
        if (el) {
            if (visible) el.classList.remove('hidden');
            else el.classList.add('hidden');
        }
    }

    setRowVisibility('row-fix-old-first-name', hasFirstName || !hasAnySpecific);
    setRowVisibility('row-fix-new-first-name', hasFirstName || !hasAnySpecific);

    setRowVisibility('row-fix-old-last-name', hasLastName || !hasAnySpecific);
    setRowVisibility('row-fix-new-last-name', hasLastName || !hasAnySpecific);

    setRowVisibility('row-fix-old-age', hasAge);
    setRowVisibility('row-fix-new-age', hasAge);

    setRowVisibility('row-fix-old-sex', hasSex);
    setRowVisibility('row-fix-new-sex', hasSex);

    // Reset button states
    const btnApply = document.getElementById('btn-apply-fix-demo');
    const alertEl = document.getElementById('fix-demo-alert');
    if (alertEl) alertEl.classList.add('hidden');

    if (btnApply) {
        btnApply.disabled = false;
        btnApply.className = "inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer";
        btnApply.innerHTML = '<i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Apply &amp; Fix Record';
    }

    document.getElementById('fix-demographics-modal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function closeFixDemographicsModal() {
    document.getElementById('fix-demographics-modal').classList.add('hidden');
}

function applyFixDemographics() {
    if (!currentFixData) return;
    const disputeId = currentFixData.id;
    const firstName = currentFixData.first_name || '';
    const lastName = currentFixData.last_name || '';
    const middleName = currentFixData.middle_name || '';

    const btnApply = document.getElementById('btn-apply-fix-demo');
    if (btnApply) {
        btnApply.disabled = true;
        btnApply.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Applying...';
    }
    if (window.lucide) lucide.createIcons();

    const fd = new FormData();
    fd.append('dispute_id', disputeId);
    fd.append('first_name', firstName);
    fd.append('last_name', lastName);
    fd.append('middle_name', middleName);

    fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/disputes.php?action=update_patient_demographics', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeFixDemographicsModal();
            if (res.both_pending_escalate) {
                Swal.fire({
                    title: 'Demographics Corrected!',
                    text: 'Patient demographic information has been updated. Please proceed to Re-upload & Escalate to Radiologist for the remaining findings issue.',
                    icon: 'info',
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: 'Changes Applied!',
                    text: 'Patient demographic corrections applied. You can now verify and release the case using the "Verify & Release" action.',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                }).then(() => location.reload());
            }
        } else {
            if (btnApply) {
                btnApply.disabled = false;
                btnApply.className = "inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer";
                btnApply.innerHTML = '<i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Apply &amp; Fix Record';
            }
            Swal.fire('Error', res.message || 'Failed to update record.', 'error');
            if (window.lucide) lucide.createIcons();
        }
    })
    .catch(err => {
        if (btnApply) {
            btnApply.disabled = false;
            btnApply.className = "inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer";
            btnApply.innerHTML = '<i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Apply &amp; Fix Record';
        }
        Swal.fire('Error', 'Connection error occurred.', 'error');
        if (window.lucide) lucide.createIcons();
    });
}

// ── VERIFY & RELEASE MODAL LOGIC ──
function openVerifyReleaseModal(data) {
    currentVerifyData = data;
    document.getElementById('verify-release-dispute-id').value = data.id || '';
    document.getElementById('verify-modal-subtitle').innerText = 'Case #: ' + (data.case_number || 'N/A') + ' | Patient #: ' + (data.patient_number || 'N/A');

    // Statement
    document.getElementById('verify-patient-statement').innerHTML = formatDisputeDescriptionHtml(data.description);

    // Demographics comparison logic
    const descText = data.description || '';
    const descLower = descText.toLowerCase();
    const cat = data.category || '';

    const hasFirstName = descLower.includes('first name');
    const hasLastName = descLower.includes('last name');
    const hasMiddleName = descLower.includes('middle name');
    const hasAge = descLower.includes('age');
    const hasSex = descLower.includes('sex') || descLower.includes('gender');
    const hasWrongInfoHeading = descLower.includes('wrong patient info');

    const hasDemoChanges = (cat === 'demographic_error' || cat === 'both_error' || hasFirstName || hasLastName || hasMiddleName || hasAge || hasSex || hasWrongInfoHeading);

    const demoCont = document.getElementById('verify-demographics-container');
    if (hasDemoChanges) {
        const newFn = data.first_name || '';
        const newLn = data.last_name || '';
        const newMn = data.middle_name || '';
        const ageVal = data.age ? `${data.age} yrs old` : 'N/A';
        const sexVal = data.sex || 'N/A';

        let oldFn = newFn;
        let oldLn = newLn;
        let oldMn = newMn;
        if (data.user_account_name && data.user_account_name !== `${newFn} ${newLn}`.trim()) {
            oldFn = data.user_account_name.split(' ')[0] || newFn;
        }

        function safeSetVer(id, text) {
            const el = document.getElementById(id);
            if (el) el.innerText = text;
        }

        safeSetVer('ver-old-first-name', oldFn || 'N/A');
        safeSetVer('ver-old-last-name', oldLn || 'N/A');
        safeSetVer('ver-old-age', ageVal);
        safeSetVer('ver-old-sex', sexVal);

        safeSetVer('ver-new-first-name', newFn || 'N/A');
        safeSetVer('ver-new-last-name', newLn || 'N/A');
        safeSetVer('ver-new-age', ageVal);
        safeSetVer('ver-new-sex', sexVal);

        // Dynamic field row filtering
        const hasAnySpecific = hasFirstName || hasLastName || hasAge || hasSex;

        function setVerRow(id, visible) {
            const el = document.getElementById(id);
            if (el) {
                if (visible) el.classList.remove('hidden');
                else el.classList.add('hidden');
            }
        }

        setVerRow('row-ver-old-first-name', hasFirstName || !hasAnySpecific);
        setVerRow('row-ver-new-first-name', hasFirstName || !hasAnySpecific);

        setVerRow('row-ver-old-last-name', hasLastName || !hasAnySpecific);
        setVerRow('row-ver-new-last-name', hasLastName || !hasAnySpecific);

        setVerRow('row-ver-old-age', hasAge);
        setVerRow('row-ver-new-age', hasAge);

        setVerRow('row-ver-old-sex', hasSex);
        setVerRow('row-ver-new-sex', hasSex);

        demoCont.classList.remove('hidden');
    } else {
        demoCont.classList.add('hidden');
    }

    // Findings comparison
    const findingsCont = document.getElementById('verify-findings-container');
    const oldFindings = data.old_findings || '';
    const newFindings = data.findings || '';
    const oldImpression = data.old_impression || '';
    const newImpression = data.impression || '';
    const examType = data.exam_type || '';

    // If only patient info/demographics was reported, do NOT show findings comparison
    const isPureDemographicError = (cat === 'demographic_error') || 
        (hasDemoChanges && !oldFindings && !descLower.includes('findings') && !descLower.includes('impression') && !descLower.includes('reading') && cat !== 'both_error');

    const hasFindings = !isPureDemographicError && (cat === 'findings_error' || cat === 'exam_details_error' || cat === 'both_error' || Boolean(oldFindings));

    if (hasFindings && (oldFindings || newFindings)) {
        function parseToHtml(fJson, iJson, fallbackKey, isNew = false) {
            let fObj = {}, iObj = {};
            try { fObj = JSON.parse(fJson || "{}"); } catch(e) { if(fJson) fObj[fallbackKey || "RESULT"] = fJson; }
            try { iObj = JSON.parse(iJson || "{}"); } catch(e) { if(iJson) iObj[fallbackKey || "RESULT"] = iJson; }
            
            let html = "";
            let keys = Array.from(new Set([...Object.keys(fObj), ...Object.keys(iObj)]));
            
            keys.forEach((key, index) => {
                html += `<div class="${index > 0 ? 'mt-3.5 pt-3.5 border-t border-gray-200' : ''}">`;
                if (key) {
                    const headerClass = isNew 
                        ? 'font-bold text-xs text-green-700 uppercase mb-2.5 pb-1.5 border-b border-green-200' 
                        : 'font-bold text-xs text-gray-700 uppercase mb-2.5 pb-1.5 border-b border-gray-200';
                    html += `<div class="${headerClass}">${key}</div>`;
                }
                
                let findingsText = "";
                let impressionText = "";
                
                if (fObj[key]) {
                    if (typeof fObj[key] === 'object' && fObj[key] !== null) {
                        findingsText = fObj[key].findings || "";
                        if (fObj[key].impression) impressionText = fObj[key].impression;
                    } else {
                        findingsText = fObj[key];
                    }
                }
                
                if (iObj[key]) {
                    if (typeof iObj[key] === 'object' && iObj[key] !== null) {
                        if (!impressionText) impressionText = iObj[key].impression || "";
                    } else {
                        if (!impressionText) impressionText = iObj[key];
                    }
                }

                if (findingsText) html += `<div class="mb-2.5"><div class="font-bold text-gray-600 text-[10px] uppercase">FINDINGS:</div><p class="mt-0.5 text-gray-700 whitespace-pre-line">${findingsText}</p></div>`;
                if (impressionText) html += `<div class="mb-1"><div class="font-bold text-gray-600 text-[10px] uppercase">IMPRESSION:</div><p class="mt-0.5 text-gray-700 whitespace-pre-line">${impressionText}</p></div>`;
                
                html += `</div>`;
            });
            return html || '<p class="text-xs text-gray-400 italic">No findings available.</p>';
        }

        document.getElementById('verify-old-findings').innerHTML = parseToHtml(oldFindings, oldImpression, examType, false);
        document.getElementById('verify-new-findings').innerHTML = parseToHtml(newFindings, newImpression, examType, true);
        findingsCont.classList.remove('hidden');
    } else {
        findingsCont.classList.add('hidden');
    }

    document.getElementById('verify-release-modal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function closeVerifyReleaseModal() {
    document.getElementById('verify-release-modal').classList.add('hidden');
}

function submitAmendedRelease() {
    let disputeId = (currentVerifyData && currentVerifyData.id) 
        ? currentVerifyData.id 
        : document.getElementById('verify-release-dispute-id')?.value;

    if (!disputeId) {
        Swal.fire({
            title: 'Error',
            text: 'Dispute record ID not found.',
            icon: 'error',
            customClass: { container: '!z-[999999]' }
        });
        return;
    }

    Swal.fire({
        title: 'Release Amended Report?',
        text: 'The amended radiological report will be finalized and officially released to the patient.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Release Report',
        cancelButtonText: 'Cancel',
        customClass: {
            container: '!z-[999999]',
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl font-bold px-4 py-2',
            cancelButton: 'rounded-xl font-semibold px-4 py-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = document.querySelector('#verify-release-modal button[onclick="submitAmendedRelease()"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Releasing...';
            }

            const fd = new FormData();
            fd.append('dispute_id', disputeId);

            fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/disputes.php?action=resolve_dispute', {
                method: 'POST', body: fd
            })
            .then(r => r.json())
            .then(res => {
                closeVerifyReleaseModal();
                if (res.success) {
                    Swal.fire({
                        title: 'Released!',
                        text: 'Amended report has been verified and released to the patient.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                    }).then(() => location.reload());
                } else {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Confirm &amp; Release Amended Report';
                        if (window.lucide) lucide.createIcons();
                    }
                    Swal.fire({
                        title: 'Error',
                        text: res.message || 'An error occurred.',
                        icon: 'error',
                        customClass: { container: '!z-[999999]' }
                    });
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Confirm &amp; Release Amended Report';
                    if (window.lucide) lucide.createIcons();
                }
                Swal.fire({
                    title: 'Error',
                    text: 'Connection or server error occurred.',
                    icon: 'error',
                    customClass: { container: '!z-[999999]' }
                });
            });
        }
    });
}

function confirmReupload(caseId) {
    Swal.fire({
        title: 'Re-upload & Correct?',
        text: 'Makakapag-upload ka ng bagong X-ray image at mababago ang exam details para sa kasong ito.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl font-bold px-4 py-2',
            cancelButton: 'rounded-xl font-semibold px-4 py-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-details&id=' + caseId + '&from=disputes';
        }
    });
}

function submitEscalation(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/disputes.php?action=escalate_to_radiologist', {
        method: 'POST', body: fd
    }).then(r=>r.json()).then(res=>{
        closeEscalateModal();
        if(res.success){
            Swal.fire('Escalated', 'The ticket is now forwarded to the Radiologist.', 'success').then(()=>location.reload());
        }else{
            Swal.fire('Error', res.message || 'An error occurred.', 'error');
        }
    });
}
function submitResolution(e) {
    if (e && e.preventDefault) e.preventDefault();
    const disputeId = document.getElementById('resolve-dispute-id').value;
    if (!disputeId) return;

    const fd = new FormData();
    fd.append('dispute_id', disputeId);

    // If in demographics mode, pass the names as well
    const firstName = document.getElementById('resolve-first-name')?.value;
    const lastName = document.getElementById('resolve-last-name')?.value;
    const middleName = document.getElementById('resolve-middle-name')?.value;
    if (firstName) fd.append('first_name', firstName);
    if (lastName) fd.append('last_name', lastName);
    if (middleName) fd.append('middle_name', middleName);

    fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/disputes.php?action=resolve_dispute', {
        method: 'POST', body: fd
    }).then(r=>r.json()).then(res=>{
        closeResolveModal();
        if(res.success){
            Swal.fire('Resolved', 'The dispute ticket is now resolved and the case has been updated.', 'success').then(()=>location.reload());
        }else{
            Swal.fire('Error', res.message || 'An error occurred.', 'error');
        }
    });
}

// Real-time polling for RadTech Patient Error Reports
setInterval(() => {
    const isDisputesTab = new URLSearchParams(window.location.search).get('tab') === 'disputes';
    const escModal = document.getElementById('escalate-dispute-modal');
    const fixModal = document.getElementById('fix-demographics-modal');
    const verModal = document.getElementById('verify-release-modal');
    const isEscalateOpen = escModal && !escModal.classList.contains('hidden');
    const isFixOpen = fixModal && !fixModal.classList.contains('hidden');
    const isVerOpen = verModal && !verModal.classList.contains('hidden');

    if (isDisputesTab && !isEscalateOpen && !isFixOpen && !isVerOpen) {
        fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists&tab=disputes')
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newTbody = doc.getElementById('disputes-table-body');
                const oldTbody = document.getElementById('disputes-table-body');
                if (newTbody && oldTbody) {
                    paginateDisputes(newTbody);
                    oldTbody.innerHTML = newTbody.innerHTML;
                    if (window.lucide) lucide.createIcons();
                }

                const newBadge = doc.getElementById('radtech-disputes-tab-badge');
                const curBadge = document.getElementById('radtech-disputes-tab-badge');
                if (newBadge && curBadge) {
                    curBadge.innerHTML = newBadge.innerHTML;
                    if (newBadge.title) curBadge.title = newBadge.title;
                }
            })
            .catch(console.error);
    }
}, 5000);
</script>