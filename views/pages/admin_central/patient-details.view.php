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
                <a href="?page=patient-records" class="p-2 rounded-xl bg-white border border-gray-200 text-gray-400 hover:text-red-600 hover:border-red-100 transition-all shadow-sm">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Patient Profile</h1>
                    <p class="text-sm text-gray-500">Case #<?= htmlspecialchars($patient['patient_number'] ?? 'N/A') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="?page=patient-history&patient_number=<?= urlencode($patient['patient_number']) ?>" 
                   class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 shadow-sm transition-all">
                    <i data-lucide="history" class="w-4 h-4 text-blue-500"></i>
                    View Clinical History
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Primary Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Identity Card -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-6 flex flex-col sm:flex-row items-center gap-6">
                        <div class="h-24 w-24 rounded-2xl bg-red-50 flex items-center justify-center text-red-600 border border-red-100">
                             <span class="text-3xl font-bold uppercase"><?= substr($patient['first_name'] ?? 'P', 0, 1) ?><?= substr($patient['last_name'] ?? '', 0, 1) ?></span>
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <h2 class="text-2xl font-bold text-gray-900 leading-tight">
                                <?= htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')) ?>
                            </h2>
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 mt-2">
                                <span class="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600 text-[11px] font-bold uppercase tracking-wider">
                                    <?= htmlspecialchars($patient['gender'] ?? 'N/A') ?>
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[11px] font-bold border border-blue-100 uppercase tracking-wider">
                                    <?= htmlspecialchars($patient['age'] ?? '0') ?> Years Old
                                </span>
                                <span class="text-xs text-gray-400 font-medium">
                                    Born <?= date('M d, Y', strtotime($patient['date_of_birth'] ?? 'today')) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Information Grid -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-sm font-bold text-gray-800">Registration Details</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Address</label>
                            <p class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <i data-lucide="mail" class="w-4 h-4 text-gray-300"></i>
                                <?= htmlspecialchars($patient['email'] ?? 'N/A') ?>
                            </p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Phone Number</label>
                            <p class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <i data-lucide="phone" class="w-4 h-4 text-gray-300"></i>
                                <?= htmlspecialchars($patient['contact_number'] ?? 'N/A') ?>
                            </p>
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Home Address</label>
                            <p class="text-sm font-bold text-gray-800 flex items-start gap-2">
                                <i data-lucide="map-pin" class="w-4 h-4 text-gray-300 mt-0.5"></i>
                                <?= htmlspecialchars($patient['address'] ?? 'N/A') ?>
                            </p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">PhilHealth ID</label>
                            <p class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-4 h-4 text-gray-300"></i>
                                <?= htmlspecialchars($patient['philhealth_id'] ?? 'Not Provided') ?>
                            </p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Registered Date</label>
                            <p class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-gray-300"></i>
                                <?= date('F j, Y', strtotime($patient['created_at'] ?? 'today')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Quick Notes & Stats -->
            <div class="space-y-6">
                 <!-- Emergency Contact Card -->
                 <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden p-6 space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="h-10 w-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600">
                            <i data-lucide="ambulance" class="w-5 h-5"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">Emergency Contact</h3>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Contact Name</p>
                            <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($patient['emergency_name'] ?? 'None Listed') ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Relationship / Phone</p>
                            <p class="text-xs font-medium text-gray-600"><?= htmlspecialchars($patient['emergency_contact'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                 </div>

                 <!-- Clinical Status -->
                 <div class="bg-gray-900 rounded-2xl border border-gray-800 shadow-xl p-6 text-white relative overflow-hidden group">
                    <i data-lucide="activity" class="absolute -bottom-4 -right-4 w-24 h-24 opacity-10 group-hover:scale-110 transition-transform"></i>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Case Statistics</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-2xl font-bold">0</p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Total Exams</p>
                        </div>
                         <div class="space-y-1">
                            <p class="text-2xl font-bold text-red-500">0</p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Critical</p>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-800">
                         <div class="flex items-center justify-between">
                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Last Visit</span>
                            <span class="text-[10px] font-bold text-blue-400">Never</span>
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
