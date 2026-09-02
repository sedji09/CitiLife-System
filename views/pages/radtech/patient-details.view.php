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

<?php
$backLink = "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-lists";
if (isset($_GET['from']) && $_GET['from'] === 'disputes') {
    $backLink .= "&tab=disputes";
}
?>

<!-- Header -->
<div class="flex items-center gap-4">
    <a href="<?= $backLink ?>"
        class="flex w-10 h-10 items-center justify-center rounded-xl bg-white border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
        <i data-lucide="chevron-left" class="w-5 h-5"></i>
    </a>
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient Details</h2>
        <p class="text-sm text-gray-500 mt-1">Diagnostic image upload and case management</p>
    </div>
</div>

<?php if ($isReadOnly): ?>
    <div class="mt-5 rounded-lg bg-blue-50 border border-blue-300 p-4 flex items-center gap-3">
        <i data-lucide="info" class="w-5 h-5 text-blue-600 shrink-0"></i>
        <p class="text-sm text-blue-800 font-medium">
            <?= (!empty($activeDispute) && $activeDispute['status'] === 'Escalated to Radiologist') 
                ? 'This error report has already been escalated to the Radiologist for review & report amendment.' 
                : 'This case has already been submitted to the radiologist' ?>
        </p>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mt-5 rounded-lg bg-red-50 border border-red-300 p-4 flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
        <p class="text-sm text-red-700"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<?php if ($successMsg ?? false): ?>
    <div class="mt-5 rounded-lg bg-green-50 border border-green-300 p-4 flex items-center gap-3 animate-in fade-in slide-in-from-top-2 duration-500">
        <i data-lucide="check-circle" class="w-5 h-5 text-green-600 shrink-0"></i>
        <p class="text-sm text-green-800 font-medium"><?= htmlspecialchars($successMsg) ?></p>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" id="patient-details-form">
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Patient Verification -->
        <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm">
            <div class="mb-3 flex items-center gap-2">
                <i data-lucide="user-check" class="h-5 w-5 text-green-600"></i>
                <h3 class="text-lg font-semibold text-gray-800">Patient Verification</h3>
            </div>
            <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                <p class="text-xs font-medium italic text-red-700 mb-3">Note: CONFIRM IDENTITY BEFORE UPLOAD</p>
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
                    <?php
                    $examInputName = 'exam_type';
                    $preSelectedExams = $caseDetails['exam_type'] ?? '';
                    require __DIR__ . '/../../components/exam-selector.php';
                    ?>
                </div>
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1.5">Priority</label>
                    <?php
                    $priorities = ['Routine', 'Urgent', 'STAT'];
                    $currentPriority = $caseDetails['priority'] ?? '';
                    $priorityHasMatch = !empty($currentPriority) && in_array($currentPriority, $priorities);
                    ?>
                    <select name="priority"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 <?= $isReadOnly ? 'opacity-70 cursor-not-allowed' : '' ?>"
                        required <?= $isReadOnly ? 'disabled' : '' ?>>
                        <option value="" disabled <?= !$priorityHasMatch ? 'selected' : '' ?>>-- Select Priority --
                        </option>
                        <?php foreach ($priorities as $pr): ?>
                            <option value="<?= $pr ?>" <?= ($currentPriority === $pr) ? 'selected' : '' ?>><?= $pr ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1.5">Clinical Information / Indication</label>
                    <textarea name="clinical_information" rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 <?= $isReadOnly ? 'opacity-70 cursor-not-allowed' : '' ?>"
                        placeholder="Enter patient symptoms, history, or clinical indication..."
                        <?= $isReadOnly ? 'disabled' : '' ?>><?= htmlspecialchars($caseDetails['clinical_information'] ?? '') ?></textarea>
                </div>
                <div class="pt-1">
                    <span class="block text-gray-600 text-sm font-medium mb-1.5">Status</span>
                    <?php
                    $displayStatus = $caseDetails['status'] ?: 'Pending';
                    $isOverdue = (time() - strtotime($caseDetails['created_at'])) >= 3 * 3600;
                    if ($displayStatus === 'Pending' && $isOverdue) {
                        $displayStatus = 'Overdue';
                    }

                    if ($displayStatus === 'Completed')
                        $sBadge = 'border border-green-400 bg-green-50 text-green-700';
                    elseif ($displayStatus === 'Under Reading')
                        $sBadge = 'border border-blue-400 bg-blue-50 text-blue-700';
                    elseif ($displayStatus === 'Report Ready')
                        $sBadge = 'border border-indigo-400 bg-indigo-50 text-indigo-700';
                    elseif ($displayStatus === 'Overdue' || $displayStatus === 'Rejected')
                        $sBadge = 'border border-red-400 bg-red-50 text-red-700';
                    else
                        $sBadge = 'border border-yellow-400 bg-yellow-50 text-yellow-700';
                    ?>
                    <span class="inline-block font-bold text-xs px-3 py-1.5 rounded-full <?= $sBadge ?>">
                        <?= htmlspecialchars($displayStatus) ?>
                    </span>
                </div>
            </div>
        </div>

    </div>

    <?php $isReportReady = empty($activeDispute) && in_array($caseDetails['status'], ['Report Ready', 'Completed']); ?>

    <?php if (!empty($activeDispute)): ?>
        <!-- 2-COLUMN DISPUTE RESOLUTION ROW -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
            
            <!-- Left Card: Patient Error Report & Endorsement -->
            <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm space-y-5 h-full flex flex-col justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="file-warning" class="w-5 h-5 text-red-600"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Correction & Endorsement</h3>
                </div>

                <!-- Patient Stated Report -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Patient's Reported Issue
                        </label>
                        <?php if (!empty($activeDispute['category'])): ?>
                            <?php
                            $cat = $activeDispute['category'];
                            $catLabel = match($cat) {
                                'findings_error' => 'Findings Error',
                                'demographic_error' => 'Demographic Error',
                                'both_error' => 'Findings & Demographic Error',
                                'exam_details_error' => 'Exam Details Error',
                                default => ucwords(str_replace('_', ' ', $cat))
                            };
                            ?>
                            <span class="text-[11px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">
                                <?= htmlspecialchars($catLabel) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php
                    $rawDesc = trim($activeDispute['description'] ?? 'No notes provided.');
                    $cleanRaw = str_replace(["\r\n", "\r"], "\n", $rawDesc);
                    
                    $findingsNote = '';
                    if (preg_match('/Findings Note:\s*(.*?)(?=(Wrong Patient Info:|Exam Details Note:|Demographics Note:|$))/si', $cleanRaw, $m)) {
                        $findingsNote = trim(preg_replace('/^\s*•\s*/m', '', trim($m[1])));
                    }

                    // Fallback to general description only if there is no demographic section
                    if (empty($findingsNote) && !preg_match('/(Wrong Patient Info:|Demographics Note:)/i', $cleanRaw)) {
                        $findingsNote = $cleanRaw;
                    }
                    ?>
                    <?php if (!empty($findingsNote)): ?>
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 text-xs text-gray-800 font-medium leading-relaxed whitespace-pre-wrap mt-1">
                            <?= htmlspecialchars($findingsNote) ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 text-xs text-gray-500 italic mt-1">
                            No specific findings notes reported.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Correction Type Options (Clean single-line options) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                        Correction Type
                    </label>
                    <?php
                    $activeCorrectionType = 'typo';
                    if (!empty($activeDispute['radtech_notes'])) {
                        if (str_contains($activeDispute['radtech_notes'], '[New Image Re-uploaded]')) {
                            $activeCorrectionType = 'reupload';
                        } elseif (str_contains($activeDispute['radtech_notes'], '[Re-reading Request]')) {
                            $activeCorrectionType = 'reread';
                        }
                    }
                    ?>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 cursor-pointer transition-colors select-none has-checked:border-red-500 has-checked:bg-red-50/40">
                            <input type="radio" name="correction_type" value="typo" <?= $activeCorrectionType === 'typo' ? 'checked' : '' ?> onchange="toggleCorrectionMode(this.value)" class="text-red-600 focus:ring-red-500" <?= $isReadOnly ? 'disabled' : '' ?>>
                            <span class="text-xs font-semibold text-gray-800">Typographical / Minor Error in Report</span>
                        </label>
                        <label class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 cursor-pointer transition-colors select-none has-checked:border-red-500 has-checked:bg-red-50/40">
                            <input type="radio" name="correction_type" value="reupload" <?= $activeCorrectionType === 'reupload' ? 'checked' : '' ?> onchange="toggleCorrectionMode(this.value)" class="text-red-600 focus:ring-red-500" <?= $isReadOnly ? 'disabled' : '' ?>>
                            <span class="text-xs font-semibold text-gray-800">Re-upload Diagnostic Image</span>
                        </label>
                        <label class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 cursor-pointer transition-colors select-none has-checked:border-red-500 has-checked:bg-red-50/40">
                            <input type="radio" name="correction_type" value="reread" <?= $activeCorrectionType === 'reread' ? 'checked' : '' ?> onchange="toggleCorrectionMode(this.value)" class="text-red-600 focus:ring-red-500" <?= $isReadOnly ? 'disabled' : '' ?>>
                            <span class="text-xs font-semibold text-gray-800">Request Second Reading / Re-interpretation</span>
                        </label>
                    </div>
                </div>

                <!-- RadTech Endorsement Notes to Radiologist -->
                <div>
                    <label for="radtech_dispute_notes" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
                        Notes for Radiologist <?= $isReadOnly ? '' : '<span class="text-red-500">*</span>' ?>
                    </label>
                    <textarea name="radtech_dispute_notes" id="radtech_dispute_notes" rows="3" <?= $isReadOnly ? 'readonly disabled' : 'required' ?>
                        class="w-full rounded-lg border border-gray-300 bg-white p-3 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 <?= $isReadOnly ? 'bg-gray-50 text-gray-600 cursor-not-allowed' : '' ?>"
                        placeholder="Enter endorsement instructions for the Radiologist..."><?= htmlspecialchars($activeDispute['radtech_notes'] ?? '') ?></textarea>
                </div>
            </div>

            <script>
                window.toggleCorrectionMode = function(mode) {
                    var isReadOnly = <?= $isReadOnly ? 'true' : 'false' ?>;
                    var retainedSection = document.getElementById('retained-image-section');
                    var dropZoneContainer = document.getElementById('drop-zone-container');
                    var fileCounter = document.getElementById('file-counter');
                    var retainedBadge = document.getElementById('retained-status-badge');
                    var errNoImg = document.getElementById('no-image-error');
                    var errLimit = document.getElementById('limit-error');

                    if (errNoImg) errNoImg.style.display = 'none';
                    if (errLimit) errLimit.style.display = 'none';

                    if (isReadOnly) {
                        // When read-only (already escalated to Radiologist), ALWAYS show the image archive section!
                        if (retainedSection) retainedSection.classList.remove('hidden');
                        if (dropZoneContainer) dropZoneContainer.classList.add('hidden');
                        if (retainedBadge) {
                            if (mode === 'reupload') {
                                retainedBadge.innerHTML = '<i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Re-uploaded Image';
                                retainedBadge.className = 'inline-flex items-center gap-1 font-semibold text-blue-700 bg-blue-100 border border-blue-200 px-2 py-0.5 rounded';
                            } else {
                                retainedBadge.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Retained for Report';
                                retainedBadge.className = 'inline-flex items-center gap-1 font-semibold text-green-700 bg-green-100 border border-green-200 px-2 py-0.5 rounded';
                            }
                        }
                        if (fileCounter) {
                            fileCounter.textContent = '<?= count($existingPaths) ?> <?= count($existingPaths) === 1 ? 'file' : 'files' ?>';
                        }
                    } else {
                        if (mode === 'typo' || mode === 'reread') {
                            if (retainedSection) retainedSection.classList.remove('hidden');
                            if (dropZoneContainer) dropZoneContainer.classList.add('hidden');
                            if (retainedBadge) {
                                retainedBadge.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Retained for Report';
                                retainedBadge.className = 'inline-flex items-center gap-1 font-semibold text-green-700 bg-green-100 border border-green-200 px-2 py-0.5 rounded';
                            }
                            if (fileCounter) {
                                fileCounter.textContent = '<?= count($existingPaths) ?> <?= count($existingPaths) === 1 ? 'file' : 'files' ?>';
                            }
                        } else {
                            if (retainedSection) retainedSection.classList.add('hidden');
                            if (dropZoneContainer) dropZoneContainer.classList.remove('hidden');
                            if (fileCounter) {
                                fileCounter.textContent = (typeof fileQueue !== 'undefined' && fileQueue.length ? fileQueue.length : 0) + ' files';
                            }
                        }
                    }
                    if (window.lucide) window.lucide.createIcons();
                };

                document.addEventListener('DOMContentLoaded', function() {
                    var curMode = '<?= $activeCorrectionType ?>';
                    if (window.toggleCorrectionMode) window.toggleCorrectionMode(curMode);
                });
            </script>

            <!-- Right Card: Diagnostic Image / Retained Image -->
            <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col h-full space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="image" class="w-5 h-5 text-gray-700"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Diagnostic Image</h3>
                    </div>
                    <?php
                    $existingPaths = [];
                    if (!empty($caseDetails['image_path'])) {
                        $decoded = json_decode($caseDetails['image_path'], true);
                        $existingPaths = is_array($decoded) ? $decoded : [$caseDetails['image_path']];
                    }
                    ?>
                    <span id="file-counter" class="text-xs font-semibold text-gray-500 bg-gray-100 border border-gray-200 rounded-full px-2.5 py-0.5">
                        <?= count($existingPaths) ?> <?= count($existingPaths) === 1 ? 'file' : 'files' ?>
                    </span>
                </div>

                <!-- Retained / Uploaded Image Section -->
                <div id="retained-image-section" class="space-y-3">
                    <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 flex items-center justify-between text-xs">
                        <span class="font-medium text-gray-700">Attached Image(s): <strong><?= count($existingPaths) ?> <?= count($existingPaths) === 1 ? 'file' : 'files' ?></strong></span>
                        <span id="retained-status-badge" class="inline-flex items-center gap-1 font-semibold <?= $activeCorrectionType === 'reupload' ? 'text-blue-700 bg-blue-100 border border-blue-200' : 'text-green-700 bg-green-100 border border-green-200' ?> px-2 py-0.5 rounded">
                            <i data-lucide="<?= $activeCorrectionType === 'reupload' ? 'check-circle' : 'check' ?>" class="w-3.5 h-3.5"></i>
                            <?= $activeCorrectionType === 'reupload' ? 'Re-uploaded Image' : 'Retained for Report' ?>
                        </span>
                    </div>

                    <p class="text-xs text-gray-500">Click thumbnail to view full-screen image.</p>

                    <?php if (!empty($existingPaths)): ?>
                        <div class="flex flex-wrap gap-4 pt-1">
                            <?php foreach ($existingPaths as $idx => $sPath): ?>
                                <div onclick="openXrayLightbox('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>')"
                                    style="width: 128px; height: 128px; min-width: 128px; min-height: 128px;"
                                    class="group relative rounded-2xl overflow-hidden border-2 border-gray-300 hover:border-red-600 bg-black cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md shrink-0 flex items-center justify-center select-none"
                                    title="Click to view image fullscreen">
                                    <img src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>" alt="X-ray <?= $idx + 1 ?>"
                                        style="width: 100%; height: 100%; object-fit: contain;"
                                        class="opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-200">
                                    
                                    <!-- Center Expand Icon on Hover -->
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
                                        <div class="w-10 h-10 rounded-xl bg-black/60 backdrop-blur-xs border border-white/20 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-200 shadow-lg">
                                            <i data-lucide="maximize-2" class="w-5 h-5 text-white stroke-[2.5]"></i>
                                        </div>
                                    </div>

                                    <!-- Bottom Label -->
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-[10px] font-bold text-white py-1 text-center uppercase tracking-wider z-10 pointer-events-none">
                                        IMG <?= $idx + 1 ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-xs text-gray-400 italic">No existing image found.</p>
                    <?php endif; ?>
                </div>

                <!-- Errors -->
                <div id="file-size-error" style="display:none;"
                    class="rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 shrink-0"></i>
                    <p id="file-size-error-msg" class="text-sm text-red-700 font-medium">File exceeds 15 MB limit.</p>
                </div>
                <div id="no-image-error" style="display:none;"
                    class="rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-3">
                    <i data-lucide="image-off" class="w-5 h-5 text-red-600 shrink-0"></i>
                    <p class="text-sm text-red-700 font-medium">Please upload at least one diagnostic image.</p>
                </div>
                <div id="limit-error" style="display:none;"
                    class="rounded-lg bg-orange-50 border border-orange-300 p-3 flex items-center gap-3">
                    <i data-lucide="info" class="w-5 h-5 text-orange-600 shrink-0"></i>
                    <p id="limit-error-msg" class="text-sm text-orange-700 font-medium">Image count must match exam count.</p>
                </div>

                <?php if (!$isReadOnly): ?>
                <!-- Dropzone Container (shown only when reupload is selected and NOT read-only) -->
                <div id="drop-zone-container" class="hidden">
                    <div class="space-y-3">
                        <div id="drop-zone"
                            class="flex flex-col items-center justify-center border-2 border-dashed border-red-200 rounded-xl min-h-[11rem] relative cursor-pointer transition-colors bg-white hover:bg-red-50">
                            <div class="text-center p-4 pointer-events-none">
                                <div class="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                                </div>
                                <p class="text-xs font-semibold text-red-600 mb-0.5">Click or drag new X-ray files here</p>
                                <p class="text-[11px] text-gray-400">Max 15 MB per file (DICOM, JPG, PNG)</p>
                            </div>
                            <input type="file" id="xray_file_input" name="xray_image[]" accept=".jpg,.jpeg,.png,.dcm,.dicom"
                                class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" multiple>
                        </div>

                        <!-- Preview list -->
                        <div id="file-preview-area" style="display:flex;flex-direction:column;gap:0.5rem;max-height:16rem;overflow-y:auto;">
                            <p id="no-file-msg" style="font-size:0.75rem;color:#9ca3af;font-style:italic;">No files selected yet.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>

    <?php else: ?>
        <!-- NORMAL CASE (No dispute): Standard Diagnostic Image Upload Card -->
        <div class="<?= $isReadOnly ? 'mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch' : 'mt-8' ?>">
            <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col h-full">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <h3 class="text-lg font-semibold text-gray-800">Diagnostic Image Upload</h3>
                    <?php if (!$isReadOnly): ?>
                        <span id="file-counter"
                            style="font-size:0.75rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:9999px;padding:2px 10px;">0 files</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-gray-500 mb-5">DICOM · JPG · JPEG · PNG — Max 15 MB per file</p>

                <!-- Errors -->
                <div id="file-size-error" style="display:none;"
                    class="mb-3 rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 shrink-0"></i>
                    <p id="file-size-error-msg" class="text-sm text-red-700 font-medium">File exceeds the 15 MB maximum size.</p>
                </div>

                <div id="no-image-error" style="display:none;"
                    class="mb-3 rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-3">
                    <i data-lucide="image-off" class="w-5 h-5 text-red-600 shrink-0"></i>
                    <p class="text-sm text-red-700 font-medium">Please upload at least one diagnostic image before submitting.</p>
                </div>

                <div id="exam-required-error" style="display:none;"
                    class="mb-3 rounded-lg bg-amber-50 border border-amber-300 p-3 flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-amber-600 shrink-0"></i>
                    <p class="text-sm text-amber-700 font-medium">Please select Examination Types above before uploading images.</p>
                </div>

                <div id="limit-error" style="display:none;"
                    class="mb-3 rounded-lg bg-orange-50 border border-orange-300 p-3 flex items-center gap-3">
                    <i data-lucide="info" class="w-5 h-5 text-orange-600 shrink-0"></i>
                    <p id="limit-error-msg" class="text-sm text-orange-700 font-medium">You can only upload as many images as there are selected exams.</p>
                </div>

                <?php if (!$isReadOnly): ?>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:start;">
                        <!-- Drop Zone -->
                        <div id="drop-zone"
                            class="flex flex-col items-center justify-center border-2 border-dashed border-red-200 rounded-xl min-h-[13rem] relative cursor-pointer transition-colors bg-white hover:bg-red-50">
                            <div class="text-center p-4 pointer-events-none">
                                <div class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-semibold text-red-600 mb-1">Click or drag X-ray files here</p>
                                <p class="text-xs text-gray-400">Patient: <?= htmlspecialchars($caseDetails['first_name'] . ' ' . $caseDetails['last_name']) ?></p>
                                <p class="text-xs text-gray-400 mt-1">Max <strong class="text-gray-500">15 MB</strong> per file</p>
                            </div>
                            <input type="file" id="xray_file_input" name="xray_image[]" accept=".jpg,.jpeg,.png,.dcm,.dicom"
                                class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" multiple>
                        </div>

                        <!-- Preview list -->
                        <div id="file-preview-area" style="display:flex;flex-direction:column;gap:0.6rem;max-height:22rem;overflow-y:auto;">
                            <p id="no-file-msg" style="font-size:0.875rem;color:#9ca3af;font-style:italic;">No files selected yet.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Read-only image grid -->
                    <?php
                    $savedPaths = [];
                    if (!empty($caseDetails['image_path'])) {
                        $decoded = json_decode($caseDetails['image_path'], true);
                        $savedPaths = is_array($decoded) ? $decoded : [$caseDetails['image_path']];
                    }
                    ?>
                    <?php if (!empty($savedPaths)): ?>
                        <div class="flex flex-wrap gap-4">
                            <?php foreach ($savedPaths as $idx => $sPath): ?>
                                <div onclick="openXrayLightbox('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>')"
                                    style="width: 128px; height: 128px; min-width: 128px; min-height: 128px;"
                                    class="group relative rounded-2xl overflow-hidden border-2 border-gray-300 hover:border-red-600 bg-black cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md shrink-0 flex items-center justify-center select-none"
                                    title="Click to view image fullscreen">
                                    <img src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>" alt="X-ray <?= $idx + 1 ?>"
                                        style="width: 100%; height: 100%; object-fit: contain;"
                                        class="opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-200">
                                    
                                    <!-- Center Expand Icon on Hover -->
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
                                        <div class="w-10 h-10 rounded-xl bg-black/60 backdrop-blur-xs border border-white/20 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-200 shadow-lg">
                                            <i data-lucide="maximize-2" class="w-5 h-5 text-white stroke-[2.5]"></i>
                                        </div>
                                    </div>

                                    <!-- Bottom Label -->
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-[10px] font-bold text-white py-1 text-center uppercase tracking-wider z-10 pointer-events-none">
                                        IMG <?= $idx + 1 ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($caseDetails['image_status'] === 'Uploaded'): ?>
                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;color:#16a34a;">
                            <i data-lucide="check-square" style="width:2rem;height:2rem;margin-bottom:0.5rem;"></i>
                            <span style="font-weight:500;">Images successfully uploaded</span>
                        </div>
                    <?php else: ?>
                        <p style="color:#9ca3af;font-size:0.875rem;font-style:italic;">No images uploaded yet.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($isReadOnly): ?>
            <!-- Radiologist Report Findings Card -->
            <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col h-full">
                <div class="mb-4 flex items-center gap-2">
                    <i data-lucide="file-text" class="h-5 w-5 <?= $isReportReady ? 'text-red-500' : 'text-gray-400' ?>"></i>
                    <h3 class="text-lg font-semibold <?= $isReportReady ? 'text-gray-800' : 'text-gray-500' ?>">Radiologist Report Findings</h3>
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
                                            <p class="text-sm text-gray-950 font-bold whitespace-pre-wrap leading-relaxed bg-white border border-gray-100 rounded-lg p-2.5 shadow-sm"><?= htmlspecialchars($reportData['impression']) ?></p>
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
                                    <div class="text-sm text-gray-950 font-bold whitespace-pre-wrap leading-relaxed bg-red-50/50 border border-red-100 rounded-lg p-3 shadow-sm"><?= htmlspecialchars($impressionRaw) ?></div>
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
            <?php endif; ?>

        </div>
    <?php endif; ?>

    <!-- Validation Error Banner -->
    <div id="rad-selection-error"
        class="bg-orange-50 border border-orange-200 text-orange-700 px-4 py-3 rounded-lg mt-6 hidden flex items-start gap-3 shadow-sm transition-opacity duration-300"
        role="alert">
        <i data-lucide="info" class="w-5 h-5 text-orange-500 mt-0.5 shrink-0"></i>
        <div>
            <strong class="font-medium text-sm">Selection Required</strong>
            <span class="block sm:inline text-sm mt-0.5 opacity-90">Please select a radiologist from the dropdown
                before submitting the case.</span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 flex gap-4 items-center">
        <?php if (!$isReadOnly): ?>
            <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 p-2 rounded-lg shadow-sm">
                <label for="radiologist_id" class="text-sm font-medium text-gray-700 whitespace-nowrap"><i
                        data-lucide="user-check" class="w-4 h-4 inline mr-1 text-red-500"></i>Send to:</label>
                <div class="relative inline-block" id="custom-radiologist-select" style="min-width: 260px;">
                    <input type="hidden" name="radiologist_id" id="radiologist_id" required>
                    <button type="button"
                        class="w-full text-left text-sm border border-gray-300 rounded-md py-1.5 px-3 bg-white flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-shadow shadow-sm"
                        onclick="document.getElementById('rad-options').classList.toggle('hidden')">
                        <span id="rad-selected-text" class="text-gray-700">-- Select Radiologist --</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500 pointer-events-none shrink-0 ml-2"></i>
                    </button>
                    <ul id="rad-options"
                        class="absolute z-50 mb-1 bottom-full w-full bg-white border border-gray-200 rounded-md shadow-lg hidden max-h-60 overflow-y-auto">
                        <?php foreach ($radiologistsList ?? [] as $rad): ?>
                            <?php
                            $caseCount = isset($rad['active_case_count']) ? (int) $rad['active_case_count'] : 0;
                            $isAvailable = isset($rad['is_available']) ? (int) $rad['is_available'] === 1 : true;
                            ?>
                            <li class="px-3 py-2 text-sm flex items-center justify-between border-b border-gray-50 last:border-0 transition-colors <?= $isAvailable ? 'cursor-pointer hover:bg-gray-50' : 'cursor-not-allowed opacity-60 bg-gray-50' ?>"
                                <?= $isAvailable ? "onclick=\"
                                    document.getElementById('radiologist_id').value = '{$rad['id']}';
                                    document.getElementById('rad-selected-text').innerHTML = 'Dr. " . addslashes(htmlspecialchars(trim(preg_replace('/^Dr\.?\s*/i', '', $rad['radiologist_name'])))) . "';
                                    document.getElementById('rad-options').classList.add('hidden');
                                    document.getElementById('rad-selection-error').classList.add('hidden');
                                \"" : '' ?>>
                                <span class="font-medium <?= $isAvailable ? 'text-gray-800' : 'text-gray-500' ?>">Dr.
                                    <?= htmlspecialchars(trim(preg_replace('/^Dr\.?\s*/i', '', $rad['radiologist_name']))) ?></span>
                                <?php if ($isAvailable): ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-yellow-400 bg-yellow-50 px-2 py-0.5 text-xs font-semibold text-yellow-700 shadow-sm ml-2"
                                        title="<?= $caseCount ?> pending cases">
                                        <?= $caseCount ?>
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 shadow-sm ml-2"
                                        title="Unavailable">
                                        Unavailable
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const availableOptions = document.querySelectorAll('#rad-options li.cursor-pointer');
                        if (availableOptions.length === 1) {
                            availableOptions[0].click();
                            // Disable dropdown button to make it look like a static selection
                            const btn = document.querySelector('#custom-radiologist-select button');
                            if (btn) {
                                btn.removeAttribute('onclick');
                                btn.classList.add('bg-gray-50', 'cursor-default');
                                btn.classList.remove('bg-white');
                                const chevron = btn.querySelector('.lucide-chevron-down');
                                if (chevron) chevron.style.display = 'none';
                            }
                        }
                    });

                    document.addEventListener('click', function (event) {
                        const selectWrap = document.getElementById('custom-radiologist-select');
                        const options = document.getElementById('rad-options');
                        if (selectWrap && options && !selectWrap.contains(event.target)) {
                            options.classList.add('hidden');
                        }
                    });
                </script>
            </div>
            <button type="button"
                onclick="if(!document.getElementById('radiologist_id').value){ const err = document.getElementById('rad-selection-error'); err.classList.remove('hidden'); setTimeout(() => err.classList.add('hidden'), 5000); lucide.createIcons(); return; } <?php if (!empty($activeDispute)): ?>const dispNotes = document.getElementById('radtech_dispute_notes'); if (dispNotes && !dispNotes.value.trim()){ Swal.fire('Notes Required', 'Please enter endorsement notes for the Radiologist before escalating.', 'warning'); dispNotes.focus(); return; }<?php endif; ?> confirmFormAction(this, '1', '<?= !empty($activeDispute) ? "Confirm Escalation" : "Confirm Submission" ?>', '<?= !empty($activeDispute) ? "Would you like to escalate this error report to the Radiologist?" : "Would you like to confirm submitting this case?" ?>', 'submit_radiologist', event)"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition shadow-sm h-full">
                <i data-lucide="send" class="w-4 h-4"></i>
                <?= !empty($activeDispute) ? 'Escalate to Radiologist' : 'Submit to Radiologist' ?>
            </button>
        <?php else: ?>
            <button type="button" disabled
                class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-bold text-gray-400 cursor-not-allowed shadow-sm">
                <i data-lucide="send" class="w-4 h-4"></i>
                Already Submitted
            </button>
        <?php endif; ?>

        <?php if (empty($activeDispute)): ?>
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
        <?php endif; ?>
    </div>
</form>


<?php if (!$isReadOnly): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var MAX_BYTES = 15 * 1024 * 1024; // 15 MB per file
            var fileQueue = []; // DataTransfer-backed list of File objects

            var input = document.getElementById('xray_file_input');
            var dropZone = document.getElementById('drop-zone');
            var previewArea = document.getElementById('file-preview-area');
            var noFileMsg = document.getElementById('no-file-msg');
            var counter = document.getElementById('file-counter');
            var errSize = document.getElementById('file-size-error');
            var errSizeMsg = document.getElementById('file-size-error-msg');
            var errLimit = document.getElementById('limit-error');
            var errLimitMsg = document.getElementById('limit-error-msg');
            var errExamReq = document.getElementById('exam-required-error');
            var errNoImg = document.getElementById('no-image-error');
            var examHidden = document.querySelector('.exam-ms-hidden-input');
            var examContainer = document.querySelector('.exam-ms-component');

            if (!input || !dropZone) return;

            function formatMB(bytes) { return (bytes / (1024 * 1024)).toFixed(2) + ' MB'; }

            function updateCounter() {
                var count = getExamCount();
                if (counter) {
                    if (count > 0) {
                        counter.textContent = fileQueue.length + ' of ' + count + (count === 1 ? ' image' : ' images');
                        if (fileQueue.length === count) {
                            counter.style.color = '#059669'; // amber/green
                            counter.style.background = '#ecfdf5';
                            counter.style.borderColor = '#6ee7b7';
                        } else {
                            counter.style.color = '#6b7280';
                            counter.style.background = '#f3f4f6';
                            counter.style.borderColor = '#e5e7eb';
                        }
                    } else {
                        counter.textContent = '0 images';
                    }
                }
            }

            function getExamCount() {
                if (examHidden && examHidden.value.trim()) {
                    var val = examHidden.value.trim();
                    return val ? val.split(',').filter(s => s.trim()).length : 0;
                }
                var phpExam = '<?= addslashes(htmlspecialchars($caseDetails['exam_type'] ?? '')) ?>'.trim();
                if (phpExam) {
                    return phpExam.split(',').filter(s => s.trim()).length;
                }
                return 1;
            }

            function renderPreviews() {
                // Clear preview area (keep no-file-msg)
                Array.from(previewArea.children).forEach(function (c) {
                    if (c.id !== 'no-file-msg') previewArea.removeChild(c);
                });

                if (fileQueue.length === 0) {
                    if (noFileMsg) noFileMsg.style.display = 'block';
                    dropZone.classList.remove('border-green-400', 'bg-green-50', 'hover:bg-green-50');
                    dropZone.classList.add('border-red-200', 'bg-white', 'hover:bg-red-50');
                } else {
                    if (noFileMsg) noFileMsg.style.display = 'none';
                    dropZone.classList.remove('border-red-200', 'bg-white', 'hover:bg-red-50');
                    dropZone.classList.add('border-green-400', 'bg-green-50', 'hover:bg-green-50');
                }

                fileQueue.forEach(function (file, idx) {
                    var card = document.createElement('div');
                    card.className = "flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-2.5";

                    // Thumb
                    var thumbWrap = document.createElement('div');
                    thumbWrap.className = "w-11 h-11 rounded-lg overflow-hidden border border-gray-100 bg-gray-50 shrink-0 flex items-center justify-center";

                    if (file.type.startsWith('image/')) {
                        var img = document.createElement('img');
                        img.alt = 'Preview';
                        img.className = "w-full h-full object-cover";
                        var reader = new window.FileReader();
                        reader.onload = (function (i) { return function (e) { i.src = e.target.result; }; })(img);
                        reader.readAsDataURL(file);
                        thumbWrap.appendChild(img);
                    } else {
                        thumbWrap.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
                    }

                    // Info
                    var info = document.createElement('div');
                    info.className = "flex-1 min-w-0";
                    var nameEl = document.createElement('p');
                    nameEl.className = "text-sm font-semibold text-gray-800 whitespace-nowrap overflow-hidden text-ellipsis m-0";
                    nameEl.textContent = file.name;
                    var sizeEl = document.createElement('p');
                    sizeEl.className = "text-[11px] text-gray-500 mt-0.5";
                    sizeEl.textContent = formatMB(file.size);
                    info.appendChild(nameEl);
                    info.appendChild(sizeEl);

                    // Badge
                    var badge = document.createElement('span');
                    badge.className = "shrink-0 text-[10px] font-bold text-gray-500 bg-gray-100 border border-gray-200 rounded-full px-2 py-0.5";
                    badge.textContent = (idx + 1);

                    // Remove btn
                    var rmBtn = document.createElement('button');
                    rmBtn.type = 'button';
                    rmBtn.title = 'Remove';
                    rmBtn.className = "shrink-0 bg-transparent border-none cursor-pointer text-gray-300 hover:text-red-500 p-1 leading-none transition-colors";
                    rmBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
                    // Hover effects are now handled by Tailwind.

                    rmBtn.addEventListener('click', function () { removeFile(idx); });

                    card.appendChild(thumbWrap);
                    card.appendChild(info);
                    card.appendChild(badge);
                    card.appendChild(rmBtn);
                    previewArea.appendChild(card);
                });

                updateCounter();
                // Do NOT sync here — the change handler clears input.value after this call
            }

            function syncInputFiles() {
                // Push current fileQueue back into the native file input
                var dt = new window.DataTransfer();
                fileQueue.forEach(function (f) { dt.items.add(f); });
                input.files = dt.files;
            }

            function removeFile(idx) {
                fileQueue.splice(idx, 1);
                if (errSize) errSize.style.display = 'none';
                if (errLimit) errLimit.style.display = 'none';
                renderPreviews();
            }

            function addFiles(newFiles) {
                if (errSize) errSize.style.display = 'none';
                if (errLimit) errLimit.style.display = 'none';

                var examCount = getExamCount();
                if (examCount === 0) {
                    if (errExamReq) errExamReq.style.display = 'flex';
                    examContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                var allowedExts = ['jpg', 'jpeg', 'png', 'dcm', 'dicom'];
                var incomingFiles = Array.from(newFiles);

                // If single exam and user selects 1 file, replace the previous selection smoothly
                if (examCount === 1 && incomingFiles.length === 1 && fileQueue.length > 0) {
                    fileQueue = [];
                }

                // Check overall limit
                if (fileQueue.length + incomingFiles.length > examCount) {
                    if (errLimitMsg) errLimitMsg.textContent = 'You can only upload ' + examCount + ' images for the ' + examCount + ' selected exams.';
                    if (errLimit) errLimit.style.display = 'flex';
                    return;
                }

                incomingFiles.forEach(function (file) {
                    if (file.size > MAX_BYTES) {
                        if (errSizeMsg) errSizeMsg.textContent = '"' + file.name + '" exceeds the 15 MB maximum size.';
                        if (errSize) errSize.style.display = 'flex';
                        return;
                    }

                    var parts = file.name.split('.');
                    var ext = parts[parts.length - 1].toLowerCase();
                    if (!allowedExts.includes(ext)) {
                        if (errSizeMsg) errSizeMsg.textContent = '"' + file.name + '" has an invalid format. Only DICOM, JPG, and PNG are allowed.';
                        if (errSize) errSize.style.display = 'flex';
                        return;
                    }

                    // Avoid duplicates by name+size
                    var dup = fileQueue.some(function (f) { return f.name === file.name && f.size === file.size; });
                    if (!dup) fileQueue.push(file);
                });

                renderPreviews();
            }

            // Form submit guard
            var form = document.getElementById('patient-details-form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    // Sync fileQueue → native input RIGHT before PHP receives the form
                    syncInputFiles();
                    var examCount = getExamCount();

                    var selectedCorrection = document.querySelector('input[name="correction_type"]:checked');
                    var isKeepImageMode = selectedCorrection && (selectedCorrection.value === 'typo' || selectedCorrection.value === 'reread');

                    if (examCount === 0) {
                        e.preventDefault();
                        if (errExamReq) { errExamReq.style.display = 'flex'; errExamReq.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        return;
                    }

                    if (!isKeepImageMode && fileQueue.length !== examCount) {
                        e.preventDefault();
                        if (errLimitMsg) errLimitMsg.textContent = 'Mismatch: You have ' + fileQueue.length + ' images but ' + examCount + ' exams selected. Please match the counts.';
                        if (errLimit) { errLimit.style.display = 'flex'; errLimit.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        return;
                    }

                    if (!isKeepImageMode && fileQueue.length === 0) {
                        e.preventDefault();
                        if (errNoImg) { errNoImg.style.display = 'flex'; errNoImg.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        dropZone.classList.add('border-red-500', 'bg-red-50');
                        setTimeout(function () { dropZone.classList.remove('border-red-500', 'bg-red-50'); }, 3000);
                    } else {
                        if (errNoImg) errNoImg.style.display = 'none';
                        if (errLimit) errLimit.style.display = 'none';
                    }
                });
            }

            input.addEventListener('change', function () { if (input.files.length) addFiles(input.files); input.value = ''; });

            dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.classList.add('bg-red-50'); });
            dropZone.addEventListener('dragleave', function () { dropZone.classList.remove('bg-red-50'); });
            dropZone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropZone.classList.remove('bg-red-50');
                if (e.dataTransfer && e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
            });

            // Listen for exam changes
            if (examContainer) {
                examContainer.addEventListener('exam-ms:change', function (e) {
                    var newCount = e.detail.count;
                    if (newCount > 0 && errExamReq) errExamReq.style.display = 'none';

                    // If exams reduced below current files, trim or warn
                    if (fileQueue.length > newCount) {
                        // For now we just warn and show the limit error
                        if (errLimitMsg) errLimitMsg.textContent = 'Please remove excess images. You have ' + fileQueue.length + ' images but only ' + newCount + ' exams selected.';
                        if (errLimit) errLimit.style.display = 'flex';
                    } else if (errLimit) {
                        errLimit.style.display = 'none';
                    }

                    updateCounter();
                    renderPreviews();
                });
            }

            // Sync on load
            updateCounter();
            renderPreviews();
        });
    </script>
<?php endif; ?>

<!-- Image Lightbox Modal -->
<div id="xray-lightbox-modal" class="fixed inset-0 z-[9999] hidden bg-black/80 backdrop-blur-sm flex flex-col items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative inline-block max-w-[95vw] max-h-[90vh]">
        <!-- Close Button -->
        <button type="button" onclick="closeXrayLightbox()" class="absolute text-black bg-white hover:bg-gray-200 rounded-full p-2 transition-colors z-10 shadow-lg border border-gray-300" style="top: -16px; right: -16px;" title="Close">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
        <!-- The Image -->
        <img id="xray-lightbox-main-img" src="" class="max-w-[95vw] max-h-[90vh] object-contain rounded-lg shadow-2xl scale-95 transition-transform duration-300 bg-black">
    </div>
</div>

<script>
function openXrayLightbox(src) {
    const modal = document.getElementById('xray-lightbox-modal');
    const img = document.getElementById('xray-lightbox-main-img');
    if (!modal || !img) return;
    img.src = src;
    
    // Disable background scrolling
    document.body.classList.add('overflow-hidden');
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Trigger animations
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        img.classList.remove('scale-95');
        img.classList.add('scale-100');
    }, 10);
    if (window.lucide) window.lucide.createIcons();
}

function closeXrayLightbox() {
    const modal = document.getElementById('xray-lightbox-modal');
    const img = document.getElementById('xray-lightbox-main-img');
    if (!modal || !img) return;
    
    // Reverse animations
    modal.classList.add('opacity-0');
    img.classList.remove('scale-100');
    img.classList.add('scale-95');
    
    // Enable background scrolling
    document.body.classList.remove('overflow-hidden');
    
    // Hide modal after animation completes
    setTimeout(() => {
        modal.classList.add('hidden');
        img.src = '';
    }, 300);
}

// Close lightbox on escape key
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('xray-lightbox-modal');
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
        closeXrayLightbox();
    }
});

// Close lightbox on background click
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('xray-lightbox-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeXrayLightbox();
            }
        });
    }
});
</script>