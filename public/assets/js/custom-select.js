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

            // Skip if explicitly flagged or already processed
            if (this.select.hasAttribute('data-no-custom') || this.select.classList.contains('no-custom-select')) {
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

            // Transfer custom classes like w-full, etc. if appropriate
            if (this.select.className) {
                const classes = this.select.className.split(' ');
                classes.forEach(c => {
                    if (['w-full', 'max-w-xs', 'max-w-sm', 'max-w-md', 'flex-1'].includes(c)) {
                        this.wrapper.classList.add(c);
                    }
                });
            }

            // Insert wrapper before select and move select inside
            this.select.parentNode.insertBefore(this.wrapper, this.select);
            this.wrapper.appendChild(this.select);
            this.select.classList.add('cs-native-hidden');

            // Trigger Button
            this.trigger = document.createElement('button');
            this.trigger.type = 'button';
            this.trigger.className = 'cs-trigger';

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

            // Bind Events
            this.bindEvents();

            // Observe DOM mutations on select element (e.g. dynamically added options)
            this.observer = new MutationObserver(() => {
                this.buildOptions();
            });
            this.observer.observe(this.select, { childList: true, attributes: true, attributeFilter: ['disabled', 'value'] });
        }

        buildOptions() {
            this.dropdown.innerHTML = '';
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

                this.dropdown.appendChild(item);
            });
        }

        selectOption(value, index) {
            if (this.select.selectedIndex !== index) {
                this.select.selectedIndex = index;
                this.select.value = value;

                // Dispatch native events so Vue, listeners & filters respond
                this.select.dispatchEvent(new Event('change', { bubbles: true }));
                this.select.dispatchEvent(new Event('input', { bubbles: true }));

                // Run inline onchange if defined
                if (typeof this.select.onchange === 'function') {
                    this.select.onchange();
                }
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

            // Check if dropdown fits below, otherwise open upward
            const rect = this.wrapper.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            if (spaceBelow < 260 && rect.top > 260) {
                this.wrapper.classList.add('cs-dropup');
            } else {
                this.wrapper.classList.remove('cs-dropup');
            }

            this.wrapper.classList.add('cs-open');
            this.isOpen = true;
            activeSelect = this;
        }

        close() {
            this.wrapper.classList.remove('cs-open');
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
        selects.forEach(select => {
            if (!select._customSelect && select.offsetParent !== null) {
                new CustomSelect(select);
            }
        });
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
