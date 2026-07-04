<?php
/**
 * Patient History View (Central Admin)
 * Shows a timeline of all clinical encounters for a patient.
 */
?>

<main class="flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mx-auto max-w-5xl space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <?php $backUrl = ($source === 'records') ? '/' . PROJECT_DIR . '/patient-records' : '/' . PROJECT_DIR . '/patient-details?id=' . $patient['id']; ?>
                <a href="<?= htmlspecialchars($backUrl) ?>"
                    class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 hover:text-red-600 transition-all shadow-sm">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Clinical History</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Record timeline for
                        <?= htmlspecialchars($patient['first_name'] ?? '') ?>
                        <?= htmlspecialchars($patient['last_name'] ?? '') ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="px-3 py-1 bg-red-50 text-red-600 rounded-full text-[10px] font-bold uppercase tracking-widest border border-red-100">
                    Patient Number: <?= htmlspecialchars($patientNumber) ?>
                </span>
            </div>
        </div>

        <?php if (empty($history)): ?>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-12 text-center">
                <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                    <div class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center">
                        <i data-lucide="folder-search" class="w-8 h-8 text-gray-300"></i>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">No clinical cases found</h2>
                    <p class="text-sm text-gray-500 leading-relaxed">This patient has no registered clinical encounters or
                        X-ray examinations in the system.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Timeline of Cases -->
            <div class="relative">

                <div class="space-y-8">
                    <?php foreach ($history as $case): ?>
                        <div>

                            <div
                                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden group hover:border-red-200 dark:hover:border-red-500 transition-all">
                                <div
                                    class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded-md border border-indigo-100 dark:border-indigo-800/50 tracking-wider">
                                            <?= htmlspecialchars($case['case_number']) ?>
                                        </span>
                                        <div
                                            class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-bold text-gray-700 dark:text-gray-200 shadow-sm">
                                            <?= date('M d, Y', strtotime($case['created_at'])) ?>
                                        </div>
                                        <span
                                            class="text-xs font-medium text-gray-400 dark:text-gray-500"><?= date('g:i A', strtotime($case['created_at'])) ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php
                                        $priorityClass = 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600';
                                        if ($case['priority'] === 'STAT')
                                            $priorityClass = 'bg-red-50 dark:bg-red-900/50 text-red-600 dark:text-red-400 border-red-100 dark:border-red-800/50';
                                        elseif ($case['priority'] === 'Urgent')
                                            $priorityClass = 'bg-yellow-50 dark:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400 border-yellow-100 dark:border-yellow-800/50';
                                        elseif ($case['priority'] === 'Routine')
                                            $priorityClass = 'bg-blue-50 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 border-blue-100 dark:border-blue-800/50';
                                        ?>
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border <?= $priorityClass ?>">
                                            <?= htmlspecialchars($case['priority']) ?>
                                        </span>
                                        <span
                                            class="px-2 py-0.5 bg-gray-900 dark:bg-gray-700 text-white rounded text-[10px] font-bold uppercase tracking-wider">
                                            <?= htmlspecialchars($case['branch_name'] ?? 'Main Branch') ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Case Type & Modality -->
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none mb-2">
                                                Exam Type
                                            </p>
                                            <div class="flex flex-wrap gap-1.5 mt-1.5">
                                                <?php 
                                                $exams = explode(',', $case['exam_type'] ?? 'General Diagnostic');
                                                foreach ($exams as $exam): 
                                                    $exam = trim($exam);
                                                    if (!empty($exam)):
                                                ?>
                                                    <span class="px-2 py-1 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded text-[11px] font-bold tracking-wide border border-gray-200 dark:border-gray-600 shadow-sm">
                                                        <?= htmlspecialchars($exam) ?>
                                                    </span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                        </div>
                                        <div class="pt-2">
                                            <p
                                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none mb-1">
                                                PhilHealth ID</p>
                                            <p class="text-xs font-mono font-medium text-gray-700 dark:text-gray-300">
                                                <?= (!empty($case['philhealth_id']) && ($case['philhealth_status'] ?? '') === 'With PhilHealth Card')
                                                    ? htmlspecialchars($case['philhealth_id'])
                                                    : 'Without PhilHealth ID' ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Status & Clinical Info -->
                                    <div class="space-y-3 md:border-l md:border-gray-50 dark:md:border-gray-700 md:pl-6">
                                        <div>
                                            <p
                                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none mb-2">
                                                Radiologist</p>
                                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                                <?= !empty($case['radiologist_name']) ? 'Dr. ' . htmlspecialchars($case['radiologist_name']) : 'Not Assigned' ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-2">
                                                Examination Status</p>
                                            <?php
                                            $statusText = $case['status'] ?? 'Released';
                                            $statusColor = 'bg-green-50 border-green-200 text-green-700';
                                            if ($statusText === 'Pending')
                                                $statusColor = 'bg-orange-50 border-orange-200 text-orange-700';
                                            elseif ($statusText === 'Under Reading')
                                                $statusColor = 'bg-blue-50 border-blue-200 text-blue-700';
                                            elseif ($statusText === 'Report Ready')
                                                $statusColor = 'bg-purple-50 border-purple-200 text-purple-700';
                                            ?>
                                            <div
                                                class="inline-flex items-center justify-center px-3 py-1 rounded-full border <?= $statusColor ?> text-xs font-bold tracking-wide">
                                                <?= htmlspecialchars($statusText) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Summary / Actions -->
                                    <div
                                        class="flex flex-col justify-between md:border-l md:border-gray-50 dark:md:border-gray-700 md:pl-6">
                                        <div class="space-y-2">
                                            <p
                                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">
                                                Diagnostic Impressions</p>
                                            <p
                                                class="text-xs text-gray-600 dark:text-gray-300 italic line-clamp-2 leading-relaxed">
                                                <?= htmlspecialchars($case['findings'] ?? 'No findings recorded for this encounter.') ?>
                                            </p>
                                        </div>
                                        <?php if (!empty($case['findings'])): ?>
                                            <div class="pt-4">
                                                <button onclick="viewCaseDetail('<?= $case['id'] ?>')"
                                                    class="w-full py-2 px-4 rounded-lg bg-gray-50 dark:bg-gray-700 text-xs font-bold text-gray-600 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-600 transition-all flex items-center justify-center gap-2">
                                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                                    View Full Report
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <div
                        class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-6 mt-8 gap-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                            Showing <span
                                class="font-semibold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($offset + 1) ?></span>
                            to <span
                                class="font-semibold text-gray-800 dark:text-gray-200"><?= htmlspecialchars(min($offset + $itemsPerPage, $totalItems)) ?></span>
                            of <span
                                class="font-semibold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($totalItems) ?></span>
                            cases
                        </span>

                        <div class="flex items-center flex-wrap gap-1.5">
                            <?php
                            $currentPage = (int) ($currentPage ?? 1);
                            // Check if records-history is accessed via route rewriting or index.php
                            $baseUrl = "/" . PROJECT_DIR . "/records-history?patient_number=" . urlencode($patient['patient_number']) . "&p=";

                            // Calculate sliding window
                            $range = 2; // Show 2 pages before and after
                            $start = max(1, $currentPage - $range);
                            $end = min($totalPages, $currentPage + $range);

                            // Adjust window if at edges to show up to 5 pages
                            if ($end - $start < $range * 2) {
                                if ($start === 1) {
                                    $end = min($totalPages, $start + ($range * 2));
                                } elseif ($end === $totalPages) {
                                    $start = max(1, $end - ($range * 2));
                                }
                            }

                            // Helper for disabled vs active buttons
                            $renderButton = function ($label, $targetPage, $isDisabled) use ($baseUrl) {
                                if ($isDisabled) {
                                    return '<span class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-xs font-semibold text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-60">' . $label . '</span>';
                                } else {
                                    return '<a href="' . $baseUrl . $targetPage . '" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-red-50 hover:text-red-600 hover:border-red-200 dark:hover:bg-red-900/20 dark:hover:text-red-400 dark:hover:border-red-800 focus:ring-2 focus:ring-red-400 transition shadow-sm">' . $label . '</a>';
                                }
                            };
                            ?>

                            <!-- First -->
                            <?= $renderButton('&laquo; First', 1, $currentPage <= 1) ?>

                            <!-- Back -->
                            <?= $renderButton('&lsaquo; Back', $currentPage - 1, $currentPage <= 1) ?>

                            <!-- Left Ellipsis -->
                            <?php if ($start > 1): ?>
                                <span class="px-2 py-1.5 text-xs font-semibold text-gray-500">...</span>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <a href="<?= $baseUrl . $i ?>"
                                    class="<?= $i == $currentPage ? 'px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600' : 'px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-red-50 hover:text-red-600 hover:border-red-200 dark:hover:bg-red-900/20 dark:hover:text-red-400 dark:hover:border-red-800 focus:ring-2 focus:ring-red-400 transition shadow-sm' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Right Ellipsis -->
                            <?php if ($end < $totalPages): ?>
                                <span class="px-2 py-1.5 text-xs font-semibold text-gray-500">...</span>
                            <?php endif; ?>

                            <!-- Next -->
                            <?= $renderButton('Next &rsaquo;', $currentPage + 1, $currentPage >= $totalPages) ?>

                            <!-- Last -->
                            <?= $renderButton('Last &raquo;', $totalPages, $currentPage >= $totalPages) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });

    function viewCaseDetail(caseId) {
        const url = `/<?= PROJECT_DIR ?>/index.php?page=print-report&id=${caseId}&preview=true`;
        const popupWidth = 850;
        const popupHeight = 800;
        const left = (screen.width - popupWidth) / 2;
        const top = (screen.height - popupHeight) / 2;

        window.open(url, 'ReportViewer', `width=${popupWidth},height=${popupHeight},top=${top},left=${left},scrollbars=yes,resizable=yes`);
    }
</script>