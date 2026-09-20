<!-- IT Backup & Maintenance -->
<style>
    .text-\[9px\] { font-size: 9px !important; }
    .text-\[10px\] { font-size: 10px !important; }
    .text-\[10\.5px\] { font-size: 10px !important; }
    .text-\[11px\] { font-size: 11px !important; }
</style>
<div class="max-w-5xl mx-auto space-y-6 animate-in fade-in duration-500">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Backup & Maintenance</h1>
            <p class="text-sm text-gray-500 mt-1">Manage database backups, export selective tables, restore data, and manage backup trash.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success && !empty($lastDeletedFile)): ?>
        <div id="backup-success-alert" class="rounded-xl bg-green-50 border border-green-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm transition-all duration-500 ease-out">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600 shrink-0"></i>
                <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($success) ?></p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <form action="" method="POST" class="inline shrink-0">
                    <input type="hidden" name="action" value="restore_deleted_file">
                    <input type="hidden" name="filename" value="<?= htmlspecialchars($lastDeletedFile) ?>">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow-sm transition cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Restore to Table
                    </button>
                </form>
                <button type="button" onclick="dismissAlert('backup-success-alert')" class="text-green-600 hover:text-green-800 p-1 rounded-md hover:bg-green-100 transition cursor-pointer" title="Dismiss">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div id="backup-error-alert" class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-center justify-between gap-3 shadow-sm transition-all duration-500 ease-out">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
                <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($error) ?></p>
            </div>
            <button type="button" onclick="dismissAlert('backup-error-alert')" class="text-red-600 hover:text-red-800 p-1 rounded-md hover:bg-red-100 transition cursor-pointer shrink-0" title="Dismiss">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Side: Actions & Controls -->
        <div class="lg:col-span-1 space-y-6">

            <!-- Stats Card -->
            <div id="stats-card" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm overflow-hidden relative group">
                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Total Backups</p>
                        <i data-lucide="server" class="w-4 h-4 text-gray-300 group-hover:text-indigo-400 transition-colors"></i>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-gray-900 leading-none"><?= count($backups) ?></span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">Backups</span>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-400 uppercase">Latest Sync:</span>
                        <span class="text-[11px] font-black text-indigo-600">
                            <?= !empty($backups) ? date('M d, H:i', $backups[0]['date']) : 'Never' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Selective & Filtered Export Card -->
            <div id="selective-export-card" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 shrink-0">
                        <i data-lucide="sliders-horizontal" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">Selective Database Export</h3>
                        <p class="text-[11px] text-gray-400">Filter by specific table and calendar year.</p>
                    </div>
                </div>

                <form id="filtered-backup-form" action="" method="POST" class="space-y-4 pt-1">
                    <input type="hidden" name="action" value="generate_backup">

                    <!-- Table Selection -->
                    <div>
                        <label for="table_filter" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                            <i data-lucide="table" class="w-3.5 h-3.5 text-gray-400"></i>
                            Target Table
                        </label>
                        <select name="table_filter" id="table_filter"
                            class="cs-theme-indigo w-full text-xs font-semibold rounded-xl border border-gray-200 px-3 py-2.5 bg-gray-50/50 hover:bg-white focus:bg-white transition text-gray-800">
                            <option value="all">All Tables (Complete Schema)</option>
                            <?php foreach ($availableTables as $tbl): ?>
                                <option value="<?= htmlspecialchars($tbl) ?>"><?= htmlspecialchars($tbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Year Selection -->
                    <div>
                        <label for="year_filter" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400"></i>
                            Calendar Year Filter
                        </label>
                        <select name="year_filter" id="year_filter"
                            class="cs-theme-indigo w-full text-xs font-semibold rounded-xl border border-gray-200 px-3 py-2.5 bg-gray-50/50 hover:bg-white focus:bg-white transition text-gray-800">
                            <option value="all">All Years (Full Record History)</option>
                            <?php foreach ($availableYears as $yr): ?>
                                <option value="<?= $yr ?>">Year <?= $yr ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="button" onclick="triggerFilteredBackup(this, event)"
                        class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-sm active:scale-95 flex items-center justify-center gap-2">
                        <i data-lucide="file-output" class="w-4 h-4"></i>
                        Export Filtered SQL
                    </button>
                </form>
            </div>

            <!-- Quick Full System Backup Card -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-700 shrink-0">
                        <i data-lucide="database-backup" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">Full Disaster Recovery Backup</h3>
                        <p class="text-[11px] text-gray-400">Complete database backup of all tables, views, and entire history.</p>
                    </div>
                </div>

                <form action="" method="POST" class="w-full">
                    <input type="hidden" name="action" value="generate_backup">
                    <input type="hidden" name="table_filter" value="all">
                    <input type="hidden" name="year_filter" value="all">
                    <button type="button"
                        onclick="confirmFormAction(this, 'generate_backup', 'Confirm Full Backup', 'Are you sure you want to initialize a full disaster recovery backup? This will dump the entire database structure and all historical records.', 'action', event)"
                        class="w-full py-3 bg-white border-2 border-gray-300 text-gray-700 hover:border-indigo-600 hover:text-indigo-600 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-sm active:scale-95 flex items-center justify-center gap-2">
                        <i data-lucide="play" class="w-3.5 h-3.5 fill-current text-current"></i>
                        Initialize Full Backup
                    </button>
                </form>
            </div>

        </div>

        <!-- Right Side: Backups List -->
        <div class="lg:col-span-2 self-start w-full">
            <div id="snapshot-history-card" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col w-full" style="max-height: 520px;">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/30 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Backup History</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">Stored backup files available for download, restore, or trash management.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openTrashModal()"
                            class="px-2.5 py-1 bg-white hover:bg-gray-50 border border-gray-200 hover:border-gray-300 rounded-md text-[10px] font-bold text-gray-600 transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5 text-gray-400"></i>
                            Trash Bin (<?= count($trashBackups) ?>)
                        </button>
                        <span class="px-2.5 py-1 bg-white border border-gray-200 rounded-md text-[9px] font-bold text-gray-500 uppercase tracking-widest leading-none hidden sm:inline-block">
                            Local Storage
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-auto flex-1 custom-scrollbar min-h-0">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-white border-b border-gray-100 shadow-[0_1px_2px_-1px_rgba(0,0,0,0.05)]">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white">
                                    Filename
                                </th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white">
                                    File Size
                                </th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (empty($backups)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center gap-3 opacity-25">
                                            <i data-lucide="folder-search" class="w-12 h-12"></i>
                                            <p class="text-sm font-black uppercase tracking-widest">No Backups Found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($backups as $backup): ?>
                                <tr class="hover:bg-gray-50/60 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="p-2 bg-gray-100 group-hover:bg-white rounded-lg transition-colors mt-0.5 shrink-0">
                                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400 group-hover:text-indigo-500"></i>
                                            </div>
                                            <div class="flex flex-col min-w-0">
                                                <span class="text-sm font-bold text-gray-800 tracking-tight break-all">
                                                    <?= htmlspecialchars($backup['name']) ?>
                                                </span>
                                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                                    <span class="text-[10px] text-gray-400 font-medium">
                                                        <?= date('M d, Y - h:i A', $backup['date']) ?>
                                                    </span>
                                                    <span class="text-gray-300">•</span>
                                                    <?php if ($backup['type'] === 'Full Database'): ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600 border border-gray-200" style="font-size: 10px; line-height: 12px;">
                                                            Full Database
                                                        </span>
                                                    <?php elseif ($backup['type'] === 'Year Filtered'): ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200" style="font-size: 10px; line-height: 12px;">
                                                            All Tables • Year <?= htmlspecialchars($backup['year']) ?>
                                                        </span>
                                                    <?php elseif ($backup['type'] === 'Table Filtered'): ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200" style="font-size: 10px; line-height: 12px;">
                                                            Table: <?= htmlspecialchars($backup['table']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200" style="font-size: 10px; line-height: 12px;">
                                                            Table: <?= htmlspecialchars($backup['table']) ?> • Year <?= htmlspecialchars($backup['year']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-xs font-black text-gray-500 tabular-nums">
                                            <?= \App\Controllers\it_admin\formatSize($backup['size']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Download SQL File -->
                                            <a href="?page=backup-maintenance&action=download_backup&filename=<?= urlencode($backup['name']) ?>"
                                                class="p-2 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                                title="Download SQL File">
                                                <i data-lucide="download" class="w-4 h-4"></i>
                                            </a>

                                            <!-- Move to Trash -->
                                            <button onclick="confirmDelete('<?= htmlspecialchars($backup['name']) ?>')"
                                                class="p-2 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                                title="Move to Trash">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trash Bin Modal -->
<div id="trash-modal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200 flex flex-col" style="max-height: 85vh;">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Backup Trash Bin</h3>
                    <p class="text-[11px] text-gray-500">Deleted backup files that can be restored to the table.</p>
                </div>
            </div>
            <button type="button" onclick="closeTrashModal()" class="text-gray-400 hover:text-gray-600 transition p-1.5 rounded-lg hover:bg-gray-100">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar flex-1" style="max-height: 400px;">
            <?php if (empty($trashBackups)): ?>
                <div class="py-12 text-center text-gray-400 space-y-2">
                    <i data-lucide="inbox" class="w-10 h-10 mx-auto opacity-30"></i>
                    <p class="text-xs font-semibold uppercase tracking-wider">Trash Bin is Empty</p>
                    <p class="text-[11px] text-gray-400">Deleted backups will appear here and can be restored anytime.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($trashBackups as $tb): ?>
                        <div class="py-2.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-semibold text-gray-800 truncate" style="font-size: 12px; line-height: 16px;" title="<?= htmlspecialchars($tb['name']) ?>">
                                        <?= htmlspecialchars($tb['name']) ?>
                                    </span>
                                    <div class="flex items-center gap-1.5 text-gray-400 mt-0.5" style="font-size: 10px; line-height: 14px;">
                                        <span style="font-size: 10px;"><?= date('M d, Y - h:i A', $tb['date']) ?></span>
                                        <span class="text-gray-300">•</span>
                                        <span style="font-size: 10px;"><?= \App\Controllers\it_admin\formatSize($tb['size']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <form action="" method="POST" class="inline">
                                    <input type="hidden" name="action" value="restore_deleted_file">
                                    <input type="hidden" name="filename" value="<?= htmlspecialchars($tb['name']) ?>">
                                    <button type="submit"
                                        class="px-2.5 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition shadow-xs flex items-center gap-1.5 cursor-pointer text-xs font-semibold"
                                        title="Restore to Backup History">
                                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                        <span>Restore</span>
                                    </button>
                                </form>
                                <button type="button" onclick="confirmPurge('<?= htmlspecialchars($tb['name']) ?>')"
                                    class="px-2.5 py-1.5 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 transition shadow-xs flex items-center gap-1.5 cursor-pointer text-xs font-semibold"
                                    title="Permanently Delete">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between shrink-0">
            <?php if (!empty($trashBackups)): ?>
                <button type="button" onclick="confirmEmptyTrash()"
                    class="text-xs font-bold text-red-600 hover:text-red-700 transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    Empty Trash Bin
                </button>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <button type="button" onclick="closeTrashModal()"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Hidden Action Forms -->
<form id="delete-form" action="" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete_backup">
    <input type="hidden" name="filename" id="delete-filename">
</form>

<form id="purge-form" action="" method="POST" class="hidden">
    <input type="hidden" name="action" value="purge_file">
    <input type="hidden" name="filename" id="purge-filename">
</form>

<form id="empty-trash-form" action="" method="POST" class="hidden">
    <input type="hidden" name="action" value="empty_trash">
</form>

<form id="restore-db-form" action="" method="POST" class="hidden">
    <input type="hidden" name="action" value="restore_database">
    <input type="hidden" name="filename" id="restore-db-filename">
</form>

<script>
    async function triggerFilteredBackup(btn, event) {
        const tableSelect = document.getElementById('table_filter');
        const yearSelect = document.getElementById('year_filter');
        const form = document.getElementById('filtered-backup-form');

        const tableText = tableSelect ? tableSelect.options[tableSelect.selectedIndex].text : 'All Tables';
        const yearText = yearSelect ? yearSelect.options[yearSelect.selectedIndex].text : 'All Years';

        const result = await confirmAlert(
            'Confirm Selective Export',
            `Generate SQL export for:\nTarget: ${tableText}\nScope: ${yearText}\n\nDo you wish to proceed?`
        );

        if (result.isConfirmed) {
            form.submit();
        }
    }

    async function confirmDelete(filename) {
        const result = await confirmAlert(
            'Move Backup to Trash',
            `Move backup to trash: ${filename}?\n\nYou can restore it back to the table anytime from the Trash Bin.`
        );
        if (result.isConfirmed) {
            document.getElementById('delete-filename').value = filename;
            document.getElementById('delete-form').submit();
        }
    }

    async function confirmDatabaseRestore(filename) {
        const result = await confirmAlert(
            'Confirm Database Restore',
            `WARNING: Are you sure you want to restore the database from this backup: ${filename}?\n\nThis will execute the SQL file and update the database records. Make sure you have current backups before proceeding.`
        );
        if (result.isConfirmed) {
            document.getElementById('restore-db-filename').value = filename;
            document.getElementById('restore-db-form').submit();
        }
    }

    async function confirmPurge(filename) {
        const result = await confirmAlert(
            'Confirm Permanent Deletion',
            `Permanently delete: ${filename}?\n\nThis action cannot be undone.`
        );
        if (result.isConfirmed) {
            document.getElementById('purge-filename').value = filename;
            document.getElementById('purge-form').submit();
        }
    }

    async function confirmEmptyTrash() {
        const result = await confirmAlert(
            'Empty Trash Bin',
            'Are you sure you want to permanently delete all backups currently in the Trash Bin? This cannot be undone.'
        );
        if (result.isConfirmed) {
            document.getElementById('empty-trash-form').submit();
        }
    }

    function openTrashModal() {
        const modal = document.getElementById('trash-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function closeTrashModal() {
        const modal = document.getElementById('trash-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function dismissAlert(alertId) {
        const el = document.getElementById(alertId);
        if (!el) return;
        el.style.transition = 'opacity 0.4s ease, transform 0.4s ease, max-height 0.4s ease, margin 0.4s ease, padding 0.4s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-6px)';
        setTimeout(() => el.remove(), 400);
    }

    function setupAutoDismiss(alertId, delay = 6000) {
        const el = document.getElementById(alertId);
        if (!el) return;

        let timer;
        const startTimer = () => {
            timer = setTimeout(() => dismissAlert(alertId), delay);
        };

        el.addEventListener('mouseenter', () => clearTimeout(timer));
        el.addEventListener('mouseleave', () => startTimer());
        startTimer();
    }

    setupAutoDismiss('backup-success-alert', 6000);
    setupAutoDismiss('backup-error-alert', 6000);

    <?php if ($success): ?>
    (function () {
        function triggerSuccessAlert() {
            if (typeof Swal !== 'undefined') {
                const sMsg = <?= json_encode($success) ?>;
                let alertTitle = 'Success!';
                const lower = sMsg.toLowerCase();
                if (lower.includes('permanent') || lower.includes('purged')) {
                    alertTitle = 'Successfully Deleted';
                } else if (lower.includes('deleted') || lower.includes('trash')) {
                    alertTitle = 'Successfully Deleted';
                } else if (lower.includes('restored') || lower.includes('database restored')) {
                    alertTitle = 'Successfully Restored';
                } else if (lower.includes('generated') || lower.includes('backup created') || lower.includes('created') || lower.includes('export')) {
                    alertTitle = 'Backup Successful';
                }

                Swal.fire({
                    icon: 'success',
                    title: alertTitle,
                    text: sMsg,
                    showConfirmButton: true,
                    confirmButtonColor: '#10b981',
                    timer: 3500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-3xl border-0 shadow-2xl',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', triggerSuccessAlert);
        } else {
            triggerSuccessAlert();
        }
    })();
    <?php endif; ?>

    <?php if ($error): ?>
    (function () {
        function triggerErrorAlert() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Action Failed',
                    text: <?= json_encode($error) ?>,
                    showConfirmButton: true,
                    confirmButtonColor: '#ef4444',
                    timer: 4500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-3xl border-0 shadow-2xl',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', triggerErrorAlert);
        } else {
            triggerErrorAlert();
        }
    })();
    <?php endif; ?>

    function syncSnapshotCardHeight() {
        const statsCard = document.getElementById('stats-card');
        const selectiveCard = document.getElementById('selective-export-card');
        const snapshotCard = document.getElementById('snapshot-history-card');
        
        if (statsCard && selectiveCard && snapshotCard) {
            if (window.innerWidth >= 1024) {
                const top = statsCard.getBoundingClientRect().top;
                const bottom = selectiveCard.getBoundingClientRect().bottom;
                const targetHeight = Math.round(bottom - top);
                if (targetHeight > 100) {
                    snapshotCard.style.height = targetHeight + 'px';
                    snapshotCard.style.maxHeight = targetHeight + 'px';
                }
            } else {
                snapshotCard.style.height = '';
                snapshotCard.style.maxHeight = '';
            }
        }
    }

    window.addEventListener('resize', syncSnapshotCardHeight);
    window.addEventListener('DOMContentLoaded', syncSnapshotCardHeight);
    setTimeout(syncSnapshotCardHeight, 50);
    setTimeout(syncSnapshotCardHeight, 300);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>