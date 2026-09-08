/**
 * Modern Custom DatePicker
 * Minimalist calendar popover matching the modern card design with Month & Year dropdowns.
 */
(function (window, document) {
    'use strict';

    const MONTH_NAMES_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const MONTH_NAMES_FULL = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const WEEKDAYS = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];

    const CHEVRON_DOWN_SVG = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>`;
    const CHEVRON_LEFT_SVG = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>`;
    const CHEVRON_RIGHT_SVG = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>`;

    function padZero(num) {
        return num < 10 ? '0' + num : String(num);
    }

    function formatDateISO(date) {
        if (!date || isNaN(date.getTime())) return '';
        const y = date.getFullYear();
        const m = padZero(date.getMonth() + 1);
        const d = padZero(date.getDate());
        return `${y}-${m}-${d}`;
    }

    function parseDateISO(str) {
        if (!str || typeof str !== 'string') return null;
        const parts = str.trim().split('-');
        if (parts.length === 3) {
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10) - 1;
            const d = parseInt(parts[2], 10);
            const dt = new Date(y, m, d);
            if (!isNaN(dt.getTime()) && dt.getDate() === d) return dt;
        }
        // Fallback for other standard formats
        const fallback = new Date(str);
        return isNaN(fallback.getTime()) ? null : fallback;
    }

    class ModernDatePicker {
        constructor(inputEl, options = {}) {
            this.input = typeof inputEl === 'string' ? document.querySelector(inputEl) : inputEl;
            if (!this.input) return;

            // Prevent duplicate attachment
            if (this.input._customDatePicker) {
                return this.input._customDatePicker;
            }

            const today = new Date();
            this.options = Object.assign({
                maxDate: today, // default for birthdate: cannot be future
                minDate: new Date(1910, 0, 1),
                format: 'YYYY-MM-DD',
                onSelect: null
            }, options);

            this.selectedDate = null;
            this.viewDate = new Date();
            this.currentView = 'days'; // 'days' | 'months' | 'years'
            this.isOpen = false;

            // Parse initial value if present
            if (this.input.value.trim()) {
                const parsed = parseDateISO(this.input.value.trim());
                if (parsed) {
                    this.selectedDate = parsed;
                    this.viewDate = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
                }
            }

            this.init();
            this.input._customDatePicker = this;
        }

        init() {
            // Build Popover DOM
            this.popover = document.createElement('div');
            this.popover.className = 'cdp-popover';

            this.popover.innerHTML = `
                <div class="cdp-header">
                    <div class="cdp-dropdown-triggers">
                        <button type="button" class="cdp-trigger-btn cdp-month-trigger">
                            <span class="cdp-month-label">Jan</span>
                            ${CHEVRON_DOWN_SVG}
                        </button>
                        <button type="button" class="cdp-trigger-btn cdp-year-trigger">
                            <span class="cdp-year-label">2025</span>
                            ${CHEVRON_DOWN_SVG}
                        </button>
                    </div>
                    <div class="cdp-nav">
                        <button type="button" class="cdp-nav-btn cdp-prev-btn" title="Previous Month">
                            ${CHEVRON_LEFT_SVG}
                        </button>
                        <button type="button" class="cdp-nav-btn cdp-next-btn" title="Next Month">
                            ${CHEVRON_RIGHT_SVG}
                        </button>
                    </div>
                </div>

                <div class="cdp-days-view">
                    <div class="cdp-weekdays">
                        ${WEEKDAYS.map(w => `<div class="cdp-weekday">${w}</div>`).join('')}
                    </div>
                    <div class="cdp-days-grid"></div>
                </div>

                <div class="cdp-months-view"></div>
                <div class="cdp-years-view"></div>
            `;

            document.body.appendChild(this.popover);

            // Cached Elements
            this.monthTrigger = this.popover.querySelector('.cdp-month-trigger');
            this.yearTrigger = this.popover.querySelector('.cdp-year-trigger');
            this.monthLabel = this.popover.querySelector('.cdp-month-label');
            this.yearLabel = this.popover.querySelector('.cdp-year-label');
            this.prevBtn = this.popover.querySelector('.cdp-prev-btn');
            this.nextBtn = this.popover.querySelector('.cdp-next-btn');

            this.daysView = this.popover.querySelector('.cdp-days-view');
            this.daysGrid = this.popover.querySelector('.cdp-days-grid');
            this.monthsView = this.popover.querySelector('.cdp-months-view');
            this.yearsView = this.popover.querySelector('.cdp-years-view');

            this.bindEvents();
            this.render();
        }

        bindEvents() {
            // Input interactions: always open reliably on both click and focus
            this.input.addEventListener('focus', () => {
                this.open();
            });

            this.input.addEventListener('click', (e) => {
                e.stopPropagation();
                this.open();
            });

            // If there's an associated label or icon, make it open the picker too
            if (this.input.id) {
                const label = document.querySelector(`label[for="${this.input.id}"]`);
                if (label) {
                    label.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.open();
                    });
                }
            }

            // Prevent clicks inside popover from closing it
            this.popover.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            // Month / Year toggle buttons
            this.monthTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (this.currentView === 'months') {
                    this.setView('days');
                } else {
                    this.setView('months');
                }
            });

            this.yearTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (this.currentView === 'years') {
                    this.setView('days');
                } else {
                    this.setView('years');
                }
            });

            // Prev / Next buttons
            this.prevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (this.currentView === 'days') {
                    this.viewDate.setMonth(this.viewDate.getMonth() - 1);
                    this.render();
                } else if (this.currentView === 'years') {
                    this.viewDate.setFullYear(this.viewDate.getFullYear() - 12);
                    this.render();
                }
            });

            this.nextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (this.currentView === 'days') {
                    this.viewDate.setMonth(this.viewDate.getMonth() + 1);
                    this.render();
                } else if (this.currentView === 'years') {
                    this.viewDate.setFullYear(this.viewDate.getFullYear() + 12);
                    this.render();
                }
            });

            // Outside click to close
            document.addEventListener('click', (e) => {
                if (this.isOpen && !this.popover.contains(e.target) && e.target !== this.input && !this.input.contains(e.target)) {
                    this.close();
                }
            });

            // Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });

            // Reposition on window resize / scroll
            window.addEventListener('resize', () => {
                if (this.isOpen) this.position();
            });
            window.addEventListener('scroll', () => {
                if (this.isOpen) this.position();
            }, true);
        }

        setView(view) {
            this.currentView = view;
            this.monthTrigger.classList.toggle('active', view === 'months');
            this.yearTrigger.classList.toggle('active', view === 'years');

            this.daysView.style.display = view === 'days' ? 'block' : 'none';
            this.monthsView.classList.toggle('active', view === 'months');
            this.yearsView.classList.toggle('active', view === 'years');

            this.render();

            if (view === 'years') {
                // Auto scroll selected year into view
                const selectedYearBtn = this.yearsView.querySelector('.cdp-year-btn.selected');
                if (selectedYearBtn) {
                    setTimeout(() => {
                        selectedYearBtn.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }, 40);
                }
            }
        }

        render() {
            const year = this.viewDate.getFullYear();
            const month = this.viewDate.getMonth();

            this.monthLabel.textContent = MONTH_NAMES_SHORT[month];
            this.yearLabel.textContent = String(year);

            if (this.currentView === 'days') {
                this.renderDays(year, month);
            } else if (this.currentView === 'months') {
                this.renderMonths(month);
            } else if (this.currentView === 'years') {
                this.renderYears(year);
            }
        }

        renderDays(year, month) {
            this.daysGrid.innerHTML = '';

            const firstDayIndex = new Date(year, month, 1).getDay(); // 0 = Sun, 1 = Mon ...
            // Shift so Monday is index 0
            const startOffset = (firstDayIndex === 0 ? 6 : firstDayIndex - 1);

            const totalDaysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Leading empty slots
            for (let i = 0; i < startOffset; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'cdp-day-cell';
                const emptyBtn = document.createElement('span');
                emptyBtn.className = 'cdp-day-btn empty';
                emptyCell.appendChild(emptyBtn);
                this.daysGrid.appendChild(emptyCell);
            }

            // Days of the month
            for (let d = 1; d <= totalDaysInMonth; d++) {
                const cell = document.createElement('div');
                cell.className = 'cdp-day-cell';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cdp-day-btn';
                btn.textContent = String(d);

                const cellDate = new Date(year, month, d);
                cellDate.setHours(0, 0, 0, 0);

                // Is Today
                if (cellDate.getTime() === today.getTime()) {
                    btn.classList.add('today');
                }

                // Is Selected
                if (this.selectedDate &&
                    this.selectedDate.getFullYear() === year &&
                    this.selectedDate.getMonth() === month &&
                    this.selectedDate.getDate() === d) {
                    btn.classList.add('selected');
                }

                // Is Disabled (e.g. Future for birthdates)
                if (this.options.maxDate) {
                    const max = new Date(this.options.maxDate);
                    max.setHours(23, 59, 59, 999);
                    if (cellDate > max) {
                        btn.classList.add('disabled');
                    }
                }
                if (this.options.minDate) {
                    const min = new Date(this.options.minDate);
                    min.setHours(0, 0, 0, 0);
                    if (cellDate < min) {
                        btn.classList.add('disabled');
                    }
                }

                if (!btn.classList.contains('disabled')) {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.selectDate(new Date(year, month, d));
                    });
                }

                cell.appendChild(btn);
                this.daysGrid.appendChild(cell);
            }
        }

        renderMonths(currentMonth) {
            this.monthsView.innerHTML = '';
            MONTH_NAMES_SHORT.forEach((name, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cdp-month-btn';
                btn.textContent = name;
                if (idx === currentMonth) {
                    btn.classList.add('selected');
                }
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.viewDate.setMonth(idx);
                    this.setView('days');
                });
                this.monthsView.appendChild(btn);
            });
        }

        renderYears(currentYear) {
            this.yearsView.innerHTML = '';
            const maxYear = this.options.maxDate ? this.options.maxDate.getFullYear() : new Date().getFullYear();
            const minYear = this.options.minDate ? this.options.minDate.getFullYear() : 1910;

            for (let y = maxYear; y >= minYear; y--) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cdp-year-btn';
                btn.textContent = String(y);
                if (y === currentYear) {
                    btn.classList.add('selected');
                }
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.viewDate.setFullYear(y);
                    this.setView('days');
                });
                this.yearsView.appendChild(btn);
            }
        }

        setDate(dateOrString, triggerEvents = true) {
            if (!dateOrString) {
                this.selectedDate = null;
                this.input.value = '';
                this.render();
                return;
            }
            const d = typeof dateOrString === 'string' ? parseDateISO(dateOrString) : dateOrString;
            if (d && !isNaN(d.getTime())) {
                this.selectedDate = d;
                this.viewDate = new Date(d.getFullYear(), d.getMonth(), 1);
                const dateString = formatDateISO(d);
                this.input.value = dateString;
                this.render();

                if (triggerEvents) {
                    this.input.dispatchEvent(new Event('input', { bubbles: true }));
                    this.input.dispatchEvent(new Event('change', { bubbles: true }));
                    this.input.dispatchEvent(new CustomEvent('changeDate', {
                        bubbles: true,
                        detail: { date: d, dateString }
                    }));
                    if (typeof this.options.onSelect === 'function') {
                        this.options.onSelect(dateString, d);
                    }
                }
            }
        }

        selectDate(date) {
            this.selectedDate = date;
            const dateString = formatDateISO(date);
            this.input.value = dateString;

            // Dispatch all standard events for forms & listeners
            this.input.dispatchEvent(new Event('input', { bubbles: true }));
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.input.dispatchEvent(new CustomEvent('changeDate', {
                bubbles: true,
                detail: { date, dateString }
            }));

            if (typeof this.options.onSelect === 'function') {
                this.options.onSelect(dateString, date);
            }

            this.close();
        }

        position() {
            if (!this.input || !this.popover) return;
            const rect = this.input.getBoundingClientRect();
            const popoverHeight = 360;
            const popoverWidth = 310;

            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;

            let top = rect.bottom + window.scrollY + 8;
            let left = rect.left + window.scrollX;

            // Only flip above if space below is too cramped (< 280px) AND space above is larger
            if (spaceBelow < 280 && spaceAbove > spaceBelow) {
                top = rect.top + window.scrollY - popoverHeight - 8;
            }

            // Adjust horizontal if overflowing viewport
            if (left + popoverWidth > window.innerWidth - 16) {
                left = window.innerWidth - popoverWidth - 16;
            }
            if (left < 16) left = 16;

            this.popover.style.top = `${top}px`;
            this.popover.style.left = `${left}px`;
        }

        open() {
            if (this.isOpen) return;

            // Sync with current input value
            if (this.input.value.trim()) {
                const parsed = parseDateISO(this.input.value.trim());
                if (parsed) {
                    this.selectedDate = parsed;
                    this.viewDate = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
                }
            } else if (!this.selectedDate) {
                // If opening empty, default to current date
                this.viewDate = new Date();
            }

            this.setView('days');
            this.position();
            this.popover.classList.add('cdp-open');
            this.isOpen = true;

            if (typeof window.sendHeight === 'function') {
                setTimeout(window.sendHeight, 60);
            }
        }

        close() {
            if (!this.isOpen) return;
            this.popover.classList.remove('cdp-open');
            this.isOpen = false;
            this.setView('days');

            if (typeof window.sendHeight === 'function') {
                setTimeout(window.sendHeight, 60);
            }
        }

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        destroy() {
            if (this.popover && this.popover.parentNode) {
                this.popover.parentNode.removeChild(this.popover);
            }
            delete this.input._customDatePicker;
        }
    }

    // Expose to window
    window.ModernDatePicker = ModernDatePicker;

    // Auto-init helper on inputs with data-custom-datepicker
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-custom-datepicker]').forEach(input => {
            new ModernDatePicker(input);
        });
    });

})(window, document);
