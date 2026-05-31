<?php
// Reusable Component for Selecting Multiple Examination Types

if (!isset($examInputName)) {
    $examInputName = 'exam_type';
}
$isReadOnly = $isReadOnly ?? false;

// Normalize selected exams to array
if (isset($preSelectedExams)) {
    if (is_string($preSelectedExams)) {
        $selectedExamsArray = array_map('trim', explode(',', $preSelectedExams));
    } else if (is_array($preSelectedExams)) {
        $selectedExamsArray = $preSelectedExams;
    } else {
        $selectedExamsArray = [];
    }
    // Filter empty and "To be determined" (case-insensitive)
    $selectedExamsArray = array_filter($selectedExamsArray, function($exam) {
        $exam = trim($exam);
        return $exam !== '' && strtolower($exam) !== 'to be determined';
    });
} else {
    $selectedExamsArray = [];
}

$placeholderText = $placeholderText ?? "Select Exam Type...";

// Flat list of exams
$allStandardExams = [
    'CHEST PA',
    'CHEST AP/L',
    'CHEST PA/L',
    'APICOLORDOTIC VIEW',
    'CONED DOWN VIEW',
    'HAND AP/O',
    'ELBOW',
    'FOREARM',
    'HUMERUS',
    'FOOT',
    'SHOULDER',
    'LEG',
    'FEMUR (THIGH)',
    'SKULL AP/LAT',
    'CERVICAL AP/LAT',
    'PELVIS',
    'THORACIC CAGE',
    'THORACIC SPINE',
    'LUMBOSACRAL (L.S.V.)',
    'THORACOLUMBAR',
    'KNEE',
    'MANDIBLE',
    "NASAL BONE SOFT T.",
    "NASAL BONE AP/LAT",
    "PNS/WATERS/CALDWELL",
    "ABDOMEN (PEDIA)",
    "ABDOMEN UPRIGHT",
    "SKULL 3PT LANDING",
    'SCAPULAR Y',
    'WATER\'S VIEW',
    'TEMPOROMANDIBULAR JOINT'
];
sort($allStandardExams);

// Map legacy inputs
$legacyMapping = [
    'Chest PA' => 'Chest PA',
    'Abdominal X-ray' => 'ABDOMEN FLAT/UPRIGHT',
    'Extremity X-ray' => 'FOREARM AP/LAT',
    'Skull X-ray' => 'SKULL AP/LAT',
    'Lumbar Spine' => 'LUMBAR',
    'Pelvis' => 'PELVIS AP/LAT',
    'Neck' => 'SOFT TISSUE NECK',
    'Head' => 'SKULL AP/LAT',
    'Legs' => 'LEG AP/LAT'
];

$processedSelectedExams = [];
foreach ($selectedExamsArray as $sel) {
    if (isset($legacyMapping[$sel])) {
        $sel = $legacyMapping[$sel];
    }
    // If not in standard list, we'll temporarily add it to standard list so it shows up in selected and can be "removed"
    if (!in_array($sel, $allStandardExams)) {
        $allStandardExams[] = $sel;
    }
    $processedSelectedExams[] = $sel;
}
$processedSelectedExams = array_unique($processedSelectedExams);
$selectedCsv = implode(', ', $processedSelectedExams);
$allStandardExams = array_unique($allStandardExams);

// Generate unique ID prefix
$uuid = uniqid('es_');
?>

<div class="relative exam-ms-component" id="<?= $uuid ?>" data-input-name="<?= htmlspecialchars($examInputName) ?>">
    <!-- Hidden input for form submission -->
    <input type="hidden" name="<?= htmlspecialchars($examInputName) ?>" value="<?= htmlspecialchars($selectedCsv) ?>"
        class="exam-ms-hidden-input" <?= $isReadOnly ? 'disabled' : '' ?>>
    <?php if (!$isReadOnly): ?>
        <input type="text" class="exam-ms-required-check" style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px; bottom: 0; left: 50%;" value="<?= htmlspecialchars($selectedCsv) ?>" required tabindex="-1" onfocus="this.parentElement.querySelector('.exam-ms-input').focus();" oninvalid="this.setCustomValidity('Please select at least one Exam Type.')" oninput="this.setCustomValidity('')">
    <?php endif; ?>

    <?php if ($isReadOnly): ?>
        <div
            class="w-full rounded border border-gray-300 bg-gray-100 px-3 py-2 text-sm flex flex-wrap gap-1 items-center min-h-[42px] cursor-not-allowed opacity-90">
            <?php if (empty($processedSelectedExams)): ?>
                <span class="text-gray-500 italic">No exams selected</span>
            <?php else: ?>
                <?php foreach ($processedSelectedExams as $ex): ?>
                    <span
                        class="inline-flex flex-shrink-0 items-center rounded border border-gray-300 bg-white px-2 py-0.5 text-xs font-medium text-gray-700 shadow-sm shadow-gray-200">
                        <?= htmlspecialchars($ex) ?>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Editable Container -->
        <div
            class="exam-ms-box w-full justify-between items-center bg-white border border-gray-300 rounded px-2 py-1.5 min-h-[42px] cursor-text focus-within:ring-2 focus-within:ring-red-500 focus-within:border-red-500 transition-shadow">
            <div class="exam-ms-chips-container flex flex-wrap gap-1.5 items-center flex-1">
                <!-- Chips are injected via JS -->

                <!-- Inline search input -->
                <input type="text"
                    class="exam-ms-input flex-1 min-w-[60px] max-w-full outline-none border-none bg-transparent text-sm p-1 text-gray-900 placeholder-gray-400"
                    placeholder="<?= htmlspecialchars($placeholderText) ?>" data-placeholder="<?= htmlspecialchars($placeholderText) ?>" autocomplete="off">
            </div>
        </div>

        <!-- Dropdown Menu -->
        <div
            class="exam-ms-dropdown hidden absolute z-[60] w-full mt-1 bg-white border border-gray-300 rounded shadow-lg max-h-56 overflow-y-auto">
            <ul class="exam-ms-list py-1 m-0 text-sm list-none text-gray-800">
                <?php foreach ($allStandardExams as $exam): ?>
                    <?php $isSelected = in_array($exam, $processedSelectedExams); ?>
                    <!-- Options that are already selected will be hidden via JS initially -->
                    <li class="exam-ms-option px-3 py-1.5 cursor-pointer flex justify-between items-center transition-colors hover:bg-red-600 hover:text-white <?= $isSelected ? 'hidden' : '' ?>"
                        data-value="<?= htmlspecialchars($exam) ?>">
                        <span><?= htmlspecialchars($exam) ?></span>
                    </li>
                <?php endforeach; ?>
                <li class="exam-ms-no-results hidden px-3 py-2 text-gray-500 italic">No matches found</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php if (!$isReadOnly): ?>
    <script>
        if (typeof window.examMSDelegationInit === 'undefined') {
            window.examMSDelegationInit = true;

            window.examMSOpenId = null;

            // Close dropdown
            function closeAllDropdowns() {
                document.querySelectorAll('.exam-ms-dropdown').forEach(d => d.classList.add('hidden'));
                window.examMSOpenId = null;
            }

            // Render chips
            function renderChips(container) {
                const hiddenInput = container.querySelector('.exam-ms-hidden-input');
                const chipsContainer = container.querySelector('.exam-ms-chips-container');
                const searchInput = container.querySelector('.exam-ms-input');
                const dropdown = container.querySelector('.exam-ms-dropdown');

                // Get selected values
                const valStr = hiddenInput.value.trim();
                const selected = valStr ? valStr.split(',').map(s => s.trim()).filter(s => s) : [];

                // Clear old chips (remove all children except the input box)
                const children = Array.from(chipsContainer.children);
                children.forEach(child => {
                    if (child !== searchInput) {
                        chipsContainer.removeChild(child);
                    }
                });

                // Re-build chips
                selected.forEach(item => {
                    const chip = document.createElement('span');
                    chip.className = 'inline-flex flex-shrink-0 items-center rounded border border-red-300 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 shadow-sm';

                    const text = document.createElement('span');
                    text.textContent = item;

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'exam-ms-remove-btn ml-1.5 text-red-400 hover:text-red-700 focus:outline-none font-bold align-middle';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.setAttribute('data-value', item);

                    chip.appendChild(text);
                    chip.appendChild(removeBtn);

                    chipsContainer.insertBefore(chip, searchInput);
                });

                // Update placeholder
                searchInput.placeholder = selected.length === 0 ? (searchInput.getAttribute('data-placeholder') || "Search...") : "";

                // Hide already selected options from the dropdown list
                dropdown.querySelectorAll('.exam-ms-option').forEach(opt => {
                    if (selected.includes(opt.getAttribute('data-value'))) {
                        opt.classList.add('hidden');
                        opt.classList.remove('match-visible'); // remove custom match flag
                    } else {
                        opt.classList.remove('hidden');
                        opt.classList.add('match-visible');
                    }
                });

                // clear search text
                searchInput.value = '';

                filterOptions(container, '');
            }

            function filterOptions(container, query) {
                const dropdown = container.querySelector('.exam-ms-dropdown');
                const options = dropdown.querySelectorAll('.exam-ms-option');
                const noResults = dropdown.querySelector('.exam-ms-no-results');
                const hiddenInput = container.querySelector('.exam-ms-hidden-input');
                const valStr = hiddenInput.value.trim();
                const selected = valStr ? valStr.split(',').map(s => s.trim()).filter(s => s) : [];

                let matchCount = 0;
                const q = query.toLowerCase();

                options.forEach(opt => {
                    const val = opt.getAttribute('data-value');
                    // Skip if already selected
                    if (selected.includes(val)) {
                        opt.classList.add('hidden');
                        opt.innerHTML = `<span>${val}</span>`;
                        return;
                    }

                    const textLower = val.toLowerCase();
                    if (textLower.includes(q)) {
                        opt.classList.remove('hidden');
                        matchCount++;

                        // Highlight match
                        if (q.length > 0) {
                            // Regex to boldly wrap the matched portion ignoring case
                            const regex = new window.RegExp("(" + q.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ")", "gi");
                            const highlighted = val.replace(regex, `<strong class="font-bold bg-yellow-100 text-black">$1</strong>`);
                            opt.innerHTML = `<span>${highlighted}</span>`;
                        } else {
                            opt.innerHTML = `<span>${val}</span>`;
                        }
                    } else {
                        opt.classList.add('hidden');
                        opt.innerHTML = `<span>${val}</span>`;
                    }
                });

                if (matchCount === 0) {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
            }

            // Global Event Delegation
            document.addEventListener('click', function (e) {
                // FIRST: Check remove btn before anything else (it's inside the box)
                const removeBtn = e.target.closest('.exam-ms-remove-btn');
                if (removeBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const container = removeBtn.closest('.exam-ms-component');
                    const val = removeBtn.getAttribute('data-value');
                    const hiddenInput = container.querySelector('.exam-ms-hidden-input');

                    let currentVals = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()).filter(s => s) : [];
                    currentVals = currentVals.filter(v => v !== val);
                    hiddenInput.value = currentVals.join(', ');

                    const reqCheck = container.querySelector('.exam-ms-required-check');
                    if (reqCheck) {
                        reqCheck.value = hiddenInput.value;
                        if (hiddenInput.value) reqCheck.setCustomValidity('');
                    }

                    renderChips(container);

                    // Dispatch custom event for listeners (like Patient Details image limits)
                    container.dispatchEvent(new CustomEvent('exam-ms:change', {
                        detail: {
                            value: hiddenInput.value,
                            count: currentVals.length
                        },
                        bubbles: true
                    }));
                    return;
                }

                // Click on an option to select it
                const option = e.target.closest('.exam-ms-option');
                if (option) {
                    const container = option.closest('.exam-ms-component');
                    const val = option.getAttribute('data-value');
                    const hiddenInput = container.querySelector('.exam-ms-hidden-input');

                    let currentVals = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()).filter(s => s) : [];
                    if (!currentVals.includes(val)) {
                        currentVals.push(val);
                        hiddenInput.value = currentVals.join(', ');
                        
                        const reqCheck = container.querySelector('.exam-ms-required-check');
                        if (reqCheck) {
                            reqCheck.value = hiddenInput.value;
                            reqCheck.setCustomValidity('');
                        }
                    }

                    renderChips(container);
                    container.querySelector('.exam-ms-input').focus();

                    // Dispatch custom event for listeners
                    container.dispatchEvent(new CustomEvent('exam-ms:change', {
                        detail: {
                            value: hiddenInput.value,
                            count: currentVals.length
                        },
                        bubbles: true
                    }));
                    return;
                }

                // Click inside the input box area -> open dropdown and focus input
                const msBox = e.target.closest('.exam-ms-box');
                if (msBox) {
                    const container = msBox.closest('.exam-ms-component');
                    const dropdown = container.querySelector('.exam-ms-dropdown');
                    const searchInput = container.querySelector('.exam-ms-input');

                    if (window.examMSOpenId && window.examMSOpenId !== container.id) {
                        closeAllDropdowns();
                    }

                    dropdown.classList.remove('hidden');
                    searchInput.focus();
                    window.examMSOpenId = container.id;
                    return;
                }

                // Click outside the component -> close
                if (window.examMSOpenId) {
                    const container = document.getElementById(window.examMSOpenId);
                    if (container && !container.contains(e.target)) {
                        closeAllDropdowns();
                    }
                }
            });

            // Search Input Handling
            document.addEventListener('input', function (e) {
                if (e.target.classList.contains('exam-ms-input')) {
                    const container = e.target.closest('.exam-ms-component');
                    const dropdown = container.querySelector('.exam-ms-dropdown');
                    dropdown.classList.remove('hidden');
                    window.examMSOpenId = container.id;

                    filterOptions(container, e.target.value);
                }
            });

            // Keydown handling for easier use (Backspace to remove last chip)
            document.addEventListener('keydown', function (e) {
                if (e.target.classList.contains('exam-ms-input')) {
                    const container = e.target.closest('.exam-ms-component');

                    // Backspace logic: remove last chip if input is empty
                    if (e.key === 'Backspace' && e.target.value === '') {
                        const hiddenInput = container.querySelector('.exam-ms-hidden-input');
                        let currentVals = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()) : [];
                        if (currentVals.length > 0) {
                            currentVals.pop();
                            hiddenInput.value = currentVals.join(', ');
                            
                            const reqCheck = container.querySelector('.exam-ms-required-check');
                            if (reqCheck) {
                                reqCheck.value = hiddenInput.value;
                                if (hiddenInput.value) reqCheck.setCustomValidity('');
                            }
                            
                            renderChips(container);

                            // Dispatch custom event for listeners
                            container.dispatchEvent(new CustomEvent('exam-ms:change', {
                                detail: {
                                    value: hiddenInput.value,
                                    count: currentVals.length
                                },
                                bubbles: true
                            }));
                        }
                    }
                }
            });

            // Initialize all components that are loaded
            setInterval(() => {
                document.querySelectorAll('.exam-ms-component:not(.initialized)').forEach(container => {
                    container.classList.add('initialized');
                    renderChips(container);
                });
            }, 300);
        }
    </script>
<?php endif; ?>