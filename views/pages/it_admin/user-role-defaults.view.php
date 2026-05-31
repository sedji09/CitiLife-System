<!-- IT Dynamic User Role Matrix -->
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
                <i data-lucide="loader-2" class="w-3 h-3 text-green-600 animate-spin"></i>
                <span class="text-[10px] font-bold text-green-700 uppercase tracking-widest leading-none">Saving
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

    <!-- Permission Matrix Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden transition-all">
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
                // Flash success color
                statusEl.querySelector('span').textContent = 'Changes Saved';
                statusEl.querySelector('i').setAttribute('data-lucide', 'check-circle');
                lucide.createIcons();

                setTimeout(() => {
                    statusEl.classList.add('hidden');
                    statusEl.classList.remove('flex');
                    statusEl.querySelector('span').textContent = 'Saving Changes...';
                    statusEl.querySelector('i').setAttribute('data-lucide', 'loader-2');
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

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>