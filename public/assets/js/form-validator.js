/**
 * CitiLife System - Unified Form & Inline Validator Engine
 * Provides instant inline error messages directly on input fields/actions
 * and action-level toast notifications across all modals and forms.
 */
(function (window, document) {
  'use strict';

  const FormValidator = {
    // Styling classes for invalid inputs
    errorInputClasses: ['border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20', 'bg-red-50/20'],

    /**
     * Helper to determine if an element is currently visible and active for validation
     */
    isElementVisible: function (el) {
      if (!el || el.disabled || el.type === 'hidden') return false;
      // If element is inside an explicitly hidden container
      if (el.closest('.hidden') || el.closest('[style*="display: none"]') || el.closest('[style*="display:none"]')) {
        return false;
      }
      return true;
    },

    /**
     * Check if a field is required through any standard attribute or pattern
     */
    isFieldRequired: function (el) {
      if (!el) return false;
      if (el.hasAttribute('required') || el.required) return true;
      if (el.classList.contains('req-new') || el.classList.contains('required')) return true;
      if (el.hasAttribute('data-required') && el.getAttribute('data-required') !== 'false') return true;

      // 1. Check if associated label via for="id" contains an asterisk '*'
      if (el.id) {
        const extLabel = document.querySelector(`label[for="${el.id}"]`);
        if (extLabel && extLabel.innerText.includes('*')) return true;
      }

      // 2. Check if wrapping label contains an asterisk '*'
      const parentLabel = el.closest('label');
      if (parentLabel && parentLabel.innerText.includes('*')) return true;

      // 3. Check if any enclosing container has a label with an asterisk '*'
      let cur = el.parentElement;
      while (cur && cur.tagName !== 'FORM' && cur.tagName !== 'BODY') {
        const siblingLabel = cur.querySelector('label');
        if (siblingLabel && siblingLabel.innerText && siblingLabel.innerText.includes('*')) return true;
        cur = cur.parentElement;
      }

      return false;
    },

    /**
     * Show an inline error message directly beneath a field
     * @param {HTMLElement|string} target - The input element or selector
     * @param {string} message - Validation error message to display
     */
    showError: function (target, message) {
      const el = typeof target === 'string' ? document.querySelector(target) : target;
      if (!el) return;

      this.clearError(el);

      // Add visual error classes to the input element
      this.errorInputClasses.forEach(cls => el.classList.add(cls));
      el.classList.remove('border-gray-300', 'border-gray-200');
      el.setAttribute('aria-invalid', 'true');

      // Find best placement for inline error container:
      let container = el;
      const msComp = el.closest('.exam-ms-component');
      if (msComp) {
        container = msComp;
        const msBox = msComp.querySelector('.exam-ms-box');
        if (msBox) {
          this.errorInputClasses.forEach(cls => msBox.classList.add(cls));
          msBox.classList.remove('border-gray-300', 'border-gray-200');
        }
      } else {
        const parent = el.parentElement;
        if (parent) {
          if (
            parent.classList.contains('relative') ||
            parent.classList.contains('input-group') ||
            (parent.classList.contains('flex') && parent.children.length > 1)
          ) {
            container = parent;
          }
        }
      }

      // Create inline error element
      const errorDiv = document.createElement('div');
      errorDiv.className = 'citilife-inline-error text-xs text-red-600 font-semibold flex items-center gap-1.5 mt-1.5 animate-in fade-in slide-in-from-top-1';
      errorDiv.setAttribute('role', 'alert');
      errorDiv.innerHTML = `
        <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" stroke-width="2"></circle>
          <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"></line>
          <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2.5" stroke-linecap="round"></line>
        </svg>
        <span class="error-text">${message}</span>
      `;

      // Insert after the container
      if (container.nextSibling) {
        container.parentNode.insertBefore(errorDiv, container.nextSibling);
      } else {
        container.parentNode.appendChild(errorDiv);
      }

      // Automatically re-evaluate and clear error in REAL TIME as the user types
      if (!el.__validatorBound) {
        el.__validatorBound = true;
        ['input', 'keyup', 'change'].forEach(evtName => {
          el.addEventListener(evtName, () => {
            if (el.getAttribute('aria-invalid') === 'true') {
              const err = FormValidator.validateField(el);
              if (!err) {
                FormValidator.clearError(el);
              } else {
                // Update error text if it changed
                const textSpan = errorDiv.querySelector('.error-text');
                if (textSpan && textSpan.innerText !== err) {
                  textSpan.innerText = err;
                }
              }
            }
          });
        });
      }
    },

    /**
     * Clear inline error on a single field
     * @param {HTMLElement|string} target
     */
    clearError: function (target) {
      const el = typeof target === 'string' ? document.querySelector(target) : target;
      if (!el) return;

      this.errorInputClasses.forEach(cls => el.classList.remove(cls));
      el.removeAttribute('aria-invalid');

      // Restore default neutral border
      if (!el.classList.contains('border-gray-300') && !el.classList.contains('border-gray-200')) {
        el.classList.add('border-gray-300');
      }

      // Handle exam-ms-component box
      const msComp = el.closest('.exam-ms-component');
      if (msComp) {
        const msBox = msComp.querySelector('.exam-ms-box');
        if (msBox) {
          this.errorInputClasses.forEach(cls => msBox.classList.remove(cls));
          if (!msBox.classList.contains('border-gray-300')) msBox.classList.add('border-gray-300');
        }
      }

      // Find existing error container
      let container = el;
      if (msComp) {
        container = msComp;
      } else {
        const parent = el.parentElement;
        if (parent && (
          parent.classList.contains('relative') ||
          parent.classList.contains('input-group') ||
          (parent.classList.contains('flex') && parent.children.length > 1)
        )) {
          container = parent;
        }
      }

      const nextEl = container.nextElementSibling;
      if (nextEl && nextEl.classList.contains('citilife-inline-error')) {
        nextEl.remove();
      } else if (container.parentElement) {
        // Fallback search inside container parent
        const inlineError = container.parentElement.querySelector('.citilife-inline-error');
        if (inlineError) inlineError.remove();
      }
    },

    /**
     * Clear all inline errors inside a form or modal container
     * @param {HTMLElement|string} containerTarget
     */
    clearAllErrors: function (containerTarget) {
      const container = typeof containerTarget === 'string' ? document.querySelector(containerTarget) : containerTarget;
      if (!container) return;

      container.querySelectorAll('.citilife-inline-error').forEach(err => err.remove());
      container.querySelectorAll('[aria-invalid="true"]').forEach(el => {
        this.errorInputClasses.forEach(cls => el.classList.remove(cls));
        el.removeAttribute('aria-invalid');
        if (!el.classList.contains('border-gray-300') && !el.classList.contains('border-gray-200')) {
          el.classList.add('border-gray-300');
        }
      });
    },

    /**
     * Validate a specific input field
     * @param {HTMLElement} el
     * @returns {string|null} - Error message if invalid, null if valid
     */
    validateField: function (el) {
      if (!this.isElementVisible(el)) {
        return null;
      }

      const val = (el.value || '').trim();
      const labelText = this.getFieldLabel(el);
      const isReq = this.isFieldRequired(el);

      // 1. Required Check
      if (isReq && !val) {
        return `${labelText} is required.`;
      }

      // If value is empty and not required, format checks are skipped
      if (!val) return null;

      // 2. Email Validation
      if (el.type === 'email' || el.name?.toLowerCase().includes('email') || el.id?.toLowerCase().includes('email')) {
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(val)) {
          return "Please enter a valid email address with '@'.";
        }
      }

      // 3. Contact / Phone Number (09XXXXXXXXX)
      if (el.id?.toLowerCase().includes('contact') || el.name?.toLowerCase().includes('contact') || el.type === 'tel') {
        const digits = val.replace(/\D/g, '');
        if (!/^09\d{9}$/.test(digits)) {
          return 'Contact number must be 11 digits starting with 09.';
        }
      }

      // 4. PhilHealth ID Number (Format: XX-XXXXXXXXX-X or 12 digits)
      if (
        (el.id?.toLowerCase().includes('philhealth') && el.id?.toLowerCase().includes('id')) ||
        el.name?.toLowerCase().includes('philhealth_id') ||
        el.id === 'id-number'
      ) {
        const raw = val.replace(/\D/g, '');
        if (raw.length !== 12) {
          return 'PhilHealth ID must contain 12 digits (format: XX-XXXXXXXXX-X).';
        }
      }

      // 5. Birthdate Validation (cannot be after today)
      if (
        el.id?.toLowerCase().includes('birthdate') ||
        el.name?.toLowerCase().includes('birthdate') ||
        el.id?.toLowerCase().includes('bday')
      ) {
        const d = new Date();
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;
        if (val > todayStr) {
          return 'Please select a valid birthdate.';
        }
      }

      // 6. Min Length
      const minLength = el.getAttribute('minlength');
      if (minLength && val.length < parseInt(minLength, 10)) {
        if (el.id?.toLowerCase().includes('contact') || el.name?.toLowerCase().includes('contact') || el.type === 'tel') {
          return 'Contact number must be 11 digits starting with 09.';
        }
        return `${labelText} must be at least ${minLength} characters.`;
      }

      // 7. Max Length
      const maxLength = el.getAttribute('maxlength');
      if (maxLength && val.length > parseInt(maxLength, 10)) {
        return `${labelText} cannot exceed ${maxLength} characters.`;
      }

      // 8. Pattern check
      const pattern = el.getAttribute('pattern');
      if (pattern) {
        try {
          const reg = new RegExp(`^(?:${pattern})$`);
          if (!reg.test(val)) {
            if (el.id?.toLowerCase().includes('contact') || el.name?.toLowerCase().includes('contact') || el.type === 'tel') {
              return 'Contact number must be 11 digits starting with 09.';
            }
            if (el.title) {
              return el.title;
            }
            return `Invalid format for ${labelText}.`;
          }
        } catch (e) {
          // ignore regex errors
        }
      }

      // 9. HTML5 native validity fallback with clean messages (NEVER show "Please match the requested format.")
      if (el.validity && !el.validity.valid) {
        if (el.validity.valueMissing) {
          return `${labelText} is required.`;
        }
        if (el.validity.typeMismatch && el.type === 'email') {
          return "Please enter a valid email address with '@'.";
        }
        if (el.validity.patternMismatch) {
          if (el.id?.toLowerCase().includes('contact') || el.name?.toLowerCase().includes('contact') || el.type === 'tel') {
            return 'Contact number must be 11 digits starting with 09.';
          }
          if (el.title) return el.title;
          return `Invalid format for ${labelText}.`;
        }
        if (el.validity.tooShort) {
          return `${labelText} is too short.`;
        }
        if (el.validity.tooLong) {
          return `${labelText} is too long.`;
        }
      // 5. Custom dynamic data-rules support
      const rules = el.dataset.rules ? el.dataset.rules.split('|') : [];
      for (const rule of rules) {
        if (rule.startsWith('min:')) {
          const minLen = parseInt(rule.split(':')[1], 10);
          if (val.length < minLen) {
            return `${labelText} must be at least ${minLen} characters.`;
          }
        }
        if (rule.startsWith('max:')) {
          const maxLen = parseInt(rule.split(':')[1], 10);
          if (val.length > maxLen) {
            return `${labelText} cannot exceed ${maxLen} characters.`;
          }
        }
      }

      // 6. Special custom field rules
      if (el.id === 'search-patient') {
        const formMode = document.getElementById('form-mode');
        if (formMode && formMode.value === 'existing-patient') {
          const selectedId = document.getElementById('existing-patient-id')?.value;
          if (!selectedId) {
            return 'Please search and select an existing patient.';
          }
        }
        return null;
      }

      return null;
    },

    /**
     * Helper to get user-friendly label of a field
     */
    getFieldLabel: function (el) {
      if (el.dataset.label) return el.dataset.label;

      const msComp = el.closest('.exam-ms-component');
      if (msComp) {
        const customLbl = msComp.getAttribute('data-label') || msComp.parentElement?.querySelector('label')?.innerText;
        if (customLbl) {
          return customLbl.replace(/\(Optional\)/gi, '').replace(/[*:]/g, '').trim();
        }
        return 'Examination Procedure';
      }

      // 1. Prioritize explicit <label for="id">
      if (el.id) {
        const label = document.querySelector(`label[for="${el.id}"]`);
        if (label && label.innerText) {
          const clean = label.innerText.replace(/\(Optional\)/gi, '').replace(/[*:]/g, '').trim();
          if (clean) return clean;
        }
      }

      // 2. Check for wrapping parent <label>
      const parentLabel = el.closest('label');
      if (parentLabel && parentLabel.innerText) {
        const clean = parentLabel.innerText.replace(/\(Optional\)/gi, '').replace(/[*:]/g, '').trim();
        if (clean) return clean;
      }

      // 3. Check for sibling <label> inside any ancestor container
      let cur = el.parentElement;
      while (cur && cur.tagName !== 'FORM' && cur.tagName !== 'BODY') {
        const siblingLabel = cur.querySelector('label');
        if (siblingLabel && siblingLabel.innerText && !siblingLabel.contains(el)) {
          const clean = siblingLabel.innerText.replace(/\(Optional\)/gi, '').replace(/[*:]/g, '').trim();
          if (clean) return clean;
        }
        cur = cur.parentElement;
      }

      // 4. Fallback to name or id formatted nicely (e.g. contact_number -> Contact Number)
      const name = el.name || el.id;
      if (name) {
        return name.replace(/[_-]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
      }

      // 5. Last resort: placeholder (only if not a sample number or email)
      if (el.placeholder && !el.placeholder.includes('•') && !/^\d+$/.test(el.placeholder) && !el.placeholder.includes('@')) {
        const cleanPh = el.placeholder.replace(/^Select\s+/i, '').replace(/\.+$/, '').trim();
        if (cleanPh.length > 2 && cleanPh.length < 35) {
          return cleanPh;
        }
      }

      return 'This field';
    },

    /**
     * Validate an entire form or container element
     * Highlights all invalid fields, focuses first one, and fires a toast
     * @param {HTMLElement|string} containerTarget
     * @returns {boolean} - true if valid, false if invalid
     */
    validate: function (containerTarget) {
      const container = typeof containerTarget === 'string' ? document.querySelector(containerTarget) : containerTarget;
      if (!container) return true;

      this.clearAllErrors(container);

      const fields = container.querySelectorAll('input, select, textarea');
      let firstErrorField = null;
      let firstErrorMessage = '';
      let hasError = false;

      fields.forEach(el => {
        const error = this.validateField(el);
        if (error) {
          this.showError(el, error);
          hasError = true;
          if (!firstErrorField) {
            firstErrorField = el;
            firstErrorMessage = error;
          }
        }
      });

      if (hasError && firstErrorField) {
        // Focus first invalid field without jumping the entire page
        try {
          firstErrorField.focus({ preventScroll: false });
          firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (e) {
          firstErrorField.focus();
        }

        // Fire toast notification simultaneously
        if (typeof window.toast === 'function') {
          window.toast(firstErrorMessage, 'error');
        } else if (typeof Swal !== 'undefined') {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: firstErrorMessage,
            showConfirmButton: false,
            timer: 3500
          });
        }
        return false;
      }

      return true;
    }
  };

  /**
   * Universal field interaction validator on focusout / blur / change:
   * As soon as a user enters incomplete or invalid data and clicks another field or leaves,
   * the inline error appears immediately beneath the field!
   */
  function handleFieldBlur(e) {
    const el = e.target;
    if (!el || !['INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName)) return;
    if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button' || el.disabled || el.readOnly) return;

    // Skip special UI elements like calendar popups
    if (el.closest('.datepicker')) return;

    // Special handling for search-patient input
    if (el.id === 'search-patient') {
      setTimeout(() => {
        const formMode = document.getElementById('form-mode')?.value;
        const selectedId = document.getElementById('existing-patient-id')?.value;
        if (formMode === 'existing-patient' && !selectedId) {
          FormValidator.showError(el, 'Please search and select an existing patient.');
        } else if (selectedId) {
          FormValidator.clearError(el);
        }
      }, 200);
      return;
    }

    const val = (el.value || '').trim();
    const isReq = FormValidator.isFieldRequired(el);

    // If field is empty and not required, clear any existing error and return
    if (!val && !isReq) {
      FormValidator.clearError(el);
      return;
    }

    // If user entered a value (even incomplete) OR if a required field was touched
    if (val.length > 0 || (isReq && el.hasAttribute('data-touched'))) {
      const error = FormValidator.validateField(el);
      if (error) {
        FormValidator.showError(el, error);
      } else {
        FormValidator.clearError(el);
      }
    }
  }

  // Mark field as touched when user focuses into it
  document.addEventListener('focus', function (e) {
    const el = e.target;
    if (el && ['INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName)) {
      el.setAttribute('data-touched', 'true');
    }
  }, true);

  document.addEventListener('focusin', function (e) {
    const el = e.target;
    if (el && ['INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName)) {
      el.setAttribute('data-touched', 'true');
    }
  }, true);

  // Trigger inline validation immediately when clicking away, tabbing to next field, or clicking another field
  document.addEventListener('focusout', handleFieldBlur, true);
  document.addEventListener('blur', handleFieldBlur, true);
  document.addEventListener('change', handleFieldBlur, true);

  // Capture HTML5 native invalid events globally so browser bubbles don't cause topbar jumps or generic messages
  document.addEventListener('invalid', function (e) {
    const el = e.target;
    if (!el || !el.form) return;

    // Suppress browser default popup tooltip
    e.preventDefault();

    const msg = FormValidator.validateField(el) || 'Please fill out this field correctly.';
    FormValidator.showError(el, msg);

    // If this is the first invalid element in the form, trigger toast and focus
    const form = el.form;
    if (!form.__hasShownFirstErrorToast) {
      form.__hasShownFirstErrorToast = true;
      if (typeof window.toast === 'function') {
        window.toast(msg, 'error');
      }

      try {
        el.focus({ preventScroll: false });
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } catch (err) {
        el.focus();
      }

      setTimeout(() => {
        delete form.__hasShownFirstErrorToast;
      }, 500);
    }
  }, true);

  // Intercept Next / Submit button clicks to ensure all fields are validated before proceeding
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('button, a[role="button"], input[type="submit"]');
    if (!btn) return;

    // Check if clicking a submit button or a Next action button
    const btnText = (btn.innerText || btn.value || '').trim().toLowerCase();
    const isActionBtn = btn.type === 'submit' || btn.classList.contains('btn-next') || btnText.includes('next');

    if (isActionBtn) {
      const container = btn.closest('form') || btn.closest('.modal') || btn.closest('[id$="Modal"]') || btn.closest('main');
      if (container && !container.hasAttribute('data-no-validate')) {
        const isValid = FormValidator.validate(container);
        if (!isValid) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        }
      }
    }
  }, true);

  // Global submit interceptor for modal forms to run validation inline
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || form.hasAttribute('data-no-validate')) return;

    // Check if form is inside a modal or explicitly marked for inline validation
    const isInsideModal = form.closest('.modal') || form.closest('[id$="Modal"]') || form.closest('.fixed');
    const isValidationRequired = isInsideModal || form.hasAttribute('data-validate');

    if (isValidationRequired) {
      const isValid = FormValidator.validate(form);
      if (!isValid) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
      }
    }
  }, false);

  // Expose globally
  window.FormValidator = FormValidator;

})(window, document);
