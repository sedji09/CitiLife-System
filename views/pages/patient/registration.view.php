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

    <?php if ($systemStatus === 'closed'): ?>
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-start gap-3 mb-5 shadow-sm">
            <i data-lucide="info" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
            <div class="text-sm text-red-800 leading-relaxed whitespace-pre-wrap">
                <strong class="block mb-1 text-red-900 flex items-center gap-2">Service Advisory</strong>
                <?= htmlspecialchars($closedMessage) ?>
            </div>
        </div>
    <?php endif; ?>

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

            <form id="patientRequestForm" method="POST"
                action="<?= url('registration') ?>"
                class="space-y-4" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="request_xray">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Select Branch <span
                            class="text-red-500">*</span></label>
                    <select name="branch_id" id="branch_select" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                        <option value="" disabled selected>Select branch</option>
                        <?php foreach ($branches as $b): ?>
                            <?php $disabled = $isBranchClosed($b['id']) ? 'disabled' : ''; ?>
                            <option value="<?= $b['id'] ?>" <?= $disabled ?>>
                                <?= htmlspecialchars($b['name']) ?>     <?= $disabled ? '(Temporarily Closed)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Body Part to Examine</label>
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
                    include basePath('views/components/exam-selector.php');
                    ?>
                    <p class="text-xs text-gray-500 mt-2">
                        Note: The Radiologic Technologist will determine the exact examination type and corresponding
                        fee upon reviewing your request. You will be able to make a payment afterward.
                    </p>
                </div>

                <?php if ($isClinicOpen && (!in_array('all', $closedBranchesArr) || $systemStatus !== 'closed')): ?>
                    <button type="submit" id="submit_btn"
                        class="flex items-center justify-center gap-2 w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-5 transition shadow-sm mt-4">
                        <i data-lucide="send" class="w-4 h-4"></i> Submit Request
                    </button>
                <?php else: ?>
                    <button type="button" disabled
                        class="flex items-center justify-center gap-2 w-full rounded-xl bg-gray-400 text-white font-bold text-sm py-3 px-5 transition shadow-sm cursor-not-allowed mt-4">
                        <i data-lucide="clock" class="w-4 h-4"></i> Clinic Closed
                    </button>
                <?php endif; ?>
            </form>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('patientRequestForm');
    if (!form) return;

    let isConfirmed = false;

    form.addEventListener('submit', async function (e) {
        if (isConfirmed) return; // Allow form to submit once confirmed

        e.preventDefault();

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

        const selectedBranchText = branchSelect.options[branchSelect.selectedIndex]?.text?.replace(/\s+/g, ' ').trim() || '';
        const examInput = form.querySelector('input[name="exam_type"]');
        const selectedExams = (examInput && examInput.value.trim()) ? examInput.value.trim() : 'To be determined by Radiologic Technologist';

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