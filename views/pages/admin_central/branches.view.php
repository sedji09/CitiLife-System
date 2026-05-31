<?php
/**
 * Branch Management View (Central Admin)
 * Backend logic handled by BranchesController.php
 */
?>

<main class="flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Branch Management</h1>
                <p class="text-sm text-gray-500">Create and manage facility locations across the system</p>
            </div>
            <button type="button" onclick="openAddBranchModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition-all active:scale-95">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                Add New Branch
            </button>
        </div>

        <?php if ($success): ?>
            <div id="statusAlert"
                class="rounded-xl bg-green-50 border border-green-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div id="statusAlert"
                class="rounded-xl bg-red-50 border border-red-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search & Filters -->
        <div class="flex flex-col md:flex-row gap-3 items-center">
            <div class="relative flex-1 w-full">
                <input type="text" id="branchSearch" oninput="filterAndSortBranches()"
                    placeholder="Search by branch name..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-gray-400"></i>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <select id="statusFilter" onchange="filterAndSortBranches()"
                    class="flex-1 md:w-48 px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Branches Table Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200">
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Branch Name</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Created Date</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Status</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="branchesTableBody" class="divide-y divide-gray-100">
                        <?php if (empty($branches)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <i data-lucide="building-2" class="w-10 h-10 text-gray-200"></i>
                                        <p>No branches found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- No Results Placeholder Row -->
                            <tr id="noResultsRow" class="hidden">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div
                                            class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-2">
                                            <i data-lucide="search-x" class="w-8 h-8 text-gray-300"></i>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-800">No matching branches</h3>
                                        <p class="text-xs text-gray-500">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php foreach ($branches as $b): ?>
                                <tr class="hover:bg-gray-50/30 transition-colors group branch-row"
                                    data-name="<?= htmlspecialchars(strtolower($b['name'])) ?>"
                                    data-address="<?= htmlspecialchars(strtolower($b['address'] ?? '')) ?>"
                                    data-status="<?= htmlspecialchars($b['status']) ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-8 w-8 rounded-lg bg-red-100 flex items-center justify-center text-red-700 font-bold text-[11px] uppercase">
                                                <i data-lucide="building" class="w-4 h-4"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-bold text-gray-800 tracking-tight"><?= htmlspecialchars($b['name']) ?></span>
                                                <span class="text-[10px] text-gray-400 font-medium tracking-tight">
                                                    <?= htmlspecialchars($b['address'] ?? 'No Address Provided') ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-600 font-medium">
                                            <?= date('M d, Y', strtotime($b['created_at'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $badgeClass = $b['status'] === 'Active'
                                            ? 'bg-green-50 text-green-600 ring-green-100'
                                            : 'bg-red-50 text-red-600 ring-red-100';
                                        ?>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold ring-1 ring-inset <?= $badgeClass ?>">
                                            <?= htmlspecialchars($b['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <?php if ($b['status'] === 'Active'): ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle-status">
                                                    <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                                                    <input type="hidden" name="new_status" value="Inactive">
                                                    <button type="submit"
                                                        class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-orange-500 hover:border-orange-200 hover:bg-orange-50 transition shadow-sm"
                                                        title="Deactivate">
                                                        <i data-lucide="minus-circle" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle-status">
                                                    <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                                                    <input type="hidden" name="new_status" value="Active">
                                                    <button type="submit"
                                                        class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-green-500 hover:border-green-200 hover:bg-green-50 transition shadow-sm"
                                                        title="Activate">
                                                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button type="button"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($b)) ?>)"
                                                class="p-1.5 rounded-md border border-blue-100 bg-blue-50 text-blue-500 hover:bg-blue-100 transition shadow-sm"
                                                title="Edit Branch">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>

                                            <button type="button"
                                                onclick="confirmDelete(<?= $b['id'] ?>, '<?= htmlspecialchars($b['name']) ?>')"
                                                class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition shadow-sm"
                                                title="Delete Branch">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
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
            <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">
                <div class="text-xs text-gray-500 font-medium">
                    Showing <span id="startIndex">0</span>-<span id="endIndex">0</span> of <span
                        id="totalRecords">0</span> records
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="changePage(-1)" id="prevBtn"
                        class="flex items-center gap-1 px-4 py-2 border border-gray-200 rounded-lg bg-white text-xs font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        Previous
                    </button>
                    <div class="px-4 py-3">
                        <span class="text-xs font-medium text-gray-500">Page <span id="currentPageDisplay">1</span> of
                            <span id="totalPagesDisplay">1</span></span>
                    </div>
                    <button onclick="changePage(1)" id="nextBtn"
                        class="flex items-center gap-1 px-4 py-2 border border-gray-200 rounded-lg bg-white text-xs font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                        Next
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ADD BRANCH MODAL -->
<div id="addBranchModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Create New Branch</h3>
            <button type="button" onclick="closeAddBranchModal()"
                class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4" autocomplete="off">
            <input type="hidden" name="action" value="create">
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch Name</label>
                <div class="relative">
                    <i data-lucide="building" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="name" name="name" required placeholder="e.g. Cabanatuan Branch" autocomplete="off"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>
            <div>
                <label for="address" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch Address</label>
                <div class="relative">
                    <i data-lucide="map-pin" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="address" name="address" placeholder="e.g. 123 Main St, City" autocomplete="off"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>
            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="button" onclick="closeAddBranchModal()"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-red-600 text-sm font-bold text-white hover:bg-red-700 shadow-sm shadow-red-200 transition-all active:scale-95">
                    Create Branch
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT BRANCH MODAL -->
<div id="editBranchModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Edit Branch</h3>
            <button type="button" onclick="closeEditBranchModal()"
                class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4" autocomplete="off">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="branch_id" id="edit_branch_id">
            <div>
                <label for="edit_name" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch Name</label>
                <div class="relative">
                    <i data-lucide="building" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="edit_name" name="name" required
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>
            <div>
                <label for="edit_address" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch
                    Address</label>
                <div class="relative">
                    <i data-lucide="map-pin" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="edit_address" name="address"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>
            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="button" onclick="closeEditBranchModal()"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-blue-600 text-sm font-bold text-white hover:bg-blue-700 shadow-sm shadow-blue-200 transition-all active:scale-95">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal Removed (Replaced by SweetAlert2) -->

<script>
    /* ── Dark-mode theming applied via JS inline styles (100% reliable) ── */
    function applyModalTheme(modalId) {
        const isDark = document.documentElement.classList.contains('theme-dark');
        const modal  = document.getElementById(modalId);
        if (!modal) return;

        const card      = modal.querySelector(':scope > div');
        const header    = card  ? card.querySelector(':scope > div:first-child') : null;
        const title     = header ? header.querySelector('h3') : null;
        const inputs    = modal.querySelectorAll('input[type="text"]');
        const labels    = modal.querySelectorAll('label');
        const footer    = modal.querySelector('form > div:last-child');
        const cancelBtn = footer ? footer.querySelector('button:first-of-type') : null;
        const icons     = modal.querySelectorAll('form i[data-lucide]');

        if (isDark) {
            if (card)   { card.style.backgroundColor  = '#1e293b'; }
            if (header) { header.style.backgroundColor = '#1e293b'; header.style.borderBottomColor = '#334155'; }
            if (title)  { title.style.color = '#f1f5f9'; }
            labels.forEach(l  => { l.style.color = '#cbd5e1'; });
            inputs.forEach(i  => { i.style.backgroundColor = '#0f172a'; i.style.borderColor = '#475569'; i.style.color = '#f1f5f9'; });
            if (footer)    { footer.style.borderTopColor = '#334155'; }
            if (cancelBtn) { cancelBtn.style.borderColor = '#64748b'; cancelBtn.style.color = '#f1f5f9'; cancelBtn.style.backgroundColor = 'transparent'; }
            icons.forEach(ic => { ic.style.color = '#64748b'; });
        } else {
            if (card)   { card.style.backgroundColor  = ''; }
            if (header) { header.style.backgroundColor = ''; header.style.borderBottomColor = ''; }
            if (title)  { title.style.color = ''; }
            labels.forEach(l  => { l.style.color = ''; });
            inputs.forEach(i  => { i.style.backgroundColor = ''; i.style.borderColor = ''; i.style.color = ''; });
            if (footer)    { footer.style.borderTopColor = ''; }
            if (cancelBtn) { cancelBtn.style.borderColor = ''; cancelBtn.style.color = ''; cancelBtn.style.backgroundColor = ''; }
            icons.forEach(ic => { ic.style.color = ''; });
        }
    }

    function openAddBranchModal() {
        // Clear inputs to prevent lingering values
        document.getElementById('name').value = '';
        document.getElementById('address').value = '';
        
        document.getElementById('addBranchModal').classList.remove('hidden');
        applyModalTheme('addBranchModal');
    }

    function closeAddBranchModal() {
        document.getElementById('addBranchModal').classList.add('hidden');
    }

    function openEditModal(branch) {
        document.getElementById('edit_branch_id').value = branch.id;
        document.getElementById('edit_name').value = branch.name;
        document.getElementById('edit_address').value = branch.address || '';
        document.getElementById('editBranchModal').classList.remove('hidden');
        applyModalTheme('editBranchModal');
    }

    function closeEditBranchModal() {
        document.getElementById('editBranchModal').classList.add('hidden');
    }

    async function confirmDelete(id, name) {
        const result = await confirmAlert('Delete Branch', `Are you sure you want to delete the branch "${name}"? This action cannot be undone.`, 'Yes, Delete');
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'branch_id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Pagination State
    let currentPage = 1;
    const itemsPerPage = 7;

    function filterAndSortBranches(resetPage = true) {
        if (resetPage) currentPage = 1;

        const branchSearch = document.getElementById('branchSearch');
        const statusFilter = document.getElementById('statusFilter');
        const tableBody = document.getElementById('branchesTableBody');

        if (!branchSearch || !tableBody) return;

        const query = branchSearch.value.toLowerCase();
        const status = statusFilter.value;

        let rows = Array.from(document.querySelectorAll('.branch-row'));

        let visibleCount = 0;
        rows.forEach(row => {
            const rowName = (row.dataset.name || "").toLowerCase();
            const rowAddress = (row.dataset.address || "").toLowerCase();
            const rowStatus = row.dataset.status || "";

            const matchesSearch = rowName.includes(query) || rowAddress.includes(query);
            const matchesStatus = status === "" || rowStatus.trim() === status;

            if (matchesSearch && matchesStatus) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            if (visibleCount === 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }
        }

        updatePagination(rows.filter(r => !r.classList.contains('hidden')));
    }

    function updatePagination(visibleRows) {
        const totalRecords = visibleRows.length;
        const totalPages = Math.ceil(totalRecords / itemsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = Math.min(startIdx + itemsPerPage, totalRecords);

        visibleRows.forEach((row, index) => {
            if (index >= startIdx && index < endIdx) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });

        const startIndexEl = document.getElementById('startIndex');
        const endIndexEl = document.getElementById('endIndex');
        const totalRecordsEl = document.getElementById('totalRecords');
        const currentPageDisplay = document.getElementById('currentPageDisplay');
        const totalPagesDisplay = document.getElementById('totalPagesDisplay');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if (startIndexEl) startIndexEl.innerText = totalRecords === 0 ? 0 : startIdx + 1;
        if (endIndexEl) endIndexEl.innerText = endIdx;
        if (totalRecordsEl) totalRecordsEl.innerText = totalRecords;
        if (currentPageDisplay) currentPageDisplay.innerText = currentPage;
        if (totalPagesDisplay) totalPagesDisplay.innerText = totalPages;

        if (prevBtn) prevBtn.disabled = (currentPage === 1);
        if (nextBtn) nextBtn.disabled = (currentPage === totalPages || totalRecords === 0);
    }

    function changePage(delta) {
        currentPage += delta;
        filterAndSortBranches(false);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
        filterAndSortBranches(false);

        const alert = document.getElementById('statusAlert');
        if (alert) {
            setTimeout(() => {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            }, 3000);
        }
    });
</script>