<?php
/**
 * Patient Registration View
 * Backend logic handled by PatientRegistrationController.php
 */
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker.min.css">
<script src="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/js/datepicker-full.min.js"></script>

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
            ?>
            <div
                class="rounded-lg bg-green-50 border border-green-300 p-4 mb-6 animate-in fade-in slide-in-from-top-4 duration-500 flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-6 h-6 text-green-600"></i>
                <div>
                    <h3 class="font-semibold text-green-800">Registration Successful</h3>
                    <p class="text-sm text-green-700">
                        <?= htmlspecialchars($regSuccess['message']) ?>
                        (Case: <strong><?= htmlspecialchars($regSuccess['case_number']) ?></strong> for
                        <?= htmlspecialchars($regSuccess['patient_name']) ?>)
                    </p>
                </div>
            </div>
        <?php endif; ?>

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

        <form method="POST" action="" class="space-y-6 rounded-xl border border-gray-300 shadow-sm bg-white p-6 mt-4">

            <!-- Hidden field for existing patient selected -->
            <input type="hidden" name="form-mode" id="form-mode"
                value="<?= htmlspecialchars($_POST['form-mode'] ?? 'new-patient') ?>">
            <input type="hidden" name="existing-patient-id" id="existing-patient-id"
                value="<?= htmlspecialchars($_POST['existing-patient-id'] ?? '') ?>">

            <div id="new-patient-section">
                <fieldset class="space-y-4">
                    <legend class="text-lg font-medium text-gray-900 mb-2">Patient Information</legend>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="first-name" class="block text-sm font-medium text-gray-700 mb-2">First Name
                                <span class="text-red-500">*</span></label>
                            <input type="text" id="first-name" name="first-name"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 req-new">
                        </div>
                        <div>
                            <label for="middle-name" class="block text-sm font-medium text-gray-700 mb-2">Middle
                                Name</label>
                            <input type="text" id="middle-name" name="middle-name"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label for="last-name" class="block text-sm font-medium text-gray-700 mb-2">Last Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="last-name" name="last-name"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 req-new">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">
                        <div>
                            <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-2">Birthdate <span
                                    class="text-red-500">*</span></label>
                            <?php $birthdateValue = $_POST['birthdate'] ?? ''; ?>
                            <div class="relative">
                                <input type="text" id="birthdate" name="birthdate" required
                                    placeholder="Select birthdate" readonly
                                    value="<?= htmlspecialchars($birthdateValue) ?>"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 pl-10 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 req-new">
                                <i data-lucide="calendar" class="absolute left-3 top-2.5 w-4 h-4 text-gray-400"></i>
                            </div>
                        </div>
                        <div>
                            <label for="sex" class="block text-sm font-medium text-gray-700 mb-2">Sex</label>
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
                            <input type="tel" id="contact" name="contact" pattern="[0-9]{11}" maxlength="11"
                                minlength="11" title="Please enter exactly 11 digits" placeholder="e.g. 09123456789"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 req-new">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email
                                Address</label>
                            <input type="email" id="email" name="email" placeholder="patient@example.com"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
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

            <div id="existing-patient-section" class="hidden">
                <fieldset class="space-y-4">
                    <legend class="text-lg font-medium text-gray-900 mb-2">Search Existing Patient</legend>
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

                    <div id="philhealth-id-container">
                        <label for="id-number" class="block text-sm font-medium text-gray-700 mb-2">PhilHealth ID
                            Number</label>
                        <input id="id-number" name="id-number" type="text" inputmode="numeric" maxlength="14"
                            oninput="formatPhilHealthInput(this)" placeholder="XX-XXXXXXXXX-X"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
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
        if (select.value === 'With PhilHealth Card') {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            idInput.value = '';
            idInput.setCustomValidity('');
        }
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
        }

        if (isChanging) {
            clearExaminationDetails();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        togglePhilHealthId();

        const datepicker = new Datepicker(document.getElementById('birthdate'), {
            autohide: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true
        });

        // Validate PhilHealth ID on form submit
        document.querySelector('form[method="POST"]').addEventListener('submit', async function (e) {
            // Check if already submitting
            const submitBtn = document.getElementById('btn-submit');
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }

            // Prevent default immediately to handle async confirmation
            e.preventDefault();

            // ── Sync exam-selector required-check so browser validation passes ──
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

            const card = document.getElementById('card');
            const idInput = document.getElementById('id-number');
            if (card.value === 'With PhilHealth Card') {
                const philHealthPattern = /^\d{2}-\d{9}-\d{1}$/;
                if (!idInput.value.trim()) {
                    idInput.setCustomValidity('PhilHealth ID Number is required.');
                    idInput.reportValidity();
                    idInput.addEventListener('input', () => idInput.setCustomValidity(''), { once: true });
                    return;
                } else if (!philHealthPattern.test(idInput.value.trim())) {
                    idInput.setCustomValidity('Format must be XX-XXXXXXXXX-X (digits only).');
                    idInput.reportValidity();
                    idInput.addEventListener('input', () => idInput.setCustomValidity(''), { once: true });
                    return;
                }
            }

            // Validate birthdate is not in the future (for new-patient mode)
            const formMode = document.getElementById('form-mode').value;
            if (formMode === 'new-patient') {
                const birthdateVal = document.getElementById('birthdate').value;
                if (birthdateVal) {
                    const bdate = new Date(birthdateVal);
                    const today = new Date();
                    bdate.setHours(0, 0, 0, 0);
                    today.setHours(0, 0, 0, 0);
                    if (bdate > today) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Birthdate',
                            text: 'Birthdate cannot be in the future.',
                            confirmButtonColor: '#dc2626'
                        });
                        return;
                    }
                }
            }

            // Show confirmation before proceeding
            const confirmed = await confirmAlert('Confirm Registration', 'Would you like to confirm registering this patient and creating a new case?');
            if (!confirmed.isConfirmed) return;

            // If we reached here, form is valid and ready to submit
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

            // Use the native form.submit() to bypass this listener
            this.submit();
        });

        // Check if form-mode is preserved in case of an error, otherwise default to new-patient
        const currentMode = document.getElementById('form-mode').value || 'new-patient';
        switchTab(currentMode);

        // If an existing patient was selected before submission error, simulate the click
        const existingPatientId = document.getElementById('existing-patient-id').value;
        if (existingPatientId) {
            // we could fetch patient details, but just simple reset is easier, 
            // since they probably got an error BEFORE selecting correctly.
        }
    });

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
                        li.className = 'cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 text-gray-900 border-b border-gray-100 last:border-0';
                        li.innerHTML = `
                            <div class="flex flex-col">
                                <span class="font-medium">${p.first_name} ${p.last_name}</span>
                                <span class="text-xs text-gray-500">Patient No: ${p.patient_number || 'N/A'} | Age: ${p.age} | Contact: ${p.contact_number || 'N/A'}</span>
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
        document.getElementById('sp-name').innerText = p.first_name + ' ' + p.last_name;
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