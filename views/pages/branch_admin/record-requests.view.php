<?php
/**
 * Incoming Record Requests View (Branch Admin)
 * Backend logic handled by RecordRequestsController.php
 */
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 text-gray-900">
        <div>
            <h2 class="text-xl font-bold tracking-tight">Incoming Record Requests</h2>
            <p class="text-sm text-gray-500 mt-1">Review requests from RadTechs in other branches asking for patient
                records stored in your branch (<?= htmlspecialchars($myBranchName) ?>).</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div
            class="p-4 rounded-lg flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
            <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
            <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Search & Filters -->
    <div class="flex flex-col md:flex-row gap-4">
        <div class="relative flex-1 group" style="position: relative; flex: 1 1 0%;">
            <div
                style="position: absolute; inset-y: 0; left: 0; padding-left: 1rem; display: flex; align-items: center; pointer-events: none; height: 100%; top: 0;">
                <i data-lucide="search" class="text-gray-400 group-hover:text-red-500 transition-colors"
                    style="width: 1.1rem; height: 1.1rem;"></i>
            </div>
            <input type="text" id="search-input" placeholder="Search by patient name or case number..."
                style="padding-left: 2.75rem !important;"
                class="block w-full pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
        </div>
        <select id="filter-branch"
            class="w-full md:w-48 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer shadow-sm">
            <option value="All">All Branches</option>
            <?php
            $branches = array_unique(array_column($pendingRequests, 'requester_branch_name'));
            sort($branches);
            foreach ($branches as $branch):
                ?>
                <option value="<?= htmlspecialchars($branch) ?>"><?= htmlspecialchars($branch) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="sort-date"
            class="w-full md:w-48 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer shadow-sm">
            <option value="Newest">Newest First</option>
            <option value="Oldest">Oldest First</option>
        </select>
    </div>

    <!-- Table Container -->
    <div id="record-requests-table"
        class="realtime-update rounded-xl border border-gray-300 bg-white shadow-sm overflow-hidden min-h-[400px] flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                        <th class="text-left font-semibold px-4 py-3.5 whitespace-nowrap">Patient Details</th>
                        <th class="text-left font-semibold px-4 py-3.5">Exam Type / Reason</th>
                        <th class="text-left font-semibold px-4 py-3.5">Requested By</th>
                        <th class="text-left font-semibold px-4 py-3.5">Date Requested</th>
                        <th class="text-center font-semibold px-4 py-3.5">Actions</th>
                    </tr>
                </thead>
                <tbody id="table-body" class="text-gray-800 divide-y divide-gray-100">
                    <?php if (empty($pendingRequests)): ?>
                        <tr>
                            <td colspan="5" class="py-24">
                                <div class="flex flex-col items-center justify-center text-center text-gray-500">
                                    <i data-lucide="file-text" class="w-12 h-12 mb-3 opacity-20"></i>
                                    <p class="text-base font-medium text-gray-900">No pending requests</p>
                                    <p class="text-sm">There are no incoming record requests from other branches.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingRequests as $req): ?>
                            <tr class="hover:bg-gray-50/80 transition-colors record-row"
                                data-name="<?= htmlspecialchars($req['patient_name']) ?>"
                                data-case="<?= htmlspecialchars($req['patient_no']) ?>"
                                data-branch="<?= htmlspecialchars($req['requester_branch_name']) ?>"
                                data-date="<?= htmlspecialchars($req['created_at']) ?>">
                                <td class="px-4 py-3.5">
                                    <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($req['patient_name']) ?>
                                    </div>
                                    <div class="text-[11px] text-gray-500">Case No: <?= htmlspecialchars($req['patient_no']) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-[13px] text-gray-900 font-bold"><?= htmlspecialchars($req['exam_type']) ?>
                                    </div>
                                    <div class="text-[11px] text-gray-500 line-clamp-2 mt-0.5"
                                        title="<?= htmlspecialchars($req['reason']) ?>">
                                        <?= htmlspecialchars($req['reason']) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-400">
                                        <?= htmlspecialchars($req['requester_branch_name'] ?: 'Unknown Branch') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-[13px] text-gray-500">
                                    <div class="font-medium text-gray-900"><?= date('F j, Y', strtotime($req['created_at'])) ?>
                                    </div>
                                    <div class="text-[11px] opacity-70"><?= date('h:i A', strtotime($req['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <form method="POST" action="" class="flex items-center justify-center gap-2">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <button type="button" name="action" value="Approve"
                                            class="p-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                            onclick="confirmFormAction(this, 'Approve', 'Confirm Approval', 'Would you like to confirm approving this record request? This will allow the requesting branch to view this patient\'s records.', 'action', event)"
                                            title="Approve Request">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" name="action" value="Deny"
                                            class="p-1.5 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                                            onclick="confirmFormAction(this, 'Deny', 'Confirm Denial', 'Would you like to confirm denying this record request?')"
                                            title="Deny Request">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 mt-auto">
            <span id="record-count" class="text-xs font-medium text-gray-500">
                Found <?= count($pendingRequests) ?> records
            </span>
            <div class="flex items-center gap-4">
                <button id="prev-btn"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i> Previous
                </button>
                <span id="page-info" class="text-xs font-bold text-gray-700">Page 1 of 1</span>
                <button id="next-btn"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    Next <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ROWS_PER_PAGE = 8;
        let currentPage = 1;

        const searchInput = document.getElementById('search-input');
        const filterBranch = document.getElementById('filter-branch');
        const sortDate = document.getElementById('sort-date');
        const tableBody = document.getElementById('table-body');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const pageInfo = document.getElementById('page-info');
        const recordCountInfo = document.getElementById('record-count');

        function getFilteredRows() {
            const searchTerm = searchInput.value.toLowerCase();
            const branchFilter = filterBranch.value;
            const sortOrder = sortDate.value;
            const rows = Array.from(tableBody.querySelectorAll('tr.record-row'));

            // Sorting
            rows.sort((a, b) => {
                const dateA = new Date(a.getAttribute('data-date'));
                const dateB = new Date(b.getAttribute('data-date'));
                return sortOrder === 'Newest' ? dateB - dateA : dateA - dateB;
            });

            // Re-append to DOM
            rows.forEach(row => tableBody.appendChild(row));

            // Filtering
            return rows.filter(row => {
                const name = row.getAttribute('data-name').toLowerCase();
                const caseNo = row.getAttribute('data-case').toLowerCase();
                const branch = row.getAttribute('data-branch');

                const matchesSearch = name.includes(searchTerm) || caseNo.includes(searchTerm);
                const matchesBranch = branchFilter === 'All' || branch === branchFilter;

                return matchesSearch && matchesBranch;
            });
        }

        function renderPage() {
            const filteredRows = getFilteredRows();
            const rows = Array.from(tableBody.querySelectorAll('tr.record-row'));
            const totalFiltered = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(totalFiltered / ROWS_PER_PAGE));

            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
            const endIdx = startIdx + ROWS_PER_PAGE;

            rows.forEach(r => r.style.display = 'none');
            filteredRows.slice(startIdx, endIdx).forEach(row => row.style.display = '');

            // Empty state
            let emptyState = document.getElementById('filtered-empty-state');
            if (totalFiltered === 0 && rows.length > 0) {
                if (!emptyState) {
                    emptyState = document.createElement('tr');
                    emptyState.id = 'filtered-empty-state';
                    emptyState.innerHTML = `
                    <td colspan="5" class="py-24">
                        <div class="flex flex-col items-center justify-center text-center text-gray-500">
                            <i data-lucide="search-x" class="w-12 h-12 mb-3 opacity-20"></i>
                            <p class="text-base font-medium text-gray-900">No requests match your filters</p>
                            <p class="text-sm">Try adjusting your keywords or branch filter.</p>
                        </div>
                    </td>`;
                    tableBody.appendChild(emptyState);
                    if (window.lucide) window.lucide.createIcons();
                }
                emptyState.style.display = '';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }

            const displayStart = totalFiltered === 0 ? 0 : startIdx + 1;
            const displayEnd = Math.min(endIdx, totalFiltered);

            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            recordCountInfo.textContent = `Showing ${displayStart}–${displayEnd} of ${totalFiltered} records`;

            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
        }

        function applyFilters() { currentPage = 1; renderPage(); }

        searchInput.addEventListener('input', applyFilters);
        filterBranch.addEventListener('change', applyFilters);
        sortDate.addEventListener('change', applyFilters);
        prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
        nextBtn.addEventListener('click', () => {
            if (currentPage < Math.ceil(getFilteredRows().length / ROWS_PER_PAGE)) {
                currentPage++; renderPage();
            }
        });

        renderPage();
    });
</script>