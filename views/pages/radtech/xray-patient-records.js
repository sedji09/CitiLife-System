(function () {
    const ROWS_PER_PAGE = 7;
    let currentPages = {
        completed: parseInt(sessionStorage.getItem('Citilife_radtechXray_page_completed')) || 1,
        disputes: parseInt(sessionStorage.getItem('Citilife_radtechXray_page_disputes')) || 1
    };

    // ── Helpers ───────────────────────────────────────────────────────────────
    function getFilteredRows(type) {
        const search = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
        const sort   = document.getElementById('sort-date')?.value    || 'Sort by:';
        const claimFilter = (document.getElementById('filter-claim-status')?.value || 'all').toLowerCase();

        const tbodyId = type === 'completed' ? 'table-body' : 'disputes-table-body';
        const rowClass = type === 'completed' ? 'tr.record-row' : 'tr.dispute-row';
        const tbody = document.getElementById(tbodyId);
        
        if (!tbody) return [];

        let rows = Array.from(tbody.querySelectorAll(rowClass));

        // Sort
        if (sort === 'Newest Case' || sort === 'Oldest Case') {
            rows.sort((a, b) => {
                const dateA = new Date(a.dataset.date || 0).getTime();
                const dateB = new Date(b.dataset.date || 0).getTime();
                if (dateA !== dateB) {
                    return sort === 'Newest Case' ? dateB - dateA : dateA - dateB;
                }
                const idA = a.dataset.id || '';
                const idB = b.dataset.id || '';
                return sort === 'Newest Case' ? idB.localeCompare(idA) : idA.localeCompare(idB);
            });
            rows.forEach(row => tbody.appendChild(row));
        }

        // Filter
        return rows.filter(row => {
            const name    = (row.dataset.name || '').toLowerCase();
            const id      = (row.dataset.id   || '').toLowerCase();
            const patient = (row.dataset.patient || '').toLowerCase();
            const claimed = (row.dataset.claimed || 'unclaimed').toLowerCase();

            const matchSearch = !search || name.includes(search) || id.includes(search) || patient.includes(search);
            const matchClaim  = (claimFilter === 'all') || (claimed === claimFilter);

            return matchSearch && matchClaim;
        });
    }

    function renderPage(type) {
        const tbodyId = type === 'completed' ? 'table-body' : 'disputes-table-body';
        const rowClass = type === 'completed' ? 'tr.record-row' : 'tr.dispute-row';
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;

        const allRows      = Array.from(tbody.querySelectorAll(rowClass));
        const filteredRows = getFilteredRows(type);
        const totalPages   = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));

        // Clamp current page
        if (currentPages[type] > totalPages) currentPages[type] = totalPages;
        if (currentPages[type] < 1)          currentPages[type] = 1;

        sessionStorage.setItem(`Citilife_radtechXray_page_${type}`, currentPages[type]);

        const startIdx = (currentPages[type] - 1) * ROWS_PER_PAGE;
        const endIdx   = startIdx + ROWS_PER_PAGE;

        // Build a Set for quick lookup of which rows are visible on this page
        const visibleSet = new Set(filteredRows.slice(startIdx, endIdx));

        allRows.forEach(row => {
            row.style.display = visibleSet.has(row) ? '' : 'none';
        });

        // Empty-state row
        let emptyMsgId = type === 'completed' ? 'empty-msg-row' : 'empty-msg-row-disputes';
        let emptyMsg = document.getElementById(emptyMsgId);
        if (filteredRows.length === 0 && allRows.length > 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('tr');
                emptyMsg.id = emptyMsgId;
                emptyMsg.innerHTML = `<td colspan="8" class="text-center py-8 text-gray-500">No records match your filters.</td>`;
                tbody.appendChild(emptyMsg);
            } else {
                emptyMsg.style.display = '';
            }
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        // Update pagination UI
        updatePaginationUI(type, filteredRows.length, totalPages);
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function updatePaginationUI(type, totalFiltered, totalPages) {
        const countId = type === 'completed' ? 'xray-record-count' : 'disputes-record-count';
        const containerId = type === 'completed' ? 'xray-pagination-controls' : 'disputes-pagination-controls';
        
        const recordCountInfo = document.getElementById(countId);
        const container = document.getElementById(containerId);

        const startIdx = totalFiltered === 0 ? 0 : (currentPages[type] - 1) * ROWS_PER_PAGE + 1;
        const endIdx   = Math.min(currentPages[type] * ROWS_PER_PAGE, totalFiltered);

        if (recordCountInfo) {
            recordCountInfo.innerHTML = totalFiltered === 0
                ? 'No records'
                : `Showing <span class="font-semibold text-gray-800">${startIdx}</span> to <span class="font-semibold text-gray-800">${endIdx}</span> of <span class="font-semibold text-gray-800">${totalFiltered}</span> record${totalFiltered !== 1 ? 's' : ''}`;
        }

        if (!container) return;
        container.innerHTML = '';

        // Helper to create a button
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
                    currentPages[type] = page;
                    renderPage(type);
                };
            }
            return btn;
        }

        // Helper to create ellipsis
        function createEllipsis() {
            const span = document.createElement('span');
            span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
            span.innerText = '...';
            return span;
        }

        const curr = currentPages[type];

        // First Button
        container.appendChild(createButton('&laquo; First', 1, curr <= 1));

        // Back Button
        container.appendChild(createButton('&lsaquo; Back', curr - 1, curr <= 1));

        // Page numbers
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) {
                container.appendChild(createButton(i, i, false, i == curr));
            }
        } else {
            if (curr <= 4) {
                for (let i = 1; i <= 5; i++) {
                    container.appendChild(createButton(i, i, false, i == curr));
                }
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, totalPages == curr));
            } else if (curr >= totalPages - 3) {
                container.appendChild(createButton(1, 1, false, 1 == curr));
                container.appendChild(createEllipsis());
                for (let i = totalPages - 4; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i == curr));
                }
            } else {
                container.appendChild(createButton(1, 1, false, 1 == curr));
                container.appendChild(createEllipsis());
                
                container.appendChild(createButton(curr - 1, curr - 1, false, false));
                container.appendChild(createButton(curr, curr, false, true));
                container.appendChild(createButton(curr + 1, curr + 1, false, false));
                
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, false));
            }
        }

        // Next Button
        container.appendChild(createButton('Next &rsaquo;', curr + 1, curr >= totalPages));

        // Last Button
        container.appendChild(createButton('Last &raquo;', totalPages, curr >= totalPages));
    }

    function saveState() {
        const searchInput = document.getElementById('search-input');
        const sortSelect = document.getElementById('sort-date');
        const claimFilter = document.getElementById('filter-claim-status');
        if (searchInput) sessionStorage.setItem('Citilife_radtechXray_search', searchInput.value);
        if (sortSelect) sessionStorage.setItem('Citilife_radtechXray_sort', sortSelect.value);
        if (claimFilter) sessionStorage.setItem('Citilife_radtechXray_claim', claimFilter.value);
    }

    function applyFilters() {
        saveState();
        currentPages.completed = 1;
        currentPages.disputes = 1;
        renderPage('completed');
        renderPage('disputes');
    }

    // ── Event listeners ───────────────────────────────────────────────────────
    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'search-input') applyFilters();
    });

    document.addEventListener('change', (e) => {
        if (e.target && (e.target.id === 'sort-date' || e.target.id === 'filter-claim-status')) applyFilters();
    });

    // ── Re-apply pagination after realtime polling replaces tbody innerHTML ───
    document.addEventListener('realtime:updated', () => {
        renderPage('completed');
        renderPage('disputes');
    });

    // ── Native Claim Modals Logic ──────────────────────────────────────────
    let activeClaimPatientName = '';
    let currentDetailCaseId = null;
    let currentDetailCaseNo = '';

    window.openMarkClaimModal = function (caseId, caseNumber, patientName, procedureName = 'X-ray') {
        const modal = document.getElementById('modalMarkClaim');
        if (!modal) return;

        activeClaimPatientName = patientName || 'Patient';

        document.getElementById('claim_modal_case_id').value = caseId;
        document.getElementById('claim_modal_case_no').textContent = caseNumber;
        document.getElementById('claim_modal_pat_name').textContent = activeClaimPatientName;
        const procEl = document.getElementById('claim_modal_procedure');
        if (procEl) procEl.textContent = procedureName || 'X-ray';

        document.getElementById('claim_receiver_name').value = activeClaimPatientName;
        document.getElementById('claim_relationship').value = 'Self';
        document.getElementById('claim_relationship').disabled = true;
        if (document.getElementById('claim_id_type')) document.getElementById('claim_id_type').value = '';
        if (document.getElementById('claim_id_number')) document.getElementById('claim_id_number').value = '';
        if (document.getElementById('claim_notes')) document.getElementById('claim_notes').value = '';

        setClaimReceiverType('Self');

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    window.closeMarkClaimModal = function () {
        const modal = document.getElementById('modalMarkClaim');
        if (modal) modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    window.setClaimReceiverType = function (type) {
        const btnSelf = document.getElementById('btn-type-self');
        const btnRep = document.getElementById('btn-type-rep');
        const hiddenType = document.getElementById('claim_receiver_type');
        const nameInput = document.getElementById('claim_receiver_name');
        const relInput = document.getElementById('claim_relationship');
        const idWrapper = document.getElementById('claim_id_wrapper');
        const idTypeInput = document.getElementById('claim_id_type');
        const idNumberInput = document.getElementById('claim_id_number');

        if (!btnSelf || !btnRep) return;

        hiddenType.value = type;

        if (type === 'Self') {
            btnSelf.className = 'flex items-center justify-center gap-2 py-2 px-3 rounded-xl border-2 border-red-500 bg-red-50 text-red-700 font-bold text-xs transition cursor-pointer shadow-2xs';
            btnRep.className = 'flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-gray-200 bg-white text-gray-600 font-semibold text-xs hover:bg-gray-50 transition cursor-pointer';
            nameInput.value = activeClaimPatientName;
            relInput.value = 'Self';
            relInput.disabled = true;
            relInput.classList.add('bg-gray-100/70', 'cursor-not-allowed');
            if (idWrapper) idWrapper.classList.add('hidden');
            if (idTypeInput) idTypeInput.value = '';
            if (idNumberInput) idNumberInput.value = '';
        } else {
            btnRep.className = 'flex items-center justify-center gap-2 py-2 px-3 rounded-xl border-2 border-red-500 bg-red-50 text-red-700 font-bold text-xs transition cursor-pointer shadow-2xs';
            btnSelf.className = 'flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-gray-200 bg-white text-gray-600 font-semibold text-xs hover:bg-gray-50 transition cursor-pointer';
            nameInput.value = '';
            relInput.value = '';
            relInput.disabled = false;
            relInput.classList.remove('bg-gray-100/70', 'cursor-not-allowed');
            if (idWrapper) idWrapper.classList.remove('hidden');
            nameInput.focus();
        }

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    window.submitMarkClaim = function (e) {
        if (e) e.preventDefault();

        const caseId = document.getElementById('claim_modal_case_id')?.value;
        const caseNo = document.getElementById('claim_modal_case_no')?.textContent || 'this record';
        const receiverType = document.getElementById('claim_receiver_type')?.value || 'Self';
        const nameInput = document.getElementById('claim_receiver_name');
        const relInput = document.getElementById('claim_relationship');
        const idTypeInput = document.getElementById('claim_id_type');
        const idNumInput = document.getElementById('claim_id_number');

        const receiverName = (nameInput?.value || '').trim();
        const relationship = (relInput?.value || '').trim();
        const idType = (idTypeInput?.value || '').trim();
        const idNumber = (idNumInput?.value || '').trim();
        const notes = (document.getElementById('claim_notes')?.value || '').trim();
        const btnSubmit = document.getElementById('btnSubmitClaim');

        // Reset previous validation borders
        [nameInput, relInput, idTypeInput, idNumInput].forEach(inp => {
            if (inp) {
                inp.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                inp.classList.add('border-gray-300');
            }
        });

        const showWarn = (msg, inputEl) => {
            if (inputEl) {
                inputEl.classList.remove('border-gray-300');
                inputEl.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                inputEl.focus();
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Field Missing',
                    text: msg,
                    customClass: { container: '!z-[9999999]', popup: 'rounded-2xl p-5' }
                });
            } else {
                alert(msg);
            }
        };

        if (!receiverName) {
            showWarn(receiverType === 'Representative' ? 'Please enter the Representative\'s Full Name.' : 'Please enter the Recipient\'s Full Name.', nameInput);
            return;
        }

        if (receiverType === 'Representative') {
            if (!relationship) {
                showWarn('Please enter the Representative\'s Relationship to Patient (e.g. Spouse, Parent, Child, Sibling).', relInput);
                return;
            }
            if (!idType) {
                showWarn('Please select or enter the Representative\'s Valid ID Presented (e.g. National ID, Driver\'s License).', idTypeInput);
                return;
            }
            if (!idNumber) {
                showWarn('Please enter the Representative\'s ID Number as proof.', idNumInput);
                return;
            }
        }

        let idPresented = '';
        if (idType && idNumber) {
            idPresented = `${idType} (ID No: ${idNumber})`;
        } else if (idType) {
            idPresented = idType;
        } else if (idNumber) {
            idPresented = `ID No: ${idNumber}`;
        }

        const proceedWithSubmission = () => {
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Saving...';
                if (window.lucide) lucide.createIcons();
            }

            fetch('app/api/claim_case.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    case_id: caseId,
                    action: 'mark_claimed',
                    claimed_by: receiverName,
                    claimed_relationship: relationship,
                    claimed_id_presented: idPresented,
                    claimed_notes: notes
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeMarkClaimModal();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Marked as Claimed!',
                            text: data.message || 'X-ray result has been marked as Claimed.',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                        }).then(() => window.location.reload());
                    } else {
                        window.location.reload();
                    }
                } else {
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Confirm Claim';
                        if (window.lucide) lucide.createIcons();
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Could not update claim status.',
                            customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                        });
                    } else {
                        alert(data.message || 'Error occurred.');
                    }
                }
            })
            .catch(err => {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Confirm Claim';
                    if (window.lucide) lucide.createIcons();
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network communication error.',
                        customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                    });
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirm Result Claim?',
                text: `Are you sure you want to mark Case ${caseNo} as Claimed by ${receiverName}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Confirm Claim',
                cancelButtonText: 'No, Cancel',
                reverseButtons: true,
                customClass: {
                    container: '!z-[999999]',
                    popup: 'rounded-2xl p-6 font-sans',
                    confirmButton: 'bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-xl text-xs sm:text-sm shadow-sm transition border-0 cursor-pointer',
                    cancelButton: 'bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 px-4 rounded-xl text-xs sm:text-sm transition border-0 mr-2 cursor-pointer'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    proceedWithSubmission();
                }
            });
        } else {
            if (confirm(`Are you sure you want to mark Case ${caseNo} as Claimed by ${receiverName}?`)) {
                proceedWithSubmission();
            }
        }
    };

    window.openClaimDetailsModal = function (info) {
        const modal = document.getElementById('modalClaimDetails');
        if (!modal) return;

        currentDetailCaseId = info.case_id;
        currentDetailCaseNo = info.case_number;

        document.getElementById('detail_case_no').textContent = `Case #${info.case_number}`;
        document.getElementById('detail_pat_name').textContent = info.patient_name;
        document.getElementById('detail_claimed_at').textContent = info.claimed_at || '—';
        document.getElementById('detail_claimed_by').textContent = info.claimed_by || '—';
        
        const relVal = (info.claimed_relationship || 'Self').trim();
        document.getElementById('detail_relationship').textContent = relVal;

        // Conditionally show/hide ID Presented (only if representative with valid ID recorded)
        const idRow = document.getElementById('detail_row_id');
        const idVal = (info.claimed_id_presented || '').trim();
        if (idRow) {
            if (idVal && idVal !== 'None Specified' && idVal !== 'None' && idVal !== '—' && relVal.toLowerCase() !== 'self') {
                document.getElementById('detail_id_presented').textContent = idVal;
                idRow.classList.remove('hidden');
            } else {
                idRow.classList.add('hidden');
            }
        }

        // Conditionally show/hide Pickup Notes (only if notes actually exist)
        const notesRow = document.getElementById('detail_row_notes');
        const notesVal = (info.claimed_notes || '').trim();
        if (notesRow) {
            if (notesVal && notesVal !== 'No additional notes' && notesVal !== 'No additional pickup notes provided.' && notesVal !== 'None' && notesVal !== '—') {
                document.getElementById('detail_notes').textContent = notesVal;
                notesRow.classList.remove('hidden');
            } else {
                notesRow.classList.add('hidden');
            }
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    window.closeClaimDetailsModal = function () {
        const modal = document.getElementById('modalClaimDetails');
        if (modal) modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    window.revertFromDetailsModal = function () {
        if (!currentDetailCaseId) return;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Revert to Unclaimed?',
                text: `Are you sure you want to mark Case ${currentDetailCaseNo} back to Unclaimed?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Revert to Unclaimed',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-2xl p-5',
                    confirmButton: 'bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-xl text-xs transition border-0',
                    cancelButton: 'bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-xl text-xs transition border-0 mr-2'
                },
                buttonsStyling: false
            }).then((res) => {
                if (res.isConfirmed) {
                    doRevertClaim(currentDetailCaseId);
                }
            });
        } else {
            if (confirm(`Are you sure you want to mark Case ${currentDetailCaseNo} back to Unclaimed?`)) {
                doRevertClaim(currentDetailCaseId);
            }
        }
    };

    function doRevertClaim(caseId) {
        fetch('app/api/claim_case.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                case_id: caseId,
                action: 'mark_unclaimed'
            })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                closeClaimDetailsModal();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Reverted!',
                        text: d.message || 'Status reverted to Unclaimed.',
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl' }
                    }).then(() => window.location.reload());
                } else {
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.message || 'Failed to revert status.' });
                }
            }
        });
    }

    // ── Init (DOM is already ready when this script loads) ────────────────────
    function init() {
        const hasHighlight = (new URLSearchParams(window.location.search)).has('highlight') ||
            (new URLSearchParams(window.location.search)).has('highlight_case') ||
            (new URLSearchParams(window.location.search)).has('case_id');

        const searchInput = document.getElementById('search-input');
        const sortSelect = document.getElementById('sort-date');
        const claimFilter = document.getElementById('filter-claim-status');

        if (!hasHighlight) {
            const savedSearch = sessionStorage.getItem('Citilife_radtechXray_search');
            const savedSort = sessionStorage.getItem('Citilife_radtechXray_sort');
            const savedClaim = sessionStorage.getItem('Citilife_radtechXray_claim');
            if (searchInput && savedSearch !== null) searchInput.value = savedSearch;
            if (sortSelect && savedSort) sortSelect.value = savedSort;
            if (claimFilter && savedClaim) claimFilter.value = savedClaim;
        } else {
            if (searchInput) searchInput.value = '';
            sessionStorage.removeItem('Citilife_radtechXray_search');
        }

        if (sortSelect && (sortSelect.value === 'Sort by:' || !sortSelect.value)) {
            sortSelect.value = 'Newest Case';
        }

        if (window.initCustomSelects) window.initCustomSelects();
        if (sortSelect && sortSelect._customSelect) {
            sortSelect._customSelect.sync();
        }

        renderPage('completed');
        renderPage('disputes');
    }

    function handleHighlight(targetId) {
        if (!targetId) {
            const params = new URLSearchParams(window.location.search);
            targetId = params.get('highlight') || params.get('highlight_case') || params.get('case_id');
        }
        if (!targetId) return false;

        const hlLower = String(targetId).trim().toLowerCase();
        const tbody = document.getElementById('table-body');
        if (!tbody) return false;

        const allRows = Array.from(tbody.querySelectorAll('tr.record-row'));
        const targetRow = allRows.find(row => {
            const id = (row.dataset.id || '').toLowerCase();
            const pat = (row.dataset.patient || '').toLowerCase();
            const name = (row.dataset.name || '').toLowerCase();
            return id === hlLower || pat === hlLower || (hlLower && id.includes(hlLower)) || name.includes(hlLower);
        });

        if (!targetRow) return false;

        const searchInput = document.getElementById('search-input');
        if (searchInput) searchInput.value = '';

        const filtered = getFilteredRows('completed');
        const targetIdx = filtered.indexOf(targetRow);
        if (targetIdx !== -1) {
            currentPages.completed = Math.floor(targetIdx / ROWS_PER_PAGE) + 1;
        }
        renderPage('completed');

        setTimeout(() => {
            targetRow.style.display = '';
            targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

            targetRow.classList.add('transition-all', 'duration-300', 'ring-2', 'ring-amber-400', 'ring-offset-1');
            targetRow.style.transition = 'background-color 0.4s ease';
            targetRow.style.backgroundColor = '#fef08a';
            setTimeout(() => {
                targetRow.style.backgroundColor = '#fde047';
                setTimeout(() => {
                    targetRow.style.backgroundColor = '#fef08a';
                    setTimeout(() => {
                        targetRow.style.backgroundColor = '#fde047';
                        setTimeout(() => {
                            targetRow.style.transition = 'background-color 1.5s ease';
                            targetRow.style.backgroundColor = '';
                            targetRow.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-1');
                        }, 400);
                    }, 300);
                }, 300);
            }, 200);

            const existingBanner = document.getElementById('highlight-banner');
            if (existingBanner) existingBanner.remove();

            const banner = document.createElement('div');
            banner.id = 'highlight-banner';
            banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Case <strong>${targetId}</strong> is highlighted below.</span></div>`;
            banner.style.cssText = 'margin:1rem 0;padding:0.65rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;box-shadow:0 2px 8px rgba(245,158,11,0.08);';
            const header = document.querySelector('h2');
            if (header && header.parentElement) {
                header.parentElement.insertAdjacentElement('afterend', banner);
            }
            setTimeout(() => {
                banner.style.transition = 'opacity 0.5s';
                banner.style.opacity = '0';
                setTimeout(() => banner.remove(), 500);
            }, 6000);
        }, 100);

        return true;
    }

    window.handlePageHighlight = handleHighlight;

    // Run immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            init();
            handleHighlight();
        });
    } else {
        init();
        handleHighlight();
    }
})();
