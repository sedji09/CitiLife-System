<?php
/**
 * audit-logs.php - Branch Admin Version
 * View for branch-specific audit logs.
 */
?>

<style>
    /* ===== Styled to match Branch X-ray Cases aesthetic ===== */
    .audit-card {
        background: white;
        border-color: #e5e7eb;
        overflow: hidden;
    }

    .audit-pagination {
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
    }

    .audit-table thead tr {
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .audit-table th {
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        text-transform: none;
        background: #f9fafb !important;
    }

    .audit-table tr {
        border-bottom: 1px solid #f3f4f6;
    }

    .audit-table tr:hover {
        background: rgba(249, 250, 251, 0.8);
    }

    .nav-button {
        background: white;
        border-color: #e5e7eb;
        color: #374151;
    }

    /* Theme-Dark Perfect Sync */
    .theme-dark .audit-card {
        background: #111827 !important;
        /* Matches X-ray cases card */
        border-color: rgba(255, 255, 255, 0.05) !important;
    }

    .theme-dark .audit-table thead tr {
        background: #1e293b !important;
        /* Matches X-ray cases header bar */
        border-bottom: none !important;
    }

    .theme-dark .audit-table th {
        background: transparent !important;
        color: #ffffff !important;
        font-weight: 700 !important;
    }

    .theme-dark .audit-table td {
        border-bottom-color: rgba(255, 255, 255, 0.05) !important;
    }

    .theme-dark .audit-table tr:hover {
        background: rgba(255, 255, 255, 0.03) !important;
    }

    .theme-dark .audit-pagination {
        background: #1e293b !important;
        /* Distinct dark footer bar */
        border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    .theme-dark .nav-button {
        background: #334155 !important;
        border-color: #475569 !important;
        color: #f1f5f9 !important;
    }

    .theme-dark .nav-button:hover {
        background: #475569 !important;
    }

    .theme-dark .audit-text-main {
        color: #f8fafc !important;
    }

    .theme-dark .audit-text-muted {
        color: #94a3b8 !important;
    }

    .theme-dark .audit-text-dim {
        color: #475569 !important;
    }

    /* Status Badge Light Mode Fix */
    .status-badge {
        border-style: solid !important;
        border-width: 1px !important;
    }

    /* Custom Status Badges */
    .theme-dark .status-badge {
        background-color: rgba(255, 255, 255, 0.03) !important;
        border-width: 1px !important;
        border-style: solid !important;
    }

    /* Status-Specific Dark Mode Overrides (Enhanced Visibility) */
    .theme-dark .status-badge-green {
        border-color: #10b981 !important;
        /* Emerald-500 */
        color: #34d399 !important;
        /* Emerald-400 */
        background-color: rgba(16, 185, 129, 0.1) !important;
        box-shadow: 0 0 0 0.5px #10b981 !important;
        /* Double-reinforce border */
    }

    .theme-dark .status-badge-red {
        border-color: #f87171 !important;
        /* Red-400 (Brighter for dark mode) */
        color: #fca5a5 !important;
        /* Red-300 */
        background-color: rgba(239, 68, 68, 0.1) !important;
        box-shadow: 0 0 0 0.5px #ef4444 !important;
        /* Double-reinforce border */
    }

    .theme-dark .status-badge-gray {
        border-color: #64748b !important;
        /* Slate-500 */
        color: #cbd5e1 !important;
        /* Slate-300 */
        background-color: rgba(100, 116, 139, 0.1) !important;
    }
</style>

<div class="mx-auto max-w-6xl space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 audit-text-main">Branch Audit Logs</h1>
            <p class="text-sm text-gray-500 audit-text-muted mt-1">Activities and system events recorded within your
                branch</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if (!empty($filters['search']) || !empty($filters['module']) || !empty($filters['role'])): ?>
                <a href="?role=branch_admin&page=audit-logs"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    Clear Filters
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
        <!-- Search Field -->
        <div class="relative md:col-span-6 w-full group">
            <form method="GET" action="index.php">
                <input type="hidden" name="role" value="branch_admin">
                <input type="hidden" name="page" value="audit-logs">
                <input type="hidden" name="module" value="<?= htmlspecialchars($filters['module']) ?>">
                <input type="hidden" name="rl" value="<?= htmlspecialchars($filters['role']) ?>">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                    placeholder="Search by action, user, or details..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm filter-control">
                <i data-lucide="search"
                    class="absolute left-3.5 top-3 w-4 h-4 text-gray-400 group-focus-within:text-red-500 transition-colors audit-text-muted"></i>
                <button type="submit" class="hidden">Search</button>
            </form>
        </div>

        <!-- Module Filter -->
        <div class="md:col-span-3">
            <form method="GET" action="index.php">
                <input type="hidden" name="role" value="branch_admin">
                <input type="hidden" name="page" value="audit-logs">
                <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search']) ?>">
                <input type="hidden" name="rl" value="<?= htmlspecialchars($filters['role']) ?>">
                <select name="module" onchange="this.form.submit()"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm cursor-pointer hover:bg-gray-50 filter-control">
                    <option value="">All Modules</option>
                    <?php foreach ($distinctModules as $mod): ?>
                        <option value="<?= htmlspecialchars($mod) ?>" <?= ($filters['module'] == $mod) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Role Filter (rl) -->
        <div class="md:col-span-3">
            <form method="GET" action="index.php">
                <input type="hidden" name="role" value="branch_admin">
                <input type="hidden" name="page" value="audit-logs">
                <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search']) ?>">
                <input type="hidden" name="module" value="<?= htmlspecialchars($filters['module']) ?>">
                <select name="rl" onchange="this.form.submit()"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm cursor-pointer hover:bg-gray-50 filter-control">
                    <option value="">All Staff Roles</option>
                    <?php foreach ($distinctRoles as $rl): ?>
                        <?php if ($rl === 'patient')
                            continue; ?>
                        <option value="<?= htmlspecialchars($rl) ?>" <?= ($filters['role'] == $rl) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $rl))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table Card -->
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden mb-12 audit-card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm audit-table">
                <thead>
                    <tr class="border-b text-gray-500 font-medium">
                        <th class="text-left px-4 py-3.5">Date</th>
                        <th class="text-left px-4 py-3.5">User</th>
                        <th class="text-left px-4 py-3.5">Action</th>
                        <th class="text-left px-4 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-gray-800">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">
                                No logs found for the selected filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            // Mapping logic (from dashboard logs reference)
                            $actionLabel = $log['action'] ?? 'Activity Recorded';
                            $statusLabel = 'Successful';
                            $sColor = 'green';

                            // Priority: Check for explicit rejections
                            if (stripos($log['action'], 'Rejected') !== false || stripos($log['details'], 'Rejected') !== false) {
                                $statusLabel = 'Unsuccessful';
                                $sColor = 'red';
                            } elseif ($log['module'] === 'Patient Management') {
                                $actionLabel = 'Account Registration';
                                if (strpos($log['action'], 'Registered') !== false) {
                                    $statusLabel = 'Pending Approval';
                                    $sColor = 'red';
                                }
                            } elseif ($log['module'] === 'X-ray Case') {
                                $actionLabel = 'X-ray Examination';
                            } elseif ($log['module'] === 'Record Request') {
                                $actionLabel = 'Information Request';
                                $statusLabel = 'Pending';
                                $sColor = 'red';
                            } elseif (strpos($log['action'], 'Password Reset') !== false) {
                                $actionLabel = 'Password Reset';
                                $statusLabel = 'Successful';
                                $sColor = 'gray';
                            }

                            $rawName = $log['user_name'] ?: ($log['user_email'] ? explode('@', $log['user_email'])[0] : 'System');
                            if (strpos($rawName, '@') !== false)
                                $rawName = explode('@', $rawName)[0];
                            $subjectName = $rawName;

                            if (!empty($log['details']) && preg_match('/(?:Name|Patient|User):\s*([^,\-]+)/i', $log['details'], $matches)) {
                                if (!empty(trim($matches[1])))
                                    $subjectName = trim($matches[1]);
                            }
                            ?>
                            <tr class="transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-[13px] text-gray-900 font-medium audit-text-main">
                                        <?= date('F j, Y', strtotime($log['created_at'])) ?>
                                    </div>
                                    <div class="text-[11px] text-gray-400 audit-text-dim">
                                        <?= date('g:i A', strtotime($log['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-900 audit-text-main"><?= htmlspecialchars($subjectName) ?>
                                    </div>
                                    <div class="text-[11px] text-gray-400 audit-text-dim">
                                        <?= htmlspecialchars($log['user_email'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold leading-tight audit-text-main">
                                        <?= htmlspecialchars($actionLabel) ?>
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5 line-clamp-1 audit-text-dim"
                                        title="<?= htmlspecialchars($log['details'] ?? '') ?>">
                                        <?= htmlspecialchars(preg_replace('/,?\s*Exam:\s*.*$/', '', $log['details'] ?? '')) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-<?= $sColor ?>-50 text-<?= $sColor ?>-700 border border-<?= $sColor === 'red' ? 'red-600' : $sColor . '-400' ?> status-badge status-badge-<?= $sColor ?>">
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <!-- Pagination -->
        <?php
        $total_count = $total_count ?? 0;
        $page_num = $page_num ?? 1;
        $limit = $limit ?? 7;
        $offset = ($page_num - 1) * $limit;
        $start = $total_count > 0 ? $offset + 1 : 0;
        $end = min($offset + $limit, $total_count);
        $totalPages = ceil($total_count / $limit) ?: 1;
        ?>
        <div class="px-6 py-3 flex items-center justify-between audit-pagination bg-gray-50 border-t border-gray-200">
            <span class="text-xs font-medium text-gray-500 audit-text-dim">
                Showing <?= $total_count > 0 ? $start : 0 ?>–<?= $total_count > 0 ? $end : 0 ?> of <?= $total_count ?>
                records
            </span>
            <div class="flex items-center gap-4">
                <a href="?role=branch_admin&page=audit-logs&p=<?= max(1, $page_num - 1) ?>&search=<?= urlencode($filters['search']) ?>&module=<?= urlencode($filters['module']) ?>"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:shadow-sm transition <?= $page_num <= 1 || $total_count == 0 ? 'pointer-events-none opacity-50' : '' ?> nav-button">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i> Previous
                </a>
                <div class="text-xs font-bold text-gray-700 px-1 audit-text-muted min-w-[80px] text-center">
                    Page <?= $page_num ?> of <?= $totalPages ?>
                </div>
                <a href="?role=branch_admin&page=audit-logs&p=<?= min($totalPages, $page_num + 1) ?>&search=<?= urlencode($filters['search']) ?>&module=<?= urlencode($filters['module']) ?>"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:shadow-sm transition <?= $page_num >= $totalPages || $total_count == 0 ? 'pointer-events-none opacity-50' : '' ?> nav-button">
                    Next <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>