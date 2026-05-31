<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/CaseModel.php';
require_once __DIR__ . '/../../../models/BranchModel.php';

$caseModel = new \CaseModel($pdo);
$branchModel = new \BranchModel($pdo);

$branchId = $_GET['branch_id'] ?? 0;

// 1. Fetch data through models
$branch = $branchModel->getBranchById($branchId);
$branchName = $branch['name'] ?? 'Unknown Branch';

// Fetch cases for this branch that are 'Pending' or 'Under Reading' and images are 'Uploaded'
$records = $caseModel->getWorklist($branchId, null, ['Pending', 'Under Reading'], true);

// Extract unique exam types for the filter dropdown
$examTypes = array_unique(array_column($records, 'exam_type'));
sort($examTypes);
?>

<!-- Header -->
<div class="flex items-center gap-4 py-2 mb-4">
    <a href="?role=radiologist&page=worklist" class="text-gray-500 hover:text-gray-900 transition mt-1">
        <i data-lucide="arrow-left" class="w-6 h-6"></i>
    </a>
  <div>
    <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($branchName) ?> Pending Queue</h2>
    <p class="text-sm text-gray-500 mt-1">Select a patient case to review and submit findings</p>
  </div>
</div>

<!-- Controls: Search, Filter, Sort -->
<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <!-- Search -->
        <div class="relative flex-1 group" style="position: relative; flex: 1 1 0%;">
            <div style="position: absolute; inset-y: 0; left: 0; padding-left: 1rem; display: flex; align-items: center; pointer-events: none; height: 100%; top: 0;">
                <i data-lucide="search" class="text-gray-400 group-hover:text-red-500 transition-colors" style="width: 1.1rem; height: 1.1rem;"></i>
            </div>
            <input type="text" id="searchInput" placeholder="Search by case no, patient no, patient name..."
                style="padding-left: 2.75rem !important;"
                class="block w-full pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
        </div>
        
        <!-- Filter by Exam Type -->
        <select id="filterExam" class="w-48 px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 text-sm bg-white cursor-pointer shadow-sm">
            <option value="">All Exam Types</option>
            <?php foreach ($examTypes as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
            <?php endforeach; ?>
        </select>
        
        <select id="sortDate" class="w-48 px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 text-sm bg-white cursor-pointer shadow-sm">
            <option value="desc">Newest Record</option>
            <option value="asc">Oldest Record</option>
        </select>
    </div>
</div>

<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
    <div class="overflow-x-auto overflow-y-auto max-h-[480px]">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10">
            <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
            <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
            <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Patient No.</th>
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
                        No pending cases for this branch.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $row): ?>
                 <tr class="hover:bg-white/10 transition-colors record-row cursor-pointer" 
                     data-id="<?= htmlspecialchars($row['case_number']) ?>"
                     data-exam-type="<?= htmlspecialchars($row['exam_type']) ?>"
                     data-search="<?= htmlspecialchars(strtolower($row['case_number'] . ' ' . ($row['patient_number'] ?? '') . ' ' . $row['first_name'] . ' ' . $row['last_name'])) ?>"
                     data-date="<?= strtotime($row['created_at']) ?>">
                    <td class="py-3 px-3 whitespace-nowrap">
                        <div class="font-medium"><?= htmlspecialchars($row['case_number']) ?></div>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        <div class="font-medium"><?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?></div>
                    </td>
                    <td class="py-3 px-3 truncate max-w-[200px]" title="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>">
                        <div class="font-medium truncate"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                    </td>
                    <td class="py-3 px-3 truncate max-w-[150px]" title="<?= htmlspecialchars($row['exam_type']) ?>"><?= htmlspecialchars($row['exam_type']) ?></td>
                    <td class="py-3 px-3">
                        <?php
                            $pColor = 'blue';
                            if ($row['priority'] === 'Emergency') $pColor = 'red';
                            if ($row['priority'] === 'Urgent') $pColor = 'yellow';
                            if ($row['priority'] === 'Priority') $pColor = 'orange';
                        ?>
                        <span class="inline-flex items-center rounded-full border border-<?= $pColor ?>-400 bg-<?= $pColor ?>-50 px-2 py-1 text-xs font-semibold text-<?= $pColor ?>-700">
                            <?= htmlspecialchars($row['priority']) ?>
                        </span>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        <a href="/<?= PROJECT_DIR ?>/index.php?role=radiologist&page=case-review&id=<?= $row['id'] ?>&branch_id=<?= $branchId ?>" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-sm transition">
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

<script>
// ── Highlight row from notification ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const params = new window.URLSearchParams(window.location.search);
    const highlightId = params.get('highlight');
    if (!highlightId) return;

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
            banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Case <strong>${highlightId}</strong> is highlighted below.</span></div>`;
            banner.style.cssText = 'margin-bottom:1rem;padding:0.75rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
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
    }, 150);
});

// ── Search, Filter, Sort Logic ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const filterExam = document.getElementById('filterExam');
    const sortDate = document.getElementById('sortDate');
    const tbody = document.querySelector('tbody');
    let allRows = Array.from(document.querySelectorAll('tr.record-row'));

    function updateTable() {
        if (!searchInput || !filterExam || !sortDate) return;
        
        const searchTerm = searchInput.value.toLowerCase();
        const filterValue = filterExam.value;
        const sortValue = sortDate.value;
        
        // Sort rows
        allRows.sort((a, b) => {
            const dateA = parseInt(a.dataset.date);
            const dateB = parseInt(b.dataset.date);
            return sortValue === 'desc' ? dateB - dateA : dateA - dateB;
        });
        
        // Apply filtering and sorting to DOM
        let visibleCount = 0;
        allRows.forEach(row => {
            const matchesSearch = row.dataset.search.includes(searchTerm);
            const matchesFilter = filterValue === '' || row.dataset.examType === filterValue;
            
            if (matchesSearch && matchesFilter) {
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
            return; // No records to begin with
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

    if (searchInput) searchInput.addEventListener('input', updateTable);
    if (filterExam) filterExam.addEventListener('change', updateTable);
    if (sortDate) sortDate.addEventListener('change', updateTable);
    
    // ensure lucide icons are created if not already
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
