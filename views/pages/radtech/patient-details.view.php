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

<!-- Modern Custom DatePicker -->
<link rel="stylesheet" href="<?= url('public/assets/css/custom-datepicker.css') ?>?v=<?= time() ?>">
<script src="<?= url('public/assets/js/custom-datepicker.js') ?>?v=<?= time() ?>"></script>

<!-- html2canvas for Report Release snapshot -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

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
    $backLink = url('branch-xray-cases');
} elseif ($userRole === 'admin_central' || $from === 'patient-records') {
    $backLink = url('patient-records');
} elseif ($from === 'report-ready') {
    $backLink = url('report-ready');
} elseif ($from === 'approval' || $from === 'patient-approval') {
    $backLink = url('patient-approval');
} elseif ($from === 'queue' || $from === 'patient-queue') {
    $backLink = url('patient-lists');
} elseif ($from === 'disputes' || !empty($activeDispute) || !empty($_GET['dispute_id'])) {
    $backLink = url('patient-lists?tab=disputes');
} else {
    $backLink = url('patient-lists');
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

<?php if (empty($activeDispute) && !($isAmendMode ?? false)): ?>
<form method="POST" action="" enctype="multipart/form-data" id="patient-details-form">
<?php endif; ?>
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Patient Verification -->
        <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="user-check" class="h-5 w-5 text-green-600"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Patient Verification</h3>
                </div>
                <?php if ($userRole === 'radtech' && !($isReadOnly && empty($activeDispute) && ($caseDetails['image_status'] ?? '') === 'Uploaded')): ?>
                    <button type="button" onclick="openEditPatientInfoModal()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-300 hover:bg-amber-100 hover:border-amber-400 transition shadow-2xs cursor-pointer">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Edit Patient Information</span>
                    </button>
                <?php endif; ?>
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
                        <span class="text-gray-600">Home Address</span>
                        <span
                            class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['home_address'] ?? '—') ?></span>
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
                                class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['philhealth_id']) ?><?= !empty($caseDetails['philhealth_relation']) ? ' (' . htmlspecialchars($caseDetails['philhealth_relation']) . ')' : '' ?></span>
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
                    $sBorder = '1.5px solid #facc15';
                    $sBg = '#fefce8';
                    $sColor = '#a16207';
                    if ($displayStatus === 'Completed' || $displayStatus === 'Resolved') {
                        $sBorder = '1.5px solid #4ade80';
                        $sBg = '#f0fdf4';
                        $sColor = '#15803d';
                    } elseif ($displayStatus === 'Correction Completed' || $displayStatus === 'Pending RadTech Verification') {
                        $sBorder = '1.5px solid #60a5fa';
                        $sBg = '#eff6ff';
                        $sColor = '#1d4ed8';
                    } elseif ($displayStatus === 'Correction in Progress') {
                        $sBorder = '1.5px solid #818cf8';
                        $sBg = '#eef2ff';
                        $sColor = '#4338ca';
                    } elseif ($displayStatus === 'For RadTech Review') {
                        $sBorder = '1.5px solid #facc15';
                        $sBg = '#fefce8';
                        $sColor = '#a16207';
                    } elseif (in_array($displayStatus, ['Issue Reported', 'Pending RadTech Review'])) {
                        $sBorder = '1.5px solid #fb7185';
                        $sBg = '#fff1f2';
                        $sColor = '#be123c';
                    } elseif ($displayStatus === 'Under Reading') {
                        $sBorder = '1.5px solid #60a5fa';
                        $sBg = '#eff6ff';
                        $sColor = '#1d4ed8';
                    } elseif ($displayStatus === 'Report Ready') {
                        $sBorder = '1.5px solid #818cf8';
                        $sBg = '#eef2ff';
                        $sColor = '#4338ca';
                    } elseif ($displayStatus === 'Overdue' || $displayStatus === 'Rejected') {
                        $sBorder = '1.5px solid #f87171';
                        $sBg = '#fef2f2';
                        $sColor = '#b91c1c';
                    }
                    ?>
                    <span id="case-status-badge" class="inline-block font-bold text-xs px-3 py-1.5 rounded-full transition-all duration-300"
                        style="border:<?= $sBorder ?>;background-color:<?= $sBg ?>;color:<?= $sColor ?>">
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
                    || in_array($activeDispute['status'] ?? '', ['Resolved', 'Correction Completed', 'Pending RadTech Verification']);
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
                    if (window.FormValidator) window.FormValidator.showError(f, 'Please enter the report findings before saving.');
                    if (typeof toast === 'function') toast('Please enter the report findings.', 'error');
                    f.focus();
                    return;
                }
            }

            if (showRename) {
                const t = form.querySelector('input[name="amend_exam_type"]');
                if (t && !t.value.trim()) {
                    if (window.FormValidator) window.FormValidator.showError(t, 'Please specify the corrected X-ray template / body part name.');
                    if (typeof toast === 'function') toast('Please specify the corrected template name.', 'error');
                    t.focus();
                    return;
                }
            }

            confirmFormAction(
                btn,
                'save_and_release',
                'Confirm Save & Resolve',
                'Would you like to save these amendments and resolve this correction request? The updated record will be marked as Resolved and released.',
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
                                        <p class="text-xs text-gray-400">Patient: <?= htmlspecialchars(formatFullName($caseDetails)) ?></p>
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
                <?php if ($isReportReady): ?>
                    <?php if (!empty($caseDetails['released']) && (int)$caseDetails['released'] === 1): ?>
                        <button type="button" disabled
                            class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-bold text-gray-400 cursor-not-allowed shadow-sm">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            Already Released
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="releaseToPhoto(<?= $caseId ?>, this, event)"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition shadow-sm cursor-pointer">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Release Result
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button type="button" disabled
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-bold text-gray-400 cursor-not-allowed shadow-sm">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Already Submitted
                    </button>
                <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($activeDispute)): ?>
                <?php if ($isReportReady): ?>
                    <?php if (empty($caseDetails['released']) || (int)$caseDetails['released'] === 0): ?>
                        <button type="button" onclick="triggerReEdit(<?= $caseId ?>, this, event)"
                            class="inline-flex items-center gap-2 rounded-lg border border-yellow-400 bg-yellow-50 px-5 py-2.5 text-sm font-semibold text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 transition shadow-sm cursor-pointer"
                            title="Revert report to draft so radiologist can edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v5"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <path d="M20 8v12a2 2 0 0 1-2 2h-7"/>
                                <path d="M3 15a4.5 4.5 0 0 1 7.5-2.5"/>
                                <polyline points="7.5 9.5 11 12 7.5 14.5"/>
                                <path d="M11 17a4.5 4.5 0 0 1-7.5 2.5"/>
                                <polyline points="6.5 22.5 3 20 6.5 17.5"/>
                            </svg>
                            Allow Re-edit
                        </button>
                    <?php endif; ?>
                        <a href="javascript:void(0)"
                            onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '<?= url('print-report?id=' . $caseId) ?>', 'Yes, Print', true, event)"
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

<!-- Edit Patient Information Modal (RadTech) -->
<div id="edit-patient-info-modal" class="fixed inset-0 z-[9999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full border border-gray-100 my-8 overflow-hidden transform transition-all">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                    <i data-lucide="user-pen" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Edit Patient Information</h3>
                    <p class="text-xs text-gray-500">Correct demographic and PhilHealth details in person</p>
                </div>
            </div>
            <button type="button" onclick="closeEditPatientInfoModal()" class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-white/80 transition flex items-center justify-center cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form method="POST" action="" id="edit-patient-info-form" onsubmit="return submitEditPatientInfo(event);" class="p-6 space-y-4">
            <input type="hidden" name="update_patient_info" value="1">
            <input type="hidden" name="patient_id" value="<?= (int)($caseDetails['patient_id'] ?? 0) ?>">
            <input type="hidden" name="case_id" value="<?= (int)($caseDetails['id'] ?? 0) ?>">

            <!-- First & Last Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" id="modal_pat_first_name" required
                        value="<?= htmlspecialchars($caseDetails['first_name'] ?? '') ?>"
                        class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl p-2.5 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" id="modal_pat_last_name" required
                        value="<?= htmlspecialchars($caseDetails['last_name'] ?? '') ?>"
                        class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl p-2.5 outline-none transition">
                </div>
            </div>

            <!-- Middle Name (Optional) -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Middle Name <span class="text-gray-400 font-normal text-[11px]">(Optional)</span></label>
                <input type="text" name="middle_name" id="modal_pat_middle_name"
                    value="<?= htmlspecialchars($caseDetails['middle_name'] ?? '') ?>"
                    placeholder="Middle name or initial"
                    class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl p-2.5 outline-none transition">
            </div>

            <!-- Birthdate & Sex -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Birthdate <span class="text-red-500">*</span></label>
                        <span id="modal_pat_calc_age" class="text-xs text-blue-600 font-semibold"><?= !empty($caseDetails['age']) ? $caseDetails['age'] . ' yrs old' : '' ?></span>
                    </div>
                    <div class="relative">
                        <input type="text" name="birthdate" id="modal_pat_birthdate" readonly placeholder="Select birthdate" required
                            value="<?= htmlspecialchars($caseDetails['birthdate'] ?? '') ?>"
                            class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl p-2.5 pr-9 outline-none transition cursor-pointer">
                        <i data-lucide="calendar" class="absolute right-3 top-3 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Sex <span class="text-red-500">*</span></label>
                    <select name="sex" id="modal_pat_sex" required
                        class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl p-2.5 outline-none transition">
                        <option value="Male" <?= ($caseDetails['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($caseDetails['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
            </div>

            <!-- Contact Number -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Contact Number <span class="text-red-500">*</span></label>
                <input type="tel" name="contact_number" id="modal_pat_contact" required maxlength="11" pattern="09[0-9]{9}"
                    value="<?= htmlspecialchars($caseDetails['contact_number'] ?? '') ?>"
                    placeholder="09XXXXXXXXX"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);"
                    class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl p-2.5 outline-none transition">
            </div>

            <!-- Home Address -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Home Address</label>
                <input type="text" name="home_address" id="modal_pat_address"
                    value="<?= htmlspecialchars($caseDetails['home_address'] ?? '') ?>"
                    placeholder="House / Unit / Street, Barangay, City / Municipality"
                    class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl p-2.5 outline-none transition">
            </div>

            <!-- PhilHealth Status -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">PhilHealth Status <span class="text-red-500">*</span></label>
                <select name="philhealth_status" id="modal_pat_philhealth_status" onchange="togglePhilHealthModalFields()"
                    class="w-full text-sm text-gray-900 bg-gray-50 border border-gray-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl p-2.5 outline-none transition">
                    <option value="Without PhilHealth Card" <?= ($caseDetails['philhealth_status'] ?? '') !== 'With PhilHealth Card' ? 'selected' : '' ?>>Without PhilHealth Card</option>
                    <option value="With PhilHealth Card" <?= ($caseDetails['philhealth_status'] ?? '') === 'With PhilHealth Card' ? 'selected' : '' ?>>With PhilHealth Card</option>
                </select>
            </div>

            <!-- PhilHealth ID Container (Conditional) -->
            <div id="modal_pat_philhealth_container" class="<?= ($caseDetails['philhealth_status'] ?? '') === 'With PhilHealth Card' ? '' : 'hidden' ?> p-4 bg-blue-50/70 border border-blue-100 rounded-xl space-y-3">
                <div>
                    <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider mb-1">PhilHealth ID Number <span class="text-red-500">*</span></label>
                    <input type="text" name="philhealth_id" id="modal_pat_philhealth_id" inputmode="numeric" maxlength="14"
                        value="<?= htmlspecialchars($caseDetails['philhealth_id'] ?? '') ?>"
                        oninput="formatPhilHealthInput(this); checkPatPhilHealthModalDup();"
                        placeholder="XX-XXXXXXXXX-X"
                        class="w-full text-sm font-mono text-gray-900 bg-white border border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-xl p-2.5 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider mb-1">Patient's Relation to ID <span class="text-red-500">*</span></label>
                    <select name="philhealth_relation" id="modal_pat_philhealth_relation"
                        class="w-full text-sm text-gray-900 bg-white border border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-xl p-2.5 outline-none transition">
                        <option value="" disabled <?= empty($caseDetails['philhealth_relation']) ? 'selected' : '' ?>>Select relation</option>
                        <option value="Principal Member" <?= ($caseDetails['philhealth_relation'] ?? '') === 'Principal Member' ? 'selected' : '' ?> id="pat-modal-opt-owner">Principal Member</option>
                        <option value="Qualified Dependent" <?= ($caseDetails['philhealth_relation'] ?? '') === 'Qualified Dependent' ? 'selected' : '' ?> id="pat-modal-opt-family">Qualified Dependent</option>
                    </select>
                    <p id="pat-modal-philhealth-msg" class="text-xs text-red-600 mt-1.5 hidden font-medium"></p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeEditPatientInfoModal()"
                    class="px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-100 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="modal_pat_save_btn"
                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-600/20 transition cursor-pointer">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let modalPatientDatePicker = null;

    function initModalPatientDatePicker() {
        const bdayInput = document.getElementById('modal_pat_birthdate');
        if (bdayInput && typeof ModernDatePicker !== 'undefined') {
            if (!modalPatientDatePicker) {
                modalPatientDatePicker = new ModernDatePicker(bdayInput, {
                    maxDate: new Date(),
                    onSelect: function () {
                        updateModalPatientAge();
                    }
                });
                bdayInput.addEventListener('changeDate', function () {
                    updateModalPatientAge();
                });
            }
        }
    }

    function openEditPatientInfoModal() {
        const modal = document.getElementById('edit-patient-info-modal');
        if (modal) {
            modal.classList.remove('hidden');
            initModalPatientDatePicker();
            togglePhilHealthModalFields();
            updateModalPatientAge();
            if (window.lucide) window.lucide.createIcons();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initModalPatientDatePicker();
    });

    function closeEditPatientInfoModal() {
        const modal = document.getElementById('edit-patient-info-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function togglePhilHealthModalFields() {
        const statusSelect = document.getElementById('modal_pat_philhealth_status');
        const container = document.getElementById('modal_pat_philhealth_container');
        const idInput = document.getElementById('modal_pat_philhealth_id');
        const relSelect = document.getElementById('modal_pat_philhealth_relation');

        if (!statusSelect || !container) return;

        const isWithCard = (statusSelect.value === 'With PhilHealth Card');
        if (isWithCard) {
            container.classList.remove('hidden');
            if (idInput) idInput.required = true;
            if (relSelect) relSelect.required = true;
            checkPatPhilHealthModalDup();
        } else {
            container.classList.add('hidden');
            if (idInput) idInput.required = false;
            if (relSelect) relSelect.required = false;
        }
    }

    function updateModalPatientAge() {
        const bdayInput = document.getElementById('modal_pat_birthdate');
        const ageLabel = document.getElementById('modal_pat_calc_age');
        if (!bdayInput || !ageLabel || !bdayInput.value) return;

        const bday = new Date(bdayInput.value);
        if (isNaN(bday.getTime())) return;

        const today = new Date();
        let age = today.getFullYear() - bday.getFullYear();
        const m = today.getMonth() - bday.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < bday.getDate())) {
            age--;
        }
        if (age >= 0) {
            ageLabel.textContent = age + (age === 1 ? ' yr old' : ' yrs old');
        }
    }

    function formatPhilHealthInput(input) {
        if (!input) return;
        let value = input.value.replace(/\D/g, '');
        if (value.length > 12) value = value.slice(0, 12);
        let formatted = '';
        if (value.length > 0) formatted += value.substring(0, Math.min(2, value.length));
        if (value.length > 2) formatted += '-' + value.substring(2, Math.min(11, value.length));
        if (value.length > 11) formatted += '-' + value.substring(11, 12);
        input.value = formatted;
    }

    let patPhilHealthCheckTimeout = null;
    function checkPatPhilHealthModalDup() {
        const idInput = document.getElementById('modal_pat_philhealth_id');
        const msgEl = document.getElementById('pat-modal-philhealth-msg');
        const ownerOpt = document.getElementById('pat-modal-opt-owner');
        const familyOpt = document.getElementById('pat-modal-opt-family');
        const currentCaseId = <?= (int)($caseDetails['id'] ?? 0) ?>;

        if (!idInput) return;
        const val = idInput.value.trim();

        if (val.length < 14) {
            if (msgEl) msgEl.classList.add('hidden');
            if (ownerOpt) ownerOpt.disabled = false;
            if (familyOpt) familyOpt.disabled = false;
            return;
        }

        clearTimeout(patPhilHealthCheckTimeout);
        patPhilHealthCheckTimeout = setTimeout(() => {
            const basePath = '<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>';
            const url = `${basePath}app/Api/check_philhealth.php?philhealth_id=${encodeURIComponent(val)}&exclude_case_id=${currentCaseId}&t=${Date.now()}`;
            fetch(url, { cache: 'no-store' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    if (ownerOpt) ownerOpt.disabled = data.owner_used_by_other;
                    if (familyOpt) familyOpt.disabled = data.family_used_by_other;

                    if (msgEl) {
                        if (data.owner_used_by_other && data.family_used_by_other) {
                            msgEl.textContent = 'This PhilHealth ID is fully utilized for both Principal Member and Qualified Dependent.';
                            msgEl.classList.remove('hidden');
                        } else if (data.owner_used_by_other) {
                            msgEl.textContent = 'Principal Member relation is already registered for this PhilHealth ID.';
                            msgEl.classList.remove('hidden');
                        } else if (data.family_used_by_other) {
                            msgEl.textContent = 'Qualified Dependent relation is already registered for this PhilHealth ID.';
                            msgEl.classList.remove('hidden');
                        } else {
                            msgEl.classList.add('hidden');
                        }
                    }
                })
                .catch(() => {});
        }, 350);
    }

    async function submitEditPatientInfo(e) {
        e.preventDefault();
        const form = document.getElementById('edit-patient-info-form');
        const saveBtn = document.getElementById('modal_pat_save_btn');
        if (!form) return false;

        if (window.FormValidator) {
            window.FormValidator.clearAllErrors(form);
        }

        const phoneInput = document.getElementById('modal_pat_contact');
        if (phoneInput && !/^09\d{9}$/.test(phoneInput.value.trim())) {
            const msg = 'Contact number must be 11 digits starting with 09.';
            if (window.FormValidator) window.FormValidator.showError(phoneInput, msg);
            if (typeof toast === 'function') toast(msg, 'error');
            phoneInput.focus();
            return false;
        }

        const statusSelect = document.getElementById('modal_pat_philhealth_status');
        if (statusSelect && statusSelect.value === 'With PhilHealth Card') {
            const idInput = document.getElementById('modal_pat_philhealth_id');
            const relSelect = document.getElementById('modal_pat_philhealth_relation');
            if (idInput && idInput.value.trim().length !== 14) {
                const msg = 'Please enter a valid 12-digit PhilHealth ID (XX-XXXXXXXXX-X).';
                if (window.FormValidator) window.FormValidator.showError(idInput, msg);
                if (typeof toast === 'function') toast(msg, 'error');
                idInput.focus();
                return false;
            }
            if (relSelect && !relSelect.value) {
                const msg = "Please select patient's relation to the PhilHealth ID.";
                if (window.FormValidator) window.FormValidator.showError(relSelect, msg);
                if (typeof toast === 'function') toast(msg, 'error');
                relSelect.focus();
                return false;
            }
        }

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.classList.add('opacity-70', 'cursor-not-allowed');
            saveBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Saving...';
        }

        try {
            const formData = new FormData(form);
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const result = await response.json();

            if (result.success) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Updated Successfully',
                        text: result.message || 'Patient information updated successfully.',
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl' }
                    });
                }
                window.location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: result.message || 'Could not update patient information.',
                        customClass: { popup: 'rounded-2xl' }
                    });
                } else {
                    alert(result.message || 'Could not update patient information.');
                }
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                    saveBtn.innerHTML = '<i data-lucide="save" class="w-4 h-4"></i><span>Save Changes</span>';
                    if (window.lucide) window.lucide.createIcons();
                }
            }
        } catch (err) {
            // Fallback to normal form submit if AJAX fails
            form.submit();
        }
        return false;
    }
</script>


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

    function getStatusBadgeStyle(status) {
        switch (status) {
            case 'Completed':
            case 'Resolved':
                return { border: '1.5px solid #4ade80', bg: '#f0fdf4', color: '#15803d' };
            case 'Correction Completed':
            case 'Pending RadTech Verification':
            case 'Under Reading':
                return { border: '1.5px solid #60a5fa', bg: '#eff6ff', color: '#1d4ed8' };
            case 'Correction in Progress':
            case 'Report Ready':
                return { border: '1.5px solid #818cf8', bg: '#eef2ff', color: '#4338ca' };
            case 'For RadTech Review':
                return { border: '1.5px solid #facc15', bg: '#fefce8', color: '#a16207' };
            case 'Issue Reported':
            case 'Pending RadTech Review':
                return { border: '1.5px solid #fb7185', bg: '#fff1f2', color: '#be123c' };
            case 'Overdue':
            case 'Rejected':
                return { border: '1.5px solid #f87171', bg: '#fef2f2', color: '#b91c1c' };
            default:
                return { border: '1.5px solid #facc15', bg: '#fefce8', color: '#a16207' };
        }
    }

    let isSyncing = false;
    function syncCaseDetailsViaAjax(newStatus) {
        if (isSyncing) return;
        isSyncing = true;

        // Immediate visual feedback on status badge
        const badge = document.getElementById('case-status-badge');
        if (badge) {
            const st = getStatusBadgeStyle(newStatus);
            badge.className = 'inline-block font-bold text-xs px-3 py-1.5 rounded-full transition-all duration-300';
            badge.style.border = st.border;
            badge.style.backgroundColor = st.bg;
            badge.style.color = st.color;
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
                    curBadge.style.cssText = freshBadge.style.cssText;
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

async function triggerReEdit(caseId, btn, event = null) {
    if (event) event.preventDefault();

    let reason = '';
    if (typeof Swal !== 'undefined') {
        const { value: formValues, isConfirmed } = await Swal.fire({
            title: 'Allow Radiologist to Re-edit?',
            html: `
                <div class="text-left text-sm text-gray-600 mb-3">
                    This will revert the report to draft status (Under Reading) so the radiologist can update findings and impressions.
                </div>
                <div class="text-left mb-2">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        RadTech Notes <span class="text-red-500">*</span>:
                    </label>
                    <textarea id="swal-reedit-reason" rows="3" class="w-full text-sm border border-gray-300 rounded-xl p-3 transition resize-none font-sans" style="outline: none !important; box-shadow: none !important;" onfocus="if(!this.dataset.error){this.style.borderColor='#d97706';}" onblur="if(!this.dataset.error){this.style.borderColor='#d1d5db';}" placeholder="Enter notes for the radiologist on what needs to be changed..."></textarea>
                    <p id="swal-reedit-error" class="hidden text-xs text-red-600 mt-2 font-medium items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="inline-block text-red-500 shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Please enter notes for the radiologist.</span>
                    </p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Allow Re-edit',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#6b7280',
            customClass: {
                popup: 'rounded-2xl p-5 max-w-lg font-sans',
                confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold shadow-sm',
                cancelButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold'
            },
            didOpen: () => {
                const textarea = document.getElementById('swal-reedit-reason');
                const errEl = document.getElementById('swal-reedit-error');
                if (textarea) {
                    textarea.focus();
                    textarea.addEventListener('input', () => {
                        if (textarea.value.trim().length > 0) {
                            textarea.dataset.error = '';
                            textarea.style.borderColor = '#d97706';
                            textarea.style.backgroundColor = '#ffffff';
                            if (errEl) {
                                errEl.classList.add('hidden');
                                errEl.classList.remove('flex');
                            }
                        }
                    });
                }
            },
            preConfirm: () => {
                const textarea = document.getElementById('swal-reedit-reason');
                const errEl = document.getElementById('swal-reedit-error');
                const text = textarea ? textarea.value.trim() : '';
                if (!text) {
                    if (textarea) {
                        textarea.dataset.error = '1';
                        textarea.style.borderColor = '#ef4444';
                        textarea.style.backgroundColor = '#fef2f2';
                        textarea.focus();
                    }
                    if (errEl) {
                        errEl.classList.remove('hidden');
                        errEl.classList.add('flex');
                    }
                    return false;
                }
                return text;
            }
        });

        if (!isConfirmed || !formValues) return;
        reason = formValues;
    } else {
        reason = prompt('Allow Radiologist to Re-edit? Please enter the reason:');
        if (reason === null) return;
        reason = reason.trim();
        if (!reason) {
            alert('A reason is required to allow re-edit.');
            return;
        }
    }

    const originalHTML = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    }

    try {
        const baseDir = '<?= defined("PROJECT_DIR") && PROJECT_DIR ? "/" . PROJECT_DIR : "" ?>';
        const res = await fetch(`${baseDir}/radtech/re-edit-case`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `case_id=${encodeURIComponent(caseId)}&reason=${encodeURIComponent(reason)}`
        });

        const data = await res.json();
        if (data.success) {
            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    icon: 'success',
                    title: 'Reverted to Draft',
                    text: data.message || 'Report reverted to draft successfully.',
                    timer: 1800,
                    showConfirmButton: false,
                    customClass: { popup: 'rounded-2xl' }
                });
            } else {
                alert(data.message || 'Report reverted to draft successfully.');
            }
            window.location.reload();
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Action Failed',
                    text: data.message || 'Could not revert report.',
                    customClass: { popup: 'rounded-2xl' }
                });
            } else {
                alert(data.message || 'Could not revert report.');
            }
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.innerHTML = originalHTML;
            }
        }
    } catch (err) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'A network error occurred. Please try again.',
                customClass: { popup: 'rounded-2xl' }
            });
        } else {
            alert('A network error occurred. Please try again.');
        }
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = originalHTML;
        }
    }
}

async function releaseToPhoto(caseId, btn, event = null) {
    if (event) event.preventDefault();
    let confirmed = false;
    if (typeof confirmAlert === 'function') {
        const res = await confirmAlert('Confirm Release', 'Would you like to confirm releasing this result and moving it to X-ray Patient Records?');
        confirmed = res && res.isConfirmed;
    } else if (typeof Swal !== 'undefined') {
        const res = await Swal.fire({
            title: 'Confirm Release',
            text: 'Would you like to confirm releasing this result and moving it to X-ray Patient Records?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Release',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            customClass: { popup: 'rounded-2xl' }
        });
        confirmed = res && res.isConfirmed;
    } else {
        confirmed = confirm('Would you like to confirm releasing this result and moving it to X-ray Patient Records?');
    }
    if (!confirmed) return;

    const baseDir = '<?= defined("PROJECT_DIR") && PROJECT_DIR ? "/" . PROJECT_DIR : "" ?>';
    const overlay = document.getElementById('release-loading-overlay');
    const statusText = document.getElementById('release-status-text');

    // Disable button to prevent double clicks
    const originalHTML = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    }

    // Show refined overlay
    if (overlay) overlay.classList.remove('hidden');
    if (statusText) statusText.textContent = 'Initializing report snapshot...';

    try {
        // 1. Create a hidden iframe to render the perfect print layout
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.top = '-10000px';
        iframe.style.left = '-10000px';
        iframe.style.width = '814px'; // Fixed A4 width for predictable rendering
        iframe.style.height = '1200px';
        iframe.style.border = 'none';

        // Use snapshot=1 to skip auto-print in the iframe
        iframe.src = `${baseDir}/index.php?page=print-report&id=${caseId}&no_shadow=1&snapshot=1`;
        document.body.appendChild(iframe);

        iframe.onload = async () => {
            try {
                const doc = iframe.contentDocument || iframe.contentWindow.document;

                // Wait briefly for fonts/images
                await new Promise(r => setTimeout(r, 1000));

                const pages = doc.querySelectorAll('.report-page');
                if (!pages.length) throw new Error("No pages found to render.");

                // Expand iframe height so it fits ALL pages
                iframe.style.height = (doc.documentElement.scrollHeight + 200) + 'px';
                await new Promise(r => setTimeout(r, 500));

                let base64Images = [];
                for (let i = 0; i < pages.length; i++) {
                    const page = pages[i];
                    if (statusText) statusText.textContent = `Processing page ${i + 1} of ${pages.length}...`;

                    const canvas = await html2canvas(page, {
                        scale: pages.length > 5 ? 1.5 : 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        width: page.scrollWidth,
                        height: page.scrollHeight,
                        windowWidth: doc.documentElement.scrollWidth,
                        windowHeight: doc.documentElement.scrollHeight
                    });

                    const imgData = canvas.toDataURL('image/jpeg', pages.length > 5 ? 0.8 : 0.9);
                    base64Images.push(imgData);
                }

                if (statusText) statusText.textContent = 'Uploading consolidated report...';

                // Submit images to backend
                const formData = new FormData();
                formData.append('id', caseId);
                formData.append('images', JSON.stringify(base64Images));

                const response = await fetch(`${baseDir}/patient-details?id=${caseId}&action=release_and_upload`, {
                    method: 'POST',
                    body: formData
                });

                const resText = await response.text();
                let result;
                try {
                    result = JSON.parse(resText);
                } catch (jsonErr) {
                    console.error("Server response was not valid JSON:", resText);
                    throw new Error('Server returned invalid response format: ' + (resText ? resText.substring(0, 100) : 'Empty response'));
                }

                if (result.success) {
                    const spinner = document.getElementById('release-spinner-container');
                    const successIcon = document.getElementById('release-success-icon');
                    const titleEl = document.getElementById('release-title-text');
                    const statusTextEl = document.getElementById('release-status-text');

                    if (spinner) spinner.classList.add('hidden');
                    if (successIcon) successIcon.classList.remove('hidden');
                    if (titleEl) {
                        titleEl.textContent = 'Result Released!';
                        titleEl.className = 'text-base font-bold text-green-600 dark:text-green-400';
                    }
                    if (statusTextEl) {
                        statusTextEl.textContent = 'Case moved to X-ray Patient Records.';
                        statusTextEl.className = 'text-xs text-gray-600 dark:text-gray-300 mt-2 text-center font-medium';
                    }

                    await new Promise(r => setTimeout(r, 1200));
                    window.location.reload();
                } else {
                    throw new Error(result.message || 'Server rejected the upload.');
                }
            } catch (err) {
                console.error(err);
                if (typeof errorAlert === 'function') {
                    errorAlert('Generation Failed', err.message);
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Generation Failed', text: err.message, customClass: { popup: 'rounded-2xl' } });
                } else {
                    alert('Generation Failed: ' + err.message);
                }
                if (overlay) overlay.classList.add('hidden');
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btn.innerHTML = originalHTML;
                }
            } finally {
                iframe.remove();
            }
        };
    } catch (e) {
        console.error(e);
        if (typeof errorAlert === 'function') {
            errorAlert('Generation Failed', e.message);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Generation Failed', text: e.message, customClass: { popup: 'rounded-2xl' } });
        } else {
            alert('Generation Failed: ' + e.message);
        }
        if (overlay) overlay.classList.add('hidden');
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = originalHTML;
        }
    }
}
</script>

<!-- Release Loading Overlay -->
<div id="release-loading-overlay" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/60 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl flex flex-col items-center max-w-xs w-full mx-4 border border-gray-100 dark:border-gray-700 transition-all">
        <div id="release-spinner-container">
            <div class="animate-spin rounded-full h-14 w-14 border-4 border-red-600 border-t-transparent mb-4"></div>
        </div>
        <div id="release-success-icon" class="hidden mb-4">
            <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center shadow-lg shadow-green-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 stroke-[3]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
        </div>
        <h3 id="release-title-text" class="text-base font-bold text-gray-800 dark:text-gray-100">Processing Release</h3>
        <p id="release-status-text" class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center font-medium">Preparing the results...</p>
    </div>
</div>