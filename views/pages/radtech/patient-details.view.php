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
    <a href="?role=radtech&page=patient-lists" class="text-gray-500 hover:text-gray-700 transition">
        <i data-lucide="arrow-left" class="w-5 h-5"></i>
    </a>
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient Details</h2>
        <p class="text-sm text-gray-500 mt-1">Diagnostic image upload and case management</p>
    </div>
</div>

<?php if ($isReadOnly): ?>
    <div class="mt-5 rounded-lg bg-blue-50 border border-blue-300 p-4 flex items-center gap-3">
        <i data-lucide="info" class="w-5 h-5 text-blue-600 shrink-0"></i>
        <p class="text-sm text-blue-800 font-medium">This case has already been submitted to the radiologist. Showing saved
            information in read-only mode.</p>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mt-5 rounded-lg bg-red-50 border border-red-300 p-4 flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
        <p class="text-sm text-red-700"><?= htmlspecialchars($errorMsg) ?></p>
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
            <div class="rounded-lg bg-red-50 border border-red-300 p-4">
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
                </div>
            </div>
        </div>

        <!-- Examination Details -->
        <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Examination Details</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1.5">Exam Types <span
                            class="text-red-500">*</span></label>
                    <?php
                    $examInputName = 'exam_type';
                    $preSelectedExams = $caseDetails['exam_type'] ?? '';
                    require __DIR__ . '/../../components/exam-selector.php';
                    ?>
                </div>
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1.5">Priority</label>
                    <?php
                    $priorities = ['Routine', 'Urgent', 'Emergency'];
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

    <!-- Image Upload -->
    <div class="mt-8 rounded-xl border border-gray-300 bg-white p-6 shadow-sm">
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
            <p id="file-size-error-msg" class="text-sm text-red-700 font-medium">File exceeds the 15 MB maximum size.
            </p>
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
                        <div
                            class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                        </div>
                        <p class="text-sm font-semibold text-red-600 mb-1">Click or drag X-ray files here</p>
                        <p class="text-xs text-gray-400">Patient:
                            <?= htmlspecialchars($caseDetails['first_name'] . ' ' . $caseDetails['last_name']) ?>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">Max <strong class="text-gray-500">15 MB</strong> per file</p>
                    </div>
                    <input type="file" id="xray_file_input" name="xray_image[]" accept=".jpg,.jpeg,.png,.dcm,.dicom" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" multiple>
                </div>

                <!-- Preview list -->
                <div id="file-preview-area"
                    style="display:flex;flex-direction:column;gap:0.6rem;max-height:22rem;overflow-y:auto;">
                    <p id="no-file-msg" style="font-size:0.875rem;color:#9ca3af;font-style:italic;">No files selected yet.
                    </p>
                </div>
            </div>

        <?php else: ?>
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
            <?php elseif ($caseDetails['image_status'] === 'Uploaded'): ?>
                <div
                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;color:#16a34a;">
                    <i data-lucide="check-square" style="width:2rem;height:2rem;margin-bottom:0.5rem;"></i>
                    <span style="font-weight:500;">Images successfully uploaded</span>
                </div>
            <?php else: ?>
                <p style="color:#9ca3af;font-size:0.875rem;font-style:italic;">No images uploaded yet.</p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Action Buttons -->
        <?php $isReportReady = in_array($caseDetails['status'], ['Report Ready', 'Completed']); ?>
        <div class="mt-8 flex gap-4">
            <?php if (!$isReadOnly): ?>
                <button type="button" 
                    onclick="confirmFormAction(this, '1', 'Confirm Submission', 'Would you like to confirm submitting this case to the Radiologist?', 'submit_radiologist', event)"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition shadow-sm">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit to Radiologist
                </button>
            <?php else: ?>
                <button type="button" disabled
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-bold text-gray-400 cursor-not-allowed shadow-sm">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Already Submitted
                </button>
            <?php endif; ?>

            <?php if ($isReportReady): ?>
                <a href="javascript:void(0)" 
                    onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '/<?= PROJECT_DIR ?>/app/views/pages/radtech/print-report.php?id=<?= $caseId ?>', 'Yes, Print', true, event)"
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
                if (!examHidden) return 0;
                var val = examHidden.value.trim();
                return val ? val.split(',').filter(s => s.trim()).length : 0;
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
                    
                    if (examCount === 0) {
                        e.preventDefault();
                        if (errExamReq) { errExamReq.style.display = 'flex'; errExamReq.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        return;
                    }

                    if (fileQueue.length !== examCount) {
                        e.preventDefault();
                        if (errLimitMsg) errLimitMsg.textContent = 'Mismatch: You have ' + fileQueue.length + ' images but ' + examCount + ' exams selected. Please match the counts.';
                        if (errLimit) { errLimit.style.display = 'flex'; errLimit.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        return;
                    }

                    if (fileQueue.length === 0) {
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
                examContainer.addEventListener('exam-ms:change', function(e) {
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