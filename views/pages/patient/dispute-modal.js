// ── Dispute / Report Error Logic ──────────────────────────────────────────────────
window.toggleDisputeFields = function () {
    const category = document.getElementById('dispute-category').value;
    const demoContainer = document.getElementById('demographic-options-container');
    const templateContainer = document.getElementById('template-rename-options-container');
    const descContainer = document.getElementById('general-description-container');
    const descLabel = document.getElementById('dispute-description-label');
    const descTextarea = document.getElementById('dispute-description');

    // Reset helper
    function clearDemographics() {
        ['chk-first-name', 'chk-last-name', 'chk-age', 'chk-sex'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.checked = false;
        });
        ['input-correct-first-name', 'input-correct-last-name', 'input-correct-age', 'input-correct-sex', 'input-correct-birthdate'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const prevAge = document.getElementById('preview-calculated-age');
        if (prevAge) prevAge.classList.add('hidden');
        const valAge = document.getElementById('val-calculated-age');
        if (valAge) valAge.textContent = '';

        ['field-correct-first-name', 'field-correct-last-name', 'field-correct-age', 'field-correct-sex'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
        const infoContainer = document.getElementById('correction-inputs-container');
        if (infoContainer) infoContainer.classList.add('hidden');
    }

    function clearTemplateRename() {
        const inTpl = document.getElementById('input-correct-template');
        if (inTpl) inTpl.value = '';
    }

    if (category === 'demographic_error') {
        // 1. Wrong Patient Info
        demoContainer.classList.remove('hidden');
        if (templateContainer) templateContainer.classList.add('hidden');
        descContainer.classList.add('hidden');
        descTextarea.removeAttribute('required');
        clearTemplateRename();
    } else if (category === 'findings_error') {
        // 2. Typographical Error in Report
        demoContainer.classList.add('hidden');
        if (templateContainer) templateContainer.classList.add('hidden');
        descContainer.classList.remove('hidden');
        descTextarea.setAttribute('required', 'required');
        if (descLabel) descLabel.innerHTML = 'Specify what typographical error occurred: <span class="text-red-500">*</span>';
        if (descTextarea) descTextarea.placeholder = 'Please describe what is misspelled or incorrect in the findings/impression...';
        clearDemographics();
        clearTemplateRename();
    } else if (category === 'template_error') {
        // 3. Rename X-ray Template / Body Part
        demoContainer.classList.add('hidden');
        if (templateContainer) templateContainer.classList.remove('hidden');
        descContainer.classList.add('hidden');
        descTextarea.removeAttribute('required');
        clearDemographics();
    } else if (category === 'both_error') {
        // 4. Both (Patient Info & Typo)
        demoContainer.classList.remove('hidden');
        if (templateContainer) templateContainer.classList.add('hidden');
        descContainer.classList.remove('hidden');
        descTextarea.setAttribute('required', 'required');
        if (descLabel) descLabel.innerHTML = 'Specify what typographical error occurred: <span class="text-red-500">*</span>';
        if (descTextarea) descTextarea.placeholder = 'Please describe what is misspelled or incorrect in the findings/impression...';
        clearTemplateRename();
    } else if (category === 'both_template_error') {
        // 5. Both (Patient Info & Rename Template)
        demoContainer.classList.remove('hidden');
        if (templateContainer) templateContainer.classList.remove('hidden');
        descContainer.classList.add('hidden');
        descTextarea.removeAttribute('required');
    } else if (category === 'other' || category === 'other_error') {
        // 6. Others
        demoContainer.classList.add('hidden');
        if (templateContainer) templateContainer.classList.add('hidden');
        descContainer.classList.remove('hidden');
        descTextarea.setAttribute('required', 'required');
        if (descLabel) descLabel.innerHTML = 'Please specify what the error or concern is: <span class="text-red-500">*</span>';
        if (descTextarea) descTextarea.placeholder = 'Please describe your concern or error in detail...';
        clearDemographics();
        clearTemplateRename();
    } else {
        demoContainer.classList.add('hidden');
        if (templateContainer) templateContainer.classList.add('hidden');
        descContainer.classList.add('hidden');
        descTextarea.removeAttribute('required');
    }
};

function calculateAgeFromBirthdate(birthdateStr) {
    if (!birthdateStr) return '';
    const bdate = new Date(birthdateStr);
    if (isNaN(bdate.getTime())) return '';
    const today = new Date();
    let age = today.getFullYear() - bdate.getFullYear();
    const m = today.getMonth() - bdate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < bdate.getDate())) {
        age--;
    }
    return age >= 0 ? age : 0;
}

window.initDisputeDatePicker = function () {
    const input = document.getElementById('input-correct-birthdate');
    if (!input) return;

    function handleDateChange(bdateVal) {
        const age = calculateAgeFromBirthdate(bdateVal);
        const ageInput = document.getElementById('input-correct-age');
        if (ageInput) ageInput.value = age !== '' ? age : '';

        const preview = document.getElementById('preview-calculated-age');
        const valSpan = document.getElementById('val-calculated-age');
        if (preview && valSpan) {
            if (age !== '') {
                valSpan.textContent = age + (age === 1 ? ' yr old' : ' yrs old');
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }
    }

    if (typeof ModernDatePicker !== 'undefined') {
        if (!input._customDatePicker) {
            new ModernDatePicker(input, {
                maxDate: new Date(),
                onSelect: function (val) {
                    handleDateChange(val);
                }
            });

            input.addEventListener('changeDate', function () {
                handleDateChange(input.value);
            });
            input.addEventListener('change', function () {
                handleDateChange(input.value);
            });
        }
    }
};

window.toggleCorrectionInputs = function () {
    const chkFn = document.getElementById('chk-first-name')?.checked;
    const chkLn = document.getElementById('chk-last-name')?.checked;
    const chkAge = document.getElementById('chk-age')?.checked;
    const chkSex = document.getElementById('chk-sex')?.checked;

    const fieldFn = document.getElementById('field-correct-first-name');
    const fieldLn = document.getElementById('field-correct-last-name');
    const fieldAge = document.getElementById('field-correct-age');
    const fieldSex = document.getElementById('field-correct-sex');

    if (fieldFn) {
        if (chkFn) fieldFn.classList.remove('hidden');
        else fieldFn.classList.add('hidden');
    }
    if (fieldLn) {
        if (chkLn) fieldLn.classList.remove('hidden');
        else fieldLn.classList.add('hidden');
    }
    if (fieldAge) {
        if (chkAge) {
            fieldAge.classList.remove('hidden');
            setTimeout(() => {
                initDisputeDatePicker();
                if (window.lucide) lucide.createIcons();
            }, 50);
        } else {
            fieldAge.classList.add('hidden');
        }
    }
    if (fieldSex) {
        if (chkSex) fieldSex.classList.remove('hidden');
        else fieldSex.classList.add('hidden');
    }

    const infoContainer = document.getElementById('correction-inputs-container');
    if (infoContainer) {
        if (chkFn || chkLn || chkAge || chkSex) {
            infoContainer.classList.remove('hidden');
        } else {
            infoContainer.classList.add('hidden');
        }
    }
};

window.setTemplateSide = function (side) {
    const input = document.getElementById('input-correct-template');
    if (!input) return;
    const currentVal = input.value.trim();
    if (!currentVal) {
        const currentExam = document.getElementById('dispute-current-exam-text')?.textContent.trim()
            || document.getElementById('dispute-exam-type')?.textContent.trim() || '';
        let baseExam = currentExam.replace(/^(Left|Right|Bilateral)\s+/i, '').trim();
        input.value = (baseExam && baseExam !== 'General Exam') ? (side + ' ' + baseExam) : side;
    } else {
        if (/^(Left|Right|Bilateral)\b/i.test(currentVal)) {
            input.value = currentVal.replace(/^(Left|Right|Bilateral)\b/i, side);
        } else {
            input.value = side + ' ' + currentVal;
        }
    }
    input.focus();
};

window.openDisputeModal = function (caseId, caseNumber, examType) {
    const modal = document.getElementById('dispute-modal');
    const content = document.getElementById('dispute-modal-content');
    if (!modal) { alert("dispute-modal NOT FOUND!"); return; }
    if (!content) { alert("dispute-modal-content NOT FOUND!"); return; }

    document.getElementById('dispute-case-id').value = caseId;
    document.getElementById('dispute-case-number').textContent = caseNumber;
    document.getElementById('dispute-exam-type').textContent = examType;
    const examBadgeText = document.getElementById('dispute-current-exam-text');
    if (examBadgeText) examBadgeText.textContent = examType;

    document.getElementById('dispute-category').value = '';
    const descTextarea = document.getElementById('dispute-description');
    if (descTextarea) descTextarea.value = '';

    ['chk-first-name', 'chk-last-name', 'chk-age', 'chk-sex'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.checked = false;
    });

    const inFn = document.getElementById('input-correct-first-name');
    if (inFn) inFn.value = '';
    const inLn = document.getElementById('input-correct-last-name');
    if (inLn) inLn.value = '';
    const inAge = document.getElementById('input-correct-age');
    if (inAge) inAge.value = '';
    const inSex = document.getElementById('input-correct-sex');
    if (inSex) inSex.value = '';
    const inTpl = document.getElementById('input-correct-template');
    if (inTpl) inTpl.value = '';

    ['field-correct-first-name', 'field-correct-last-name', 'field-correct-age', 'field-correct-sex'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });

    const infoContainer = document.getElementById('correction-inputs-container');
    if (infoContainer) infoContainer.classList.add('hidden');
    const tplContainer = document.getElementById('template-rename-options-container');
    if (tplContainer) tplContainer.classList.add('hidden');

    toggleDisputeFields();

    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.classList.remove('opacity-0');
    content.classList.remove('scale-95');

    if (window.lucide) lucide.createIcons();
};

window.closeDisputeModal = function () {
    const modal = document.getElementById('dispute-modal');
    const content = document.getElementById('dispute-modal-content');
    if (!modal || !content) return;

    modal.classList.add('opacity-0');
    content.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
};

window.submitDisputeForm = function (e) {
    e.preventDefault();
    const btn = document.getElementById('dispute-submit-btn');
    const form = document.getElementById('dispute-form');
    const category = document.getElementById('dispute-category').value;
    const formData = new FormData(form);

    if (!category) {
        Swal.fire({
            icon: 'warning',
            title: 'Required Field',
            text: 'Please select what type of error this is.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

    let finalDescription = '';

    // 1. Findings / Typo notes (Categories 2 & 4)
    if (category === 'findings_error' || category === 'both_error') {
        const desc = document.getElementById('dispute-description').value.trim();
        if (!desc && category === 'findings_error') {
            Swal.fire({
                icon: 'warning',
                title: 'Required Field',
                text: 'Please describe the typographical error.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }
        finalDescription += desc ? `Typographical Error Note:\n  • ${desc}\n\n` : '';
    } else if (category === 'other' || category === 'other_error') {
        const desc = document.getElementById('dispute-description').value.trim();
        if (!desc) {
            Swal.fire({
                icon: 'warning',
                title: 'Required Field',
                text: 'Please specify what the error or concern is.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }
        finalDescription += `Other Concern Note:\n  • ${desc}\n\n`;
    }

    // 2. Template Rename Details (Categories 3 & 5)
    if (category === 'template_error' || category === 'both_template_error') {
        const correctTpl = document.getElementById('input-correct-template')?.value.trim();
        const currentExam = document.getElementById('dispute-current-exam-text')?.textContent.trim() || 'General Exam';

        if (!correctTpl) {
            Swal.fire({
                icon: 'warning',
                title: 'Required Field',
                text: 'Please enter the correct template or exam name.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let tplLines = [];
        tplLines.push(`  • Current Exam: ${currentExam}`);
        tplLines.push(`  • Correct Template: ${correctTpl}`);
        finalDescription += `Template Rename Request:\n${tplLines.join('\n')}\n\n`;
    }

    // 3. Demographic info (Categories 1, 4, 5)
    if (category === 'demographic_error' || category === 'both_error' || category === 'both_template_error') {
        const chkFn = document.getElementById('chk-first-name')?.checked;
        const chkLn = document.getElementById('chk-last-name')?.checked;
        const chkAge = document.getElementById('chk-age')?.checked;
        const chkSex = document.getElementById('chk-sex')?.checked;

        if (!chkFn && !chkLn && !chkAge && !chkSex && category === 'demographic_error') {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Details',
                text: 'Please select which information needs correction.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        const demoLines = [];

        if (chkFn) {
            const val = document.getElementById('input-correct-first-name')?.value.trim();
            if (!val) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Field',
                    text: 'Please enter your correct First Name.',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }
            demoLines.push(`  • First Name: ${val}`);
        }

        if (chkLn) {
            const val = document.getElementById('input-correct-last-name')?.value.trim();
            if (!val) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Field',
                    text: 'Please enter your correct Last Name.',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }
            demoLines.push(`  • Last Name: ${val}`);
        }

        if (chkAge) {
            let finalAge = document.getElementById('input-correct-age')?.value.trim();
            const bdateVal = document.getElementById('input-correct-birthdate')?.value.trim();
            if (!finalAge && bdateVal) {
                finalAge = String(calculateAgeFromBirthdate(bdateVal));
            }
            if (!finalAge) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Field',
                    text: 'Please select your birthdate to provide your correct Age.',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }
            demoLines.push(`  • Age: ${finalAge}`);
        }

        if (chkSex) {
            const val = document.getElementById('input-correct-sex')?.value.trim();
            if (!val) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Field',
                    text: 'Please select your correct Sex.',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }
            demoLines.push(`  • Sex: ${val}`);
        }

        if (demoLines.length > 0) {
            finalDescription += `Wrong Patient Info:\n${demoLines.join('\n')}\n\n`;
        }
    }

    if (!finalDescription.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Details',
            text: 'Please provide details for the error.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }
    
    formData.set('description', finalDescription.trim());

    btn.disabled = true;
    btn.innerHTML = 'Submitting...';

    fetch(window.__APP__.basePath + '/app/Api/disputes.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Correction Request';
            if (window.lucide) lucide.createIcons();

            if (data.success) {
                closeDisputeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Request Submitted!',
                    text: data.message,
                    confirmButtonColor: '#dc2626',
                    customClass: { popup: 'rounded-2xl' }
                }).then(() => {
                    window.location.href = window.__APP__.basePath + '/my-records?tab=disputes';
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Correction Request';
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Network connection error.' });
        });
};
