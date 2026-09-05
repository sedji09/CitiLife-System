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
$userRole = $_SESSION['role'] ?? 'radtech';
$from = $_GET['from'] ?? '';

if (!function_exists('getXrayImageLabel')) {
    function getXrayImageLabel($sPath, $idx = 0, $examType = '') {
        $baseName = pathinfo($sPath, PATHINFO_FILENAME);

        // 1. If saved with original file name: case_{caseId}_{time}_{idx}_{originalName}
        if (preg_match('/^case_\d+_\d+_\d+_(.+)$/', $baseName, $m)) {
            $name = trim($m[1]);
            return $name;
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

$from = $_GET['from'] ?? '';

if ($userRole === 'branch_admin' || $from === 'branch-xray-cases') {
    $backLink = "/" . PROJECT_DIR . "/index.php?page=branch-xray-cases";
} elseif ($userRole === 'admin_central' || $from === 'patient-records') {
    $backLink = "/" . PROJECT_DIR . "/index.php?page=patient-records";
} elseif ($from === 'report-ready') {
    $backLink = "/" . PROJECT_DIR . "/index.php?role=radtech&page=report-ready";
} elseif ($from === 'approval' || $from === 'patient-approval') {
    $backLink = "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-approval";
} elseif ($from === 'queue' || $from === 'patient-queue') {
    $backLink = "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-lists";
} elseif ($from === 'disputes') {
    $backLink = "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-lists&tab=disputes";
} elseif (!empty($activeDispute) || !empty($_GET['dispute_id'])) {
    $backLink = "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-lists&tab=disputes";
} else {
    $backLink = "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-lists";
}
?>

<!-- Header -->
<div class="flex items-center gap-4">
    <a href="<?= htmlspecialchars($backLink) ?>"
        id="patient-details-back-btn"
        class="flex w-10 h-10 items-center justify-center rounded-xl bg-white border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
        <i data-lucide="chevron-left" class="w-5 h-5"></i>
    </a>
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient Details</h2>
        <p class="text-sm text-gray-500 mt-1">
            <?= $userRole === 'branch_admin' ? 'View patient diagnostic examination details' : 'Diagnostic image upload and case management' ?>
        </p>
    </div>
</div>

<?php
// Determine amendment type based on reported issue
$dCategory  = $activeDispute['dispute_category'] ?? $activeDispute['category'] ?? '';
$dDescLower = strtolower($activeDispute['description'] ?? '');

$isTemplateOnly = ($dCategory === 'template_error');
$isBothTemplate = ($dCategory === 'both_template_error');
$isPureDemo     = ($dCategory === 'demographic_error');
$isBothTypo     = ($dCategory === 'both_error');
$isTypoOnly     = ($dCategory === 'findings_error');

$showFindings         = in_array($dCategory, ['findings_error', 'both_error', 'exam_details_error', 'other', 'other_error']);
$showXrayTemplateName = in_array($dCategory, ['template_error', 'both_template_error']);
$showDemographics     = false; // Patient demographics are managed via Fix Patient Demographics in the table

// Fallback detection from description if category wasn't exact
if (!$showFindings && !$showXrayTemplateName && !$isPureDemo) {
    if (strpos($dDescLower, 'template rename') !== false || strpos($dDescLower, 'correct template') !== false) {
        $showXrayTemplateName = true;
    } elseif (strpos($dDescLower, 'findings') !== false || strpos($dDescLower, 'typo') !== false) {
        $showFindings = true;
    }
}

// Extract patient's requested changes from dispute description
$dDesc = $activeDispute['description'] ?? '';
$reqCorrectTemplate = '';
$reqSide = '';
$reqNotes = '';
$reqFirstName = '';
$reqLastName = '';
$reqAge = '';
$reqSex = '';

if (preg_match('/Correct Template:\s*([^\n\r]+)/i', $dDesc, $m)) {
    $reqCorrectTemplate = trim($m[1]);
}
if (preg_match('/Selected Side:\s*([^\n\r]+)/i', $dDesc, $m)) {
    $reqSide = trim($m[1]);
}
if (preg_match('/Notes:\s*([^\n\r]+)/i', $dDesc, $m)) {
    $reqNotes = trim($m[1]);
}
if (preg_match('/First Name:\s*([^\n\r,]+)/i', $dDesc, $m)) {
    $reqFirstName = trim($m[1]);
}
if (preg_match('/Last Name:\s*([^\n\r,]+)/i', $dDesc, $m)) {
    $reqLastName = trim($m[1]);
}
if (preg_match('/(?:Age|Birthdate):\s*([^\n\r,]+)/i', $dDesc, $m)) {
    $rawVal = trim($m[1]);
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $rawVal, $dm)) {
        try {
            $bdate = new DateTime($dm[0]);
            $today = new DateTime();
            $reqAge = (string)$today->diff($bdate)->y;
        } catch (\Exception $e) {
            $reqAge = '';
        }
    } elseif (preg_match('/(\d+)/', $rawVal, $nm)) {
        $reqAge = $nm[1];
    }
}
if (preg_match('/Sex:\s*([^\n\r,]+)/i', $dDesc, $m)) {
    $reqSex = trim($m[1]);
}

// Banner text tailored to category
$amendBannerMsg = 'Amend Mode — Edit findings directly below and save when done.';
if ($showXrayTemplateName && $showFindings) {
    $amendBannerMsg = 'Amend Mode — Edit findings and rename X-ray template below and save when done.';
} elseif ($showXrayTemplateName) {
    $amendBannerMsg = 'Amend Mode — Rename X-ray template name below and save when done.';
} elseif ($showFindings) {
    $amendBannerMsg = 'Amend Mode — Edit findings directly below and save when done.';
}

$catBadgeLabel = match ($dCategory) {
    'findings_error'      => 'Typographical Error',
    'demographic_error'   => 'Patient Info Error',
    'template_error'      => 'Template Rename',
    'both_error'          => 'Typo & Info Error',
    'both_template_error' => 'Info & Template Rename',
    'exam_details_error'  => 'Exam Details Error',
    'other', 'other_error'=> 'Other Concern',
    default               => ucwords(str_replace('_', ' ', $dCategory ?: 'Correction Request'))
};
?>

<div id="patient-details-status-banner-container">
<?php if ($isReadOnly && $userRole === 'radtech' && !($isAmendMode ?? false)): ?>
    <?php if ($caseDetails['status'] === 'Report Ready'): ?>
        <div class="mt-5 rounded-lg bg-purple-50 border border-purple-300 p-4 flex items-center gap-3 shadow-xs">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-purple-600 shrink-0"></i>
            <p class="text-sm text-purple-800 font-medium">The radiologist report is ready. You can review the findings below and print the result.</p>
        </div>
    <?php elseif ($caseDetails['status'] === 'Completed' || $caseDetails['status'] === 'Released'): ?>
        <div class="mt-5 rounded-lg bg-green-50 border border-green-300 p-4 flex items-center gap-3 shadow-xs">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-600 shrink-0"></i>
            <p class="text-sm text-green-800 font-medium">This case has been completed and released.</p>
        </div>
    <?php elseif ($caseDetails['status'] === 'Under Reading'): ?>
        <div class="mt-5 rounded-lg bg-blue-50 border border-blue-300 p-4 flex items-center gap-3 shadow-xs">
            <i data-lucide="clock" class="w-5 h-5 text-blue-600 shrink-0"></i>
            <p class="text-sm text-blue-800 font-medium">This case is currently under reading by the radiologist.</p>
        </div>
    <?php else: ?>
        <div class="mt-5 rounded-lg bg-blue-50 border border-blue-300 p-4 flex items-center gap-3 shadow-xs">
            <i data-lucide="info" class="w-5 h-5 text-blue-600 shrink-0"></i>
            <p class="text-sm text-blue-800 font-medium">This case has already been submitted to the radiologist.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>
</div>

<?php if ($errorMsg): ?>
    <div class="mt-5 rounded-lg bg-red-50 border border-red-300 p-4 flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
        <p class="text-sm text-red-700"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<?php if ($successMsg ?? false): ?>
    <div
        class="mt-5 rounded-lg bg-green-50 border border-green-300 p-4 flex items-center gap-3 animate-in fade-in slide-in-from-top-2 duration-500">
        <i data-lucide="check-circle" class="w-5 h-5 text-green-600 shrink-0"></i>
        <p class="text-sm text-green-800 font-medium"><?= htmlspecialchars($successMsg) ?></p>
    </div>
<?php endif; ?>

<?php if (empty($activeDispute) && !($isAmendMode ?? false)): ?>
<form method="POST" action="" enctype="multipart/form-data" id="patient-details-form">
<?php endif; ?>
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
                    <label class="block text-gray-600 text-sm font-medium mb-1.5">Clinical Information /
                        Indication</label>
                    <textarea name="clinical_information" rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 <?= $isReadOnly ? 'opacity-70 cursor-not-allowed' : '' ?>"
                        placeholder="Enter patient symptoms, history, or clinical indication..." <?= $isReadOnly ? 'disabled' : '' ?>><?= htmlspecialchars($caseDetails['clinical_information'] ?? '') ?></textarea>
                </div>
                <div class="pt-1">
                    <span class="block text-gray-600 text-sm font-medium mb-1.5">Status</span>
                    <?php
                    if (!empty($activeDispute)) {
                        $displayStatus = $activeDispute['status'] ?? ($caseDetails['status'] ?: 'Pending');
                    } else {
                        $displayStatus = $caseDetails['status'] ?: 'Pending';
                    }
                    if ($displayStatus === 'Escalated to Radiologist') {
                        $displayStatus = 'Correction in Progress';
                    }
                    $isOverdue = (time() - strtotime($caseDetails['created_at'])) >= 3 * 3600;
                    if ($displayStatus === 'Pending' && $isOverdue) {
                        $displayStatus = 'Overdue';
                    }
                    if ($displayStatus === 'Completed' || $displayStatus === 'Resolved') {
                        $sBadge = 'border border-green-200 bg-green-50 text-green-700';
                    } elseif ($displayStatus === 'Correction Completed' || $displayStatus === 'Pending RadTech Verification') {
                        $sBadge = 'border border-blue-200 bg-blue-50 text-blue-700';
                    } elseif ($displayStatus === 'Correction in Progress') {
                        $sBadge = 'border border-indigo-200 bg-indigo-50 text-indigo-700';
                    } elseif ($displayStatus === 'For RadTech Review') {
                        $sBadge = 'border border-amber-200 bg-amber-50 text-amber-700';
                    } elseif (in_array($displayStatus, ['Issue Reported', 'Pending RadTech Review'])) {
                        $sBadge = 'border border-rose-200 bg-rose-50 text-rose-700';
                    } elseif ($displayStatus === 'Under Reading') {
                        $sBadge = 'border border-blue-200 bg-blue-50 text-blue-700';
                    } elseif ($displayStatus === 'Report Ready') {
                        $sBadge = 'border border-purple-200 bg-purple-50 text-purple-700';
                    } elseif ($displayStatus === 'Overdue' || $displayStatus === 'Rejected') {
                        $sBadge = 'border border-red-200 bg-red-50 text-red-700';
                    } else {
                        $sBadge = 'border border-yellow-200 bg-yellow-50 text-yellow-700';
                    }
                    ?>
                    <span id="case-status-badge" class="inline-block font-bold text-xs px-3 py-1.5 rounded-full transition-all duration-300 <?= $sBadge ?>">
                        <?= htmlspecialchars($displayStatus) ?>
                    </span>
                </div>
            </div>
        </div>

    </div>

    <?php $isReportReady = empty($activeDispute) && in_array($caseDetails['status'], ['Report Ready', 'Completed', 'Released']); ?>

    <?php if (!empty($activeDispute)): ?>
        <!-- 2-COLUMN DISPUTE RESOLUTION ROW -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
            <!-- ═════════════════════════════════════════════════════════════════
                 DISPUTE MODE: LEFT CARD is Diagnostic Image & Patient Issue
                 ═════════════════════════════════════════════════════════════════ -->
            <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col h-full space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-lg font-semibold text-gray-800">Diagnostic Image</h3>
                        <span class="text-xs font-semibold text-gray-500 bg-gray-100 border border-gray-200 rounded-full px-2.5 py-0.5">
                            <?= $existingCount = count(json_decode($caseDetails['image_path'] ?? '[]', true) ?: [$caseDetails['image_path'] ?? '']) ?> <?= $existingCount === 1 ? 'file' : 'files' ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">DICOM · JPG · JPEG · PNG — Max 15 MB per file</p>
                </div>

                <!-- Retained / Uploaded Image Section -->
                <div id="retained-image-section" class="space-y-3 flex-1">
                    <p class="text-xs text-gray-500">Click thumbnail to view full-screen image.</p>

                    <?php $existingPaths = json_decode($caseDetails['image_path'] ?? '[]', true) ?: [$caseDetails['image_path'] ?? '']; ?>
                    <?php if (!empty($existingPaths)): ?>
                        <div class="flex flex-wrap gap-4 pt-1">
                            <?php foreach ($existingPaths as $idx => $sPath): ?>
                                <?php $imgLabel = getXrayImageLabel($sPath, $idx, $caseDetails['exam_type'] ?? ''); ?>
                                <div onclick="openXrayLightbox('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>')"
                                     style="width: 128px; height: 128px; min-width: 128px; min-height: 128px;"
                                     class="group relative rounded-2xl overflow-hidden border-2 border-gray-300 hover:border-red-600 bg-black cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md shrink-0 flex items-center justify-center select-none"
                                     title="<?= htmlspecialchars($imgLabel) ?> — Click to view fullscreen">
                                    <img src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>" alt="<?= htmlspecialchars($imgLabel) ?>"
                                         style="width: 100%; height: 100%; object-fit: contain;"
                                         class="opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-200">

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
                        <p class="text-xs text-gray-400 italic">No existing image found.</p>
                    <?php endif; ?>
                </div>

            </div>

                <!-- ═════════════════════════════════════════════════════════════════
                     AMEND MODE: RIGHT CARD is Edit / Amend Container
                     ═════════════════════════════════════════════════════════════════ -->
                <?php
                $isEdited = (isset($_GET['saved']) && $_GET['saved'] == '1')
                    || (!empty($caseDetails['is_amended']) && (int)$caseDetails['is_amended'] === 1)
                    || in_array($activeDispute['status'] ?? '', ['Resolved', 'Correction Completed']);
                ?>
                <div class="rounded-xl border border-amber-300 bg-white shadow-sm flex flex-col h-full overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100">
                        <div class="flex items-center gap-2">
                            <i data-lucide="edit-3" class="h-4 w-4 text-amber-600"></i>
                            <h3 class="text-sm font-bold text-gray-800">
                                <?php if ($showXrayTemplateName && $showFindings): ?>
                                    Edit Report &amp; Rename X-ray Template
                                <?php elseif ($showXrayTemplateName): ?>
                                    Rename X-ray Template
                                <?php else: ?>
                                    Edit Report Findings
                                <?php endif; ?>
                            </h3>
                        </div>
                        <?php if ($isEdited): ?>
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-300 px-3 py-1 rounded-full flex items-center gap-1.5 shadow-2xs shrink-0">
                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 stroke-[2.5]"></i>
                                Edited
                            </span>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="" class="p-6 space-y-5 flex-1 flex flex-col">
                        <input type="hidden" name="save_amendment" value="1">
                        <input type="hidden" name="dispute_id" value="<?= (int)($activeDispute['id'] ?? 0) ?>">

                        <!-- 1. TEMPLATE RENAME CONTROLS (Categories 3 & 5) -->
                        <?php if ($showXrayTemplateName): ?>
                            <div class="space-y-4">
                                <?php if ($reqCorrectTemplate || $reqNotes || $reqSide): ?>
                                    <!-- Patient Reported Rename Callout -->
                                    <div class="rounded-xl bg-gray-50/80 border border-gray-300 p-4 px-5 py-4 text-xs text-black space-y-2" style="padding: 1rem 1.25rem;">
                                        <div class="flex items-center justify-between font-semibold text-black">
                                            <span>Patient Requested Change:</span>
                                            <?php if ($reqSide): ?>
                                                <span class="px-2 py-0.5 rounded bg-white text-gray-800 font-semibold text-[11px] border border-gray-300 uppercase tracking-wider">Side: <?= htmlspecialchars($reqSide) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($reqCorrectTemplate): ?>
                                            <p class="text-black font-normal">Desired Template: <strong class="font-bold text-black"><?= htmlspecialchars($reqCorrectTemplate) ?></strong></p>
                                        <?php endif; ?>
                                        <?php if ($reqNotes): ?>
                                            <p class="text-gray-700 italic">Notes: "<?= htmlspecialchars($reqNotes) ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Template Rename Input & Quick Modifiers -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">X-RAY TEMPLATE / EXAM NAME</label>
                                        <?php if (!$isEdited): ?>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs text-gray-400 font-medium mr-0.5">Quick Side:</span>
                                                <button type="button" onclick="applyRadtechSidePrefix('Left')" class="px-2.5 py-1 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md border border-gray-200 transition cursor-pointer active:scale-95">Left</button>
                                                <button type="button" onclick="applyRadtechSidePrefix('Right')" class="px-2.5 py-1 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md border border-gray-200 transition cursor-pointer active:scale-95">Right</button>
                                                <button type="button" onclick="applyRadtechSidePrefix('Bilateral')" class="px-2.5 py-1 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md border border-gray-200 transition cursor-pointer active:scale-95">Bilateral</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="relative">
                                        <input type="text"
                                               id="amend_exam_type_input"
                                               name="amend_exam_type"
                                               value="<?= htmlspecialchars($reqCorrectTemplate ?: ($caseDetails['exam_type'] ?? '')) ?>"
                                               <?= $isEdited ? 'readonly disabled' : '' ?>
                                               placeholder="e.g. Left Knee AP / Lateral"
                                               class="w-full text-sm font-semibold p-3 rounded-lg border <?= $isEdited ? 'border-gray-200 bg-gray-100/80 text-gray-700 cursor-not-allowed select-text' : 'border-gray-300 bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-100' ?> outline-none transition">
                                    </div>
                                    <div class="text-xs text-gray-500 flex items-center gap-1.5 pt-0.5">
                                        <span>Current record:</span>
                                        <strong class="font-bold text-black"><?= htmlspecialchars($caseDetails['exam_type'] ?? '') ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>


                        <!-- 3. REPORT FINDINGS & IMPRESSION (Categories 2, 4, 6 - Omitted for 3 & 5) -->
                        <?php if ($showFindings): ?>
                            <?php
                            // Pre-populate previous findings & impression so RadTech can just edit the typo
                            $currentFindings   = trim($caseDetails['findings'] ?? '');
                            $currentImpression = trim($caseDetails['impression'] ?? '');

                            // Fallback to activeDispute snapshot if case fields were empty
                            if (empty($currentFindings) && !empty($activeDispute['old_findings'])) {
                                $currentFindings = trim($activeDispute['old_findings']);
                            }
                            if (empty($currentImpression) && !empty($activeDispute['old_impression'])) {
                                $currentImpression = trim($activeDispute['old_impression']);
                            }

                            // If stored as JSON (multi-exam format), format cleanly as readable text
                            if (!empty($currentFindings) && ($currentFindings[0] === '{' || $currentFindings[0] === '[')) {
                                $decodedFindings = json_decode($currentFindings, true);
                                if (is_array($decodedFindings)) {
                                    $fParts = [];
                                    $iParts = [];
                                    foreach ($decodedFindings as $eKey => $eData) {
                                        if (is_array($eData)) {
                                            if (!empty($eData['findings'])) {
                                                $fParts[] = (count($decodedFindings) > 1 ? "[$eKey]\n" : '') . trim($eData['findings']);
                                            }
                                            if (!empty($eData['impression'])) {
                                                $iParts[] = (count($decodedFindings) > 1 ? "[$eKey]\n" : '') . trim($eData['impression']);
                                            }
                                        } elseif (is_string($eData)) {
                                            $fParts[] = trim($eData);
                                        }
                                    }
                                    if (!empty($fParts)) {
                                        $currentFindings = implode("\n\n", $fParts);
                                    }
                                    if (empty($currentImpression) && !empty($iParts)) {
                                        $currentImpression = implode("\n\n", $iParts);
                                    }
                                }
                            }

                            // Standard template fallback if still blank
                            if (empty($currentFindings)) {
                                $examUpper = strtoupper(trim($caseDetails['exam_type'] ?? ''));
                                if (strpos($examUpper, 'CHEST') !== false) {
                                    $currentFindings = "The lung fields are clear without evidence of focal consolidation, mass, or infiltrates. The cardiac silhouette is within normal limits in size and configuration. The costophrenic angles are sharp and well-defined. No pleural effusion or pneumothorax is seen. The visualized osseous structures are intact.";
                                    if (empty($currentImpression)) {
                                        $currentImpression = "No radiographic evidence of active cardiopulmonary disease.";
                                    }
                                } elseif (strpos($examUpper, 'ABDOMEN') !== false) {
                                    $currentFindings = "There is a normal distribution of bowel gas within the abdomen. No dilated bowel loops or abnormal air-fluid levels are seen. No radiopaque foreign bodies or abnormal calcifications are identified. The soft tissue shadows are within normal limits, and the visualized bony structures appear intact.";
                                    if (empty($currentImpression)) {
                                        $currentImpression = "No radiographic evidence of acute intra-abdominal pathology.";
                                    }
                                } elseif (strpos($examUpper, 'SKULL') !== false || strpos($examUpper, 'PARANASAL') !== false || strpos($examUpper, 'PNS') !== false) {
                                    $currentFindings = "The cranial vault and visualized facial bones show normal configuration and bone density. No evidence of fracture or focal lytic or blastic bone lesion. Paranasal sinuses and mastoid air cells appear normally aerated.";
                                    if (empty($currentImpression)) {
                                        $currentImpression = "No radiographic evidence of acute cranial or facial bone injury.";
                                    }
                                } else {
                                    $currentFindings = "The visualized osseous structures demonstrate normal alignment and density. No evidence of fracture or dislocation is seen. Joint spaces are well maintained, and there is no significant soft tissue swelling or abnormal calcification.";
                                    if (empty($currentImpression)) {
                                        $currentImpression = "No acute bony abnormality.";
                                    }
                                }
                            }

                            if (empty($currentImpression)) {
                                $examUpper = strtoupper(trim($caseDetails['exam_type'] ?? ''));
                                if (strpos($examUpper, 'CHEST') !== false) {
                                    $currentImpression = "No radiographic evidence of active cardiopulmonary disease.";
                                } elseif (strpos($examUpper, 'ABDOMEN') !== false) {
                                    $currentImpression = "No radiographic evidence of acute intra-abdominal pathology.";
                                } else {
                                    $currentImpression = "No acute bony abnormality.";
                                }
                            }
                            ?>

                            <!-- Findings -->
                            <div class="flex-1 flex flex-col">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">FINDINGS</label>
                                </div>
                                <textarea name="amend_findings" rows="5"
                                          <?= $isEdited ? 'readonly disabled' : '' ?>
                                          class="w-full flex-1 text-sm font-mono p-3 rounded-xl border <?= $isEdited ? 'border-gray-200 bg-gray-100/80 text-gray-700 cursor-not-allowed select-text' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100' ?> outline-none leading-relaxed resize-y transition"
                                          placeholder="Enter or correct findings…"><?= htmlspecialchars($currentFindings) ?></textarea>
                            </div>

                            <!-- Impression -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">IMPRESSION</label>
                                </div>
                                <textarea name="amend_impression" rows="3"
                                          <?= $isEdited ? 'readonly disabled' : '' ?>
                                          class="w-full text-sm font-mono p-3 rounded-xl border <?= $isEdited ? 'border-gray-200 bg-gray-100/80 text-gray-700 cursor-not-allowed select-text' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100' ?> outline-none leading-relaxed resize-y transition"
                                          placeholder="Enter or correct impression…"><?= htmlspecialchars($currentImpression) ?></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if (!$isEdited): ?>
                            <!-- Single Action Button -->
                            <div class="flex items-center justify-end pt-3 border-t border-gray-100 mt-auto">
                                <button type="button"
                                        onclick="submitRadtechAmendment(this, event);"
                                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition shadow-sm active:scale-95 cursor-pointer">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    Save &amp; Resolve
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
        </div>

        <script>
        function applyRadtechSidePrefix(side) {
            const inp = document.getElementById('amend_exam_type_input');
            if (!inp || inp.disabled || inp.readOnly) return;
            let val = inp.value.trim();
            val = val.replace(/^(Left|Right|Bilateral)\s+/i, '');
            inp.value = side + (val ? ' ' + val : '');
            inp.focus();
            inp.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function submitRadtechAmendment(btn, e) {
            const form = btn.closest('form');
            if (!form) return;

            const showFindings = <?= json_encode($showFindings) ?>;
            const showRename   = <?= json_encode($showXrayTemplateName) ?>;

            if (showFindings) {
                const f = form.querySelector('textarea[name="amend_findings"]');
                if (f && !f.value.trim()) {
                    Swal.fire('Findings Required', 'Please enter the report findings before saving.', 'warning');
                    f.focus();
                    return;
                }
            }

            if (showRename) {
                const t = form.querySelector('input[name="amend_exam_type"]');
                if (t && !t.value.trim()) {
                    Swal.fire('Exam Template Required', 'Please specify the corrected X-ray template / body part name.', 'warning');
                    t.focus();
                    return;
                }
            }

            confirmFormAction(
                btn,
                'save_and_release',
                'Confirm Save & Resolve',
                'Would you like to save these amendments and resolve this error report? The updated record will be marked as Resolved and released.',
                'amendment_action',
                e
            );
        }

        <?php if (!$isEdited): ?>
        // ── RadTech Amendment Activity Tracking (Typing Indicator) ────────
        (function() {
            let amendPingInterval = null;
            let radtechActivityStatus = 'viewing';
            let lastAmendTypedTime = 0;
            const currentCaseId = <?= (int)($caseId ?? ($caseDetails['id'] ?? 0)) ?>;
            if (!currentCaseId) return;

            function sendAmendPing() {
                if (document.visibilityState === 'hidden') return;
                if (radtechActivityStatus === 'typing' && (Date.now() - lastAmendTypedTime > 5000)) {
                    radtechActivityStatus = 'viewing';
                }
                const fd = new FormData();
                fd.append('status', radtechActivityStatus);
                fetch(`<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/case_activity.php?action=ping&case_id=${currentCaseId}`, {
                    method: 'POST',
                    body: fd
                }).catch(err => console.debug('Amend ping error:', err));
            }

            function sendAmendInactivePing() {
                if (amendPingInterval) {
                    clearInterval(amendPingInterval);
                    amendPingInterval = null;
                }
                const pingUrl = `<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/case_activity.php?action=ping&case_id=${currentCaseId}&status=inactive`;
                const fd = new FormData();
                fd.append('status', 'inactive');
                try {
                    navigator.sendBeacon(pingUrl, fd);
                } catch(e) {}
                try {
                    fetch(pingUrl, {
                        method: 'POST', body: fd, keepalive: true
                    }).catch(()=>{});
                } catch(e) {}
            }

            // Listen for typing/input on all amendment form fields
            const amendInputs = document.querySelectorAll(
                '#amend_exam_type_input, input[name="amend_first_name"], input[name="amend_middle_name"], input[name="amend_last_name"], input[name="amend_age"], select[name="amend_sex"], textarea[name="amend_findings"], textarea[name="amend_impression"]'
            );
            amendInputs.forEach(el => {
                const onInput = () => {
                    lastAmendTypedTime = Date.now();
                    if (radtechActivityStatus !== 'typing') {
                        radtechActivityStatus = 'typing';
                        sendAmendPing();
                    }
                };
                el.addEventListener('input', onInput);
                el.addEventListener('change', onInput);
            });

            // Initial viewing ping and continuous 2.5s ping
            sendAmendPing();
            amendPingInterval = setInterval(sendAmendPing, 2500);

            // Inactive beacons on leaving the page or clicking back
            window.addEventListener('beforeunload', sendAmendInactivePing);
            window.addEventListener('pagehide', sendAmendInactivePing);
            window.addEventListener('popstate', sendAmendInactivePing);

            document.querySelectorAll('#patient-details-back-btn, a[href*="role=radtech"], button.back-btn, #back-btn, a[href*="page="], a[href*="tab="]').forEach(el => {
                el.addEventListener('click', sendAmendInactivePing);
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    sendAmendInactivePing();
                } else {
                    radtechActivityStatus = 'viewing';
                    if (!amendPingInterval) {
                        sendAmendPing();
                        amendPingInterval = setInterval(sendAmendPing, 2500);
                    }
                }
            });
        })();
        <?php endif; ?>
        </script>

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
                                                <?php $imgLabel = getXrayImageLabel($sPath, $idx, $caseDetails['exam_type'] ?? ''); ?>
                                                <div onclick="openXrayLightbox('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>')"
                                                    style="width: 128px; height: 128px; min-width: 128px; min-height: 128px;"
                                                    class="group relative rounded-2xl overflow-hidden border-2 border-gray-300 hover:border-red-600 bg-black cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md shrink-0 flex items-center justify-center select-none"
                                                    title="<?= htmlspecialchars($imgLabel) ?> — Click to view fullscreen">
                                                    <img src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?><?= htmlspecialchars($sPath) ?>" alt="<?= htmlspecialchars($imgLabel) ?>"
                                                        style="width: 100%; height: 100%; object-fit: contain;"
                                                        class="opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-200">
                                    
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

                <div id="radiologist-findings-wrapper" class="flex flex-col h-full">
                <?php if ($isReadOnly): ?>
                    <!-- READ-ONLY FINDINGS CARD -->
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
                    </div><!-- end read-only card -->
                <?php endif; ?><!-- end isReadOnly -->
                </div>

            </div>
    <?php endif; ?>

    <?php if (empty($activeDispute) && !($isAmendMode ?? false)): ?>
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
    <div id="patient-details-action-buttons" class="mt-6 flex gap-4 items-center">
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
                    onclick="if(!document.getElementById('radiologist_id').value){ const err = document.getElementById('rad-selection-error'); err.classList.remove('hidden'); setTimeout(() => err.classList.add('hidden'), 5000); lucide.createIcons(); return; } confirmFormAction(this, '1', 'Confirm Submission', 'Would you like to confirm submitting this case?', 'submit_radiologist', event)"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition shadow-sm h-full">
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
<?php endif; ?>


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

    // Smart return to originating table (Patient Error Reports, Patient Queue, Report Ready, etc.)
    const backBtn = document.getElementById('patient-details-back-btn');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            try {
                const lastTableUrl = sessionStorage.getItem('radtech_last_table_url');
                if (lastTableUrl && (
                    lastTableUrl.includes('page=patient-lists') ||
                    lastTableUrl.includes('page=patient-approval') ||
                    lastTableUrl.includes('page=report-ready') ||
                    lastTableUrl.includes('page=branch-xray-cases') ||
                    lastTableUrl.includes('page=patient-records')
                )) {
                    e.preventDefault();
                    window.location.href = lastTableUrl;
                }
            } catch (err) {}
        });
    }
});

// Real-time status sync for RadTech Patient Details via AJAX (no page reload)
(function() {
    const caseId = <?= (int)($caseId ?? ($caseDetails['id'] ?? 0)) ?>;
    if (!caseId) return;

    let currentCaseStatus = <?= json_encode($displayStatus) ?>;

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'Completed':
            case 'Resolved':
                return 'border border-green-200 bg-green-50 text-green-700';
            case 'Correction Completed':
            case 'Pending RadTech Verification':
                return 'border border-blue-200 bg-blue-50 text-blue-700';
            case 'Correction in Progress':
                return 'border border-indigo-200 bg-indigo-50 text-indigo-700';
            case 'For RadTech Review':
                return 'border border-amber-200 bg-amber-50 text-amber-700';
            case 'Issue Reported':
            case 'Pending RadTech Review':
                return 'border border-rose-200 bg-rose-50 text-rose-700';
            case 'Under Reading':
                return 'border border-blue-200 bg-blue-50 text-blue-700';
            case 'Report Ready':
                return 'border border-purple-200 bg-purple-50 text-purple-700';
            case 'Overdue':
            case 'Rejected':
                return 'border border-red-200 bg-red-50 text-red-700';
            default:
                return 'border border-yellow-200 bg-yellow-50 text-yellow-700';
        }
    }

    let isSyncing = false;
    function syncCaseDetailsViaAjax(newStatus) {
        if (isSyncing) return;
        isSyncing = true;

        // Immediate visual feedback on status badge
        const badge = document.getElementById('case-status-badge');
        if (badge) {
            badge.className = 'inline-block font-bold text-xs px-3 py-1.5 rounded-full transition-all duration-300 ' + getStatusBadgeClass(newStatus);
            badge.textContent = newStatus;
            badge.classList.add('scale-110', 'ring-2', 'ring-purple-400');
            setTimeout(() => {
                badge.classList.remove('scale-110', 'ring-2', 'ring-purple-400');
            }, 600);
        }

        // Fetch fresh rendered page sections via AJAX (NO PAGE RELOAD)
        const syncUrl = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_ajax_sync=1&_t=' + Date.now();
        fetch(syncUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // 1. Update status banner
                const curBanner = document.getElementById('patient-details-status-banner-container');
                const newBanner = doc.getElementById('patient-details-status-banner-container');
                if (curBanner && newBanner) {
                    curBanner.innerHTML = newBanner.innerHTML;
                }

                // 2. Update findings card
                const curFindings = document.getElementById('radiologist-findings-wrapper');
                const newFindings = doc.getElementById('radiologist-findings-wrapper');
                if (curFindings && newFindings) {
                    curFindings.innerHTML = newFindings.innerHTML;
                }

                // 3. Update action buttons
                const curActions = document.getElementById('patient-details-action-buttons');
                const newActions = doc.getElementById('patient-details-action-buttons');
                if (curActions && newActions) {
                    curActions.innerHTML = newActions.innerHTML;
                }

                // 4. Update status badge with precise server-side classes and text
                const curBadge = document.getElementById('case-status-badge');
                const freshBadge = doc.getElementById('case-status-badge');
                if (curBadge && freshBadge) {
                    curBadge.className = freshBadge.className;
                    curBadge.textContent = freshBadge.textContent;
                }

                // Re-initialize Lucide icons for new elements
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            })
            .catch(err => {
                console.error('AJAX sync failed:', err);
            })
            .finally(() => {
                isSyncing = false;
            });
    }

    const pollInterval = setInterval(() => {
        if (document.visibilityState === 'hidden') return;

        fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/case_activity.php?action=status&case_id=' + caseId + '&_t=' + Date.now())
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success) return;

                const newStatus = data.display_status || data.case_status;
                if (newStatus && newStatus !== currentCaseStatus) {
                    currentCaseStatus = newStatus;
                    syncCaseDetailsViaAjax(newStatus);
                }
            })
            .catch(() => {});
    }, 2500);

    window.addEventListener('beforeunload', () => clearInterval(pollInterval));
})();
</script>