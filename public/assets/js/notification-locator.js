/**
 * CitiLife Notification Locator & Highlighting Engine
 * Universally locates and visually highlights records navigated to from notifications.
 */
(function () {
    'use strict';

    function cleanTargetString(str) {
        if (!str) return '';
        return String(str).trim().toLowerCase();
    }

    function showHighlightBanner(targetId, customLabel) {
        if (!targetId && !customLabel) return;
        const existing = document.getElementById('highlight-banner');
        if (existing) existing.remove();

        const label = customLabel || targetId;

        const banner = document.createElement('div');
        banner.id = 'highlight-banner';
        banner.innerHTML = `
            <div style="display:flex;align-items:center;gap:0.5rem;white-space:nowrap;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-amber-600">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span style="font-size:0.8125rem;">Navigated from notification &mdash; <strong style="font-weight:700;color:#78350f;">${label}</strong> is highlighted below.</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#92400e;padding:0 2px;font-size:16px;line-height:1;margin-left:0.5rem;display:flex;align-items:center;" title="Dismiss">&times;</button>
        `;
        banner.style.cssText = 'margin-left:auto;padding:0.5rem 0.85rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.8125rem;font-weight:500;display:inline-flex;align-items:center;gap:0.5rem;box-shadow:0 1px 3px rgba(245,158,11,0.08);transition:opacity 0.4s ease;width:fit-content;max-width:100%;flex-shrink:0;';

        const main = document.querySelector('main') || document.querySelector('#app') || document.body;
        const heading = main.querySelector('h1, h2');
        const headerFlex = main.querySelector('.flex.items-center.justify-between') || (heading ? heading.closest('.flex') : null);

        if (headerFlex && heading && headerFlex.contains(heading)) {
            let titleWrapper = heading.parentElement;
            while (titleWrapper && titleWrapper.parentElement !== headerFlex) {
                titleWrapper = titleWrapper.parentElement;
            }
            if (titleWrapper && titleWrapper !== headerFlex) {
                titleWrapper.insertAdjacentElement('afterend', banner);
            } else {
                headerFlex.appendChild(banner);
            }
        } else if (heading && heading.parentElement) {
            heading.parentElement.insertAdjacentElement('afterend', banner);
        } else {
            main.insertBefore(banner, main.firstChild);
        }

        setTimeout(() => {
            banner.style.transition = 'opacity 0.5s ease';
            banner.style.opacity = '0';
            setTimeout(() => banner.remove(), 500);
        }, 6000);
    }
    window.showHighlightBanner = showHighlightBanner;

    function animateElement(element) {
        if (!element) return;

        // Ensure we animate only the row or card, never an entire table or card wrapper
        const target = element.closest('tr') || element;

        // Clear any previous highlights on all other elements to guarantee single highlight
        document.querySelectorAll('.ring-2.ring-amber-400, tr[data-highlighted]').forEach(el => {
            if (el !== target) {
                el.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-1');
                el.style.backgroundColor = '';
                el.removeAttribute('data-highlighted');
            }
        });
        target.setAttribute('data-highlighted', 'true');

        // Scroll to element
        setTimeout(() => {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 80);

        const originalBg = target.style.backgroundColor;
        const originalTransition = target.style.transition;

        target.style.transition = 'all 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
        target.classList.add('ring-2', 'ring-amber-400', 'ring-offset-1');
        target.style.backgroundColor = '#fef08a';

        setTimeout(() => {
            target.style.backgroundColor = '#fde047';
            setTimeout(() => {
                target.style.backgroundColor = '#fef08a';
                setTimeout(() => {
                    target.style.backgroundColor = '#fde047';
                    setTimeout(() => {
                        target.style.transition = 'all 1.5s ease';
                        target.style.backgroundColor = originalBg;
                        target.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-1');
                        setTimeout(() => {
                            target.style.transition = originalTransition;
                        }, 1500);
                    }, 400);
                }, 350);
            }, 350);
        }, 250);
    }

    function cleanUrlParams() {
        try {
            const cleanUrl = new URL(window.location.href);
            let changed = false;
            ['highlight', 'highlight_case', 'highlight_req', 'highlight_id', 'is_new'].forEach(p => {
                if (cleanUrl.searchParams.has(p)) {
                    cleanUrl.searchParams.delete(p);
                    changed = true;
                }
            });
            if (changed) {
                window.history.replaceState({}, document.title, cleanUrl.toString());
                if (window.__APP__) {
                    window.__APP__.currentPath = cleanUrl.pathname + cleanUrl.search;
                }
            }
        } catch (e) {}
    }

    function universalLocate(targetId) {
        if (!targetId) return false;
        const normalized = cleanTargetString(targetId);

        // 1. Try page-specific highlight function first if registered
        if (typeof window.handlePageHighlight === 'function') {
            try {
                if (window.handlePageHighlight(targetId)) {
                    cleanUrlParams();
                    return true;
                }
            } catch (err) {
                console.warn('Page-specific handlePageHighlight error:', err);
            }
        }

        // 2. Search strictly for row or item candidates in DOM (never whole table cards or wrappers)
        const candidates = Array.from(document.querySelectorAll(
            'tbody tr, tr.record-row, tr.pending-row, tr.history-row, tr.dispute-table-row, [data-record-item], .feedback-item'
        ));

        let match = null;

        // Exact match check on specific rows/cards
        for (const el of candidates) {
            if (el.tagName === 'TABLE' || el.tagName === 'TBODY' || (el.id && el.id.includes('table-card'))) continue;

            const id = cleanTargetString(el.dataset?.id);
            const caseNum = cleanTargetString(el.dataset?.case);
            const caseId = cleanTargetString(el.dataset?.caseId);
            const reqNum = cleanTargetString(el.dataset?.requestNumber || el.dataset?.request);
            const patNum = cleanTargetString(el.dataset?.patient || el.dataset?.patientNumber);
            const disputeId = cleanTargetString(el.dataset?.disputeId);

            if (id === normalized || caseNum === normalized || caseId === normalized || reqNum === normalized || patNum === normalized || disputeId === normalized) {
                match = el.closest('tr') || el;
                break;
            }
        }

        // Substring / text search fallback (only for identifiers of at least 4 characters)
        if (!match && normalized.length >= 4) {
            for (const el of candidates) {
                if (el.tagName === 'TABLE' || el.tagName === 'TBODY' || (el.id && el.id.includes('table-card'))) continue;
                const searchStr = cleanTargetString(el.dataset?.search || el.dataset?.case);
                if (searchStr && searchStr.includes(normalized)) {
                    match = el.closest('tr') || el;
                    break;
                }
            }
        }

        if (!match && normalized.length >= 4) {
            // Search cell contents within table rows
            const rows = Array.from(document.querySelectorAll('tbody tr'));
            for (const r of rows) {
                const text = cleanTargetString(r.textContent);
                if (text && text.includes(normalized)) {
                    match = r;
                    break;
                }
            }
        }

        if (match) {
            // Check if match is inside a tab that is currently hidden (excluding tr itself)
            const hiddenContainer = match.parentElement ? match.parentElement.closest('.hidden, [style*="display: none"]') : null;
            if (hiddenContainer) {
                const containerId = hiddenContainer.id;
                let tabSwitched = false;
                if (containerId) {
                    // Try to trigger matching tab button
                    const tabBtn = document.querySelector(`[onclick*="${containerId}"], #tab-${containerId.replace('content-', '')}, [data-tab="${containerId}"]`);
                    if (tabBtn) {
                        tabBtn.click();
                        tabSwitched = true;
                    } else if (typeof window.switchTab === 'function') {
                        const tabKey = containerId.replace('content-', '').replace('tab-', '').replace('-table-card', '').replace('-controls-bar', '');
                        window.switchTab(tabKey);
                        tabSwitched = true;
                    }
                }
                // Do not blindly unhide table cards or controls bars without switching tabs,
                // which would cause multiple tables to be rendered simultaneously.
                if (!tabSwitched && !containerId?.includes('table-card') && !containerId?.includes('controls-bar')) {
                    hiddenContainer.classList.remove('hidden');
                    hiddenContainer.style.display = '';
                }
            }

            const friendlyLabel = match.dataset?.case || match.dataset?.requestNumber || match.dataset?.id || targetId;
            showHighlightBanner(targetId, friendlyLabel);
            animateElement(match);
            cleanUrlParams();
            return true;
        }

        return false;
    }

    // Expose global locator
    window.locateAndHighlight = function (targetId) {
        if (!targetId) {
            const params = new URLSearchParams(window.location.search);
            targetId = params.get('highlight') || params.get('highlight_case') || params.get('highlight_req') || params.get('case_number') || params.get('case_id');
        }
        if (!targetId) return false;

        return universalLocate(targetId);
    };

    // Auto-execute on page load
    function initAutoHighlight() {
        const params = new URLSearchParams(window.location.search);
        const targetId = params.get('highlight') || params.get('highlight_case') || params.get('highlight_req');
        if (targetId) {
            // Slight delay allows Vue/dynamic tables to mount and render first page
            setTimeout(() => {
                window.locateAndHighlight(targetId);
            }, 300);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoHighlight);
    } else {
        initAutoHighlight();
    }
})();
