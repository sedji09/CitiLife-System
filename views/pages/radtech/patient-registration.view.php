<?php
/**
 * Patient Registration View
 * Backend logic handled by PatientRegistrationController.php
 */
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker.min.css">
<script src="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/js/datepicker-full.min.js"></script>
<script src="/<?= PROJECT_DIR ?>/public/assets/js/birthdate-picker.js?v=<?= time() ?>"></script>

<style>
@keyframes inputShake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-4px); }
    40%, 80% { transform: translateX(4px); }
}
.animate-shake {
    animation: inputShake 0.35s ease-in-out;
}
.field-error {
    border-color: #ef4444 !important;
    background-color: #fef2f2 !important;
    box-shadow: 0 0 0 1px #ef4444 !important;
}
.field-warning {
    border-color: #f59e0b !important;
    background-color: #fffbeb !important;
    box-shadow: 0 0 0 1px #f59e0b !important;
}
.field-success {
    border-color: #10b981 !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 0 0 1px #10b981 !important;
}
</style>

<main class="flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Patient Registration</h1>
            <p class="text-sm text-gray-500">Walk-in patient entry — system auto-generates case number</p>
        </div>

        <?php
        $regSuccess = $_SESSION['registration_success'] ?? null;
        if ($regSuccess):
            unset($_SESSION['registration_success']); // Clear for next load
        endif; 
        ?>

        <?php if ($error): ?>
            <div class="rounded-lg bg-red-50 border border-red-300 p-4 mb-6">
                <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-2" aria-label="Tabs">
                <button type="button" onclick="switchTab('new-patient')" id="tab-new"
                    class="border-red-500 text-red-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none">
                    New Patient
                </button>
                <button type="button" onclick="switchTab('existing-patient')" id="tab-existing"
                    class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none">
                    Returning Patient
                </button>
            </nav>
        </div>

        <form method="POST" action="" novalidate class="space-y-6 rounded-xl border border-gray-300 shadow-sm bg-white px-6 py-5 mt-4">

            <!-- Hidden field for existing patient selected -->
            <div class="hidden" hidden>
                <input type="hidden" name="form-mode" id="form-mode"
                    value="<?= htmlspecialchars($_POST['form-mode'] ?? 'new-patient') ?>">
                <input type="hidden" name="existing-patient-id" id="existing-patient-id"
                    value="<?= htmlspecialchars($_POST['existing-patient-id'] ?? '') ?>">
            </div>

            <div id="new-patient-section" class="!mt-0">
                <fieldset class="space-y-4">
                    <legend class="text-lg font-semibold text-gray-900 mb-2">Patient Information</legend>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="first-name" class="block text-sm font-medium text-gray-700 mb-2">First Name
                                <span class="text-red-500">*</span></label>
                            <input type="text" id="first-name" name="first-name"
                                value="<?= htmlspecialchars($_POST['first-name'] ?? '') ?>"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 req-new transition-all">
                            <p id="first-name-feedback" class="hidden text-xs mt-1.5 transition-all duration-200"></p>
                        </div>
                        <div>
                            <label for="middle-name" class="block text-sm font-medium text-gray-700 mb-2">
                                Middle Name 
                                <span id="middle-name-req" class="text-red-500 font-bold hidden">*</span>
                                <span id="middle-name-opt" class="text-xs text-gray-400 font-normal ml-1">(Optional)</span>
                            </label>
                            <input type="text" id="middle-name" name="middle-name"
                                value="<?= htmlspecialchars($_POST['middle-name'] ?? '') ?>"
                                placeholder="Enter middle name or initial"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 transition-all">
                            <p id="middle-name-feedback" class="hidden text-xs mt-1.5 transition-all duration-200"></p>
                        </div>
                        <div>
                            <label for="last-name" class="block text-sm font-medium text-gray-700 mb-2">Last Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="last-name" name="last-name"
                                value="<?= htmlspecialchars($_POST['last-name'] ?? '') ?>"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 req-new transition-all">
                            <p id="last-name-feedback" class="hidden text-xs mt-1.5 transition-all duration-200"></p>
                        </div>
                    </div>

                    <!-- Namesake / Duplicate Name Warning Banner -->
                    <div id="namesake-warning" class="hidden rounded-xl border border-amber-300 bg-amber-50/70 p-4 shadow-2xs transition-all duration-300">
                        <div class="flex items-start gap-3.5">
                            <div class="p-2 rounded-lg bg-amber-500 text-white shrink-0 mt-0.5 shadow-2xs">
                                <i data-lucide="users" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-gray-900">
                                    Existing Patient Record Found
                                </h4>
                                <p class="mt-1 text-xs text-gray-600 leading-relaxed">
                                    A matching record was found. Verify the details below. If this is the same patient, select <strong>"Use This Profile"</strong>. If this is a new patient, enter their middle name above.
                                </p>

                                <!-- Existing Matching Patient Cards -->
                                <div id="namesake-list" class="mt-3 space-y-2.5 max-h-60 overflow-y-auto pr-1">
                                    <!-- Populated via JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-2">Birthdate <span
                                    class="text-red-500">*</span></label>
                            <?php $birthdateValue = $_POST['birthdate'] ?? ''; ?>
                            <div id="birthdate-picker-container" data-birthdate-picker data-id="birthdate" data-name="birthdate" data-value="<?= htmlspecialchars($birthdateValue) ?>" data-feedback-id="birthdate-feedback" data-required></div>
                            <p id="birthdate-feedback" class="hidden text-xs mt-1.5 transition-all duration-200"></p>
                        </div>
                        <div>
                            <label for="sex" class="block text-sm font-medium text-gray-700 mb-2">Sex <span
                                    class="text-red-500">*</span></label>
                            <select id="sex" name="sex"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">
                        <div>
                            <label for="contact" class="block text-sm font-medium text-gray-700 mb-2">Contact Number
                                <span class="text-red-500">*</span></label>
                            <input type="tel" id="contact" name="contact" maxlength="11"
                                placeholder="09123456789"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 req-new transition-all">
                            <p id="contact-feedback" class="hidden text-xs mt-1.5 transition-all duration-200"></p>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email
                                Address <span class="text-xs text-gray-400 font-normal">(Optional)</span></label>
                            <input type="email" id="email" name="email" placeholder="patient@example.com"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 transition-all">
                            <p id="email-feedback" class="hidden text-xs mt-1.5 transition-all duration-200"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="home_address" class="block text-sm font-medium text-gray-700 mb-2">Home
                                Address</label>
                            <input type="text" id="home_address" name="home_address"
                                placeholder="123 Main St, Brgy, City"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>
                </fieldset>
            </div>

            <div id="existing-patient-section" class="hidden !mt-0">
                <fieldset class="space-y-4">
                    <legend class="text-lg font-semibold text-gray-900 mb-2">Search Existing Patient</legend>
                    <div class="relative">
                        <input type="text" id="search-patient" placeholder="Search by name or ID... (Type and wait)"
                            onkeydown="return event.key != 'Enter';"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 pl-10 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500"
                            autocomplete="off">
                        <i data-lucide="search" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
                        <!-- Dropdown list -->
                        <ul id="search-results"
                            class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm hidden">
                            <!-- JS populated -->
                        </ul>
                    </div>

                    <div id="selected-patient-info"
                        class="hidden mt-4 p-4 border border-blue-200 bg-blue-50 rounded-lg flex items-start gap-4">
                        <i data-lucide="user-check" class="w-6 h-6 text-blue-600 mt-1"></i>
                        <div>
                            <h4 class="font-medium text-blue-900" id="sp-name">John Doe</h4>
                            <p class="text-sm text-blue-700 mt-1">Patient No: <span id="sp-patient-no"
                                    class="font-bold"></span></p>
                            <p class="text-sm text-blue-700 mt-0.5">Age: <span id="sp-age"></span></p>
                            <p class="text-sm text-blue-700 mt-0.5">Sex: <span id="sp-sex"></span></p>
                            <p class="text-sm text-blue-700">Contact: <span id="sp-contact"></span></p>
                            <button type="button" onclick="clearSelectedPatient()"
                                class="mt-2 text-xs font-semibold text-red-600 hover:text-red-800 focus:outline-none">
                                Remove / Search Again
                            </button>
                        </div>
                    </div>
                </fieldset>
            </div>

            <hr class="border-gray-200">

            <fieldset class="space-y-4">
                <legend class="text-lg font-medium text-gray-900 py-2">Examination Details</legend>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Exam Type</label>
                        <?php
                        $examInputName = 'exam-type';
                        $isReadOnly = false;
                        require __DIR__ . '/../../components/exam-selector.php';
                        ?>
                    </div>
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority
                            Level</label>
                        <select id="priority" name="priority"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
                            <option value="Routine">Routine</option>
                            <option value="Urgent">Urgent</option>
                            <option value="STAT">STAT</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="card" class="block text-sm font-medium text-gray-700 mb-2">PhilHealth Card</label>
                    <select id="card" name="card" onchange="togglePhilHealthId()"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 mb-3">
                        <option value="With PhilHealth Card">With PhilHealth Card</option>
                        <option value="Without PhilHealth Card">Without PhilHealth Card</option>
                    </select>

                    <div id="philhealth-id-container" class="hidden">
                        <label for="id-number" class="block text-sm font-medium text-gray-700 mb-2">PhilHealth ID Number <span class="text-red-500">*</span></label>
                        <input id="id-number" name="id-number" type="text" inputmode="numeric" maxlength="14"
                            oninput="formatPhilHealthInput(this); checkPhilHealthId();" placeholder="XX-XXXXXXXXX-X"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
                        
                        <div id="philhealth-relation-container" class="mt-3">
                            <label for="philhealth_relation" class="block text-sm font-medium text-gray-700 mb-2">Patient's Relation to ID <span class="text-red-500">*</span></label>
                            <select id="philhealth_relation" name="philhealth_relation"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
                                <option value="" disabled selected>Select relation</option>
                                <option value="Principal Member" id="opt-owner">Principal Member</option>
                                <option value="Qualified Dependent" id="opt-family">Qualified Dependent</option>
                            </select>
                            <p id="philhealth-status-msg" class="text-xs text-red-600 mt-2 hidden"></p>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 mt-6">
                <a href="?role=radtech&page=patient-registration"
                    class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 border border-gray-300 transition-colors">
                    Clear Form
                </a>
                <button type="submit" id="btn-submit"
                    class="inline-flex items-center rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    Register Patient
                </button>
            </div>
        </form>
    </div>
</main>

<script>


    function togglePhilHealthId() {
        const select = document.getElementById('card');
        const container = document.getElementById('philhealth-id-container');
        const idInput = document.getElementById('id-number');
        const relSelect = document.getElementById('philhealth_relation');
        if (select.value === 'With PhilHealth Card') {
            container.classList.remove('hidden');
            relSelect.required = true;
        } else {
            container.classList.add('hidden');
            idInput.value = '';
            idInput.setCustomValidity('');
            relSelect.value = '';
            relSelect.required = false;
        }
    }

    // Debounce timer for API call
    let phCheckTimer = null;

    function checkPhilHealthId() {
        clearTimeout(phCheckTimer);
        const idInput = document.getElementById('id-number');
        const msg = document.getElementById('philhealth-status-msg');
        const optOwner = document.getElementById('opt-owner');
        const optFamily = document.getElementById('opt-family');
        const relSelect = document.getElementById('philhealth_relation');
        const idValue = idInput.value;

        // Reset state
        msg.classList.add('hidden');
        msg.innerText = '';
        optOwner.disabled = false;
        optOwner.innerText = 'Principal Member';
        optFamily.disabled = false;
        optFamily.innerText = 'Qualified Dependent';
        idInput.setCustomValidity('');

        // Only check if format is correct
        const philHealthPattern = /^\d{2}-\d{9}-\d{1}$/;
        if (!philHealthPattern.test(idValue)) {
            return;
        }

        phCheckTimer = setTimeout(() => {
            fetch(window.__APP__.basePath + `/app/api/check_philhealth.php?philhealth_id=${encodeURIComponent(idValue)}&t=${new Date().getTime()}`, { cache: 'no-store' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (idInput.value !== idValue) return;

                        if (data.owner_used) {
                            optOwner.disabled = true;
                            optOwner.innerText = `Principal Member - Used on ${data.owner_used_date}`;
                            if (relSelect.value === 'Principal Member') relSelect.value = '';
                        }
                        if (data.family_used) {
                            optFamily.disabled = true;
                            optFamily.innerText = `Qualified Dependent - Used on ${data.family_used_date}`;
                            if (relSelect.value === 'Qualified Dependent') relSelect.value = '';
                        }
                        
                        if (data.owner_used && data.family_used) {
                            idInput.setCustomValidity("This PhilHealth ID is already fully utilized.");
                            msg.innerText = "This PhilHealth ID is already fully utilized.";
                            msg.classList.remove('hidden');
                        }
                    }
                })
                .catch(err => console.error("Error checking PhilHealth ID:", err));
        }, 500);
    }

    function formatPhilHealthInput(input) {
        let digits = input.value.replace(/\D/g, '');
        digits = digits.slice(0, 12);
        let formatted = '';
        if (digits.length <= 2) {
            formatted = digits;
        } else if (digits.length <= 11) {
            formatted = digits.slice(0, 2) + '-' + digits.slice(2);
        } else {
            formatted = digits.slice(0, 2) + '-' + digits.slice(2, 11) + '-' + digits.slice(11);
        }
        input.value = formatted;
        input.setCustomValidity('');
    }

    function clearExaminationDetails() {
        // Clear Exam Type
        document.querySelectorAll('.exam-ms-component').forEach(container => {
            const hidden = container.querySelector('.exam-ms-hidden-input');
            const reqCheck = container.querySelector('.exam-ms-required-check');
            if (hidden) hidden.value = '';
            if (reqCheck) reqCheck.value = '';
            if (typeof renderChips === 'function') {
                renderChips(container);
            }
        });

        // Clear Priority
        const priority = document.getElementById('priority');
        if (priority) priority.value = 'Routine';

        // Clear PhilHealth
        const card = document.getElementById('card');
        const idInput = document.getElementById('id-number');
        if (card) {
            card.value = 'With PhilHealth Card';
            togglePhilHealthId();
        }
        if (idInput) {
            idInput.value = '';
            idInput.setCustomValidity('');
        }
    }

    function switchTab(tab) {
        const newSec = document.getElementById('new-patient-section');
        const existSec = document.getElementById('existing-patient-section');
        const btnNew = document.getElementById('tab-new');
        const btnExist = document.getElementById('tab-existing');
        const reqFields = document.querySelectorAll('.req-new');
        const formMode = document.getElementById('form-mode');

        const isChanging = formMode.value !== tab;

        if (tab === 'new-patient') {
            formMode.value = 'new-patient';
            newSec.classList.remove('hidden');
            existSec.classList.add('hidden');

            btnNew.classList.replace('border-transparent', 'border-red-500');
            btnNew.classList.replace('text-gray-500', 'text-red-600');
            btnExist.classList.replace('border-red-500', 'border-transparent');
            btnExist.classList.replace('text-red-600', 'text-gray-500');

            document.getElementById('existing-patient-id').value = '';
            document.getElementById('btn-submit').innerText = "Register Patient";
            reqFields.forEach(f => f.setAttribute('required', 'required'));
            if (typeof checkNamesake === 'function') {
                checkNamesake();
            }
        } else {
            formMode.value = 'existing-patient';
            newSec.classList.add('hidden');
            existSec.classList.remove('hidden');

            btnExist.classList.replace('border-transparent', 'border-red-500');
            btnExist.classList.replace('text-gray-500', 'text-red-600');
            btnNew.classList.replace('border-red-500', 'border-transparent');
            btnNew.classList.replace('text-red-600', 'text-gray-500');

            document.getElementById('btn-submit').innerText = "Create Case";
            reqFields.forEach(f => f.removeAttribute('required'));

            const wBox = document.getElementById('namesake-warning');
            if (wBox) wBox.classList.add('hidden');
            const mnFeedback = document.getElementById('middle-name-feedback');
            if (mnFeedback) mnFeedback.classList.add('hidden');
        }
    }

    // ── Inline Validation Helper ─────────────────────────────────────────────
    function setFieldStatus(inputEl, feedbackEl, state, message) {
        if (!inputEl) return;
        inputEl.classList.remove('field-error', 'field-warning', 'field-success', 'border-red-400', 'border-gray-300');

        if (feedbackEl) {
            feedbackEl.className = 'text-xs mt-1.5 transition-all duration-200';
            if (message) {
                feedbackEl.innerHTML = message;
                feedbackEl.classList.remove('hidden');
            } else {
                feedbackEl.innerHTML = '';
                feedbackEl.classList.add('hidden');
            }
        }

        if (state === 'error') {
            inputEl.classList.add('field-error');
            if (feedbackEl) feedbackEl.classList.add('text-red-600', 'font-medium');
        } else if (state === 'warning') {
            inputEl.classList.add('field-warning');
            if (feedbackEl) feedbackEl.classList.add('text-amber-700', 'font-medium');
        } else if (state === 'success') {
            inputEl.classList.add('field-success');
            if (feedbackEl) feedbackEl.classList.add('text-emerald-600', 'font-medium');
        } else {
            inputEl.classList.add('border-gray-300');
        }
    }

    // ── Field Validations ───────────────────────────────────────────────────
    function validateFirstName(isSubmitted = false) {
        const input = document.getElementById('first-name');
        const feedback = document.getElementById('first-name-feedback');
        if (!input) return true;

        const val = input.value.trim();
        if (!val) {
            if (isSubmitted) {
                setFieldStatus(input, feedback, 'error', 'First name is required.');
                return false;
            }
            setFieldStatus(input, feedback, 'normal', '');
            return false;
        }

        if (!/^[a-zA-ZÀ-ÿ\s.\-ñÑ]+$/.test(val)) {
            setFieldStatus(input, feedback, 'error', 'Letters, spaces, and hyphens only.');
            return false;
        }

        if (val.length < 2) {
            if (isSubmitted) {
                setFieldStatus(input, feedback, 'error', 'Must be at least 2 characters.');
                return false;
            }
            setFieldStatus(input, feedback, 'warning', 'Minimum 2 characters.');
            return false;
        }

        setFieldStatus(input, feedback, 'success', '');
        return true;
    }

    function validateLastName(isSubmitted = false) {
        const input = document.getElementById('last-name');
        const feedback = document.getElementById('last-name-feedback');
        if (!input) return true;

        const val = input.value.trim();
        if (!val) {
            if (isSubmitted) {
                setFieldStatus(input, feedback, 'error', 'Last name is required.');
                return false;
            }
            setFieldStatus(input, feedback, 'normal', '');
            return false;
        }

        if (!/^[a-zA-ZÀ-ÿ\s.\-ñÑ]+$/.test(val)) {
            setFieldStatus(input, feedback, 'error', 'Letters, spaces, and hyphens only.');
            return false;
        }

        if (val.length < 2) {
            if (isSubmitted) {
                setFieldStatus(input, feedback, 'error', 'Must be at least 2 characters.');
                return false;
            }
            setFieldStatus(input, feedback, 'warning', 'Minimum 2 characters.');
            return false;
        }

        setFieldStatus(input, feedback, 'success', '');
        return true;
    }

    function validateMiddleName(isSubmitted = false) {
        const input = document.getElementById('middle-name');
        const feedback = document.getElementById('middle-name-feedback');
        const reqAsterisk = document.getElementById('middle-name-req');
        const optBadge = document.getElementById('middle-name-opt');
        if (!input) return true;

        const val = input.value.trim();

        if (window.__hasNamesake) {
            if (reqAsterisk) reqAsterisk.classList.remove('hidden');
            if (optBadge) optBadge.classList.add('hidden');

            if (!val) {
                setFieldStatus(input, feedback, 'error', 'Middle name is required because a matching patient record exists.');
                return false;
            }

            if (!/^[a-zA-ZÀ-ÿ\s.\-ñÑ]+$/.test(val)) {
                setFieldStatus(input, feedback, 'error', 'Letters, spaces, and hyphens only.');
                return false;
            }

            // Check if user entered the exact same middle name as any namesake match
            const exactMatch = (window.__namesakeMatches || []).some(m => {
                const mMid = (m.middle_name || '').trim().toLowerCase();
                return mMid && mMid === val.toLowerCase();
            });

            if (exactMatch) {
                setFieldStatus(input, feedback, 'warning', '⚠ An existing record has this exact middle name. Verify patient profile above.');
                return true;
            }

            setFieldStatus(input, feedback, 'success', '✓ Middle name differentiates patient from existing records.');
            return true;
        } else {
            if (reqAsterisk) reqAsterisk.classList.add('hidden');
            if (optBadge) optBadge.classList.remove('hidden');

            if (!val) {
                setFieldStatus(input, feedback, 'normal', '');
                return true;
            }

            if (!/^[a-zA-ZÀ-ÿ\s.\-ñÑ]+$/.test(val)) {
                setFieldStatus(input, feedback, 'error', 'Letters, spaces, and hyphens only.');
                return false;
            }

            setFieldStatus(input, feedback, 'success', '');
            return true;
        }
    }

    function validateBirthdate(isSubmitted = false) {
        const picker = document.getElementById('birthdate-picker-container')?._bdPicker;
        if (picker) {
            const res = picker.validate(isSubmitted);
            return res.isValid;
        }

        const input = document.getElementById('birthdate');
        const feedback = document.getElementById('birthdate-feedback');
        if (!input) return true;

        const val = input.value.trim();
        if (!val) {
            if (isSubmitted) {
                setFieldStatus(input, feedback, 'error', 'Birthdate is required.');
                return false;
            }
            setFieldStatus(input, feedback, 'normal', '');
            return false;
        }

        const bdate = new Date(val);
        const today = new Date();
        bdate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        if (isNaN(bdate.getTime())) {
            setFieldStatus(input, feedback, 'error', 'Please enter a valid birthdate.');
            return false;
        }

        if (bdate > today) {
            setFieldStatus(input, feedback, 'error', 'Birthdate cannot be in the future.');
            return false;
        }

        let age = today.getFullYear() - bdate.getFullYear();
        const mDiff = today.getMonth() - bdate.getMonth();
        if (mDiff < 0 || (mDiff === 0 && today.getDate() < bdate.getDate())) {
            age--;
        }
        if (age < 0) age = 0;

        setFieldStatus(input, feedback, 'success', `✓ Age: ${age} year${age === 1 ? '' : 's'} old`);
        return true;
    }

    function validateContact(isSubmitted = false) {
        const input = document.getElementById('contact');
        const feedback = document.getElementById('contact-feedback');
        if (!input) return true;

        // Auto-sanitize digits live
        input.value = input.value.replace(/\D/g, '').slice(0, 11);
        const val = input.value;

        if (!val) {
            if (isSubmitted) {
                setFieldStatus(input, feedback, 'error', 'Contact number is required.');
                return false;
            }
            setFieldStatus(input, feedback, 'normal', '');
            return false;
        }

        if (val.length >= 2 && !val.startsWith('09')) {
            setFieldStatus(input, feedback, 'error', 'Must start with 09 (e.g. 09123456789).');
            return false;
        }

        if (val.length < 11) {
            if (isSubmitted) {
                setFieldStatus(input, feedback, 'error', `Must be exactly 11 digits (${val.length}/11 entered).`);
                return false;
            }
            setFieldStatus(input, feedback, 'warning', `11 digits required (${val.length}/11 entered).`);
            return false;
        }

        setFieldStatus(input, feedback, 'success', '✓ Valid Philippine mobile number (11/11)');
        return true;
    }

    function validateEmail(isSubmitted = false) {
        const input = document.getElementById('email');
        const feedback = document.getElementById('email-feedback');
        if (!input) return true;

        const val = input.value.trim();
        if (!val) {
            setFieldStatus(input, feedback, 'normal', '');
            return true;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(val)) {
            setFieldStatus(input, feedback, 'error', 'Please enter a valid email format (e.g. patient@example.com).');
            return false;
        }

        setFieldStatus(input, feedback, 'success', '✓ Valid email address');
        return true;
    }

    function validatePhilHealth(isSubmitted = false) {
        const card = document.getElementById('card');
        const idInput = document.getElementById('id-number');
        const statusMsg = document.getElementById('philhealth-status-msg');
        const relSelect = document.getElementById('philhealth_relation');

        if (card && card.value === 'With PhilHealth Card') {
            const val = (idInput ? idInput.value : '').trim();
            const philHealthPattern = /^\d{2}-\d{9}-\d{1}$/;

            if (!val) {
                if (isSubmitted) {
                    setFieldStatus(idInput, statusMsg, 'error', 'PhilHealth ID Number is required.');
                    return false;
                }
                return false;
            }

            if (!philHealthPattern.test(val)) {
                setFieldStatus(idInput, statusMsg, 'error', 'Format must be XX-XXXXXXXXX-X (12 digits).');
                return false;
            }

            if (relSelect && !relSelect.value) {
                if (isSubmitted) {
                    relSelect.classList.add('field-error');
                    if (statusMsg) {
                        statusMsg.innerText = "Please select patient's relation to ID.";
                        statusMsg.className = 'text-xs text-red-600 mt-2 block font-medium';
                    }
                    return false;
                }
            } else if (relSelect) {
                relSelect.classList.remove('field-error');
            }

            if (statusMsg && statusMsg.innerText.includes('already fully utilized')) {
                return false;
            }
        }
        return true;
    }

    document.addEventListener('DOMContentLoaded', () => {
        togglePhilHealthId();

        const bdateInput = document.getElementById('birthdate');
        if (bdateInput) {
            bdateInput.addEventListener('change', () => validateBirthdate(true));
            bdateInput.addEventListener('input', () => validateBirthdate(false));
            if (bdateInput.value.trim()) {
                validateBirthdate(false);
            }
        }

        // Live validation listeners for patient form fields
        const fnInput = document.getElementById('first-name');
        const lnInput = document.getElementById('last-name');
        const mnInput = document.getElementById('middle-name');
        const ctInput = document.getElementById('contact');
        const emInput = document.getElementById('email');

        if (fnInput) {
            fnInput.addEventListener('input', () => {
                validateFirstName(false);
                scheduleNamesakeCheck();
            });
            fnInput.addEventListener('blur', () => {
                validateFirstName(true);
                checkNamesake();
            });
        }

        if (lnInput) {
            lnInput.addEventListener('input', () => {
                validateLastName(false);
                scheduleNamesakeCheck();
            });
            lnInput.addEventListener('blur', () => {
                validateLastName(true);
                checkNamesake();
            });
        }

        if (mnInput) {
            mnInput.addEventListener('input', () => {
                validateMiddleName(false);
            });
            mnInput.addEventListener('blur', () => {
                validateMiddleName(true);
            });
        }

        if (ctInput) {
            ctInput.addEventListener('input', () => validateContact(false));
            ctInput.addEventListener('blur', () => validateContact(true));
            if (ctInput.value.trim()) {
                validateContact(false);
            }
        }

        if (emInput) {
            emInput.addEventListener('input', () => validateEmail(false));
            emInput.addEventListener('blur', () => validateEmail(true));
        }

        // ── Form Submit Validation ───────────────────────────────────────────
        document.querySelector('form[method="POST"]').addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('btn-submit');
            if (submitBtn.disabled) return;

            // Sync exam-selector required check
            document.querySelectorAll('.exam-ms-component').forEach(container => {
                const hidden = container.querySelector('.exam-ms-hidden-input');
                const reqCheck = container.querySelector('.exam-ms-required-check');
                if (hidden && reqCheck) {
                    reqCheck.value = hidden.value;
                    if (hidden.value.trim()) {
                        reqCheck.setCustomValidity('');
                    }
                }
            });

            const formMode = document.getElementById('form-mode').value;
            let invalidElements = [];

            if (formMode === 'new-patient') {
                await checkNamesake();
                const isFnValid = validateFirstName(true);
                const isMnValid = validateMiddleName(true);
                const isLnValid = validateLastName(true);
                const isBdValid = validateBirthdate(true);
                const isCtValid = validateContact(true);
                const isEmValid = validateEmail(true);
                const isPhValid = validatePhilHealth(true);

                // Exam validation
                let isExamValid = true;
                const examReqCheck = document.querySelector('.exam-ms-required-check');
                const examContainer = document.querySelector('.exam-ms-component');
                if (examReqCheck && !examReqCheck.value.trim()) {
                    isExamValid = false;
                    examContainer?.classList.add('field-error', 'rounded-lg');
                } else {
                    examContainer?.classList.remove('field-error');
                }

                if (!isFnValid) invalidElements.push(document.getElementById('first-name'));
                if (!isMnValid) invalidElements.push(document.getElementById('middle-name'));
                if (!isLnValid) invalidElements.push(document.getElementById('last-name'));
                if (!isBdValid) invalidElements.push(document.getElementById('birthdate'));
                if (!isCtValid) invalidElements.push(document.getElementById('contact'));
                if (!isEmValid) invalidElements.push(document.getElementById('email'));
                if (!isPhValid) invalidElements.push(document.getElementById('id-number') || document.getElementById('philhealth_relation'));
                if (!isExamValid) invalidElements.push(examContainer || examReqCheck);
            } else {
                // Existing Patient Mode
                const existingPatientId = document.getElementById('existing-patient-id')?.value;
                if (!existingPatientId) {
                    const searchInput = document.getElementById('search-patient');
                    invalidElements.push(searchInput);
                    searchInput?.classList.add('field-error');
                }

                const isPhValid = validatePhilHealth(true);
                if (!isPhValid) invalidElements.push(document.getElementById('id-number') || document.getElementById('philhealth_relation'));

                let isExamValid = true;
                const examReqCheck = document.querySelector('.exam-ms-required-check');
                const examContainer = document.querySelector('.exam-ms-component');
                if (examReqCheck && !examReqCheck.value.trim()) {
                    isExamValid = false;
                    examContainer?.classList.add('field-error', 'rounded-lg');
                } else {
                    examContainer?.classList.remove('field-error');
                }
                if (!isExamValid) invalidElements.push(examContainer || examReqCheck);
            }

            invalidElements = invalidElements.filter(Boolean);

            // If there are errors, focus and shake the first invalid field immediately!
            // Do NOT open the confirmation modal or show a SweetAlert error modal.
            if (invalidElements.length > 0) {
                const firstInvalid = invalidElements[0];
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (typeof firstInvalid.focus === 'function') {
                    firstInvalid.focus();
                }
                firstInvalid.classList.add('animate-shake');
                setTimeout(() => firstInvalid.classList.remove('animate-shake'), 400);
                return;
            }

            // Only prompt confirmation if 100% valid!
            const confirmed = await confirmAlert('Confirm Registration', 'Would you like to confirm registering this patient and creating a new case?');
            if (!confirmed.isConfirmed) return;

            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
            document.body.style.cursor = 'wait';

            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;

            this.submit();
        });

        // Mode initialization
        const currentMode = document.getElementById('form-mode').value || 'new-patient';
        switchTab(currentMode);

        // Run initial check if names are already pre-filled (e.g. after POST reload)
        if (fnInput && lnInput && fnInput.value.trim() && lnInput.value.trim()) {
            checkNamesake();
        }
    });

    // ── Namesake / Duplicate Name Real-Time Detection ────────────────────────
    let namesakeTimeout = null;
    window.__hasNamesake = false;
    window.__namesakeMatches = [];

    function safeEscape(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function checkNamesake() {
        const formMode = document.getElementById('form-mode')?.value;
        if (formMode !== 'new-patient') return Promise.resolve(false);

        const fnInput = document.getElementById('first-name');
        const lnInput = document.getElementById('last-name');
        const warningBox = document.getElementById('namesake-warning');
        const namesakeList = document.getElementById('namesake-list');

        if (!fnInput || !lnInput) return Promise.resolve(false);

        const fn = fnInput.value.trim();
        const ln = lnInput.value.trim();

        if (fn.length < 2 || ln.length < 2) {
            window.__hasNamesake = false;
            window.__namesakeMatches = [];
            if (warningBox) warningBox.classList.add('hidden');
            validateMiddleName(false);
            return Promise.resolve(false);
        }

        return fetch(`index.php?role=radtech&page=patient-registration&check_duplicate_name=1&first_name=${encodeURIComponent(fn)}&last_name=${encodeURIComponent(ln)}`)
            .then(res => {
                if (!res.ok) throw new window.Error("Network response was not ok");
                return res.json();
            })
            .then(data => {
                if (data.has_duplicate) {
                    window.__hasNamesake = true;
                    window.__namesakeMatches = data.matches || [];

                    if (warningBox) warningBox.classList.remove('hidden');

                    // Immediately re-validate middle name with duplicate context
                    validateMiddleName(true);

                    if (namesakeList && data.matches) {
                        namesakeList.innerHTML = data.matches.map(m => `
                            <div class="bg-white p-3 rounded-lg border border-amber-200 shadow-2xs hover:border-amber-300 transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-gray-900">${safeEscape(m.first_name)} ${safeEscape(m.middle_name ? m.middle_name + ' ' : '')}${safeEscape(m.last_name)}</span>
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-semibold border border-gray-200">ID: ${safeEscape(m.patient_number || 'N/A')}</span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-0.5 text-[11px] text-gray-500">
                                        <span><strong class="text-gray-700">Birthdate:</strong> ${safeEscape(m.birthdate || 'N/A')} (${safeEscape(String(m.age || ''))} yrs)</span>
                                        <span>•</span>
                                        <span><strong class="text-gray-700">Sex:</strong> ${safeEscape(m.sex || 'N/A')}</span>
                                        <span>•</span>
                                        <span><strong class="text-gray-700">Branch:</strong> ${safeEscape(m.branch_name || 'Branch N/A')}</span>
                                        ${m.contact_number ? `<span>•</span><span><strong class="text-gray-700">Contact:</strong> ${safeEscape(m.contact_number)}</span>` : ''}
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <button type="button" onclick="useExistingPatientById(${m.id})" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 transition shadow-2xs">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                        Use This Profile
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }

                    if (window.lucide) {
                        lucide.createIcons();
                    }
                } else {
                    window.__hasNamesake = false;
                    window.__namesakeMatches = [];
                    if (warningBox) warningBox.classList.add('hidden');
                    validateMiddleName(false);
                }
            })
            .catch(err => {
                console.error("Namesake check error:", err);
            });
    }

    function scheduleNamesakeCheck() {
        clearTimeout(namesakeTimeout);
        namesakeTimeout = setTimeout(checkNamesake, 300);
    }

    function useExistingPatientById(id) {
        const match = window.__namesakeMatches?.find(m => String(m.id) === String(id));
        if (!match) return;

        switchTab('existing-patient');
        selectPatient({
            id: match.id,
            first_name: match.first_name,
            middle_name: match.middle_name || '',
            last_name: match.last_name,
            patient_number: match.patient_number,
            age: match.age,
            sex: match.sex,
            contact_number: match.contact_number
        });

        const selectedDispName = `${match.first_name}${match.middle_name ? ' ' + match.middle_name : ''} ${match.last_name}`;

        // Clear feedback modal - guide user to specify examination and submit
        Swal.fire({
            icon: 'info',
            title: 'Patient Profile Selected',
            html: `Loaded record for <strong>${safeEscape(selectedDispName)}</strong> (${safeEscape(match.patient_number || 'N/A')}).<br><br><span class="text-sm text-gray-600">Please choose the <strong>Exam Type</strong> below and click <strong>"Create Case"</strong> to add them to the Patient Queue.</span>`,
            confirmButtonText: 'Proceed to Exam Details',
            confirmButtonColor: '#2563eb',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
            }
        }).then(() => {
            const examBox = document.querySelector('.exam-ms-component');
            if (examBox) {
                examBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        document.getElementById('selected-patient-info')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function focusMiddleName() {
        const mnInput = document.getElementById('middle-name');
        if (mnInput) {
            mnInput.focus();
            mnInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Flash ring
            mnInput.classList.add('ring-4', 'ring-amber-300');
            setTimeout(() => {
                mnInput.classList.remove('ring-4', 'ring-amber-300');
            }, 1200);
        }
    }

    // AJAX Search logic using Event Delegation since Vue.js replaces the DOM nodes!
    let searchTimeout;

    document.addEventListener('keydown', (e) => {
        if (e.target && e.target.id === 'search-patient') {
            if (e.key === 'Enter') {
                e.preventDefault(); // Stop form submission
                clearTimeout(searchTimeout);
                triggerSearch(e.target.value.trim());
            }
        }
    });

    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'search-patient') {
            clearTimeout(searchTimeout);
            const q = e.target.value.trim();
            const resultsList = document.getElementById('search-results');
            if (q.length < 2) {
                if (resultsList) resultsList.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(() => triggerSearch(q), 300);
        }
    });

    function triggerSearch(q) {
        const resultsList = document.getElementById('search-results');
        if (!resultsList) return;

        resultsList.innerHTML = `<li class="py-2 px-3 text-sm text-gray-500 flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Searching...
        </li>`;
        resultsList.classList.remove('hidden');

        fetch(`index.php?role=radtech&page=patient-registration&ajax_search=1&q=${encodeURIComponent(q)}`)
            .then(res => {
                if (!res.ok) throw new window.Error("Network response was not ok");
                return res.json();
            })
            .then(data => {
                resultsList.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(p => {
                        const li = document.createElement('li');
                        const patFullDisp = safeEscape(p.first_name) + (p.middle_name ? ' ' + safeEscape(p.middle_name) : '') + ' ' + safeEscape(p.last_name);
                        li.className = 'cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 text-gray-900 border-b border-gray-100 last:border-0';
                        li.innerHTML = `
                            <div class="flex flex-col">
                                <span class="font-medium">${patFullDisp}</span>
                                <span class="text-xs text-gray-500">Patient No: ${safeEscape(p.patient_number || 'N/A')} | Age: ${safeEscape(p.age)} | Contact: ${safeEscape(p.contact_number || 'N/A')}</span>
                            </div>
                        `;
                        li.onclick = () => selectPatient(p);
                        resultsList.appendChild(li);
                    });
                    resultsList.classList.remove('hidden');
                } else {
                    resultsList.innerHTML = `<li class="py-2 px-3 text-sm text-gray-500">No patients found matches "${q}".</li>`;
                    resultsList.classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error(err);
                resultsList.innerHTML = `<li class="py-2 px-3 text-sm text-red-500">Error searching. Please check your connection.</li>`;
                resultsList.classList.remove('hidden');
            });
    }

    // Hide dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const searchInput = document.getElementById('search-patient');
        const resultsList = document.getElementById('search-results');
        if (searchInput && resultsList && !searchInput.contains(e.target) && !resultsList.contains(e.target)) {
            resultsList.classList.add('hidden');
        }
    });

    function selectPatient(p) {
        const searchInput = document.getElementById('search-patient');
        const resultsList = document.getElementById('search-results');
        document.getElementById('existing-patient-id').value = p.id;
        const pFull = (p.first_name || '') + (p.middle_name ? ' ' + p.middle_name : '') + ' ' + (p.last_name || '');
        document.getElementById('sp-name').innerText = pFull.trim();
        document.getElementById('sp-patient-no').innerText = p.patient_number || 'N/A';
        document.getElementById('sp-age').innerText = p.age;
        document.getElementById('sp-sex').innerText = p.sex;
        document.getElementById('sp-contact').innerText = p.contact_number || 'N/A';

        document.getElementById('selected-patient-info').classList.remove('hidden');
        if (resultsList) resultsList.classList.add('hidden');
        if (searchInput) {
            searchInput.value = '';
            searchInput.disabled = true;
        }
    }

    function clearSelectedPatient() {
        const searchInput = document.getElementById('search-patient');
        document.getElementById('existing-patient-id').value = '';
        document.getElementById('selected-patient-info').classList.add('hidden');
        if (searchInput) {
            searchInput.disabled = false;
            searchInput.focus();
        }
    }
</script>