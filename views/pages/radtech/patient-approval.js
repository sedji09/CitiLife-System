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

function openAssignModal(id, requestedBodyPart, assignedExam = '') {
    document.getElementById('assignModal').classList.remove('hidden');
    
    let displayStr = requestedBodyPart || 'Not specified';
    if (displayStr !== 'Not specified' && window.examCategoryMap) {
        const parts = displayStr.split(',').map(s => s.trim()).filter(s => s);
        const categories = new Set();
        let allMapped = true;
        parts.forEach(p => {
            if (window.examCategoryMap[p]) {
                categories.add(window.examCategoryMap[p]);
            } else {
                allMapped = false;
            }
        });
        
        if (categories.size > 0 && allMapped) {
            displayStr = Array.from(categories).join(', ');
        }
    }
    
    // Make the requested body part more visible
    const bodyPartEl = document.getElementById('assignBodyPart');
    bodyPartEl.innerText = displayStr;
    bodyPartEl.setAttribute('data-raw', requestedBodyPart || 'Not specified');
    
    const form = document.getElementById('assignForm');
    form.action = window.__APP__.basePath + '/patient-approval?action=assign_exam&id=' + id;
    
    // set or reset select
    const hiddenInput = form.querySelector('.exam-ms-hidden-input');
    if (hiddenInput) {
        hiddenInput.value = assignedExam;
        const container = hiddenInput.closest('.exam-ms-component');
        if (container && typeof renderChips === 'function') {
            renderChips(container);
        }
    }
    
    document.getElementById('assign_exam_price').value = '0';
}

function validateAssignForm(e) {
    e.preventDefault();

    // Get requested exams
    const requestedStr = document.getElementById('assignBodyPart').getAttribute('data-raw') || '';
    if (requestedStr === 'Not specified') {
        window.confirmAction('Confirm Assignment', 'Are you sure you want to assign this exact examination and request payment?', function() {
            closeAssignModal();
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: 'Processing...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            }
            document.getElementById('assignForm').submit();
        });
        return false;
    }

    const requestedExams = requestedStr.split(',').map(s => s.trim()).filter(s => s);
    const requestedCategories = new Set();
    let hasUnknownRequested = false;

    // Determine requested categories
    requestedExams.forEach(exam => {
        const cat = window.examCategoryMap && window.examCategoryMap[exam];
        if (cat) {
            requestedCategories.add(cat.toLowerCase());
        } else {
            hasUnknownRequested = true;
        }
    });

    // If we couldn't map ANY requested exam to a category, we fallback to allowing anything
    if (requestedCategories.size === 0 && !hasUnknownRequested) {
        window.confirmAction('Confirm Assignment', 'Are you sure you want to assign this exact examination and request payment?', function() {
            closeAssignModal();
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: 'Processing...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            }
            document.getElementById('assignForm').submit();
        });
        return false;
    }

    // Get assigned exams
    const form = document.getElementById('assignForm');
    const hiddenInput = form.querySelector('.exam-ms-hidden-input');
    const assignedStr = hiddenInput ? hiddenInput.value : '';
    const assignedExams = assignedStr.split(',').map(s => s.trim()).filter(s => s);

    if (assignedExams.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Please select at least one exam type.', 'error');
        } else {
            alert('Please select at least one exam type.');
        }
        return false;
    }

    // Validate assigned exams
    let invalidExams = [];
    assignedExams.forEach(exam => {
        const cat = window.examCategoryMap && window.examCategoryMap[exam];
        if (cat) {
            // If requested categories exist, the assigned category MUST be in it
            if (requestedCategories.size > 0 && !requestedCategories.has(cat.toLowerCase())) {
                invalidExams.push(exam);
            }
        }
    });

    if (invalidExams.length > 0) {
        const reqCats = Array.from(requestedCategories).map(c => c.charAt(0).toUpperCase() + c.slice(1)).join(', ');
        const errMsg = `You cannot assign "${invalidExams.join(', ')}" because it does not match the patient's requested category (${reqCats}).\n\nPlease select exams only from the requested categories.`;
        if (typeof Swal !== 'undefined') {
            Swal.fire('Restriction Error', errMsg, 'error');
        } else {
            alert(errMsg);
        }
        return false;
    }

    window.confirmAction('Confirm Assignment', 'Are you sure you want to assign this exact examination and request payment?', function() {
        closeAssignModal();
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Processing...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        }
        document.getElementById('assignForm').submit();
    });
    return false;
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const assignSelect = document.getElementById('assign_exam_select');
    const assignPriceInput = document.getElementById('assign_exam_price');
    
    if(assignSelect) {
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


