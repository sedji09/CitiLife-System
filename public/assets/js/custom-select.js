/**
 * CitiLife Modern Custom Select
 * Transforms native HTML <select> elements into sleek, accessible, red-branded custom dropdowns.
 * Fully compatible with Vue.js, inline onchange, and native form POST submissions.
 */
(function (window, document) {
    'use strict';

    const CHEVRON_SVG = `<svg class="cs-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>`;
    const CHECK_SVG = `<svg class="cs-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

    let activeSelect = null;

    class CustomSelect {
        constructor(selectEl) {
            this.select = selectEl;
            if (!this.select || this.select._customSelect) return;

            // Skip if explicitly flagged or has no-custom-select class
            if (this.select.hasAttribute('data-no-custom') || 
                this.select.classList.contains('no-custom-select')) {
                return;
            }

            this.isOpen = false;
            this.init();
            this.select._customSelect = this;
        }

        init() {
            // Wrapper container
            this.wrapper = document.createElement('div');
            this.wrapper.className = 'cs-wrapper';

            // Transfer layout, width, flex, margin, and sizing classes from native select
            if (this.select.className) {
                const classes = this.select.className.split(/\s+/);
                classes.forEach(c => {
                    if (!c) return;
                    // Layout, sizing, flex, margin, visibility classes go to wrapper
                    if (/^(sm:|md:|lg:|xl:|2xl:)?(w-|min-w-|max-w-|flex-|shrink|grow|self-|m-|mx-|my-|mt-|mb-|ml-|mr-|hidden|block|inline|order-)/.test(c) ||
                        c === 'shrink-0' || c === 'flex-1' || c === 'flex-none' || c === 'w-full' || c === 'w-auto') {
                        this.wrapper.classList.add(c);
                    }
                    // Typography & aesthetic classes on trigger
                    if (/^(text-xs|text-sm|rounded-|font-)/.test(c)) {
                        this.wrapper.classList.add(c);
                    }
                    if (c === 'py-2' || c === 'text-xs') {
                        this.wrapper.classList.add('cs-compact');
                    }
                });
            }

            // Copy inline style dimensions if present
            if (this.select.style.width) this.wrapper.style.width = this.select.style.width;
            if (this.select.style.minWidth) this.wrapper.style.minWidth = this.select.style.minWidth;
            if (this.select.style.maxWidth) this.wrapper.style.maxWidth = this.select.style.maxWidth;
            if (this.select.style.flex) this.wrapper.style.flex = this.select.style.flex;

            // Insert wrapper before select and move select inside
            this.select.parentNode.insertBefore(this.wrapper, this.select);
            this.wrapper.appendChild(this.select);
            this.select.classList.add('cs-native-hidden');

            // Trigger Button
            this.trigger = document.createElement('button');
            this.trigger.type = 'button';
            this.trigger.className = 'cs-trigger';

            if (this.wrapper.classList.contains('cs-compact')) {
                this.trigger.classList.add('cs-compact');
            }

            if (this.select.classList.contains('rounded-lg')) {
                this.trigger.classList.add('rounded-lg');
            } else if (this.select.classList.contains('rounded-xl')) {
                this.trigger.classList.add('rounded-xl');
            } else if (this.select.classList.contains('rounded-full')) {
                this.trigger.classList.add('rounded-full');
            }

            this.label = document.createElement('span');
            this.label.className = 'cs-label';

            this.trigger.appendChild(this.label);
            this.trigger.insertAdjacentHTML('beforeend', CHEVRON_SVG);
            this.wrapper.appendChild(this.trigger);

            // Dropdown List
            this.dropdown = document.createElement('div');
            this.dropdown.className = 'cs-dropdown';
            this.wrapper.appendChild(this.dropdown);

            // Build Options
            this.buildOptions();

            // Check if inside overflow-hidden ancestor (modal/panel) - use fixed dropdown
            this._useFixed = !!this.select.closest('[class*="overflow-hidden"], [class*="overflow-y-auto"], [class*="overflow-auto"], .modal, [role="dialog"]');
            if (this._useFixed) {
                // Wrap dropdown content in a scroll container
                const scrollEl = document.createElement('div');
                scrollEl.className = 'cs-fixed-scroll';
                // Move existing children into scroll wrapper
                while (this.dropdown.firstChild) scrollEl.appendChild(this.dropdown.firstChild);
                this.dropdown.appendChild(scrollEl);
                this._scrollEl = scrollEl;

                document.body.appendChild(this.dropdown);
                this.dropdown.classList.add('cs-fixed-dropdown');
            }

            // Bind Events
            this.bindEvents();

            // Observe DOM mutations on select element (e.g. dynamically added options)
            this.observer = new MutationObserver(() => {
                this.buildOptions();
            });
            this.observer.observe(this.select, { childList: true, attributes: true, attributeFilter: ['disabled', 'value'] });
        }

        buildOptions() {
            if (this.select.disabled) {
                this.wrapper.classList.add('cs-disabled');
                if (this.trigger) this.trigger.disabled = true;
            } else {
                this.wrapper.classList.remove('cs-disabled');
                if (this.trigger) this.trigger.disabled = false;
            }

            const target = (this._useFixed && this._scrollEl) ? this._scrollEl : this.dropdown;
            target.innerHTML = '';
            const options = Array.from(this.select.options);
            const selectedOpt = this.select.options[this.select.selectedIndex] || options[0];

            this.label.textContent = selectedOpt ? selectedOpt.textContent.trim() : 'Select...';

            options.forEach((opt, index) => {
                const item = document.createElement('div');
                item.className = 'cs-option';
                if (opt.disabled) item.classList.add('cs-disabled');
                if (opt.selected) {
                    item.classList.add('cs-selected');
                    item.innerHTML = `<span>${opt.textContent.trim()}</span> ${CHECK_SVG}`;
                } else {
                    item.innerHTML = `<span>${opt.textContent.trim()}</span>`;
                }

                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (opt.disabled) return;
                    this.selectOption(opt.value, index);
                });

                target.appendChild(item);
            });
        }

        selectOption(value, index) {
            this.select.selectedIndex = index;
            this.select.value = value;

            // Dispatch native events so Vue, listeners & filters respond
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.select.dispatchEvent(new Event('input', { bubbles: true }));

            // Run inline onchange if defined
            if (typeof this.select.onchange === 'function') {
                this.select.onchange();
            }

            this.sync();
            this.close();
        }

        sync() {
            const selectedOpt = this.select.options[this.select.selectedIndex];
            if (selectedOpt) {
                this.label.textContent = selectedOpt.textContent.trim();
            }

            // Update cs-selected class in dropdown
            const items = this.dropdown.querySelectorAll('.cs-option');
            items.forEach((item, idx) => {
                const opt = this.select.options[idx];
                if (opt && opt.selected) {
                    item.classList.add('cs-selected');
                    if (!item.querySelector('.cs-check-icon')) {
                        item.insertAdjacentHTML('beforeend', CHECK_SVG);
                    }
                } else {
                    item.classList.remove('cs-selected');
                    const icon = item.querySelector('.cs-check-icon');
                    if (icon) icon.remove();
                }
            });
        }

        open() {
            if (this.select.disabled) return;

            // Close any other open custom select
            if (activeSelect && activeSelect !== this) {
                activeSelect.close();
            }

            if (this._useFixed) {
                // Position dropdown using fixed coords relative to trigger
                const rect = this.trigger.getBoundingClientRect();
                const safeMargin = 12;
                const maxW = window.innerWidth - safeMargin * 2;
                const dropW = Math.min(Math.max(rect.width, 220), maxW);
                this.dropdown.style.width = dropW + 'px';
                this.dropdown.style.minWidth = Math.min(rect.width, maxW) + 'px';
                this.dropdown.style.maxWidth = maxW + 'px';

                // Left: start at trigger left, clamp so it doesn't go off-screen
                let leftPos = rect.left;
                if (leftPos + dropW > window.innerWidth - safeMargin) {
                    leftPos = window.innerWidth - safeMargin - dropW;
                }
                if (leftPos < safeMargin) leftPos = safeMargin;
                this.dropdown.style.left = leftPos + 'px';
                this.dropdown.style.right = '';

                // Calculate available vertical space with margins
                const spaceBelow = window.innerHeight - rect.bottom - 16;
                const spaceAbove = rect.top - 16;
                const openUp = spaceBelow < 220 && spaceAbove > spaceBelow;
                const maxAvailableH = openUp ? spaceAbove : spaceBelow;
                const finalMaxH = Math.min(360, Math.max(180, maxAvailableH));

                if (this._scrollEl) {
                    this._scrollEl.style.maxHeight = finalMaxH + 'px';
                }

                if (openUp) {
                    this.dropdown.style.top = '';
                    this.dropdown.style.bottom = (window.innerHeight - rect.top + 5) + 'px';
                    this.dropdown.style.transformOrigin = 'bottom center';
                } else {
                    this.dropdown.style.bottom = '';
                    this.dropdown.style.top = (rect.bottom + 5) + 'px';
                    this.dropdown.style.transformOrigin = 'top center';
                }
            } else {
                // Check if dropdown fits below, otherwise open upward
                const rect = this.wrapper.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                if (spaceBelow < 300 && rect.top > 300) {
                    this.wrapper.classList.add('cs-dropup');
                } else {
                    this.wrapper.classList.remove('cs-dropup');
                }

                // Check horizontal space: if close to right edge, open towards left
                if (rect.right + 100 > window.innerWidth || rect.left + 220 > window.innerWidth) {
                    this.wrapper.classList.add('cs-dropdown-right');
                } else {
                    this.wrapper.classList.remove('cs-dropdown-right');
                }
            }

            this.wrapper.classList.add('cs-open');
            this.wrapper.style.zIndex = '9999';
            if (this._useFixed) {
                this.dropdown.classList.add('cs-visible');
                document.body.classList.add('cs-noscroll');
            }
            this.isOpen = true;
            activeSelect = this;
        }

        close() {
            this.wrapper.classList.remove('cs-open');
            this.wrapper.style.zIndex = '';
            if (this._useFixed) {
                this.dropdown.classList.remove('cs-visible');
                document.body.classList.remove('cs-noscroll');
            }
            this.isOpen = false;
            if (activeSelect === this) {
                activeSelect = null;
            }
        }

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        bindEvents() {
            this.trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                this.toggle();
            });

            // Listen to external changes on select
            this.select.addEventListener('change', () => {
                this.sync();
            });
        }

        destroy() {
            if (this.observer) this.observer.disconnect();
            if (this._useFixed && this.dropdown && this.dropdown.parentNode === document.body) {
                document.body.removeChild(this.dropdown);
            }
            if (this.wrapper && this.wrapper.parentNode) {
                this.wrapper.parentNode.insertBefore(this.select, this.wrapper);
                this.wrapper.remove();
                this.select.classList.remove('cs-native-hidden');
            }
            delete this.select._customSelect;
        }
    }

    // Global click listener to close when clicking outside
    document.addEventListener('click', (e) => {
        if (activeSelect && !activeSelect.wrapper.contains(e.target)) {
            activeSelect.close();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && activeSelect) {
            activeSelect.close();
        }
    });

    // Global init helper
    function initCustomSelects(root = document) {
        if (!root) return;
        const selects = root.querySelectorAll('select:not([data-no-custom]):not(.no-custom-select)');
        selects.forEach(select =>
            !select._customSelect && select.offsetParent !== null && new CustomSelect(select)
        );
    }

    window.CustomSelect = CustomSelect;
    window.initCustomSelects = initCustomSelects;

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initCustomSelects());
    } else {
        initCustomSelects();
    }

    // Also auto-scan periodically or when modals open
    setInterval(() => {
        initCustomSelects();
    }, 1000);

})(window, document);
