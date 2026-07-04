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
                <h1 class="text-2xl font-semibold text-gray-900">Branch Management</h1>
                <p class="text-sm text-gray-500">Create and manage facility locations across the system</p>
            </div>
            <button type="button" onclick="openAddBranchModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition-all active:scale-95">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                Add New Branch
            </button>
        </div>

        <?php
        $activeBranchesCount = 0;
        $totalBranchesCount = count($branches);
        foreach ($branches as $b) {
            if ($b['status'] === 'Active') $activeBranchesCount++;
        }
        ?>
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Branches</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $activeBranchesCount ?></h3>
                </div>
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                    <i data-lucide="building-2" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Branches</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $totalBranchesCount ?></h3>
                </div>
                <div class="p-3 bg-gray-50 text-gray-600 rounded-lg">
                    <i data-lucide="list" class="w-6 h-6"></i>
                </div>
            </div>
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
        <div id="branches-table-card" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200">
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Branch Name</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Created Date</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Status</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500 text-left">Actions</th>
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
                                        <div class="flex items-center justify-start gap-1.5">
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
            <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
                <div class="text-xs text-gray-500">
                    Showing <span id="startIndex" class="font-semibold text-gray-800">0</span> to <span id="endIndex" class="font-semibold text-gray-800">0</span> of <span
                        id="totalRecords" class="font-semibold text-gray-800">0</span> records
                </div>
                <div class="flex items-center flex-wrap gap-1.5" id="paginationControls">
                    <!-- Dynamic page buttons will be inserted here -->
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
                    <input type="text" id="name" name="name" required placeholder="e.g. Cabanatuan Branch"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>
            <div>
                <label for="address" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch Address</label>
                <div class="relative">
                    <i data-lucide="map-pin" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="address" name="address" placeholder="e.g. 123 Main St, City"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>
            <div>
                <label for="additional_address" class="block text-sm font-semibold text-gray-700 mb-1.5">Additional
                    Address / Direction</label>
                <div class="relative">
                    <i data-lucide="map" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="additional_address" name="additional_address"
                        placeholder="e.g. In front of Hospital" autocomplete="off"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-semibold text-gray-700">Contact Numbers</label>
                    <button type="button" onclick="addContactField('add')" id="add_btn_add"
                        class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1 transition-colors">
                        <i data-lucide="plus" class="w-3 h-3"></i> Add Number
                    </button>
                </div>
                <div class="space-y-2">
                    <div id="add_contact_1_wrapper">
                        <input type="text" id="contact_number_1" name="contact_number_1"
                            placeholder="Contact 1 (e.g. 0919-...)"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                    </div>
                    <div id="add_contact_2_wrapper" class="hidden relative">
                        <input type="text" id="contact_number_2" name="contact_number_2"
                            placeholder="Contact 2 (e.g. 0919-...)"
                            class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <button type="button" onclick="removeContactField('add', 2)"
                            class="absolute right-3 top-3 text-gray-400 hover:text-red-500 transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div id="add_contact_3_wrapper" class="hidden relative">
                        <input type="text" id="contact_number_3" name="contact_number_3"
                            placeholder="Contact 3 (e.g. 0919-...)"
                            class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <button type="button" onclick="removeContactField('add', 3)"
                            class="absolute right-3 top-3 text-gray-400 hover:text-red-500 transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
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
            <div>
                <label for="edit_additional_address" class="block text-sm font-semibold text-gray-700 mb-1.5">Additional
                    Address / Direction</label>
                <div class="relative">
                    <i data-lucide="map" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="edit_additional_address" name="additional_address"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-semibold text-gray-700">Contact Numbers</label>
                    <button type="button" onclick="addContactField('edit')" id="edit_btn_add"
                        class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1 transition-colors">
                        <i data-lucide="plus" class="w-3 h-3"></i> Add Number
                    </button>
                </div>
                <div class="space-y-2">
                    <div id="edit_contact_1_wrapper">
                        <input type="text" id="edit_contact_number_1" name="contact_number_1" placeholder="Contact 1"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                    </div>
                    <div id="edit_contact_2_wrapper" class="hidden relative">
                        <input type="text" id="edit_contact_number_2" name="contact_number_2" placeholder="Contact 2"
                            class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <button type="button" onclick="removeContactField('edit', 2)"
                            class="absolute right-3 top-3 text-gray-400 hover:text-red-500 transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div id="edit_contact_3_wrapper" class="hidden relative">
                        <input type="text" id="edit_contact_number_3" name="contact_number_3" placeholder="Contact 3"
                            class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <button type="button" onclick="removeContactField('edit', 3)"
                            class="absolute right-3 top-3 text-gray-400 hover:text-red-500 transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
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
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const card = modal.querySelector(':scope > div');
        const header = card ? card.querySelector(':scope > div:first-child') : null;
        const title = header ? header.querySelector('h3') : null;
        const inputs = modal.querySelectorAll('input[type="text"]');
        const labels = modal.querySelectorAll('label');
        const footer = modal.querySelector('form > div:last-child');
        const cancelBtn = footer ? footer.querySelector('button:first-of-type') : null;
        const icons = modal.querySelectorAll('form i[data-lucide]');

        if (isDark) {
            if (card) { card.style.backgroundColor = '#1e293b'; }
            if (header) { header.style.backgroundColor = '#1e293b'; header.style.borderBottomColor = '#334155'; }
            if (title) { title.style.color = '#f1f5f9'; }
            labels.forEach(l => { l.style.color = '#cbd5e1'; });
            inputs.forEach(i => { i.style.backgroundColor = '#0f172a'; i.style.borderColor = '#475569'; i.style.color = '#f1f5f9'; });
            if (footer) { footer.style.borderTopColor = '#334155'; }
            if (cancelBtn) { cancelBtn.style.borderColor = '#64748b'; cancelBtn.style.color = '#f1f5f9'; cancelBtn.style.backgroundColor = 'transparent'; }
            icons.forEach(ic => { ic.style.color = '#64748b'; });
        } else {
            if (card) { card.style.backgroundColor = ''; }
            if (header) { header.style.backgroundColor = ''; header.style.borderBottomColor = ''; }
            if (title) { title.style.color = ''; }
            labels.forEach(l => { l.style.color = ''; });
            inputs.forEach(i => { i.style.backgroundColor = ''; i.style.borderColor = ''; i.style.color = ''; });
            if (footer) { footer.style.borderTopColor = ''; }
            if (cancelBtn) { cancelBtn.style.borderColor = ''; cancelBtn.style.color = ''; cancelBtn.style.backgroundColor = ''; }
            icons.forEach(ic => { ic.style.color = ''; });
        }
    }

    function openAddBranchModal() {
        // Clear inputs to prevent lingering values
        document.getElementById('name').value = '';
        document.getElementById('address').value = '';
        document.getElementById('additional_address').value = '';
        document.getElementById('contact_number_1').value = '';
        document.getElementById('contact_number_2').value = '';
        document.getElementById('contact_number_3').value = '';

        document.getElementById('add_contact_2_wrapper').classList.add('hidden');
        document.getElementById('add_contact_3_wrapper').classList.add('hidden');
        document.getElementById('add_btn_add').classList.remove('hidden');

        document.getElementById('addBranchModal').classList.remove('hidden');
        applyModalTheme('addBranchModal');
    }

    function addContactField(prefix) {
        const wrapper2 = document.getElementById(`${prefix}_contact_2_wrapper`);
        const wrapper3 = document.getElementById(`${prefix}_contact_3_wrapper`);
        const btnAdd = document.getElementById(`${prefix}_btn_add`);

        if (wrapper2.classList.contains('hidden')) {
            wrapper2.classList.remove('hidden');
            if (!wrapper3.classList.contains('hidden')) {
                btnAdd.classList.add('hidden');
            }
        } else if (wrapper3.classList.contains('hidden')) {
            wrapper3.classList.remove('hidden');
            btnAdd.classList.add('hidden');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function removeContactField(prefix, num) {
        const wrapper = document.getElementById(`${prefix}_contact_${num}_wrapper`);
        const input = document.getElementById(prefix === 'add' ? `contact_number_${num}` : `edit_contact_number_${num}`);
        const btnAdd = document.getElementById(`${prefix}_btn_add`);

        wrapper.classList.add('hidden');
        input.value = '';
        btnAdd.classList.remove('hidden');
    }

    function closeAddBranchModal() {
        document.getElementById('addBranchModal').classList.add('hidden');
    }

    function openEditModal(branch) {
        document.getElementById('edit_branch_id').value = branch.id;
        document.getElementById('edit_name').value = branch.name;
        document.getElementById('edit_address').value = branch.address || '';
        document.getElementById('edit_additional_address').value = branch.additional_address || '';
        document.getElementById('edit_contact_number_1').value = branch.contact_number_1 || '';
        document.getElementById('edit_contact_number_2').value = branch.contact_number_2 || '';
        document.getElementById('edit_contact_number_3').value = branch.contact_number_3 || '';

        const wrapper2 = document.getElementById('edit_contact_2_wrapper');
        const wrapper3 = document.getElementById('edit_contact_3_wrapper');
        const btnAdd = document.getElementById('edit_btn_add');

        if (branch.contact_number_2) {
            wrapper2.classList.remove('hidden');
        } else {
            wrapper2.classList.add('hidden');
        }

        if (branch.contact_number_3) {
            wrapper3.classList.remove('hidden');
        } else {
            wrapper3.classList.add('hidden');
        }

        if (branch.contact_number_2 && branch.contact_number_3) {
            btnAdd.classList.add('hidden');
        } else {
            btnAdd.classList.remove('hidden');
        }

        document.getElementById('editBranchModal').classList.remove('hidden');
        applyModalTheme('editBranchModal');
        if (window.lucide) window.lucide.createIcons();
    }

    function closeEditBranchModal() {
        document.getElementById('editBranchModal').classList.add('hidden');
    }

    async function confirmDelete(id, name) {
        const result = await confirmAlert('Delete Branch', `Are you sure you want to delete this branch?`, 'Yes, Delete');
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
    let currentPage = parseInt(sessionStorage.getItem('CitiLife_adminBranches_page')) || 1;
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

    function renderPaginationControls(totalPages) {
        const container = document.getElementById('paginationControls');
        if (!container) return;
        container.innerHTML = '';

        // Helper to create a button
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
                    filterAndSortBranches(false);
                    const card = document.getElementById('branches-table-card');
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
        container.appendChild(createButton('&laquo; First', 1, currentPage <= 1));

        // Back Button
        container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage <= 1));

        // Page numbers
        if (totalPages <= 7) {
            // Show all pages
            for (let i = 1; i <= totalPages; i++) {
                container.appendChild(createButton(i, i, false, i == currentPage));
            }
        } else {
            // We have many pages
            if (currentPage <= 4) {
                // Near start: 1, 2, 3, 4, 5, ..., T
                for (let i = 1; i <= 5; i++) {
                    container.appendChild(createButton(i, i, false, i == currentPage));
                }
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, totalPages == currentPage));
            } else if (currentPage >= totalPages - 3) {
                // Near end: 1, ..., T-4, T-3, T-2, T-1, T
                container.appendChild(createButton(1, 1, false, 1 == currentPage));
                container.appendChild(createEllipsis());
                for (let i = totalPages - 4; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i == currentPage));
                }
            } else {
                // Middle: 1, ..., C-1, C, C+1, ..., T
                container.appendChild(createButton(1, 1, false, 1 == currentPage));
                container.appendChild(createEllipsis());
                
                container.appendChild(createButton(currentPage - 1, currentPage - 1, false, false));
                container.appendChild(createButton(currentPage, currentPage, false, true));
                container.appendChild(createButton(currentPage + 1, currentPage + 1, false, false));
                
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, false));
            }
        }

        // Next Button
        container.appendChild(createButton('Next &rsaquo;', currentPage + 1, currentPage >= totalPages));

        // Last Button
        container.appendChild(createButton('Last &raquo;', totalPages, currentPage >= totalPages));
    }

    function updatePagination(visibleRows) {
        const totalRecords = visibleRows.length;
        const totalPages = Math.ceil(totalRecords / itemsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        sessionStorage.setItem('CitiLife_adminBranches_page', currentPage);

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

        if (startIndexEl) startIndexEl.innerText = totalRecords === 0 ? 0 : startIdx + 1;
        if (endIndexEl) endIndexEl.innerText = endIdx;
        if (totalRecordsEl) totalRecordsEl.innerText = totalRecords;

        // Render dynamic page controls
        renderPaginationControls(totalPages);
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

        // Auto-format contact numbers
        const contactInputs = document.querySelectorAll('input[name^="contact_number"]');
        contactInputs.forEach(input => {
            input.addEventListener('input', function () {
                let val = this.value.replace(/\D/g, ''); // Remove non-digits
                if (val.length > 11) val = val.substring(0, 11); // Limit to 11 digits

                let formatted = '';
                if (val.length > 0) formatted = val.substring(0, 4);
                if (val.length > 4) formatted += '-' + val.substring(4, 7);
                if (val.length > 7) formatted += '-' + val.substring(7, 11);

                this.value = formatted;
            });
        });
    });
</script>