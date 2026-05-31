<?php
require_once __DIR__ . '/../../../config/database.php';

require_once __DIR__ . '/../../../models/BranchModel.php';
require_once __DIR__ . '/../../../models/CaseModel.php';

$branchModel = new \BranchModel($pdo);
$caseModel = new \CaseModel($pdo);

// Fetch all branches
$branchesList = $branchModel->getAllBranches();

// Fetch all pending cases (Standardized via Model)
$records = $caseModel->getWorklist(null, null, ['Pending', 'Under Reading'], true);

// Extract unique priorities for filters
$priorities = array_unique(array_column($records, 'priority'));
sort($priorities);
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div class="ml-5">
        <h2 id="worklist-title" class="text-2xl font-bold text-gray-900">Worklist</h2>
        <p id="worklist-subtitle" class="text-sm text-gray-500 mt-1">Manage pending cases across all branches</p>
    </div>
</div>

<!-- Controls: Search, Filter, Sort -->
<div class="mt-6 flex flex-col gap-4 px-4">
    <div class="flex flex-wrap gap-4 items-center">
        <!-- Search -->
        <div class="relative flex-1 min-w-[250px] group" style="position: relative; flex: 1 1 0%;">
            <div
                style="position: absolute; inset-y: 0; left: 0; padding-left: 1rem; display: flex; align-items: center; pointer-events: none; height: 100%; top: 0;">
                <i data-lucide="search" class="text-gray-400 group-hover:text-red-500 transition-colors"
                    style="width: 1.1rem; height: 1.1rem;"></i>
            </div>
            <input type="text" id="searchInput" placeholder="Search by case no, patient name, branch..."
                style="padding-left: 2.75rem !important;"
                class="block w-full pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
        </div>

        <!-- Filter by Branch -->
        <select id="filterBranch"
            class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="">All Branches</option>
            <?php foreach ($branchesList as $b): ?>
                <option value="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Filter by Priority -->
        <select id="filterPriority"
            class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="">All Priorities</option>
            <option value="Emergency">Emergency</option>
            <option value="Urgent">Urgent</option>
            <option value="Routine">Routine</option>
        </select>

        <!-- Sort by -->
        <select id="sortOption"
            class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="date_desc">Newest Record</option>
            <option value="date_asc">Oldest Record</option>
            <option value="branch_asc">Branch (A-Z)</option>
            <option value="branch_desc">Branch (Z-A)</option>
            <option value="priority_desc">Priority (High-Low)</option>
            <option value="priority_asc">Priority (Low-High)</option>
        </select>
    </div>
</div>

<div class="px-4">
    <div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Branch</th>
                        <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                        <th class="text-left font-semibold px-3 py-3 truncate max-w-[150px]">Exam Type</th>
                        <th class="text-left font-semibold px-3 py-3">Priority</th>
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Date Submitted</th>
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Action</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 bg-white divide-y divide-gray-100">
                    <?php if (count($records) === 0): ?>
                        <tr class="empty-state-row">
                            <td colspan="7" class="text-center py-8 text-gray-500">
                                No pending cases.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $row):
                            // Map Priority Weight for sorting: Emergency > Urgent > Priority > Normal > Routine
                            $pWeight = 0;
                            if ($row['priority'] === 'Emergency')
                                $pWeight = 5;
                            elseif ($row['priority'] === 'Urgent')
                                $pWeight = 4;
                            elseif ($row['priority'] === 'Priority')
                                $pWeight = 3;
                            elseif ($row['priority'] === 'Normal')
                                $pWeight = 2;
                            else
                                $pWeight = 1;

                            $isEmergency = ($row['priority'] === 'Emergency') ? 1 : 0;
                            ?>
                            <tr class="hover:bg-white/10 transition-colors record-row cursor-pointer"
                                data-id="<?= htmlspecialchars($row['case_number']) ?>"
                                data-branch="<?= htmlspecialchars($row['branch_name']) ?>"
                                data-priority="<?= htmlspecialchars($row['priority']) ?>" data-emergency="<?= $isEmergency ?>"
                                data-pweight="<?= $pWeight ?>"
                                data-search="<?= htmlspecialchars(strtolower($row['case_number'] . ' ' . $row['first_name'] . ' ' . $row['last_name'] . ' ' . $row['branch_name'])) ?>"
                                data-date="<?= strtotime($row['created_at']) ?>">
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium"><?= htmlspecialchars($row['case_number']) ?></div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-600"><?= htmlspecialchars($row['branch_name']) ?></div>
                                </td>
                                <td class="py-3 px-3 truncate max-w-[200px]"
                                    title="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>">
                                    <div class="font-medium truncate">
                                        <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3 truncate max-w-[150px]" title="<?= htmlspecialchars($row['exam_type']) ?>">
                                    <?= htmlspecialchars($row['exam_type']) ?>
                                </td>
                                <td class="py-3 px-3">
                                    <?php
                                    $pColor = 'blue';
                                    if ($row['priority'] === 'Emergency')
                                        $pColor = 'red';
                                    if ($row['priority'] === 'Urgent')
                                        $pColor = 'yellow';
                                    if ($row['priority'] === 'Priority')
                                        $pColor = 'orange';
                                    ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-<?= $pColor ?>-400 bg-<?= $pColor ?>-50 px-2 py-1 text-xs font-semibold text-<?= $pColor ?>-700">
                                        <?= htmlspecialchars($row['priority']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        <?= date('M d, Y h:i A', strtotime($row['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <a href="/<?= PROJECT_DIR ?>/index.php?role=radiologist&page=case-review&id=<?= $row['id'] ?>&branch_id=<?= $row['branch_id'] ?>"
                                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-sm transition">
                                        <i data-lucide="microscope" class="w-4 h-4 mr-1"></i> Review Case
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Highlight row handling
    document.addEventListener('DOMContentLoaded', () => {
        const params = new window.URLSearchParams(window.location.search);
        const highlightId = params.get('highlight');
        if (highlightId) {
            setTimeout(() => {
                const rows = document.querySelectorAll('tr.record-row');
                let targetRow = null;
                rows.forEach(row => {
                    if ((row.dataset.id || '').toLowerCase() === highlightId.toLowerCase()) {
                        targetRow = row;
                    }
                });

                if (targetRow) {
                    const tableWrapper = targetRow.closest('.overflow-y-auto');
                    if (tableWrapper) {
                        tableWrapper.scrollTo({ top: targetRow.offsetTop - tableWrapper.offsetTop - 40, behavior: 'smooth' });
                    } else {
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    targetRow.style.transition = 'background-color 0.4s ease';
                    targetRow.style.backgroundColor = '#fef08a';
                    setTimeout(() => targetRow.style.backgroundColor = '', 1500);
                }
            }, 150);
        }
    });

    // Search, Filter, Sort Logic
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const filterBranch = document.getElementById('filterBranch');
        const filterPriority = document.getElementById('filterPriority');
        const sortOption = document.getElementById('sortOption');
        const tbody = document.querySelector('tbody');
        let allRows = Array.from(document.querySelectorAll('tr.record-row'));

        function updateTable() {
            if (!searchInput || !filterBranch || !filterPriority || !sortOption) return;

            const searchTerm = searchInput.value.toLowerCase();
            const branchValue = filterBranch.value;
            const priorityValue = filterPriority.value;
            const sortValue = sortOption.value;

            // Update Title dynamically based on selected branch
            const worklistTitle = document.getElementById('worklist-title');
            const worklistSubtitle = document.getElementById('worklist-subtitle');
            if (worklistTitle && worklistSubtitle) {
                if (branchValue) {
                    worklistTitle.innerText = "Worklist - " + branchValue;
                    worklistSubtitle.innerText = "Manage pending cases for " + branchValue + " branch";
                } else {
                    worklistTitle.innerText = "Worklist";
                    worklistSubtitle.innerText = "Manage pending cases across all branches";
                }
            }

            // Sort rows
            allRows.sort((a, b) => {
                // Emergency ALWAYS first overrides everything
                const emA = parseInt(a.dataset.emergency);
                const emB = parseInt(b.dataset.emergency);
                if (emA !== emB) {
                    return emB - emA; // 1 before 0
                }

                // Normal sorting if neither is emergency, or if both are emergency
                let val;
                if (sortValue === 'date_desc') {
                    val = parseInt(b.dataset.date) - parseInt(a.dataset.date);
                } else if (sortValue === 'date_asc') {
                    val = parseInt(a.dataset.date) - parseInt(b.dataset.date);
                } else if (sortValue === 'branch_asc') {
                    val = a.dataset.branch.localeCompare(b.dataset.branch);
                } else if (sortValue === 'branch_desc') {
                    val = b.dataset.branch.localeCompare(a.dataset.branch);
                } else if (sortValue === 'priority_desc') {
                    val = parseInt(b.dataset.pweight) - parseInt(a.dataset.pweight);
                } else if (sortValue === 'priority_asc') {
                    val = parseInt(a.dataset.pweight) - parseInt(b.dataset.pweight);
                }
                // Secondary sort by date just in case
                if (val === 0) {
                    return parseInt(b.dataset.date) - parseInt(a.dataset.date);
                }
                return val;
            });

            // Apply filtering and sorting to DOM
            let visibleCount = 0;
            allRows.forEach(row => {
                const matchesSearch = row.dataset.search.includes(searchTerm);
                const matchesBranch = branchValue === '' || row.dataset.branch === branchValue;
                let rowPriority = row.dataset.priority;
                let mappedPriority = rowPriority;
                if (rowPriority === 'Normal' || rowPriority === 'Priority') {
                    mappedPriority = 'Routine';
                }
                const matchesPriority = priorityValue === '' || rowPriority === priorityValue || mappedPriority === priorityValue;

                if (matchesSearch && matchesBranch && matchesPriority) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }

                tbody.appendChild(row); // Reorders them in the DOM
            });

            // Handle "No records found" state
            let noRecordsRow = tbody.querySelector('.no-records');
            let emptyStateRow = tbody.querySelector('.empty-state-row');

            if (emptyStateRow && emptyStateRow.style.display !== 'none') {
                return;
            }

            if (visibleCount === 0 && allRows.length > 0) {
                if (!noRecordsRow) {
                    noRecordsRow = document.createElement('tr');
                    noRecordsRow.className = 'no-records';
                    noRecordsRow.innerHTML = `<td colspan="7" class="text-center py-8 text-gray-500">No matching records found.</td>`;
                    tbody.appendChild(noRecordsRow);
                } else {
                    noRecordsRow.style.display = '';
                    tbody.appendChild(noRecordsRow);
                }
            } else if (noRecordsRow) {
                noRecordsRow.style.display = 'none';
            }
        }

        const paramsList = new window.URLSearchParams(window.location.search);
        const urlBranch = paramsList.get('branch');
        const urlPriority = paramsList.get('priority');

        if (urlBranch && filterBranch) filterBranch.value = urlBranch;
        if (urlPriority && filterPriority) filterPriority.value = urlPriority;

        if (searchInput) searchInput.addEventListener('input', updateTable);
        if (filterBranch) filterBranch.addEventListener('change', updateTable);
        if (filterPriority) filterPriority.addEventListener('change', updateTable);
        if (sortOption) sortOption.addEventListener('change', updateTable);

        // Initial sort
        updateTable();

        // ensure lucide icons are created if not already
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>