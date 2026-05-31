/**
 * Patient Signup Logic
 * Handles multi-step form, validation, and password strength checks.
 */

let currentStep = 1;
const totalSteps = 6;

function showStep(step) {
    document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
    // Safety check for mobile elements
    const stepEl = document.getElementById('step' + step);
    if (stepEl) stepEl.classList.add('active');

    // Back button is now strictly visible at all steps
}

function nextStep(step) {
    if (!validateStep(step)) return;
    if (step < totalSteps) {
        currentStep = step + 1;
        showStep(currentStep);
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    } else {
        window.location.href = 'patient-login.php';
    }
}

function validateStep(step) {
    const stepEl = document.getElementById('step' + step);
    if (!stepEl) return true;

    const inputs = stepEl.querySelectorAll('input:required, select:required');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.checkValidity()) {
            input.reportValidity();
            isValid = false;
        }
    });

    if (step === 2) {
        const ageInput = document.getElementById('m_age');
        if (ageInput) {
            const age = parseInt(ageInput.value);
            if (isNaN(age) || age < 1 || age > 110) {
                ageInput.setCustomValidity('Age must be between 1 and 110.');
                ageInput.reportValidity();
                isValid = false;
            } else {
                ageInput.setCustomValidity('');
            }
        }
    }

    if (step === 3) {
        const sexSelected = stepEl.querySelector('input[name="sex"]:checked');
        if (!sexSelected) {
            isValid = false;
        }
    }

    if (step === 6) {
        const pw = document.getElementById('m_password').value;
        const cpw = document.getElementById('m_confirm_password').value;
        if (pw !== cpw) {
            if (typeof toast === 'function') {
                toast("Passwords do not match.", "error");
            } else {
                alert("Passwords do not match.");
            }
            isValid = false;
        }
    }

    return isValid;
}

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPassword = input.getAttribute('type') === 'password';
    input.setAttribute('type', isPassword ? 'text' : 'password');
    btn.innerHTML = isPassword ?
        '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>' :
        '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>';
}

// Initialize password strengths
function initPwChecker(inputId, checkerId) {
    const input = document.getElementById(inputId);
    const checker = document.getElementById(checkerId);
    if (!input || !checker) return;

    const reqs = [
        { class: '.pw-req-length', regex: /.{8,}/ },
        { class: '.pw-req-upper', regex: /[A-Z]/ },
        { class: '.pw-req-number', regex: /[0-9]/ },
        { class: '.pw-req-special', regex: /[^A-Za-z0-9]/ }
    ];

    input.addEventListener('focus', function () {
        checker.classList.remove('hidden');
    });

    input.addEventListener('input', function (e) {
        const val = e.target.value;
        let passed = 0;

        reqs.forEach(req => {
            const el = checker.querySelector(req.class);
            const iconX = el.querySelector('.icon-x');
            const iconCheck = el.querySelector('.icon-check');
            if (req.regex.test(val)) {
                passed++;
                el.classList.remove('text-red-600');
                el.classList.add('text-green-600');
                iconX.classList.add('hidden');
                iconCheck.classList.remove('hidden');
            } else {
                el.classList.remove('text-green-600');
                el.classList.add('text-red-600');
                iconX.classList.remove('hidden');
                iconCheck.classList.add('hidden');
            }
        });

        const bar = checker.querySelector('.pw-bar');
        const label = checker.querySelector('.pw-label');
        const percent = (passed / reqs.length) * 100;

        bar.style.width = percent + '%';
        bar.className = 'h-full transition-all duration-300 pw-bar ';
        label.className = 'text-xs font-bold pw-label ';

        if (val.length === 0) {
            label.textContent = '';
            bar.style.backgroundColor = 'transparent';
        } else if (passed <= 1) {
            bar.style.backgroundColor = '#ef4444';
            label.style.color = '#ef4444';
            label.textContent = 'Weak';
        } else if (passed <= 3) {
            bar.style.backgroundColor = '#eab308';
            label.style.color = '#eab308';
            label.textContent = 'Medium';
        } else {
            bar.style.backgroundColor = '#22c55e';
            label.style.color = '#22c55e';
            label.textContent = 'Strong';
        }
    });
}

// Initialize password match checker
function initMatchChecker(pwdId, confirmId, indicatorId) {
    const pwd = document.getElementById(pwdId);
    const confirmPwd = document.getElementById(confirmId);
    const indicator = document.getElementById(indicatorId);

    if (!pwd || !confirmPwd || !indicator) return;

    function checkMatch() {
        const val1 = pwd.value;
        const val2 = confirmPwd.value;

        if (val2.length === 0) {
            indicator.classList.add('hidden');
            return;
        }

        indicator.classList.remove('hidden');
        if (val1 === val2) {
            indicator.textContent = 'Passwords match';
            indicator.style.color = '#22c55e';
        } else {
            indicator.textContent = 'Passwords do not match';
            indicator.style.color = '#ef4444';
        }
    }

    pwd.addEventListener('input', checkMatch);
    confirmPwd.addEventListener('input', checkMatch);
}

document.addEventListener('DOMContentLoaded', function () {
    // Handle Enter key for seamless steps on Mobile only
    const mobileForm = document.getElementById('signupFormMobile');
    if (mobileForm) {
        mobileForm.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (currentStep < totalSteps) {
                    nextStep(currentStep);
                } else {
                    if (validateStep(6)) {
                        this.submit();
                    }
                }
            }
        });
    }

    initPwChecker('d_password', 'd_pw_checker');
    initPwChecker('m_password', 'm_pw_checker');
    initMatchChecker('d_password', 'd_confirm_password', 'd_match_indicator');
    initMatchChecker('m_password', 'm_confirm_password', 'm_match_indicator');
});
