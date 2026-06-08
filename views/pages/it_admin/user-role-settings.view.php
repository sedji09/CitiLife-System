<!-- IT Dynamic User Role Matrix -->
<?php
$roleApplicablePermissions = [
    'it_admin' => ['system_security', 'backup_mgmt', 'audit_logs', 'dashboard'],
    'admin_central' => ['system_security', 'branch_mgmt', 'user_mgmt', 'patient_history', 'global_reports', 'audit_logs', 'dashboard'],
    'branch_admin' => ['record_requests', 'patient_history', 'audit_logs', 'global_reports', 'dashboard'],
    'radtech' => ['patient_reg', 'worklist', 'patient_history', 'record_requests', 'dashboard'],
    'radiologist' => ['worklist', 'patient_history', 'dashboard', 'case_review', 'write_report'],
    'patient' => ['patient_reg', 'dashboard']
];

function getRoleIcon($roleKey)
{
    switch ($roleKey) {
        case 'it_admin':
            return 'shield-alert';
        case 'admin_central':
            return 'building-2';
        case 'branch_admin':
            return 'folder-sync';
        case 'radtech':
            return 'user-plus';
        case 'radiologist':
            return 'activity';
        case 'patient':
            return 'user';
        default:
            return 'circle';
    }
}
?>
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Access Control Center</h1>
            <p class="text-sm text-gray-500 mt-1">Configure functional permissions and security boundaries for all staff
                roles.</p>
        </div>
        <div class="flex items-center gap-4">
            <div id="save-status"
                class="hidden items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-100 rounded-full">
                <span id="save-status-icon">
                    <i data-lucide="loader-2" class="w-3 h-3 text-green-600 animate-spin"></i>
                </span>
                <span id="save-status-text"
                    class="text-[10px] font-bold text-green-700 uppercase tracking-widest leading-none">Saving
                    Changes...</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-rose-50 border border-rose-100 rounded-full">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                <span class="text-[10px] font-bold text-rose-700 uppercase tracking-widest leading-none">Dynamic RBAC
                    Active</span>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
        <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-900 leading-none">Full Access</p>
                <p class="text-[9px] text-gray-400 mt-1 uppercase tracking-tighter">Global Visibility</p>
            </div>
        </div>
        <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-900 leading-none">Branch Bound</p>
                <p class="text-[9px] text-gray-400 mt-1 uppercase tracking-tighter">Assigned Branch Only</p>
            </div>
        </div>
        <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                <i data-lucide="slash" class="w-4 h-4"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-900 leading-none">No Access</p>
                <p class="text-[9px] text-gray-400 mt-1 uppercase tracking-tighter">Hidden / Disabled</p>
            </div>
        </div>
    </div>

    <!-- View Switcher -->
    <div
        class="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-white p-4 rounded-2xl border border-gray-200 shadow-sm gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                <i data-lucide="settings-2" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-sm font-extrabold text-gray-900">View Mode</p>
                <p class="text-xs text-gray-500">Choose between a focused role-by-role setup or the full matrix grid.
                </p>
            </div>
        </div>
        <div class="inline-flex rounded-xl bg-gray-100 p-1 border border-gray-200 w-full sm:w-auto">
            <button onclick="setViewMode('role')" id="btn-view-role"
                class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all">
                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                Role-Focused
            </button>
            <button onclick="setViewMode('grid')" id="btn-view-grid"
                class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all">
                <i data-lucide="grid" class="w-3.5 h-3.5"></i>
                Full Matrix
            </button>
        </div>
    </div>

    <!-- ROLE-FOCUSED VIEW CONTAINER -->
    <div id="rbac-role-container" class="hidden space-y-6">
        <!-- Role Selection Tabs -->
        <div class="flex flex-wrap gap-4 mb-2">
            <?php foreach ($roles as $roleKey => $roleLabel): ?>
                <button onclick="setActiveRoleTab('<?= $roleKey ?>')" id="role-tab-<?= $roleKey ?>"
                    class="role-tab flex items-center gap-2.5 px-5 py-3.5 rounded-xl border text-xs font-black uppercase tracking-wider transition-all shadow-sm">
                    <i data-lucide="<?= getRoleIcon($roleKey) ?>" class="w-4 h-4"></i>
                    <?= htmlspecialchars($roleLabel) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Role Contents Pane -->
        <?php foreach ($roles as $roleKey => $roleLabel): ?>
            <div id="role-content-<?= $roleKey ?>" class="role-content-pane hidden space-y-6">
                <?php foreach ($permissions as $category => $categoryPerms): ?>
                    <?php
                    $hasApplicableInCat = false;
                    foreach ($categoryPerms as $permKey => $permInfo) {
                        if (isset($roleApplicablePermissions[$roleKey]) && in_array($permKey, $roleApplicablePermissions[$roleKey])) {
                            $hasApplicableInCat = true;
                            break;
                        }
                    }
                    ?>
                    <?php if ($hasApplicableInCat): ?>
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                            <h3 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                <?= $category ?> Control
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($categoryPerms as $permKey => $permInfo): ?>
                                    <?php if (isset($roleApplicablePermissions[$roleKey]) && in_array($permKey, $roleApplicablePermissions[$roleKey])): ?>
                                        <?php $currentLevel = $activeMatrix[$roleKey][$permKey] ?? 0; ?>
                                        <div
                                            class="flex items-center justify-between p-4 rounded-2xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition-all gap-4">
                                            <div class="flex flex-col min-w-0">
                                                <span
                                                    class="text-sm font-extrabold text-gray-900 truncate leading-snug"><?= $permInfo['label'] ?></span>
                                                <span
                                                    class="text-[10px] text-gray-400 font-bold mt-1 leading-relaxed"><?= $permInfo['desc'] ?></span>
                                            </div>
                                            <div class="relative inline-block w-full max-w-[120px] shrink-0">
                                                <select onchange="updatePermission('<?= $roleKey ?>', '<?= $permKey ?>', this.value)"
                                                    class="w-full pl-3 pr-8 py-2 rounded-xl border-2 border-transparent bg-white text-xs font-black uppercase tracking-tighter transition-all focus:border-indigo-500 appearance-none cursor-pointer shadow-sm
                                                     <?= $currentLevel == 1 ? 'text-green-600 border-green-200 bg-green-50/30' : ($currentLevel == 2 ? 'text-amber-600 border-amber-200 bg-amber-50/30' : 'text-gray-400 border-gray-200') ?>">
                                                    <option value="0" <?= $currentLevel == 0 ? 'selected' : '' ?>>No Access</option>
                                                    <option value="1" <?= $currentLevel == 1 ? 'selected' : '' ?>>Full</option>
                                                    <option value="2" <?= $currentLevel == 2 ? 'selected' : '' ?>>Branch</option>
                                                </select>
                                                <i data-lucide="chevron-down"
                                                    class="absolute right-3 top-3 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- FULL MATRIX VIEW CONTAINER -->
    <div id="rbac-grid-container"
        class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden transition-all hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200">
                        <th
                            class="sticky left-0 z-20 bg-gray-50 px-6 py-4 text-left text-xs font-black text-gray-400 uppercase tracking-widest border-r border-gray-200 min-w-[280px]">
                            Functional Capability
                        </th>
                        <?php foreach ($roles as $roleKey => $roleLabel): ?>
                            <th class="px-6 py-4 text-center min-w-[140px]">
                                <p class="text-[11px] font-black text-gray-900 uppercase tracking-tighter">
                                    <?= htmlspecialchars($roleLabel) ?>
                                </p>
                                <span
                                    class="text-[9px] text-gray-400 font-bold uppercase tracking-widest leading-none"><?= strtoupper($roleKey) ?></span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($permissions as $category => $categoryPerms): ?>
                        <tr class="bg-gray-50/30">
                            <td colspan="<?= count($roles) + 1 ?>" class="px-6 py-2 h-10 border-y border-gray-100">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                                    <span
                                        class="text-[10px] font-black text-indigo-700 uppercase tracking-widest"><?= $category ?>
                                        Control</span>
                                </div>
                            </td>
                        </tr>

                        <?php foreach ($categoryPerms as $permKey => $permInfo): ?>
                            <tr class="group hover:bg-indigo-50/10 transition-colors">
                                <td
                                    class="sticky left-0 z-10 bg-white group-hover:bg-indigo-50/20 px-6 py-5 border-r border-gray-100 transition-colors">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-bold text-gray-800 tracking-tight leading-none mb-1.5"><?= $permInfo['label'] ?></span>
                                        <span
                                            class="text-[10px] text-gray-500 font-medium leading-relaxed max-w-[220px]"><?= $permInfo['desc'] ?></span>
                                    </div>
                                </td>

                                <?php foreach ($roles as $roleKey => $roleLabel): ?>
                                    <?php $currentLevel = $activeMatrix[$roleKey][$permKey] ?? 0; ?>
                                    <td class="px-4 py-5 text-center">
                                        <?php if (isset($roleApplicablePermissions[$roleKey]) && in_array($permKey, $roleApplicablePermissions[$roleKey])): ?>
                                            <div class="relative inline-block w-full max-w-[100px]">
                                                <select onchange="updatePermission('<?= $roleKey ?>', '<?= $permKey ?>', this.value)"
                                                    class="w-full pl-2 pr-6 py-1.5 rounded-lg border-2 border-transparent bg-gray-50 text-[10px] font-black uppercase tracking-tighter transition-all focus:bg-white focus:border-indigo-500 appearance-none cursor-pointer
                                             <?= $currentLevel == 1 ? 'text-green-600 bg-green-50' : ($currentLevel == 2 ? 'text-amber-600 bg-amber-50' : 'text-gray-400') ?>">
                                                    <option value="0" <?= $currentLevel == 0 ? 'selected' : '' ?>>No Access</option>
                                                    <option value="1" <?= $currentLevel == 1 ? 'selected' : '' ?>>Full</option>
                                                    <option value="2" <?= $currentLevel == 2 ? 'selected' : '' ?>>Branch</option>
                                                </select>
                                                <i data-lucide="chevron-down"
                                                    class="absolute right-2 top-2 w-3 h-3 text-gray-300 pointer-events-none"></i>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    async function updatePermission(role, perm, level) {
        const statusEl = document.getElementById('save-status');
        statusEl.classList.remove('hidden');
        statusEl.classList.add('flex');

        try {
            const formData = new FormData();
            formData.append('action', 'toggle_perm');
            formData.append('role', role);
            formData.append('perm', perm);
            formData.append('level', level);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                // Sync all select dropdowns with the same role and permission in both views
                document.querySelectorAll('select').forEach(select => {
                    const onchangeAttr = select.getAttribute('onchange');
                    if (onchangeAttr && onchangeAttr.includes(`'${role}'`) && onchangeAttr.includes(`'${perm}'`)) {
                        select.value = level;

                        // Update classes based on which view container it belongs to
                        if (select.classList.contains('text-[10px]')) {
                            // Grid select classes
                            select.className = "w-full pl-2 pr-6 py-1.5 rounded-lg border-2 border-transparent bg-gray-50 text-[10px] font-black uppercase tracking-tighter transition-all focus:bg-white focus:border-indigo-500 appearance-none cursor-pointer " +
                                (level == 1 ? 'text-green-600 bg-green-50' : (level == 2 ? 'text-amber-600 bg-amber-50' : 'text-gray-400'));
                        } else {
                            // Role-focused select classes
                            select.className = "w-full pl-3 pr-8 py-2 rounded-xl border-2 border-transparent bg-white text-xs font-black uppercase tracking-tighter transition-all focus:border-indigo-500 appearance-none cursor-pointer shadow-sm " +
                                (level == 1 ? 'text-green-600 border-green-200 bg-green-50/30' : (level == 2 ? 'text-amber-600 border-amber-200 bg-amber-50/30' : 'text-gray-400 border-gray-200'));
                        }
                    }
                });

                // Flash success color
                document.getElementById('save-status-text').textContent = 'Changes Saved';
                document.getElementById('save-status-icon').innerHTML = '<i data-lucide="check-circle" class="w-3 h-3 text-green-600"></i>';
                lucide.createIcons();

                setTimeout(() => {
                    statusEl.classList.add('hidden');
                    statusEl.classList.remove('flex');
                    document.getElementById('save-status-text').textContent = 'Saving Changes...';
                    document.getElementById('save-status-icon').innerHTML = '<i data-lucide="loader-2" class="w-3 h-3 text-green-600 animate-spin"></i>';
                    lucide.createIcons();
                }, 2000);
            } else {
                errorAlert('Update Failed', data.message || 'Unknown error');
            }
        } catch (err) {
            console.error('Update failed:', err);
            errorAlert('Network Error', 'A network error occurred while updating permissions.');
        }
    }

    function setViewMode(mode) {
        localStorage.setItem('citilife_rbac_view_mode', mode);
        const gridContainer = document.getElementById('rbac-grid-container');
        const roleContainer = document.getElementById('rbac-role-container');
        const btnGrid = document.getElementById('btn-view-grid');
        const btnRole = document.getElementById('btn-view-role');

        if (mode === 'grid') {
            gridContainer.classList.remove('hidden');
            roleContainer.classList.add('hidden');
            btnGrid.className = "flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all text-gray-700 hover:text-gray-900 bg-white shadow-sm border border-gray-200/50";
            btnRole.className = "flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all text-gray-500 hover:text-gray-700";
        } else {
            gridContainer.classList.add('hidden');
            roleContainer.classList.remove('hidden');
            btnRole.className = "flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all text-gray-700 hover:text-gray-900 bg-white shadow-sm border border-gray-200/50";
            btnGrid.className = "flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all text-gray-500 hover:text-gray-700";
        }
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function setActiveRoleTab(roleKey) {
        localStorage.setItem('citilife_rbac_active_role', roleKey);

        // Hide all contents
        document.querySelectorAll('.role-content-pane').forEach(el => el.classList.add('hidden'));

        // Show active content
        const activePane = document.getElementById('role-content-' + roleKey);
        if (activePane) activePane.classList.remove('hidden');

        // Deactivate all tabs
        document.querySelectorAll('.role-tab').forEach(el => {
            el.className = "role-tab flex items-center gap-2.5 px-5 py-3.5 rounded-xl border text-xs font-black uppercase tracking-wider transition-all shadow-sm bg-white text-gray-500 border-gray-200 hover:text-gray-700 hover:border-gray-300";
        });

        // Activate active tab
        const activeTab = document.getElementById('role-tab-' + roleKey);
        if (activeTab) {
            activeTab.className = "role-tab flex items-center gap-2.5 px-5 py-3.5 rounded-xl border text-xs font-black uppercase tracking-wider transition-all shadow-sm bg-indigo-600 text-white border-indigo-600";
        }
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Initialize View & Role Tab from LocalStorage
    (function () {
        const savedMode = localStorage.getItem('citilife_rbac_view_mode') || 'role';
        const savedRole = localStorage.getItem('citilife_rbac_active_role') || 'it_admin';

        // Run after DOM is ready or immediately
        const init = () => {
            setViewMode(savedMode);
            setActiveRoleTab(savedRole);
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>