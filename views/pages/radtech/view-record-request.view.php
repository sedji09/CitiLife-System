<?php
/**
 * View Record Request Details View
 * Backend logic handled by ViewRecordRequestController.php
 */
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">

    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="index.php?role=radtech&page=record-request"
                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-700 transition">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    Record Request Details
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border <?= $statusColorClass ?>">
                        <?= htmlspecialchars($request['status']) ?>
                    </span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Requested on
                    <?= date('F j, Y, h:i A', strtotime($request['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Patient Information</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Case No.</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900">
                        <?= htmlspecialchars($request['patient_no']) ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Patient Name</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900">
                        <?= htmlspecialchars($request['patient_name']) ?>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Request Details</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Exam Type</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900">
                        <?= htmlspecialchars($request['exam_type']) ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Target Branch</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-sm border border-blue-100">
                            <i data-lucide="building-2" class="w-4 h-4"></i>
                            <?= htmlspecialchars($request['request_branch']) ?>
                        </span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Reason for Request</dt>
                    <dd
                        class="mt-2 text-sm text-gray-800 bg-gray-50 rounded-lg p-4 border border-gray-100 leading-relaxed">
                        <?= nl2br(htmlspecialchars($request['reason'])) ?>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Case Report Section -->
    <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">
        <?php if ($request['status'] === 'Approved' && $caseDetails): ?>

            <div class="flex items-start justify-between border-b border-gray-300 pb-4 mb-8">
                <div>
                    <h3 class="text-[22px] font-bold text-gray-900">Requested Findings Report and Images</h3>
                    <span
                        class="text-gray-400 text-sm mt-1"><?= date('M d, Y', strtotime($caseDetails['created_at'])) ?></span>
                </div>
                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <a href="/<?= PROJECT_DIR ?>/app/views/pages/radtech/print-report.php?id=<?= $caseDetails['id'] ?>&download=true"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        <i data-lucide="download" class="w-4 h-4"></i> Download PDF
                    </a>
                    <a href="/<?= PROJECT_DIR ?>/app/views/pages/radtech/print-report.php?id=<?= $caseDetails['id'] ?>"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#f3f4f6] hover:bg-[#e5e7eb] text-[#1f2937] text-sm font-semibold rounded-lg shadow-sm border border-gray-300 transition">
                        <i data-lucide="printer" class="w-4 h-4"></i> Print
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Findings Report -->
                <div class="flex flex-col border border-gray-200 rounded-2xl overflow-hidden">
                    <div
                        class="bg-red-600 px-5 h-14 flex items-center gap-3 text-white shadow-lg z-10 w-full rounded-t-2xl">
                        <div class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/20 shadow-inner">
                            <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
                        </div>
                        <span class="font-black text-xs uppercase tracking-widest">Findings Report</span>
                    </div>
                    <div id="findings-viewer-container"
                        class="flex-1 h-[480px] flex flex-col transition-all overflow-hidden p-4 bg-white border-x border-b border-gray-100 rounded-b-2xl">
                        <?php if ($caseDetails['status'] === 'Completed'): ?>
                            <?php
                            // Clean JSON preparation for the previewer
                            $caseNum = htmlspecialchars($caseDetails['case_number']);
                            $patientName = str_replace(' ', '_', $request['patient_name']);

                            // Look for static photo reports generated at Release
                            $photoPattern = __DIR__ . "/../../../../public/uploads/reports/{$caseDetails['case_number']}_page_*.jpg";
                            $photos = glob($photoPattern);
                            $previewItems = [];

                            if (!empty($photos)) {
                                natsort($photos);
                                foreach ($photos as $photoFile) {
                                    $baseUrl = "/" . PROJECT_DIR . "/public/uploads/reports/" . basename($photoFile);
                                    $previewItems[] = ['type' => 'report_image', 'url' => $baseUrl, 'name' => "REPORT_" . $caseNum];
                                }
                                $thumbnailUrl = $previewItems[0]['url']; // Use first page as thumbnail
                            } else {
                                // Fallback if photo not yet generated
                                $reportUrl = "/" . PROJECT_DIR . "/app/views/pages/radtech/print-report.php?id=" . $caseDetails['id'] . "&preview=true";
                                $previewItems = [['type' => 'report', 'url' => $reportUrl, 'name' => "REPORT_" . $caseNum . "_" . $patientName]];
                                $thumbnailUrl = false;
                                $miniUrl = $reportUrl . "&single_page=true";
                            }

                            $jsonItems = htmlspecialchars(json_encode($previewItems), ENT_QUOTES, 'UTF-8');
                            $reportName = "REPORT_" . $caseNum . ".jpg";
                            ?>

                            <!-- GDrive Style Card -->
                            <div
                                class="flex-1 flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden group/card relative">
                                <!-- Card Header -->
                                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200 diagnostic-card-header">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center flex-shrink-0 border border-red-500/20">
                                            <i data-lucide="file-text" class="w-4 h-4 text-red-500"></i>
                                        </div>
                                        <span class="text-[10px] font-black text-gray-900 uppercase tracking-widest truncate" id="findings-report-name"><?= $reportName ?></span>
                                    </div>
                                </div>

                                <!-- Card Body (The Floating Preview) -->
                                <div class="flex-1 relative bg-white overflow-hidden cursor-zoom-in flex items-center justify-center p-4 group-hover/card:bg-gray-50 transition-colors diagnostic-card-body"
                                    onclick="if(window.DrivePreviewer) DrivePreviewer.open(<?= $jsonItems ?>, 0)">

                                    <?php if ($thumbnailUrl): ?>
                                        <img id="findings-main-img" src="<?= $thumbnailUrl ?>"
                                            class="w-full h-full object-contain filter drop-shadow-2xl transform transition-transform group-hover/card:scale-105"
                                            alt="Report Thumbnail">
                                    <?php else: ?>
                                        <div class="w-[900px] h-[1270px] origin-top transform scale-[0.25] pointer-events-none absolute top-4">
                                            <iframe src="<?= $miniUrl ?>" class="w-full h-full border-none bg-transparent"
                                                title="Findings Report Preview"></iframe>
                                        </div>
                                    <?php endif; ?>

                                    <div class="absolute inset-0 z-20 flex items-center justify-center opacity-0 group-hover/card:opacity-100 transition-opacity bg-black/20">
                                        <div class="bg-red-600 p-3 rounded-full shadow-2xl transform scale-90 group-hover/card:scale-100 transition-transform">
                                            <i data-lucide="maximize" class="w-5 h-5 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div
                                class="flex flex-col items-center justify-center text-center p-6 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 w-full mb-auto mt-auto">
                                <i data-lucide="clock-3" class="w-10 h-10 mb-3 text-red-300"></i>
                                <p class="font-bold text-gray-800">Waiting for Radiologist</p>
                                <p class="text-sm text-gray-500 mt-1 max-w-[250px]">The findings report will appear here once
                                    the radiologist submits their evaluation.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced X-Ray Viewer -->
                <div class="flex flex-col">
                    <?php
                    $savedPaths = [];
                    if (!empty($caseDetails['image_path'])) {
                        $decoded = json_decode($caseDetails['image_path'], true);
                        $rawPaths = is_array($decoded) ? $decoded : [$caseDetails['image_path']];
                        foreach ($rawPaths as $p) {
                            $savedPaths[] = '/' . PROJECT_DIR . '/' . ltrim($p, '/');
                        }
                    }
                    // encode paths for JS safely
                    $jsonPaths = json_encode($savedPaths);
                    ?>

                        <div id="xray-viewer-container"
                            class="bg-[#0a0a0a] border border-gray-200 rounded-2xl overflow-hidden shadow-2xl flex flex-col h-[480px] relative transition-all w-full">
                            <?php if (!empty($savedPaths)): ?>
                                <!-- Classic Integrated Header Toolbar -->
                                <div class="bg-red-600 px-5 h-14 flex justify-between items-center text-white z-20 w-full select-none shadow-lg"
                                    id="xray-toolbar">
                                    
                                    <div class="flex items-center gap-4">
                                        <div class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/20 shadow-inner">
                                            <i data-lucide="scan-line" class="w-5 h-5"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-black text-xs uppercase tracking-widest leading-none">X-ray Viewer</span>
                                            <?php if (count($savedPaths) > 1): ?>
                                                <span id="xray-counter" class="text-[9px] font-bold text-white/60 tracking-tighter uppercase mt-1">
                                                    Image 1 of <?= count($savedPaths) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>


                                    <!-- Zoom Controls -->
                                    <div class="flex items-center gap-3 bg-black/20 rounded-xl px-3 py-1.5 border border-white/5 shadow-inner">
                                        <button id="btn-zoom-out" class="text-white/60 hover:text-white transition-colors" title="Zoom Out">
                                            <i data-lucide="minus-circle" class="w-4 h-4"></i>
                                        </button>
                                        <span id="zoom-level" class="text-[10px] font-black text-white min-w-[35px] text-center tabular-nums"><?= isset($isZoomed) ? $isZoomed : '100%' ?></span>
                                        <button id="btn-zoom-in" class="text-white/60 hover:text-white transition-colors" title="Zoom In">
                                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>

                            <!-- Main Viewing Area -->
                            <div class="flex-1 flex flex-col items-center justify-center relative bg-[#0a0a0a] overflow-hidden group/viewer">
                                <img id="xray-main-image" src="<?= htmlspecialchars($savedPaths[0] ?? '') ?>"
                                    class="max-w-full max-h-full object-contain transition-transform duration-100 ease-out origin-center"
                                    alt="X-ray" draggable="false">

                                <!-- Floating Side Navigation (Fullscreen Only) -->
                                <?php if (count($savedPaths) > 1): ?>
                                    <button id="btn-prev-side" class="hidden absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[30] group" title="Previous Image">
                                        <i data-lucide="chevron-left" class="w-7 h-7 group-hover:-translate-x-0.5 transition-transform"></i>
                                    </button>
                                    <button id="btn-next-side" class="hidden absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[30] group" title="Next Image">
                                        <i data-lucide="chevron-right" class="w-7 h-7 group-hover:translate-x-0.5 transition-transform"></i>
                                    </button>
                                <?php endif; ?>

                                <!-- Classic Bottom Thumbnails -->
                                <?php if (count($savedPaths) > 1): ?>
                                    <div id="xray-thumb-strip" class="absolute bottom-4 left-1/2 -translate-x-1/2 h-16 bg-black/40 backdrop-blur-md rounded-2xl flex items-center px-4 gap-3 z-20 border border-white/10 shadow-2xl overflow-x-auto max-w-[90%] scrollbar-hide">
                                        <?php foreach ($savedPaths as $index => $path): ?>
                                            <div class="xray-thumb-item flex-shrink-0 w-10 h-10 rounded-xl border-2 <?= $index === 0 ? 'border-red-500 bg-red-500/10' : 'border-transparent opacity-60' ?> overflow-hidden cursor-pointer transition-all hover:scale-110 hover:opacity-100"
                                                data-index="<?= $index ?>" data-url="<?= htmlspecialchars($path) ?>">
                                                <img src="<?= htmlspecialchars($path) ?>" class="w-full h-full object-cover">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Expand Button -->
                                <div id="btn-fullscreen"
                                    class="absolute bottom-4 left-4 bg-black/60 hover:bg-black/80 text-white/90 hover:text-white p-2.5 rounded-xl cursor-pointer backdrop-blur-md transition-all active:scale-90 border border-white/10 shadow-2xl z-30 flex items-center justify-center"
                                    title="Toggle Fullscreen">
                                    <span id="fullscreen-icon-wrapper"></span>
                                </div>
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
                                const btnPrev = document.getElementById('btn-prev-side');
                                const btnNext = document.getElementById('btn-next-side');
                                const counter = document.getElementById('xray-counter');
                                const thumbnails = document.querySelectorAll('.thumbnail-wrapper');
                                const thumbStripItems = document.querySelectorAll('.xray-thumb-item');

                                if (!img) return;

                                const imagePaths = <?= $jsonPaths ?>;
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

                                    img.style.opacity = '0';
                                    setTimeout(() => {
                                        img.src = imagePaths[currentIndex];
                                        img.onload = () => { img.style.opacity = '1'; };
                                    }, 150);

                                    // Initial Icon State
                                    updateIconState();

                                    // Update Counter
                                    if (counter) counter.textContent = (currentIndex + 1) + ' / ' + imagePaths.length;
                                    
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
                                    if (Math.abs(scale - 1) > 0.01) {
                                        // Reset Zoom if currently zoomed (in or out)
                                        scale = 1; translateX = 0; translateY = 0;
                                        updateTransform();
                                    } else {
                                        // Toggle Fullscreen if at 100%
                                        if (!document.fullscreenElement) {
                                            viewer.requestFullscreen().catch(() => { });
                                        } else {
                                            document.exitFullscreen();
                                        }
                                    }
                                });

                                document.addEventListener('fullscreenchange', () => {
                                    const isFull = !!document.fullscreenElement;
                                    if (isFull && document.fullscreenElement === viewer) {
                                        viewer.classList.remove('h-[480px]', 'rounded-xl', 'border');
                                        viewer.classList.add('h-screen', 'rounded-none');
                                    } else if (!isFull) {
                                        viewer.classList.add('h-[480px]', 'rounded-xl', 'border');
                                        viewer.classList.remove('h-screen', 'rounded-none');
                                    }

                                    // Toggle Side Arrows visibility based on Fullscreen state
                                    if (btnPrev) btnPrev.classList.toggle('hidden', !isFull);
                                    if (btnNext) btnNext.classList.toggle('hidden', !isFull);

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
                                    if (!isDragging) return;
                                    translateX = e.clientX - startX;
                                    translateY = e.clientY - startY;
                                    updateTransform();
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
            </div>
        </div>

    <?php elseif ($request['status'] === 'Approved' && !$caseDetails): ?>
        <div class="p-6 bg-yellow-50 text-yellow-800 rounded-xl text-center border border-yellow-200">
            <i data-lucide="alert-triangle" class="w-12 h-12 mx-auto text-yellow-400 mb-3"></i>
            <h4 class="text-lg font-semibold">Case Record Not Found</h4>
            <p class="text-sm mt-1">This request was approved, but the requested Case No.
                (<?= htmlspecialchars($request['patient_no']) ?>) could not be located in the database.</p>
        </div>

    <?php elseif ($request['status'] === 'Pending'): ?>
        <div class="p-10 bg-blue-50 bg-opacity-30 rounded-xl flex flex-col items-center text-center">
            <div class="p-4 rounded-full bg-blue-100/50 mb-4 border border-blue-100">
                <i data-lucide="clock" class="w-10 h-10 text-blue-500"></i>
            </div>
            <h4 class="text-xl font-semibold text-gray-900 leading-tight">Awaiting Approval</h4>
            <p class="text-sm text-gray-500 mt-2 max-w-sm">The requested medical report will be available here once the
                target branch approves your request.</p>
        </div>

    <?php else: ?>
        <div
            class="p-10 bg-red-50 bg-opacity-30 rounded-xl flex flex-col items-center text-center border-dashed border-2 border-red-100">
            <div class="p-4 rounded-full bg-red-100/50 mb-4 border border-red-100">
                <i data-lucide="x-octagon" class="w-10 h-10 text-red-500"></i>
            </div>
            <h4 class="text-xl font-semibold text-gray-900 leading-tight">Request Denied</h4>
            <p class="text-sm text-gray-500 mt-2 max-w-sm">This record request was denied by the target branch. You do not
                have authorization to view the records.</p>
        </div>
    <?php endif; ?>
</div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>