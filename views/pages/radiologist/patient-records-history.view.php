<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/CaseModel.php';

$caseModel = new \CaseModel($pdo);

$caseId = $_GET['id'] ?? 0;

// Fetch case details (Backend logic)
$caseDetails = $caseModel->getCaseById($caseId);

if (!$caseDetails) {
    echo "<div class='p-6 mt-10 text-center text-red-600 bg-red-50 rounded-lg'>Case not found or invalid ID.</div>";
    exit;
}

$fullName = htmlspecialchars($caseDetails['first_name'] . ' ' . $caseDetails['last_name']);

// Parse uploaded images (JSON array or legacy single path)
$imagePaths = [];
if (!empty($caseDetails['image_path'])) {
    $decoded   = json_decode($caseDetails['image_path'], true);
    $rawPaths  = is_array($decoded) ? $decoded : [$caseDetails['image_path']];
    foreach ($rawPaths as $p) {
        $imagePaths[] = '/' . PROJECT_DIR . '/' . ltrim($p, '/');
    }
}
?>

<!-- Header nav -->
<div class="mb-4">
    <a href="?role=radiologist&page=patient-history"
        class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 transition">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to Patient History
    </a>
</div>

<!-- Title Section -->
<div class="flex items-start justify-between mb-6">
    <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">
            <?= htmlspecialchars($caseDetails['case_number'] ?? 'N/A') ?>
        </h2>
        <p class="text-gray-500 text-sm mt-0.5"><?= htmlspecialchars($caseDetails['branch_name']) ?> Branch </p>
    </div>
    <div class="flex items-center gap-2 mt-1">
        <?php
        $pColor = 'blue';
        if ($caseDetails['priority'] === 'Emergency')
            $pColor = 'red';
        if ($caseDetails['priority'] === 'Urgent')
            $pColor = 'orange';
        if ($caseDetails['priority'] === 'Priority')
            $pColor = 'yellow';
        ?>
        <span
            class="inline-flex items-center rounded-full border border-<?= $pColor ?>-400 bg-<?= $pColor ?>-50 px-3 py-1 text-xs font-semibold text-<?= $pColor ?>-700 shadow-sm">
            <?= htmlspecialchars($caseDetails['priority']) ?>
        </span>

        <?php
        $sColor = match ($caseDetails['status']) {
            'Completed' => 'green',
            'Report Ready' => 'purple',
            'Under Reading' => 'blue',
            'Pending' => 'gray',
            default => 'gray'
        };
        ?>
        <span
            class="inline-flex items-center text-<?= $sColor ?>-600 border border-<?= $sColor ?>-400 rounded-full bg-<?= $sColor ?>-50 px-3 py-1 text-xs font-semibold shadow-sm">
            <?= htmlspecialchars($caseDetails['status']) ?>
        </span>
    </div>
</div>

<!-- ══ Row 1: Info Cards (Patient + RadTech) ══ -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">

        <!-- Patient Information -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
            <div class="flex items-center gap-2 mb-4 border-b border-gray-50 pb-2">
                <i data-lucide="user" class="w-4 h-4 text-red-500"></i>
                <h3 class="font-bold text-gray-800 text-sm">Patient Information</h3>
            </div>
            <div class="grid grid-cols-2 gap-y-4 gap-x-2 text-sm pl-2">
                <div>
                    <p class="text-gray-400 text-xs">Name</p>
                    <p class="font-medium text-gray-900"><?= $fullName ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Age / Sex</p>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['age']) ?> /
                        <?= htmlspecialchars(ucfirst($caseDetails['sex'])) ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Patient Number</p>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['patient_number']) ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Branch</p>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['branch_name']) ?> Branch
                    </p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Exam Type</p>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($caseDetails['exam_type']) ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Date</p>
                    <p class="font-medium text-gray-900"><?= date('Y-m-d', strtotime($caseDetails['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <!-- RadTech Information -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
            <div class="flex items-center gap-2 mb-4 border-b border-gray-50 pb-2">
                <i data-lucide="activity" class="w-4 h-4 text-red-500"></i>
                <h3 class="font-bold text-gray-800 text-sm">RadTech Information</h3>
            </div>
            <div class="grid grid-cols-2 gap-y-4 gap-x-2 text-sm pl-2">
                <div class="col-span-2">
                    <p class="text-gray-400 text-xs">Radiologic Technologist</p>
                    <?php
                    $rtNameRaw = !empty($caseDetails['radtech_name']) ? $caseDetails['radtech_name'] : ('RT ' . $caseDetails['branch_name']);
                    $rtDisplay = ucwords(str_replace('.', ' ', $rtNameRaw));
                    if (!empty($caseDetails['radtech_title'])) {
                        $rtDisplay .= ', ' . $caseDetails['radtech_title'];
                    }
                    ?>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($rtDisplay) ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Exam Completed</p>
                    <p class="font-medium text-gray-900"><?= date('Y-m-d', strtotime($caseDetails['created_at'])) ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Upload Time</p>
                    <p class="font-medium text-gray-900"><?= date('h:i A', strtotime($caseDetails['created_at'])) ?></p>
                </div>
            </div>
        </div>
</div>

<!-- ══ Row 2: Viewer + Report Editor ══ -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start">

    <!-- LEFT: Image Viewer -->
    <div class="space-y-4">
        <!-- Enhanced Image Viewer -->
        <div id="dicom-viewer" class="bg-[#0a0a0a] border border-gray-200 rounded-2xl overflow-hidden shadow-2xl flex flex-col h-[480px] relative transition-all w-full">
            
            <!-- Classic Integrated Header Toolbar -->
            <div class="bg-red-600 px-5 h-14 flex justify-between items-center text-white z-20 w-full select-none shadow-lg"
                id="dicom-toolbar">
                
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/20 shadow-inner">
                        <i data-lucide="scan-line" class="w-5 h-5"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-black text-xs uppercase tracking-widest leading-none">X-ray Viewer</span>
                        <?php if (count($imagePaths) > 1): ?>
                            <span id="img-counter" class="text-[9px] font-bold text-white/60 tracking-tighter uppercase mt-1">
                                Image 1 / <?= count($imagePaths) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- Zoom Controls -->
                <div class="flex items-center gap-3 bg-black/20 rounded-xl px-3 py-1.5 border border-white/5 shadow-inner">
                    <button id="btn-zoom-out" class="text-white/60 hover:text-white transition-colors" title="Zoom Out">
                        <i data-lucide="minus-circle" class="w-4 h-4"></i>
                    </button>
                    <span id="zoom-level" class="text-[10px] font-black text-white min-w-[35px] text-center tabular-nums">100%</span>
                    <button id="btn-zoom-in" class="text-white/60 hover:text-white transition-colors" title="Zoom In">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Main Viewing Area -->
            <div class="flex-1 flex flex-col items-center justify-center relative bg-[#0a0a0a] overflow-hidden group/viewer" id="img-canvas">
                <?php if (!empty($imagePaths)): ?>
                    <?php foreach ($imagePaths as $idx => $iPath): ?>
                    <img id="xray-main-image-<?= $idx ?>" src="<?= htmlspecialchars($iPath) ?>"
                         data-img-index="<?= $idx ?>"
                         class="dicom-img max-w-full max-h-full object-contain transition-transform duration-100 ease-out origin-center absolute inset-0 m-auto <?= $idx > 0 ? 'hidden' : '' ?>"
                         alt="X-ray <?= $idx + 1 ?>">
                    <?php endforeach; ?>

                    <!-- Floating Side Navigation (Fullscreen Only) -->
                    <?php if (count($imagePaths) > 1): ?>
                        <button id="btn-prev-side" class="hidden absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[30] group" title="Previous Image">
                            <i data-lucide="chevron-left" class="w-7 h-7 group-hover:-translate-x-0.5 transition-transform"></i>
                        </button>
                        <button id="btn-next-side" class="hidden absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[30] group" title="Next Image">
                            <i data-lucide="chevron-right" class="w-7 h-7 group-hover:translate-x-0.5 transition-transform"></i>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center">
                        <i data-lucide="file-scan" class="w-12 h-12 text-white/20 mb-2 mx-auto"></i>
                        <p class="text-white/40 text-sm font-medium">DICOM Image Placeholder</p>
                        <p class="text-white/25 text-xs mt-1"><?= htmlspecialchars($caseDetails['exam_type']) ?> — <?= $fullName ?></p>
                    </div>
                <?php endif; ?>

                <!-- Classic Bottom Thumbnails -->
                <?php if (count($imagePaths) > 1): ?>
                    <div id="xray-thumb-strip" class="absolute bottom-4 left-1/2 -translate-x-1/2 h-16 bg-black/40 backdrop-blur-md rounded-2xl flex items-center px-4 gap-3 z-20 border border-white/10 shadow-2xl overflow-x-auto max-w-[90%] scrollbar-hide">
                        <?php foreach ($imagePaths as $index => $path): ?>
                            <div class="xray-thumb-item flex-shrink-0 w-10 h-10 rounded-xl border-2 <?= $index === 0 ? 'border-red-500 bg-red-500/10' : 'border-transparent opacity-60' ?> overflow-hidden cursor-pointer transition-all hover:scale-110 hover:opacity-100"
                                data-index="<?= $index ?>" data-url="<?= htmlspecialchars($path) ?>">
                                <img src="<?= htmlspecialchars($path) ?>" class="w-full h-full object-cover">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Expand Button -->
                <button type="button" id="btn-fullscreen-wrapper" title="Toggle Fullscreen"
                        class="absolute bottom-4 left-4 bg-black/60 hover:bg-black/80 text-white/90 hover:text-white p-2.5 rounded-xl cursor-pointer backdrop-blur-md transition-all active:scale-90 border border-white/10 shadow-2xl z-30 flex items-center justify-center">
                    <span id="fullscreen-icon-wrap"></span>
                </button>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const images   = document.querySelectorAll('.dicom-img');
                const counter  = document.getElementById('img-counter');
                const strip    = document.querySelectorAll('.strip-thumb');
                let currentImg = 0;
                let scale = 1, tx = 0, ty = 0, isDragging = false, sx = 0, sy = 0;

                const zoomLevelEl   = document.getElementById('zoom-level');
                const btnZoomIn     = document.getElementById('btn-zoom-in');
                const btnZoomOut    = document.getElementById('btn-zoom-out');
                const dicomViewer   = document.getElementById('dicom-viewer');
                const btnFullscreen = document.getElementById('btn-fullscreen-wrapper');

                function showImage(idx) {
                    currentImg = idx;
                    images.forEach((img, i) => img.classList.toggle('hidden', i !== idx));
                    
                    // Update counter
                    if (counter) counter.textContent = `${idx + 1} / ${images.length}`;
                    
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

                document.getElementById('btn-img-prev')?.addEventListener('click', () => showImage(Math.max(0, currentImg - 1)));
                document.getElementById('btn-img-next')?.addEventListener('click', () => showImage(Math.min(images.length - 1, currentImg + 1)));
                
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
                document.addEventListener('mouseleave', () => { if (isDragging) { isDragging = false; applyTransform(); } });

                // Inline SVGs so the icon never goes missing after Lucide replaces <i> tags
                const SVG_EXPAND = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';
                const SVG_SHRINK  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>';

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

                const btnPrev = document.getElementById('btn-prev-side');
                const btnNext = document.getElementById('btn-next-side');

                btnPrev?.addEventListener('click', () => { if(currentImg > 0) showImage(currentImg - 1); });
                btnNext?.addEventListener('click', () => { if(currentImg < images.length - 1) showImage(currentImg + 1); });
                document.addEventListener('fullscreenchange', () => {
                    const full = !!document.fullscreenElement;
                    dicomViewer.style.height = full ? '100vh' : '400px';
                    dicomViewer.classList.toggle('rounded-xl', !full);
                    
                    // Toggle Side Arrows visibility based on Fullscreen state
                    if (btnPrev) btnPrev.classList.toggle('hidden', !full);
                    if (btnNext) btnNext.classList.toggle('hidden', !full);
                    
                    updateIconState();
                });

                if (images.length > 0) showImage(0);
            });
        </script>
    </div>

    <!-- RIGHT SIDE: Report Editor (Read Only) -->
    <div class="bg-white border text-sm border-gray-200 shadow-sm rounded-xl flex flex-col p-3 border-t-gray-100">

        <div class="bg-red-600 px-5 h-14 flex items-center gap-3 text-white shadow-lg z-10 w-full rounded-t-xl">
            <div class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/20 shadow-inner">
                <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
            </div>
            <span class="font-black text-xs uppercase tracking-widest">Findings Report</span>
        </div>

        <div class="flex-1 flex flex-col px-3 pb-2 mt-4">

            <div class="space-y-4 flex-1">
                <?php
                // Parse multi-exam json if present
                $rawF = $caseDetails['findings'] ?? '';
                $isMulti = false;
                $reports = [];
                if (!empty($rawF) && $rawF[0] === '{') {
                    $decoded = json_decode($rawF, true);
                    if (is_array($decoded)) {
                        $isMulti = true;
                        $reports = $decoded;
                    }
                }

                if ($isMulti):
                    foreach ($reports as $examName => $data):
                        ?>
                        <div class="mb-4">
                            <h4 class="font-bold text-red-600 text-xs mb-2 uppercase"><?= htmlspecialchars($examName) ?></h4>
                            <div class="mb-3">
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Findings</label>
                                <div
                                    class="w-full rounded-lg border border-gray-100 border-l-4 border-l-red-500 bg-white px-4 py-3 text-sm text-gray-800 whitespace-pre-wrap">
                                    <?= htmlspecialchars($data['findings'] ?: 'None') ?>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Impression</label>
                                <div
                                    class="w-full rounded-lg border border-gray-100 border-l-4 border-l-red-500 bg-gray-50 px-4 py-3 text-sm text-gray-800 whitespace-pre-wrap">
                                    <?= htmlspecialchars($data['impression'] ?: 'None') ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    endforeach;
                else:
                    ?>
                    <!-- Findings -->
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Findings</label>
                        <div
                            class="w-full rounded-lg border border-gray-100 border-l-4 border-l-red-500 bg-gray-50 px-4 py-3 text-sm text-gray-800 whitespace-pre-wrap">
                            <?= htmlspecialchars($caseDetails['findings'] ?: 'None') ?>
                        </div>
                    </div>

                    <!-- Impression -->
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Impression</label>
                        <div
                            class="w-full rounded-lg border border-gray-100 border-l-4 border-l-red-500 bg-gray-50 px-4 py-3 text-sm text-gray-800 whitespace-pre-wrap">
                            <?= htmlspecialchars($caseDetails['impression'] ?: 'None') ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 flex flex-col items-center">
                <?php if (!empty($caseDetails['date_completed'])): ?>
                    <p class="text-xs text-gray-400">Report Completed on
                        <?= date('M d, Y h:i A', strtotime($caseDetails['date_completed'])) ?>
                    </p>
                <?php else: ?>
                    <p class="text-xs text-gray-400 font-medium">Report not yet completed</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>