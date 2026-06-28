<?php
$statusTextClass = match ($systemStatusTone ?? 'emerald') {
    'rose' => 'noc-text-rose',
    'amber' => 'noc-text-amber',
    default => 'noc-text-emerald',
};
$statusBgClass = match ($systemStatusTone ?? 'emerald') {
    'rose' => 'noc-bg-rose',
    'amber' => 'noc-bg-amber',
    default => 'noc-bg-emerald',
};
$storageTextClass = match ($storageStatusTone ?? 'indigo') {
    'rose' => 'noc-text-rose',
    'amber' => 'noc-text-amber',
    default => 'noc-text-indigo',
};
$storageBarClass = match ($storageStatusTone ?? 'indigo') {
    'rose' => 'noc-bg-rose',
    'amber' => 'noc-bg-amber',
    default => 'noc-bg-indigo',
};
$serverTextClass = match ($serverStatus) {
    'Stopped' => 'noc-text-rose',
    'Warning' => 'noc-text-amber',
    default => 'noc-text-emerald',
};
$dbStatusTone = ($dbProfile['status'] ?? '') === 'Online' ? 'emerald' : 'rose';
?>
<!-- IT Admin Dashboard -->
<div class="space-y-6 noc-dashboard">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight flex items-center gap-2 noc-text-main">
                <i data-lucide="terminal" class="w-6 h-6 noc-text-indigo"></i>
                IT Command Center
            </h2>
            <p class="text-sm mt-1 font-mono noc-text-muted">System administration · security · monitoring</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full noc-card">
                <div class="w-2 h-2 rounded-full animate-pulse <?= $statusBgClass ?>"
                    style="box-shadow: 0 0 8px var(--noc-accent-<?= $systemStatusTone === 'rose' ? 'rose' : ($systemStatusTone === 'amber' ? 'amber' : 'emerald') ?>);">
                </div>
                <span class="text-[11px] font-bold uppercase tracking-wider font-mono <?= $statusTextClass ?>">SYSTEM:
                    <?= htmlspecialchars($systemStatus) ?></span>
            </div>
            <div class="text-xs font-mono font-medium px-3 py-1.5 rounded-lg noc-card noc-text-muted">
                <span id="server-clock-standard"><?= date('H:i:s') ?></span>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="flex overflow-x-auto gap-3 pb-2 w-full">
        <div class="rounded-xl p-4 noc-card flex-1 flex flex-col justify-between" style="min-width: 140px;">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[13px] font-medium noc-text-muted">Total Users</p>
                <div class="p-1.5 rounded-lg" style="background-color: rgba(79, 70, 229, 0.1);">
                    <i data-lucide="users" class="w-4 h-4 noc-text-indigo"></i>
                </div>
            </div>
            <p class="text-2xl font-black font-mono noc-text-main"><?= number_format($totalUsers) ?></p>
        </div>
        <div class="rounded-xl p-4 noc-card flex-1 flex flex-col justify-between" style="min-width: 140px;">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[13px] font-medium noc-text-muted">Active Users</p>
                <div class="p-1.5 rounded-lg" style="background-color: rgba(6, 182, 212, 0.1);">
                    <i data-lucide="activity" class="w-4 h-4 noc-text-cyan"></i>
                </div>
            </div>
            <p class="text-2xl font-black font-mono noc-text-main" id="realtime-active-users">
                <?= number_format($activeUsers) ?>
            </p>
        </div>
        <div class="rounded-xl p-4 noc-card flex-1 flex flex-col justify-between" style="min-width: 140px;">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[13px] font-medium noc-text-muted">Total Branches</p>
                <div class="p-1.5 rounded-lg" style="background-color: rgba(217, 119, 6, 0.1);">
                    <i data-lucide="git-merge" class="w-4 h-4 noc-text-amber"></i>
                </div>
            </div>
            <p class="text-2xl font-black font-mono noc-text-main"><?= number_format($totalBranches) ?></p>
        </div>
        <div class="rounded-xl p-4 noc-card flex-1 flex flex-col justify-between" style="min-width: 140px;">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[13px] font-medium noc-text-muted">System Status</p>
                <div class="p-1.5 rounded-lg" style="background-color: rgba(16, 185, 129, 0.1);">
                    <i data-lucide="server" class="w-4 h-4 noc-text-emerald"></i>
                </div>
            </div>
            <p class="text-2xl font-black font-mono <?= $statusTextClass ?> uppercase">
                <?= htmlspecialchars($systemStatus) ?>
            </p>
        </div>
        <div class="rounded-xl p-4 noc-card flex-1 flex flex-col justify-between" style="min-width: 140px;">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[13px] font-medium noc-text-muted">Database</p>
                <div class="p-1.5 rounded-lg" style="background-color: rgba(6, 182, 212, 0.1);">
                    <i data-lucide="database" class="w-4 h-4 noc-text-cyan"></i>
                </div>
            </div>
            <p class="text-sm font-bold noc-text-muted"><?= htmlspecialchars($dbProfile['type']) ?> Database</p>
            <p
                class="text-2xl font-black font-mono <?= $dbStatusTone === 'emerald' ? 'noc-text-emerald' : 'noc-text-rose' ?> uppercase">
                <?= htmlspecialchars($dbProfile['status']) ?>
            </p>
        </div>
        <div class="rounded-xl p-4 noc-card flex-1 flex flex-col justify-between" style="min-width: 140px;">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[13px] font-medium noc-text-muted">Storage</p>
                <div class="p-1.5 rounded-lg" style="background-color: rgba(79, 70, 229, 0.1);">
                    <i data-lucide="hard-drive" class="w-4 h-4 noc-text-indigo"></i>
                </div>
            </div>
            <p class="text-sm font-bold noc-text-muted"><?= $formattedDiskUsed ?> / <?= $formattedDiskTotal ?></p>
            <p class="text-2xl font-black font-mono <?= $storageTextClass ?>"><?= $diskUsagePercentage ?>% Used</p>
        </div>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="it-dash-grid">

        <!-- LEFT COLUMN -->
        <div class="it-dash-col-main">

            <!-- System Health -->
            <div class="rounded-xl overflow-hidden relative noc-card">
                <div class="absolute inset-0 z-0"
                    style="background-image: linear-gradient(var(--noc-grid-color) 1px, transparent 1px), linear-gradient(90deg, var(--noc-grid-color) 1px, transparent 1px); background-size: 20px 20px; opacity: var(--noc-grid-opacity);">
                </div>
                <div class="px-6 py-4 flex items-center justify-between relative z-10 noc-card-alt noc-border-b">
                    <h3 class="font-bold text-sm flex items-center gap-2 noc-text-main">
                        <i data-lucide="heart-pulse" class="w-4 h-4 <?= $statusTextClass ?>"></i>
                        System Health
                    </h3>
                    <span class="text-[10px] font-black uppercase tracking-widest <?= $serverTextClass ?>">
                        Overall: <?= htmlspecialchars($systemStatus) ?>
                    </span>
                </div>

                <!-- Compact Service Status Summary -->
                <div class="px-6 py-4 relative z-10 noc-border-b" style="background: var(--noc-card-bg);">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                        <!-- Server -->
                        <div class="flex items-center gap-3">
                            <?php
                            $serverOk = $serverStatus === 'Running';
                            $serverWarn = $serverStatus === 'Warning';
                            ?>
                            <div class="w-2.5 h-2.5 rounded-full shrink-0"
                                style="background-color: <?= $serverOk ? '#10b981' : ($serverWarn ? '#f59e0b' : '#f43f5e') ?>; box-shadow: 0 0 12px <?= $serverOk ? 'rgba(16,185,129,0.6)' : ($serverWarn ? 'rgba(245,158,11,0.6)' : 'rgba(244,63,94,0.6)') ?>;">
                            </div>

                            <div>
                                <p class="text-[10px] font-bold noc-text-muted uppercase tracking-wider">Server</p>
                                <p class="text-xs font-bold <?= $serverTextClass ?>">
                                    <?= htmlspecialchars($serverStatus) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Database -->
                        <div class="flex items-center gap-3">
                            <?php $dbOk = ($dbProfile['status'] ?? '') === 'Online'; ?>

                            <div class="w-2.5 h-2.5 rounded-full shrink-0"
                                style="background-color: <?= $dbOk ? '#10b981' : '#f43f5e' ?>; box-shadow: 0 0 12px <?= $dbOk ? 'rgba(16,185,129,0.6)' : 'rgba(244,63,94,0.6)' ?>;">
                            </div>

                            <div>
                                <p class="text-[10px] font-bold noc-text-muted uppercase tracking-wider">Database</p>
                                <p
                                    class="text-xs font-bold <?= $dbStatusTone === 'emerald' ? 'noc-text-emerald' : 'noc-text-rose' ?>">
                                    <?= htmlspecialchars($dbProfile['status'] ?? '—') ?>
                                </p>
                            </div>
                        </div>

                        <!-- Backup -->
                        <div class="flex items-center gap-3">
                            <?php
                            $backupOk = $backupDirWritable &&
                                $lastBackupTimestamp !== null &&
                                (time() - $lastBackupTimestamp) <= 7 * 86400;
                            ?>

                            <div class="w-2.5 h-2.5 rounded-full shrink-0"
                                style="background-color: <?= $backupOk ? '#10b981' : '#f59e0b' ?>; box-shadow: 0 0 12px <?= $backupOk ? 'rgba(16,185,129,0.6)' : 'rgba(245,158,11,0.6)' ?>;">
                            </div>

                            <div>
                                <p class="text-[10px] font-bold noc-text-muted uppercase tracking-wider">Backup</p>
                                <p class="text-xs font-bold <?= $backupOk ? 'noc-text-emerald' : 'noc-text-amber' ?>">
                                    <?= $lastBackupTimestamp ? date('M d', $lastBackupTimestamp) : 'Never' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Storage -->
                        <div class="flex items-center gap-3">
                            <?php
                            $storageOk = $diskUsagePercentage < 85;
                            $storageWarn = $diskUsagePercentage < 95;
                            ?>

                            <div class="w-2.5 h-2.5 rounded-full shrink-0"
                                style="background-color: <?= $storageOk ? '#10b981' : ($storageWarn ? '#f59e0b' : '#f43f5e') ?>; box-shadow: 0 0 12px <?= $storageOk ? 'rgba(16,185,129,0.6)' : ($storageWarn ? 'rgba(245,158,11,0.6)' : 'rgba(244,63,94,0.6)') ?>;">
                            </div>

                            <div>
                                <p class="text-[10px] font-bold noc-text-muted uppercase tracking-wider">Storage</p>
                                <p class="text-xs font-bold <?= $storageTextClass ?>">
                                    <?= $diskUsagePercentage ?>% Used
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                <?php if (!empty($healthIssues)): ?>
                    <div class="px-6 py-3 relative z-10 noc-border-b" style="background-color: rgba(217, 119, 6, 0.08);">
                        <p class="text-[10px] font-bold uppercase tracking-wider noc-text-amber mb-1.5">Health Warnings</p>
                        <ul class="space-y-1">
                            <?php foreach ($healthIssues as $issue): ?>
                                <li class="text-[11px] font-mono noc-text-main flex items-start gap-2">
                                    <i data-lucide="alert-triangle" class="w-3 h-3 noc-text-amber shrink-0 mt-0.5"></i>
                                    <?= htmlspecialchars($issue) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="p-6 relative z-10 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Database Profile -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider noc-text-main">Database</span>
                            <span
                                class="text-[10px] font-black uppercase tracking-widest flex items-center gap-1 <?= ($dbProfile['type'] ?? 'Local') === 'Local' ? 'noc-text-cyan' : 'noc-text-indigo' ?>">
                                <i data-lucide="<?= ($dbProfile['type'] ?? 'Local') === 'Local' ? 'monitor' : 'cloud' ?>"
                                    class="w-3 h-3"></i>
                                <?= htmlspecialchars($dbProfile['type'] ?? 'Local') ?>
                            </span>
                        </div>
                        <div class="p-4 rounded-lg noc-card-alt space-y-2.5">
                            <div class="flex justify-between text-[11px] font-mono">
                                <span class="noc-text-muted">Type</span>
                                <span
                                    class="noc-text-main font-bold"><?= htmlspecialchars($dbProfile['type'] ?? 'Local') ?>
                                    Database</span>
                            </div>
                            <div class="flex justify-between text-[11px] font-mono">
                                <span class="noc-text-muted">Status</span>
                                <span
                                    class="<?= $dbStatusTone === 'emerald' ? 'noc-text-emerald' : 'noc-text-rose' ?> font-bold"><?= htmlspecialchars($dbProfile['status'] ?? '—') ?></span>
                            </div>
                            <div class="flex justify-between text-[11px] font-mono">
                                <span class="noc-text-muted">Engine</span>
                                <span
                                    class="noc-text-main font-bold"><?= htmlspecialchars($dbProfile['engine'] ?? 'MySQL') ?></span>
                            </div>
                            <div class="flex justify-between text-[11px] font-mono">
                                <span class="noc-text-muted">Database Size</span>
                                <span
                                    class="noc-text-amber font-bold"><?= htmlspecialchars($dbProfile['size'] ?? '—') ?></span>
                            </div>
                            <div class="flex justify-between text-[11px] font-mono">
                                <span class="noc-text-muted">Last Backup</span>
                                <span
                                    class="noc-text-main font-bold"><?= htmlspecialchars($dbProfile['last_backup'] ?? 'Never') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Usage -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider noc-text-main">Storage Usage</span>
                        <div class="p-4 rounded-lg noc-card-alt space-y-3">
                            <div class="flex justify-between items-end">
                                <span class="text-[11px] font-mono noc-text-muted"><?= $formattedDiskUsed ?> /
                                    <?= $formattedDiskTotal ?></span>
                                <span
                                    class="text-xs font-mono font-black <?= $storageTextClass ?>"><?= $diskUsagePercentage ?>%
                                    Used</span>
                            </div>
                            <div class="w-full rounded-sm h-2 overflow-hidden noc-card">
                                <div class="h-full transition-all duration-1000 <?= $storageBarClass ?>"
                                    style="width: <?= $diskUsagePercentage ?>%; box-shadow: 0 0 10px var(--noc-accent-<?= $storageStatusTone === 'rose' ? 'rose' : ($storageStatusTone === 'amber' ? 'amber' : 'indigo') ?>);">
                                </div>
                            </div>
                            <div class="flex justify-between text-[11px] font-mono pt-1">
                                <span class="noc-text-muted">Server Status</span>
                                <span
                                    class="<?= $serverTextClass ?> font-bold"><?= htmlspecialchars($serverStatus) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent System Activities -->
            <div class="rounded-xl overflow-hidden noc-card it-dash-grow">
                <div class="noc-feed-card-header">
                    <h3 class="font-bold text-sm noc-text-main flex items-center gap-2">
                        <i data-lucide="list-tree" class="w-4 h-4 noc-text-cyan"></i>
                        Recent System Activities
                    </h3>
                    <a href="/<?= PROJECT_DIR ?>/audit-logs"
                        class="text-xs font-bold hover:underline noc-text-cyan">View All</a>
                </div>
                <div class="noc-feed-card-body noc-card-alt custom-scrollbar"
                    style="max-height: 252px; overflow-y: auto;">
                    <table class="w-full text-xs font-mono">
                        <tbody>
                            <?php foreach ($recentActivities as $log): ?>
                                <tr class="transition-colors noc-hover noc-border-b">
                                    <td class="py-2.5 pr-4 whitespace-nowrap noc-text-muted">
                                        [<?= date('M d H:i', strtotime($log['created_at'])) ?>]
                                    </td>
                                    <td class="py-2.5 px-4 whitespace-nowrap noc-text-emerald">
                                        <?= htmlspecialchars($log['user_name'] ?? $log['user_email'] ?? 'System') ?>
                                    </td>
                                    <td class="py-2.5 px-4 w-full noc-text-main">
                                        &gt; <?= htmlspecialchars($log['action']) ?>
                                    </td>
                                    <td class="py-2.5 pl-4 text-right whitespace-nowrap">
                                        <span
                                            class="text-[9px] uppercase tracking-widest px-1.5 py-0.5 rounded noc-text-muted"
                                            style="border: 1px solid var(--noc-border); background-color: var(--noc-card-bg);">
                                            <?= htmlspecialchars($log['module'] ?? 'SYS') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentActivities)): ?>
                                <tr>
                                    <td colspan="4" class="py-6 text-center noc-text-muted">No recent system activities.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- end LEFT COLUMN -->

        <!-- RIGHT COLUMN -->
        <div class="it-dash-col-side">

            <!-- Quick Actions (natural/compact height) -->
            <div class="rounded-xl p-6 relative overflow-hidden noc-card">
                <h3 class="font-bold text-sm mb-4 flex items-center gap-2 noc-text-main">
                    <i data-lucide="zap" class="w-4 h-4 noc-text-amber"></i> Quick Actions
                </h3>
                <div class="space-y-3">
                    <a href="/<?= PROJECT_DIR ?>/backup-maintenance"
                        class="flex items-center justify-between w-full p-3 rounded-lg transition noc-card-alt noc-hover">
                        <div class="flex items-center gap-3">
                            <i data-lucide="database-backup" class="w-4 h-4 noc-text-cyan"></i>
                            <span class="text-xs font-semibold noc-text-main">Generate Database Backup</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 noc-text-muted"></i>
                    </a>
                    <a href="/<?= PROJECT_DIR ?>/audit-logs"
                        class="flex items-center justify-between w-full p-3 rounded-lg transition noc-card-alt noc-hover">
                        <div class="flex items-center gap-3">
                            <i data-lucide="file-terminal" class="w-4 h-4 noc-text-indigo"></i>
                            <span class="text-xs font-semibold noc-text-main">View Audit Logs</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 noc-text-muted"></i>
                    </a>
                    <?php if ($lockedAccounts > 0): ?>
                        <a href="/<?= PROJECT_DIR ?>/audit-logs?module=Authentication"
                            class="flex items-center justify-between w-full p-3 rounded-lg transition noc-card-alt noc-hover"
                            style="border-left: 3px solid var(--noc-accent-rose);">
                            <div class="flex items-center gap-3">
                                <i data-lucide="shield-alert" class="w-4 h-4 noc-text-rose"></i>
                                <span class="text-xs font-semibold noc-text-main">Review Locked Accounts
                                    (<?= $lockedAccounts ?>)</span>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 noc-text-muted"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Alerts (flex-1: fills remaining height to match Recent Activities) -->
            <div class="rounded-xl overflow-hidden noc-card it-dash-grow">
                <div class="noc-feed-card-header">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 noc-text-rose"></i>
                        <h3 class="font-bold text-sm noc-text-main uppercase tracking-wider">Security Alerts</h3>
                    </div>
                    <div class="px-2 py-0.5 rounded text-[10px] font-bold noc-text-rose"
                        style="background-color: rgba(244, 63, 94, 0.1); border: 1px solid var(--noc-accent-rose);">
                        <?= $lockedAccounts ?> Locked
                    </div>
                </div>
                <div class="noc-feed-card-body custom-scrollbar" style="max-height: 430px; overflow-y: auto;">
                    <?php if (empty($securityAlerts)): ?>
                        <div class="p-3 rounded text-xs font-mono noc-text-emerald"
                            style="background-color: rgba(16, 185, 129, 0.1);">
                            &gt; No security alerts at this time.
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($securityAlerts as $alert): ?>
                                <?php
                                $severityClass = match ($alert['severity']) {
                                    'high' => 'noc-text-rose',
                                    'medium' => 'noc-text-amber',
                                    default => 'noc-text-muted',
                                };
                                ?>
                                <div class="p-3 rounded transition noc-card-alt border border-transparent">
                                    <div class="flex items-start gap-2 mb-1">
                                        <i data-lucide="shield-alert"
                                            class="w-3.5 h-3.5 mt-0.5 <?= $severityClass ?> shrink-0"></i>
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-wider <?= $severityClass ?>">
                                                <?= htmlspecialchars($alert['type']) ?>
                                            </p>
                                            <p class="text-[11px] font-bold noc-text-main leading-tight mt-0.5">
                                                <?= htmlspecialchars($alert['title']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if (!empty($alert['detail'])): ?>
                                        <p class="text-[10px] noc-text-muted ml-5"><?= htmlspecialchars($alert['detail']) ?></p>
                                    <?php endif; ?>
                                    <p class="text-[10px] font-mono noc-text-muted ml-5 mt-1">
                                        <?= htmlspecialchars($alert['meta']) ?><br>
                                        <?= date('M d, Y H:i', strtotime($alert['time'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- end RIGHT COLUMN -->

    </div><!-- end Main Grid -->

</div>

<script>
    setInterval(() => {
        const clock = document.getElementById('server-clock-standard');
        if (clock) {
            const now = new Date();
            clock.textContent = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
    }, 1000);
    setInterval(async () => {
        try {
            const res = await fetch('/<?= PROJECT_DIR ?>/app/api/active_users_count.php');
            if (res.ok) {
                const data = await res.json();
                const activeEl = document.getElementById('realtime-active-users');
                if (activeEl && data.count !== undefined) {
                    activeEl.textContent = data.count.toLocaleString();
                }
            }
        } catch (e) { }
    }, 5000);

    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>