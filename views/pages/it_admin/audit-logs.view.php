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
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer bg-white">
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
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer bg-white">
                        <option value="">All Roles</option>
                        <?php foreach ($distinctRoles as $rl): ?>
                            <option value="<?= htmlspecialchars($rl) ?>" <?= $filters['role'] == $rl ? 'selected' : '' ?>>
                                <?= ucwords(strtolower(str_replace('_', ' ', $rl))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="col-span-1 md:col-span-2 lg:col-span-1">
                    <select name="sort" onchange="document.getElementById('filterForm').submit()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all cursor-pointer bg-white">
                        <option value="desc" <?= (($filters['sort'] ?? '') !== 'asc') ? 'selected' : '' ?>>Newest First</option>
                        <option value="asc" <?= (($filters['sort'] ?? '') === 'asc') ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div id="audit-log-card" class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 bg-white">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600">
                            Timestamp
                        </th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600">Actor</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600">Branch</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600">Category</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600">Action</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-600 text-center">Info</th>
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
                            <td class="px-6 py-2.5">
                                <div class="flex flex-col">
                                    <span class="text-[11px] font-medium text-gray-700 tabular-nums mb-0.5">
                                        <?= date('M d, Y', strtotime($log['created_at'])) ?>
                                    </span>
                                    <span class="text-[10px] text-gray-500">
                                        <?= date('h:i:s A', strtotime($log['created_at'])) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-2.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-xs font-bold text-gray-800 tracking-tight mb-0.5"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></span>
                                        <span
                                            class="text-[10px] text-gray-500 lowercase mb-1"><?= htmlspecialchars($log['user_email'] ?? 'no email') ?></span>
                                        <span
                                            class="text-[8px] font-black <?= strtolower($log['user_role'] ?? '') == 'it_admin' ? 'text-red-500' : 'text-gray-400' ?> uppercase tracking-widest"><?= htmlspecialchars(str_replace('_', ' ', $log['user_role'] ?? 'AUTOMATED')) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-2.5 text-xs text-gray-600">
                                <?php 
                                    if (in_array(strtolower($log['user_role'] ?? ''), ['it_admin', 'admin_central'])) {
                                        echo 'Global';
                                    } else {
                                        echo htmlspecialchars(ucwords(strtolower($log['branch_name'] ?? 'Global')));
                                    }
                                ?>
                            </td>
                            <td class="px-6 py-2.5 text-xs text-gray-600">
                                <?= htmlspecialchars(ucwords(strtolower($log['module'] ?? 'Unknown'))) ?>
                            </td>
                            <td class="px-6 py-2.5">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-800 tracking-tight mb-0.5">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                    <?php if (!empty($log['details'])): ?>
                                    <span class="text-[10px] text-gray-400 max-w-xs truncate" title="<?= htmlspecialchars($log['details']) ?>">
                                        <?= htmlspecialchars($log['details']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-2.5">
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
                            <td class="px-6 py-2.5 text-center">
                                <button onclick="showDetails(<?= htmlspecialchars(json_encode($log)) ?>)"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                    <i data-lucide="info" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_count > 0): ?>
            <div
                class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
                <?php
                $start_record = $offset + 1;
                $end_record = min($offset + count($logs), $total_count);
                ?>
                <span class="text-xs text-gray-500">
                    Showing <span class="font-semibold text-gray-800"><?= $start_record ?></span> to <span class="font-semibold text-gray-800"><?= $end_record ?></span> of <span class="font-semibold text-gray-800"><?= $total_count ?></span> records
                </span>

                <div class="flex items-center flex-wrap gap-1.5">
                    <?php
                    $renderPageBtn = function($label, $targetPage, $disabled, $isActive = false) use ($filters) {
                        $query = http_build_query(array_merge(array_filter($filters), ['page' => 'audit-logs', 'p' => $targetPage]));
                        $url = '?' . $query;
                        
                        if ($isActive) {
                            return '<span class="px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600">' . $label . '</span>';
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
                    echo $renderPageBtn('&laquo; First', 1, $page_num <= 1);

                    // Back Button
                    echo $renderPageBtn('&lsaquo; Back', $page_num - 1, $page_num <= 1);

                    // Page numbers
                    if ($total_pages <= 7) {
                        for ($i = 1; $i <= $total_pages; $i++) {
                            echo $renderPageBtn($i, $i, false, $i == $page_num);
                        }
                    } else {
                        if ($page_num <= 4) {
                            for ($i = 1; $i <= 5; $i++) {
                                echo $renderPageBtn($i, $i, false, $i == $page_num);
                            }
                            echo $renderEllipsis();
                            echo $renderPageBtn($total_pages, $total_pages, false, $total_pages == $page_num);
                        } elseif ($page_num >= $total_pages - 3) {
                            echo $renderPageBtn(1, 1, false, 1 == $page_num);
                            echo $renderEllipsis();
                            for ($i = $total_pages - 4; $i <= $total_pages; $i++) {
                                echo $renderPageBtn($i, $i, false, $i == $page_num);
                            }
                        } else {
                            echo $renderPageBtn(1, 1, false, 1 == $page_num);
                            echo $renderEllipsis();
                            echo $renderPageBtn($page_num - 1, $page_num - 1, false, false);
                            echo $renderPageBtn($page_num, $page_num, false, true);
                            echo $renderPageBtn($page_num + 1, $page_num + 1, false, false);
                            echo $renderEllipsis();
                            echo $renderPageBtn($total_pages, $total_pages, false, false);
                        }
                    }

                    // Next Button
                    echo $renderPageBtn('Next &rsaquo;', $page_num + 1, $page_num >= $total_pages);

                    // Last Button
                    echo $renderPageBtn('Last &raquo;', $total_pages, $page_num >= $total_pages);
                    ?>
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