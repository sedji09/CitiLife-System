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

<!-- Sticky Layout Wrapper -->
<div class="flex items-start gap-4">
    <!-- Sticky Back Button -->
    <div class="lg:sticky lg:top-6 z-40 shrink-0">
        <a href="javascript:void(0)" data-back-btn data-fallback="<?= url('worklist') ?>" title="Back"
            class="flex w-10 h-10 items-center justify-center rounded-xl bg-white border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
        </a>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 min-w-0">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Patient Details</h2>
            <p class="text-sm text-gray-500 mt-1">View patient examination and clinical information</p>
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
                $pBorder = '1.5px solid #60a5fa';
                $pBg = '#eff6ff';
                $pColor = '#1d4ed8';
                if ($caseDetails['priority'] === 'STAT') {
                    $pBorder = '1.5px solid #f87171';
                    $pBg = '#fef2f2';
                    $pColor = '#b91c1c';
                } elseif ($caseDetails['priority'] === 'Urgent') {
                    $pBorder = '1.5px solid #facc15';
                    $pBg = '#fefce8';
                    $pColor = '#a16207';
                } elseif ($caseDetails['priority'] === 'Priority') {
                    $pBorder = '1.5px solid #fb923c';
                    $pBg = '#fff7ed';
                    $pColor = '#c2410c';
                }
                ?>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                    style="border:<?= $pBorder ?>;background-color:<?= $pBg ?>;color:<?= $pColor ?>">
                    <?= htmlspecialchars($caseDetails['priority'] ?: 'Routine') ?>
                </span>
            </div>
            <div class="pt-1">
                <span class="block text-gray-600 text-sm font-medium mb-1.5">Status</span>
                <?php
                $displayStatus = $caseDetails['status'] ?: 'Pending';
                $sBorder = '1.5px solid #facc15';
                $sBg = '#fefce8';
                $sColor = '#a16207';
                if ($displayStatus === 'Report Ready') {
                    $sBorder = '1.5px solid #818cf8';
                    $sBg = '#eef2ff';
                    $sColor = '#4338ca';
                } elseif ($displayStatus === 'Under Reading') {
                    $sBorder = '1.5px solid #60a5fa';
                    $sBg = '#eff6ff';
                    $sColor = '#1d4ed8';
                } elseif ($displayStatus === 'Completed') {
                    $sBorder = '1.5px solid #4ade80';
                    $sBg = '#f0fdf4';
                    $sColor = '#15803d';
                } elseif ($displayStatus === 'Overdue' || $displayStatus === 'Rejected') {
                    $sBorder = '1.5px solid #f87171';
                    $sBg = '#fef2f2';
                    $sColor = '#b91c1c';
                } elseif ($displayStatus === 'Released') {
                    $sBorder = '1.5px solid #34d399';
                    $sBg = '#ecfdf5';
                    $sColor = '#047857';
                }
                ?>
                <span class="inline-block font-bold text-xs px-3 py-1.5 rounded-full"
                    style="border:<?= $sBorder ?>;background-color:<?= $sBg ?>;color:<?= $sColor ?>">
                    <?= htmlspecialchars($displayStatus) ?>
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
                    <?php 
                    $imgLabel = getXrayImageLabel($sPath, $idx, $caseDetails['exam_type'] ?? ''); 
                    $cleanImgUrl = url(ltrim($sPath, '/'));
                    ?>
                    <div onclick="openXrayLightbox('<?= $cleanImgUrl ?>', '<?= htmlspecialchars($imgLabel, ENT_QUOTES) ?>')"
                        style="width: 128px; height: 128px; min-width: 128px; min-height: 128px;"
                        class="group relative rounded-2xl overflow-hidden border-2 border-gray-300 hover:border-red-600 bg-black cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md shrink-0 flex items-center justify-center select-none"
                        title="<?= htmlspecialchars($imgLabel) ?> — Click to view fullscreen">
                        <img src="<?= $cleanImgUrl ?>" 
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
    </div> <!-- End Grid -->
    </div> <!-- End Main Content Area -->
</div> <!-- End Sticky Layout Wrapper -->

<!-- Image Lightbox Modal with Blurred Gray Background & Right-Side Close Button -->
<div id="xray-lightbox-modal" v-pre
    style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(15, 23, 42, 0.65) !important; backdrop-filter: blur(8px) !important; -webkit-backdrop-filter: blur(8px) !important; z-index: 9999999 !important; display: none; align-items: center !important; justify-content: center !important; padding: 24px !important; box-sizing: border-box !important; opacity: 0; transition: opacity 0.25s ease-out; cursor: pointer; user-select: none;"
    onclick="closeXrayLightbox(event)">
    <div id="xray-lightbox-wrapper"
        style="position: relative !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; max-width: min(440px, 85vw) !important; max-height: 72vh !important; cursor: default !important; border-radius: 16px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65) !important; background: #000000 !important; transform: scale(0.95); transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);"
        onclick="event.stopPropagation()">
        <!-- Circular Close Button Outside on the Top-Right Side -->
        <button type="button" id="xray-lightbox-close-btn"
            style="position: absolute !important; top: -16px !important; right: -16px !important; width: 38px !important; height: 38px !important; border-radius: 50% !important; background-color: #ffffff !important; color: #111827 !important; display: flex !important; align-items: center !important; justify-content: center !important; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45) !important; border: 1px solid rgba(229, 231, 235, 0.9) !important; cursor: pointer !important; z-index: 60 !important; padding: 0 !important; outline: none !important;"
            onclick="closeXrayLightbox(event)">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111827" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:block;">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- Centered X-Ray Image (Compact Natural Scale) -->
        <img id="xray-lightbox-main-img" src="" alt="X-Ray Image"
            style="display: block !important; max-width: min(440px, 85vw) !important; max-height: 72vh !important; width: auto !important; height: auto !important; object-fit: contain !important; border-radius: 16px !important; background-color: #000000 !important; border: 1px solid rgba(255, 255, 255, 0.12) !important; pointer-events: auto !important;"
            draggable="false">
    </div>
</div>

<script>
    (function () {
        function getElements() {
            const modal = document.getElementById('xray-lightbox-modal');
            const wrapper = document.getElementById('xray-lightbox-wrapper');
            const img = document.getElementById('xray-lightbox-main-img');
            return { modal, wrapper, img };
        }

        window.openXrayLightbox = function (src) {
            let { modal, wrapper, img } = getElements();
            if (!modal || !img) return;

            // Ensure modal is directly under document.body so no parent container clips or hides it
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
                const els = getElements();
                modal = els.modal;
                wrapper = els.wrapper;
                img = els.img;
            }

            img.src = src;
            document.body.style.overflow = 'hidden';
            modal.style.display = 'flex';

            // Force reflow for smooth animation
            void modal.offsetWidth;
            modal.style.opacity = '1';
            if (wrapper) wrapper.style.transform = 'scale(1)';
        };

        window.closeXrayLightbox = function (e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const { modal, wrapper, img } = getElements();
            if (!modal || !img) return;

            modal.style.opacity = '0';
            if (wrapper) wrapper.style.transform = 'scale(0.95)';
            document.body.style.overflow = '';

            setTimeout(() => {
                if (modal.style.opacity === '0') {
                    modal.style.display = 'none';
                    img.src = '';
                }
            }, 260);
        };

        // Close on Escape Key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('xray-lightbox-modal');
                if (modal && modal.style.display === 'flex') {
                    closeXrayLightbox(e);
                }
        // Clean URL address bar so ?id=... is never exposed to users
        try {
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, document.title, window.location.pathname);
            }
        } catch (e) { }
    })();
</script>

