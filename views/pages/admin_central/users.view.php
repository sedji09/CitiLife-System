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
                <h1 class="text-2xl font-semibold text-gray-900">User Management</h1>
                <p class="text-sm text-gray-500">Create and manage staff accounts across all branches</p>
            </div>
            <button type="button" onclick="openAddUserModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition-all active:scale-95">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Add New Staff
            </button>
        </div>

        <?php
        $activeStaffCount = 0;
        $totalStaffCount = count($users);
        foreach ($users as $u) {
            if ($u['status'] === 'Active')
                $activeStaffCount++;
        }
        ?>
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Staff</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $activeStaffCount ?></h3>
                </div>
                <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                    <i data-lucide="user-check" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Staff</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $totalStaffCount ?></h3>
                </div>
                <div class="p-3 bg-gray-50 text-gray-600 rounded-lg">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: <?= json_encode($success) ?>,
                            showConfirmButton: false,
                            timer: 2500,
                            customClass: { popup: 'rounded-3xl border-0 shadow-2xl' }
                        });
                    } else if (typeof toast === 'function') {
                        toast(<?= json_encode($success) ?>, 'success');
                    }
                });
            </script>
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
                    placeholder="Search by name, email, or role..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-gray-400"></i>
            </div>
            <div class="flex gap-3 w-full md:w-auto items-center">
                <select id="branchFilter" onchange="filterAndSortUsers()"
                    class="flex-1 md:w-48 px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="statusFilter" onchange="filterAndSortUsers()"
                    class="flex-1 md:w-44 px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="" selected>All Status</option>
                    <option value="Active">Active</option>
                    <option value="Pending">Pending Activation</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Users Table Card -->
        <div id="users-table-card"
            class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden mb-12">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200">
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Staff Member / Email</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Role</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500 text-left">Branch Assignment
                            </th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Status</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="text-gray-800 bg-white divide-y divide-gray-100">
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
                                    data-name="<?= htmlspecialchars(strtolower($u['name'] ?? '')) ?>"
                                    data-email="<?= htmlspecialchars(strtolower($u['email'])) ?>"
                                    data-role="<?= htmlspecialchars(strtolower($u['role'])) ?>"
                                    data-branch="<?= htmlspecialchars(strtolower($u['branch_name'] ?? 'all branches')) ?>"
                                    data-status="<?= htmlspecialchars($u['status']) ?>">
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="h-7 w-7 rounded-full bg-red-100 flex items-center justify-center text-red-700 font-bold text-[11px] uppercase overflow-hidden shrink-0">
                                                <?php $uAvatarUrl = function_exists('getAvatarUrl') ? getAvatarUrl($u['avatar']) : $u['avatar']; ?>
                                                <?php if (!empty($uAvatarUrl)): ?>
                                                    <img src="<?= htmlspecialchars($uAvatarUrl) ?>" alt="Avatar"
                                                        class="h-full w-full object-cover">
                                                <?php else: ?>
                                                    <?= htmlspecialchars(strtoupper(substr(!empty($u['name']) ? $u['name'] : $u['email'], 0, 2))) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex flex-col">
                                                <?php if (!empty($u['name'])): ?>
                                                    <span
                                                        class="text-sm font-bold text-gray-800 tracking-tight"><?= htmlspecialchars($u['name']) ?></span>
                                                    <span
                                                        class="text-xs text-gray-500 font-normal"><?= htmlspecialchars($u['email']) ?></span>
                                                <?php else: ?>
                                                    <span
                                                        class="text-sm font-bold text-gray-800 tracking-tight"><?= htmlspecialchars($u['email']) ?></span>
                                                <?php endif; ?>
                                                <span class="text-[10px] text-gray-400 font-medium tracking-tight">Joined
                                                    <?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="text-sm text-gray-600 capitalize">
                                            <?= htmlspecialchars(str_replace('_', ' ', $u['role'])) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="text-sm text-gray-600">
                                            <?= htmlspecialchars($u['branch_name'] ?? 'All Branches') ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <?php
                                        $badgeClass = 'bg-gray-100 text-gray-600 ring-gray-200';
                                        $badgeLabel = $u['status'];
                                        if ($u['status'] === 'Active') {
                                            $badgeClass = 'bg-green-50 text-green-600 ring-green-100';
                                        } elseif ($u['status'] === 'Pending') {
                                            $badgeClass = 'bg-amber-50 text-amber-700 ring-amber-200';
                                            $badgeLabel = 'Pending Activation';
                                        } elseif ($u['status'] === 'Rejected' || $u['status'] === 'Inactive') {
                                            $badgeClass = 'bg-red-50 text-red-600 ring-red-100';
                                        }
                                        ?>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold ring-1 ring-inset <?= $badgeClass ?>">
                                            <?= htmlspecialchars($badgeLabel) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-left">
                                        <div class="flex items-center justify-start gap-1.5">
                                            <?php if ($u['status'] === 'Pending'): ?>
                                                <form action="" method="POST" class="inline"
                                                    onsubmit="return confirm('Resend account activation email to <?= htmlspecialchars($u['email']) ?>?')">
                                                    <input type="hidden" name="action" value="resend_invite">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit"
                                                        class="p-1.5 rounded-md border border-amber-200 bg-amber-50 text-amber-600 hover:text-amber-800 hover:border-amber-300 hover:bg-amber-100 transition shadow-sm"
                                                        title="Resend Activation Email">
                                                        <i data-lucide="send" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($u['status'] === 'Active'): ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle-status">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="new_status" value="Inactive">
                                                    <button type="submit"
                                                        class="p-1.5 rounded-md border border-gray-300 bg-gray-100 text-gray-500 hover:bg-gray-600 hover:text-white hover:border-gray-600 transition shadow-sm inline-flex items-center justify-center"
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
                                                        class="p-1.5 rounded-md border border-green-500 bg-green-100 text-green-600 hover:bg-green-600 hover:text-white hover:border-green-600 transition shadow-sm inline-flex items-center justify-center"
                                                        title="Activate">
                                                        <i data-lucide="user-check" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button type="button"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                                class="p-1.5 rounded-md border  border-amber-500 bg-amber-100 text-amber-600 hover:bg-amber-600 hover:text-white hover:border-amber-600 transition shadow-sm inline-flex items-center justify-center"
                                                title="Edit User">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>

                                            <button type="button"
                                                onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email']) ?>')"
                                                class="p-1.5 rounded-md border border-red-500 bg-red-100 text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 transition shadow-sm inline-flex items-center justify-center"
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
            <div
                class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
                <div class="text-xs text-gray-500">
                    Showing <span id="startIndex" class="font-semibold text-gray-800">0</span> to <span id="endIndex"
                        class="font-semibold text-gray-800">0</span> of <span id="totalRecords"
                        class="font-semibold text-gray-800">0</span> records
                </div>
                <div class="flex items-center flex-wrap gap-1.5" id="paginationControls">
                    <!-- Dynamic page buttons will be inserted here -->
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ADD USER MODAL -->
<div id="addUserModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 rounded-t-2xl">
            <h3 class="text-lg font-bold text-gray-900">Create New Staff Account</h3>
            <button type="button" onclick="closeAddUserModal()"
                class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4" autocomplete="off">
            <input type="hidden" name="action" value="create">

            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">Staff Full Name</label>
                <div class="relative">
                    <i data-lucide="user" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="name" name="name" required placeholder="Dr. Juan Dela Cruz"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                <div class="relative">
                    <i data-lucide="mail" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="email" id="email" name="email" required placeholder="staff@gmail.com"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div
                class="p-3 bg-blue-50/80 border border-blue-100 rounded-xl text-xs text-blue-800 flex items-center gap-2.5">
                <i data-lucide="mail-check" class="w-4 h-4 text-blue-600 shrink-0"></i>
                <span>An invitation link will be emailed to the staff member to set their password and activate their
                    account (valid for 7 days).</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label>
                    <select id="role" name="role" required onchange="toggleBranchSelect()"
                        class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="" disabled selected hidden>Select Role</option>
                        <option value="branch_admin">Branch Admin</option>
                        <option value="radtech">RadTech</option>
                        <option value="radiologist">Radiologist</option>
                        <option value="it_admin">IT Admin</option>
                        <option value="admin_central">Admin Central</option>
                    </select>
                </div>
                <div id="branchSelectWrapper">
                    <label for="branch_id" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch</label>
                    <select id="branch_id" name="branch_id"
                        class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="" disabled selected hidden>Select Branch</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
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
                    class="flex-1 py-2.5 rounded-xl bg-red-600 text-sm font-bold text-white hover:bg-red-700 shadow-sm shadow-red-200 transition-all active:scale-95 inline-flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Create & Send Invitation</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div id="editUserModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 rounded-t-2xl">
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
                <label for="edit_name" class="block text-sm font-semibold text-gray-700 mb-1.5">Staff Full Name</label>
                <div class="relative">
                    <i data-lucide="user" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="edit_name" name="name" placeholder="Dr. Juan Dela Cruz"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div>
                <label for="edit_email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                <div class="relative">
                    <i data-lucide="mail" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="email" id="edit_email" name="email" required placeholder="staff@gmail.com"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit_role" class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label>
                    <select id="edit_role" name="role" required onchange="toggleEditBranchSelect()"
                        class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="" disabled hidden>Select Role</option>
                        <option value="branch_admin">Branch Admin</option>
                        <option value="radtech">RadTech</option>
                        <option value="radiologist">Radiologist</option>
                        <option value="it_admin">IT Admin</option>
                        <option value="admin_central">Admin Central</option>
                    </select>
                </div>
                <div id="edit_branchSelectWrapper">
                    <label for="edit_branch_id" class="block text-sm font-semibold text-gray-700 mb-1.5">Branch</label>
                    <select id="edit_branch_id" name="branch_id"
                        class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="" disabled hidden>Select Branch</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
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
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const card = modal.querySelector(':scope > div');
        const header = card ? card.querySelector(':scope > div:first-child') : null;
        const title = header ? header.querySelector('h3') : null;
        const inputs = modal.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
        const selects = modal.querySelectorAll('select');
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
            selects.forEach(s => { s.style.backgroundColor = '#0f172a'; s.style.borderColor = '#475569'; s.style.color = '#f1f5f9'; });
            if (footer) { footer.style.borderTopColor = '#334155'; }
            if (cancelBtn) { cancelBtn.style.borderColor = '#64748b'; cancelBtn.style.color = '#f1f5f9'; cancelBtn.style.backgroundColor = 'transparent'; }
            icons.forEach(ic => { ic.style.color = '#64748b'; });
        } else {
            if (card) { card.style.backgroundColor = ''; }
            if (header) { header.style.backgroundColor = ''; header.style.borderBottomColor = ''; }
            if (title) { title.style.color = ''; }
            labels.forEach(l => { l.style.color = ''; });
            inputs.forEach(i => { i.style.backgroundColor = ''; i.style.borderColor = ''; i.style.color = ''; });
            selects.forEach(s => { s.style.backgroundColor = ''; s.style.borderColor = ''; s.style.color = ''; });
            if (footer) { footer.style.borderTopColor = ''; }
            if (cancelBtn) { cancelBtn.style.borderColor = ''; cancelBtn.style.color = ''; cancelBtn.style.backgroundColor = ''; }
            icons.forEach(ic => { ic.style.color = ''; });
        }
    }


    function generateRandomPassword() {
        const year = new Date().getFullYear();
        const randomNum = Math.floor(10000 + Math.random() * 90000);
        return `${year}_${randomNum}`;
    }

    function openAddUserModal() {
        // Clear inputs to prevent lingering values or autofill
        const nameEl = document.getElementById('name');
        if (nameEl) nameEl.value = '';
        document.getElementById('email').value = '';
        const passEl = document.getElementById('password');
        if (passEl) passEl.value = generateRandomPassword();
        document.getElementById('role').value = '';
        document.getElementById('branch_id').value = '';

        document.getElementById('addUserModal').classList.remove('hidden');
        applyUserModalTheme('addUserModal');
        if (window.lucide) window.lucide.createIcons();
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').classList.add('hidden');
    }

    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        const editNameEl = document.getElementById('edit_name');
        if (editNameEl) editNameEl.value = user.name || '';
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_branch_id').value = user.branch_id || '';

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
        if (role === 'it_admin' || role === 'admin_central' || role === 'radiologist') {
            branchWrapper.classList.add('opacity-30', 'pointer-events-none');
            document.getElementById('edit_branch_id').value = '';
        } else {
            branchWrapper.classList.remove('opacity-30', 'pointer-events-none');
        }
    }

    function toggleBranchSelect() {
        const role = document.getElementById('role').value;
        const branchWrapper = document.getElementById('branchSelectWrapper');
        if (role === 'it_admin' || role === 'admin_central' || role === 'radiologist') {
            branchWrapper.classList.add('opacity-30', 'pointer-events-none');
            document.getElementById('branch_id').value = '';
        } else {
            branchWrapper.classList.remove('opacity-30', 'pointer-events-none');
        }
    }

    async function confirmDelete(id, email) {
        const result = await confirmAlert('Delete Account', `Are you sure you want to delete this account?`, 'Yes, Delete');
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
    let currentPage = parseInt(sessionStorage.getItem('Citilife_adminUsers_page')) || 1;
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
            const rowName = (row.dataset.name || "").toLowerCase();
            const rowEmail = (row.dataset.email || "").toLowerCase();
            const rowRole = (row.dataset.role || "").replace(/_/g, ' ').toLowerCase(); // Allow searching with spaces
            const rowBranch = (row.dataset.branch || "").toLowerCase();
            const rowStatus = row.dataset.status || "";

            const matchesSearch = rowName.includes(query) || rowEmail.includes(query) || rowRole.includes(query) || (row.dataset.role || "").toLowerCase().includes(query);
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
                    filterAndSortUsers(false);
                    const card = document.getElementById('users-table-card');
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

        // Sanitize current page
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        sessionStorage.setItem('Citilife_adminUsers_page', currentPage);

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

        if (startIndexEl) startIndexEl.innerText = totalRecords === 0 ? 0 : startIdx + 1;
        if (endIndexEl) endIndexEl.innerText = endIdx;
        if (totalRecordsEl) totalRecordsEl.innerText = totalRecords;

        // Render dynamic page controls
        renderPaginationControls(totalPages);
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

        // Inline form validation for Add User Modal
        document.querySelector('#addUserModal form')?.addEventListener('submit', function (e) {
            if (window.FormValidator) window.FormValidator.clearAllErrors('#addUserModal');
            const email = document.getElementById('email');
            const role = document.getElementById('role');
            const branch = document.getElementById('branch_id');

            let hasError = false;
            let firstError = null;

            if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                if (window.FormValidator) window.FormValidator.showError(email, 'Please enter a valid email address.');
                hasError = true;
                firstError = email;
            }
            if (!role.value) {
                if (window.FormValidator) window.FormValidator.showError(role, 'Please select a role.');
                if (!hasError) firstError = role;
                hasError = true;
            }
            if (['branch_admin', 'radtech'].includes(role.value) && !branch.value) {
                if (window.FormValidator) window.FormValidator.showError(branch, 'Branch is required for this role.');
                if (!hasError) firstError = branch;
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (firstError) firstError.focus();
                if (typeof toast === 'function') toast('Please check the required fields.', 'error');
                return false;
            }
        });

        // Inline form validation for Edit User Modal
        document.querySelector('#editUserModal form')?.addEventListener('submit', function (e) {
            if (window.FormValidator) window.FormValidator.clearAllErrors('#editUserModal');
            const email = document.getElementById('edit_email');
            const role = document.getElementById('edit_role');
            const branch = document.getElementById('edit_branch_id');

            let hasError = false;
            let firstError = null;

            if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                if (window.FormValidator) window.FormValidator.showError(email, 'Please enter a valid email address.');
                hasError = true;
                firstError = email;
            }
            if (!role.value) {
                if (window.FormValidator) window.FormValidator.showError(role, 'Please select a role.');
                if (!hasError) firstError = role;
                hasError = true;
            }
            if (['branch_admin', 'radtech'].includes(role.value) && !branch.value) {
                if (window.FormValidator) window.FormValidator.showError(branch, 'Branch is required for this role.');
                if (!hasError) firstError = branch;
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (firstError) firstError.focus();
                if (typeof toast === 'function') toast('Please check the required fields.', 'error');
                return false;
            }
        });

        <?php if (!empty($error) && ($_POST['action'] ?? '') === 'create'): ?>
            openAddUserModal();
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.value = <?= json_encode($_POST['email'] ?? '') ?>;
                if (window.FormValidator) window.FormValidator.showError(emailField, <?= json_encode($error) ?>);
            }
            const roleField = document.getElementById('role');
            if (roleField) {
                roleField.value = <?= json_encode($_POST['role'] ?? '') ?>;
                toggleBranchSelect();
            }
            const branchField = document.getElementById('branch_id');
            if (branchField) {
                branchField.value = <?= json_encode($_POST['branch_id'] ?? '') ?>;
            }
            if (typeof toast === 'function') toast(<?= json_encode($error) ?>, 'error');
        <?php elseif (!empty($error) && ($_POST['action'] ?? '') === 'update'): ?>
            const editModal = document.getElementById('editUserModal');
            if (editModal) editModal.classList.remove('hidden');
            const editEmailField = document.getElementById('edit_email');
            if (editEmailField) {
                editEmailField.value = <?= json_encode($_POST['email'] ?? '') ?>;
                if (window.FormValidator) window.FormValidator.showError(editEmailField, <?= json_encode($error) ?>);
            }
            if (typeof toast === 'function') toast(<?= json_encode($error) ?>, 'error');
        <?php endif; ?>
    });
</script>