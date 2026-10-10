<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);

$caseId = (int) ($_GET['id'] ?? 0);
if ($caseId > 0) {
    $_SESSION['active_record_history_case_id'] = $caseId;
} else {
    $caseId = (int) ($_SESSION['active_record_history_case_id'] ?? 0);
}
$branchId = $_SESSION['branch_id'] ?? 1;

// Fetch case details (Backend logic)
$caseDetails = $caseModel->getCaseById($caseId);

// Security Check:
// 1. Case must exist and belong to this RadTech's branch.
// 2. Case must be released (released = 1) — prevents accessing in-progress queue cases
//    by manually changing the ?id= parameter.
//    This mirrors exactly the set of records shown in the Records History list.
$isReleased = !empty($caseDetails['released']) && (int) $caseDetails['released'] === 1;
$isInBranch = $caseDetails && (int) $caseDetails['branch_id'] === (int) $branchId;

if (!$caseDetails || !$isInBranch || !$isReleased) {
    ?>
    <div class="max-w-2xl mx-auto mt-12 p-8 rounded-2xl bg-white border border-gray-200 shadow-sm text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-red-50 flex items-center justify-center mb-4 text-red-600">
            <i data-lucide="shield-alert" class="w-8 h-8"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Record Not Available</h3>
        <p class="text-sm text-gray-500 mb-6">Record not found or invalid branch access.</p>
        <a href="<?= url('xray-patient-records') ?>" data-back-btn data-fallback="<?= url('xray-patient-records') ?>" title="Back"
            class="inline-flex items-center gap-2 rounded-xl bg-gray-900 hover:bg-gray-800 text-white font-medium text-sm py-2.5 px-5 transition shadow-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Patient Records
        </a>
    </div>
    <?php
    return;
}

$fullName = htmlspecialchars(formatFullName($caseDetails));
$philHealthLabel = ($caseDetails['philhealth_status'] === 'With PhilHealth Card') ? 'With PhilHealth ID' : 'Without PhilHealth ID';

$userRole = $_SESSION['role'] ?? 'radtech';
$from = $_GET['from'] ?? '';

$backLink = url('xray-patient-records');
if ($userRole === 'branch_admin' || $from === 'branch-xray-cases') {
    $backLink = url('branch-xray-cases?tab=records');
} elseif ($userRole === 'admin_central' || $from === 'patient-records') {
    $backLink = url('patient-records');
}
?>

<!-- Sticky Layout Wrapper -->
<div class="flex items-start gap-4">
    <!-- Sticky Back Button -->
    <div class="lg:sticky lg:top-6 z-40 shrink-0">
        <a href="<?= $backLink ?>" data-back-btn data-fallback="<?= $backLink ?>" aria-label="Go back to records" title="Back"
            class="flex w-10 h-10 items-center justify-center rounded-xl bg-white border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
        </a>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 min-w-0">
        <!-- Title -->
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">Patient Records History</h2>
            <p class="text-sm text-gray-400 mt-0.5">Historical patient database and examination archive</p>
        </div>

<div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">
    <!-- Top Details Section -->
        <?php
        $isClaimedCase = !empty($caseDetails['is_claimed']) && (int)$caseDetails['is_claimed'] === 1;
        ?>
        <div class="flex items-center justify-between border-b border-gray-200 pb-4 flex-wrap gap-3">
            <h3 class="text-[22px] font-bold text-gray-900">Findings Report and Images</h3>
            <div class="flex items-center gap-3">
                <span class="text-gray-500 font-medium text-xs sm:text-sm bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200/80 shadow-2xs">
                    <?= date('F d, Y', strtotime($caseDetails['created_at'])) ?>
                </span>
                <?php if ($isClaimedCase): ?>
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-300 text-xs sm:text-sm font-bold rounded-xl shadow-2xs">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i> Released &amp; Claimed
                    </span>
                <?php else: ?>
                    <button type="button"
                        onclick="openClaimModalHistory(<?= $caseId ?>, '<?= htmlspecialchars(addslashes($caseDetails['case_number'])) ?>', '<?= htmlspecialchars(addslashes($fullName)) ?>', '<?= htmlspecialchars(addslashes($caseDetails['exam_type'] ?? 'X-ray')) ?>')"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs sm:text-sm font-bold rounded-xl transition shadow-sm cursor-pointer active:scale-95">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i> Mark as Claimed
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-6 flex flex-col md:flex-row justify-between gap-8">
            <div class="flex-1 space-y-1">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h4 class="text-xl font-bold text-gray-800 tracking-wide">
                        <?= htmlspecialchars($caseDetails['case_number']) ?>
                    </h4>
                    <?php if (!empty($caseDetails['is_amended']) && (int) $caseDetails['is_amended'] === 1): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-300 shadow-xs" title="This record has been corrected / edited">
                            <i data-lucide="edit-3" class="w-3 h-3"></i> Edited
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-lg text-gray-500 font-medium"><?= $fullName ?></p>


                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide w-36">Patient No.</span>
                    <p class="text-sm font-normal text-gray-500 italic">
                        <?= htmlspecialchars($caseDetails['patient_number'] ?? '—') ?>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide w-36">Age / Sex</span>
                    <span class="text-gray-400 text-sm"> <?= htmlspecialchars($caseDetails['age']) ?> /
                        <?= htmlspecialchars($caseDetails['sex']) ?></span>
                </div>

                <!-- Contact Number -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide w-36">Contact No.</span>
                    <span
                        class="text-sm text-gray-700"><?= htmlspecialchars($caseDetails['contact_number'] ?? '—') ?></span>
                </div>

                <!-- PhilHealth ID -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide w-36">PhilHealth ID</span>
                    <span class="text-sm text-gray-700">
                        <?php if ($caseDetails['philhealth_status'] === 'With PhilHealth Card') {
                            echo '<span class="text-green-600 font-medium">' . htmlspecialchars($caseDetails['philhealth_id'] ?? 'N/A') . '</span>';
                        } else {
                            echo '<span class="text-red-600 font-medium">Without PhilHealth ID</span>';
                        } ?>
                    </span>
                </div>

                <!-- Radiologist -->
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide w-36">Radiologist</span>
                    <span class="text-sm font-medium text-gray-900">
                        <?php
                        if (!empty($caseDetails['radiologist_name'])) {
                            $radDisplay = ucwords(str_replace('.', ' ', $caseDetails['radiologist_name']));
                            if (!empty($caseDetails['radiologist_title'])) {
                                $radDisplay .= ', ' . $caseDetails['radiologist_title'];
                            }
                            echo 'Dr. ' . htmlspecialchars($radDisplay);
                        } else {
                            echo '<span class="text-gray-400 font-normal italic">Waiting for assignment</span>';
                        }
                        ?>
                    </span>
                </div>

                <?php if ($isClaimedCase): ?>
                    <!-- Claim Details -->
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide w-36">Claim Details</span>
                        <div class="flex items-center gap-2 flex-wrap text-sm">
                            <span class="text-gray-900 font-medium">
                                <?= htmlspecialchars($caseDetails['claimed_by'] ?: $fullName) ?>
                                <span class="text-gray-500 font-normal">(<?= htmlspecialchars($caseDetails['claimed_relationship'] ?: 'Self') ?>)</span>
                            </span>
                            <span class="text-gray-300">•</span>
                            <button type="button"
                                onclick="openClaimDetailsModalHistory(<?= htmlspecialchars(json_encode([
                                    'case_id' => $caseId,
                                    'case_number' => $caseDetails['case_number'],
                                    'patient_name' => $fullName,
                                    'claimed_at' => !empty($caseDetails['claimed_at']) ? date('M d, Y h:i A', strtotime($caseDetails['claimed_at'])) : '—',
                                    'claimed_by' => $caseDetails['claimed_by'] ?: $fullName,
                                    'claimed_relationship' => $caseDetails['claimed_relationship'] ?: 'Self',
                                    'claimed_id_presented' => $caseDetails['claimed_id_presented'] ?? '',
                                    'claimed_notes' => $caseDetails['claimed_notes'] ?? ''
                                ]), ENT_QUOTES, 'UTF-8') ?>)"
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline cursor-pointer transition">
                                See Full Details
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-right md:w-1/3 flex flex-col items-end justify-start">
                <h4 class="text-xl font-bold text-gray-900">Exam Type</h4>
                <p class="text-gray-500 font-medium text-lg mt-0.5"><?= htmlspecialchars($caseDetails['exam_type']) ?></p>
            </div>
        </div>
    </div>

    <!-- Dual Column Split -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-10">


        <!-- Findings Report -->
        <div class="flex flex-col border border-gray-200 rounded-2xl overflow-hidden h-[480px]">
            <div class="shrink-0 bg-red-600 px-5 h-14 flex items-center gap-3 text-white shadow-lg z-10 w-full">
                <div
                    class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/20 shadow-inner">
                    <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
                </div>
                <span class="font-black text-xs uppercase tracking-widest">Findings Report</span>
            </div>
            <div id="findings-viewer-container"
                class="flex-1 bg-[#0a0a0a] relative overflow-hidden group flex items-center justify-center p-4">
                <?php 
                $isCaseReverted = !empty($caseDetails['re_edit_reason']) 
                    || ($caseDetails['report_status'] ?? '') === 'Draft' 
                    || in_array($caseDetails['status'], ['Under Reading', 'Pending', 'For Revision', 'Rejected', 'Cancelled']);
                $isReportAvailable = in_array($caseDetails['status'], ['Report Ready', 'Completed', 'Released']) && !$isCaseReverted;
                ?>
                <?php if ($isReportAvailable): ?>
                    <?php
                    $reportUrl = url("print-report?ref=" . generateReportToken($caseId) . "&preview=true");
                    ?>

                    <button type="button" aria-label="Download or Print Report"
                        onclick="openReportViewer('<?= $reportUrl ?>')"
                        class="group relative w-full h-full flex flex-col items-center justify-center bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl hover:border-red-400 hover:bg-red-50 transition-all cursor-pointer">
                        <div class="bg-white p-4 rounded-full shadow-md mb-4 group-hover:scale-110 transition-transform">
                            <i data-lucide="file-text" class="w-10 h-10 text-red-500"></i>
                        </div>
                        <span class="font-bold text-gray-800 text-lg group-hover:text-red-600 transition-colors">Download /
                            Print Report</span>
                        <span class="text-sm text-gray-500 mt-1">Open report in a popup window</span>
                    </button>
                <?php elseif ($isCaseReverted && !empty($caseDetails['re_edit_reason'])): ?>
                    <div class="text-center p-6">
                        <div class="w-12 h-12 rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="rotate-ccw" class="w-6 h-6 text-amber-500"></i>
                        </div>
                        <p class="font-bold text-amber-400">Reverted for Re-edit</p>
                        <p class="text-sm text-gray-400 mt-1 max-w-[280px] mx-auto">This case was returned to the radiologist for revision. The updated report will be available once resubmitted.</p>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <i data-lucide="clock-3" class="w-12 h-12 mb-4 text-gray-600 mx-auto opacity-50"></i>
                        <p class="font-bold text-gray-400">Waiting for Radiologist</p>
                        <p class="text-sm text-gray-500 mt-1 max-w-[250px] mx-auto">The findings report will appear here
                            once the radiologist submits their evaluation.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Enhanced X-Ray Viewer -->
        <div class="flex flex-col min-h-0">
            <?php
            $savedPaths = [];
            $savedLabels = [];
            if (!empty($caseDetails['image_path'])) {
                $decoded = json_decode($caseDetails['image_path'], true);
                $rawPaths = is_array($decoded) ? $decoded : [$caseDetails['image_path']];
                $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
                $baseUrl = ($isLocalhost && PROJECT_DIR) ? '/' . PROJECT_DIR . '/' : '/';

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

                foreach ($rawPaths as $i => $p) {
                    $savedPaths[] = $baseUrl . ltrim($p, '/');
                    $savedLabels[] = getXrayImageLabel($p, $i, $caseDetails['exam_type'] ?? '');
                }
            }
            // encode paths for JS safely
            $jsonPaths = json_encode($savedPaths);
            $jsonLabels = json_encode($savedLabels);
            ?>

            <div id="xray-viewer-container"
                class="bg-[#0a0a0a] border border-gray-200 rounded-2xl overflow-hidden shadow-2xl flex flex-col min-h-0 h-[480px] relative transition-all w-full">
                <?php if (!empty($savedPaths)): ?>
                    <!-- Classic Integrated Header Toolbar -->
                    <div class="bg-red-600 px-5 h-14 flex justify-between items-center text-white z-20 w-full select-none shadow-lg"
                        id="xray-toolbar">

                        <div class="flex items-center gap-4">
                            <div
                                class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/20 shadow-inner shrink-0">
                                <i data-lucide="scan-line" class="w-5 h-5"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-black text-xs uppercase tracking-widest leading-none">X-ray Viewer</span>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <?php if (count($savedPaths) > 1): ?>
                                        <span id="xray-counter"
                                            class="text-[9px] font-bold text-white/75 tracking-tighter uppercase shrink-0">
                                            1 / <?= count($savedPaths) ?>
                                        </span>
                                        <span class="text-white/40 text-[9px] shrink-0">•</span>
                                    <?php endif; ?>
                                    <span id="xray-exam-name"
                                        class="text-[10px] font-black text-white bg-black/30 px-2 py-0.5 rounded uppercase tracking-wider truncate max-w-[200px]"
                                        title="<?= htmlspecialchars($savedLabels[0] ?? '') ?>">
                                        <?= htmlspecialchars($savedLabels[0] ?? ($caseDetails['exam_type'] ?? 'X-RAY')) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Central Navigation -->
                        <?php if (count($savedPaths) > 1): ?>
                            <div class="flex items-center bg-black/20 rounded-xl p-1 gap-1 border border-white/5 shadow-inner">
                                <button id="btn-prev-img" aria-label="Previous Image"
                                    class="w-8 h-8 flex items-center justify-center hover:bg-white/10 rounded-lg transition-all active:scale-95 disabled:opacity-20"
                                    title="Previous Image">
                                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                                </button>
                                <div class="w-px h-4 bg-white/10 mx-1"></div>
                                <button id="btn-next-img" aria-label="Next Image"
                                    class="w-8 h-8 flex items-center justify-center hover:bg-white/10 rounded-lg transition-all active:scale-95 disabled:opacity-20"
                                    title="Next Image">
                                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Zoom Controls -->
                        <div
                            class="flex items-center gap-3 bg-black/20 rounded-xl px-3 py-1.5 border border-white/5 shadow-inner">
                            <button id="btn-zoom-out" aria-label="Zoom Out"
                                class="text-white/60 hover:text-white transition-colors" title="Zoom Out">
                                <i data-lucide="minus-circle" class="w-4 h-4"></i>
                            </button>
                            <span id="zoom-level"
                                class="text-[10px] font-black text-white min-w-[35px] text-center tabular-nums"><?= isset($isZoomed) ? $isZoomed : '100%' ?></span>
                            <button id="btn-zoom-in" aria-label="Zoom In"
                                class="text-white/60 hover:text-white transition-colors" title="Zoom In">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Main Viewing Area -->
                    <div
                        class="flex-1 relative bg-[#0a0a0a] overflow-hidden group/viewer">
                        <div class="absolute inset-0 flex items-center justify-center p-2">
                            <img id="xray-main-image" src="<?= htmlspecialchars($savedPaths[0] ?? '') ?>"
                                class="w-full h-full object-contain transition-transform duration-100 ease-out origin-center"
                                alt="X-ray" draggable="false">
                        </div>

                        <!-- Classic Bottom Thumbnails -->
                        <?php if (count($savedPaths) > 1): ?>
                            <div id="xray-thumb-strip"
                                class="absolute bottom-4 left-1/2 -translate-x-1/2 h-16 bg-black/40 backdrop-blur-md rounded-2xl flex items-center px-4 gap-3 z-20 border border-white/10 shadow-2xl overflow-x-auto max-w-[90%] scrollbar-hide">
                                <?php foreach ($savedPaths as $index => $path): ?>
                                    <?php $lbl = $savedLabels[$index] ?? ('IMG ' . ($index + 1)); ?>
                                    <div class="xray-thumb-item flex-shrink-0 w-10 h-10 rounded-xl border-2 <?= $index === 0 ? 'border-red-500 bg-red-500/10' : 'border-transparent opacity-60' ?> overflow-hidden cursor-pointer transition-all hover:scale-110 hover:opacity-100"
                                        data-index="<?= $index ?>" data-url="<?= htmlspecialchars($path) ?>" data-label="<?= htmlspecialchars($lbl) ?>"
                                        title="<?= htmlspecialchars($lbl) ?> (<?= $index + 1 ?> / <?= count($savedPaths) ?>)">
                                        <img src="<?= htmlspecialchars($path) ?>" class="w-full h-full object-cover"
                                            alt="<?= htmlspecialchars($lbl) ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Expand Button -->
                        <button type="button" id="btn-fullscreen" aria-label="Toggle Fullscreen"
                            class="absolute bottom-4 left-4 bg-black/60 hover:bg-black/80 text-white p-2.5 rounded-xl cursor-pointer backdrop-blur-md transition-all active:scale-90 border border-white/10 shadow-2xl z-30 flex items-center justify-center"
                            title="Toggle Fullscreen">
                            <span id="fullscreen-icon-wrapper"></span>
                        </button>
                    </div>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="flex-1 flex flex-col items-center justify-center p-6 bg-gray-50">
                        <i data-lucide="image-off" class="w-16 h-16 mx-auto mb-3 text-gray-300"></i>
                        <p class="font-medium text-gray-500">No original image archived</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($savedPaths)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const viewer = document.getElementById('xray-viewer-container');
                        const img = document.getElementById('xray-main-image');
                        const btnZoomOut = document.getElementById('btn-zoom-out');
                        const btnZoomIn = document.getElementById('btn-zoom-in');
                        const zoomLevelText = document.getElementById('zoom-level');
                        const btnFullscreen = document.getElementById('btn-fullscreen');
                        const fsIconWrapper = document.getElementById('fullscreen-icon-wrapper');
                        const btnPrev = document.getElementById('btn-prev-img');
                        const btnNext = document.getElementById('btn-next-img');
                        const counter = document.getElementById('xray-counter');
                        const thumbnails = document.querySelectorAll('.thumbnail-wrapper');
                        const thumbStripItems = document.querySelectorAll('.xray-thumb-item');

                        if (!img) return;

                        const imagePaths = <?= $jsonPaths ?>;
                        const imageLabels = <?= $jsonLabels ?>;
                        let currentIndex = 0;
                        let scale = 1;
                        const ZOOM_STEP = 0.2;
                        const MIN_ZOOM = 0.4;
                        const MAX_ZOOM = 5.0;

                        let isDragging = false;
                        let startX, startY, translateX = 0, translateY = 0;

                        // Load specific image
                        function loadImage(index) {
                            currentIndex = index;

                            // Reset zoom
                            scale = 1; translateX = 0; translateY = 0;
                            updateTransform();

                            // Update image source directly without opacity hacks to ensure it renders reliably
                            img.src = imagePaths[currentIndex];

                            // Initial Icon State
                            updateIconState();

                            // Update Counter
                            if (counter) counter.textContent = (currentIndex + 1) + ' / ' + imagePaths.length;

                            // Update Exam Name
                            const examNameEl = document.getElementById('xray-exam-name');
                            if (examNameEl && imageLabels && imageLabels[currentIndex]) {
                                examNameEl.textContent = imageLabels[currentIndex];
                                examNameEl.title = imageLabels[currentIndex];
                            }

                            // Update Filename
                            const filenameEl = document.getElementById('xray-filename');
                            if (filenameEl) {
                                filenameEl.textContent = 'IMG_' + (currentIndex + 1) + '_' + '<?= $caseDetails['case_number'] ?>';
                            }

                            // Update arrow opacities
                            if (btnPrev && btnNext) {
                                btnPrev.disabled = currentIndex === 0;
                                btnNext.disabled = currentIndex === imagePaths.length - 1;
                            }

                            // Update thumbnails
                            thumbnails.forEach((thumb, idx) => {
                                if (idx === currentIndex) {
                                    thumb.classList.add('border-red-500', 'opacity-100');
                                    thumb.classList.remove('border-black', 'opacity-50', 'hover:border-gray-500');
                                    thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                                } else {
                                    thumb.classList.remove('border-red-500', 'opacity-100');
                                    thumb.classList.add('border-black', 'opacity-50', 'hover:border-gray-500');
                                }
                            });

                            // Update floating strip
                            thumbStripItems.forEach((thumb, idx) => {
                                if (idx === currentIndex) {
                                    thumb.classList.add('border-red-500', 'bg-red-500/10', 'opacity-100');
                                    thumb.classList.remove('border-transparent', 'opacity-60');
                                } else {
                                    thumb.classList.remove('border-red-500', 'bg-red-500/10', 'opacity-100');
                                    thumb.classList.add('border-transparent', 'opacity-60');
                                }
                            });
                        }

                        // Navigation events
                        if (btnPrev) btnPrev.addEventListener('click', () => { if (currentIndex > 0) loadImage(currentIndex - 1); });
                        if (btnNext) btnNext.addEventListener('click', () => { if (currentIndex < imagePaths.length - 1) loadImage(currentIndex + 1); });

                        thumbnails.forEach(thumb => {
                            thumb.addEventListener('click', function () {
                                loadImage(parseInt(this.getAttribute('data-index')));
                            });
                        });

                        thumbStripItems.forEach(thumb => {
                            thumb.addEventListener('click', function () {
                                loadImage(parseInt(this.getAttribute('data-index')));
                            });
                        });

                        function updateTransform() {
                            if (scale <= 1) {
                                translateX = 0; translateY = 0;
                            } else {
                                const containerRect = img.parentElement.getBoundingClientRect();
                                const imgWidth = img.clientWidth * scale;
                                const imgHeight = img.clientHeight * scale;
                                const maxTx = Math.max(0, (imgWidth - containerRect.width) / 2);
                                const maxTy = Math.max(0, (imgHeight - containerRect.height) / 2);

                                if (translateX > maxTx) translateX = maxTx;
                                if (translateX < -maxTx) translateX = -maxTx;
                                if (translateY > maxTy) translateY = maxTy;
                                if (translateY < -maxTy) translateY = -maxTy;
                            }
                            img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
                            zoomLevelText.textContent = Math.round(scale * 100) + '%';
                            img.style.cursor = scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default';

                            updateIconState();
                        }

                        // Robust Icon State Management
                        const SVG_EXPAND = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';
                        const SVG_SHRINK = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>';

                        function updateIconState() {
                            if (!fsIconWrapper) return;
                            const isZoomed = Math.abs(scale - 1) > 0.01;
                            const isFull = !!document.fullscreenElement;

                            fsIconWrapper.innerHTML = (isZoomed || isFull) ? SVG_SHRINK : SVG_EXPAND;
                            btnFullscreen.title = isZoomed ? 'Reset Zoom' : (isFull ? 'Exit Fullscreen' : 'Toggle Fullscreen');
                        }

                        btnZoomIn.addEventListener('click', () => { if (scale < MAX_ZOOM) { scale += ZOOM_STEP; updateTransform(); } });
                        btnZoomOut.addEventListener('click', () => { if (scale > MIN_ZOOM) { scale -= ZOOM_STEP; updateTransform(); } });
                        zoomLevelText.addEventListener('click', () => { scale = 1; translateX = 0; translateY = 0; updateTransform(); });

                        btnFullscreen.addEventListener('click', () => {
                            if (!document.fullscreenElement) {
                                viewer.requestFullscreen().catch(() => { });
                            } else {
                                document.exitFullscreen();
                            }
                        });

                        document.addEventListener('fullscreenchange', () => {
                            const isFull = !!document.fullscreenElement;
                            if (isFull && document.fullscreenElement === viewer) {
                                viewer.classList.remove('h-[480px]', 'rounded-2xl', 'border');
                                viewer.classList.add('h-screen', 'rounded-none');
                            } else if (!isFull) {
                                viewer.classList.add('h-[480px]', 'rounded-2xl', 'border');
                                viewer.classList.remove('h-screen', 'rounded-none');
                            }
                            updateIconState();
                        });

                        // Panning logic
                        img.addEventListener('mousedown', (e) => {
                            if (scale > 1) {
                                e.preventDefault();
                                isDragging = true;
                                startX = e.clientX - translateX;
                                startY = e.clientY - translateY;
                                updateTransform();
                            }
                        });

                        document.addEventListener('mousemove', (e) => {
                            if (isDragging) {
                                translateX = e.clientX - startX;
                                translateY = e.clientY - startY;
                                updateTransform();
                            }
                        });

                        document.addEventListener('mouseup', () => { if (isDragging) { isDragging = false; updateTransform(); } });
                        document.addEventListener('mouseleave', () => { if (isDragging) { isDragging = false; updateTransform(); } });

                        viewer.addEventListener('wheel', (e) => {
                            // only intercept if zooming over image
                            if (e.target === img || isDragging) {
                                e.preventDefault();
                                if (e.deltaY < 0 && scale < MAX_ZOOM) scale += ZOOM_STEP;
                                else if (e.deltaY > 0 && scale > MIN_ZOOM) scale -= ZOOM_STEP;
                                scale = Math.round(scale * 10) / 10;
                                updateTransform();
                            }
                        }, { passive: false });

                        // Final Step: Load the first image and initialize icon state
                        if (imagePaths && imagePaths.length > 0) {
                            loadImage(0);
                        } else {
                            updateIconState();
                        }
                    });
                </script>
            <?php endif; ?>
        </div>

    </div> <!-- End Main Content Container -->
    </div> <!-- End Main Content Area -->
</div> <!-- End Sticky Layout Wrapper -->

    <script>
        // Clean URL to hide id and role parameters
        if (window.history && window.history.replaceState) {
            try {
                const cleanUrl = window.location.pathname;
                window.history.replaceState(null, document.title, cleanUrl);
            } catch (e) {}
        }

        function openReportViewer(url) {
            // Open the report in a popup window similar to the COR viewer
            const popupWidth = 850;
            const popupHeight = 800;
            const left = (screen.width - popupWidth) / 2;
            const top = (screen.height - popupHeight) / 2;

            window.open(url, 'ReportViewer', `width=${popupWidth},height=${popupHeight},top=${top},left=${left},scrollbars=yes,resizable=yes`);
        }

    </script>

    <!-- ========================================== -->
    <!-- NATIVE MODAL: MARK RESULT AS CLAIMED       -->
    <!-- ========================================== -->
    <div id="modalMarkClaimHistory" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target === this) closeClaimModalHistory()">
        <div class="flex min-h-screen items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative w-full max-w-lg rounded-2xl bg-white text-left shadow-2xl transition-all border border-gray-100 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-150">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center border border-red-100 shrink-0">
                            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 leading-tight">Mark Result as Claimed</h3>
                            <p class="text-xs text-gray-500">Verify receiver and confirm physical result release</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeClaimModalHistory()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Form -->
                <form id="formMarkClaimHistory" onsubmit="submitMarkClaimHistory(event)" class="p-5 space-y-3.5">
                    <input type="hidden" id="claim_hist_case_id">

                    <!-- Summary Info Card -->
                    <div class="p-3 bg-gray-50/80 border border-gray-200/80 rounded-xl shadow-2xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Case Number</span>
                        <div class="text-sm font-black text-gray-900 font-mono tracking-tight" id="claim_hist_modal_case_no">—</div>
                        <div class="text-xs font-semibold text-gray-700 flex items-center gap-1.5 mt-0.5 truncate">
                            <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
                            <span class="truncate" id="claim_hist_modal_pat_name">—</span>
                        </div>
                    </div>

                    <!-- Verification Tip -->
                    <div class="flex items-center gap-2 p-2.5 bg-blue-50/70 border border-blue-100 rounded-xl text-xs text-blue-800">
                        <i data-lucide="shield-check" class="w-4 h-4 text-blue-600 shrink-0"></i>
                        <span>Please verify receiver ID before handing over the official printed film & envelope.</span>
                    </div>

                    <!-- Receiver Type Selector -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Who is claiming? <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" id="btn-hist-type-self" onclick="setClaimHistoryReceiverType('Self')" class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border-2 border-red-500 bg-red-50 text-red-700 font-bold text-xs transition cursor-pointer shadow-2xs">
                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i> Patient (Self)
                            </button>
                            <button type="button" id="btn-hist-type-rep" onclick="setClaimHistoryReceiverType('Representative')" class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-gray-200 bg-white text-gray-600 font-semibold text-xs hover:bg-gray-50 transition cursor-pointer">
                                <i data-lucide="users" class="w-3.5 h-3.5"></i> Representative
                            </button>
                        </div>
                        <input type="hidden" id="claim_hist_receiver_type" value="Self">
                    </div>

                    <!-- Receiver Name & Relationship -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Claimed By (Full Name) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="claim_hist_receiver_name" required class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs font-medium" placeholder="Full name of receiver">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Relationship <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="claim_hist_relationship" value="Self" disabled class="w-full rounded-xl border border-gray-300 bg-gray-100/70 px-3 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs disabled:cursor-not-allowed font-medium" placeholder="e.g. Spouse, Parent, Child">
                        </div>
                    </div>

                    <!-- Valid ID (Representative Only) -->
                    <div id="claim_hist_id_wrapper" class="hidden space-y-2.5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Valid ID Presented <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="claim_hist_id_type" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs font-medium" placeholder="e.g. PhilHealth ID, Driver's License, National ID">
                            <!-- Quick Chips -->
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                <button type="button" onclick="document.getElementById('claim_hist_id_type').value = 'National ID (PhilSys)'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">National ID</button>
                                <button type="button" onclick="document.getElementById('claim_hist_id_type').value = 'PhilHealth ID'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">PhilHealth</button>
                                <button type="button" onclick="document.getElementById('claim_hist_id_type').value = 'Driver\'s License'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">Driver's License</button>
                                <button type="button" onclick="document.getElementById('claim_hist_id_type').value = 'UMID'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">UMID</button>
                                <button type="button" onclick="document.getElementById('claim_hist_id_type').value = 'Passport'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">Passport</button>
                                <button type="button" onclick="document.getElementById('claim_hist_id_type').value = 'Company / School ID'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">Company ID</button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                ID Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="claim_hist_id_number" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs font-medium" placeholder="e.g. 1234-5678-9012">
                        </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="closeClaimModalHistory()" class="px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" id="btnSubmitClaimHistory" class="inline-flex items-center gap-1.5 px-5 py-2 text-xs sm:text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition active:scale-95 cursor-pointer">
                            <i data-lucide="check" class="w-4 h-4"></i> Confirm Claim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- NATIVE MODAL: RESULT CLAIM DETAILS         -->
    <!-- ========================================== -->
    <div id="modalClaimDetailsHistory" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target === this) closeClaimDetailsModalHistory()">
        <div class="flex min-h-screen items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative w-full max-w-md rounded-2xl bg-white text-left shadow-2xl transition-all border border-gray-100 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-150">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 leading-tight">Result Claim Details</h3>
                            <p class="text-xs text-gray-500">Physical result pickup verification record</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeClaimDetailsModalHistory()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 space-y-3.5">
                    <!-- Summary Card -->
                    <div class="p-3.5 bg-emerald-50/80 border border-emerald-200/80 rounded-xl flex items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-9 w-9 rounded-lg bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-700 shrink-0">
                                <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md uppercase tracking-wide" id="detail_hist_case_no">Case #—</span>
                                <p class="text-sm font-bold text-gray-900 mt-0.5 truncate" id="detail_hist_pat_name">—</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-bold text-emerald-800 block" id="detail_hist_claimed_at">—</span>
                        </div>
                    </div>

                    <!-- Info List Card -->
                    <div class="bg-gray-50/80 border border-gray-200/80 rounded-xl p-3.5 space-y-2.5">
                        <div class="flex items-center justify-between border-b border-gray-200/60 pb-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Claimed By</span>
                            <span class="text-xs sm:text-sm font-bold text-gray-900" id="detail_hist_claimed_by">—</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-gray-200/60 pb-2" id="detail_hist_row_rel">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Relationship</span>
                            <span class="text-xs font-semibold text-gray-800 bg-white border border-gray-200 px-2 py-0.5 rounded-md shadow-2xs" id="detail_hist_relationship">—</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-gray-200/60 pb-2" id="detail_hist_row_id">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">ID Presented</span>
                            <span class="text-xs font-semibold text-gray-800" id="detail_hist_id_presented">—</span>
                        </div>
                        <div class="flex flex-col gap-1 pt-0.5" id="detail_hist_row_notes">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pickup Notes</span>
                            <p class="text-xs text-gray-700 bg-white p-2.5 rounded-lg border border-gray-200/80 italic leading-relaxed shadow-2xs" id="detail_hist_notes">—</p>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end">
                        <button type="button" onclick="closeClaimDetailsModalHistory()" class="px-5 py-2 text-xs sm:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-xl transition shadow-2xs cursor-pointer active:scale-95">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let activeHistPatientName = '';

        function openClaimDetailsModalHistory(info) {
            const modal = document.getElementById('modalClaimDetailsHistory');
            if (!modal || !info) return;

            document.getElementById('detail_hist_case_no').textContent = `Case #${info.case_number}`;
            document.getElementById('detail_hist_pat_name').textContent = info.patient_name;
            document.getElementById('detail_hist_claimed_at').textContent = info.claimed_at || '—';
            document.getElementById('detail_hist_claimed_by').textContent = info.claimed_by || '—';
            
            const relVal = (info.claimed_relationship || 'Self').trim();
            document.getElementById('detail_hist_relationship').textContent = relVal;

            // Conditionally show/hide ID Presented (only if representative with valid ID recorded)
            const idRow = document.getElementById('detail_hist_row_id');
            const idVal = (info.claimed_id_presented || '').trim();
            if (idRow) {
                if (idVal && idVal !== 'None Specified' && idVal !== 'None' && idVal !== '—' && relVal.toLowerCase() !== 'self') {
                    document.getElementById('detail_hist_id_presented').textContent = idVal;
                    idRow.classList.remove('hidden');
                } else {
                    idRow.classList.add('hidden');
                }
            }

            // Conditionally show/hide Pickup Notes (only if notes actually exist)
            const notesRow = document.getElementById('detail_hist_row_notes');
            const notesVal = (info.claimed_notes || '').trim();
            if (notesRow) {
                if (notesVal && notesVal !== 'No additional notes' && notesVal !== 'No additional pickup notes provided.' && notesVal !== 'None' && notesVal !== '—') {
                    document.getElementById('detail_hist_notes').textContent = notesVal;
                    notesRow.classList.remove('hidden');
                } else {
                    notesRow.classList.add('hidden');
                }
            }

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }

        function closeClaimDetailsModalHistory() {
            const modal = document.getElementById('modalClaimDetailsHistory');
            if (modal) modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function openClaimModalHistory(caseId, caseNumber, patientName, procedureName = 'X-ray') {
            const modal = document.getElementById('modalMarkClaimHistory');
            if (!modal) return;

            activeHistPatientName = patientName || 'Patient';

            document.getElementById('claim_hist_case_id').value = caseId;
            document.getElementById('claim_hist_modal_case_no').textContent = caseNumber;
            document.getElementById('claim_hist_modal_pat_name').textContent = activeHistPatientName;
            const procHistEl = document.getElementById('claim_hist_modal_procedure');
            if (procHistEl) procHistEl.textContent = procedureName || 'X-ray';

            document.getElementById('claim_hist_receiver_name').value = activeHistPatientName;
            document.getElementById('claim_hist_relationship').value = 'Self';
            document.getElementById('claim_hist_relationship').disabled = true;
            if (document.getElementById('claim_hist_id_type')) document.getElementById('claim_hist_id_type').value = '';
            if (document.getElementById('claim_hist_id_number')) document.getElementById('claim_hist_id_number').value = '';
            if (document.getElementById('claim_hist_notes')) document.getElementById('claim_hist_notes').value = '';

            setClaimHistoryReceiverType('Self');

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }

        function closeClaimModalHistory() {
            const modal = document.getElementById('modalMarkClaimHistory');
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function setClaimHistoryReceiverType(type) {
            const btnSelf = document.getElementById('btn-hist-type-self');
            const btnRep = document.getElementById('btn-hist-type-rep');
            const hiddenType = document.getElementById('claim_hist_receiver_type');
            const nameInput = document.getElementById('claim_hist_receiver_name');
            const relInput = document.getElementById('claim_hist_relationship');
            const idWrapper = document.getElementById('claim_hist_id_wrapper');
            const idTypeInput = document.getElementById('claim_hist_id_type');
            const idNumberInput = document.getElementById('claim_hist_id_number');

            if (!btnSelf || !btnRep) return;

            hiddenType.value = type;

            if (type === 'Self') {
                btnSelf.className = 'flex items-center justify-center gap-2 py-2 px-3 rounded-xl border-2 border-red-500 bg-red-50 text-red-700 font-bold text-xs transition cursor-pointer shadow-2xs';
                btnRep.className = 'flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-gray-200 bg-white text-gray-600 font-semibold text-xs hover:bg-gray-50 transition cursor-pointer';
                nameInput.value = activeHistPatientName;
                relInput.value = 'Self';
                relInput.disabled = true;
                relInput.classList.add('bg-gray-100/70', 'cursor-not-allowed');
                if (idWrapper) idWrapper.classList.add('hidden');
                if (idTypeInput) idTypeInput.value = '';
                if (idNumberInput) idNumberInput.value = '';
            } else {
                btnRep.className = 'flex items-center justify-center gap-2 py-2 px-3 rounded-xl border-2 border-red-500 bg-red-50 text-red-700 font-bold text-xs transition cursor-pointer shadow-2xs';
                btnSelf.className = 'flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-gray-200 bg-white text-gray-600 font-semibold text-xs hover:bg-gray-50 transition cursor-pointer';
                nameInput.value = '';
                relInput.value = '';
                relInput.disabled = false;
                relInput.classList.remove('bg-gray-100/70', 'cursor-not-allowed');
                if (idWrapper) idWrapper.classList.remove('hidden');
                nameInput.focus();
            }

            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }

        function submitMarkClaimHistory(e) {
            if (e) e.preventDefault();

            const caseId = document.getElementById('claim_hist_case_id')?.value;
            const caseNo = document.getElementById('claim_hist_modal_case_no')?.textContent || 'this record';
            const receiverType = document.getElementById('claim_hist_receiver_type')?.value || 'Self';
            const nameInput = document.getElementById('claim_hist_receiver_name');
            const relInput = document.getElementById('claim_hist_relationship');
            const idTypeInput = document.getElementById('claim_hist_id_type');
            const idNumInput = document.getElementById('claim_hist_id_number');

            const receiverName = (nameInput?.value || '').trim();
            const relationship = (relInput?.value || '').trim();
            const idType = (idTypeInput?.value || '').trim();
            const idNumber = (idNumInput?.value || '').trim();
            const notes = (document.getElementById('claim_hist_notes')?.value || '').trim();
            const btnSubmit = document.getElementById('btnSubmitClaimHistory');

            [nameInput, relInput, idTypeInput, idNumInput].forEach(inp => {
                if (inp) {
                    inp.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                    inp.classList.add('border-gray-300');
                }
            });

            if (!receiverName) {
                if (nameInput) {
                    nameInput.classList.remove('border-gray-300');
                    nameInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                    nameInput.focus();
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Receiver Name Required',
                    text: 'Please enter the name of the person claiming the result.',
                    customClass: { container: '!z-[999999]', popup: 'rounded-2xl p-5' }
                });
                return;
            }

            if (receiverType === 'Representative') {
                if (!relationship) {
                    if (relInput) {
                        relInput.classList.remove('border-gray-300');
                        relInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                        relInput.focus();
                    }
                    Swal.fire({
                        icon: 'warning',
                        title: 'Relationship Required',
                        text: 'Please specify the relationship of the representative to the patient.',
                        customClass: { container: '!z-[999999]', popup: 'rounded-2xl p-5' }
                    });
                    return;
                }
                if (!idType) {
                    if (idTypeInput) {
                        idTypeInput.classList.remove('border-gray-300');
                        idTypeInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                        idTypeInput.focus();
                    }
                    Swal.fire({
                        icon: 'warning',
                        title: 'Valid ID Type Required',
                        text: 'Please enter or select the Valid ID presented by the representative.',
                        customClass: { container: '!z-[999999]', popup: 'rounded-2xl p-5' }
                    });
                    return;
                }
                if (!idNumber) {
                    if (idNumInput) {
                        idNumInput.classList.remove('border-gray-300');
                        idNumInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                        idNumInput.focus();
                    }
                    Swal.fire({
                        icon: 'warning',
                        title: 'ID Number Required',
                        text: 'Please enter the ID Number of the presented ID for verification.',
                        customClass: { container: '!z-[999999]', popup: 'rounded-2xl p-5' }
                    });
                    return;
                }
            }

            let fullIdPresented = '';
            if (receiverType === 'Representative') {
                fullIdPresented = `${idType} (#${idNumber})`;
            }

            const proceedWithHistorySubmission = () => {
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Saving...';
                    if (window.lucide) lucide.createIcons();
                }

                fetch('app/api/claim_case.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        case_id: caseId,
                        action: 'mark_claimed',
                        claimed_by: receiverName,
                        claimed_relationship: relationship || 'Self',
                        claimed_id_presented: fullIdPresented,
                        claimed_notes: notes
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeClaimModalHistory();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Marked as Claimed!',
                                text: data.message || 'X-ray result has been marked as Claimed.',
                                timer: 1500,
                                showConfirmButton: false,
                                customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                            }).then(() => window.location.reload());
                        } else {
                            window.location.reload();
                        }
                    } else {
                        if (btnSubmit) {
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Confirm Claim';
                            if (window.lucide) lucide.createIcons();
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: data.message || 'Failed to update claim status.',
                                customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                            });
                        }
                    }
                })
                .catch(err => {
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Confirm Claim';
                        if (window.lucide) lucide.createIcons();
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while communicating with the server.',
                            customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                        });
                    }
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Confirm Result Claim?',
                    html: `Are you sure you want to mark Case <b>${caseNo}</b> as claimed by <b>${receiverName}</b>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Confirm Claim',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    reverseButtons: true,
                    customClass: {
                        container: '!z-[999999]',
                        popup: 'rounded-2xl p-5'
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        proceedWithHistorySubmission();
                    }
                });
            } else {
                if (confirm(`Are you sure you want to mark Case ${caseNo} as Claimed by ${receiverName}?`)) {
                    proceedWithHistorySubmission();
                }
            }
        }
    </script>

