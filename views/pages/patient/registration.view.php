<?php
/**
 * Patient Registration View (Portal)
 * Backend logic handled by RegistrationController.php
 */
?>

<div class="space-y-5 pb-8 max-w-3xl mx-auto">

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Patient Registration</h1>
        <p class="text-sm text-gray-500 mt-1">
            Request a new X-ray examination at your preferred branch.
        </p>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-start gap-3 mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
            <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: <?= json_encode($error) ?>,
                        confirmButtonColor: '#dc2626',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                        }
                    });
                }
            });
        </script>
    <?php endif; ?>

    <?php if (!$isClinicOpen): ?>
        <div class="rounded-xl bg-yellow-50 border border-yellow-200 p-4 flex items-start gap-3 mb-5">
            <i data-lucide="clock" class="w-5 h-5 text-yellow-600 shrink-0 mt-0.5"></i>
            <p class="text-sm text-yellow-800">
                <strong>Notice:</strong> The clinic is currently closed. Online requests are only accepted between
                <strong><?= htmlspecialchars($openTimeDisplay) ?></strong> and
                <strong><?= htmlspecialchars($closeTimeDisplay) ?></strong>.
            </p>
        </div>
    <?php endif; ?>

    <?php
    $showAdvisoryInitially = ($systemStatus === 'closed' && in_array('all', $closedBranchesArr));
    ?>
    <div id="serviceAdvisoryBox" class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-start gap-3 mb-5 shadow-sm transition-all duration-200 <?= $showAdvisoryInitially ? '' : 'hidden' ?>">
        <i data-lucide="info" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
        <div class="text-sm text-red-800 leading-relaxed whitespace-pre-wrap">
            <strong class="block mb-1 text-red-900 flex items-center gap-2">Service Advisory</strong>
            <span id="serviceAdvisoryMessage"><?= htmlspecialchars($closedMessage) ?></span>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-5">
        <!-- Request form -->
        <div class="flex-1 rounded-2xl bg-white border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i data-lucide="send" class="w-5 h-5 text-red-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Request New X-ray</h3>
                    <p class="text-xs text-gray-500">Select your preferred branch to submit a new X-ray request.</p>
                </div>
            </div>

            <form id="patientRequestForm" method="POST" action="<?= url('registration') ?>" class="space-y-4"
                enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="request_xray">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Select Branch <span
                            class="text-red-500">*</span></label>
                    <select name="branch_id" id="branch_select" required <?= !$isClinicOpen ? 'disabled' : '' ?>
                        class="w-full rounded-xl border border-gray-200 <?= !$isClinicOpen ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-50 text-gray-900' ?> px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                        <option value="" disabled selected><?= !$isClinicOpen ? 'Clinic is currently closed' : 'Select branch' ?></option>
                        <?php foreach ($branches as $b): ?>
                            <?php $isClosed = $isBranchClosed($b['id']); ?>
                            <option value="<?= $b['id'] ?>" data-closed="<?= $isClosed ? '1' : '0' ?>">
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Body Part to Examine <span
                            class="text-gray-500 font-normal">(Optional)</span></label>
                    <div id="examSelectorContainer" class="transition-all duration-200">
                    <?php
                    // Retrieve dynamic categories from DB and standard anatomical parts
                    $dbCategories = !empty($groupedServices) ? array_keys($groupedServices) : [];
                    $standardBodyParts = [
                        'Chest',
                        'Skull',
                        'Head',
                        'Abdomen',
                        'Upper Extremities',
                        'Lower Extremities',
                        'Spine',
                        'Pelvis',
                        'Neck',
                        'Shoulder',
                        'Arm',
                        'Elbow',
                        'Forearm',
                        'Hand / Wrist',
                        'Upper Back',
                        'Lower Back',
                        'Pelvis / Hip',
                        'Thigh',
                        'Knee',
                        'Lower Leg',
                        'Ankle',
                        'Foot'
                    ];
                    $availableOptions = array_values(array_unique(array_merge($dbCategories, $standardBodyParts)));
                    sort($availableOptions);
                    $examInputName = 'exam_type';
                    $placeholderText = 'Select body parts (e.g. Chest, Skull)...';
                    $isRequired = false;
                    $isReadOnly = !$isClinicOpen;
                    $readOnlyPlaceholder = 'Clinic is currently closed';
                    include basePath('views/components/exam-selector.php');
                    ?>
                    </div>
                    <p id="examNoteText" class="text-xs text-gray-500 mt-2 <?= (!$isClinicOpen || ($systemStatus === 'closed' && in_array('all', $closedBranchesArr))) ? 'hidden' : '' ?>">
                        Note: The Radiologic Technologist will determine the exact examination type and corresponding
                        fee upon reviewing your request. You will be able to make a payment afterward.
                    </p>
                </div>

                <div id="submitButtonContainer">
                    <?php if ($isClinicOpen && (!in_array('all', $closedBranchesArr) || $systemStatus !== 'closed')): ?>
                        <button type="submit" id="submit_btn"
                            class="flex items-center justify-center gap-2 w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-5 transition shadow-sm mt-4">
                            <i data-lucide="send" class="w-4 h-4"></i> Submit Request
                        </button>
                    <?php else: ?>
                        <button type="button" id="submit_btn" disabled
                            class="flex items-center justify-center gap-2 w-full rounded-xl bg-gray-400 text-white font-bold text-sm py-3 px-5 transition shadow-sm cursor-not-allowed mt-4">
                            <i data-lucide="clock" class="w-4 h-4"></i> Clinic Closed
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('patientRequestForm');
        if (!form) return;

        const branchSelect = document.getElementById('branch_select');
        const advisoryBox = document.getElementById('serviceAdvisoryBox');
        const submitContainer = document.getElementById('submitButtonContainer');
        const examContainer = document.getElementById('examSelectorContainer');
        const examNote = document.getElementById('examNoteText');

        const isClinicOpen = <?= json_encode((bool) $isClinicOpen) ?>;
        const systemStatus = <?= json_encode($systemStatus) ?>;
        const isAllClosed = <?= json_encode($systemStatus === 'closed' && in_array('all', $closedBranchesArr)) ?>;
        const closedMessage = <?= json_encode($closedMessage) ?>;

        function updateBranchStatus() {
            if (!branchSelect) return;

            if (!isClinicOpen) {
                branchSelect.disabled = true;
            }

            const selectedOption = branchSelect.options[branchSelect.selectedIndex];
            const isOptionClosed = selectedOption && selectedOption.dataset.closed === '1';
            const isBranchSpecificClosed = isOptionClosed && selectedOption.value !== '';
            const shouldDisableForm = !isClinicOpen || isAllClosed || (systemStatus === 'closed' && isBranchSpecificClosed);

            // Toggle Announcement / Service Advisory visibility
            if (isAllClosed || (systemStatus === 'closed' && isBranchSpecificClosed)) {
                if (advisoryBox) advisoryBox.classList.remove('hidden');
            } else {
                if (advisoryBox) advisoryBox.classList.add('hidden');
            }

            // Toggle Note visibility below exam selector (show only when open)
            if (examNote) {
                if (shouldDisableForm) {
                    examNote.classList.add('hidden');
                } else {
                    examNote.classList.remove('hidden');
                }
            }

            // Lock / Unlock Exam Selector Component
            const examComponent = document.querySelector('.exam-ms-component');
            if (examComponent) {
                const msBox = examComponent.querySelector('.exam-ms-box');
                const searchInput = examComponent.querySelector('.exam-ms-input');
                const dropdown = examComponent.querySelector('.exam-ms-dropdown');
                const hiddenInput = examComponent.querySelector('.exam-ms-hidden-input');

                if (shouldDisableForm) {
                    examComponent.setAttribute('data-readonly', 'true');
                    if (dropdown) dropdown.classList.add('hidden');
                    if (msBox) {
                        msBox.classList.add('bg-gray-100', 'cursor-not-allowed', 'pointer-events-none');
                        msBox.classList.remove('bg-white', 'cursor-text');
                    }
                    if (searchInput) {
                        searchInput.disabled = true;
                        if (!searchInput.getAttribute('data-orig-placeholder')) {
                            searchInput.setAttribute('data-orig-placeholder', searchInput.getAttribute('data-placeholder') || searchInput.placeholder);
                        }
                        searchInput.placeholder = 'Clinic is currently closed';
                    }
                    if (hiddenInput) {
                        hiddenInput.disabled = true;
                    }
                    if (examContainer) {
                        examContainer.classList.add('opacity-60', 'pointer-events-none', 'cursor-not-allowed');
                    }
                } else {
                    examComponent.removeAttribute('data-readonly');
                    if (msBox) {
                        msBox.classList.remove('bg-gray-100', 'cursor-not-allowed', 'pointer-events-none');
                        msBox.classList.add('bg-white', 'cursor-text');
                    }
                    if (searchInput) {
                        searchInput.disabled = false;
                        const orig = searchInput.getAttribute('data-orig-placeholder') || searchInput.getAttribute('data-placeholder') || 'Select body parts (e.g. Chest, Skull)...';
                        searchInput.placeholder = orig;
                    }
                    if (hiddenInput) {
                        hiddenInput.disabled = false;
                    }
                    if (examContainer) {
                        examContainer.classList.remove('opacity-60', 'pointer-events-none', 'cursor-not-allowed');
                    }
                }
            }

            // Update Submit / Clinic Closed Button
            if (submitContainer) {
                if (shouldDisableForm) {
                    submitContainer.innerHTML = `
                        <button type="button" id="submit_btn" disabled
                            class="flex items-center justify-center gap-2 w-full rounded-xl bg-gray-400 text-white font-bold text-sm py-3 px-5 transition shadow-sm cursor-not-allowed mt-4">
                            <i data-lucide="clock" class="w-4 h-4"></i> Clinic Closed
                        </button>
                    `;
                } else {
                    submitContainer.innerHTML = `
                        <button type="submit" id="submit_btn"
                            class="flex items-center justify-center gap-2 w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-5 transition shadow-sm mt-4">
                            <i data-lucide="send" class="w-4 h-4"></i> Submit Request
                        </button>
                    `;
                }
                if (window.lucide) {
                    lucide.createIcons();
                }
            }
        }

        if (branchSelect) {
            branchSelect.addEventListener('change', updateBranchStatus);
            updateBranchStatus();
        }

        let isConfirmed = false;

        form.addEventListener('submit', async function (e) {
            if (isConfirmed) return; // Allow form to submit once confirmed

            e.preventDefault();

            if (!isClinicOpen) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Clinic Closed',
                        text: 'Online requests are only accepted during clinic operating hours.',
                        confirmButtonColor: '#dc2626',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                        }
                    });
                } else {
                    alert('Online requests are only accepted during clinic operating hours.');
                }
                return;
            }

            const branchSelect = form.querySelector('select[name="branch_id"]');
            if (!branchSelect || !branchSelect.value) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Branch Required',
                        text: 'Please select your preferred clinic branch first.',
                        confirmButtonColor: '#dc2626',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                        }
                    });
                } else {
                    alert('Please select your preferred clinic branch first.');
                }
                branchSelect?.focus();
                return;
            }

            const selectedOption = branchSelect.options[branchSelect.selectedIndex];
            if (selectedOption && (selectedOption.dataset.closed === '1' || isAllClosed)) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Clinic Closed',
                        text: closedMessage || 'This clinic branch is temporarily closed for online requests.',
                        confirmButtonColor: '#dc2626',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                        }
                    });
                } else {
                    alert(closedMessage || 'This clinic branch is temporarily closed for online requests.');
                }
                return;
            }

            const selectedBranchText = branchSelect.options[branchSelect.selectedIndex]?.text?.replace(/\s+/g, ' ').trim() || '';
            const examInput = form.querySelector('input[name="exam_type"]');
            const selectedExams = (examInput && examInput.value.trim()) ? examInput.value.trim() : 'To be determined by Radiologic Technologist';

            // Check for active ongoing duplicate exam
            const activeExams = <?= json_encode($activePatientExams ?? []) ?>;
            if (activeExams && activeExams.length > 0) {
                const rawChosen = (examInput && examInput.value.trim()) ? examInput.value.trim().toLowerCase() : 'to be determined';
                const chosenParts = rawChosen.split(',').map(s => s.trim()).filter(Boolean);

                let duplicateFound = null;
                for (const act of activeExams) {
                    const actExamRaw = (act.exam_type || '').toLowerCase();
                    const actParts = actExamRaw.split(',').map(s => s.trim()).filter(Boolean);

                    for (const cp of chosenParts) {
                        if (cp === 'to be determined' && (actExamRaw === 'to be determined' || actParts.includes('to be determined'))) {
                            duplicateFound = act;
                            break;
                        }
                        for (const ap of actParts) {
                            if (ap !== 'to be determined' && (cp === ap || ap.includes(cp) || cp.includes(ap))) {
                                duplicateFound = act;
                                break;
                            }
                        }
                        if (duplicateFound) break;
                    }
                    if (duplicateFound) break;
                }

                if (duplicateFound) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Active Request in Progress',
                            html: `You currently have an ongoing request for <b>${duplicateFound.exam_type}</b> (<b>${duplicateFound.ref}</b>).<br><br>To prevent duplicate requests and unintentional double payments, submitting the same examination is restricted while one is still in progress.<br><br><span class="text-xs text-gray-500">You may still request other body parts if you have a separate prescription (e.g., Spine, Skull, Extremities).</span>`,
                            confirmButtonColor: '#dc2626',
                            confirmButtonText: 'I Understand',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                            }
                        });
                    } else {
                        alert(`You currently have an active request for ${duplicateFound.exam_type} (${duplicateFound.ref}). Duplicate requests for the same examination are not permitted while in progress.`);
                    }
                    return;
                }
            }

            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Are you sure?',
                    text: 'Are you sure you want to submit this X-ray examination request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, Submit Request',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm',
                        cancelButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                    }
                });

                if (!result.isConfirmed) {
                    return;
                }
            } else {
                if (!confirm('Are you sure you want to submit this X-ray request?')) {
                    return;
                }
            }

            isConfirmed = true;

            const submitBtn = document.getElementById('submit_btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Submitting...';
                if (window.lucide) lucide.createIcons();
            }

            form.submit();
        });
    });
</script>