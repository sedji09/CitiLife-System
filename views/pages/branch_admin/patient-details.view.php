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
    <a href="?role=branch_admin&page=branch-xray-cases" class="text-gray-500 hover:text-gray-900 transition mt-1">
        <i data-lucide="arrow-left" class="w-5 h-5"></i>
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
                $pColor = 'blue';
                if ($caseDetails['priority'] === 'Emergency') $pColor = 'red';
                elseif ($caseDetails['priority'] === 'Urgent') $pColor = 'yellow';
                ?>
                <span class="inline-flex items-center rounded-full border border-<?= $pColor ?>-200 bg-<?= $pColor ?>-50/50 px-2.5 py-1 text-xs font-semibold text-<?= $pColor ?>-700">
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
            <div class="flex flex-wrap gap-3">
                <?php foreach ($savedPaths as $idx => $sPath): ?>
                    <div class="group relative w-32 h-32 rounded-lg overflow-hidden border border-gray-200 bg-black cursor-pointer hover:border-red-400 transition-all shadow-sm">
                        <img src="/<?= PROJECT_DIR ?>/<?= htmlspecialchars($sPath) ?>" 
                             alt="X-ray <?= $idx + 1 ?>"
                             class="w-full h-full object-contain opacity-90 group-hover:opacity-100 transition-opacity">
                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-[10px] font-bold text-white py-1 text-center uppercase tracking-tighter">
                            IMG <?= $idx + 1 ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#9ca3af;font-size:0.875rem;font-style:italic;">No images uploaded yet.</p>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <?php $isReportReady = in_array($caseDetails['status'], ['Report Ready', 'Completed']); ?>
    <div class="mt-8 flex gap-4">
        <?php if ($isReportReady): ?>
            <a href="/<?= PROJECT_DIR ?>/app/views/pages/radtech/print-report.php?id=<?= $caseId ?>" target="_blank"
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