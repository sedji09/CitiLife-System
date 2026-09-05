/**
 * radtech-amend.js
 * RadTech Findings & Information Amendment Workflow
 * CitiLife Diagnostic Center
 */

let currentAmendData = null;

function getAppBasePath() {
    if (typeof window.__APP__ !== 'undefined' && window.__APP__.basePath !== undefined && window.__APP__.basePath !== '') {
        return window.__APP__.basePath;
    }
    if (typeof window.PROJECT_DIR !== 'undefined' && window.PROJECT_DIR) {
        return '/' + window.PROJECT_DIR.replace(/^\/+|\/+$/g, '');
    }
    // Auto-detect from current path, e.g. "/CitiLife-System/index.php" -> "/CitiLife-System"
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    if (pathParts.length > 0 && pathParts[0] !== 'index.php' && pathParts[0] !== 'app' && pathParts[0] !== 'views') {
        return '/' + pathParts[0];
    }
    return '';
}

function openRadTechAmendModal(caseId, disputeId) {
    if (!caseId) return;

    Swal.fire({
        title: 'Loading Case Data...',
        text: 'Fetching findings, exam details, and patient information for amendment.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const basePath = getAppBasePath();
    const endpoint = `${basePath}/app/Api/disputes.php?action=get_case_for_amend&case_id=${caseId}&dispute_id=${disputeId || 0}`;

    fetch(endpoint)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}: Network error loading case data from ${endpoint}`);
            return res.json();
        })
        .then(data => {
            Swal.close();
            if (!data.success) {
                Swal.fire('Error', data.message || 'Failed to fetch case data.', 'error');
                return;
            }

            currentAmendData = data;
            populateAmendModal(data);

            const modal = document.getElementById('radtech-amend-modal');
            if (modal) {
                modal.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
            }
        })
        .catch(err => {
            console.error('Amend modal fetch error:', err);
            Swal.fire('Error', 'Could not connect to server: ' + err.message, 'error');
        });
}

function closeRadTechAmendModal() {
    const modal = document.getElementById('radtech-amend-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function populateAmendModal(data) {
    const c = data.case || {};
    const p = data.patient || {};
    const d = data.dispute || null;

    // Header info
    const caseNumEl = document.getElementById('amend-modal-case-number');
    if (caseNumEl) caseNumEl.innerText = c.case_number || 'N/A';

    const patientInfoEl = document.getElementById('amend-modal-patient-info');
    if (patientInfoEl) patientInfoEl.innerText = `${p.first_name || ''} ${p.last_name || ''} (${p.patient_number || 'PX'})`;

    const examTypeEl = document.getElementById('amend-modal-exam-type');
    if (examTypeEl) examTypeEl.innerText = c.exam_type || 'General Exam';

    // Hidden inputs
    const caseIdInput = document.getElementById('amend-case-id');
    if (caseIdInput) caseIdInput.value = c.id || 0;

    const disputeIdInput = document.getElementById('amend-dispute-id');
    if (disputeIdInput) disputeIdInput.value = (d && d.id) ? d.id : 0;

    // Dispute reported banner
    const issueBanner = document.getElementById('amend-reported-issue-container');
    if (issueBanner) {
        if (d && d.description) {
            issueBanner.classList.remove('hidden');
            const catEl = document.getElementById('amend-reported-category');
            if (catEl) catEl.innerText = d.category ? d.category.replace(/_/g, ' ').toUpperCase() : 'ERROR REPORT';
            const descEl = document.getElementById('amend-reported-description');
            if (descEl) descEl.innerText = d.description;
        } else {
            issueBanner.classList.add('hidden');
        }
    }

    // Prefill form fields
    const findingsEl = document.getElementById('amend-findings');
    if (findingsEl) findingsEl.value = c.findings || '';

    const impressionEl = document.getElementById('amend-impression');
    if (impressionEl) impressionEl.value = c.impression || '';

    const firstNameEl = document.getElementById('amend-first-name');
    if (firstNameEl) firstNameEl.value = p.first_name || '';

    const lastNameEl = document.getElementById('amend-last-name');
    if (lastNameEl) lastNameEl.value = p.last_name || '';

    const middleNameEl = document.getElementById('amend-middle-name');
    if (middleNameEl) middleNameEl.value = p.middle_name || '';

    const examTypeInput = document.getElementById('amend-exam-type');
    if (examTypeInput) examTypeInput.value = c.exam_type || '';

    const templateEl = document.getElementById('amend-template');
    if (templateEl) templateEl.value = c.report_template || 'General Standard';

    const notesEl = document.getElementById('amend-notes');
    if (notesEl) notesEl.value = '';

    // Render Previous Amendments if any
    const historyContainer = document.getElementById('amend-history-container');
    const historyList = document.getElementById('amend-history-list');
    if (historyContainer && historyList) {
        if (data.amendments && data.amendments.length > 0) {
            historyContainer.classList.remove('hidden');
            historyList.innerHTML = data.amendments.map(item => `
                <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs space-y-1">
                    <div class="flex items-center justify-between text-gray-500 font-semibold">
                        <span>By: ${item.amended_by_name || 'Staff'} (${item.amended_by_role || 'radtech'})</span>
                        <span>${item.amended_at}</span>
                    </div>
                    <div class="text-gray-700 font-medium">${item.notes || 'Amended findings, exam type or details'}</div>
                </div>
            `).join('');
        } else {
            historyContainer.classList.add('hidden');
            historyList.innerHTML = '';
        }
    }
}

function submitRadTechAmendment(actionType) {
    const caseId = document.getElementById('amend-case-id').value;
    const disputeId = document.getElementById('amend-dispute-id').value;
    const findings = document.getElementById('amend-findings').value.trim();
    const impression = document.getElementById('amend-impression').value.trim();
    const firstName = document.getElementById('amend-first-name').value.trim();
    const lastName = document.getElementById('amend-last-name').value.trim();
    const middleName = document.getElementById('amend-middle-name').value.trim();
    const examTypeEl = document.getElementById('amend-exam-type');
    const examType = examTypeEl ? examTypeEl.value.trim() : '';
    const template = document.getElementById('amend-template').value.trim();
    const notes = document.getElementById('amend-notes').value.trim();

    if (!caseId) {
        Swal.fire('Missing Case', 'Case ID is missing.', 'warning');
        return;
    }

    if (!findings) {
        Swal.fire('Required Field', 'Please provide or verify the Findings text before saving.', 'warning');
        return;
    }

    if (!firstName || !lastName) {
        Swal.fire('Required Field', 'Patient First Name and Last Name cannot be empty.', 'warning');
        return;
    }

    if (!examType) {
        Swal.fire('Required Field', 'X-ray Examination Name cannot be empty.', 'warning');
        return;
    }

    const isRelease = (actionType === 'save_and_release');
    const confirmTitle = isRelease 
        ? 'Save & Release Amended Report?' 
        : 'Save as Correction Completed?';
    const confirmText = isRelease
        ? 'This will immediately update the report findings, exam type, patient records, mark the case as Resolved, and notify the patient via email.'
        : 'This will save the corrections as Correction Completed, ready for final release.';

    Swal.fire({
        title: confirmTitle,
        text: confirmText,
        icon: isRelease ? 'question' : 'info',
        showCancelButton: true,
        confirmButtonColor: isRelease ? '#16a34a' : '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: isRelease ? 'Yes, Save & Release' : 'Yes, Save Correction',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Saving Amendment...',
                text: 'Please wait while we process the corrections.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const basePath = getAppBasePath();
            const formData = new FormData();
            formData.append('action', 'save_radtech_amendment');
            formData.append('case_id', caseId);
            formData.append('dispute_id', disputeId);
            formData.append('findings', findings);
            formData.append('impression', impression);
            formData.append('first_name', firstName);
            formData.append('last_name', lastName);
            formData.append('middle_name', middleName);
            formData.append('exam_type', examType);
            formData.append('report_template', template);
            formData.append('notes', notes);
            formData.append('action_type', actionType);

            fetch(`${basePath}/app/Api/disputes.php`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: res.message || 'Amendment saved successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        closeRadTechAmendModal();
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message || 'Failed to save amendment.', 'error');
                }
            })
            .catch(err => {
                console.error('Save amendment error:', err);
                Swal.fire('Error', 'Failed to communicate with server: ' + err.message, 'error');
            });
        }
    });
}
