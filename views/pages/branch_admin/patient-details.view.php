<?php
/**
 * Patient Details View
 * Backend logic handled by PatientDetailsController.php
 */
if (isset($caseNotFound) && $caseNotFound) {
    echo "<div class='p-6 mt-10 text-center text-red-600 bg-red-50 rounded-lg'>Case not found or invalid ID.</div>";
    return; // Stop rendering the view
}
?>

<!-- Header -->
<div class="flex items-center gap-4">
    <a href="javascript:void(0)" data-back-btn data-fallback="<?= url('branch-xray-cases') ?>" title="Back" class="flex w-10 h-10 items-center justify-center rounded-xl bg-white border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors mt-1">
        <i data-lucide="chevron-left" class="w-5 h-5"></i>
    </a>
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient Details</h2>
        <p class="text-sm text-gray-500 mt-1">View patient examination and clinical information</p>
    </div>
</div>

<?php if ($errorMsg): ?>
    <div class="mt-5 rounded-lg bg-red-50 border border-red-300 p-4 flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
        <p class="text-sm text-red-700"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Patient Verification -->
    <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col justify-between h-full">
        <div class="mb-3 flex items-center gap-2">
            <i data-lucide="user-check" class="h-5 w-5 text-green-600"></i>
            <h3 class="text-lg font-semibold text-gray-800">Patient Verification</h3>
        </div>
        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 flex-1 flex flex-col justify-center">
            <div class="px-2 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Case Number</span>
                    <span
                        class="font-bold text-gray-900"><?= htmlspecialchars($caseDetails['case_number']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Patient Number</span>
                    <span
                        class="font-bold text-gray-900"><?= htmlspecialchars($caseDetails['patient_number']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Full Name</span>
                    <span
                        class="font-bold text-gray-900"><?= htmlspecialchars(formatFullName($caseDetails)) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Age/Sex</span>
                    <span
                        class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['age'] . ' / ' . $caseDetails['sex']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Contact Number</span>
                    <span
                        class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['contact_number'] ?? '—') ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Branch</span>
                    <span
                        class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['branch_name'] ?? '—') ?></span>
                </div>
                <?php if (($caseDetails['philhealth_status'] ?? '') === 'With PhilHealth Card' && !empty($caseDetails['philhealth_id'])): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">PhilHealth Number</span>
                        <span
                            class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['philhealth_id']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Examination Details -->
    <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col justify-between h-full">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Examination Details</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1.5">Exam Types</label>
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $exams = explode(',', $caseDetails['exam_type']);
                    foreach($exams as $ex): 
                    ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                            <?= htmlspecialchars(trim($ex)) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1.5">Priority</label>
                <?php
                $pBorder = '1.5px solid #60a5fa';
                $pBg = '#eff6ff';
                $pColor = '#1d4ed8';
                if ($caseDetails['priority'] === 'STAT') {
                    $pBorder = '1.5px solid #f87171';
                    $pBg = '#fef2f2';
                    $pColor = '#b91c1c';
                } elseif ($caseDetails['priority'] === 'Urgent') {
                    $pBorder = '1.5px solid #facc15';
                    $pBg = '#fefce8';
                    $pColor = '#a16207';
                } elseif ($caseDetails['priority'] === 'Priority') {
                    $pBorder = '1.5px solid #fb923c';
                    $pBg = '#fff7ed';
                    $pColor = '#c2410c';
                }
                ?>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                    style="border:<?= $pBorder ?>;background-color:<?= $pBg ?>;color:<?= $pColor ?>">
                    <?= htmlspecialchars($caseDetails['priority'] ?: 'Routine') ?>
                </span>
            </div>
            <div class="pt-1">
                <span class="block text-gray-600 text-sm font-medium mb-1.5">Status</span>
                <?php
                $displayStatus = $caseDetails['status'] ?: 'Pending';
                $isOverdue = (time() - strtotime($caseDetails['created_at'])) >= 3 * 3600;
                if ($displayStatus === 'Pending' && $isOverdue) {
                    $displayStatus = 'Overdue';
                }

                $sBorder = '1.5px solid #facc15';
                $sBg = '#fefce8';
                $sColor = '#a16207';
                if ($displayStatus === 'Report Ready') {
                    $sBorder = '1.5px solid #818cf8';
                    $sBg = '#eef2ff';
                    $sColor = '#4338ca';
                } elseif ($displayStatus === 'Under Reading') {
                    $sBorder = '1.5px solid #60a5fa';
                    $sBg = '#eff6ff';
                    $sColor = '#1d4ed8';
                } elseif ($displayStatus === 'Completed') {
                    $sBorder = '1.5px solid #4ade80';
                    $sBg = '#f0fdf4';
                    $sColor = '#15803d';
                } elseif ($displayStatus === 'Overdue' || $displayStatus === 'Rejected') {
                    $sBorder = '1.5px solid #f87171';
                    $sBg = '#fef2f2';
                    $sColor = '#b91c1c';
                } elseif ($displayStatus === 'Released') {
                    $sBorder = '1.5px solid #34d399';
                    $sBg = '#ecfdf5';
                    $sColor = '#047857';
                }
                ?>
                <span class="inline-block font-bold text-xs px-3 py-1.5 rounded-full"
                    style="border:<?= $sBorder ?>;background-color:<?= $sBg ?>;color:<?= $sColor ?>">
                    <?= htmlspecialchars($displayStatus) ?>
                </span>
            </div>
        </div>
    </div>


</div>

<?php 
$isReverted = !empty($caseDetails['re_edit_reason']) 
    || ($caseDetails['report_status'] ?? '') === 'Draft' 
    || in_array($caseDetails['status'], ['Under Reading', 'Pending', 'For Revision', 'Rejected', 'Cancelled']);
$isReportReady = in_array($caseDetails['status'], ['Report Ready', 'Completed', 'Released']) && !$isReverted; 
?>

<div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">

<div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col justify-between h-full">
    <div class="mb-4">
        <div class="flex items-center gap-2">
            <i data-lucide="image" class="h-5 w-5 text-blue-600"></i>
            <h3 class="text-lg font-semibold text-gray-800">Diagnostic Image Archive</h3>
        </div>
        <p class="text-xs text-gray-500 mt-1">Archived X-ray images and diagnostic files</p>
    </div>

    <div id="file-preview-area" class="flex-1 flex flex-col justify-center">
        <!-- Read-only image grid -->
        <?php
        if (!function_exists('getXrayImageLabel')) {
            function getXrayImageLabel($sPath, $idx = 0, $examType = '') {
                $baseName = pathinfo($sPath, PATHINFO_FILENAME);
                if (preg_match('/^case_\d+_\d+_\d+_(.+)$/', $baseName, $m)) {
                    return trim($m[1]);
                }
                if (!empty($examType)) {
                    $exams = array_values(array_filter(array_map('trim', explode(',', $examType))));
                    if (isset($exams[$idx]) && $exams[$idx] !== '') {
                        return $exams[$idx];
                    }
                }
                if (!preg_match('/^case_\d+/i', $baseName) && strlen($baseName) > 2) {
                    return str_replace(['_', '-'], ' ', $baseName);
                }
                if (!empty($examType) && !str_contains($examType, ',')) {
                    return trim($examType);
                }
                return 'IMG ' . ($idx + 1);
            }
        }
        $savedPaths = [];
        if (!empty($caseDetails['image_path'])) {
            $decoded = json_decode($caseDetails['image_path'], true);
            if (is_array($decoded)) {
                $savedPaths = $decoded;
            } else {
                $savedPaths = [$caseDetails['image_path']]; // legacy single path
            }
        }
        ?>
        <?php if (!empty($savedPaths)): ?>
            <div class="flex flex-wrap gap-4">
                <?php foreach ($savedPaths as $idx => $sPath): ?>
                    <?php $imgLabel = getXrayImageLabel($sPath, $idx, $caseDetails['exam_type'] ?? ''); ?>
                    <div onclick="openXrayLightbox('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>')"
                        style="width: 128px; height: 128px; min-width: 128px; min-height: 128px;"
                        class="group relative rounded-2xl overflow-hidden border-2 border-gray-300 hover:border-red-600 bg-black cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md shrink-0 flex items-center justify-center select-none"
                        title="<?= htmlspecialchars($imgLabel) ?> — Click to view fullscreen">
                        <img src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>" 
                             alt="<?= htmlspecialchars($imgLabel) ?>"
                             class="w-full h-full object-contain opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-200">
                        
                        <!-- Center Expand Icon on Hover -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
                            <div class="w-10 h-10 rounded-xl bg-black/60 backdrop-blur-xs border border-white/20 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-200 shadow-lg">
                                <i data-lucide="maximize-2" class="w-5 h-5 text-white stroke-[2.5]"></i>
                            </div>
                        </div>

                        <!-- Bottom Label -->
                        <div class="absolute bottom-0 left-0 right-0 bg-black/75 text-[10px] font-bold text-white py-1 px-1.5 text-center uppercase tracking-wider z-10 pointer-events-none truncate" title="<?= htmlspecialchars($imgLabel) ?>">
                            <?= htmlspecialchars($imgLabel) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 flex flex-col items-center justify-center text-center flex-1 min-h-[200px]">
                <div class="w-14 h-14 bg-white border border-gray-200 rounded-full flex items-center justify-center mb-4 shadow-sm">
                    <i data-lucide="image-off" class="h-6 w-6 text-gray-400"></i>
                </div>
                <h4 class="text-sm font-semibold text-gray-700 mb-1">No Images Uploaded</h4>
                <p class="text-xs text-gray-500 max-w-[280px]">No diagnostic X-ray images have been uploaded for this case yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 flex gap-4 shrink-0">
        <?php if ($isReportReady): ?>
            <a href="javascript:void(0)"
                onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?page=print-report&id=<?= $caseId ?>', 'Yes, Print', true, event)"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition shadow-sm">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Print Result
            </a>
        <?php else: ?>
            <button type="button" disabled title="Print Result (Available after Radiologist submits report)"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-5 py-2.5 text-sm font-semibold text-gray-400 cursor-not-allowed shadow-sm">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Print Result
            </button>
        <?php endif; ?>
    </div>
</div>

    <!-- Radiologist Report Findings Card -->
    <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col justify-between h-full">
        <div class="mb-4">
            <div class="flex items-center gap-2">
                <i data-lucide="file-text" class="h-5 w-5 <?= $isReportReady ? 'text-red-500' : 'text-gray-400' ?>"></i>
                <h3 class="text-lg font-semibold <?= $isReportReady ? 'text-gray-800' : 'text-gray-700' ?>">Radiologist Report Findings</h3>
            </div>
            <p class="text-xs text-gray-500 mt-1">Official radiological interpretation and clinical impression</p>
        </div>
        
        <?php if ($isReportReady): ?>
        
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-4">
            <?php
            $findingsRaw = trim($caseDetails['findings'] ?? '');
            $impressionRaw = trim($caseDetails['impression'] ?? '');
            $isMultiExam = false;
            $parsedFindings = [];

            if (!empty($findingsRaw) && (str_starts_with($findingsRaw, '{') || str_starts_with($findingsRaw, '['))) {
                $decoded = json_decode($findingsRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $isMultiExam = true;
                    $parsedFindings = $decoded;
                }
            }
            ?>

            <?php if ($isMultiExam): ?>
                <?php foreach ($parsedFindings as $examName => $reportData): ?>
                    <div class="mb-4 last:mb-0 border-b border-gray-200 pb-3 last:border-0 last:pb-0">
                        <h5 class="text-xs font-bold text-red-600 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                            <?= htmlspecialchars($examName) ?>
                        </h5>
                        <div class="space-y-3 pl-3">
                            <div>
                                <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Findings</span>
                                <p class="text-sm text-gray-855 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($reportData['findings'] ?? '—') ?></p>
                            </div>
                            <?php if (!empty($reportData['impression'])): ?>
                            <div>
                                <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Impression</span>
                                <p class="text-sm text-gray-855 whitespace-pre-wrap leading-relaxed bg-white border border-gray-100 rounded-lg p-2.5 shadow-sm"><?= htmlspecialchars($reportData['impression']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="space-y-3">
                    <div>
                        <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Findings</span>
                        <div class="text-sm text-gray-855 whitespace-pre-wrap leading-relaxed bg-white border border-gray-150 rounded-lg p-3 shadow-sm"><?= htmlspecialchars($findingsRaw ?: '—') ?></div>
                    </div>
                    <?php if (!empty($impressionRaw)): ?>
                    <div>
                        <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Impression</span>
                        <div class="text-sm text-gray-855 whitespace-pre-wrap leading-relaxed bg-red-50/50 border border-red-100 rounded-lg p-3 shadow-sm"><?= htmlspecialchars($impressionRaw) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <!-- Waiting for Report Empty State -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 flex flex-col items-center justify-center text-center flex-1 min-h-[200px]">
                <div class="w-14 h-14 bg-white border border-gray-200 rounded-full flex items-center justify-center mb-4 shadow-sm">
                    <i data-lucide="clock" class="h-6 w-6 text-gray-400"></i>
                </div>
                <h4 class="text-sm font-semibold text-gray-700 mb-1">Waiting for Report</h4>
                <p class="text-xs text-gray-500 max-w-[280px]">The radiologist has not yet submitted the findings and impression for this case.</p>
            </div>
        <?php endif; ?>
    </div>
</div> <!-- End of Grid -->