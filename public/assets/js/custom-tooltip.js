/**
 * CitiLife Global Custom Tooltips
 * Intercepts native browser [title] tooltips system-wide and displays
 * an elegant, floating card-style tooltip with bold title & subtle description.
 */
(function (window, document) {
    'use strict';

    let tooltipEl = null;
    let tooltipTitle = null;
    let tooltipDesc = null;
    let currentTarget = null;
    let showTimer = null;

    function createTooltipElement() {
        if (tooltipEl) return;
        tooltipEl = document.createElement('div');
        tooltipEl.className = 'c-tooltip';
        tooltipEl.id = 'c-global-tooltip';

        const content = document.createElement('div');
        content.className = 'c-tooltip-content';

        tooltipTitle = document.createElement('div');
        tooltipTitle.className = 'c-tooltip-title';

        tooltipDesc = document.createElement('div');
        tooltipDesc.className = 'c-tooltip-desc';

        content.appendChild(tooltipTitle);
        content.appendChild(tooltipDesc);
        tooltipEl.appendChild(content);

        document.body.appendChild(tooltipEl);
    }

    function hideTooltip() {
        if (showTimer) {
            clearTimeout(showTimer);
            showTimer = null;
        }
        if (tooltipEl) {
            tooltipEl.classList.remove('c-tooltip-visible');
        }
        currentTarget = null;
    }

    function showTooltip(el, text) {
        if (!text || !el) return;
        createTooltipElement();

        // Extract title & description if present
        let titleText = text.trim();
        let descText = (el.getAttribute('data-tooltip-desc') || el.getAttribute('data-desc') || '').trim();

        if (!descText && titleText.includes('\n')) {
            const parts = titleText.split('\n');
            titleText = parts[0].trim();
            descText = parts.slice(1).join(' ').trim();
        } else if (!descText && titleText.includes('::')) {
            const parts = titleText.split('::');
            titleText = parts[0].trim();
            descText = parts.slice(1).join(' ').trim();
        }

        tooltipTitle.textContent = titleText;

        if (descText) {
            tooltipDesc.textContent = descText;
            tooltipDesc.style.display = 'block';
            tooltipEl.classList.remove('c-tooltip-single');
        } else {
            tooltipDesc.textContent = '';
            tooltipDesc.style.display = 'none';
            tooltipEl.classList.add('c-tooltip-single');
        }

        currentTarget = el;

        // Danger accent for delete/danger actions
        const lowerCombined = (titleText + ' ' + descText).toLowerCase();
        if (lowerCombined.includes('delete') || lowerCombined.includes('remove') || lowerCombined.includes('danger')) {
            tooltipEl.classList.add('c-tooltip-danger');
        } else {
            tooltipEl.classList.remove('c-tooltip-danger');
        }

        // Measure tooltip
        tooltipEl.style.left = '0px';
        tooltipEl.style.top = '0px';
        tooltipEl.style.display = 'block';

        const targetRect = el.getBoundingClientRect();
        const tooltipRect = tooltipEl.getBoundingClientRect();

        // Preferred placement:
        // If data-tooltip-placement explicitly provided: use it
        // Otherwise: if space on top, prefer 'top'; else 'bottom'
        let placement = el.getAttribute('data-tooltip-placement');
        if (!placement) {
            if (targetRect.top >= tooltipRect.height + 12) {
                placement = 'top';
            } else {
                placement = 'bottom';
            }
        }

        tooltipEl.setAttribute('data-placement', placement);

        let top = 0;
        let left = 0;
        const offset = 6;

        if (placement === 'top') {
            top = targetRect.top - tooltipRect.height - offset;
            left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
        } else if (placement === 'bottom') {
            top = targetRect.bottom + offset;
            left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
        } else if (placement === 'left') {
            top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
            left = targetRect.left - tooltipRect.width - offset;
        } else if (placement === 'right') {
            top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
            left = targetRect.right + offset;
        }

        // Keep inside viewport horizontally
        const padding = 8;
        if (left < padding) left = padding;
        if (left + tooltipRect.width > window.innerWidth - padding) {
            left = window.innerWidth - tooltipRect.width - padding;
        }

        // Keep inside viewport vertically
        if (top < padding) top = padding;
        if (top + tooltipRect.height > window.innerHeight - padding) {
            top = window.innerHeight - tooltipRect.height - padding;
        }

        tooltipEl.style.top = `${Math.round(top)}px`;
        tooltipEl.style.left = `${Math.round(left)}px`;

        requestAnimationFrame(() => {
            if (currentTarget === el) {
                tooltipEl.classList.add('c-tooltip-visible');
            }
        });
    }

    // Event Delegation: Mouse Over
    document.addEventListener('mouseover', (e) => {
        const el = e.target.closest('[title], [data-tooltip]');
        if (!el) {
            hideTooltip();
            return;
        }

        // Migrate native title to data-tooltip to suppress ugly OS popup
        if (el.hasAttribute('title')) {
            const rawTitle = el.getAttribute('title');
            if (rawTitle && rawTitle.trim()) {
                el.setAttribute('data-tooltip', rawTitle.trim());
            }
            el.removeAttribute('title');
        }

        const text = el.getAttribute('data-tooltip');
        if (!text) {
            hideTooltip();
            return;
        }

        if (currentTarget === el) return;

        if (showTimer) clearTimeout(showTimer);
        // Snappy 90ms micro-delay
        showTimer = setTimeout(() => {
            showTooltip(el, text);
        }, 90);
    }, true);

    // Mouse Out
    document.addEventListener('mouseout', (e) => {
        const el = e.target.closest('[data-tooltip]');
        if (el && currentTarget === el) {
            hideTooltip();
        }
    }, true);

    // Hide on Click, Scroll, or Escape
    window.addEventListener('scroll', hideTooltip, true);
    document.addEventListener('click', hideTooltip, true);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') hideTooltip();
    });

})(window, document);
