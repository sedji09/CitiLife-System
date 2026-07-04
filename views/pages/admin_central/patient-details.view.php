<?php
/**
 * Patient Details View (Central Admin)
 * Shows comprehensive profile for a single patient.
 */
?>

<main class="flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mx-auto max-w-4xl space-y-6">
        <!-- Breadcrumbs & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/<?= PROJECT_DIR ?>/patient-records"
                    class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:border-red-100 dark:hover:border-red-500/50 transition-all shadow-sm">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Patient Profile</h1>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="/<?= PROJECT_DIR ?>/records-history?patient_number=<?= urlencode($patient['patient_number']) ?>"
                    class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-all">
                    <i data-lucide="history" class="w-4 h-4 text-blue-500"></i>
                    View Clinical History
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Primary Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Identity Card -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-6 flex flex-col sm:flex-row items-center gap-6">
                        <?php if (!empty($patient['avatar'])): ?>
                            <img src="<?= htmlspecialchars($patient['avatar']) ?>" alt="Profile Picture"
                                class="h-24 w-24 rounded-2xl object-cover shadow-sm border border-gray-200 dark:border-gray-700 shrink-0">
                        <?php else: ?>
                            <div
                                class="h-24 w-24 rounded-2xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800/50 shrink-0">
                                <span
                                    class="text-3xl font-bold uppercase"><?= substr($patient['first_name'] ?? 'P', 0, 1) ?><?= substr($patient['last_name'] ?? '', 0, 1) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex-1 text-center sm:text-left">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">
                                <?= htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')) ?>
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($patient['patient_number'] ?? 'N/A') ?>
                            </p>
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 mt-2">
                                <span
                                    class="px-2.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[11px] font-bold uppercase tracking-wider">
                                    <?= htmlspecialchars($patient['sex'] ?? 'N/A') ?>
                                </span>
                                <span
                                    class="px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold border border-blue-100 dark:border-blue-800/50 uppercase tracking-wider">
                                    <?= htmlspecialchars($patient['age'] ?? '0') ?> Years Old
                                </span>
                                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">
                                    <?php $bdate = !empty($patient['birthdate']) ? $patient['birthdate'] : 'today'; ?>
                                    Born <?= date('M d, Y', strtotime($bdate)) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Information Grid -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">Registration Details</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                        <div class="space-y-1">
                            <label
                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Email
                                Address</label>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                <i data-lucide="mail" class="w-4 h-4 text-gray-300 dark:text-gray-600"></i>
                                <?= htmlspecialchars($patient['email'] ?? 'N/A') ?>
                            </p>
                        </div>
                        <div class="space-y-1">
                            <label
                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Phone
                                Number</label>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                <i data-lucide="phone" class="w-4 h-4 text-gray-300 dark:text-gray-600"></i>
                                <?= htmlspecialchars($patient['contact_number'] ?? 'N/A') ?>
                            </p>
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <label
                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Home
                                Address</label>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-start gap-2">
                                <i data-lucide="map-pin" class="w-4 h-4 text-gray-300 dark:text-gray-600 mt-0.5"></i>
                                <?= htmlspecialchars($patient['home_address'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <div class="space-y-1">
                            <label
                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Registered
                                Date</label>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-gray-300 dark:text-gray-600"></i>
                                <?= date('F j, Y', strtotime($patient['created_at'] ?? 'today')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Quick Notes & Stats -->
            <div class="space-y-6">

                <!-- Clinical Status -->
                <div
                    class="bg-gray-900 rounded-2xl border border-gray-800 shadow-xl p-6 text-white relative overflow-hidden group">
                    <i data-lucide="activity"
                        class="absolute -bottom-4 -right-4 w-24 h-24 opacity-10 group-hover:scale-110 transition-transform"></i>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Case Statistics</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-2xl font-bold"><?= (int) ($patientStats['total_exams'] ?? 0) ?></p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Total Exams</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-2xl font-bold text-emerald-400">
                                <?= (int) ($patientStats['branches_visited'] ?? 0) ?>
                            </p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Branches Visited
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-800">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Last
                                Visit</span>
                            <span class="text-[10px] font-bold text-blue-400">
                                <?= !empty($patientStats['last_visit']) ? date('M d, Y', strtotime($patientStats['last_visit'])) : 'Never' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>