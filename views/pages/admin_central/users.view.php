<?php
/**
 * User Management View (Central Admin)
 * Backend logic handled by UsersController.php
 */
?>

<main class="flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                <p class="text-sm text-gray-500">Create and manage staff accounts across all branches</p>
            </div>
            <button type="button" onclick="openAddUserModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition-all active:scale-95">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Add New Staff
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
                <input type="text" id="userSearch" oninput="filterAndSortUsers()"
                    placeholder="Search by email or role..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-gray-400"></i>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <select id="branchFilter" onchange="filterAndSortUsers()"
                    class="flex-1 md:w-48 px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <?php $displayName = htmlspecialchars($b['name']) . ($b['address'] ? ' (' . htmlspecialchars($b['address']) . ')' : ''); ?>
                        <option value="<?= htmlspecialchars($b['name']) ?>"><?= $displayName ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="statusFilter" onchange="filterAndSortUsers()"
                    class="flex-1 md:w-40 px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="" selected>All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200">
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Email Address / User</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Role</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Branch Assignment</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Status</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="divide-y divide-gray-100">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <i data-lucide="users" class="w-10 h-10 text-gray-200"></i>
                                        <p>No staff accounts found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- No Results Placeholder Row (Dynamically toggled) -->
                            <tr id="noResultsRow" class="hidden">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div
                                            class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-2">
                                            <i data-lucide="search-x" class="w-8 h-8 text-gray-300"></i>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-800">No matching accounts</h3>
                                        <p class="text-xs text-gray-500">Try adjusting your search or filters to find what
                                            you're looking for.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-gray-50/30 transition-colors group user-row"
                                    data-email="<?= htmlspecialchars(strtolower($u['email'])) ?>"
                                    data-role="<?= htmlspecialchars(strtolower($u['role'])) ?>"
                                    data-branch="<?= htmlspecialchars(strtolower($u['branch_name'] ?? 'universal')) ?>"
                                    data-status="<?= htmlspecialchars($u['status']) ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center text-red-700 font-bold text-[11px] uppercase">
                                                <?= substr($u['email'], 0, 2) ?>
                                            </div>
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-bold text-gray-800 tracking-tight"><?= htmlspecialchars($u['email']) ?></span>
                                                <span class="text-[10px] text-gray-400 font-medium tracking-tight">Joined
                                                    <?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-medium text-gray-600 tracking-tight uppercase">
                                            <?= htmlspecialchars(str_replace('_', ' ', $u['role'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 tracking-tight">
                                            <i data-lucide="map-pin" class="w-3.5 h-3.5 opacity-40"></i>
                                            <?= htmlspecialchars($u['branch_name'] ?? 'Universal') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $badgeClass = 'bg-gray-100 text-gray-600 ring-gray-200';
                                        if ($u['status'] === 'Active') {
                                            $badgeClass = 'bg-green-50 text-green-600 ring-green-100';
                                        } elseif ($u['status'] === 'Pending') {
                                            $badgeClass = 'bg-yellow-50 text-yellow-600 ring-yellow-200';
                                        } elseif ($u['status'] === 'Rejected' || $u['status'] === 'Inactive') {
                                            $badgeClass = 'bg-red-50 text-red-600 ring-red-100';
                                        }
                                        ?>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold ring-1 ring-inset <?= $badgeClass ?>">
                                            <?= htmlspecialchars($u['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <?php if ($u['status'] === 'Active'): ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle-status">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="new_status" value="Inactive">
                                                    <button type="submit"
                                                        class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-orange-500 hover:border-orange-200 hover:bg-orange-50 transition shadow-sm"
                                                        title="Deactivate">
                                                        <i data-lucide="user-minus" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle-status">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="new_status" value="Active">
                                                    <button type="submit"
                                                        class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-green-500 hover:border-green-200 hover:bg-green-50 transition shadow-sm"
                                                        title="Activate">
                                                        <i data-lucide="user-check" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button type="button"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                                class="p-1.5 rounded-md border border-blue-100 bg-blue-50 text-blue-500 hover:bg-blue-100 transition shadow-sm"
                                                title="Edit User">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>

                                            <button type="button"
                                                onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email']) ?>')"
                                                class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition shadow-sm"
                                                title="Delete Account">
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
                <div class="text-xs text-gray-500 dark:text-slate-400 font-medium">
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

<!-- ADD USER MODAL -->
<div id="addUserModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Create New Staff Account</h3>
            <button type="button" onclick="closeAddUserModal()"
                class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4" autocomplete="off">
            <input type="hidden" name="action" value="create">

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                <div class="relative">
                    <i data-lucide="mail" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="email" id="email" name="email" required placeholder="staff@citilife.com" autocomplete="off"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Initial Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="new-password"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label>
                    <select id="role" name="role" required onchange="toggleBranchSelect()"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="" disabled selected hidden>Select Role</option>
                        <option value="branch_admin">Branch Admin</option>
                        <option value="radtech">RadTech</option>
                        <option value="radiologist">Radiologist</option>
                        <option value="it_admin">IT Admin</option>
                    </select>
                </div>
                <div id="branchSelectWrapper">
                    <label for="branch_id" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch</label>
                    <select id="branch_id" name="branch_id"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="" disabled selected hidden>Select Branch</option>
                        <?php foreach ($branches as $b): ?>
                            <?php $displayName = htmlspecialchars($b['name']) . ($b['address'] ? ' (' . htmlspecialchars($b['address']) . ')' : ''); ?>
                            <option value="<?= $b['id'] ?>"><?= $displayName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="button" onclick="closeAddUserModal()"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-red-600 text-sm font-bold text-white hover:bg-red-700 shadow-sm shadow-red-200 transition-all active:scale-95">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div id="editUserModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Edit Staff Account</h3>
            <button type="button" onclick="closeEditUserModal()"
                class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4" autocomplete="off">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div>
                <label for="edit_email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                <div class="relative">
                    <i data-lucide="mail" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="email" id="edit_email" name="email" required placeholder="staff@citilife.com"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div>
                <label for="edit_password" class="block text-sm font-semibold text-gray-700 mb-1.5">New Password (Leave
                    blank to keep current)</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="password" id="edit_password" name="password" placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit_role" class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label>
                    <select id="edit_role" name="role" required onchange="toggleEditBranchSelect()"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="" disabled hidden>Select Role</option>
                        <option value="branch_admin">Branch Admin</option>
                        <option value="radtech">RadTech</option>
                        <option value="radiologist">Radiologist</option>
                        <option value="it_admin">IT Admin</option>
                    </select>
                </div>
                <div id="edit_branchSelectWrapper">
                    <label for="edit_branch_id" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch</label>
                    <select id="edit_branch_id" name="branch_id"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="" disabled hidden>Select Branch</option>
                        <?php foreach ($branches as $b): ?>
                            <?php $displayName = htmlspecialchars($b['name']) . ($b['address'] ? ' (' . htmlspecialchars($b['address']) . ')' : ''); ?>
                            <option value="<?= $b['id'] ?>"><?= $displayName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="button" onclick="closeEditUserModal()"
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

<script>
    /* ── Dark-mode theming via JS inline styles ── */
    function applyUserModalTheme(modalId) {
        const isDark = document.documentElement.classList.contains('theme-dark');
        const modal  = document.getElementById(modalId);
        if (!modal) return;

        const card      = modal.querySelector(':scope > div');
        const header    = card  ? card.querySelector(':scope > div:first-child') : null;
        const title     = header ? header.querySelector('h3') : null;
        const inputs    = modal.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
        const selects   = modal.querySelectorAll('select');
        const labels    = modal.querySelectorAll('label');
        const footer    = modal.querySelector('form > div:last-child');
        const cancelBtn = footer ? footer.querySelector('button:first-of-type') : null;
        const icons     = modal.querySelectorAll('form i[data-lucide]');

        if (isDark) {
            if (card)   { card.style.backgroundColor   = '#1e293b'; }
            if (header) { header.style.backgroundColor = '#1e293b'; header.style.borderBottomColor = '#334155'; }
            if (title)  { title.style.color = '#f1f5f9'; }
            labels.forEach(l  => { l.style.color = '#cbd5e1'; });
            inputs.forEach(i  => { i.style.backgroundColor = '#0f172a'; i.style.borderColor = '#475569'; i.style.color = '#f1f5f9'; });
            selects.forEach(s => { s.style.backgroundColor = '#0f172a'; s.style.borderColor = '#475569'; s.style.color = '#f1f5f9'; });
            if (footer)    { footer.style.borderTopColor = '#334155'; }
            if (cancelBtn) { cancelBtn.style.borderColor = '#64748b'; cancelBtn.style.color = '#f1f5f9'; cancelBtn.style.backgroundColor = 'transparent'; }
            icons.forEach(ic => { ic.style.color = '#64748b'; });
        } else {
            if (card)   { card.style.backgroundColor   = ''; }
            if (header) { header.style.backgroundColor = ''; header.style.borderBottomColor = ''; }
            if (title)  { title.style.color = ''; }
            labels.forEach(l  => { l.style.color = ''; });
            inputs.forEach(i  => { i.style.backgroundColor = ''; i.style.borderColor = ''; i.style.color = ''; });
            selects.forEach(s => { s.style.backgroundColor = ''; s.style.borderColor = ''; s.style.color = ''; });
            if (footer)    { footer.style.borderTopColor = ''; }
            if (cancelBtn) { cancelBtn.style.borderColor = ''; cancelBtn.style.color = ''; cancelBtn.style.backgroundColor = ''; }
            icons.forEach(ic => { ic.style.color = ''; });
        }
    }

    function openAddUserModal() {
        // Clear inputs to prevent lingering values or autofill
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
        document.getElementById('role').value = '';
        document.getElementById('branch_id').value = '';
        
        document.getElementById('addUserModal').classList.remove('hidden');
        applyUserModalTheme('addUserModal');
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').classList.add('hidden');
    }

    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_branch_id').value = user.branch_id || '';
        document.getElementById('edit_password').value = ''; // Always clear for security

        toggleEditBranchSelect();
        document.getElementById('editUserModal').classList.remove('hidden');
        applyUserModalTheme('editUserModal');
    }

    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }

    function toggleEditBranchSelect() {
        const role = document.getElementById('edit_role').value;
        const branchWrapper = document.getElementById('edit_branchSelectWrapper');
        if (role === 'it_admin') {
            branchWrapper.classList.add('opacity-30', 'pointer-events-none');
        } else {
            branchWrapper.classList.remove('opacity-30', 'pointer-events-none');
        }
    }

    function toggleBranchSelect() {
        const role = document.getElementById('role').value;
        const branchWrapper = document.getElementById('branchSelectWrapper');
        if (role === 'it_admin') {
            branchWrapper.classList.add('opacity-30', 'pointer-events-none');
        } else {
            branchWrapper.classList.remove('opacity-30', 'pointer-events-none');
        }
    }

    async function confirmDelete(id, email) {
        const result = await confirmAlert('Delete Account', `Are you sure you want to delete the account for ${email}? This action cannot be undone.`, 'Yes, Delete');
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
            idInput.name = 'user_id';
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

    // Real-time Filtering & Sorting Logic
    function filterAndSortUsers(resetPage = true) {
        if (resetPage) currentPage = 1;

        const userSearch = document.getElementById('userSearch');
        const branchFilter = document.getElementById('branchFilter');
        const statusFilter = document.getElementById('statusFilter');
        const sortStatus = document.getElementById('sortStatus');
        const tableBody = document.getElementById('usersTableBody');

        if (!userSearch || !tableBody) return;

        const query = userSearch.value.toLowerCase();
        const branch = branchFilter.value.toLowerCase();
        const status = statusFilter.value;
        const sortMode = sortStatus ? sortStatus.value : 'none';

        let rows = Array.from(document.querySelectorAll('.user-row'));

        // 1. Filtering
        let visibleCount = 0;
        rows.forEach(row => {
            const rowEmail = (row.dataset.email || "").toLowerCase();
            const rowRole = (row.dataset.role || "").replace(/_/g, ' ').toLowerCase(); // Allow searching with spaces
            const rowBranch = (row.dataset.branch || "").toLowerCase();
            const rowStatus = row.dataset.status || "";

            const matchesSearch = rowEmail.includes(query) || rowRole.includes(query) || (row.dataset.role || "").toLowerCase().includes(query);
            const matchesBranch = branch === "" || rowBranch.includes(branch);
            const matchesStatus = status === "" || rowStatus.trim() === status;

            if (matchesSearch && matchesBranch && matchesStatus) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        // 1.1 Toggle No Results Placeholder
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            if (visibleCount === 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }
        }

        // 2. Sorting (Only if visible and requested)
        if (sortMode !== 'none') {
            rows.sort((a, b) => {
                const statusA = (a.dataset.status || "").trim();
                const statusB = (b.dataset.status || "").trim();

                if (sortMode === 'active') {
                    if (statusA === 'Active' && statusB !== 'Active') return -1;
                    if (statusA !== 'Active' && statusB === 'Active') return 1;
                } else if (sortMode === 'inactive') {
                    if ((statusA === 'Inactive' || statusA === 'Rejected') && (statusB !== 'Inactive' && statusB !== 'Rejected')) return -1;
                    if ((statusA !== 'Inactive' && statusA !== 'Rejected') && (statusB === 'Inactive' || statusB === 'Rejected')) return 1;
                } else if (sortMode === 'pending') {
                    if (statusA === 'Pending' && statusB !== 'Pending') return -1;
                    if (statusA !== 'Pending' && statusB === 'Pending') return 1;
                }
                return 0;
            });

            // Re-append sorted rows to the table body
            rows.forEach(row => tableBody.appendChild(row));
        }

        updatePagination(rows.filter(r => !r.classList.contains('hidden')));
    }

    function updatePagination(visibleRows) {
        const totalRecords = visibleRows.length;
        const totalPages = Math.ceil(totalRecords / itemsPerPage) || 1;

        // Sanitize current page
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = Math.min(startIdx + itemsPerPage, totalRecords);

        // Hide/Show based on page
        visibleRows.forEach((row, index) => {
            if (index >= startIdx && index < endIdx) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });

        // Update UI
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
        filterAndSortUsers(false); // Don't reset page when navigating
    }

    // Refresh Lucide icons after dynamic updates or modal show
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
        filterAndSortUsers(false); // Initial pagination

        // Auto-dismiss alerts after 3 seconds
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