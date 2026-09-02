let currentEditId = null;
let currentOriginalPhilHealthId = '';
let currentOriginalRelation = '';
window.currentEditingPatientId = null;

function setModalInputsDisabled(disabled) {
    document.getElementById('modalName').disabled = disabled;
    document.getElementById('modalBirthdate').disabled = disabled;
    document.getElementById('modalSex').disabled = disabled;
    document.getElementById('modalContact').disabled = disabled;
    document.getElementById('modalAddress').disabled = disabled;
    document.getElementById('modalPhilHealth').disabled = disabled;
    document.getElementById('modalPhilHealthId').disabled = disabled;
    document.getElementById('modalPhilHealthRelation').disabled = disabled;

    const okBtn = document.getElementById('modalOkBtn');
    if (okBtn) {
        okBtn.style.display = disabled ? 'none' : 'block';
    }
    const cancelBtn = document.getElementById('modalCancelBtn');
    if (cancelBtn) {
        cancelBtn.innerText = disabled ? 'Close' : 'Cancel';
    }
}

function openEditModal(id, name, birthdate, sex, contact, homeAddress, philhealth, philhealthId, philhealthRelation = '') {
    window.currentEditingPatientId = id;
    currentEditId = id;
    setModalInputsDisabled(false);
    document.getElementById('modalName').value = name;
    // Set the datepicker date (use the picker if available, fallback to direct value)
    const modalBirthdateInput = document.getElementById('modalBirthdate');
    modalBirthdateInput.value = birthdate;
    if (typeof modalDatePicker !== 'undefined' && modalDatePicker) {
        modalDatePicker.setDate(birthdate);
    }
    document.getElementById('modalSex').value = sex;
    document.getElementById('modalContact').value = contact;
    document.getElementById('modalAddress').value = homeAddress || '';
    document.getElementById('modalPhilHealth').value = philhealth;
    document.getElementById('modalPhilHealthId').value = philhealthId || '';
    document.getElementById('modalPhilHealthRelation').value = philhealthRelation || '';
    
    currentOriginalPhilHealthId = philhealthId || '';
    currentOriginalRelation = philhealthRelation || '';
    
    document.getElementById('editModal').classList.remove('hidden');
    togglePhilHealthId();
    if (philhealth === 'With PhilHealth Card' && philhealthId) {
        checkModalPhilHealthId();
    }
}

function openViewModal(id, name, birthdate, sex, contact, homeAddress, philhealth, philhealthId, philhealthRelation = '') {
    currentEditId = id;
    setModalInputsDisabled(true);
    document.getElementById('modalName').value = name;
    // Set the datepicker date (use the picker if available, fallback to direct value)
    const modalBirthdateInput = document.getElementById('modalBirthdate');
    modalBirthdateInput.value = birthdate;
    if (typeof modalDatePicker !== 'undefined' && modalDatePicker) {
        modalDatePicker.setDate(birthdate);
    }
    document.getElementById('modalSex').value = sex;
    document.getElementById('modalContact').value = contact;
    document.getElementById('modalAddress').value = homeAddress || '';
    document.getElementById('modalPhilHealth').value = philhealth;
    document.getElementById('modalPhilHealthId').value = philhealthId || '';
    document.getElementById('modalPhilHealthRelation').value = philhealthRelation || '';
    
    currentOriginalPhilHealthId = philhealthId || '';
    currentOriginalRelation = philhealthRelation || '';
    
    document.getElementById('editModal').classList.remove('hidden');
    togglePhilHealthId();
    if (philhealth === 'With PhilHealth Card' && philhealthId) {
        checkModalPhilHealthId();
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    currentEditId = null;
}

function getMatchedCategoriesAndExams(requestedStr) {
    if (!requestedStr || requestedStr.trim() === '' || 
        requestedStr.toLowerCase() === 'to be determined' || 
        requestedStr.toLowerCase() === 'not specified') {
        return { isRestricted: false, allowedCategories: [], allowedExams: [] };
    }

    const requestedItems = requestedStr.split(',').map(s => s.trim()).filter(Boolean);
    if (requestedItems.length === 0) {
        return { isRestricted: false, allowedCategories: [], allowedExams: [] };
    }

    const allowedCategoriesSet = new Set();
    const allowedExamsSet = new Set();
    const activeServices = window.allActiveServices || [];
    const examCategoryMap = window.examCategoryMap || {};
    const servicesByCategory = window.servicesByCategory || {};
    const bodyPartAliases = window.bodyPartAliases || {};

    requestedItems.forEach(item => {
        const itemLower = item.toLowerCase();
        let matchedSomething = false;

        // 1. Direct match with category name in DB (e.g. "Chest", "Abdomen", "Spine")
        Object.keys(servicesByCategory).forEach(cat => {
            if (cat.toLowerCase() === itemLower) {
                allowedCategoriesSet.add(cat);
                (servicesByCategory[cat] || []).forEach(exam => allowedExamsSet.add(exam));
                matchedSomething = true;
            }
        });

        // 2. Direct match with an active exam_type name (e.g. "Chest PA")
        if (examCategoryMap[item]) {
            const cat = examCategoryMap[item];
            allowedCategoriesSet.add(cat);
            allowedExamsSet.add(item);
            (servicesByCategory[cat] || []).forEach(exam => allowedExamsSet.add(exam));
            matchedSomething = true;
        }

        // 3. Match via bodyPartAliases
        if (bodyPartAliases[itemLower]) {
            const aliasTargets = bodyPartAliases[itemLower];
            aliasTargets.forEach(targetCat => {
                Object.keys(servicesByCategory).forEach(cat => {
                    if (cat.toLowerCase() === targetCat.toLowerCase() || 
                        cat.toLowerCase().includes(targetCat.toLowerCase()) || 
                        targetCat.toLowerCase().includes(cat.toLowerCase())) {
                        allowedCategoriesSet.add(cat);
                        (servicesByCategory[cat] || []).forEach(exam => allowedExamsSet.add(exam));
                        matchedSomething = true;
                    }
                });
            });
        }

        // 4. Fuzzy match against procedure names and categories
        activeServices.forEach(srv => {
            const srvNameLower = (srv.exam_type || srv.name || '').toLowerCase();
            const srvCatLower = (srv.category || '').toLowerCase();
            
            if (srvNameLower.includes(itemLower) || itemLower.includes(srvNameLower)) {
                allowedCategoriesSet.add(srv.category);
                allowedExamsSet.add(srv.exam_type || srv.name);
                matchedSomething = true;
            } else if (srvCatLower.includes(itemLower) || itemLower.includes(srvCatLower)) {
                allowedCategoriesSet.add(srv.category);
                (servicesByCategory[srv.category] || []).forEach(exam => allowedExamsSet.add(exam));
                matchedSomething = true;
            }
        });

        if (!matchedSomething) {
            allowedCategoriesSet.add(item);
        }
    });

    if (allowedExamsSet.size > 0) {
        return {
            isRestricted: true,
            allowedCategories: Array.from(allowedCategoriesSet),
            allowedExams: Array.from(allowedExamsSet)
        };
    }

    return {
        isRestricted: false,
        allowedCategories: Array.from(allowedCategoriesSet),
        allowedExams: []
    };
}


// ── Per-body-part helpers ──────────────────────────────────────────────────────

/**
 * Returns the allowed exam list for a SINGLE body part string.
 */
function getAllowedExamsForSinglePart(partStr) {
    const info = getMatchedCategoriesAndExams(partStr);
    return info.allowedExams.length > 0 ? info.allowedExams : (window.allActiveServices || []).map(s => s.exam_type || s.name);
}

/**
 * Escapes HTML special characters.
 */
function _escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/**
 * Builds the per-body-part inline selector section.
 * Returns an HTMLElement (div).
 */
function buildPartSection(part, allowedExams, preSelected, idx) {
    const section = document.createElement('div');
    section.className = 'assign-part-section rounded-xl border border-gray-200 bg-gray-50 p-3';
    section.setAttribute('data-part', part);

    // Header badge
    const header = document.createElement('div');
    header.className = 'flex items-center gap-2 mb-2';
    header.innerHTML = `
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold border border-indigo-200">
            ${_escHtml(part)}
        </span>
        <span class="text-xs text-gray-500 italic">Select exam(s) for this part</span>
    `;
    section.appendChild(header);

    // Chips container (selected items)
    const chipsWrap = document.createElement('div');
    chipsWrap.className = 'assign-part-chips flex flex-wrap gap-1.5 mb-2';
    section.appendChild(chipsWrap);

    // Search input wrapper (relative so dropdown can be absolute)
    const inputWrap = document.createElement('div');
    inputWrap.className = 'relative';
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'assign-part-search w-full rounded border border-gray-300 bg-white px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 placeholder-gray-400';
    searchInput.placeholder = 'Search & select exam...';
    searchInput.setAttribute('autocomplete', 'off');
    inputWrap.appendChild(searchInput);
    section.appendChild(inputWrap);

    // Dropdown list (absolute positioned inside inputWrap)
    const dropdown = document.createElement('div');
    dropdown.className = 'assign-part-dropdown hidden absolute left-0 right-0 z-[70] bg-white border border-gray-300 rounded shadow-lg max-h-44 overflow-y-auto mt-1 text-sm';
    inputWrap.appendChild(dropdown);

    const ul = document.createElement('ul');
    ul.className = 'py-1 m-0 list-none text-gray-800';
    allowedExams.forEach(exam => {
        const li = document.createElement('li');
        li.className = 'assign-part-option px-3 py-1.5 cursor-pointer hover:bg-indigo-600 hover:text-white transition-colors';
        li.setAttribute('data-value', exam);
        li.textContent = exam;
        ul.appendChild(li);
    });
    const noRes = document.createElement('li');
    noRes.className = 'assign-part-no-results hidden px-3 py-2 text-gray-400 italic text-xs';
    noRes.textContent = 'No matches found';
    ul.appendChild(noRes);
    dropdown.appendChild(ul);

    // Hidden selected state
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.className = 'assign-part-hidden';
    hiddenInput.setAttribute('data-part', part);
    hiddenInput.value = '';
    section.appendChild(hiddenInput);

    // Required indicator
    const reqMsg = document.createElement('p');
    reqMsg.className = 'assign-part-required-msg hidden text-xs text-red-600 mt-1 font-medium';
    reqMsg.textContent = `\u26A0 Please select at least one exam for "${part}"`;
    section.appendChild(reqMsg);

    // Pre-populate with already-selected exams matching this part's allowed list
    if (preSelected && preSelected.length > 0) {
        const matching = preSelected.filter(e => allowedExams.includes(e));
        if (matching.length > 0) {
            hiddenInput.value = matching.join(', ');
        }
    }

    function renderPartChips() {
        const vals = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()).filter(Boolean) : [];
        chipsWrap.innerHTML = '';
        vals.forEach(v => {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1 rounded border border-indigo-300 bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 shadow-sm';
            chip.innerHTML = `${_escHtml(v)} <button type="button" class="assign-part-remove-chip ml-0.5 text-indigo-400 hover:text-red-600 font-bold" data-value="${_escHtml(v)}" data-part="${_escHtml(part)}">&times;</button>`;
            chipsWrap.appendChild(chip);
        });
        // Hide already-selected options in dropdown
        ul.querySelectorAll('.assign-part-option').forEach(opt => {
            opt.classList.toggle('hidden', vals.includes(opt.getAttribute('data-value')));
        });
        updateAggregatedExamInput();
    }

    function filterDropdown(q) {
        const vals = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()).filter(Boolean) : [];
        let count = 0;
        ul.querySelectorAll('.assign-part-option').forEach(opt => {
            const v = opt.getAttribute('data-value');
            if (vals.includes(v)) { opt.classList.add('hidden'); return; }
            const match = !q || v.toLowerCase().includes(q.toLowerCase());
            opt.classList.toggle('hidden', !match);
            if (match) count++;
        });
        noRes.classList.toggle('hidden', count > 0);
    }

    searchInput.addEventListener('focus', () => {
        dropdown.classList.remove('hidden');
        filterDropdown(searchInput.value);
    });
    searchInput.addEventListener('input', () => {
        dropdown.classList.remove('hidden');
        filterDropdown(searchInput.value);
    });
    searchInput.addEventListener('blur', () => {
        setTimeout(() => dropdown.classList.add('hidden'), 200);
    });

    ul.addEventListener('mousedown', (e) => {
        const opt = e.target.closest('.assign-part-option');
        if (!opt) return;
        e.preventDefault();
        const val = opt.getAttribute('data-value');
        let vals = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()).filter(Boolean) : [];
        if (!vals.includes(val)) {
            vals.push(val);
            hiddenInput.value = vals.join(', ');
        }
        searchInput.value = '';
        renderPartChips();
        dropdown.classList.add('hidden');
    });

    renderPartChips();
    section._renderChips = renderPartChips;
    return section;
}

/**
 * Updates the aggregated hidden input with all selected exams across all part sections.
 */
function updateAggregatedExamInput() {
    const allSelected = [];
    document.querySelectorAll('#assignPerPartSections .assign-part-hidden').forEach(inp => {
        const vals = inp.value ? inp.value.split(',').map(s => s.trim()).filter(Boolean) : [];
        vals.forEach(v => { if (!allSelected.includes(v)) allSelected.push(v); });
    });
    const agg = document.getElementById('assignAggregatedExam');
    if (agg) agg.value = allSelected.join(', ');
}

// Chip removal via event delegation
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.assign-part-remove-chip');
    if (!btn) return;
    const val = btn.getAttribute('data-value');
    const part = btn.getAttribute('data-part');
    const section = document.querySelector('#assignPerPartSections .assign-part-section[data-part="' + CSS.escape(part) + '"]');
    if (!section) return;
    const hiddenInput = section.querySelector('.assign-part-hidden');
    if (!hiddenInput) return;
    let vals = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()).filter(Boolean) : [];
    vals = vals.filter(v => v !== val);
    hiddenInput.value = vals.join(', ');
    if (section._renderChips) section._renderChips();
});

function openAssignModal(id, requestedBodyPart, assignedExam) {
    if (assignedExam === undefined) assignedExam = '';
    document.getElementById('assignModal').classList.remove('hidden');

    const bodyPartEl = document.getElementById('assignBodyPart');
    const rawText = requestedBodyPart || 'Not specified';
    if (bodyPartEl) {
        bodyPartEl.innerText = rawText;
        bodyPartEl.setAttribute('data-raw', rawText);
    }

    const form = document.getElementById('assignForm');
    form.action = window.__APP__.basePath + '/patient-approval?action=assign_exam&id=' + id;

    const warningBox = document.getElementById('assignExamWarning');
    if (warningBox) warningBox.classList.add('hidden');

    // Build per-body-part sections
    const sectionsContainer = document.getElementById('assignPerPartSections');
    sectionsContainer.innerHTML = '';

    const preSelected = assignedExam ? assignedExam.split(',').map(s => s.trim()).filter(Boolean) : [];

    const isUnspecified = !requestedBodyPart || requestedBodyPart.trim() === '' ||
        requestedBodyPart.toLowerCase() === 'to be determined' ||
        requestedBodyPart.toLowerCase() === 'not specified';

    if (isUnspecified) {
        const allExams = (window.allActiveServices || []).map(s => s.exam_type || s.name);
        sectionsContainer.appendChild(buildPartSection('All / Unspecified', allExams, preSelected, 0));
    } else {
        const parts = requestedBodyPart.split(',').map(s => s.trim()).filter(Boolean);
        parts.forEach((part, idx) => {
            const allowed = getAllowedExamsForSinglePart(part);
            sectionsContainer.appendChild(buildPartSection(part, allowed, preSelected, idx));
        });
    }

    updateAggregatedExamInput();
    document.getElementById('assign_exam_price').value = '0';

    if (window.lucide) window.lucide.createIcons();
}

function checkLiveExamCategoryMatch() {
    // No-op kept for compatibility; per-part sections handle their own UI.
}

function validateAssignForm(e) {
    e.preventDefault();

    const sections = document.querySelectorAll('#assignPerPartSections .assign-part-section');
    let missingParts = [];

    sections.forEach(section => {
        const part = section.getAttribute('data-part');
        const hiddenInput = section.querySelector('.assign-part-hidden');
        const reqMsg = section.querySelector('.assign-part-required-msg');
        const vals = hiddenInput && hiddenInput.value
            ? hiddenInput.value.split(',').map(s => s.trim()).filter(Boolean)
            : [];

        if (vals.length === 0) {
            missingParts.push(part);
            if (reqMsg) reqMsg.classList.remove('hidden');
        } else {
            if (reqMsg) reqMsg.classList.add('hidden');
        }
    });

    if (missingParts.length > 0) {
        const partList = missingParts.map(p => `"${p}"`).join(', ');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Missing Exam Assignment',
                html: `Please select at least one exam procedure for each requested body part.<br><br><strong>Missing:</strong> ${partList}`,
                customClass: { popup: 'rounded-3xl border-0 shadow-2xl' }
            });
        } else {
            alert(`Please select at least one exam for: ${partList}`);
        }
        return false;
    }

    updateAggregatedExamInput();

    const aggInput = document.getElementById('assignAggregatedExam');
    if (!aggInput || !aggInput.value.trim()) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'No Exam Selected', text: 'Please select at least one examination procedure.', customClass: { popup: 'rounded-3xl border-0 shadow-2xl' } });
        } else {
            alert('Please select at least one examination procedure.');
        }
        return false;
    }

    const form = document.getElementById('assignForm');

    const doSubmit = function() {
        closeAssignModal();
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Processing...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        }
        form.submit();
    };

    if (typeof confirmAction === 'function') {
        confirmAction('Confirm Assignment', 'Are you sure you want to assign the selected examination(s) and request payment from the patient?', doSubmit);
    } else {
        if (confirm('Are you sure you want to assign the selected examination(s) and request payment?')) {
            doSubmit();
        }
    }
    return false;
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    // Legacy assign select (unused but kept for compatibility)
    const assignSelect = document.getElementById('assign_exam_select');
    const assignPriceInput = document.getElementById('assign_exam_price');
    if (assignSelect) {
        assignSelect.addEventListener('change', function() {
            const selectedOption = assignSelect.options[assignSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.price) {
                assignPriceInput.value = selectedOption.dataset.price;
            }
        });
    }
});

function togglePhilHealthId() {
    const status = document.getElementById('modalPhilHealth').value;
    const idField = document.getElementById('philHealthIdField');
    const idInput = document.getElementById('modalPhilHealthId');
    const relSelect = document.getElementById('modalPhilHealthRelation');
    if (status === 'With PhilHealth Card') {
        idField.classList.remove('hidden');
        relSelect.required = true;
    } else {
        idField.classList.add('hidden');
        idInput.value = '';
        idInput.setCustomValidity('');
        relSelect.value = '';
        relSelect.required = false;
    }
}

let modalPhCheckTimer = null;
function checkModalPhilHealthId() {
    clearTimeout(modalPhCheckTimer);
    const idInput = document.getElementById('modalPhilHealthId');
    const msg = document.getElementById('modal-philhealth-status-msg');
    const optOwner = document.getElementById('modal-opt-owner');
    const optFamily = document.getElementById('modal-opt-family');
    const relSelect = document.getElementById('modalPhilHealthRelation');
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

    modalPhCheckTimer = setTimeout(() => {
        // Exclude current request so we know if it's used by SOMEONE ELSE
        // (Use a global variable that tracks the currently editing request ID, if one exists)
        const reqIdParam = window.currentEditingPatientId ? `&exclude_request_id=${window.currentEditingPatientId}` : '';

        fetch(window.__APP__.basePath + `/app/api/check_philhealth.php?philhealth_id=${encodeURIComponent(idValue)}${reqIdParam}&t=${new Date().getTime()}`, { cache: 'no-store' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (idInput.value !== idValue) return; // Prevent race conditions

                    const isSameId = (idValue === currentOriginalPhilHealthId);

                    if (data.owner_used) {
                        if (isSameId && currentOriginalRelation === 'Principal Member' && !data.owner_used_by_other) {
                            optOwner.disabled = false;
                        } else {
                            optOwner.disabled = true;
                            if (relSelect.value === 'Principal Member') relSelect.value = '';
                        }
                        optOwner.innerText = `Principal Member - Used on ${data.owner_used_date}`;
                    }
                    if (data.family_used) {
                        if (isSameId && currentOriginalRelation === 'Qualified Dependent' && !data.family_used_by_other) {
                            optFamily.disabled = false;
                        } else {
                            optFamily.disabled = true;
                            if (relSelect.value === 'Qualified Dependent') relSelect.value = '';
                        }
                        optFamily.innerText = `Qualified Dependent - Used on ${data.family_used_date}`;
                    }
                    
                    if (data.owner_used && data.family_used) {
                        if (!isSameId) {
                            idInput.setCustomValidity("This PhilHealth ID is already fully utilized.");
                        }
                        msg.innerText = "This PhilHealth ID is already fully utilized.";
                        msg.classList.remove('hidden');
                    }
                }
            })
            .catch(err => console.error("Error checking PhilHealth ID:", err));
    }, 500);
}

function formatPhilHealthInput(input) {
    // Strip everything except digits
    let digits = input.value.replace(/\D/g, '');
    // Limit to 12 digits total (2 + 9 + 1)
    digits = digits.slice(0, 12);
    // Build XX-XXXXXXXXX-X
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

function saveEditModal() {
    const name = document.getElementById('modalName').value;
    const birthdate = document.getElementById('modalBirthdate').value;
    const sex = document.getElementById('modalSex').value;
    const contact = document.getElementById('modalContact').value;
    const homeAddress = document.getElementById('modalAddress').value;
    const philhealth = document.getElementById('modalPhilHealth').value;
    const philhealthId = document.getElementById('modalPhilHealthId').value;
    const philhealthRelation = document.getElementById('modalPhilHealthRelation').value;

    if (!name || !birthdate || !sex || !contact) {
        toast('Please fill in all required fields', 'error');
        return;
    }

    const idInput = document.getElementById('modalPhilHealthId');
    if (philhealth === 'With PhilHealth Card') {
        const philHealthPattern = /^\d{2}-\d{9}-\d{1}$/;
        if (!philhealthId.trim()) {
            idInput.setCustomValidity('PhilHealth ID Number is required.');
            idInput.reportValidity();
            idInput.addEventListener('input', () => idInput.setCustomValidity(''), { once: true });
            return;
        } else if (!philHealthPattern.test(philhealthId.trim())) {
            idInput.setCustomValidity('Format must be XX-XXXXXXXXX-X (digits only).');
            idInput.reportValidity();
            idInput.addEventListener('input', () => idInput.setCustomValidity(''), { once: true });
            return;
        }
        
        const relInput = document.getElementById('modalPhilHealthRelation');
        if (!philhealthRelation) {
            relInput.setCustomValidity('Relation is required.');
            relInput.reportValidity();
            relInput.addEventListener('change', () => relInput.setCustomValidity(''), { once: true });
            return;
        }
    }

    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.__APP__.basePath + '/config/update_patient.php';

    const inputs = [
        { name: 'id', value: currentEditId },
        { name: 'name', value: name },
        { name: 'birthdate', value: birthdate },
        { name: 'sex', value: sex },
        { name: 'contact', value: contact },
        { name: 'home_address', value: homeAddress },
        { name: 'philhealth', value: philhealth },
        { name: 'philhealth_id', value: philhealthId, required: true },
        { name: 'philhealth_relation', value: philhealthRelation, required: true }
    ];

    inputs.forEach(input => {
        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = input.name;
        field.value = input.value;
        if (input.required) {
            field.required = true;
        }
        form.appendChild(field);
    });

    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('input', (e) => {
    if (e.target && (e.target.id === 'search-input' || e.target.id === 'filter-priority' || e.target.id === 'sort-date' || e.target.id === 'filter-status')) {
        applyFilters();
    }
});

document.addEventListener('change', (e) => {
    if (e.target && (e.target.id === 'filter-priority' || e.target.id === 'sort-date' || e.target.id === 'filter-status')) {
        applyFilters();
    }
});

function applyFilters() {
    const search = (document.getElementById('search-input')?.value || '').toLowerCase();
    const sort = document.getElementById('sort-date')?.value || 'Newest Request';
    const filterStatus = document.getElementById('filter-status')?.value || 'All';

    const tbody = document.getElementById('table-body');
    if (!tbody) return;

    let rows = Array.from(tbody.querySelectorAll('tr.record-row'));
    let visibleCount = 0;

    // Sort
    if (sort === 'Newest Request' || sort === 'Oldest Request') {
        rows.sort((a, b) => {
            const dateA = new Date(a.dataset.date).getTime();
            const dateB = new Date(b.dataset.date).getTime();
            return sort === 'Newest Request' ? dateB - dateA : dateA - dateB;
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    let matchedRows = [];

    // Filter
    rows.forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const id = (row.dataset.id || '').toLowerCase();
        const statusSpan = row.querySelector('td:nth-child(6) span');
        const status = statusSpan ? statusSpan.textContent.trim() : '';

        const matchSearch = name.includes(search) || id.includes(search);
        const matchStatus = filterStatus === 'All' || status === filterStatus;

        if (matchSearch && matchStatus) {
            matchedRows.push(row);
        } else {
            row.style.display = 'none';
        }
    });

    visibleCount = matchedRows.length;
    
    // Pagination Logic
    const itemsPerPage = 8;
    let totalPages = Math.ceil(visibleCount / itemsPerPage);
    if (window.currentApprovalPage === undefined) window.currentApprovalPage = 1;
    if (window.currentApprovalPage > totalPages && totalPages > 0) window.currentApprovalPage = totalPages;
    if (totalPages === 0) window.currentApprovalPage = 1;

    const start = (window.currentApprovalPage - 1) * itemsPerPage;
    const end = Math.min(start + itemsPerPage, visibleCount);

    matchedRows.forEach((row, idx) => {
        row.style.display = (idx >= start && idx < end) ? '' : 'none';
    });

    // Render Pagination Controls
    renderPaginationControls(totalPages, visibleCount, start, end);

    let emptyMsg = document.getElementById('empty-msg-row');
    if (visibleCount === 0 && rows.length > 0) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('tr');
            emptyMsg.id = 'empty-msg-row';
            emptyMsg.innerHTML = `<td colspan="10" class="text-center py-8 text-gray-500">No records match your filters.</td>`;
            tbody.appendChild(emptyMsg);
        } else {
            emptyMsg.style.display = '';
        }
    } else if (emptyMsg) {
        emptyMsg.style.display = 'none';
    }
}

function renderPaginationControls(totalPages, totalRecords, startIdx, endIdx) {
    const container = document.getElementById('approval-pagination-container');
    const controls = document.getElementById('approval-pagination-controls');
    const startSpan = document.getElementById('approval-start');
    const endSpan = document.getElementById('approval-end');
    const totalSpan = document.getElementById('approval-total');
    
    if (!container || !controls) return;
    
    container.style.display = 'flex';
    controls.innerHTML = '';
    
    startSpan.innerText = totalRecords > 0 ? startIdx + 1 : 0;
    endSpan.innerText = endIdx;
    totalSpan.innerText = totalRecords;
    
    function createButton(label, page, disabled, isActive = false) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.innerHTML = label;
        
        if (isActive) {
            btn.className = "px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600";
        } else {
            btn.className = "px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed shadow-sm";
        }
        
        if (disabled) {
            btn.disabled = true;
        } else {
            btn.onclick = () => {
                window.currentApprovalPage = page;
                applyFilters();
                // scroll to top of table
                const tableContainer = document.querySelector('.max-h-\\[400px\\]');
                if (tableContainer) tableContainer.scrollTo({ top: 0, behavior: 'smooth' });
            };
        }
        return btn;
    }
    
    function createEllipsis() {
        const span = document.createElement('span');
        span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
        span.innerHTML = "...";
        return span;
    }
    
    controls.appendChild(createButton('&lsaquo; Back', window.currentApprovalPage - 1, window.currentApprovalPage === 1));
    
    if (totalPages <= 5) {
        for (let i = 1; i <= totalPages; i++) {
            controls.appendChild(createButton(i, i, false, i === window.currentApprovalPage));
        }
    } else {
        controls.appendChild(createButton(1, 1, false, 1 === window.currentApprovalPage));
        if (window.currentApprovalPage > 3) controls.appendChild(createEllipsis());
        
        let startPage = Math.max(2, window.currentApprovalPage - 1);
        let endPage = Math.min(totalPages - 1, window.currentApprovalPage + 1);
        
        if (window.currentApprovalPage === 1) endPage = 3;
        if (window.currentApprovalPage === totalPages) startPage = totalPages - 2;
        
        for (let i = startPage; i <= endPage; i++) {
            controls.appendChild(createButton(i, i, false, i === window.currentApprovalPage));
        }
        
        if (window.currentApprovalPage < totalPages - 2) controls.appendChild(createEllipsis());
        controls.appendChild(createButton(totalPages, totalPages, false, totalPages === window.currentApprovalPage));
    }
    
    controls.appendChild(createButton('Next &rsaquo;', window.currentApprovalPage + 1, window.currentApprovalPage >= totalPages));
}

// Initial sorting on load and re-applying filters after real-time updates
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        applyFilters();
    }, 100);
});

// Re-apply filters when real-time polling updates the table content
document.addEventListener('realtime:updated', () => {
    applyFilters();
});


