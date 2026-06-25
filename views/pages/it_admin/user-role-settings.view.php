<?php
$roles = [
    'it_admin' => 'IT System Admin',
    'admin_central' => 'Admin (Central)',
    'branch_admin' => 'Branch Admin',
    'radtech' => 'RadTech (Staff)',
    'radiologist' => 'Radiologist',
    'patient' => 'Patient'
];

$roleDescriptions = [
    'it_admin' => 'Manages system infrastructure, security, backups, and audit logs.',
    'admin_central' => 'Oversees all branches, users, and reports at the system level.',
    'branch_admin' => 'Manages records and operations within their assigned branch.',
    'radtech' => 'Handles patient registration, worklist, and record requests.',
    'radiologist' => 'Reviews cases, reads images, and submits diagnostic reports.',
    'patient' => 'Portal access for viewing personal health records.',
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

function getRoleColor($roleKey)
{
    switch ($roleKey) {
        case 'it_admin':
            return 'text-indigo-600 bg-indigo-50';
        case 'admin_central':
            return 'text-pink-600 bg-pink-50';
        case 'branch_admin':
            return 'text-emerald-600 bg-emerald-50';
        case 'radtech':
            return 'text-orange-600 bg-orange-50';
        case 'radiologist':
            return 'text-sky-600 bg-sky-50';
        case 'patient':
            return 'text-purple-600 bg-purple-50';
        default:
            return 'text-gray-600 bg-gray-50';
    }
}
?>

<div class="max-w-5xl mx-auto space-y-8 pb-20">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-lg font-bold text-gray-900 tracking-tight">Access Control Center</h1>
            <p class="text-xs text-gray-500 mt-0.5">Configure permissions and security boundaries for staff roles.</p>
        </div>
        <div id="save-status"
            class="hidden items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-100 rounded-full transition-all">
            <i data-lucide="loader-2" class="w-3.5 h-3.5 text-green-600 animate-spin" id="save-status-icon"></i>
            <span id="save-status-text"
                class="text-[10px] font-bold text-green-700 uppercase tracking-widest">Saving...</span>
        </div>
    </div>

    <!-- Role Selector + Save Button Row -->
    <div class="flex items-end justify-between relative z-50">
        <div class="relative flex-1 max-w-sm">
            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Select
                Role</label>
            <button type="button" onclick="toggleRoleDropdown()" id="role-selector-btn"
                class="w-full flex items-center justify-between px-3 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <div class="flex items-center gap-3">
                    <div id="selected-role-icon-bg"
                        class="w-8 h-8 rounded-lg flex items-center justify-center bg-indigo-50 text-indigo-600">
                        <i id="selected-role-icon" data-lucide="shield-alert" class="w-4 h-4"></i>
                    </div>
                    <div class="text-left">
                        <p id="selected-role-title" class="text-sm font-semibold text-gray-900 leading-tight">IT System
                            Admin</p>
                        <p id="selected-role-desc" class="text-[10px] text-gray-400 mt-0.5 truncate max-w-[200px]">
                            Manages system infrastructure, security, backups, and audit logs.</p>
                    </div>
                </div>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
            </button>

            <!-- Dropdown Menu -->
            <div id="role-dropdown-menu"
                class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden divide-y divide-gray-50">
                <?php foreach ($roles as $roleKey => $roleLabel): ?>
                    <button type="button"
                        onclick="selectRole('<?= $roleKey ?>', '<?= htmlspecialchars($roleLabel) ?>', '<?= htmlspecialchars($roleDescriptions[$roleKey] ?? '') ?>', '<?= getRoleIcon($roleKey) ?>', '<?= getRoleColor($roleKey) ?>')"
                        class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 transition-colors text-left group">
                        <div
                            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 <?= getRoleColor($roleKey) ?>">
                            <i data-lucide="<?= getRoleIcon($roleKey) ?>" class="w-3.5 h-3.5"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-900 leading-none"><?= htmlspecialchars($roleLabel) ?>
                            </p>
                            <p class="text-[10px] text-gray-400 mt-1">
                                <?= htmlspecialchars($roleDescriptions[$roleKey] ?? '') ?>
                            </p>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Save Button -->
        <button type="button" onclick="saveAllPermissions()" id="btn-save-all"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 shadow-sm">
            <i data-lucide="save" class="w-3.5 h-3.5"></i>
            Save Changes
        </button>
    </div>

    <!-- Flat List Permissions Container -->
    <div id="permissions-container" class="mt-4">
        <?php foreach ($permissions as $category => $categoryPerms): ?>
            <!-- Category Section -->
            <div class="rbac-category mb-6" data-category="<?= htmlspecialchars($category) ?>">
                <!-- Category Header -->
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-t-lg">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider"><?= $category ?></h3>
                </div>

                <!-- Permission Rows -->
                <div class="border-x border-b border-gray-200 rounded-b-lg bg-white divide-y divide-gray-100">
                    <?php foreach ($categoryPerms as $permKey => $permInfo): ?>
                        <div class="perm-item flex items-center justify-between px-4 py-3" data-perm="<?= $permKey ?>">
                            <!-- Permission Label & Description -->
                            <div class="min-w-0 pr-4">
                                <p class="text-sm font-medium text-gray-800"><?= $permInfo['label'] ?></p>
                                <p class="text-[11px] text-gray-400 mt-0.5"><?= $permInfo['desc'] ?></p>
                            </div>

                            <!-- Segmented Control -->
                            <div
                                class="flex items-center rounded-md overflow-hidden border border-gray-200 shrink-0 segmented-control">
                                <button type="button" onclick="setPermission('<?= $permKey ?>', 0)" id="btn-<?= $permKey ?>-0"
                                    class="perm-btn px-4 py-1.5 text-[11px] font-semibold transition-colors focus:outline-none border-r border-gray-200">
                                    None
                                </button>
                                <button type="button" onclick="setPermission('<?= $permKey ?>', 2)" id="btn-<?= $permKey ?>-2"
                                    class="perm-btn px-4 py-1.5 text-[11px] font-semibold transition-colors focus:outline-none border-r border-gray-200">
                                    Branch
                                </button>
                                <button type="button" onclick="setPermission('<?= $permKey ?>', 1)" id="btn-<?= $permKey ?>-1"
                                    class="perm-btn px-4 py-1.5 text-[11px] font-semibold transition-colors focus:outline-none">
                                    Full
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
    // System Data
    const activeMatrix = <?= json_encode($activeMatrix) ?>;

    // State
    let currentRole = localStorage.getItem('citilife_rbac_role') || 'it_admin';
    let currentMatrix = JSON.parse(JSON.stringify(activeMatrix));

    // Initialize Page
    document.addEventListener('DOMContentLoaded', () => {
        // Load saved role or default
        const btn = document.querySelector(`button[onclick*="'${currentRole}'"]`);
        if (btn) {
            btn.click();
        } else {
            renderPermissions(currentRole);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('role-dropdown-menu');
            const selector = document.getElementById('role-selector-btn');
            if (!selector.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    });

    function toggleRoleDropdown() {
        document.getElementById('role-dropdown-menu').classList.toggle('hidden');
    }

    function selectRole(roleKey, label, desc, icon, colorClasses) {
        currentRole = roleKey;
        localStorage.setItem('citilife_rbac_role', roleKey);

        // Update selector UI
        document.getElementById('selected-role-title').textContent = label;
        document.getElementById('selected-role-desc').textContent = desc;

        const iconBg = document.getElementById('selected-role-icon-bg');
        iconBg.className = `w-8 h-8 rounded-lg flex items-center justify-center ${colorClasses}`;

        const iconEl = document.getElementById('selected-role-icon');
        iconEl.setAttribute('data-lucide', icon);

        // Hide dropdown
        document.getElementById('role-dropdown-menu').classList.add('hidden');

        // Render permissions for this role
        renderPermissions(roleKey);

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function renderPermissions(roleKey) {
        document.querySelectorAll('.perm-item').forEach(item => {
            const permKey = item.dataset.perm;
            // Get level from DB state, default to 0 if not set
            const level = (currentMatrix[roleKey] && currentMatrix[roleKey][permKey]) ?? 0;
            updateSegmentedControlUI(permKey, level);
        });
    }

    function updateSegmentedControlUI(permKey, level) {
        const activeClasses = 'bg-indigo-500 text-white hover:bg-indigo-600';
        const inactiveClasses = 'bg-gray-50 text-gray-600 hover:bg-gray-100';

        const buttons = [
            { id: `btn-${permKey}-0`, isMatch: level == 0, hasBorderR: true },  // None
            { id: `btn-${permKey}-2`, isMatch: level == 2, hasBorderR: true },  // Branch
            { id: `btn-${permKey}-1`, isMatch: level == 1, hasBorderR: false }  // Full (last)
        ];

        buttons.forEach(btnConfig => {
            const btn = document.getElementById(btnConfig.id);
            if (btn) {
                const borderClass = btnConfig.hasBorderR ? 'border-r border-gray-200' : '';
                if (btnConfig.isMatch) {
                    btn.className = `perm-btn px-4 py-1.5 text-[11px] font-semibold transition-colors focus:outline-none ${borderClass} ${activeClasses}`;
                } else {
                    btn.className = `perm-btn px-4 py-1.5 text-[11px] font-semibold transition-colors focus:outline-none ${borderClass} ${inactiveClasses}`;
                }
            }
        });
    }

    // Track pending (unsaved) changes: { 'role|perm': { role, perm, level } }
    let pendingChanges = {};

    function setPermission(permKey, level) {
        // Safe-guard to prevent IT Admin from locking themselves out
        if (currentRole === 'it_admin' && permKey === 'system_security' && level === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Security Lock',
                text: 'You cannot remove System Security access from the IT Admin role as it would lock you out of this page.',
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Understood'
            });
            // Revert the segmented control UI back to Full Access visually (Level 1)
            updateSegmentedControlUI('system_security', 1);
            return;
        }

        // Update UI only
        if (!currentMatrix[currentRole]) currentMatrix[currentRole] = {};
        currentMatrix[currentRole][permKey] = level;
        updateSegmentedControlUI(permKey, level);

        // Track pending change
        const key = `${currentRole}|${permKey}`;
        pendingChanges[key] = { role: currentRole, perm: permKey, level: level };
    }

    async function saveAllPermissions() {
        const changes = Object.values(pendingChanges);
        if (changes.length === 0) return;

        // Show Saving
        const statusEl = document.getElementById('save-status');
        statusEl.classList.remove('hidden');
        statusEl.classList.add('flex');
        document.getElementById('save-status-text').textContent = 'Saving...';
        document.getElementById('save-status-icon').setAttribute('data-lucide', 'loader-2');
        document.getElementById('save-status-icon').classList.add('animate-spin');
        if (typeof lucide !== 'undefined') lucide.createIcons();

        try {
            for (const change of changes) {
                const formData = new FormData();
                formData.append('action', 'toggle_perm');
                formData.append('role', change.role);
                formData.append('perm', change.perm);
                formData.append('level', change.level);

                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to save');
                }
            }

            // All saved successfully
            document.getElementById('save-status-text').textContent = 'All Changes Saved';
            document.getElementById('save-status-icon').setAttribute('data-lucide', 'check-circle');
            document.getElementById('save-status-icon').classList.remove('animate-spin');
            if (typeof lucide !== 'undefined') lucide.createIcons();
            pendingChanges = {};

            setTimeout(() => {
                window.location.reload();
            }, 800);
        } catch (err) {
            console.error('Save failed:', err);
            alert('Failed to save some permission changes. Please try again.');
            statusEl.classList.add('hidden');
            statusEl.classList.remove('flex');
        }
    }
</script>