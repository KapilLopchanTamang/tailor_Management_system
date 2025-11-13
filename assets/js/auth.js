/**
 * Authentication Form Validation
 * Tailoring Management System
 */

// Email validation regex
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Validate email format
 */
function validateEmail(email) {
    return emailRegex.test(email);
}

/**
 * Check password strength
 * Returns object with strength level and feedback
 */
function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    // Length checks
    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Character type checks
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    // Determine strength level
    let level = 'weak';
    let color = 'danger';
    
    if (strength <= 2) {
        level = 'very weak';
        color = 'danger';
        feedback.push('Use at least 6 characters');
    } else if (strength <= 4) {
        level = 'weak';
        color = 'warning';
        feedback.push('Add uppercase letters and numbers');
    } else if (strength <= 6) {
        level = 'medium';
        color = 'info';
        feedback.push('Good! Consider adding special characters');
    } else {
        level = 'strong';
        color = 'success';
        feedback.push('Excellent password strength');
    }
    
    return {
        level: level,
        color: color,
        strength: strength,
        feedback: feedback
    };
}

/**
 * Validate phone number (basic validation)
 */
function validatePhone(phone) {
    // Remove all non-digit characters
    const digits = phone.replace(/\D/g, '');
    // Check if it has at least 10 digits
    return digits.length >= 10;
}

/**
 * Show password strength indicator
 */
function showPasswordStrength(input, strengthBar, strengthText) {
    if (!input || !strengthBar || !strengthText) return;
    
    input.addEventListener('input', function() {
        const password = this.value;
        if (password.length === 0) {
            strengthBar.style.width = '0%';
            strengthText.textContent = 'Password strength';
            strengthText.className = 'form-text text-muted';
            return;
        }
        
        const result = checkPasswordStrength(password);
        const percentage = Math.min((result.strength / 7) * 100, 100);
        
        strengthBar.style.width = percentage + '%';
        strengthBar.className = 'progress-bar bg-' + result.color;
        strengthText.textContent = 'Password strength: ' + result.level;
        strengthText.className = 'form-text text-' + result.color;
    });
}

/**
 * Toggle password visibility
 */
function togglePasswordVisibility(toggleButton, passwordInput, iconElement) {
    if (!toggleButton || !passwordInput || !iconElement) return;
    
    toggleButton.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        iconElement.classList.toggle('bi-eye');
        iconElement.classList.toggle('bi-eye-slash');
    });
}

/**
 * Validate form with custom rules
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Show modal with message
 */
function showModal(modalId, title, message, type = 'info') {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const modalTitle = modal.querySelector('.modal-title');
    const modalBody = modal.querySelector('.modal-body');
    const modalHeader = modal.querySelector('.modal-header');
    
    if (modalTitle) modalTitle.innerHTML = title;
    if (modalBody) modalBody.textContent = message;
    
    // Set header color based on type
    if (modalHeader) {
        modalHeader.className = 'modal-header bg-' + (type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary') + ' text-white';
    }
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/**
 * Initialize authentication forms
 */
function initAuthForms() {
    // Password toggle for all password fields
    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        const targetId = button.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        const icon = button.querySelector('i');
        if (passwordInput && icon) {
            togglePasswordVisibility(button, passwordInput, icon);
        }
    });
    
    // Password strength indicators
    document.querySelectorAll('[data-password-strength]').forEach(input => {
        const strengthBarId = input.getAttribute('data-strength-bar');
        const strengthTextId = input.getAttribute('data-strength-text');
        const strengthBar = document.getElementById(strengthBarId);
        const strengthText = document.getElementById(strengthTextId);
        if (strengthBar && strengthText) {
            showPasswordStrength(input, strengthBar, strengthText);
        }
    });
    
    // Email validation
    document.querySelectorAll('input[type="email"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validateEmail(this.value)) {
                this.setCustomValidity('Please enter a valid email address.');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Phone validation
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validatePhone(this.value)) {
                this.setCustomValidity('Please enter a valid phone number.');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuthForms);
} else {
    initAuthForms();
}

