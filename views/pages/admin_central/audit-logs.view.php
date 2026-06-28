<?php
/**
 * audit-logs.php
 * View for system audit logs (Central Admin)
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">System Audit Logs</h1>
            <p class="text-sm text-gray-500 mt-1">Real-time global monitoring of all system events and administrative
                actions.</p>
        </div>
        <div class="flex items-center gap-3">
            <?php if (!empty($filters['search']) || !empty($filters['module']) || !empty($filters['role']) || (!empty($filters['sort']) && $filters['sort'] !== 'desc')): ?>
                <a href="/<?= PROJECT_DIR ?>/audit-logs"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    Clear Filters
                </a>
            <?php endif; ?>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-red-50 border border-red-100 rounded-full">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                <span class="text-[10px] font-black text-red-700 uppercase tracking-widest leading-none">Live
                    Monitoring</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <form method="GET" action="" class="flex flex-col gap-4" id="filterForm">
            <input type="hidden" name="page" value="audit-logs">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Search -->
                <div class="md:col-span-2 lg:col-span-2">
                    <div class="relative group">
                        <i data-lucide="search"
                            class="absolute left-3 top-2.5 w-4 h-4 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                            placeholder="Search action, user, or details..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all">
                        <button type="submit" class="hidden">Search</button>
                    </div>
                </div>

                <!-- Module Filter -->
                <div class="col-span-1">
                    <select name="module" onchange="document.getElementById('filterForm').submit()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs tracking-wider focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer bg-white">
                        <option value="">All Modules</option>
                        <?php foreach ($distinctModules as $mod): ?>
                            <option value="<?= htmlspecialchars($mod) ?>" <?= $filters['module'] == $mod ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mod) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Role Filter -->
                <div class="col-span-1">
                    <select name="role" onchange="document.getElementById('filterForm').submit()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs tracking-wider focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer bg-white">
                        <option value="">All Roles</option>
                        <?php foreach ($distinctRoles as $rl): ?>
                            <option value="<?= htmlspecialchars($rl) ?>" <?= $filters['role'] == $rl ? 'selected' : '' ?>>
                                <?= strtoupper(str_replace('_', ' ', $rl)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="col-span-1 md:col-span-2 lg:col-span-1">
                    <select name="sort" onchange="document.getElementById('filterForm').submit()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs tracking-wider focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer bg-white">
                        <option value="desc" <?= (($filters['sort'] ?? '') !== 'asc') ? 'selected' : '' ?>>Newest First</option>
                        <option value="asc" <?= (($filters['sort'] ?? '') === 'asc') ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table Card -->
    <div id="audit-log-card" class="rounded-xl border border-gray-300 bg-white shadow-sm overflow-hidden mb-12">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                        <th class="text-left font-semibold px-4 py-4 whitespace-nowrap">Timestamp</th>
                        <th class="text-left font-semibold px-4 py-4 truncate">Actor</th>
                        <th class="text-left font-semibold px-4 py-4">Branch</th>
                        <th class="text-left font-semibold px-4 py-4">Category</th>
                        <th class="text-left font-semibold px-4 py-4">Action</th>
                        <th class="text-left font-semibold px-4 py-4">Status</th>
                        <th class="text-center font-semibold px-4 py-4">Info</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 bg-white divide-y divide-gray-100">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
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
                                    <div class="text-[13px] text-gray-500 font-medium tabular-nums">
                                        <?= date('M j, Y', strtotime($log['created_at'])) ?>
                                        <span
                                            class="block text-[11px] text-gray-400 mt-0.5"><?= date('g:i:s A', strtotime($log['created_at'])) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-gray-800 tracking-tight leading-none mb-1 truncate">
                                            <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                        </span>
                                        <?php if (!empty($log['user_email'])): ?>
                                        <span class="text-[10px] text-gray-500 mb-1 truncate">
                                            <?= htmlspecialchars($log['user_email']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <span
                                            class="text-[9px] font-black <?= ($log['user_role'] ?? '') === 'it_admin' ? 'text-red-500' : 'text-gray-400' ?> uppercase tracking-widest leading-none">
                                            <?= htmlspecialchars(str_replace('_', ' ', $log['user_role'] ?? 'AUTOMATED')) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-sm text-gray-600 tracking-tight">
                                        <?= htmlspecialchars($log['branch_name'] ?? 'Global') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="text-sm text-gray-600 tracking-tight capitalize"><?= htmlspecialchars(strtolower($log['module'] ?? 'System')) ?></span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-gray-700 font-semibold leading-tight max-w-xs truncate"
                                        title="<?= htmlspecialchars($log['action']) ?>">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </div>
                                    <?php if (!empty($log['details'])): ?>
                                        <div class="text-[11px] text-gray-400 mt-1 line-clamp-1 truncate max-w-xs"
                                            title="<?= htmlspecialchars($log['details']) ?>">
                                            <?php
                                            $displayDetails = trim($log['details'], " ,");
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
                                    } elseif (($log['module'] ?? '') === 'Patient Management' && strpos($log['action'], 'Registered') !== false) {
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
                                <td class="px-4 py-4 text-center">
                                    <button type="button" onclick="showDetails(<?= htmlspecialchars(json_encode($log)) ?>)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                        title="View Details">
                                        <i data-lucide="info" class="w-4 h-4"></i>
                                    </button>
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
            ?>
            <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
                <div class="text-xs text-gray-500">
                    Showing <span class="font-semibold text-gray-800"><?= $start ?></span> to <span class="font-semibold text-gray-800"><?= $end ?></span> of <span class="font-semibold text-gray-800"><?= $total_count ?></span> records
                </div>
                <div class="flex items-center flex-wrap gap-1.5">
                    <?php
                    $renderPageBtn = function($label, $targetPage, $disabled, $isActive = false) use ($filters) {
                        $query = http_build_query(array_merge(array_filter($filters), ['p' => $targetPage]));
                        $url = '/' . PROJECT_DIR . '/audit-logs?' . $query;
                        
                        if ($isActive) {
                            return '<span class="px-3 py-1.5 rounded-lg bg-black text-xs font-bold text-white shadow-sm border border-black">' . $label . '</span>';
                        }
                        
                        if ($disabled) {
                            return '<span class="px-3 py-1.5 rounded-lg border border-gray-200 bg-gray-50 text-xs font-semibold text-gray-400 cursor-not-allowed shadow-sm opacity-60">' . $label . '</span>';
                        }
                        
                        return '<a href="' . htmlspecialchars($url) . '" class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 transition shadow-sm">' . $label . '</a>';
                    };

                    $renderEllipsis = function() {
                        return '<span class="px-2 py-1 text-xs text-gray-400 font-semibold select-none">...</span>';
                    };

                    // First Button
                    echo $renderPageBtn('&laquo; First', 1, $page_num === 1);

                    // Back Button
                    echo $renderPageBtn('&lsaquo; Back', $page_num - 1, $page_num === 1);

                    // Page numbers
                    if ($total_pages <= 7) {
                        for ($i = 1; $i <= $total_pages; $i++) {
                            echo $renderPageBtn($i, $i, false, $i === $page_num);
                        }
                    } else {
                        if ($page_num <= 4) {
                            for ($i = 1; $i <= 5; $i++) {
                                echo $renderPageBtn($i, $i, false, $i === $page_num);
                            }
                            echo $renderEllipsis();
                            echo $renderPageBtn($total_pages, $total_pages, false, $total_pages === $page_num);
                        } elseif ($page_num >= $total_pages - 3) {
                            echo $renderPageBtn(1, 1, false, 1 === $page_num);
                            echo $renderEllipsis();
                            for ($i = $total_pages - 4; $i <= $total_pages; $i++) {
                                echo $renderPageBtn($i, $i, false, $i === $page_num);
                            }
                        } else {
                            echo $renderPageBtn(1, 1, false, 1 === $page_num);
                            echo $renderEllipsis();
                            echo $renderPageBtn($page_num - 1, $page_num - 1, false, false);
                            echo $renderPageBtn($page_num, $page_num, false, true);
                            echo $renderPageBtn($page_num + 1, $page_num + 1, false, false);
                            echo $renderEllipsis();
                            echo $renderPageBtn($total_pages, $total_pages, false, false);
                        }
                    }

                    // Next Button
                    echo $renderPageBtn('Next &rsaquo;', $page_num + 1, $page_num === $total_pages);

                    // Last Button
                    echo $renderPageBtn('Last &raquo;', $total_pages, $page_num === $total_pages);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div id="logModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
    <div
        class="bg-white w-full max-w-md rounded-2xl overflow-hidden shadow-2xl transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest">Action Details</h3>
            <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1 tracking-wider">IP Address</p>
                    <p id="modalIp" class="text-xs font-bold text-gray-700 tabular-nums">0.0.0.0</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1 tracking-wider">Target Module</p>
                    <p id="modalModule" class="text-xs font-bold text-gray-700"></p>
                </div>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase mb-1 tracking-wider">Detailed Logs</p>
                <div id="modalDetails"
                    class="bg-gray-50 rounded-xl p-3 border border-gray-100 text-[11px] text-gray-600 font-semibold leading-relaxed whitespace-pre-wrap">
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50/50 flex justify-end border-t border-gray-100">
            <button type="button" onclick="closeModal()"
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

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Restore scroll position if flag is set
        if (sessionStorage.getItem('should_restore_scroll') === 'true') {
            const savedScrollY = sessionStorage.getItem('audit_logs_scroll_y');
            if (savedScrollY !== null) {
                window.scrollTo(0, parseInt(savedScrollY, 10));
            }
            sessionStorage.removeItem('should_restore_scroll');
        }

        const saveScroll = () => {
            sessionStorage.setItem('should_restore_scroll', 'true');
            sessionStorage.setItem('audit_logs_scroll_y', window.scrollY);
        };

        // Save scroll on pagination click
        document.querySelectorAll('a').forEach(link => {
            const href = link.getAttribute('href') || '';
            if (href.includes('p=') || link.closest('.audit-pagination')) {
                link.addEventListener('click', saveScroll);
            }
        });

        // Save scroll on form submissions
        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('[name="page"][value="audit-logs"]') || form.action.includes('audit-logs')) {
                form.addEventListener('submit', saveScroll);
            }
        });

        // Save scroll on select changes
        document.querySelectorAll('select.filter-control, select[name="module"], select[name="rl"], select[name="role"], select[name="sort"]').forEach(select => {
            select.addEventListener('change', saveScroll);
        });
    });
</script>