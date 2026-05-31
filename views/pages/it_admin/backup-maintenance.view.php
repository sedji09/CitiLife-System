<!-- IT Backup & Maintenance -->
<div class="max-w-5xl mx-auto space-y-6 animate-in fade-in duration-500">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Backup & Maintenance</h1>
            <p class="text-sm text-gray-500 mt-1">Manage database snapshots and ensure system data integrity.</p>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-full">
            <i data-lucide="database" class="w-4 h-4 text-indigo-600"></i>
            <span class="text-[10px] font-black text-indigo-700 uppercase tracking-widest leading-none">System Engine
                Active</span>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success): ?>
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 flex items-center gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
            <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
            <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Side: Actions & Stats -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Stats Card -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm overflow-hidden relative group">
                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Snapshot
                            Count</p>
                        <i data-lucide="server"
                            class="w-4 h-4 text-gray-300 group-hover:text-indigo-400 transition-colors"></i>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-gray-900 leading-none"><?= count($backups) ?></span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">Snapshots</span>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-400 uppercase">Latest Sync:</span>
                        <span
                            class="text-[11px] font-black text-indigo-600"><?= !empty($backups) ? date('M d, H:i', $backups[0]['date']) : 'Never' ?></span>
                    </div>
                </div>
            </div>

            <!-- Generate Action Card -->
            <div
                class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm flex flex-col items-center text-center space-y-6">
                <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600">
                    <i data-lucide="database-backup" class="w-8 h-8"></i>
                </div>
                <div>
                    <h3 class="text-md font-bold text-gray-900 tracking-tight">Run System Backup</h3>
                    <p class="text-[11px] text-gray-400 mt-2 leading-relaxed px-2">Generates a complete SQL file of the
                        current state.</p>
                </div>
                <form action="" method="POST" class="w-full">
                    <button type="button" 
                        onclick="confirmFormAction(this, 'generate_backup', 'Confirm System Backup', 'Are you sure you want to initialize a new system backup? This will generate a full SQL snapshot of the current database.', 'action', event)"
                        class="w-full py-4 bg-white border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-sm active:scale-95 flex items-center justify-center gap-2">
                        <i data-lucide="play" class="w-3 h-3 fill-current text-current"></i>
                        Initialize Now
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Side: Backups List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col h-full">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/30 flex items-center justify-between">
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Snapshot History</h3>
                    <span
                        class="px-2 py-1 bg-white border border-gray-200 rounded text-[9px] font-bold text-gray-400 uppercase tracking-widest leading-none">Local
                        Storage</span>
                </div>

                <div class="overflow-x-auto flex-grow">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/20 border-b border-gray-100">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    Filename</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    Size</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (empty($backups)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center gap-3 opacity-20">
                                            <i data-lucide="folder-search" class="w-12 h-12"></i>
                                            <p class="text-sm font-black uppercase tracking-widest">No Backups Found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($backups as $backup): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-gray-100 group-hover:bg-white rounded-lg transition-colors">
                                                <i data-lucide="file-text"
                                                    class="w-4 h-4 text-gray-400 group-hover:text-indigo-500"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-bold text-gray-700 tracking-tight"><?= htmlspecialchars($backup['name']) ?></span>
                                                <span
                                                    class="text-[10px] text-gray-400 font-medium"><?= date('M d, Y - h:i A', $backup['date']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="text-xs font-black text-gray-400 tabular-nums"><?= formatSize($backup['size']) ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <a href="?page=backup-maintenance&action=download_backup&filename=<?= urlencode($backup['name']) ?>"
                                                class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm"
                                                title="Download SQL File">
                                                <i data-lucide="download" class="w-4 h-4"></i>
                                            </a>
                                            <button onclick="confirmDelete('<?= htmlspecialchars($backup['name']) ?>')"
                                                class="p-2 bg-white border border-gray-200 rounded-lg text-gray-400 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm"
                                                title="Delete Permanent">
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

<!-- Simple Hidden Form for Deletion -->
<form id="delete-form" action="" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete_backup">
    <input type="hidden" name="filename" id="delete-filename">
</form>

<script>
    async function confirmDelete(filename) {
        const result = await confirmAlert('Confirm Deletion', `Would you like to confirm deleting the backup: ${filename}? This cannot be undone.`);
        if (result.isConfirmed) {
            document.getElementById('delete-filename').value = filename;
            document.getElementById('delete-form').submit();
        }
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>