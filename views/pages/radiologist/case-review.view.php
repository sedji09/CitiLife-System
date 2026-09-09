<?php
/**
 * Case Review View (Radiologist)
 * Backend logic handled by CaseReviewController.php
 */
if (isset($caseNotFound) && $caseNotFound) {
    echo "<div class='p-6 mt-10 text-center text-red-600 bg-red-50 rounded-lg'>Case not found or invalid ID.</div>";
    exit;
}
?>

<?php
require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';
$disputeMdl = new \ResultDisputeModel($pdo);
$activeDispute = $disputeMdl->getActiveDisputeByCase($caseDetails['id']);

$backPage = $_GET['back_to'] ?? 'worklist';
$backId = $_GET['back_id'] ?? '';
$backUrl = "/" . PROJECT_DIR . "/index.php?role=radiologist&page=" . urlencode($backPage);
if ($backId) {
    $backUrl .= "&id=" . urlencode($backId);
}
if ($activeDispute && $backPage === 'worklist') {
    $backUrl .= "&tab=disputes";
}
?>



<!-- Title row -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
    <div class="flex items-center gap-4">
        <a href="<?= htmlspecialchars($backUrl) ?>" id="back-to-worklist-btn" title="Back"
            class="flex w-10 h-10 items-center justify-center rounded-xl bg-white border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors mt-1">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 tracking-tight">
                Case Review
            </h2>
            <p class="text-gray-500 text-sm mt-0.5">Review patient case and submit diagnostic findings</p>
        </div>
    </div>
    <div class="flex items-center flex-wrap gap-1.5">
        <?php
        $pColor = match ($caseDetails['priority']) { 'STAT' => 'red', 'Urgent' => 'yellow', 'Priority' => 'orange', default => 'blue'};
        $sColor = ($isSubmitted || $caseDetails['status'] === 'Report Ready') ? 'indigo' : ($caseDetails['status'] === 'Completed' ? 'green' : ($caseDetails['status'] === 'Under Reading' ? 'blue' : 'yellow'));
        $statusDisplay = $isSubmitted ? 'Report Ready' : $caseDetails['status'];
        ?>
        <span
            class="inline-flex items-center rounded-full border border-<?= $pColor ?>-400 bg-<?= $pColor ?>-50 px-3 py-1 text-xs font-bold text-<?= $pColor ?>-700 shadow-sm"><?= htmlspecialchars($caseDetails['priority']) ?></span>
        <span
            class="inline-flex items-center rounded-full border border-<?= $sColor ?>-400 bg-<?= $sColor ?>-50 px-3 py-1 text-xs font-bold text-<?= $sColor ?>-700 shadow-sm"><?= htmlspecialchars($statusDisplay) ?></span>
        <?php if (count($examTypes) > 1): ?>
            <span
                class="inline-flex items-center rounded-full border border-indigo-300 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 shadow-sm">
                <i data-lucide="layers" class="w-3 h-3 mr-1"></i><?= count($examTypes) ?> Exams
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if ($successMsg): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: <?= json_encode($successMsg) ?>,
                    showConfirmButton: false,
                    timer: 2500,
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl' }
                });
            } else if (typeof toast === 'function') {
                toast(<?= json_encode($successMsg) ?>, 'success');
            }
        });
    </script>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mb-5 rounded-xl bg-red-50 border border-red-300 p-4 flex items-center gap-3 shadow-sm">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
        <p class="text-sm text-red-700 font-medium"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<!-- ══ Row 1: Info Cards (Patient + RadTech) ══ -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">

    <!-- Patient Info -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
        <div class="flex items-center gap-2 mb-3 border-b border-gray-100 pb-2">
            <i data-lucide="user" class="w-4 h-4 text-red-500"></i>
            <h3 class="font-bold text-gray-800 text-sm">Patient Information</h3>
        </div>
        <div class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm pl-1">
            <div>
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Name</p>
                <p class="font-semibold text-gray-900 text-xs">
                    <?= $fullName ?>
                </p>
            </div>
            <div>
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Case No.</p>
                <p class="font-semibold text-gray-900 text-xs"><?= htmlspecialchars($caseDetails['case_number']) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Patient No.</p>
                <p class="font-semibold text-gray-900 text-xs">
                    <?= htmlspecialchars($caseDetails['patient_number'] ?? 'N/A') ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Age / Sex</p>
                <p class="font-semibold text-gray-900 text-xs"><?= htmlspecialchars($caseDetails['age']) ?> /
                    <?= htmlspecialchars(ucfirst($caseDetails['sex'])) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Branch</p>
                <p class="font-semibold text-gray-900 text-xs"><?= htmlspecialchars($caseDetails['branch_name']) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Date</p>
                <p class="font-semibold text-gray-900 text-xs">
                    <?= date('M d, Y', strtotime($caseDetails['created_at'])) ?></p>
            </div>

        </div>
    </div>

    <!-- RadTech Information -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
        <div class="flex items-center gap-2 mb-3 border-b border-gray-100 pb-2">
            <i data-lucide="activity" class="w-4 h-4 text-red-500"></i>
            <h3 class="font-bold text-gray-800 text-sm">RadTech Information</h3>
        </div>
        <div class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm pl-1">
            <div class="col-span-2">
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Radiologic Technologist</p>
                <?php
                $rtNameRaw = !empty($caseDetails['radtech_name']) ? $caseDetails['radtech_name'] : ('RT ' . $caseDetails['branch_name']);
                $rtDisplay = ucwords(str_replace('.', ' ', $rtNameRaw));
                if (!empty($caseDetails['radtech_title'])) {
                    $rtDisplay .= ', ' . $caseDetails['radtech_title'];
                }
                ?>
                <p class="font-semibold text-gray-900 text-xs"><?= htmlspecialchars($rtDisplay) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Exam Completed</p>
                <p class="font-semibold text-gray-900 text-xs">
                    <?= date('M d, Y', strtotime($caseDetails['created_at'])) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-[10px] uppercase tracking-wide">Upload Time</p>
                <p class="font-semibold text-gray-900 text-xs">
                    <?= date('h:i A', strtotime($caseDetails['created_at'])) ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($caseDetails['re_edit_reason'])): ?>
    <div class="mb-5 rounded-xl bg-amber-50 border border-amber-200 p-4 flex items-start gap-3 shadow-xs">
        <div class="h-8 w-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 text-amber-700 mt-0.5">
            <i data-lucide="message-square" class="w-4 h-4 stroke-[2]"></i>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-amber-900 uppercase tracking-wider">RadTech Notes</span>
            </div>
            <p class="text-sm text-amber-900 mt-1 font-normal leading-relaxed">
                <?= nl2br(htmlspecialchars($caseDetails['re_edit_reason'])) ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<!-- ══ Row 2: Viewer + Report Editor ══ -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start">

    <!-- ══ LEFT: Image Viewer + History ══ -->
    <div class="space-y-4">

        <!-- Image Viewer -->
        <!-- Enhanced Image Viewer -->
        <?php
        if (!function_exists('getXrayImageLabel')) {
            function getXrayImageLabel($sPath, $idx = 0, $examType = '') {
                $baseName = pathinfo($sPath, PATHINFO_FILENAME);

                // 1. If saved with original file name: case_{caseId}_{time}_{idx}_{originalName}
                if (preg_match('/^case_\d+_\d+_\d+_(.+)$/', $baseName, $m)) {
                    $name = trim($m[1]);
                    if (!empty($name) && !preg_match('/^image[_\s]?\d+$/i', $name)) {
                        return $name;
                    }
                }

                // 2. If corresponding exam from exam_type exists (e.g. "Chest PA", "Chest AP, Chest PA")
                if (!empty($examType)) {
                    $exams = array_values(array_filter(array_map('trim', explode(',', $examType))));
                    if (isset($exams[$idx]) && $exams[$idx] !== '') {
                        return $exams[$idx];
                    }
                }

                // 3. If file has a descriptive name (not starting with case_ or random hash)
                if (!preg_match('/^case_\d+/i', $baseName) && strlen($baseName) > 2) {
                    return str_replace(['_', '-'], ' ', $baseName);
                }

                // 4. Default fallback: exam_type if single exam, else IMG {idx + 1}
                if (!empty($examType) && !str_contains($examType, ',')) {
                    return trim($examType);
                }

                return 'IMG ' . ($idx + 1);
            }
        }

        $imageLabels = [];
        foreach ($imagePaths as $i => $p) {
            $imageLabels[$i] = getXrayImageLabel($p, $i, $caseDetails['exam_type'] ?? '');
        }
        ?>
        <div id="dicom-viewer"
            class="bg-[#0a0a0a] border border-gray-200 rounded-2xl overflow-hidden shadow-2xl flex flex-col h-[480px] relative transition-all w-full">

            <!-- Classic Integrated Header Toolbar -->
            <div class="bg-red-600 px-5 h-14 flex justify-between items-center text-white z-20 w-full select-none shadow-lg"
                id="dicom-toolbar">

                <div class="flex items-center gap-4">
                    <div
                        class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/20 shadow-inner shrink-0">
                        <i data-lucide="scan-line" class="w-5 h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-black text-xs uppercase tracking-widest leading-none">X-ray Viewer</span>
                        <div class="flex items-center gap-1.5 mt-1">
                            <?php if (count($imagePaths) > 1): ?>
                                <span id="img-counter"
                                    class="text-[9px] font-bold text-white/75 tracking-tighter uppercase shrink-0">
                                    1 / <?= count($imagePaths) ?>
                                </span>
                                <span class="text-white/40 text-[9px] shrink-0">•</span>
                            <?php endif; ?>
                            <span id="img-exam-name"
                                class="text-[10px] font-black text-white bg-black/30 px-2 py-0.5 rounded uppercase tracking-wider truncate max-w-[200px]"
                                title="<?= htmlspecialchars($imageLabels[0] ?? '') ?>">
                                <?= htmlspecialchars($imageLabels[0] ?? ($caseDetails['exam_type'] ?? 'X-RAY')) ?>
                            </span>
                        </div>
                    </div>
                </div>


                <!-- Zoom Controls -->
                <div
                    class="flex items-center gap-3 bg-black/20 rounded-xl px-3 py-1.5 border border-white/5 shadow-inner">
                    <button id="btn-zoom-out" class="text-white/60 hover:text-white transition-colors" title="Zoom Out">
                        <i data-lucide="minus-circle" class="w-4 h-4"></i>
                    </button>
                    <span id="zoom-level"
                        class="text-[10px] font-black text-white min-w-[35px] text-center tabular-nums">100%</span>
                    <button id="btn-zoom-in" class="text-white/60 hover:text-white transition-colors" title="Zoom In">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Main Viewing Area -->
            <div class="flex-1 flex flex-col items-center justify-center relative bg-[#0a0a0a] overflow-hidden group/viewer"
                id="img-canvas">
                <?php if (!empty($imagePaths)): ?>
                    <?php foreach ($imagePaths as $idx => $path): ?>
                        <?php
                        $imgPath = str_starts_with($path, '/') ? '/' . PROJECT_DIR . $path : '/' . PROJECT_DIR . '/' . $path;
                        $imgPath = str_replace('//', '/', $imgPath);
                        $lbl = $imageLabels[$idx] ?? ('IMG ' . ($idx + 1));
                        ?>
                        <img src="<?= htmlspecialchars($imgPath) ?>"
                            class="dicom-img w-auto h-full max-w-full max-h-full object-contain transition-transform duration-200 select-none <?= $idx > 0 ? 'hidden' : '' ?>"
                            alt="<?= htmlspecialchars($lbl) ?>" data-index="<?= $idx ?>" data-label="<?= htmlspecialchars($lbl) ?>">
                    <?php endforeach; ?>

                    <!-- Floating Side Navigation (Fullscreen Only) -->
                    <?php if (count($imagePaths) > 1): ?>
                        <button type="button" id="btn-prev-side"
                            class="hidden absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[30] group"
                            title="Previous Image">
                            <i data-lucide="chevron-left" class="w-7 h-7 group-hover:-translate-x-0.5 transition-transform"></i>
                        </button>
                        <button type="button" id="btn-next-side"
                            class="hidden absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[30] group"
                            title="Next Image">
                            <i data-lucide="chevron-right" class="w-7 h-7 group-hover:translate-x-0.5 transition-transform"></i>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center">
                        <i data-lucide="file-scan" class="w-12 h-12 text-white/20 mb-2 mx-auto"></i>
                        <p class="text-white/40 text-sm font-medium">No image uploaded</p>
                        <p class="text-white/25 text-xs mt-1"><?= htmlspecialchars($examTypeRaw) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Classic Bottom Thumbnails -->
                <?php if (count($imagePaths) > 1): ?>
                    <div id="xray-thumb-strip"
                        class="absolute bottom-4 left-1/2 -translate-x-1/2 h-16 bg-black/40 backdrop-blur-md rounded-2xl flex items-center px-4 gap-3 z-20 border border-white/10 shadow-2xl overflow-x-auto max-w-[90%] scrollbar-hide">
                        <?php foreach ($imagePaths as $index => $path): ?>
                            <?php
                            $imgPath = str_starts_with($path, '/') ? '/' . PROJECT_DIR . $path : '/' . PROJECT_DIR . '/' . $path;
                            $imgPath = str_replace('//', '/', $imgPath);
                            $lbl = $imageLabels[$index] ?? ('IMG ' . ($index + 1));
                            ?>
                            <div class="xray-thumb-item flex-shrink-0 w-10 h-10 rounded-xl border-2 <?= $index === 0 ? 'border-red-500 bg-red-500/10' : 'border-transparent opacity-60' ?> overflow-hidden cursor-pointer transition-all hover:scale-110 hover:opacity-100"
                                data-index="<?= $index ?>" data-url="<?= htmlspecialchars($imgPath) ?>" data-label="<?= htmlspecialchars($lbl) ?>"
                                title="<?= htmlspecialchars($lbl) ?> (<?= $index + 1 ?> / <?= count($imagePaths) ?>)">
                                <img src="<?= htmlspecialchars($imgPath) ?>" class="w-full h-full object-cover">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Expand Button -->
                <button type="button" id="btn-fullscreen-wrapper" title="Toggle Fullscreen"
                    class="absolute bottom-4 left-4 bg-black/60 hover:bg-black/80 text-white/90 hover:text-white p-2.5 rounded-xl cursor-pointer backdrop-blur-md transition-all active:scale-90 border border-white/10 shadow-2xl z-[35] flex items-center justify-center">
                    <span id="fullscreen-icon-wrap"></span>
                </button>
            </div>
        </div>

        <!-- Clinical Information Panel -->
        <?php if (!empty($caseDetails['clinical_information'])): ?>
            <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden p-5 mb-4 relative">
                <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="clipboard-list" class="w-4 h-4 text-red-500"></i>
                    <h3 class="font-bold text-gray-800 text-sm">Clinical Information</h3>
                </div>
                <p class="text-xs text-gray-700 leading-relaxed pl-1">
                    <?= nl2br(htmlspecialchars($caseDetails['clinical_information'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Patient History accordion -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
            <button type="button" id="history-toggle"
                class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-gray-400"></i>
                    <span class="font-bold text-gray-800 text-sm">Patient History</span>
                    <span
                        class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= count($patientHistory) ?></span>
                </div>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform"
                    id="history-chevron"></i>
            </button>
            <div id="history-panel"
                class="hidden border-t border-gray-100 divide-y divide-gray-50 max-h-64 overflow-y-auto">
                <?php if (empty($patientHistory)): ?>
                    <div class="py-6 text-center">
                        <i data-lucide="folder-open" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
                        <p class="text-xs text-gray-400">No previous records across all branches.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($patientHistory as $h): ?>
                        <div class="px-5 py-3 hover:bg-red-50 transition cursor-pointer group"
                            onclick="window.location.href='<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radiologist&page=patient-records-history&id=<?= $h['id'] ?>&back_to=case-review&back_id=<?= $caseId ?>'">
                            <div class="flex justify-between items-start">
                                <p class="text-xs font-bold text-gray-800 group-hover:text-red-600 transition">
                                    <?= htmlspecialchars($h['exam_type']) ?></p>
                                <span
                                    class="text-[10px] bg-gray-100 text-gray-600 rounded px-1.5 py-0.5"><?= htmlspecialchars($h['branch_name']) ?></span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-0.5"><?= htmlspecialchars($h['case_number']) ?> &bull;
                                <?= date('M d, Y', strtotime($h['created_at'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ RIGHT PANEL – Report Editor ════════════════════════════════════════ -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl flex flex-col min-h-[650px] overflow-hidden">

        <div class="bg-red-600 px-5 h-14 flex items-center justify-between border-b border-red-700 shadow-lg">
            <div class="flex items-center gap-3">
                <div
                    class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/20 shadow-inner">
                    <i data-lucide="file-heart" class="w-5 h-5 text-white"></i>
                </div>
                <span class="font-black text-xs uppercase tracking-widest text-white">Radiologic Evaluation</span>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="text-[9px] font-bold text-white/80 uppercase tracking-tighter bg-black/10 px-2 py-1 rounded border border-white/5">Auto-Saving...</span>
            </div>
        </div>

        <!-- ── Exam Navigator: Prev / Next arrows ── -->
        <div class="border-b border-gray-100 px-4 pt-3 pb-2" id="exam-tab-nav">
                <div class="flex items-center gap-2">
                    <!-- Prev -->
                    <button type="button" id="exam-nav-prev"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition flex-shrink-0 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-gray-400 disabled:hover:border-gray-200"
                        disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <polyline points="15 18 9 12 15 6" />
                        </svg>
                    </button>

                    <!-- Label + counter -->
                    <div class="flex-1 min-w-0 text-center">
                        <p class="text-xs font-black text-gray-900 uppercase tracking-wide truncate leading-tight"
                            id="exam-nav-label"><?= htmlspecialchars($examTypes[0] ?? '') ?></p>
                        <p class="text-[10px] text-gray-400 mt-0.5" id="exam-nav-counter">Exam 1 of <?= count($examTypes) ?>
                        </p>
                    </div>

                    <!-- Next -->
                    <button type="button" id="exam-nav-next"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition flex-shrink-0 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-gray-400 disabled:hover:border-gray-200"
                        <?= count($examTypes) <= 1 ? 'disabled' : '' ?>>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </button>
                </div>

            <?php if (count($examTypes) > 1): ?>
                <!-- Progress dots -->
                <div class="flex items-center justify-center gap-1 mt-2">
                    <?php foreach ($examTypes as $idx => $exam): ?>
                        <span
                            class="progress-dot block rounded-full transition-all duration-200 <?= $idx === 0 ? 'w-4 h-1.5 bg-red-500' : 'w-1.5 h-1.5 bg-gray-200' ?>"
                            data-idx="<?= $idx ?>" title="<?= htmlspecialchars($exam) ?>"></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hidden form -->
        <form id="report-form" method="POST" action="">
            <input type="hidden" name="clinical_information" id="clinical_information_hidden"
                value="<?= htmlspecialchars($caseDetails['clinical_information'] ?? '') ?>">
            <input type="hidden" name="exam_reports" id="exam_reports_hidden" value="">
            <input type="hidden" name="save_draft" id="save_draft_input" disabled>
            <input type="hidden" name="submit_final" id="submit_final_input" disabled>
        </form>

        <!-- Exam panels -->
        <div class="flex-1 flex flex-col" id="exam-panels-container">
            <?php foreach ($examTypes as $idx => $exam): ?>
                <?php
                $savedF = $savedReports[$exam]['findings'] ?? '';
                $savedI = $savedReports[$exam]['impression'] ?? '';
                ?>
                <div class="exam-panel flex-1 flex flex-col px-5 py-5 <?= $idx > 0 ? 'hidden' : '' ?>"
                    data-exam-idx="<?= $idx ?>" data-exam-key="<?= htmlspecialchars($exam) ?>">

                    <!-- Exam header -->
                    <div class="flex items-center gap-3 mb-5">
                        <div
                            class="w-8 h-8 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="file-heart" class="w-4 h-4 text-red-500"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 text-sm leading-tight"><?= htmlspecialchars($exam) ?></h4>
                            <p class="text-[10px] text-gray-400">Exam <?= $idx + 1 ?> of <?= count($examTypes) ?> &bull;
                                Standardized Radiologic Report</p>
                        </div>
                    </div>

                    <div class="space-y-4 flex-1 flex flex-col">
                        <!-- Findings -->
                        <div class="flex flex-col" style="flex: 1.5;">
                            <div class="flex items-center justify-between mb-1.5 ml-1">
                                <label
                                    class="block text-[10px] font-bold text-red-500 uppercase tracking-wider ml-1">Radiographic
                                    Findings</label>
                                <span id="findings-count-<?= $idx ?>" class="text-[10px] text-gray-400">0 words</span>
                            </div>
                            <textarea
                                class="exam-findings flex-1 w-full rounded-xl border border-gray-200 <?= $isCompleted ? 'bg-gray-50 cursor-not-allowed text-gray-600' : 'bg-white focus:ring-2 focus:ring-red-100 focus:border-red-300' ?> px-4 py-3 text-sm text-gray-800 outline-none transition resize-none"
                                placeholder="Enter detailed radiographic findings..." data-exam-idx="<?= $idx ?>"
                                data-exam-key="<?= htmlspecialchars($exam) ?>" <?= $isCompleted ? 'readonly' : '' ?>><?= htmlspecialchars($savedF) ?></textarea>
                        </div>

                        <!-- Impression -->
                        <div class="flex flex-col flex-1 min-h-0">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-400">Diagnostic Impression</label>
                                <span id="impression-count-<?= $idx ?>" class="text-[11px] text-gray-400">0 words</span>
                            </div>

                            <textarea
                                class="exam-impression flex-1 w-full rounded-xl border border-gray-200 <?= $isCompleted ? 'bg-gray-50 cursor-not-allowed text-gray-600' : 'bg-white focus:ring-2 focus:ring-red-100 focus:border-red-300' ?> px-4 py-3 text-sm text-gray-800 outline-none transition resize-none"
                                placeholder="Enter definitive diagnostic impression..." data-exam-idx="<?= $idx ?>"
                                data-exam-key="<?= htmlspecialchars($exam) ?>" <?= $isCompleted ? 'readonly' : '' ?>><?= htmlspecialchars($savedI) ?></textarea>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer Actions -->
        <div class="border-t border-gray-100 px-5 py-4 rounded-b-xl">
            <div id="completed-footer-actions" class="<?= $isCompleted ? '' : 'hidden' ?> w-full flex flex-col gap-2">
                <div
                    class="w-full rounded-xl bg-red-50 border border-red-200 py-3 px-5 flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-red-600 flex-shrink-0"></i>
                    <span class="text-sm font-bold text-red-600">Report Submitted &mdash; Ready for Release</span>
                </div>
            </div>

            <div id="editable-footer-actions" class="<?= $isCompleted ? 'hidden' : '' ?> w-full flex flex-col gap-2">
                <button type="button" id="btn-submit-final"
                    class="w-full inline-flex items-center justify-center gap-2 py-3 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm focus:ring-4 focus:ring-red-200 focus:outline-none transition-all">
                    <i data-lucide="send" class="w-4 h-4"></i> Submit as Final
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const examKeys = <?= json_encode($examTypes) ?>;
        const savedData = <?= json_encode($savedReports) ?>;
        let isCompleted = <?= $isCompleted ? 'true' : 'false' ?>;
        const STORAGE_KEY = `rad_case_draft_<?= $caseId ?>`;

        const store = {}; // { examKey: { findings, impression } }
        examKeys.forEach(k => {
            store[k] = {
                findings: (savedData[k] && savedData[k].findings) ? savedData[k].findings : '',
                impression: (savedData[k] && savedData[k].impression) ? savedData[k].impression : '',
            };
        });

        // Load from local storage if available and not completed
        if (!isCompleted) {
            try {
                const cached = localStorage.getItem(STORAGE_KEY);
                if (cached) {
                    const draft = JSON.parse(cached);
                    Object.keys(draft).forEach(k => {
                        if (store[k]) {
                            // Only overwrite if the cached version has content
                            if (draft[k].findings) store[k].findings = draft[k].findings;
                            if (draft[k].impression) store[k].impression = draft[k].impression;
                        }
                    });
                }
            } catch (e) { console.error("Failed to load draft:", e); }
        }

        // ── DOM refs ──────────────────────────────────────────────────────────────
        const examPanels = document.querySelectorAll('.exam-panel');
        const progressDots = document.querySelectorAll('.progress-dot');
        const progressLabel = document.getElementById('progress-label');
        const hiddenInput = document.getElementById('exam_reports_hidden');
        const clinicalHid = document.getElementById('clinical_information_hidden');
        const navPrevBtn = document.getElementById('exam-nav-prev');
        const navNextBtn = document.getElementById('exam-nav-next');
        const navLabel = document.getElementById('exam-nav-label');
        const navCounter = document.getElementById('exam-nav-counter');

        let activeIdx = 0;

        // ── Exam navigator (prev/next) ────────────────────────────────────────────
        function switchTab(newIdx) {
            if (newIdx < 0 || newIdx >= examKeys.length) return;
            activeIdx = newIdx;

            // Show/hide panels
            examPanels.forEach((p, i) => p.classList.toggle('hidden', i !== newIdx));

            // Update nav label & counter
            if (navLabel) navLabel.textContent = examKeys[newIdx];
            if (navCounter) navCounter.textContent = `Exam ${newIdx + 1} of ${examKeys.length}`;

            // Arrow disabled states
            if (navPrevBtn) navPrevBtn.disabled = newIdx <= 0;
            if (navNextBtn) navNextBtn.disabled = newIdx >= examKeys.length - 1;

            // Progress dots — active dot is wider pill; filled dots turn green
            progressDots.forEach((dot, i) => {
                const isActive = i === newIdx;
                const done = !!(store[examKeys[i]] && (store[examKeys[i]].findings || store[examKeys[i]].impression));
                dot.style.width = isActive ? '1rem' : '0.375rem';
                dot.style.height = '0.375rem';
                dot.style.backgroundColor = isActive ? '#ef4444' : (done ? '#4ade80' : '#e5e7eb');
            });
        }

        // Wire arrow buttons
        navPrevBtn?.addEventListener('click', () => switchTab(activeIdx - 1));
        navNextBtn?.addEventListener('click', () => switchTab(activeIdx + 1));

        // ── Word counter ──────────────────────────────────────────────────────────
        function wordCount(str) { return str.trim() ? str.trim().split(/\s+/).length : 0; }

        function updateCount(textarea) {
            const idx = +textarea.dataset.examIdx;
            const type = textarea.classList.contains('exam-findings') ? 'findings' : 'impression';
            const countEl = document.getElementById(`${type}-count-${idx}`);
            if (countEl) countEl.textContent = wordCount(textarea.value) + ' words';
        }

        // ── Store sync & draft badge ──────────────────────────────────────────────
        let draftTimers = {};

        function syncStore(textarea) {
            const key = textarea.dataset.examKey;
            const idx = +textarea.dataset.examIdx;
            const type = textarea.classList.contains('exam-findings') ? 'findings' : 'impression';
            if (!store[key]) store[key] = { findings: '', impression: '' };
            store[key][type] = textarea.value;

            // Flash draft badge
            const badge = document.getElementById(`draft-badge-${idx}`);
            if (badge) {
                badge.style.opacity = '1';
                clearTimeout(draftTimers[idx]);
                draftTimers[idx] = setTimeout(() => badge.style.opacity = '0', 2500);
            }

            // Save to local storage
            if (!isCompleted) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
            }

            syncHiddenInput();
            updateProgress();
        }

        function syncHiddenInput() {
            if (hiddenInput) hiddenInput.value = JSON.stringify(store);
        }

        // ── Progress tracking ─────────────────────────────────────────────────────
        function updateProgress() {
            let filled = 0;
            examKeys.forEach((k, i) => {
                const done = !!(store[k] && (store[k].findings || store[k].impression));
                if (done) filled++;
            });
            // Re-render dots via switchTab so active/done colours stay in sync
            switchTab(activeIdx);
            if (progressLabel) progressLabel.textContent = `${filled} of ${examKeys.length} completed`;
        }

        // ── Initialize textareas ──────────────────────────────────────────────────
        document.querySelectorAll('.exam-findings, .exam-impression').forEach(ta => {
            const key = ta.dataset.examKey;
            const type = ta.classList.contains('exam-findings') ? 'findings' : 'impression';

            // If we have data in store (possibly from localStorage), populate it
            if (!isCompleted && store[key] && store[key][type]) {
                ta.value = store[key][type];
            }

            updateCount(ta);
            if (!isCompleted) {
                ta.addEventListener('input', () => { syncStore(ta); updateCount(ta); });
            }
        });

        // Clear local storage if just submitted successfully
        <?php if ($successMsg): ?>
            localStorage.removeItem(STORAGE_KEY);
        <?php endif; ?>

        // Initialize hidden input
        syncHiddenInput();
        updateProgress();

        // ── Clinical info sync ────────────────────────────────────────────────────
        // (clinical_information_hidden carries the value from PHP; no display input)

        const clinicalPanel = null; // removed
        document.getElementById('btn-clinical')?.addEventListener('click', () => { });

        // ── Submit Logic ──────────────────────────────────────────────────────────

        function validateReport() {
            let firstInvalid = -1;
            let firstInvalidField = null;

            examKeys.forEach((key, i) => {
                if (firstInvalid !== -1) return; // stop at first error
                const f = (store[key].findings || '').trim();
                const im = (store[key].impression || '').trim();
                if (!f || !im) {
                    firstInvalid = i;
                    firstInvalidField = !f ? 'findings' : 'impression';
                }
            });

            if (firstInvalid !== -1) {
                // Switch to the offending exam tab
                switchTab(firstInvalid);

                // Highlight and trigger native browser validation bubble
                const panel = document.querySelectorAll('.exam-panel')[firstInvalid];
                const field = panel?.querySelector(firstInvalidField === 'findings' ? '.exam-findings' : '.exam-impression');
                if (field) {
                    field.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                    field.setCustomValidity('Please fill out this field before finalizing.');
                    field.reportValidity();
                    field.addEventListener('input', () => {
                        field.setCustomValidity('');
                        field.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                    }, { once: true });
                }
                return false;
            }
            return true;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function showReviewModal(startSlide = 0) {
            // Build rich confirmation modal content
            const pName = <?= json_encode($fullName) ?>;
            const cNum = <?= json_encode($caseDetails['case_number']) ?>;

            const dicomImgs = document.querySelectorAll('.dicom-img');
            const imgList = [];
            dicomImgs.forEach(img => {
                if (img.src) imgList.push(img.src);
            });

            let slidesHtml = '';
            let dotsHtml = '';
            const totalExams = examPanels.length;

            examPanels.forEach((panel, i) => {
                const examKey = panel.dataset.examKey || ('Exam ' + (i + 1));
                const findings = panel.querySelector('.exam-findings')?.value.trim() || '—';
                const impression = panel.querySelector('.exam-impression')?.value.trim() || '—';

                // Match image to exam by index or fallback
                const currentImg = imgList[i] || imgList[0] || '';

                slidesHtml += `
                <div class="preview-carousel-slide" data-slide-idx="${i}" data-exam-name="${escapeHtml(examKey)}" style="${i === startSlide ? 'display: flex;' : 'display: none;'} flex-direction: row; gap: 14px; align-items: stretch; width: 100%; box-sizing: border-box;">
                    <!-- Left: Image Preview Box -->
                    <div style="flex: 0 0 44%; width: 44%; max-width: 44%; min-width: 44%; height: 350px; background: #0a0a0a; border: 1px solid #374151; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; box-sizing: border-box; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);">
                        ${currentImg ? `
                            <img id="preview-main-img-${i}" src="${escapeHtml(currentImg)}" alt="X-ray" style="width: 100%; height: 100%; object-fit: contain; padding: 6px; user-select: none;">
                        ` : `
                            <div style="text-align: center; padding: 16px; color: #9ca3af;">
                                <svg style="width: 40px; height: 40px; margin: 0 auto 8px auto; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p style="font-size: 12px; margin: 0;">No X-ray image available</p>
                            </div>
                        `}
                    </div>

                    <!-- Right: Findings & Impression Column -->
                    <div style="flex: 1 1 0%; min-width: 0; height: 350px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box; text-align: left; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <!-- Exam Header -->
                        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 8px; margin-bottom: 6px; border-bottom: 1px solid #f1f5f9;">
                            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626; flex-shrink: 0;"></span>
                                <h4 style="font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.03em; color: #dc2626; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(examKey)}">${escapeHtml(examKey)}</h4>
                            </div>
                            <span style="background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 400; padding: 2px 8px; border-radius: 6px; white-space: nowrap; flex-shrink: 0;">Exam ${i + 1} of ${totalExams}</span>
                        </div>

                        <!-- Findings -->
                        <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin-bottom: 8px;">
                            <span style="font-size: 10.5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin-bottom: 4px;">Radiographic Findings</span>
                            <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 12px; font-weight: 400; color: #334155; line-height: 1.6; overflow-y: auto; white-space: pre-wrap; word-break: break-word; font-family: inherit;">${escapeHtml(findings)}</div>
                        </div>

                        <!-- Impression -->
                        <div style="display: flex; flex-direction: column; min-height: 0;">
                            <span style="font-size: 10.5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin-bottom: 4px;">Impression</span>
                            <div style="max-height: 90px; background: #fff5f5; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 12px; font-size: 12px; font-weight: 400; color: #334155; line-height: 1.55; overflow-y: auto; white-space: pre-wrap; word-break: break-word; font-family: inherit;">${escapeHtml(impression)}</div>
                        </div>
                    </div>
                </div>
            `;

                dotsHtml += `
                <button type="button" class="preview-carousel-dot" data-slide-idx="${i}" title="${escapeHtml(examKey)}" style="width: ${i === startSlide ? '24px' : '8px'}; height: 8px; border-radius: 9999px; background: ${i === startSlide ? '#dc2626' : '#d1d5db'}; border: none; padding: 0; cursor: pointer; transition: all 0.2s;"></button>
            `;
            });

            const modalHtml = `
            <div style="width: 100%; box-sizing: border-box; text-align: left; user-select: none; font-family: inherit;">
                <!-- Header: Patient summary -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; font-family: inherit;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; font-weight: 400; color: #64748b; letter-spacing: 0.04em; font-family: inherit;">Patient Name</span>
                        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 2px 0 0 0; line-height: 1.25; font-family: inherit;">${escapeHtml(pName)}</h3>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="padding: 3px 10px; border-radius: 9999px; font-size: 11.5px; font-weight: 500; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-family: inherit;">${escapeHtml(cNum)}</span>
                    </div>
                </div>

                <!-- Carousel Body with Nav Arrows -->
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; margin: 6px 0; box-sizing: border-box;">
                    <!-- Left Arrow Button -->
                    <button type="button" id="preview-carousel-prev"
                        style="width: 44px; height: 44px; min-width: 44px; border-radius: 50%; background: #ffffff; border: 1px solid #d1d5db; color: #374151; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.1); flex-shrink: 0;"
                        title="Previous Exam">
                        <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    <!-- Slides Wrapper -->
                    <div style="flex: 1; min-width: 0; overflow: hidden; padding: 2px 4px; box-sizing: border-box;" id="preview-carousel-slides">
                        ${slidesHtml}
                    </div>

                    <!-- Right Arrow Button -->
                    <button type="button" id="preview-carousel-next"
                        style="width: 44px; height: 44px; min-width: 44px; border-radius: 50%; background: #ffffff; border: 1px solid #d1d5db; color: #374151; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.1); flex-shrink: 0;"
                        title="Next Exam">
                        <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <!-- Bottom Indicator Dots & Counter (for multiple exams) -->
                <div id="preview-bottom-nav" style="display: flex; align-items: center; justify-content: space-between; padding-top: 10px; margin-top: 8px; border-top: 1px solid #f3f4f6;">
                    <div style="display: flex; align-items: center; gap: 6px;" id="preview-dots-container">
                        ${dotsHtml}
                    </div>
                    <div style="font-size: 11px; font-weight: 400; color: #6b7280; font-family: inherit;" id="preview-exam-indicator">
                        Exam ${startSlide + 1} of ${totalExams}
                    </div>
                </div>

                <!-- Notice -->
                <div style="margin-top: 8px; border-radius: 8px; background: #fffbeb; border: 1px solid #fde68a; padding: 6px 12px; display: flex; align-items: center; gap: 8px; font-size: 11px; color: #92400e; font-family: inherit;">
                    <span style="font-weight: 500; color: #b45309;">Notice:</span> Once submitted as final, report editing requires RadTech authorization to revert to draft.
                </div>
            </div>
        `;

            let activeSlide = startSlide;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '<div style="font-size: 18px; font-weight: 600; color: #111827; text-align: left; font-family: inherit;">Review Final Report</div>',
                    html: modalHtml,
                    width: '920px',
                    showCancelButton: true,
                    confirmButtonText: 'Submit as final',
                    cancelButtonText: 'Review & Edit',
                    reverseButtons: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    buttonsStyling: true,
                    customClass: {
                        popup: 'rounded-3xl p-6 font-sans',
                        htmlContainer: 'm-0 p-0 text-left overflow-visible font-sans',
                        actions: 'w-full !flex !justify-end !gap-3 font-sans',
                        confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-medium shadow-sm font-sans',
                        cancelButton: 'rounded-xl px-5 py-2.5 text-sm font-normal font-sans'
                    },
                    didOpen: () => {
                        const actions = Swal.getActions ? Swal.getActions() : document.querySelector('.swal2-actions');
                        if (actions) {
                            actions.style.justifyContent = 'flex-end';
                            actions.style.width = '100%';
                            actions.style.margin = '16px 0 0 0';
                            actions.style.padding = '0';
                            actions.style.gap = '10px';
                        }

                        const slides = document.querySelectorAll('.preview-carousel-slide');
                        const dots = document.querySelectorAll('.preview-carousel-dot');
                        const prevBtn = document.getElementById('preview-carousel-prev');
                        const nextBtn = document.getElementById('preview-carousel-next');
                        const indicator = document.getElementById('preview-exam-indicator');
                        let currentIdx = startSlide;
                        const total = slides.length;

                        function updateCarousel(idx) {
                            currentIdx = idx;
                            activeSlide = idx;
                            slides.forEach((s, i) => {
                                s.style.display = (i === currentIdx ? 'flex' : 'none');
                            });
                            dots.forEach((d, i) => {
                                if (i === currentIdx) {
                                    d.style.width = '24px';
                                    d.style.background = '#dc2626';
                                } else {
                                    d.style.width = '8px';
                                    d.style.background = '#d1d5db';
                                }
                            });

                            if (indicator) {
                                const currentExamName = slides[currentIdx]?.dataset.examName || '';
                                indicator.textContent = `Exam ${currentIdx + 1} of ${total}${currentExamName ? ': ' + currentExamName : ''}`;
                            }

                            if (prevBtn) {
                                prevBtn.disabled = (currentIdx === 0);
                                prevBtn.style.opacity = (currentIdx === 0 ? '0.3' : '1');
                                prevBtn.style.pointerEvents = (currentIdx === 0 ? 'none' : 'auto');
                            }
                            if (nextBtn) {
                                nextBtn.disabled = (currentIdx === total - 1);
                                nextBtn.style.opacity = (currentIdx === total - 1 ? '0.3' : '1');
                                nextBtn.style.pointerEvents = (currentIdx === total - 1 ? 'none' : 'auto');
                            }
                        }

                        if (total <= 1) {
                            if (prevBtn) prevBtn.style.display = 'none';
                            if (nextBtn) nextBtn.style.display = 'none';
                            const bottomNav = document.getElementById('preview-bottom-nav');
                            if (bottomNav) bottomNav.style.display = 'none';
                        } else {
                            prevBtn?.addEventListener('click', () => {
                                if (currentIdx > 0) updateCarousel(currentIdx - 1);
                            });
                            nextBtn?.addEventListener('click', () => {
                                if (currentIdx < total - 1) updateCarousel(currentIdx + 1);
                            });
                            dots.forEach((dot, i) => {
                                dot.addEventListener('click', () => updateCarousel(i));
                            });
                        }

                        updateCarousel(startSlide);
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const returnSlide = activeSlide;
                        Swal.fire({
                            title: 'Are you sure this is the final version of the report?',
                            text: 'Once you submit this, it can no longer be edited and will be submitted to the RadTech, unless returned.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Confirm',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                            confirmButtonColor: '#dc2626',
                            cancelButtonColor: '#6b7280',
                            customClass: {
                                popup: 'rounded-2xl p-6',
                                title: 'text-lg font-bold text-gray-900',
                                confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-bold shadow-sm',
                                cancelButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold'
                            }
                        }).then(async (finalResult) => {
                            if (finalResult.isConfirmed) {
                                try { localStorage.removeItem(STORAGE_KEY); } catch (e) { }
                                const formEl = document.getElementById('report-form');
                                document.getElementById('save_draft_input').disabled = true;
                                document.getElementById('submit_final_input').disabled = false;

                                // 1. Show processing loader
                                Swal.fire({
                                    title: 'Submitting Report...',
                                    text: 'Please wait while the final report is being processed.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    customClass: {
                                        popup: 'rounded-2xl p-6',
                                        title: 'text-lg font-bold text-gray-900'
                                    },
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                try {
                                    const formData = new FormData(formEl);
                                    formData.set('submit_final', '1');
                                    formData.set('ajax', '1');

                                    const response = await fetch(window.location.href, {
                                        method: 'POST',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        body: formData
                                    });

                                    const data = await response.json();

                                    if (data.success) {
                                        // 2. Success transition popup
                                        await Swal.fire({
                                            icon: 'success',
                                            title: 'Report Submitted!',
                                            text: data.message || 'The report has been finalized and submitted to the RadTech.',
                                            timer: 1800,
                                            showConfirmButton: false,
                                            customClass: {
                                                popup: 'rounded-2xl p-6',
                                                title: 'text-xl font-bold text-gray-900'
                                            }
                                        });

                                        // 3. Redirect to worklist
                                        const baseDir = '<?= defined("PROJECT_DIR") && PROJECT_DIR ? "/" . PROJECT_DIR : "" ?>';
                                        window.location.href = data.redirect || `${baseDir}/index.php?role=radiologist&page=worklist`;
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Submission Failed',
                                            text: data.message || 'An error occurred while submitting the report.',
                                            customClass: { popup: 'rounded-2xl p-6' }
                                        });
                                    }
                                } catch (err) {
                                    console.error('Fetch submission error:', err);
                                    formEl.submit();
                                }
                            } else {
                                // User clicked Cancel or closed the confirmation: return back to preview modal
                                showReviewModal(returnSlide);
                            }
                        });
                    }
                });
            } else {
                if (confirm('Are you sure this is the final version of the report? Once submitted, it can no longer be edited and will be submitted to the RadTech.')) {
                    try { localStorage.removeItem(STORAGE_KEY); } catch (e) { }
                    document.getElementById('save_draft_input').disabled = true;
                    document.getElementById('submit_final_input').disabled = false;
                    document.getElementById('report-form').submit();
                }
            }
        }

        document.getElementById('btn-submit-final')?.addEventListener('click', (e) => {
            if (isCompleted) return;
            syncHiddenInput();

            if (!validateReport()) return;

            showReviewModal(0);
        });

        // ── Image Viewer ──────────────────────────────────────────────────────────
        const images = document.querySelectorAll('.dicom-img');
        const counter = document.getElementById('img-counter');
        const strip = document.querySelectorAll('.strip-thumb');
        const xrayLabels = <?= json_encode(array_values($imageLabels)) ?>;
        let currentImg = 0;
        let scale = 1, tx = 0, ty = 0, isDragging = false, sx = 0, sy = 0;

        const zoomLevelEl = document.getElementById('zoom-level');
        const btnZoomIn = document.getElementById('btn-zoom-in');
        const btnZoomOut = document.getElementById('btn-zoom-out');
        const dicomViewer = document.getElementById('dicom-viewer');
        const btnFullscreen = document.getElementById('btn-fullscreen-wrapper');

        function showImage(idx) {
            currentImg = idx;
            images.forEach((img, i) => img.classList.toggle('hidden', i !== idx));

            // Update counter
            if (counter) counter.textContent = `${idx + 1} / ${images.length}`;

            // Update exam name
            const examNameEl = document.getElementById('img-exam-name');
            if (examNameEl && xrayLabels && xrayLabels[idx]) {
                examNameEl.textContent = xrayLabels[idx];
                examNameEl.title = xrayLabels[idx];
            }

            // Update filename
            const filenameEl = document.getElementById('xray-filename');
            if (filenameEl) {
                filenameEl.textContent = 'IMG_' + (idx + 1) + '_' + '<?= $caseDetails['case_number'] ?>';
            }

            // Update floating strip
            const thumbItems = document.querySelectorAll('.xray-thumb-item');
            thumbItems.forEach((th, i) => {
                th.classList.toggle('border-red-500', i === idx);
                th.classList.toggle('bg-red-500/10', i === idx);
                th.classList.toggle('opacity-100', i === idx);
                th.classList.toggle('border-transparent', i !== idx);
                th.classList.toggle('opacity-60', i !== idx);
            });

            scale = 1; tx = 0; ty = 0; applyTransform();
        }

        function applyTransform() {
            const activeImg = images[currentImg];
            if (!activeImg) return;
            if (scale <= 1) { tx = 0; ty = 0; }
            activeImg.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
            if (zoomLevelEl) zoomLevelEl.textContent = Math.round(scale * 100) + '%';
            activeImg.style.cursor = scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default';

            // Update Fullscreen/Reset Icon based on zoom state
            if (fsIconWrap) {
                const isZoomed = Math.abs(scale - 1) > 0.01;
                const full = !!document.fullscreenElement;
                fsIconWrap.innerHTML = (full || isZoomed) ? SVG_SHRINK : SVG_EXPAND;
                if (btnFullscreen) {
                    btnFullscreen.title = isZoomed ? 'Reset Zoom' : (full ? 'Exit Fullscreen' : 'Toggle Fullscreen');
                }
            }
        }

        btnZoomIn?.addEventListener('click', () => { scale = Math.min(scale + 0.2, 5); applyTransform(); });
        btnZoomOut?.addEventListener('click', () => { scale = Math.max(scale - 0.2, 0.4); applyTransform(); });
        zoomLevelEl?.addEventListener('click', () => { scale = 1; tx = 0; ty = 0; applyTransform(); });

        document.getElementById('btn-prev-side')?.addEventListener('click', () => showImage(Math.max(0, currentImg - 1)));
        document.getElementById('btn-next-side')?.addEventListener('click', () => showImage(Math.min(images.length - 1, currentImg + 1)));

        document.addEventListener('fullscreenchange', () => {
            const full = !!document.fullscreenElement;
            dicomViewer.style.height = full ? '100vh' : '480px';
            dicomViewer.classList.toggle('rounded-xl', !full);

            const btnPrevSide = document.getElementById('btn-prev-side');
            const btnNextSide = document.getElementById('btn-next-side');
            if (btnPrevSide) btnPrevSide.classList.toggle('hidden', !full);
            if (btnNextSide) btnNextSide.classList.toggle('hidden', !full);

            applyTransform();
        });

        document.querySelectorAll('.xray-thumb-item').forEach(th => {
            th.addEventListener('click', () => showImage(parseInt(th.dataset.index)));
        });

        dicomViewer?.addEventListener('wheel', e => {
            e.preventDefault();
            scale = Math.round((scale + (e.deltaY < 0 ? 0.2 : -0.2)) * 10) / 10;
            scale = Math.max(0.4, Math.min(5, scale));
            applyTransform();
        });

        images.forEach(img => {
            img.addEventListener('mousedown', e => {
                if (scale <= 1) return;
                e.preventDefault(); isDragging = true;
                sx = e.clientX - tx; sy = e.clientY - ty; applyTransform();
            });
        });
        document.addEventListener('mousemove', e => {
            if (!isDragging) return;
            tx = e.clientX - sx; ty = e.clientY - sy; applyTransform();
        });
        document.addEventListener('mouseup', () => { if (isDragging) { isDragging = false; applyTransform(); } });

        // Inline SVGs so the icon never goes missing after Lucide replaces <i> tags
        const SVG_EXPAND = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';
        const SVG_SHRINK = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>';

        const fsIconWrap = document.getElementById('fullscreen-icon-wrap');
        if (fsIconWrap) fsIconWrap.innerHTML = SVG_EXPAND;

        btnFullscreen?.addEventListener('click', () => {
            if (Math.abs(scale - 1) > 0.01) {
                // Reset Zoom if currently zoomed (in or out)
                scale = 1; tx = 0; ty = 0;
                applyTransform();
            } else {
                // Toggle Fullscreen if at 100%
                if (!document.fullscreenElement) {
                    dicomViewer.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            }
        });
        document.addEventListener('fullscreenchange', () => {
            const full = !!document.fullscreenElement;
            dicomViewer.style.height = full ? '100vh' : '340px';
            dicomViewer.classList.toggle('rounded-xl', !full);
            if (fsIconWrap) fsIconWrap.innerHTML = full ? SVG_SHRINK : SVG_EXPAND;
        });

        // ── History accordion ─────────────────────────────────────────────────────
        document.getElementById('history-toggle')?.addEventListener('click', () => {
            const p = document.getElementById('history-panel');
            const c = document.getElementById('history-chevron');
            p.classList.toggle('hidden');
            c.classList.toggle('rotate-180');
        });

        // Initialize
        if (images.length > 0) {
            showImage(0);
        } else {
            applyTransform();
        }

        // ── Activity tracking (viewing vs typing) ────────
        let pingInterval;
        let radStatus = 'viewing';
        let lastTypedTime = 0;

        // Listen to typing in textareas
        document.querySelectorAll('.exam-findings, .exam-impression').forEach(ta => {
            ta.addEventListener('input', () => {
                if (!isCompleted) {
                    lastTypedTime = Date.now();
                    if (radStatus !== 'typing') {
                        radStatus = 'typing';
                        sendPing(); // send immediately on state change
                    }
                }
            });
        });

        function sendPing() {
            if (isCompleted) return;

            // Skip ping if page is hidden to avoid conflicting with inactive pings
            if (document.visibilityState === 'hidden') return;

            if (radStatus === 'typing' && (Date.now() - lastTypedTime > 5000)) {
                radStatus = 'viewing';
            }

            const fd = new window.FormData();
            fd.append('status', radStatus);

            fetch(`<?= url('app/Api/case_activity.php') ?>?action=ping&case_id=<?= $caseId ?>`, {
                method: 'POST',
                body: fd
            }).catch(err => console.error(err));
        }

        if (!isCompleted) {
            sendPing(); // initial ping
            pingInterval = setInterval(sendPing, 2500); // Ping every 2.5 seconds

            function sendInactivePing() {
                // Stop regular pings immediately
                if (pingInterval) {
                    clearInterval(pingInterval);
                    pingInterval = null;
                }

                const fd = new window.FormData();
                fd.append('status', 'inactive');
                try {
                    navigator.sendBeacon(`<?= url('app/Api/case_activity.php') ?>?action=ping&case_id=<?= $caseId ?>`, fd);
                } catch (e) { }
                // Fallback keepalive
                try {
                    fetch(`<?= url('app/Api/case_activity.php') ?>?action=ping&case_id=<?= $caseId ?>`, {
                        method: 'POST', body: fd, keepalive: true
                    }).catch(() => { });
                } catch (e) { }
            }

            // When user leaves the page or unloads
            window.addEventListener('beforeunload', sendInactivePing);
            window.addEventListener('pagehide', sendInactivePing);

            // When user explicitly clicks back to worklist
            document.getElementById('back-to-worklist-btn')?.addEventListener('click', sendInactivePing);

            // Also handle visibility change (e.g. switching tabs on mobile)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    sendInactivePing();
                } else {
                    // Return to page: restart interval if it was cleared
                    radStatus = 'viewing';
                    if (!pingInterval) {
                        pingInterval = setInterval(sendPing, 2500);
                    }
                    sendPing();
                }
            });
        }
    });
</script>