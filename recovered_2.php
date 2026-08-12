    // Initial sorting and highlight logic on load
    document.addEventListener('DOMContentLoaded', () => {
        // Default Sort & Filter
        setTimeout(() => {
            applyFilters();
        }, 100);

        // ── Highlight row from notification ───────────────────────────────
        setTimeout(() => {
            const params = new window.URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
                            setTimeout(() => {
                                targetRow.style.backgroundColor = '#fde047';
                                setTimeout(() => {
                                    targetRow.style.transition = 'background-color 1.5s ease';
                                    targetRow.style.backgroundColor = '';
                                }, 300);
                            }, 300);
                        }, 300);
                    }, 200);

                    const banner = document.createElement('div');
                    banner.id = 'highlight-banner';
                    banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Case <strong>${highlightId}</strong> is highlighted below.</span></div>`;
                    banner.style.cssText = 'margin-top:1rem;padding:0.75rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
                    const header = document.querySelector('h2');
                    if (header && header.parentElement) {
                        header.parentElement.insertAdjacentElement('afterend', banner);
                    }
                    setTimeout(() => {
                        banner.style.transition = 'opacity 0.5s';
