<?php
/**
 * Patient Details View for Radiologist
 * Backend logic handled by PatientDetailsController.php
 */
if (isset($caseNotFound) && $caseNotFound) {
    echo "<div class='p-6 mt-10 text-center text-red-600 bg-red-50 rounded-lg'>Case not found or invalid ID.</div>";
    return; // Stop rendering the view
}
?>

<!-- Header -->
<div class="flex items-center gap-4">
    <a href="javascript:history.back()" class="flex w-10 h-10 items-center justify-center rounded-xl bg-white border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors mt-1">
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
    <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm">
        <div class="mb-3 flex items-center gap-2">
            <i data-lucide="user-check" class="h-5 w-5 text-green-600"></i>
            <h3 class="text-lg font-semibold text-gray-800">Patient Verification</h3>
        </div>
        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4">
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
                        class="font-bold text-gray-900"><?= htmlspecialchars($caseDetails['first_name'] . ' ' . $caseDetails['last_name']) ?></span>
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
    <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm">
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
                if ($caseDetails['priority'] === 'STAT') {
                    $pClasses = 'border-red-400 bg-red-50 text-red-700';
                } elseif ($caseDetails['priority'] === 'Urgent') {
                    $pClasses = 'border-yellow-400 bg-yellow-50 text-yellow-700';
                } else {
                    $pClasses = 'border-blue-400 bg-blue-50 text-blue-700';
                }
                ?>
                <span class="inline-flex items-center rounded-full border <?= $pClasses ?> px-2.5 py-1 text-xs font-semibold">
                    <?= htmlspecialchars($caseDetails['priority']) ?>
                </span>
            </div>
            <div class="pt-1">
                <span class="block text-gray-600 text-sm font-medium mb-1.5">Status</span>
                <?php
                if ($caseDetails['status'] === 'Completed')
                    $sBadge = 'border border-green-400 bg-green-50 text-green-700';
                elseif ($caseDetails['status'] === 'Under Reading')
                    $sBadge = 'border border-blue-400 bg-blue-50 text-blue-700';
                elseif ($caseDetails['status'] === 'Report Ready')
                    $sBadge = 'border border-indigo-400 bg-indigo-50 text-indigo-700';
                else
                    $sBadge = 'border border-yellow-400 bg-yellow-50 text-yellow-700';
                ?>
                <span class="inline-block font-bold text-xs px-3 py-1.5 rounded-full <?= $sBadge ?>">
                    <?= htmlspecialchars($caseDetails['status']) ?>
                </span>
            </div>
        </div>
    </div>


</div>

<!-- Image Archive -->
<div class="mt-8 rounded-xl border border-gray-300 bg-white p-6 shadow-sm">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
        <h3 class="text-lg font-semibold text-gray-800">Diagnostic Image Archive</h3>
    </div>
    <p class="text-xs text-gray-500 mb-5">Archived X-ray images and diagnostic files</p>

    <div id="file-preview-area">
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
            <p style="color:#9ca3af;font-size:0.875rem;font-style:italic;">No images uploaded yet.</p>
        <?php endif; ?>
    </div>
</div>
