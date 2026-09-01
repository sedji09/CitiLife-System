(function () {
    if (window.__RECORDS_INIT_DONE__) return;
    
    // Prevent running on non-my-records pages
    if (!document.getElementById('my-records-wrapper')) {
        return;
    }
    
    window.__RECORDS_INIT_DONE__ = true;

    const ROWS_PER_PAGE = 10;

    // State per tab
    const state = {
        completed: { page: 1 },
        rejected: { page: 1 },
        cancelled: { page: 1 },
        disputes: { page: 1 }
    };

    // Default tab (Stealth Mode: capture ?tab and then clean URL)
    const urlParams = new URLSearchParams(window.location.search);
    let currentTab = urlParams.get('tab') || 'completed';

    // Immediately clean the URL bar to hide the tab parameter
    if (window.history && window.history.replaceState) {
        const cleanUrl = new URL(window.location.href);
        if (cleanUrl.searchParams.has('tab')) {
            cleanUrl.searchParams.delete('tab');
            window.history.replaceState(null, null, cleanUrl.toString());
        }
    }

    // Global exposed function for tab switching
    window.switchPatientTab = function (tabId) {
        currentTab = tabId;

        // Hide all contents
        ['completed', 'rejected', 'cancelled', 'disputes'].forEach(t => {
            const el = document.getElementById(`tab-${t}-content`);
            if (el) el.classList.add('hidden');

            // Reset tab button styling
            const btn = document.getElementById(`tab-patient-${t}-btn`);
            if (btn) {
                btn.classList.remove('border-red-600', 'text-red-600', 'font-bold');
                btn.classList.add('border-transparent', 'text-gray-500', 'font-semibold', 'hover:text-gray-700');
            }
        });

        // Show active content
        const activeEl = document.getElementById(`tab-${tabId}-content`);
        if (activeEl) activeEl.classList.remove('hidden');

        // Style active button
        const activeBtn = document.getElementById(`tab-patient-${tabId}-btn`);
        if (activeBtn) {
            activeBtn.classList.remove('border-transparent', 'text-gray-500', 'font-semibold', 'hover:text-gray-700');
            activeBtn.classList.add('border-red-600', 'text-red-600', 'font-bold');
        }

        // Re-render the active tab
        renderTab(tabId);
    };

    function getFilteredItems(tabId) {
        let containerId = '';
        let searchId = '';
        let filterId = '';
        let sortId = '';

        if (tabId === 'completed') {
            containerId = 'completed-cards-container';
            searchId = 'completed-search-input';
            filterId = 'completed-branch-filter';
            sortId = 'completed-sort-date';
        } else if (tabId === 'rejected') {
            containerId = 'rejected-cards-container';
            searchId = 'rejected-search-input';
        } else if (tabId === 'cancelled') {
            containerId = 'cancelled-cards-container';
            searchId = 'cancelled-search-input';
        } else if (tabId === 'disputes') {
            containerId = 'disputes-cards-container';
        }

        const container = document.getElementById(containerId);
        if (!container) return [];

        const searchInput = document.getElementById(searchId);
        const branchSelect = document.getElementById(filterId);
        const sortSelect = document.getElementById(sortId);

        const search = (searchInput?.value || '').toLowerCase().trim();
        const branch = (branchSelect?.value || 'all branches').toLowerCase().trim();
        const sort = sortSelect?.value || 'Newest Case';

        // Select items in container based on tab
        const items = Array.from(container.querySelectorAll(
            tabId === 'completed' ? '.completed-card' :
                tabId === 'rejected' ? '.rejected-card' :
                    tabId === 'cancelled' ? '.cancelled-card' : '.dispute-card'
        ));

        // Sorting (only implemented for completed and rejected via data-date if present)
        items.sort((a, b) => {
            const dateA = new Date((a.dataset.date || '').replace(' ', 'T')).getTime();
            const dateB = new Date((b.dataset.date || '').replace(' ', 'T')).getTime();
            if (isNaN(dateA) || isNaN(dateB)) return 0;
            return sort === 'Newest Case' ? dateB - dateA : dateA - dateB;
        });

        // We must re-append to re-order the DOM
        items.forEach(item => container.appendChild(item));

        return items.filter(item => {
            const id = (item.dataset.id || '').toLowerCase();
            const exam = (item.dataset.exam || '').toLowerCase();
            const itemBranch = (item.dataset.branch || '').toLowerCase().trim();

            const matchSearch = !search || id.includes(search) || exam.includes(search);
            const isAllBranch = branch === 'all branches' || branch === 'all' || branch === '';
            const matchBranch = isAllBranch || itemBranch === branch;

            return matchSearch && matchBranch;
        });
    }

    function renderTab(tabId) {
        const items = getFilteredItems(tabId);
        const totalItems = items.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / ROWS_PER_PAGE));

        if (state[tabId].page > totalPages) state[tabId].page = totalPages;
        if (state[tabId].page < 1) state[tabId].page = 1;

        const startIdx = (state[tabId].page - 1) * ROWS_PER_PAGE;
        const endIdx = startIdx + ROWS_PER_PAGE;
        const visibleItems = new Set(items.slice(startIdx, endIdx));

        const container = document.getElementById(`${tabId}-cards-container`);
        if (container) {
            const allItems = Array.from(container.querySelectorAll(`.${tabId === 'disputes' ? 'dispute' : tabId}-card`));
            allItems.forEach(item => {
                item.style.display = visibleItems.has(item) ? '' : 'none';
            });

            let emptyMsg = document.getElementById(`${tabId}-empty-msg`);
            if (totalItems === 0 && allItems.length > 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.id = `${tabId}-empty-msg`;
                    emptyMsg.className = 'text-center py-10 text-gray-500 bg-white rounded-xl border border-gray-200';
                    emptyMsg.innerHTML = `No records match your filters.`;
                    container.appendChild(emptyMsg);
                } else {
                    emptyMsg.style.display = '';
                }
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
        }

        updatePaginationUI(tabId, totalItems, totalPages);
    }

    function updatePaginationUI(tabId, totalItems, totalPages) {
        const prevBtn = document.getElementById(`${tabId}-prev-btn`);
        const nextBtn = document.getElementById(`${tabId}-next-btn`);
        const pageInfo = document.getElementById(`${tabId}-page-info`);
        const countInfo = document.getElementById(`${tabId}-count-info`);

        if (!pageInfo) return; // If pagination not present for this tab

        const startDisplay = totalItems === 0 ? 0 : (state[tabId].page - 1) * ROWS_PER_PAGE + 1;
        const endDisplay = Math.min(state[tabId].page * ROWS_PER_PAGE, totalItems);

        pageInfo.textContent = `Page ${state[tabId].page} of ${totalPages}`;
        if (countInfo) {
            countInfo.textContent = totalItems === 0
                ? 'No records'
                : `Showing ${startDisplay}–${endDisplay} of ${totalItems} records`;
        }

        if (prevBtn) prevBtn.disabled = state[tabId].page <= 1;
        if (nextBtn) nextBtn.disabled = state[tabId].page >= totalPages;
    }

    // Event Listeners
    document.addEventListener('input', (e) => {
        if (['completed-search-input', 'rejected-search-input', 'cancelled-search-input'].includes(e.target.id)) {
            state[currentTab].page = 1;
            renderTab(currentTab);
        }
    });

    document.addEventListener('change', (e) => {
        if (['completed-branch-filter', 'completed-sort-date'].includes(e.target.id)) {
            state[currentTab].page = 1;
            renderTab(currentTab);
        }
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;

        const match = btn.id.match(/^(completed|rejected|cancelled|disputes)-(prev|next)-btn$/);
        if (match) {
            const tabId = match[1];
            const action = match[2];
            const items = getFilteredItems(tabId);
            const totalPages = Math.ceil(items.length / ROWS_PER_PAGE);

            if (action === 'prev' && state[tabId].page > 1) {
                state[tabId].page--;
                renderTab(tabId);
            } else if (action === 'next' && state[tabId].page < totalPages) {
                state[tabId].page++;
                renderTab(tabId);
            }
        }
    });

    function init() {
        renderTab('completed');
        renderTab('rejected');
        renderTab('cancelled');
        renderTab('disputes');
        // By default switch to currentTab from URL
        window.switchPatientTab(currentTab);

        // Highlighting for case params
        handleHighlight();

        // Real-time polling for disputes
        setInterval(() => {
            if (currentTab === 'disputes') {
                fetch(window.location.href)
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContainer = doc.getElementById('disputes-cards-container');
                        const oldContainer = document.getElementById('disputes-cards-container');
                        if (newContainer && oldContainer) {
                            oldContainer.innerHTML = newContainer.innerHTML;
                            if (window.lucide) lucide.createIcons({ root: oldContainer });
                        }
                    })
                    .catch(err => console.error('Dispute polling error:', err));
            }
        }, 5000);
    }

    function handleHighlight() {
        const urlParams = new URLSearchParams(window.location.search);
        const highlightId = urlParams.get('highlight_case') || urlParams.get('highlight_dispute_id');
        if (!highlightId) return;

        // Determine which tab has the case
        let foundTab = null;
        let foundItem = null;
        ['completed', 'rejected', 'cancelled', 'disputes'].forEach(tabId => {
            if (foundTab) return;
            const container = document.getElementById(`${tabId}-cards-container`);
            if (container) {
                const item = Array.from(container.querySelectorAll(`.${tabId === 'disputes' ? 'dispute' : tabId}-card`))
                    .find(c => c.dataset.caseId === highlightId || c.dataset.id === highlightId);
                if (item) {
                    foundTab = tabId;
                    foundItem = item;
                }
            }
        });

        if (foundTab && foundItem) {
            window.switchPatientTab(foundTab);

            const items = getFilteredItems(foundTab);
            const index = items.indexOf(foundItem);

            if (index !== -1) {
                state[foundTab].page = Math.floor(index / ROWS_PER_PAGE) + 1;
                renderTab(foundTab);

                setTimeout(() => {
                    const el = document.querySelector(`.${foundTab === 'disputes' ? 'dispute' : foundTab}-card[data-case-id="${highlightId}"]`) ||
                        document.querySelector(`.${foundTab === 'disputes' ? 'dispute' : foundTab}-card[data-id="${highlightId}"]`);

                    if (el && el.style.display !== 'none') {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        const isNew = urlParams.get('is_new') === '1';
                        
                        if (isNew) {
                            setTimeout(() => {
                                el.classList.add('scale-[1.02]', 'shadow-xl', 'z-10', 'relative', 'transition-all', 'duration-300');
                                el.style.backgroundColor = '#fef08a';
                                setTimeout(() => {
                                    el.style.backgroundColor = '#fde047';
                                    setTimeout(() => {
                                        el.style.backgroundColor = '#fef08a';
                                        setTimeout(() => {
                                            el.style.backgroundColor = '';
                                            el.classList.remove('scale-[1.02]', 'shadow-xl', 'z-10', 'relative', 'transition-all', 'duration-300');
                                        }, 500);
                                    }, 500);
                                }, 400);
                            }, 600);
                        }
                    }

                    const newUrl = new URL(window.location);
                    newUrl.searchParams.delete('highlight_case');
                    newUrl.searchParams.delete('highlight_dispute_id');
                    newUrl.searchParams.delete('is_new');
                    window.history.replaceState({}, document.title, newUrl.toString());
                }, 200);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('realtime:updated', () => {
        renderTab(currentTab);
    });

})();

window.showExpiredAlert = function (e, contacts = []) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const contactBtn = document.getElementById('expired-alert-contact-btn');
    if (contacts && contacts.length > 0) {
        contactBtn.setAttribute('onclick', `window.showContactOptions(${JSON.stringify(contacts)}); document.getElementById('expired-alert-modal').classList.remove('show'); return false;`);
        contactBtn.href = "#";
        contactBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> Contact Clinic';
        contactBtn.style.display = 'inline-flex';
    } else {
        contactBtn.style.display = 'none';
    }
    document.getElementById('expired-alert-modal').classList.add('show');
};

window.showContactOptions = function (numbers) {
    if (!numbers || numbers.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Contact Info',
            text: 'There is no contact information available for this clinic at the moment.',
            confirmButtonColor: '#dc2626',
            customClass: { popup: 'rounded-2xl' }
        });
        return;
    }

    let html = '<div class="flex flex-col gap-3 mt-2">';
    numbers.forEach(num => {
        html += `<a href="tel:${num}" class="flex items-center justify-center gap-2 p-3 rounded-xl border border-gray-200 hover:bg-red-50 hover:border-red-200 hover:text-red-600 text-gray-700 font-bold transition shadow-sm" style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> 
                ${num}
            </a>`;
    });
    html += '</div>';

    Swal.fire({
        title: 'Contact Clinic',
        html: html,
        showConfirmButton: false,
        showCloseButton: true,
        didOpen: () => {
            const closeBtn = Swal.getCloseButton();
            if (closeBtn) closeBtn.blur();
        },
        customClass: {
            popup: 'rounded-2xl',
            title: 'text-xl font-bold text-gray-800',
            closeButton: '!outline-none !ring-0 !border-0 !shadow-none !text-gray-500 hover:!text-gray-800'
        }
    });
};

document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('expired')) {
        window.showExpiredAlert();
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('expired');
        window.history.replaceState({}, document.title, newUrl.toString());
    }
});

// ── Feedback Modal Logic ──────────────────────────────────────────────────
window.openFeedbackModal = function (caseId, caseNumber, examType) {
    const modal = document.getElementById('feedback-modal');
    const content = document.getElementById('feedback-modal-content');
    if (!modal || !content) return;

    const form = document.getElementById('feedback-form');
    if (form) form.reset();
    document.getElementById('feedback-case-id').value = caseId;
    document.getElementById('feedback-rating-input').value = "";
    updateFeedbackStars(0);

    document.getElementById('feedback-case-number').textContent = caseNumber;

    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.classList.remove('opacity-0');
    content.classList.remove('scale-95');
};

window.closeFeedbackModal = function () {
    const modal = document.getElementById('feedback-modal');
    const content = document.getElementById('feedback-modal-content');
    if (!modal || !content) return;

    modal.classList.add('opacity-0');
    content.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
};

const feedbackTexts = {
    1: "Poor", 2: "Fair", 3: "Good", 4: "Very Good", 5: "Excellent"
};

window.updateFeedbackStars = function (value) {
    const stars = document.querySelectorAll('.feedback-star-btn');
    const ratingText = document.getElementById('feedback-rating-text');

    stars.forEach(star => {
        const rating = parseInt(star.getAttribute('data-rating'));
        if (rating <= value) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-yellow-400');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-300');
        }
    });
    if (ratingText) {
        ratingText.textContent = value == 0 ? "Select a rating" : feedbackTexts[value];
    }
};

document.addEventListener('click', function (e) {
    const starBtn = e.target.closest('.feedback-star-btn');
    if (starBtn) {
        const value = starBtn.getAttribute('data-rating');
        document.getElementById('feedback-rating-input').value = value;
        updateFeedbackStars(value);
    }
});

window.submitFeedbackForm = function () {
    const feedbackForm = document.getElementById('feedback-form');
    if (!feedbackForm) return;

    const submitBtn = document.getElementById('feedback-submit-btn');
    const rating = document.getElementById('feedback-rating-input').value;

    if (!rating) {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Please provide a star rating to let us know how we did!' });
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Submitting...';
    if (window.lucide) lucide.createIcons();

    const formData = new FormData(feedbackForm);

    fetch(window.__APP__.basePath + '/app/api/submit_feedback.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Feedback';
            if (window.lucide) lucide.createIcons();

            if (data.success) {
                closeFeedbackModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Thank you!',
                    text: 'Your feedback has been submitted successfully.',
                    confirmButtonColor: '#dc2626',
                    customClass: {
                        popup: 'rounded-2xl',
                        title: 'text-xl font-bold text-gray-800'
                    }
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to submit feedback' });
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Feedback';
            if (window.lucide) lucide.createIcons();
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'A network error occurred. Please try again.' });
        });
};

// ── Dispute / Report Error Logic ──────────────────────────────────────────────────
window.toggleDisputeFields = function () {
    const category = document.getElementById('dispute-category').value;
    const demoContainer = document.getElementById('demographic-options-container');
    const descContainer = document.getElementById('general-description-container');
    const descTextarea = document.getElementById('dispute-description');

    if (category === 'demographic_error') {
        demoContainer.classList.remove('hidden');
        descContainer.classList.add('hidden');
        descTextarea.removeAttribute('required');
    } else if (category === 'exam_details_error' || category === 'findings_error') {
        demoContainer.classList.add('hidden');
        descContainer.classList.remove('hidden');
        descTextarea.setAttribute('required', 'required');
        ['chk-first-name', 'chk-last-name', 'chk-age', 'chk-sex'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.checked = false;
        });
        const infoContainer = document.getElementById('correction-inputs-container');
        if (infoContainer) infoContainer.classList.add('hidden');
    } else if (category === 'both_error') {
        demoContainer.classList.remove('hidden');
        descContainer.classList.remove('hidden');
        descTextarea.setAttribute('required', 'required');
    } else {
        demoContainer.classList.add('hidden');
        descContainer.classList.add('hidden');
        descTextarea.removeAttribute('required');
    }
};

window.toggleCorrectionInputs = function () {
    const ids = ['chk-first-name', 'chk-last-name', 'chk-age', 'chk-sex'];
    let anyChecked = false;

    ids.forEach(id => {
        if (document.getElementById(id)?.checked) {
            anyChecked = true;
        }
    });

    const infoContainer = document.getElementById('correction-inputs-container');
    if (infoContainer) {
        if (anyChecked) {
            infoContainer.classList.remove('hidden');
        } else {
            infoContainer.classList.add('hidden');
        }
    }
};

window.openDisputeModal = function (caseId, caseNumber, examType) {
    const modal = document.getElementById('dispute-modal');
    const content = document.getElementById('dispute-modal-content');
    if (!modal) { alert("dispute-modal NOT FOUND!"); return; }
    if (!content) { alert("dispute-modal-content NOT FOUND!"); return; }

    document.getElementById('dispute-case-id').value = caseId;
    document.getElementById('dispute-case-number').textContent = caseNumber;
    document.getElementById('dispute-exam-type').textContent = examType;
    document.getElementById('dispute-category').value = '';
    const descTextarea = document.getElementById('dispute-description');
    if (descTextarea) descTextarea.value = '';

    ['chk-first-name', 'chk-last-name', 'chk-age', 'chk-sex'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.checked = false;
    });
    toggleDisputeFields();
    
    // Hide info alert on modal open
    const infoContainer = document.getElementById('correction-inputs-container');
    if (infoContainer) infoContainer.classList.add('hidden');

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

    if (category === 'findings_error' || category === 'both_error') {
        const desc = document.getElementById('dispute-description').value.trim();
        if (!desc && category === 'findings_error') {
            Swal.fire({
                icon: 'warning',
                title: 'Required Field',
                text: 'Please provide details of the correction needed.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }
        finalDescription += desc ? `Findings Note:\n  • ${desc}\n\n` : '';
    }

    if (category === 'demographic_error' || category === 'both_error') {
        const items = [];
        if (document.getElementById('chk-first-name')?.checked) items.push(`First Name`);
        if (document.getElementById('chk-last-name')?.checked) items.push(`Last Name`);
        if (document.getElementById('chk-age')?.checked) items.push(`Age`);
        if (document.getElementById('chk-sex')?.checked) items.push(`Sex`);

        if (items.length === 0 && category === 'demographic_error') {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Details',
                text: 'Pumili ng checkbox na naglalaman ng maling detalye.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }
        
        if (items.length > 0) {
            finalDescription += `Wrong Patient Info:\n  • ${items.join(', ')}\n`;
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

    fetch(window.__APP__.basePath + '/app/api/disputes.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Error Report';
            if (window.lucide) lucide.createIcons();

            if (data.success) {
                closeDisputeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Report Submitted!',
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
            btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Error Report';
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Network connection error.' });
        });
};


