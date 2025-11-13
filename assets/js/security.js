/**
 * Security Utilities JavaScript
 * Tailoring Management System
 * Includes: Idle detection, auto-logout, form validation helpers
 */

(function() {
    'use strict';
    
    let idleTimer = null;
    let idleTimeout = 30 * 60 * 1000; // 30 minutes in milliseconds
    let warningTimeout = 5 * 60 * 1000; // 5 minutes warning before logout
    let warningShown = false;
    let lastActivity = Date.now();
    
    /**
     * Initialize idle detection
     */
    function initIdleDetection() {
        // Reset timer on user activity
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.addEventListener(event, resetIdleTimer, true);
        });
        
        // Start idle timer
        startIdleTimer();
    }
    
    /**
     * Reset idle timer
     */
    function resetIdleTimer() {
        lastActivity = Date.now();
        warningShown = false;
        clearTimeout(idleTimer);
        startIdleTimer();
        
        // Hide warning if shown
        const warningModal = document.getElementById('idleWarningModal');
        if (warningModal) {
            const modal = bootstrap.Modal.getInstance(warningModal);
            if (modal) {
                modal.hide();
            }
        }
    }
    
    /**
     * Start idle timer
     */
    function startIdleTimer() {
        clearTimeout(idleTimer);
        
        // Show warning before logout
        idleTimer = setTimeout(function() {
            if (!warningShown) {
                showIdleWarning();
                warningShown = true;
                
                // Auto-logout after warning period
                idleTimer = setTimeout(function() {
                    logoutDueToIdle();
                }, warningTimeout);
            }
        }, idleTimeout - warningTimeout);
    }
    
    /**
     * Show idle warning modal
     */
    function showIdleWarning() {
        // Check if modal exists, if not create it
        let modal = document.getElementById('idleWarningModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'idleWarningModal';
            modal.className = 'modal fade';
            modal.setAttribute('data-bs-backdrop', 'static');
            modal.setAttribute('data-bs-keyboard', 'false');
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">
                                <i class="bi bi-exclamation-triangle"></i> Session Timeout Warning
                            </h5>
                        </div>
                        <div class="modal-body">
                            <p>You have been inactive for a while. Your session will expire in <strong id="countdown">5:00</strong> minutes.</p>
                            <p>Click "Stay Logged In" to continue your session.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="stayLoggedInBtn">
                                <i class="bi bi-check-circle"></i> Stay Logged In
                            </button>
                            <button type="button" class="btn btn-secondary" id="logoutNowBtn">
                                <i class="bi bi-box-arrow-right"></i> Logout Now
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add event listeners
            document.getElementById('stayLoggedInBtn').addEventListener('click', function() {
                resetIdleTimer();
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });
            
            document.getElementById('logoutNowBtn').addEventListener('click', function() {
                logoutDueToIdle();
            });
        }
        
        // Show modal and start countdown
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Start countdown
        let seconds = 300; // 5 minutes
        const countdownElement = document.getElementById('countdown');
        const countdownInterval = setInterval(function() {
            seconds--;
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            if (countdownElement) {
                countdownElement.textContent = minutes + ':' + (secs < 10 ? '0' : '') + secs;
            }
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }
    
    /**
     * Logout due to idle timeout
     */
    function logoutDueToIdle() {
        // Show logout message
        alert('Your session has expired due to inactivity. You will be logged out.');
        
        // Redirect to logout
        const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '/TMS/';
        window.location.href = baseUrl + 'includes/logout.php?timeout=1';
    }
    
    /**
     * Show loading spinner
     */
    function showLoading(element) {
        if (!element) return;
        
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('role', 'status');
        spinner.innerHTML = '<span class="visually-hidden">Loading...</span>';
        
        if (element.tagName === 'BUTTON' || element.tagName === 'INPUT') {
            element.disabled = true;
            const originalText = element.innerHTML;
            element.setAttribute('data-original-text', originalText);
            element.innerHTML = spinner.outerHTML + ' Processing...';
        }
    }
    
    /**
     * Hide loading spinner
     */
    function hideLoading(element) {
        if (!element) return;
        
        if (element.tagName === 'BUTTON' || element.tagName === 'INPUT') {
            element.disabled = false;
            const originalText = element.getAttribute('data-original-text');
            if (originalText) {
                element.innerHTML = originalText;
                element.removeAttribute('data-original-text');
            }
        }
    }
    
    /**
     * Validate email format
     */
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Validate phone number (basic validation)
     */
    function validatePhone(phone) {
        const re = /^[0-9+\-\s()]+$/;
        return re.test(phone) && phone.replace(/[^0-9]/g, '').length >= 10;
    }
    
    /**
     * Sanitize input (client-side)
     */
    function sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Only initialize idle detection if user is logged in
        const body = document.body;
        if (body && body.classList.contains('logged-in') || document.querySelector('.navbar')) {
            initIdleDetection();
        }
        
        // Add loading spinners to forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn && !form.classList.contains('no-loading')) {
                    showLoading(submitBtn);
                }
            });
        });
        
        // Add form validation
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value && !validateEmail(this.value)) {
                    this.setCustomValidity('Please enter a valid email address');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                }
            });
        });
        
        const phoneInputs = document.querySelectorAll('input[type="tel"], input[name*="phone"]');
        phoneInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value && !validatePhone(this.value)) {
                    this.setCustomValidity('Please enter a valid phone number');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
    
    // Export functions to global scope
    window.TMSSecurity = {
        showLoading: showLoading,
        hideLoading: hideLoading,
        validateEmail: validateEmail,
        validatePhone: validatePhone,
        sanitizeInput: sanitizeInput,
        resetIdleTimer: resetIdleTimer
    };
})();

