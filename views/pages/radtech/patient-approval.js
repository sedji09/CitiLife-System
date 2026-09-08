let currentEditId = null;
let currentOriginalPhilHealthId = '';
let currentOriginalRelation = '';
window.currentEditingPatientId = null;

function setModalInputsDisabled(disabled) {
    if (document.getElementById('modalName')) document.getElementById('modalName').disabled = disabled;
    if (document.getElementById('modalBirthdate')) document.getElementById('modalBirthdate').disabled = disabled;
    if (document.getElementById('modalSex')) document.getElementById('modalSex').disabled = disabled;
    if (document.getElementById('modalContact')) document.getElementById('modalContact').disabled = disabled;
    if (document.getElementById('modalAddress')) document.getElementById('modalAddress').disabled = disabled;
    if (document.getElementById('modalPhilHealth')) document.getElementById('modalPhilHealth').disabled = disabled;
    if (document.getElementById('modalPhilHealthId')) document.getElementById('modalPhilHealthId').disabled = disabled;
    if (document.getElementById('modalPhilHealthRelation')) document.getElementById('modalPhilHealthRelation').disabled = disabled;

    const okBtn = document.getElementById('modalOkBtn');
    if (okBtn) {
        okBtn.style.display = disabled ? 'none' : 'block';
    }
    const cancelBtn = document.getElementById('modalCancelBtn');
    if (cancelBtn) {
        cancelBtn.innerText = disabled ? 'Close' : 'Cancel';
    }
}

function openEditModal(id, name, birthdate, sex, contact, homeAddress, philhealth = '', philhealthId = '', philhealthRelation = '') {
    window.currentEditingPatientId = id;
    currentEditId = id;
    setModalInputsDisabled(false);
    if (document.getElementById('modalName')) document.getElementById('modalName').value = name;
    // Set the datepicker date (use the picker if available, fallback to direct value)
    const modalBirthdateInput = document.getElementById('modalBirthdate');
    if (modalBirthdateInput) {
        modalBirthdateInput.value = birthdate;
        if (typeof modalDatePicker !== 'undefined' && modalDatePicker) {
            modalDatePicker.setDate(birthdate);
        }
    }
    if (document.getElementById('modalSex')) document.getElementById('modalSex').value = sex;
    if (document.getElementById('modalContact')) document.getElementById('modalContact').value = contact;
    if (document.getElementById('modalAddress')) document.getElementById('modalAddress').value = homeAddress || '';
    if (document.getElementById('modalPhilHealth')) document.getElementById('modalPhilHealth').value = philhealth;
    if (document.getElementById('modalPhilHealthId')) document.getElementById('modalPhilHealthId').value = philhealthId || '';
    if (document.getElementById('modalPhilHealthRelation')) document.getElementById('modalPhilHealthRelation').value = philhealthRelation || '';
    
    currentOriginalPhilHealthId = philhealthId || '';
    currentOriginalRelation = philhealthRelation || '';
    
    const editModal = document.getElementById('editModal');
    if (editModal) editModal.classList.remove('hidden');
    if (document.getElementById('modalPhilHealth')) {
        togglePhilHealthId();
        if (philhealth === 'With PhilHealth Card' && philhealthId) {
            checkModalPhilHealthId();
        }
    }
}

function openViewModal(id, name, birthdate, sex, contact, homeAddress, philhealth = '', philhealthId = '', philhealthRelation = '') {
    currentEditId = id;
    setModalInputsDisabled(true);
    if (document.getElementById('modalName')) document.getElementById('modalName').value = name;
    // Set the datepicker date (use the picker if available, fallback to direct value)
    const modalBirthdateInput = document.getElementById('modalBirthdate');
    if (modalBirthdateInput) {
        modalBirthdateInput.value = birthdate;
        if (typeof modalDatePicker !== 'undefined' && modalDatePicker) {
            modalDatePicker.setDate(birthdate);
        }
    }
    if (document.getElementById('modalSex')) document.getElementById('modalSex').value = sex;
    if (document.getElementById('modalContact')) document.getElementById('modalContact').value = contact;
    if (document.getElementById('modalAddress')) document.getElementById('modalAddress').value = homeAddress || '';
    if (document.getElementById('modalPhilHealth')) document.getElementById('modalPhilHealth').value = philhealth;
    if (document.getElementById('modalPhilHealthId')) document.getElementById('modalPhilHealthId').value = philhealthId || '';
    if (document.getElementById('modalPhilHealthRelation')) document.getElementById('modalPhilHealthRelation').value = philhealthRelation || '';
    
    currentOriginalPhilHealthId = philhealthId || '';
    currentOriginalRelation = philhealthRelation || '';
    
    const editModal = document.getElementById('editModal');
    if (editModal) editModal.classList.remove('hidden');
    if (document.getElementById('modalPhilHealth')) {
        togglePhilHealthId();
        if (philhealth === 'With PhilHealth Card' && philhealthId) {
            checkModalPhilHealthId();
        }
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


// ── Single Dropdown Multi-Select & Per-Body-Part Validation Helpers ───────────

/**
 * Returns the allowed exam list for a SINGLE body part string.
 */
function getAllowedExamsForSinglePart(partStr) {
    const info = getMatchedCategoriesAndExams(partStr);
    return info.allowedExams.length > 0 ? info.allowedExams : (window.allActiveServices || []).map(s => s.exam_type || s.name);
}

/**
 * Filters the single exam-selector dropdown to only display options matching
 * ANY of the patient's requested body parts.
 */
function filterAssignModalExams(requestedBodyPart) {
    const modal = document.getElementById('assignModal');
    if (!modal) return;

    const container = modal.querySelector('.exam-ms-component');
    if (!container) return;

    const matchInfo = getMatchedCategoriesAndExams(requestedBodyPart);
    const dropdown = container.querySelector('.exam-ms-dropdown');
    const options = dropdown ? dropdown.querySelectorAll('.exam-ms-option') : [];
    const searchInput = container.querySelector('.exam-ms-input');
    const noResults = dropdown ? dropdown.querySelector('.exam-ms-no-results') : null;
    const badge = document.getElementById('assignAllowedBadge');
    const badgeText = document.getElementById('assignAllowedBadgeText');

    if (matchInfo.isRestricted && matchInfo.allowedExams.length > 0) {
        // Restrict options in dropdown to only allowed exams across all requested body parts
        options.forEach(opt => {
            const val = opt.getAttribute('data-value');
            if (matchInfo.allowedExams.includes(val)) {
                opt.setAttribute('data-allowed', 'true');
            } else {
                opt.setAttribute('data-allowed', 'false');
            }
        });

        const catNames = matchInfo.allowedCategories.join(', ');
        if (noResults) {
            noResults.textContent = `No matches found (Only ${catNames} exams allowed)`;
        }
        if (searchInput) {
            const ph = `Select ${matchInfo.allowedCategories.join(' / ')} procedure(s)...`;
            searchInput.placeholder = ph;
            searchInput.setAttribute('data-placeholder', ph);
        }
        if (badge && badgeText) {
            badge.classList.remove('hidden');
            badgeText.innerHTML = `Choices filtered to <strong>${catNames}</strong> procedures only`;
            if (window.lucide) window.lucide.createIcons();
        }
    } else {
        // Unrestricted (show all active options)
        options.forEach(opt => {
            opt.setAttribute('data-allowed', 'true');
        });
        if (noResults) {
            noResults.textContent = 'No matches found';
        }
        if (searchInput) {
            searchInput.placeholder = 'Select procedure(s)...';
            searchInput.setAttribute('data-placeholder', 'Select procedure(s)...');
        }
        if (badge) {
            badge.classList.add('hidden');
        }
    }

    // Trigger renderChips to update option visibility in the dropdown
    if (typeof renderChips === 'function') {
        renderChips(container);
    }
}

/**
 * Live validation during chip add/remove in the single dropdown.
 * Checks if all requested body parts have at least one exam selected.
 */
function checkLiveExamCategoryMatch() {
    const warningBox = document.getElementById('assignExamWarning');
    const warningText = document.getElementById('assignExamWarningText');
    if (!warningBox || !warningText) return;

    const requestedStr = document.getElementById('assignBodyPart')
        ? document.getElementById('assignBodyPart').getAttribute('data-raw') || ''
        : '';

    if (!requestedStr || requestedStr.trim() === '' ||
        requestedStr.toLowerCase() === 'to be determined' ||
        requestedStr.toLowerCase() === 'not specified') {
        warningBox.classList.add('hidden');
        return;
    }

    const modal = document.getElementById('assignModal');
    const hiddenInput = modal ? modal.querySelector('.exam-ms-hidden-input') : null;
    const assignedStr = hiddenInput ? hiddenInput.value : '';
    const assignedExams = assignedStr.split(',').map(s => s.trim()).filter(Boolean);

    const requestedParts = requestedStr.split(',').map(s => s.trim()).filter(Boolean);
    if (requestedParts.length <= 1 || assignedExams.length === 0) {
        warningBox.classList.add('hidden');
        return;
    }

    // Check which parts are covered and which are still missing
    const missingParts = [];
    const coveredParts = [];

    requestedParts.forEach(part => {
        const allowedForPart = getAllowedExamsForSinglePart(part);
        if (allowedForPart.length > 0) {
            const hasMatch = assignedExams.some(exam => allowedForPart.includes(exam));
            if (hasMatch) {
                coveredParts.push(part);
            } else {
                missingParts.push(part);
            }
        }
    });

    if (missingParts.length > 0 && coveredParts.length > 0) {
        warningText.innerHTML = `<strong>Reminder:</strong> You have selected exams for <em>${coveredParts.join(', ')}</em>. Please also select at least one procedure for <strong>${missingParts.join(', ')}</strong>.`;
        warningBox.classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    } else {
        warningBox.classList.add('hidden');
    }
}

function getAppBasePath() {
    let bp = window.__APP__?.basePath || '';
    if (!bp) return '/';
    return bp.endsWith('/') ? bp : bp + '/';
}

window.currentAssignRequestId = null;
window.currentAssignPatientId = null;
window.currentAssignPatientName = '';
let assignPhilHealthCheckTimeout = null;

function toggleAssignPhilHealth(isWithCard) {
    const withRadio = document.getElementById('assign_ph_with');
    const withoutRadio = document.getElementById('assign_ph_without');
    const detailsBox = document.getElementById('assign_philhealth_details');
    const idInput = document.getElementById('assign_philhealth_id');
    const relSelect = document.getElementById('assign_philhealth_relation');

    if (withRadio && withoutRadio) {
        withRadio.checked = isWithCard;
        withoutRadio.checked = !isWithCard;
    }

    if (detailsBox) {
        if (isWithCard) {
            detailsBox.classList.remove('hidden');
            if (idInput) {
                idInput.required = true;
                idInput.setAttribute('data-required', 'true');
            }
            if (relSelect) {
                relSelect.required = true;
                relSelect.setAttribute('data-required', 'true');
            }
            checkAssignPhilHealthDup();
        } else {
            detailsBox.classList.add('hidden');
            if (idInput) {
                idInput.required = false;
                idInput.removeAttribute('data-required');
                idInput.setCustomValidity('');
                if (window.FormValidator) window.FormValidator.clearError(idInput);
            }
            if (relSelect) {
                relSelect.required = false;
                relSelect.removeAttribute('data-required');
                relSelect.disabled = false;
                if (window.FormValidator) window.FormValidator.clearError(relSelect);
            }
        }
    }
    recalculateAssignPricing();
}

function recalculateAssignPricing() {
    const form = document.getElementById('assignForm');
    const hiddenInput = form ? form.querySelector('.exam-ms-hidden-input') : null;
    const assignedStr = hiddenInput ? hiddenInput.value : '';
    const assignedExams = assignedStr.split(',').map(s => s.trim()).filter(Boolean);

    const isWithCard = document.getElementById('assign_ph_with')?.checked || false;
    const relSelect = document.getElementById('assign_philhealth_relation');
    const selectedOpt = relSelect ? relSelect.selectedOptions[0] : null;
    // PhilHealth discount is only applied if With Card is selected AND a valid, non-disabled relation is selected
    const isRelValid = isWithCard && relSelect && relSelect.value && (!selectedOpt || !selectedOpt.disabled);

    const allServices = window.allActiveServices || [];

    let originalPrice = 0.00;
    let philhealthDiscount = 0.00;

    assignedExams.forEach(examName => {
        const srv = allServices.find(s => s.exam_type.toLowerCase() === examName.toLowerCase());
        if (srv) {
            const price = parseFloat(srv.price) || 0.00;
            originalPrice += price;

            if (isRelValid && (parseInt(srv.is_philhealth_covered) === 1 || srv.is_philhealth_covered === true)) {
                const discount = parseFloat(srv.philhealth_discount) || 0.00;
                philhealthDiscount += Math.min(discount, price);
            }
        }
    });

    const amountDue = Math.max(0.00, originalPrice - philhealthDiscount);

    const origEl = document.getElementById('assign_calc_original_price');
    const discEl = document.getElementById('assign_calc_discount');
    const dueEl = document.getElementById('assign_calc_amount_due');
    const hiddenPrice = document.getElementById('assign_exam_price');

    if (origEl) origEl.textContent = '₱' + originalPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (discEl) discEl.textContent = (philhealthDiscount > 0 ? '-₱' : '₱') + philhealthDiscount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (dueEl) dueEl.textContent = '₱' + amountDue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (hiddenPrice) hiddenPrice.value = amountDue.toFixed(2);
}

function checkAssignPhilHealthDup(immediate = false) {
    const idInput = document.getElementById('assign_philhealth_id');
    const msgEl = document.getElementById('assign-philhealth-msg');
    const ownerOpt = document.getElementById('assign-opt-owner');
    const familyOpt = document.getElementById('assign-opt-family');
    const relSelect = document.getElementById('assign_philhealth_relation');
    const reqId = window.currentAssignRequestId || 0;
    const patId = window.currentAssignPatientId || 0;

    if (!idInput) return;
    const val = idInput.value.trim();
    const cleanDigits = val.replace(/\D/g, '');

    if (cleanDigits.length < 12) {
        if (msgEl) {
            msgEl.classList.add('hidden');
            msgEl.innerHTML = '';
        }
        if (ownerOpt) {
            ownerOpt.disabled = false;
            ownerOpt.innerText = 'Principal Member';
        }
        if (familyOpt) {
            familyOpt.disabled = false;
            familyOpt.innerText = 'Qualified Dependent';
        }
        if (relSelect) {
            relSelect.disabled = false;
        }
        recalculateAssignPricing();
        return;
    }

    const performCheck = () => {
        const basePath = getAppBasePath();
        const url = `${basePath}app/Api/check_philhealth.php?philhealth_id=${encodeURIComponent(val)}&exclude_request_id=${reqId}&patient_id=${patId}&t=${Date.now()}`;
        fetch(url, { cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                if (idInput.value.trim() !== val) return;

                const ownerUsed = Boolean(data.owner_used_by_other);
                const familyUsed = Boolean(data.family_used_by_other);
                const familyBlockedForOwner = Boolean(data.family_blocked_for_owner);

                if (ownerOpt) {
                    ownerOpt.disabled = ownerUsed;
                    ownerOpt.innerText = ownerUsed
                        ? `Principal Member - Unavailable (Already used ${data.owner_used_date || ''})`
                        : 'Principal Member (Available)';
                }

                if (familyOpt) {
                    if (familyBlockedForOwner) {
                        familyOpt.disabled = true;
                        familyOpt.innerText = 'Qualified Dependent - Unavailable (Not allowed for Cardholder)';
                    } else if (familyUsed) {
                        familyOpt.disabled = true;
                        familyOpt.innerText = `Qualified Dependent - Unavailable (Already used ${data.family_used_date || ''})`;
                    } else {
                        familyOpt.disabled = false;
                        familyOpt.innerText = 'Qualified Dependent (Available for Family Member)';
                    }
                }

                // If currently selected relation is disabled, reset selection back to unselected
                if (relSelect) {
                    const curVal = relSelect.value;
                    if (curVal === 'Principal Member' && ownerUsed) {
                        relSelect.selectedIndex = 0;
                        relSelect.value = '';
                    } else if (curVal === 'Qualified Dependent' && (familyBlockedForOwner || familyUsed)) {
                        relSelect.selectedIndex = 0;
                        relSelect.value = '';
                    }
                }

                // If both options are disabled, disable the entire dropdown
                if (ownerOpt && familyOpt && relSelect) {
                    if (ownerOpt.disabled && familyOpt.disabled) {
                        relSelect.selectedIndex = 0;
                        relSelect.value = '';
                        relSelect.disabled = true;
                    } else {
                        relSelect.disabled = false;
                    }
                }

                if (msgEl) {
                    if (familyBlockedForOwner) {
                        msgEl.innerHTML = `
                            <p class="text-xs text-red-600 font-medium leading-normal">
                                The patient (<strong>${escapeHtmlApproval(data.current_patient_name || window.currentAssignPatientName || 'this patient')}</strong>) is registered as the Principal Member of this PhilHealth card and cannot be registered as a Qualified Dependent. Please select <strong>"Without PhilHealth"</strong> above to proceed.
                            </p>`;
                        msgEl.classList.remove('hidden');
                    } else if (ownerUsed && familyUsed) {
                        msgEl.innerHTML = `
                            <p class="text-xs text-red-600 font-medium leading-normal">
                                Both Principal Member and Qualified Dependent coverage have already been registered for this PhilHealth ID. Please select "Without PhilHealth" or provide another ID.
                            </p>`;
                        msgEl.classList.remove('hidden');
                    } else if (ownerUsed) {
                        msgEl.innerHTML = `
                            <div class="text-xs leading-normal space-y-0.5">
                                <p class="text-red-600 font-medium">Principal Member is already registered to ${escapeHtmlApproval(data.owner_patient_name || 'prior patient')} (${data.owner_used_date || 'Used'}).</p>
                                <p class="text-emerald-700 font-semibold">Qualified Dependent is available for family members.</p>
                            </div>`;
                        msgEl.classList.remove('hidden');
                    } else if (familyUsed) {
                        msgEl.innerHTML = `
                            <div class="text-xs leading-normal space-y-0.5">
                                <p class="text-red-600 font-medium">Qualified Dependent is already registered (${data.family_used_date || 'Used'}).</p>
                                <p class="text-emerald-700 font-semibold">Principal Member is still available for the cardholder.</p>
                            </div>`;
                        msgEl.classList.remove('hidden');
                    } else {
                        msgEl.classList.add('hidden');
                        msgEl.innerHTML = '';
                    }
                }
                recalculateAssignPricing();
            })
            .catch(err => console.error("Error checking PhilHealth ID:", err));
    };

    clearTimeout(assignPhilHealthCheckTimeout);
    if (immediate) {
        performCheck();
    } else {
        assignPhilHealthCheckTimeout = setTimeout(performCheck, 200);
    }
}

function closeAssignModal() {
    const modal = document.getElementById('assignModal');
    if (modal) {
        modal.classList.add('hidden');
        const form = document.getElementById('assignForm');
        if (form) {
            const examContainer = form.querySelector('.exam-ms-component');
            if (examContainer) {
                examContainer.removeAttribute('data-readonly');
                const searchInput = examContainer.querySelector('.exam-ms-input');
                if (searchInput) searchInput.style.display = '';
            }
        }
    }
    window.currentAssignRequestId = null;
    window.currentAssignPatientId = null;
    window.currentAssignPatientName = '';
}

function openAssignModal(id, requestedBodyPart, assignedExam = '', philhealthStatus = '', philhealthId = '', philhealthRelation = '', patientId = 0, patientName = '', isReadOnly = false) {
    window.currentAssignRequestId = id;
    window.currentAssignPatientId = patientId || 0;
    window.currentAssignPatientName = patientName || '';
    document.getElementById('assignModal').classList.remove('hidden');

    const bodyPartEl = document.getElementById('assignBodyPart');
    const rawText = requestedBodyPart || 'Not specified';
    if (bodyPartEl) {
        bodyPartEl.innerText = rawText;
        bodyPartEl.setAttribute('data-raw', rawText);
    }

    const form = document.getElementById('assignForm');
    form.action = window.__APP__.basePath + '/patient-approval?action=assign_exam&id=' + id;

    const examContainer = form ? form.querySelector('.exam-ms-component') : null;
    const searchInput = examContainer ? examContainer.querySelector('.exam-ms-input') : null;
    const msBox = examContainer ? examContainer.querySelector('.exam-ms-box') : null;

    if (isReadOnly) {
        if (examContainer) {
            examContainer.setAttribute('data-readonly', 'true');
        }
    } else {
        if (examContainer) {
            examContainer.removeAttribute('data-readonly');
        }
        // Filter choices inside the single dropdown to match all requested body parts
        filterAssignModalExams(requestedBodyPart);
    }

    // Set or reset selected exams in the single multi-select component
    const hiddenInput = form ? form.querySelector('.exam-ms-hidden-input') : null;
    if (hiddenInput) {
        hiddenInput.value = assignedExam || '';
        if (examContainer && typeof renderChips === 'function') {
            renderChips(examContainer);
        }
    }

    // Reset relation dropdown options state
    const ownerOpt = document.getElementById('assign-opt-owner');
    const familyOpt = document.getElementById('assign-opt-family');
    const msgEl = document.getElementById('assign-philhealth-msg');
    const relSelect = document.getElementById('assign_philhealth_relation');
    if (ownerOpt) {
        ownerOpt.disabled = false;
        ownerOpt.innerText = 'Principal Member';
    }
    if (familyOpt) {
        familyOpt.disabled = false;
        familyOpt.innerText = 'Qualified Dependent';
    }
    if (msgEl) {
        msgEl.classList.add('hidden');
        msgEl.innerHTML = '';
    }
    if (relSelect) {
        relSelect.disabled = false;
    }

    // Set PhilHealth values
    const hasCard = (philhealthStatus === 'With PhilHealth Card');
    const idInput = document.getElementById('assign_philhealth_id');
    if (idInput) idInput.value = philhealthId || '';
    if (relSelect) relSelect.value = philhealthRelation || '';

    toggleAssignPhilHealth(hasCard);
    checkLiveExamCategoryMatch();
    recalculateAssignPricing();

    // Configure Read-Only vs Editable mode UI elements
    const modalTitle = document.getElementById('assignModalTitle');
    const modalSubtitle = document.getElementById('assignModalSubtitle');
    const modalIconBox = document.getElementById('assignModalHeaderIcon');
    const examLabel = document.getElementById('assignExamLabel');
    const requiredAsterisk = document.getElementById('assignExamRequiredAsterisk');
    const submitBtn = document.getElementById('assignSubmitBtn');
    const cancelBtn = document.getElementById('assignCancelBtn');
    const badge = document.getElementById('assignAllowedBadge');
    const warningBox = document.getElementById('assignExamWarning');

    const phWithout = document.getElementById('assign_ph_without');
    const phWith = document.getElementById('assign_ph_with');

    if (isReadOnly) {
        if (modalTitle) modalTitle.innerText = 'Assigned Examination Details';
        if (modalSubtitle) modalSubtitle.innerText = 'Assigned procedure(s) and PhilHealth coverage summary (Read-Only)';
        if (modalIconBox) {
            modalIconBox.className = 'bg-blue-100 text-blue-600 p-2.5 rounded-lg border border-blue-200';
            modalIconBox.innerHTML = '<i data-lucide="clipboard-check" class="w-6 h-6"></i>';
        }
        if (examLabel) examLabel.innerText = 'Assigned Examination Procedure(s)';
        if (requiredAsterisk) requiredAsterisk.classList.add('hidden');
        if (submitBtn) submitBtn.style.display = 'none';
        if (cancelBtn) {
            cancelBtn.innerText = 'Close';
            cancelBtn.className = 'px-5 py-2 bg-gray-600 text-white text-xs font-semibold rounded-xl hover:bg-gray-700 transition cursor-pointer shadow-sm';
        }
        if (badge) badge.classList.add('hidden');
        if (warningBox) warningBox.classList.add('hidden');

        // Disable PhilHealth radios and inputs
        if (phWithout) phWithout.disabled = true;
        if (phWith) phWith.disabled = true;
        if (idInput) idInput.disabled = true;
        if (relSelect) relSelect.disabled = true;

        if (searchInput) searchInput.style.display = 'none';
        if (msBox) {
            msBox.style.cursor = 'default';
            msBox.classList.remove('cursor-text');
        }
    } else {
        if (modalTitle) modalTitle.innerText = 'Assign Examination';
        if (modalSubtitle) modalSubtitle.innerText = 'Select procedure(s) and specify PhilHealth coverage';
        if (modalIconBox) {
            modalIconBox.className = 'bg-indigo-100 text-indigo-600 p-2.5 rounded-lg border border-indigo-200';
            modalIconBox.innerHTML = '<i data-lucide="clipboard-list" class="w-6 h-6"></i>';
        }
        if (examLabel) examLabel.innerText = 'Select Examination Procedure(s) ';
        if (requiredAsterisk) requiredAsterisk.classList.remove('hidden');
        if (submitBtn) submitBtn.style.display = 'block';
        if (cancelBtn) {
            cancelBtn.innerText = 'Cancel';
            cancelBtn.className = 'px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl hover:bg-gray-200 transition cursor-pointer';
        }

        // Enable PhilHealth radios and inputs
        if (phWithout) phWithout.disabled = false;
        if (phWith) phWith.disabled = false;
        if (idInput) idInput.disabled = false;
        if (relSelect) relSelect.disabled = false;

        if (searchInput) searchInput.style.display = '';
        if (msBox) {
            msBox.style.cursor = '';
            msBox.classList.add('cursor-text');
        }

        // Immediately run validation check if With PhilHealth Card and ID is 12 digits
        if (hasCard && idInput && idInput.value.replace(/\D/g, '').length === 12) {
            checkAssignPhilHealthDup(true);
        }
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
}

function validateAssignForm(e) {
    e.preventDefault();

    const form = document.getElementById('assignForm');
    const hiddenInput = form ? form.querySelector('.exam-ms-hidden-input') : null;
    const assignedStr = hiddenInput ? hiddenInput.value : '';
    const assignedExams = assignedStr.split(',').map(s => s.trim()).filter(Boolean);

    if (assignedExams.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'No Exam Selected',
                text: 'Please select at least one examination procedure.',
                customClass: { popup: 'rounded-3xl border-0 shadow-2xl' }
            });
        } else {
            alert('Please select at least one examination procedure.');
        }
        return false;
    }

    const requestedStr = document.getElementById('assignBodyPart')
        ? document.getElementById('assignBodyPart').getAttribute('data-raw') || ''
        : '';

    const isUnspecified = !requestedStr || requestedStr.trim() === '' ||
        requestedStr.toLowerCase() === 'to be determined' ||
        requestedStr.toLowerCase() === 'not specified';

    if (!isUnspecified) {
        const requestedParts = requestedStr.split(',').map(s => s.trim()).filter(Boolean);
        const missingParts = [];

        // ENFORCE: At least one assigned exam for EACH requested body part!
        requestedParts.forEach(part => {
            const allowedForPart = getAllowedExamsForSinglePart(part);
            if (allowedForPart.length > 0) {
                const hasCoverage = assignedExams.some(exam => allowedForPart.includes(exam));
                if (!hasCoverage) {
                    missingParts.push(part);
                }
            }
        });

        if (missingParts.length > 0) {
            const partList = missingParts.map(p => `"${p}"`).join(', ');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Exam Assignment',
                    html: `Please select at least one exam procedure for each requested body part.<br><br><strong>Missing:</strong> ${partList}`,
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold bg-red-600' }
                });
            } else {
                alert(`Please select at least one exam for: ${partList}`);
            }
            return false;
        }

        // Also check if any selected exam is outside ALL allowed exams
        const matchInfo = getMatchedCategoriesAndExams(requestedStr);
        if (matchInfo.isRestricted && matchInfo.allowedExams.length > 0) {
            const invalidExams = assignedExams.filter(exam => !matchInfo.allowedExams.includes(exam));
            if (invalidExams.length > 0) {
                const reqCats = matchInfo.allowedCategories.join(', ');
                const errMsg = `You cannot assign "${invalidExams.join(', ')}" because it does not match the patient's requested body part(s) (${reqCats}).\n\nPlease select only procedures matching the requested body parts.`;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Category Mismatch',
                        text: errMsg,
                        customClass: { popup: 'rounded-3xl border-0 shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold bg-red-600' }
                    });
                } else {
                    alert(errMsg);
                }
                return false;
            }
        }
    }

    // Check PhilHealth validation if "With PhilHealth Card" is selected
    const isWithCard = document.getElementById('assign_ph_with')?.checked || false;
    if (isWithCard) {
        const idInput = document.getElementById('assign_philhealth_id');
        const relSelect = document.getElementById('assign_philhealth_relation');
        const ownerOpt = document.getElementById('assign-opt-owner');
        const familyOpt = document.getElementById('assign-opt-family');

        const cleanDigits = idInput ? idInput.value.replace(/\D/g, '') : '';
        if (cleanDigits.length !== 12) {
            const errText = !cleanDigits ? 'PhilHealth ID Number is required.' : 'PhilHealth ID must contain 12 digits (format: XX-XXXXXXXXX-X).';
            if (window.FormValidator && idInput) {
                window.FormValidator.showError(idInput, errText);
            }
            if (typeof toast === 'function') {
                toast(errText, 'error');
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete PhilHealth ID',
                    text: errText,
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold bg-blue-600' }
                });
            }
            if (idInput) idInput.focus();
            return false;
        }

        // Both options disabled check
        if (ownerOpt?.disabled && familyOpt?.disabled) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'PhilHealth ID Not Applicable',
                    html: `This PhilHealth ID cannot be applied to this patient.<br><br>Please select <strong>"Without PhilHealth"</strong> to proceed with standard pricing without a discount.`,
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold bg-red-600' }
                });
            } else {
                alert('This PhilHealth ID cannot be applied to this patient.');
            }
            return false;
        }

        if (relSelect && !relSelect.value) {
            const relErr = "Patient's Relation to ID is required.";
            if (window.FormValidator && relSelect) {
                window.FormValidator.showError(relSelect, relErr);
            }
            if (typeof toast === 'function') {
                toast(relErr, 'error');
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Relation Required',
                    text: 'Please select patient\'s relation to the PhilHealth ID (Principal Member or Qualified Dependent).',
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold bg-blue-600' }
                });
            }
            relSelect.focus();
            return false;
        }

        const selectedOpt = relSelect ? relSelect.selectedOptions[0] : null;
        if (selectedOpt && selectedOpt.disabled) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Selected Relation Unavailable',
                    text: 'The selected relation is disabled and cannot be used for this ID and patient. Please select an available option or choose "Without PhilHealth".',
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold bg-red-600' }
                });
            } else {
                alert('The selected relation is disabled and cannot be used for this ID and patient.');
            }
            relSelect.focus();
            return false;
        }

        if (relSelect?.value === 'Qualified Dependent' && familyOpt?.disabled) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Qualified Dependent Unavailable',
                    text: 'The cardholder cannot be registered as their own Qualified Dependent. Please choose "Without PhilHealth".',
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold bg-red-600' }
                });
            } else {
                alert('The cardholder cannot be registered as their own Qualified Dependent.');
            }
            return false;
        }

        if (relSelect?.value === 'Principal Member' && ownerOpt?.disabled) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Principal Member Unavailable',
                    text: 'Principal Member has already been registered for this PhilHealth ID.',
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold bg-red-600' }
                });
            } else {
                alert('Principal Member has already been registered for this PhilHealth ID.');
            }
            return false;
        }
    }

    const doSubmit = function () {
        closeAssignModal();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        }
        form.submit();
    };

    if (typeof confirmAction === 'function') {
        confirmAction(
            'Confirm Assignment',
            'Are you sure you want to assign the selected examination(s)?',
            doSubmit
        );
    } else {
        if (confirm('Are you sure you want to assign the selected examination(s)?')) {
            doSubmit();
        }
    }
    return false;
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function () {
    const assignModal = document.getElementById('assignModal');
    if (assignModal) {
        assignModal.addEventListener('exam-ms:change', function () {
            checkLiveExamCategoryMatch();
            recalculateAssignPricing();
        });
    }

    const assignSelect = document.getElementById('assign_exam_select');
    const assignPriceInput = document.getElementById('assign_exam_price');
    if (assignSelect) {
        assignSelect.addEventListener('change', function () {
            const selectedOption = assignSelect.options[assignSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.price) {
                assignPriceInput.value = selectedOption.dataset.price;
            }
            recalculateAssignPricing();
        });
    }
});

function togglePhilHealthId() {
    const phEl = document.getElementById('modalPhilHealth');
    if (!phEl) return;
    const status = phEl.value;
    const idField = document.getElementById('philHealthIdField');
    const idInput = document.getElementById('modalPhilHealthId');
    const relSelect = document.getElementById('modalPhilHealthRelation');
    if (status === 'With PhilHealth Card') {
        if (idField) idField.classList.remove('hidden');
        if (relSelect) relSelect.required = true;
    } else {
        if (idField) idField.classList.add('hidden');
        if (idInput) {
            idInput.value = '';
            idInput.setCustomValidity('');
        }
        if (relSelect) {
            relSelect.value = '';
            relSelect.required = false;
        }
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
    if (digits.length === 12 && window.FormValidator) {
        window.FormValidator.clearError(input);
    }
}

function saveEditModal() {
    const nameEl = document.getElementById('modalName');
    const birthdateEl = document.getElementById('modalBirthdate');
    const sexEl = document.getElementById('modalSex');
    const contactEl = document.getElementById('modalContact');
    const homeAddressEl = document.getElementById('modalAddress');
    const philhealthEl = document.getElementById('modalPhilHealth');
    const philhealthIdEl = document.getElementById('modalPhilHealthId');
    const philhealthRelationEl = document.getElementById('modalPhilHealthRelation');

    const name = nameEl ? nameEl.value.trim() : '';
    const birthdate = birthdateEl ? birthdateEl.value.trim() : '';
    const sex = sexEl ? sexEl.value : '';
    const contact = contactEl ? contactEl.value.trim() : '';
    const homeAddress = homeAddressEl ? homeAddressEl.value.trim() : '';
    const philhealth = philhealthEl ? philhealthEl.value : '';
    const philhealthId = philhealthIdEl ? philhealthIdEl.value.trim() : '';
    const philhealthRelation = philhealthRelationEl ? philhealthRelationEl.value : '';

    if (window.FormValidator) {
        window.FormValidator.clearAllErrors('#editPatientModal');
    }

    let firstErrorField = null;
    let firstErrorMsg = '';

    if (!name) {
        firstErrorMsg = 'Patient name is required.';
        if (window.FormValidator) window.FormValidator.showError(nameEl, firstErrorMsg);
        firstErrorField = nameEl;
    } else if (!birthdate) {
        firstErrorMsg = 'Birthdate is required.';
        if (window.FormValidator) window.FormValidator.showError(birthdateEl, firstErrorMsg);
        firstErrorField = birthdateEl;
    } else if (!sex) {
        firstErrorMsg = 'Sex is required.';
        if (window.FormValidator) window.FormValidator.showError(sexEl, firstErrorMsg);
        firstErrorField = sexEl;
    } else if (!contact) {
        firstErrorMsg = 'Contact number is required.';
        if (window.FormValidator) window.FormValidator.showError(contactEl, firstErrorMsg);
        firstErrorField = contactEl;
    } else if (!/^09\d{9}$/.test(contact)) {
        firstErrorMsg = 'Contact number must be 11 digits starting with 09.';
        if (window.FormValidator) window.FormValidator.showError(contactEl, firstErrorMsg);
        firstErrorField = contactEl;
    }

    if (!firstErrorField && philhealth === 'With PhilHealth Card') {
        const philHealthPattern = /^\d{2}-\d{9}-\d{1}$/;
        if (!philhealthId) {
            firstErrorMsg = 'PhilHealth ID Number is required.';
            if (window.FormValidator) window.FormValidator.showError(philhealthIdEl, firstErrorMsg);
            firstErrorField = philhealthIdEl;
        } else if (!philHealthPattern.test(philhealthId)) {
            firstErrorMsg = 'Format must be XX-XXXXXXXXX-X (12 digits).';
            if (window.FormValidator) window.FormValidator.showError(philhealthIdEl, firstErrorMsg);
            firstErrorField = philhealthIdEl;
        } else if (!philhealthRelation) {
            firstErrorMsg = 'Relation to PhilHealth ID is required.';
            if (window.FormValidator) window.FormValidator.showError(philhealthRelationEl, firstErrorMsg);
            firstErrorField = philhealthRelationEl;
        }
    }

    if (firstErrorField) {
        if (typeof toast === 'function') {
            toast(firstErrorMsg, 'error');
        }
        firstErrorField.focus();
        return;
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
    const itemsPerPage = 7;
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


