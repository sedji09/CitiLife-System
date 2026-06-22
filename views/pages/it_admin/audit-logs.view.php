<!-- IT Global Audit Logs -->
<style>
    /* Custom Status Badges */
    .status-badge {
        transition: all 0.2s ease;
    }

    .theme-dark .status-badge {
        background-color: rgba(255, 255, 255, 0.03) !important;
        border-width: 1px !important;
        border-style: solid !important;
    }

    /* Status-Specific Dark Mode Overrides (Enhanced Visibility) */
    .theme-dark .status-badge-green {
        border-color: #10b981 !important;
        color: #34d399 !important;
        background-color: rgba(16, 185, 129, 0.1) !important;
        box-shadow: 0 0 0 0.5px #10b981 !important;
    }

    .theme-dark .status-badge-red {
        border-color: #f87171 !important;
        color: #fca5a5 !important;
        background-color: rgba(239, 68, 68, 0.1) !important;
        box-shadow: 0 0 0 0.5px #ef4444 !important;
    }

    .theme-dark .status-badge-gray {
        border-color: #64748b !important;
        color: #cbd5e1 !important;
        background-color: rgba(100, 116, 139, 0.1) !important;
    }
</style>
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">System Audit logs</h1>
            <p class="text-sm text-gray-500 mt-1">Real-time global monitoring of all system events and administrative
                actions.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-full">
                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                <span class="text-[10px] font-black text-indigo-700 uppercase tracking-widest leading-none">Live
                    Monitoring</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <input type="hidden" name="page" value="audit-logs">

            <!-- Search -->
            <div class="lg:col-span-2">
                <div class="relative group">
                    <i data-lucide="search"
                        class="absolute left-3 top-2.5 w-4 h-4 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                        placeholder="Search action, user, or details..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                </div>
            </div>

            <!-- Role Filter -->
            <div>
                <select name="role"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl text-xs font-bold uppercase tracking-widest focus:border-indigo-500">
                    <option value="">All Roles</option>
                    <?php foreach ($distinctRoles as $roleOption): ?>
                        <option value="<?= $roleOption ?>" <?= $filters['role'] == $roleOption ? 'selected' : '' ?>>
                            <?= strtoupper($roleOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:border-indigo-500">
            </div>

            <!-- End Date -->
            <div>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:border-indigo-500">
            </div>

            <!-- Submit -->
            <div class="flex gap-2">
                <button type="submit"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2 flex items-center justify-center gap-2 transition shadow-sm">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span class="text-xs font-bold uppercase tracking-widest leading-none">Filter</span>
                </button>
                <a href="?page=audit-logs"
                    class="p-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"
                    title="Reset Filters">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Timestamp
                        </th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Actor</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Branch</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Category
                        </th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Action</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                        <th
                            class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">
                            Info</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center gap-4 opacity-30">
                                    <i data-lucide="clipboard-list" class="w-16 h-16"></i>
                                    <p class="text-sm font-black uppercase tracking-widest">No audit records found matching
                                        your filters</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="text-[11px] font-black text-gray-900 tabular-nums">
                                    <?= date('M d, Y', strtotime($log['created_at'])) ?>
                                </span>
                                <p class="text-[10px] text-gray-400 font-bold">
                                    <?= date('h:i:s A', strtotime($log['created_at'])) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                                        <?= strtoupper(substr($log['user_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-xs font-bold text-gray-800 tracking-tight leading-none mb-1"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></span>
                                        <span
                                            class="text-[9px] font-black <?= $log['user_role'] == 'it_admin' ? 'text-rose-500' : 'text-gray-400' ?> uppercase tracking-widest"><?= $log['user_role'] ?? 'AUTOMATED' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 bg-gray-100 rounded text-[9px] font-black text-gray-500 uppercase tracking-widest"><?= htmlspecialchars($log['branch_name'] ?? 'GLOBAL') ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="text-[10px] font-bold text-gray-600 uppercase tracking-tight"><?= htmlspecialchars($log['module'] ?? 'Unknown') ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-gray-700 leading-relaxed max-w-xs">
                                    <?= htmlspecialchars($log['action']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusLabel = 'Successful';
                                $sColor = 'green';
                                if (stripos($log['action'], 'Rejected') !== false || (isset($log['details']) && stripos($log['details'], 'Rejected') !== false)) {
                                    $statusLabel = 'Unsuccessful';
                                    $sColor = 'red';
                                } elseif ($log['module'] === 'Patient Management' && strpos($log['action'], 'Registered') !== false) {
                                    $statusLabel = 'Pending';
                                    $sColor = 'red';
                                } elseif (strpos($log['action'], 'Password Reset') !== false) {
                                    $sColor = 'gray';
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-<?= $sColor ?>-50 text-<?= $sColor ?>-700 border border-<?= $sColor === 'red' ? 'red-500' : $sColor . '-400' ?> status-badge status-badge-<?= $sColor ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="showDetails(<?= htmlspecialchars(json_encode($log)) ?>)"
                                    class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                    <i data-lucide="info" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    Showing <?= count($logs) ?> of <?= $total_count ?> entries
                </span>
                <div class="flex gap-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=audit-logs&p=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all
                       <?= $page_num == $i ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white border border-gray-200 text-gray-500 hover:border-indigo-200' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div id="logModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-2xl overflow-hidden shadow-2xl animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest">Action Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x"
                    class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">IP Address</p>
                    <p id="modalIp" class="text-xs font-bold text-gray-700 tabular-nums">0.0.0.0</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Target Module</p>
                    <p id="modalModule" class="text-xs font-bold text-gray-700"></p>
                </div>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Detailed Logs</p>
                <div id="modalDetails"
                    class="bg-gray-50 rounded-xl p-3 border border-gray-100 text-[11px] text-gray-600 font-medium leading-relaxed whitespace-pre-wrap">
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50/50 flex justify-end">
            <button onclick="closeModal()"
                class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition">Close</button>
        </div>
    </div>
</div>

<script>
    function showDetails(log) {
        document.getElementById('modalIp').textContent = log.ip_address || 'Unknown';
        document.getElementById('modalModule').textContent = log.module || 'System';
        document.getElementById('modalDetails').textContent = log.details || 'No additional parameters provided.';

        const modal = document.getElementById('logModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('logModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>