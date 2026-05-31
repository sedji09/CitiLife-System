<!-- IT Admin Dashboard (Redesigned for Consistency) -->
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">IT System Dashboard</h2>
            <p class="text-sm text-gray-500 mt-1">Real-time system health and administration oversight.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-100 rounded-full">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">System Status:
                    <?= $uptime ?></span>
            </div>
            <div class="text-xs font-medium text-gray-400 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                <span id="server-clock-standard"><?= date('H:i:s') ?></span>
            </div>
        </div>
    </div>

    <!-- Key Metrics Grid (Consistent with RadTech style) -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
        <!-- Total Users -->
        <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition group">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <div
                    class="p-2 bg-blue-50 rounded-lg text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2 text-gray-900"><?= number_format($totalUsers) ?></p>
        </div>

        <!-- Active Sessions -->
        <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition group">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">Active Accounts</p>
                <div
                    class="p-2 bg-emerald-50 rounded-lg text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2 text-gray-900"><?= number_format($activeUsers) ?></p>
        </div>

        <!-- Total Patients -->
        <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition group">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">Patients</p>
                <div
                    class="p-2 bg-amber-50 rounded-lg text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                    <i data-lucide="heart-pulse" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2 text-gray-900"><?= number_format($totalPatients) ?></p>
        </div>

        <!-- Total Cases -->
        <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition group">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">Total Cases</p>
                <div
                    class="p-2 bg-rose-50 rounded-lg text-rose-500 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                    <i data-lucide="folder-kanban" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2 text-gray-900"><?= number_format($totalCases) ?></p>
        </div>

        <!-- DB Status -->
        <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition group">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">DB Version</p>
                <div
                    class="p-2 bg-indigo-50 rounded-lg text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                    <i data-lucide="database" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-xl font-bold mt-3 text-gray-700 truncate"><?= explode('-', $mysqlVersion)[0] ?></p>
        </div>
    </div>

    <!-- Main Content Areas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Resource Monitor & Logs -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Server Resources -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h3 class="font-bold text-gray-900 text-sm">Server Resource Logs</h3>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Live Diagnostics</span>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Disk Usage -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-end">
                            <div class="flex items-center gap-2">
                                <i data-lucide="hard-drive" class="w-4 h-4 text-gray-400"></i>
                                <span class="text-xs font-bold text-gray-700">Disk Storage Occupancy</span>
                            </div>
                            <span class="text-xs font-black text-indigo-600"><?= $diskUsagePercent ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden border border-gray-200">
                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000"
                                style="width: <?= $diskUsagePercent ?>%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] font-medium text-gray-400">
                            <span>Used: <?= $formattedDiskUsed ?></span>
                            <span>Total: <?= $formattedDiskTotal ?></span>
                        </div>
                    </div>

                    <!-- Memory/RAM (Simulated for display) -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-end">
                            <div class="flex items-center gap-2">
                                <i data-lucide="cpu" class="w-4 h-4 text-gray-400"></i>
                                <span class="text-xs font-bold text-gray-700">Memory Allocation</span>
                            </div>
                            <span class="text-xs font-black text-emerald-600">32%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden border border-gray-200">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-1000"
                                style="width: 32%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Audit Logs -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-sm">Recent System Activity</h3>
                    <a href="?page=audit-logs"
                        class="text-xs font-bold text-red-600 hover:text-red-700 hover:underline">View All Logs</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-tighter">
                                    Timestamp</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-tighter">User
                                </th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-tighter">
                                    Action</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-tighter">
                                    Module</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($recentLogs as $log): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 tabular-nums text-gray-500">
                                        <?= date('M d, H:i', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-3 font-semibold text-gray-700">
                                        <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600"><?= htmlspecialchars($log['action']) ?></td>
                                    <td class="px-6 py-3">
                                        <span
                                            class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-bold border border-gray-200"
                                            style="font-size: 9px;">
                                            <?= htmlspecialchars($log['module'] ?? 'System') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentLogs)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-400">No recent activity
                                        recorded.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Stats & Actions -->
        <div class="space-y-6">

            <!-- System Specifications -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden p-6">
                <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-4">
                    <i data-lucide="info" class="w-4 h-4 text-red-600"></i>
                    <h3 class="font-bold text-gray-900 text-sm">System Specifications</h3>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">PHP Version</span>
                        <span class="text-xs font-bold text-gray-800"><?= $phpVersion ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Database Engine</span>
                        <span class="text-xs font-bold text-gray-800">MariaDB 10.4.32</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Latest Backup</span>
                        <span
                            class="text-xs font-bold text-emerald-600 truncate max-w-[120px]"><?= $latestBackup ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Environment</span>
                        <span
                            class="text-[10px] font-black bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100 uppercase tracking-widest">Development</span>
                    </div>
                </div>
            </div>

            <!-- Quick Management Actions -->
            <div class="bg-gray-900 rounded-xl p-6 shadow-xl relative overflow-hidden group">
                <!-- Decorative circle -->
                <div
                    class="absolute -top-10 -right-10 w-32 h-32 bg-red-600/20 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700">
                </div>

                <h3 class="font-bold text-white text-sm mb-4 relative z-10">Quick Management</h3>
                <div class="space-y-3 relative z-10">
                    <a href="?page=security-settings"
                        class="flex items-center gap-3 w-full p-3 rounded-lg bg-white/5 hover:bg-white/10 text-white border border-white/10 transition group/item">
                        <i data-lucide="shield-lock" class="w-4 h-4 text-red-500"></i>
                        <span class="text-xs font-semibold">Security Settings</span>
                    </a>
                    <button type="button"
                        class="flex items-center gap-3 w-full p-3 rounded-lg bg-white/5 hover:bg-white/10 text-white border border-white/10 transition group/item">
                        <i data-lucide="download-cloud" class="w-4 h-4 text-blue-500"></i>
                        <span class="text-xs font-semibold">Download Backup</span>
                    </button>
                </div>
            </div>

            <!-- System Guard Alert -->
            <div class="bg-red-50 rounded-xl border border-red-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="mt-1 p-1 bg-red-100 rounded text-red-600">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </div>
                    <div class="space-y-1">
                        <span
                            class="text-[10px] font-black text-red-700 uppercase tracking-widest leading-none">Maintenance
                            Alert</span>
                        <p class="text-[11px] text-red-600/80 font-medium leading-relaxed">
                            Database optimization is recommended every 30 days. Last manual check was 12 days ago.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
    // System clock update (Standard style)
    setInterval(() => {
        const clock = document.getElementById('server-clock-standard');
        if (clock) {
            const now = new Date();
            clock.textContent = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
    }, 1000);

    // Initial icon sync
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>