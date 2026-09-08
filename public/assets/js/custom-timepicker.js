/**
 * Modern Custom TimePicker for CitiLife System
 * Minimalist, elegant time popover matching CitiLife's modern design aesthetic.
 * Supports auto-jumping keyboard typing:
 * Type 08 on hours -> automatically moves to minutes -> type 00 -> type A/P or Enter!
 */
(function (window, document) {
  'use strict';

  const CLOCK_ICON_SVG = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
  const CLOSE_ICON_SVG = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;

  function padZero(num) {
    return num < 10 ? '0' + num : String(num);
  }

  function parseTimeString(str) {
    if (!str || typeof str !== 'string') return null;
    str = str.trim().toUpperCase();

    // 1. With AM/PM: e.g. "8:30 PM", "8:30PM", "8:30P", "8PM", "8 PM", "8P", "08:00 AM", "11:59PM"
    const matchAmpm = str.match(/^(\d{1,2})(?::(\d{1,2}))?\s*(AM|PM|A|P)$/i);
    if (matchAmpm) {
      let h = parseInt(matchAmpm[1], 10);
      let m = matchAmpm[2] !== undefined ? parseInt(matchAmpm[2], 10) : 0;
      let pStr = matchAmpm[3].toUpperCase();
      let period = (pStr === 'P' || pStr === 'PM') ? 'PM' : 'AM';
      if (h >= 1 && h <= 12 && m >= 0 && m <= 59) {
        let h24 = h;
        if (period === 'PM' && h < 12) h24 += 12;
        if (period === 'AM' && h === 12) h24 = 0;
        return { hour12: h, hour24: h24, minute: m, period };
      }
    }

    // 2. Colon format 24h/12h without AM/PM: e.g. "08:00", "23:59", "8:00", "14:30"
    const matchColon = str.match(/^(\d{1,2}):(\d{1,2})$/);
    if (matchColon) {
      const h = parseInt(matchColon[1], 10);
      const m = parseInt(matchColon[2], 10);
      if (h >= 0 && h <= 23 && m >= 0 && m <= 59) {
        let period = h >= 12 ? 'PM' : 'AM';
        let h12 = h % 12;
        if (h12 === 0) h12 = 12;
        return { hour12: h12, hour24: h, minute: m, period };
      }
    }

    // 3. Raw 3 or 4 digits: e.g. "0800", "830", "2359", "1159"
    const matchRaw = str.match(/^(\d{3,4})$/);
    if (matchRaw) {
      const digits = matchRaw[1];
      const h = parseInt(digits.length === 3 ? digits.slice(0, 1) : digits.slice(0, 2), 10);
      const m = parseInt(digits.slice(-2), 10);
      if (h >= 0 && h <= 23 && m >= 0 && m <= 59) {
        let period = h >= 12 ? 'PM' : 'AM';
        let h12 = h % 12;
        if (h12 === 0) h12 = 12;
        return { hour12: h12, hour24: h, minute: m, period };
      }
    }

    // 4. Single or double digit hour: e.g. "8", "08"
    const matchHourOnly = str.match(/^(\d{1,2})$/);
    if (matchHourOnly) {
      const h = parseInt(matchHourOnly[1], 10);
      if (h >= 1 && h <= 12) {
        return { hour12: h, hour24: h, minute: 0, period: 'AM' };
      }
    }

    return null;
  }

  function formatTime12(h12, m, period) {
    return `${padZero(h12)}:${padZero(m)} ${period}`;
  }

  function formatTime24(h24, m) {
    return `${padZero(h24)}:${padZero(m)}`;
  }

  class ModernTimePicker {
    constructor(inputEl, options = {}) {
      this.input = typeof inputEl === 'string' ? document.querySelector(inputEl) : inputEl;
      if (!this.input) return;

      if (this.input._customTimePicker) {
        return this.input._customTimePicker;
      }

      this.options = Object.assign({
        format: '24h', // Format for underlying form input: '24h' (HH:mm)
        defaultTime: '08:00',
        onChange: null
      }, options);

      // Current state
      const initial = parseTimeString(this.input.value) || parseTimeString(this.options.defaultTime) || {
        hour12: 8,
        hour24: 8,
        minute: 0,
        period: 'AM'
      };

      this.selectedHour12 = initial.hour12;
      this.selectedMinute = initial.minute;
      this.selectedPeriod = initial.period;
      this.activeField = 'hour'; // 'hour' | 'minute'
      this.typedHourBuffer = '';
      this.typedMinuteBuffer = '';
      this.isOpen = false;

      this.init();
      this.input._customTimePicker = this;
    }

    init() {
      this.buildTrigger();
      this.buildPopover();
      this.bindEvents();
      this.updateDisplay();
    }

    buildTrigger() {
      this.wrapper = document.createElement('div');
      this.wrapper.className = 'ctp-trigger-wrap';

      // Hide native input visually for form POST
      this.input.style.display = 'none';
      this.input.parentNode.insertBefore(this.wrapper, this.input);
      this.wrapper.appendChild(this.input);

      this.triggerBtn = document.createElement('button');
      this.triggerBtn.type = 'button';
      this.triggerBtn.className = 'ctp-trigger-btn';
      this.triggerBtn.innerHTML = `
        <span class="ctp-trigger-time">--:-- --</span>
        <span class="ctp-trigger-icon">${CLOCK_ICON_SVG}</span>
      `;
      this.wrapper.appendChild(this.triggerBtn);

      this.timeDisplay = this.triggerBtn.querySelector('.ctp-trigger-time');
    }

    buildPopover() {
      this.popover = document.createElement('div');
      this.popover.className = 'ctp-popover';
      this.popover.style.display = 'none';
      this.popover.style.top = '0px';
      this.popover.style.left = '0px';

      this.popover.innerHTML = `
        <div class="ctp-header-bar">
          <div class="ctp-header-title">
            ${CLOCK_ICON_SVG}
            <span>Select Time</span>
          </div>
          <button type="button" class="ctp-close-btn" title="Close">
            ${CLOSE_ICON_SVG}
          </button>
        </div>

        <div class="ctp-display-box">
          <div class="ctp-digits-group">
            <button type="button" class="ctp-digit-pill ctp-digit-hour active" title="Click to edit hours">08</button>
            <span class="ctp-digit-colon">:</span>
            <button type="button" class="ctp-digit-pill ctp-digit-minute" title="Click to edit minutes">00</button>
          </div>
          <div class="ctp-period-toggle">
            <button type="button" class="ctp-period-btn ctp-period-am active" data-period="AM">AM</button>
            <button type="button" class="ctp-period-btn ctp-period-pm" data-period="PM">PM</button>
          </div>
        </div>

        <div class="ctp-columns-grid">
          <div class="ctp-col-container">
            <div class="ctp-col-label">Hour</div>
            <div class="ctp-scroll-col ctp-hours-list">
              ${Array.from({ length: 12 }, (_, i) => i + 1).map(h => `
                <button type="button" class="ctp-item-btn ctp-hour-item" data-hour="${h}">${padZero(h)}</button>
              `).join('')}
            </div>
          </div>
          <div class="ctp-col-container">
            <div class="ctp-col-label">Minute</div>
            <div class="ctp-scroll-col ctp-minutes-list">
              ${Array.from({ length: 60 }, (_, i) => i).map(m => `
                <button type="button" class="ctp-item-btn ctp-minute-item" data-minute="${m}">${padZero(m)}</button>
              `).join('')}
            </div>
          </div>
        </div>

        <div class="ctp-footer">
          <button type="button" class="ctp-done-btn">Set Time</button>
        </div>
      `;

      document.body.appendChild(this.popover);

      // Cache elements
      this.closeBtn = this.popover.querySelector('.ctp-close-btn');
      this.digitHour = this.popover.querySelector('.ctp-digit-hour');
      this.digitMinute = this.popover.querySelector('.ctp-digit-minute');
      this.periodAm = this.popover.querySelector('.ctp-period-am');
      this.periodPm = this.popover.querySelector('.ctp-period-pm');
      this.hoursList = this.popover.querySelector('.ctp-hours-list');
      this.minutesList = this.popover.querySelector('.ctp-minutes-list');
      this.doneBtn = this.popover.querySelector('.ctp-done-btn');
    }

    bindEvents() {
      // Open / Close on trigger click
      this.triggerBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.toggle();
      });

      // Prevent clicks inside popover from bubbling to document
      this.popover.addEventListener('click', (e) => {
        e.stopPropagation();
      });

      // Close button
      this.closeBtn.addEventListener('click', () => {
        this.close();
      });

      // Click digit badges to change active field
      this.digitHour.addEventListener('click', () => {
        this.typedHourBuffer = '';
        this.setActiveField('hour');
        this.scrollToSelected('hour');
      });

      this.digitMinute.addEventListener('click', () => {
        this.typedMinuteBuffer = '';
        this.setActiveField('minute');
        this.scrollToSelected('minute');
      });

      // AM / PM Switch
      this.periodAm.addEventListener('click', () => {
        this.setPeriod('AM');
      });

      this.periodPm.addEventListener('click', () => {
        this.setPeriod('PM');
      });

      // Hour items click
      this.hoursList.addEventListener('click', (e) => {
        const btn = e.target.closest('.ctp-hour-item');
        if (!btn) return;
        const h = parseInt(btn.dataset.hour, 10);
        this.setHour(h);
      });

      // Minute items click
      this.minutesList.addEventListener('click', (e) => {
        const btn = e.target.closest('.ctp-minute-item');
        if (!btn) return;
        const m = parseInt(btn.dataset.minute, 10);
        this.setMinute(m);
      });

      // Done button
      this.doneBtn.addEventListener('click', () => {
        this.commit();
        this.close();
      });

      // Keyboard listener while popover is open
      document.addEventListener('keydown', (e) => {
        if (!this.isOpen) return;

        // 1. Number keys 0 - 9
        if (e.key >= '0' && e.key <= '9') {
          e.preventDefault();
          const d = parseInt(e.key, 10);

          if (this.activeField === 'hour') {
            if (this.typedHourBuffer === '') {
              if (d >= 2) {
                // If 2-9, hour is 02-09, immediately advance to minutes!
                this.selectedHour12 = d;
                this.typedHourBuffer = '';
                this.updateDisplay();
                this.scrollToSelected('hour');
                this.setActiveField('minute');
                this.typedMinuteBuffer = '';
              } else {
                // 0 or 1: wait for second digit
                this.typedHourBuffer = String(d);
                this.digitHour.textContent = d + '_';
              }
            } else {
              // Second digit of hour
              let h = parseInt(this.typedHourBuffer + d, 10);
              if (h === 0) h = 12;
              if (h > 12) h = 12;
              this.selectedHour12 = h;
              this.typedHourBuffer = '';
              this.updateDisplay();
              this.scrollToSelected('hour');
              // Auto move to minute!
              this.setActiveField('minute');
              this.typedMinuteBuffer = '';
            }
          } else if (this.activeField === 'minute') {
            if (this.typedMinuteBuffer === '') {
              if (d >= 6) {
                // Minute is 06-09
                this.selectedMinute = d;
                this.typedMinuteBuffer = '';
                this.updateDisplay();
                this.scrollToSelected('minute');
              } else {
                // 0 to 5
                this.typedMinuteBuffer = String(d);
                this.digitMinute.textContent = d + '_';
              }
            } else {
              // Second digit of minute
              let m = parseInt(this.typedMinuteBuffer + d, 10);
              if (m > 59) m = 59;
              this.selectedMinute = m;
              this.typedMinuteBuffer = '';
              this.updateDisplay();
              this.scrollToSelected('minute');
            }
          }
          return;
        }

        // 2. Tab, Colon, or ArrowRight: switch between hour and minute
        if (e.key === 'Tab' || e.key === ':' || e.key === 'ArrowRight') {
          e.preventDefault();
          this.typedHourBuffer = '';
          this.typedMinuteBuffer = '';
          if (this.activeField === 'hour') {
            this.setActiveField('minute');
            this.scrollToSelected('minute');
          } else {
            this.setActiveField('hour');
            this.scrollToSelected('hour');
          }
          this.updateDisplay();
          return;
        }

        // 3. ArrowLeft: switch back to hour
        if (e.key === 'ArrowLeft') {
          e.preventDefault();
          this.typedHourBuffer = '';
          this.typedMinuteBuffer = '';
          this.setActiveField('hour');
          this.updateDisplay();
          this.scrollToSelected('hour');
          return;
        }

        // 4. Backspace: clear buffer or move back to hour
        if (e.key === 'Backspace') {
          e.preventDefault();
          if (this.activeField === 'minute') {
            if (this.typedMinuteBuffer !== '') {
              this.typedMinuteBuffer = '';
              this.updateDisplay();
            } else {
              this.setActiveField('hour');
              this.updateDisplay();
              this.scrollToSelected('hour');
            }
          } else {
            this.typedHourBuffer = '';
            this.updateDisplay();
          }
          return;
        }

        // 5. Letter A or P: toggle AM / PM
        if (e.key === 'a' || e.key === 'A') {
          e.preventDefault();
          this.setPeriod('AM');
          return;
        }
        if (e.key === 'p' || e.key === 'P') {
          e.preventDefault();
          this.setPeriod('PM');
          return;
        }

        // 6. Up / Down arrow: increment / decrement current active field
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          this.typedHourBuffer = '';
          this.typedMinuteBuffer = '';
          if (this.activeField === 'hour') {
            let h = this.selectedHour12 + 1;
            if (h > 12) h = 1;
            this.setHour(h);
          } else {
            let m = this.selectedMinute + 1;
            if (m > 59) m = 0;
            this.setMinute(m);
          }
          return;
        }
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          this.typedHourBuffer = '';
          this.typedMinuteBuffer = '';
          if (this.activeField === 'hour') {
            let h = this.selectedHour12 - 1;
            if (h < 1) h = 12;
            this.setHour(h);
          } else {
            let m = this.selectedMinute - 1;
            if (m < 0) m = 59;
            this.setMinute(m);
          }
          return;
        }

        // 7. Enter: Confirm and close
        if (e.key === 'Enter') {
          e.preventDefault();
          this.commit();
          this.close();
          return;
        }

        // 8. Escape: Close
        if (e.key === 'Escape') {
          e.preventDefault();
          this.close();
          return;
        }
      });

      // Outside click to close
      document.addEventListener('click', (e) => {
        if (this.isOpen && !this.popover.contains(e.target) && !this.wrapper.contains(e.target)) {
          this.commit();
          this.close();
        }
      });

      // Reposition on resize and scroll
      window.addEventListener('resize', () => {
        if (this.isOpen) this.position();
      });
      window.addEventListener('scroll', () => {
        if (this.isOpen) this.position();
      }, true);

      // Form reset support
      if (this.input.form) {
        this.input.form.addEventListener('reset', () => {
          setTimeout(() => {
            const parsed = parseTimeString(this.input.value) || parseTimeString(this.options.defaultTime);
            if (parsed) {
              this.selectedHour12 = parsed.hour12;
              this.selectedMinute = parsed.minute;
              this.selectedPeriod = parsed.period;
              this.typedHourBuffer = '';
              this.typedMinuteBuffer = '';
              this.updateDisplay();
            }
          }, 20);
        });
      }
    }

    setActiveField(field) {
      this.activeField = field;
      this.digitHour.classList.toggle('active', field === 'hour');
      this.digitMinute.classList.toggle('active', field === 'minute');
    }

    setHour(h) {
      this.selectedHour12 = h;
      this.typedHourBuffer = '';
      this.updateDisplay();
      // Auto-move to minute after picking hour!
      this.setActiveField('minute');
      this.scrollToSelected('minute');
    }

    setMinute(m) {
      this.selectedMinute = m;
      this.typedMinuteBuffer = '';
      this.updateDisplay();
    }

    setPeriod(period) {
      this.selectedPeriod = period;
      this.updateDisplay();
    }

    getHour24() {
      let h = this.selectedHour12;
      if (this.selectedPeriod === 'PM' && h < 12) h += 12;
      if (this.selectedPeriod === 'AM' && h === 12) h = 0;
      return h;
    }

    commit() {
      this.typedHourBuffer = '';
      this.typedMinuteBuffer = '';
      this.updateDisplay();
    }

    updateDisplay() {
      const hStr = this.typedHourBuffer ? this.typedHourBuffer + '_' : padZero(this.selectedHour12);
      const mStr = this.typedMinuteBuffer ? this.typedMinuteBuffer + '_' : padZero(this.selectedMinute);

      this.digitHour.textContent = hStr;
      this.digitMinute.textContent = mStr;

      this.periodAm.classList.toggle('active', this.selectedPeriod === 'AM');
      this.periodPm.classList.toggle('active', this.selectedPeriod === 'PM');

      this.hoursList.querySelectorAll('.ctp-hour-item').forEach(btn => {
        const isSel = parseInt(btn.dataset.hour, 10) === this.selectedHour12;
        btn.classList.toggle('selected', isSel);
      });

      this.minutesList.querySelectorAll('.ctp-minute-item').forEach(btn => {
        const isSel = parseInt(btn.dataset.minute, 10) === this.selectedMinute;
        btn.classList.toggle('selected', isSel);
      });

      const formatted12 = formatTime12(this.selectedHour12, this.selectedMinute, this.selectedPeriod);
      this.timeDisplay.textContent = formatted12;

      // Update native hidden input value (24h)
      const val24 = formatTime24(this.getHour24(), this.selectedMinute);
      const outputVal = this.options.format === '12h' ? formatted12 : val24;

      if (this.input.value !== outputVal) {
        this.input.value = outputVal;
        this.input.dispatchEvent(new Event('input', { bubbles: true }));
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
        this.input.dispatchEvent(new CustomEvent('changeTime', {
          bubbles: true,
          detail: {
            hour24: this.getHour24(),
            hour12: this.selectedHour12,
            minute: this.selectedMinute,
            period: this.selectedPeriod,
            value24: val24,
            value12: formatted12
          }
        }));

        if (typeof this.options.onChange === 'function') {
          this.options.onChange(outputVal, {
            hour24: this.getHour24(),
            hour12: this.selectedHour12,
            minute: this.selectedMinute,
            period: this.selectedPeriod
          });
        }
      }
    }

    scrollToSelected(column) {
      setTimeout(() => {
        if (column === 'hour') {
          const sel = this.hoursList.querySelector('.ctp-hour-item.selected');
          if (sel) {
            this.hoursList.scrollTop = sel.offsetTop - (this.hoursList.clientHeight / 2) + (sel.clientHeight / 2);
          }
        } else if (column === 'minute') {
          const sel = this.minutesList.querySelector('.ctp-minute-item.selected');
          if (sel) {
            this.minutesList.scrollTop = sel.offsetTop - (this.minutesList.clientHeight / 2) + (sel.clientHeight / 2);
          }
        }
      }, 40);
    }

    position() {
      if (!this.wrapper || !this.popover) return;
      const rect = this.wrapper.getBoundingClientRect();
      const popoverHeight = this.popover.offsetHeight || 300;
      const popoverWidth = this.popover.offsetWidth || 280;

      // Position directly ABOVE the trigger button
      let top = rect.top + window.scrollY - popoverHeight - 8;
      let left = rect.left + window.scrollX;

      // Horizontal clamp inside viewport
      if (left + popoverWidth > window.innerWidth - 16) {
        left = window.innerWidth - popoverWidth - 16;
      }
      if (left < 16) left = 16;

      this.popover.style.top = `${top}px`;
      this.popover.style.left = `${left}px`;
    }

    open() {
      if (this.isOpen) return;

      // Close any other open popovers
      document.querySelectorAll('.ctp-popover.ctp-open').forEach(p => {
        if (p !== this.popover) {
          p.classList.remove('ctp-open');
          p.style.display = 'none';
        }
      });
      document.querySelectorAll('.ctp-trigger-btn.ctp-active').forEach(b => {
        if (b !== this.triggerBtn) b.classList.remove('ctp-active');
      });

      // Sync state from input
      const current = parseTimeString(this.input.value);
      if (current) {
        this.selectedHour12 = current.hour12;
        this.selectedMinute = current.minute;
        this.selectedPeriod = current.period;
      }

      this.typedHourBuffer = '';
      this.typedMinuteBuffer = '';
      this.setActiveField('hour');
      this.updateDisplay();

      this.popover.style.display = 'block';
      this.position();

      requestAnimationFrame(() => {
        this.popover.classList.add('ctp-open');
        this.triggerBtn.classList.add('ctp-active');
        this.isOpen = true;
      });

      this.scrollToSelected('hour');
      this.scrollToSelected('minute');
    }

    close() {
      if (!this.isOpen) return;
      this.popover.classList.remove('ctp-open');
      this.triggerBtn.classList.remove('ctp-active');
      this.isOpen = false;
      this.typedHourBuffer = '';
      this.typedMinuteBuffer = '';
      setTimeout(() => {
        if (!this.isOpen) {
          this.popover.style.display = 'none';
        }
      }, 180);
    }

    toggle() {
      if (this.isOpen) {
        this.commit();
        this.close();
      } else {
        this.open();
      }
    }

    destroy() {
      if (this.popover && this.popover.parentNode) {
        this.popover.parentNode.removeChild(this.popover);
      }
      if (this.wrapper && this.wrapper.parentNode) {
        this.wrapper.parentNode.insertBefore(this.input, this.wrapper);
        this.wrapper.parentNode.removeChild(this.wrapper);
        this.input.style.display = '';
      }
      delete this.input._customTimePicker;
    }
  }

  // Expose to window
  window.ModernTimePicker = ModernTimePicker;

  function initAll() {
    document.querySelectorAll('[data-custom-timepicker]').forEach(input => {
      new ModernTimePicker(input);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }

})(window, document);
