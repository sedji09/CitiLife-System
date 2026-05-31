<?php
/**
 * audit-logs.php
 * View for system audit logs - redesigned to match user reference.
 */
?>

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

<div class="mx-auto max-w-6xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">System Audit Logs</h1>
            <p class="text-sm text-gray-500">Track and monitor all system activities and user actions</p>
        </div>
        <?php if (!empty($filters['search']) || !empty($filters['module']) || !empty($filters['role'])): ?>
            <a href="?page=audit-logs"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Clear Filters
            </a>
        <?php endif; ?>
    </div>

    <!-- Search & Filters -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
        <!-- Search Field -->
        <div class="relative md:col-span-6 w-full group">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="audit-logs">
                <input type="hidden" name="module" value="<?= htmlspecialchars($filters['module']) ?>">
                <input type="hidden" name="role" value="<?= htmlspecialchars($filters['role']) ?>">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                    placeholder="Search by action, user, or details..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                <i data-lucide="search"
                    class="absolute left-3.5 top-3 w-4 h-4 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                <button type="submit" class="hidden">Search</button>
            </form>
        </div>

        <!-- Module Filter -->
        <div class="md:col-span-3">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="audit-logs">
                <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search']) ?>">
                <input type="hidden" name="role" value="<?= htmlspecialchars($filters['role']) ?>">
                <select name="module" onchange="this.form.submit()"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm cursor-pointer hover:bg-gray-50">
                    <option value="">All Modules</option>
                    <?php foreach ($distinctModules as $mod): ?>
                        <option value="<?= htmlspecialchars($mod) ?>" <?= ($filters['module'] == $mod) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Role Filter -->
        <div class="md:col-span-3">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="audit-logs">
                <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search']) ?>">
                <input type="hidden" name="module" value="<?= htmlspecialchars($filters['module']) ?>">
                <select name="role" onchange="this.form.submit()"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm cursor-pointer hover:bg-gray-50">
                    <option value="">All Roles</option>
                    <?php foreach ($distinctRoles as $rl): ?>
                        <option value="<?= htmlspecialchars($rl) ?>" <?= ($filters['role'] == $rl) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(array_map('ucfirst', explode('_', $rl))[0] . (isset(explode('_', $rl)[1]) ? ' ' . ucfirst(explode('_', $rl)[1]) : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table Card -->
    <div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden mb-12">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                        <th class="text-left font-semibold px-4 py-4 whitespace-nowrap">Date</th>
                        <th class="text-left font-semibold px-4 py-4 truncate">User</th>
                        <th class="text-left font-semibold px-4 py-4">Role</th>
                        <th class="text-left font-semibold px-4 py-4">Action</th>
                        <th class="text-left font-semibold px-4 py-4">Status</th>
                        <th class="text-left font-semibold px-4 py-4">Module</th>
                        <th class="text-left font-semibold px-4 py-4">Branch</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 bg-white divide-y divide-gray-100">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-2">
                                        <i data-lucide="search-x" class="w-8 h-8 text-gray-300"></i>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800">No logs found</h3>
                                    <p class="text-xs text-gray-500">Try adjusting your filters or search terms.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-[13px] text-gray-500">
                                        <?= date('M j, Y', strtotime($log['created_at'])) ?>
                                        <span
                                            class="block text-[11px] text-gray-400"><?= date('g:i A', strtotime($log['created_at'])) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-bold text-gray-800">
                                        <?= htmlspecialchars($log['user_name'] ?? ($log['user_email'] ? explode('@', $log['user_email'])[0] : 'System')) ?>
                                    </div>
                                    <div class="text-[11px] text-gray-400 truncate max-w-[150px]">
                                        <?= htmlspecialchars($log['user_email'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-xs font-medium px-2 py-1 rounded-md bg-gray-100 text-gray-600">
                                        <?php
                                        $roleDisplay = str_replace('_', ' ', $log['user_role'] ?? 'System');
                                        echo ucwords($roleDisplay);
                                        ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-gray-700 font-medium leading-tight">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </div>
                                    <?php if (!empty($log['details'])): ?>
                                        <div class="text-[11px] text-gray-400 mt-1 line-clamp-1"
                                            title="<?= htmlspecialchars($log['details']) ?>">
                                            <?php
                                            $displayDetails = preg_replace('/,?\s*Exam:\s*.*$/', '', $log['details']);
                                            $displayDetails = trim($displayDetails, " ,");
                                            echo htmlspecialchars($displayDetails);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4">
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
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-<?= $sColor ?>-50 text-<?= $sColor ?>-700 border border-<?= $sColor === 'red' ? 'red-500' : $sColor . '-400' ?> status-badge status-badge-<?= $sColor ?>">
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-gray-600"><?= htmlspecialchars($log['module'] ?? 'N/A') ?></span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-medium text-gray-600 flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-3 h-3 text-gray-400"></i>
                                        <?= htmlspecialchars($log['branch_name'] ?? 'System-wide') ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <?php if (!empty($logs)): ?>
            <?php
            $start = $offset + 1;
            $end = min($offset + $limit, $total_count);
            $totalPages = ceil($total_count / $limit) ?: 1;
            ?>
            <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">
                <div class="text-xs text-gray-500">
                    Showing <span class="font-medium"><?= $start ?></span>-<span class="font-medium"><?= $end ?></span> of
                    <span class="font-medium"><?= $total_count ?></span> records
                </div>
                <div class="flex items-center gap-3">
                    <a href="?page=audit-logs&p=<?= max(1, $page_num - 1) ?>&search=<?= urlencode($filters['search']) ?>&module=<?= urlencode($filters['module']) ?>&role=<?= urlencode($filters['role']) ?>"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition <?= $page_num <= 1 ? 'pointer-events-none opacity-40 cursor-not-allowed' : '' ?>">
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Previous
                    </a>
                    <span class="text-xs font-medium text-gray-600 min-w-[90px] text-center">
                        Page <?= $page_num ?> of <?= $totalPages ?>
                    </span>
                    <a href="?page=audit-logs&p=<?= $page_num + 1 ?>&search=<?= urlencode($filters['search']) ?>&module=<?= urlencode($filters['module']) ?>&role=<?= urlencode($filters['role']) ?>"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition <?= $page_num >= $totalPages ? 'pointer-events-none opacity-40 cursor-not-allowed' : '' ?>">
                        Next <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>