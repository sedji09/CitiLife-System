/**
 * CitiLife System - Modern 3-Segment Birthdate Picker Component
 *
 * Implements best practices for birthdate input:
 * - 3 separate numeric fields: Month (MM), Day (DD), Year (YYYY)
 * - Auto-advance focus on completed segments
 * - Auto-padding for single-digit months/days (e.g. typing '5' turns into '05' and jumps)
 * - Backspace & arrow key seamless navigation between boxes
 * - Intelligent paste support (parses YYYY-MM-DD, MM/DD/YYYY, DD/MM/YYYY)
 * - Instant inline validation (impossible dates like Feb 31, leap years, future dates)
 * - Real-time Age calculation (e.g. "✓ Age: 25 years old")
 * - Synchronized hidden input (YYYY-MM-DD) for seamless form submission
 * - Optional calendar icon button with native .showPicker() or Datepicker fallback
 */

(function () {
    'use strict';

    function daysInMonth(month, year) {
        if (!month) return 31;
        const m = parseInt(month, 10);
        const y = parseInt(year, 10) || 2000;
        return new Date(y, m, 0).getDate();
    }

    function isLeapYear(year) {
        const y = parseInt(year, 10);
        return (y % 4 === 0 && y % 100 !== 0) || (y % 400 === 0);
    }

    function calculateAge(birthdateStr) {
        if (!birthdateStr) return null;
        const parts = birthdateStr.split('-');
        if (parts.length !== 3) return null;
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const d = parseInt(parts[2], 10);

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const bdate = new Date(y, m, d);
        bdate.setHours(0, 0, 0, 0);

        if (isNaN(bdate.getTime())) return null;

        let age = today.getFullYear() - bdate.getFullYear();
        const mDiff = today.getMonth() - bdate.getMonth();
        if (mDiff < 0 || (mDiff === 0 && today.getDate() < bdate.getDate())) {
            age--;
        }
        return age;
    }

    class BirthdatePicker {
        constructor(container, options = {}) {
            this.container = typeof container === 'string' ? document.getElementById(container) : container;
            if (!this.container) {
                console.error('[BirthdatePicker] Target container not found:', container);
                return;
            }

            this.options = Object.assign({
                name: 'birthdate',
                id: this.container.id ? this.container.id + '_input' : 'birthdate_' + Math.random().toString(36).substr(2, 6),
                value: '',
                required: false,
                readonly: false,
                disabled: false,
                feedbackId: null,
                showAge: true,
                allowFuture: false,
                minAge: 0,
                maxAge: 125,
                inputClass: '',
                onInput: null,
                onChange: null
            }, options);

            this.render();
            this.bindEvents();

            if (this.options.value) {
                this.setValue(this.options.value, false);
            }
        }

        render() {
            const id = this.options.id;
            const name = this.options.name;
            const requiredAttr = this.options.required ? 'required' : '';
            const disabledAttr = this.options.disabled ? 'disabled' : '';
            const readonlyAttr = this.options.readonly ? 'readonly' : '';

            this.container.classList.add('birthdate-picker-root', 'relative', 'w-full');

            this.container.innerHTML = `
                <div class="birthdate-control-box flex items-center justify-between w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-all focus-within:border-red-500 focus-within:ring-2 focus-within:ring-red-500/20">
                    <div class="flex items-center flex-1 space-x-1.5 font-mono text-[14px]">
                        <!-- Month -->
                        <div class="flex flex-col items-center">
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                                id="${id}_mm" placeholder="MM"
                                class="bd-segment bd-mm w-8 text-center bg-transparent border-0 p-0 text-gray-900 font-semibold focus:outline-none focus:ring-0 select-all placeholder-gray-400"
                                autocomplete="bday-month" ${disabledAttr} ${readonlyAttr} />
                            <span class="text-[9px] font-sans font-medium text-gray-400 tracking-wider -mt-0.5">MM</span>
                        </div>

                        <span class="text-gray-300 font-sans font-light select-none text-base pb-3">/</span>

                        <!-- Day -->
                        <div class="flex flex-col items-center">
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                                id="${id}_dd" placeholder="DD"
                                class="bd-segment bd-dd w-8 text-center bg-transparent border-0 p-0 text-gray-900 font-semibold focus:outline-none focus:ring-0 select-all placeholder-gray-400"
                                autocomplete="bday-day" ${disabledAttr} ${readonlyAttr} />
                            <span class="text-[9px] font-sans font-medium text-gray-400 tracking-wider -mt-0.5">DD</span>
                        </div>

                        <span class="text-gray-300 font-sans font-light select-none text-base pb-3">/</span>

                        <!-- Year -->
                        <div class="flex flex-col items-center">
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4"
                                id="${id}_yyyy" placeholder="YYYY"
                                class="bd-segment bd-yyyy w-14 text-center bg-transparent border-0 p-0 text-gray-900 font-semibold focus:outline-none focus:ring-0 select-all placeholder-gray-400"
                                autocomplete="bday-year" ${disabledAttr} ${readonlyAttr} />
                            <span class="text-[9px] font-sans font-medium text-gray-400 tracking-wider -mt-0.5">YYYY</span>
                        </div>
                    </div>

                    <!-- Hidden Input for Form Submission -->
                    <input type="hidden" id="${id}" name="${name}" ${requiredAttr} />

                    <!-- Native Picker Fallback / Trigger -->
                    <input type="date" id="${id}_native" tabindex="-1" class="sr-only absolute opacity-0 pointer-events-none" />

                    <!-- Calendar Action Button -->
                    <button type="button" id="${id}_cal_btn" title="Open Calendar" tabindex="-1"
                        class="bd-cal-btn p-1.5 -mr-1 text-gray-400 hover:text-red-600 rounded-md hover:bg-gray-100 transition-colors focus:outline-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6" stroke-width="2"></line>
                            <line x1="8" y1="2" x2="8" y2="6" stroke-width="2"></line>
                            <line x1="3" y1="10" x2="21" y2="10" stroke-width="2"></line>
                        </svg>
                    </button>
                </div>
            `;

            this.controlBox = this.container.querySelector('.birthdate-control-box');
            this.inputMM = document.getElementById(`${id}_mm`);
            this.inputDD = document.getElementById(`${id}_dd`);
            this.inputYYYY = document.getElementById(`${id}_yyyy`);
            this.hiddenInput = document.getElementById(id);
            this.nativeInput = document.getElementById(`${id}_native`);
            this.calBtn = document.getElementById(`${id}_cal_btn`);

            if (this.options.feedbackId) {
                this.feedbackEl = document.getElementById(this.options.feedbackId);
            }
        }

        bindEvents() {
            const mm = this.inputMM;
            const dd = this.inputDD;
            const yyyy = this.inputYYYY;

            // 1. Month Input Handling
            mm.addEventListener('input', (e) => {
                let val = mm.value.replace(/\D/g, '');
                if (val.length === 1) {
                    const firstDigit = parseInt(val, 10);
                    // If user enters 2..9, it's obviously a single-digit month (e.g. 02, 05, 09)
                    if (firstDigit >= 2 && firstDigit <= 9) {
                        mm.value = '0' + val;
                        dd.focus();
                        dd.select();
                    } else {
                        mm.value = val;
                    }
                } else if (val.length >= 2) {
                    val = val.slice(0, 2);
                    const mNum = parseInt(val, 10);
                    if (mNum > 12) val = '12';
                    if (mNum === 0) val = '01';
                    mm.value = val;
                    dd.focus();
                    dd.select();
                }
                this.syncValue();
            });

            // 2. Day Input Handling
            dd.addEventListener('input', (e) => {
                let val = dd.value.replace(/\D/g, '');
                const mNum = parseInt(mm.value, 10) || 1;
                const yNum = parseInt(yyyy.value, 10) || 2000;
                const maxDays = daysInMonth(mNum, yNum);

                if (val.length === 1) {
                    const firstDigit = parseInt(val, 10);
                    // If first digit is 4..9, day cannot be 40..99, so auto-pad to 04..09 and jump to year
                    if (firstDigit >= 4 && firstDigit <= 9) {
                        dd.value = '0' + val;
                        yyyy.focus();
                        yyyy.select();
                    } else {
                        dd.value = val;
                    }
                } else if (val.length >= 2) {
                    val = val.slice(0, 2);
                    let dNum = parseInt(val, 10);
                    if (dNum > maxDays) dNum = maxDays;
                    if (dNum === 0) dNum = 1;
                    dd.value = dNum < 10 ? '0' + dNum : String(dNum);
                    yyyy.focus();
                    yyyy.select();
                }
                this.syncValue();
            });

            // 3. Year Input Handling
            yyyy.addEventListener('input', (e) => {
                let val = yyyy.value.replace(/\D/g, '');
                if (val.length > 4) val = val.slice(0, 4);
                yyyy.value = val;
                this.syncValue();
            });

            // Keyboard navigation (Backspace, Left/Right arrows)
            mm.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight' && mm.selectionStart === mm.value.length) {
                    e.preventDefault();
                    dd.focus();
                    dd.setSelectionRange(0, 0);
                }
            });

            dd.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && dd.selectionStart === 0 && dd.selectionEnd === 0) {
                    e.preventDefault();
                    mm.focus();
                    mm.setSelectionRange(mm.value.length, mm.value.length);
                } else if (e.key === 'ArrowLeft' && dd.selectionStart === 0) {
                    e.preventDefault();
                    mm.focus();
                    mm.setSelectionRange(mm.value.length, mm.value.length);
                } else if (e.key === 'ArrowRight' && dd.selectionStart === dd.value.length) {
                    e.preventDefault();
                    yyyy.focus();
                    yyyy.setSelectionRange(0, 0);
                }
            });

            yyyy.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && yyyy.selectionStart === 0 && yyyy.selectionEnd === 0) {
                    e.preventDefault();
                    dd.focus();
                    dd.setSelectionRange(dd.value.length, dd.value.length);
                } else if (e.key === 'ArrowLeft' && yyyy.selectionStart === 0) {
                    e.preventDefault();
                    dd.focus();
                    dd.setSelectionRange(dd.value.length, dd.value.length);
                }
            });

            // Paste Handling (Supports YYYY-MM-DD, MM/DD/YYYY, DD/MM/YYYY, etc.)
            const handlePaste = (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                this.parseAndSet(text);
            };
            mm.addEventListener('paste', handlePaste);
            dd.addEventListener('paste', handlePaste);
            yyyy.addEventListener('paste', handlePaste);

            // Blur Validation
            const handleBlur = () => {
                setTimeout(() => {
                    const activeEl = document.activeElement;
                    if (activeEl !== mm && activeEl !== dd && activeEl !== yyyy) {
                        this.validate(true);
                    }
                }, 50);
            };
            mm.addEventListener('blur', handleBlur);
            dd.addEventListener('blur', handleBlur);
            yyyy.addEventListener('blur', handleBlur);

            // Calendar Toggle Button
            if (this.calBtn && this.nativeInput) {
                this.calBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Set current value in native picker before opening
                    if (this.hiddenInput.value) {
                        this.nativeInput.value = this.hiddenInput.value;
                    }
                    if (typeof this.nativeInput.showPicker === 'function') {
                        try {
                            this.nativeInput.showPicker();
                        } catch (err) {
                            this.nativeInput.focus();
                        }
                    } else {
                        this.nativeInput.focus();
                    }
                });

                this.nativeInput.addEventListener('change', () => {
                    if (this.nativeInput.value) {
                        this.setValue(this.nativeInput.value, true);
                    }
                });
            }
        }

        parseAndSet(text) {
            if (!text) return;
            const clean = text.trim();

            // Match YYYY-MM-DD or YYYY/MM/DD
            let m = clean.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})$/);
            if (m) {
                this.setValue(`${m[1]}-${String(m[2]).padStart(2, '0')}-${String(m[3]).padStart(2, '0')}`, true);
                return;
            }

            // Match MM/DD/YYYY or MM-DD-YYYY
            m = clean.match(/^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})$/);
            if (m) {
                const part1 = parseInt(m[1], 10);
                const part2 = parseInt(m[2], 10);
                const year = m[3];
                // If part1 > 12, user pasted DD/MM/YYYY
                if (part1 > 12 && part2 <= 12) {
                    this.setValue(`${year}-${String(part2).padStart(2, '0')}-${String(part1).padStart(2, '0')}`, true);
                } else {
                    // Default to MM/DD/YYYY
                    this.setValue(`${year}-${String(part1).padStart(2, '0')}-${String(part2).padStart(2, '0')}`, true);
                }
                return;
            }

            // Try standard Date parse
            const parsedDate = new Date(clean);
            if (!isNaN(parsedDate.getTime())) {
                const y = parsedDate.getFullYear();
                const mon = String(parsedDate.getMonth() + 1).padStart(2, '0');
                const d = String(parsedDate.getDate()).padStart(2, '0');
                this.setValue(`${y}-${mon}-${d}`, true);
            }
        }

        setValue(isoDateStr, triggerChange = true) {
            if (!isoDateStr) {
                this.inputMM.value = '';
                this.inputDD.value = '';
                this.inputYYYY.value = '';
                this.hiddenInput.value = '';
                if (triggerChange) this.syncValue();
                return;
            }

            const parts = isoDateStr.split(/[-/]/);
            if (parts.length === 3) {
                // Determine whether YYYY-MM-DD or MM/DD/YYYY
                if (parts[0].length === 4) {
                    this.inputYYYY.value = parts[0];
                    this.inputMM.value = String(parts[1]).padStart(2, '0');
                    this.inputDD.value = String(parts[2]).padStart(2, '0');
                } else if (parts[2].length === 4) {
                    this.inputMM.value = String(parts[0]).padStart(2, '0');
                    this.inputDD.value = String(parts[1]).padStart(2, '0');
                    this.inputYYYY.value = parts[2];
                }
            }

            this.syncValue(triggerChange);
        }

        getValue() {
            return this.hiddenInput.value;
        }

        syncValue(triggerEvents = true) {
            const mm = this.inputMM.value.trim();
            const dd = this.inputDD.value.trim();
            const yyyy = this.inputYYYY.value.trim();

            let iso = '';
            if (yyyy.length === 4 && mm.length > 0 && dd.length > 0) {
                iso = `${yyyy}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
            }

            this.hiddenInput.value = iso;
            if (this.nativeInput) {
                this.nativeInput.value = iso;
            }

            const validation = this.validate(false);

            if (triggerEvents) {
                // Dispatch change events on the hidden input for existing form scripts
                this.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                this.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

                if (typeof this.options.onInput === 'function') {
                    this.options.onInput(iso, validation.age, validation.isValid, validation.message);
                }
            }

            return validation;
        }

        validate(isSubmittedOrBlur = false) {
            const mm = this.inputMM.value.trim();
            const dd = this.inputDD.value.trim();
            const yyyy = this.inputYYYY.value.trim();
            const iso = this.hiddenInput.value;

            // Empty state
            if (!mm && !dd && !yyyy) {
                if (this.options.required && isSubmittedOrBlur) {
                    this.setStatus('error', 'Birthdate is required.');
                    return { isValid: false, message: 'Birthdate is required.', age: null };
                }
                this.setStatus('normal', '');
                return { isValid: !this.options.required, message: '', age: null };
            }

            // Incomplete state
            if (mm.length < 1 || dd.length < 1 || yyyy.length < 4) {
                if (isSubmittedOrBlur) {
                    this.setStatus('error', 'Please complete the birthdate (MM / DD / YYYY).');
                    return { isValid: false, message: 'Please complete the birthdate.', age: null };
                }
                this.setStatus('normal', '');
                return { isValid: false, message: 'Incomplete', age: null };
            }

            const mNum = parseInt(mm, 10);
            const dNum = parseInt(dd, 10);
            const yNum = parseInt(yyyy, 10);

            // Month bounds
            if (mNum < 1 || mNum > 12) {
                this.setStatus('error', 'Month must be between 01 and 12.');
                return { isValid: false, message: 'Invalid month.', age: null };
            }

            // Day bounds for the specific month/year
            const maxDays = daysInMonth(mNum, yNum);
            if (dNum < 1 || dNum > maxDays) {
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                const mName = monthNames[mNum - 1];
                if (mNum === 2 && !isLeapYear(yNum) && dNum === 29) {
                    this.setStatus('error', `February ${yNum} is not a leap year (max 28 days).`);
                } else {
                    this.setStatus('error', `${mName} has a maximum of ${maxDays} days.`);
                }
                return { isValid: false, message: 'Invalid day for selected month.', age: null };
            }

            // Year bounds
            const today = new Date();
            const currentYear = today.getFullYear();
            if (yNum < 1900 || yNum > currentYear + 10) {
                this.setStatus('error', `Year must be between 1900 and ${currentYear}.`);
                return { isValid: false, message: 'Invalid year.', age: null };
            }

            // Date instance check
            const bdate = new Date(yNum, mNum - 1, dNum);
            today.setHours(0, 0, 0, 0);

            if (bdate > today && !this.options.allowFuture) {
                this.setStatus('error', 'Birthdate cannot be in the future.');
                return { isValid: false, message: 'Birthdate cannot be in the future.', age: null };
            }

            // Age check
            const age = calculateAge(iso);
            if (age !== null) {
                if (age < this.options.minAge) {
                    this.setStatus('error', `Patient must be at least ${this.options.minAge} years old.`);
                    return { isValid: false, message: 'Under min age.', age };
                }
                if (age > this.options.maxAge) {
                    this.setStatus('error', `Age (${age}) exceeds normal maximum limit.`);
                    return { isValid: false, message: 'Exceeds max age.', age };
                }
            }

            // Valid!
            const successMsg = this.options.showAge && age !== null 
                ? `✓ Age: ${age} year${age === 1 ? '' : 's'} old` 
                : '✓ Valid birthdate';

            this.setStatus('success', successMsg);

            if (typeof this.options.onChange === 'function') {
                this.options.onChange(iso, age, true, successMsg);
            }

            return { isValid: true, message: successMsg, age };
        }

        setStatus(state, message) {
            if (!this.controlBox) return;

            this.controlBox.classList.remove(
                'border-red-500', 'ring-2', 'ring-red-500/20',
                'border-emerald-500', 'ring-emerald-500/20',
                'border-gray-300'
            );

            if (state === 'error') {
                this.controlBox.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
            } else if (state === 'success') {
                this.controlBox.classList.add('border-emerald-500');
            } else {
                this.controlBox.classList.add('border-gray-300');
            }

            if (this.feedbackEl) {
                this.feedbackEl.className = 'text-xs mt-1.5 transition-all duration-200';
                if (state === 'error') {
                    this.feedbackEl.className = 'text-xs mt-1.5 font-medium text-red-600 flex items-center gap-1';
                    this.feedbackEl.innerHTML = `<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"></circle><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"></line><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"></line></svg> <span>${message}</span>`;
                    this.feedbackEl.classList.remove('hidden');
                } else if (state === 'success') {
                    this.feedbackEl.className = 'text-xs mt-1.5 font-medium text-emerald-600 flex items-center gap-1';
                    this.feedbackEl.innerHTML = `<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="20 6 9 17 4 12" stroke-width="2"></polyline></svg> <span>${message}</span>`;
                    this.feedbackEl.classList.remove('hidden');
                } else {
                    this.feedbackEl.classList.add('hidden');
                    this.feedbackEl.textContent = '';
                }
            }
        }

        focus() {
            if (this.inputMM) this.inputMM.focus();
        }
    }

    // Export globally
    window.BirthdatePicker = BirthdatePicker;

    // Helper to easily auto-mount all data-birthdate-picker containers
    window.initBirthdatePickers = function () {
        document.querySelectorAll('[data-birthdate-picker]').forEach(container => {
            if (!container._bdPicker) {
                const name = container.getAttribute('data-name') || 'birthdate';
                const id = container.getAttribute('data-id') || container.id + '_input';
                const value = container.getAttribute('data-value') || '';
                const feedbackId = container.getAttribute('data-feedback-id') || null;
                const required = container.hasAttribute('data-required');

                container._bdPicker = new BirthdatePicker(container, {
                    name,
                    id,
                    value,
                    feedbackId,
                    required
                });
            }
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.initBirthdatePickers();
    });
})();
