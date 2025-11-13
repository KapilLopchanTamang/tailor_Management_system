<?php
/**
 * Registration Page - Customers Only
 * Tailoring Management System
 */
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . getRoleRedirectUrl($_SESSION['user_role']));
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Validate CSRF token
    validateCSRF();
    
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $address = sanitize($_POST['address'] ?? '');
    
    try {
        // Validate passwords match
        if ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $result = registerCustomer($name, $email, $phone, $password, $address);
            
            if ($result['success']) {
                // Redirect to login with success message
                header('Location: ' . baseUrl('login.php?registered=1'));
                exit();
            } else {
                $error = $result['message'];
            }
        }
    } catch (Exception $e) {
        error_log('Registration error: ' . $e->getMessage());
        $error = 'An error occurred during registration. Please try again.';
    }
}

$pageTitle = "Register";
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">
                        <i class="bi bi-person-plus"></i> Create Account
                    </h3>
                    <p class="mb-0 small">Register as a new customer</p>
                </div>
                <div class="card-body p-4">
                    <!-- Error Alert -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm" novalidate>
                        <input type="hidden" name="register" value="1">
                        <?php echo csrfField(); ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="Enter your full name" required>
                                <div class="invalid-feedback">
                                    Please enter your full name.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            <small class="form-text text-muted">We'll never share your email with anyone else.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="Enter your phone number" required>
                                <div class="invalid-feedback">
                                    Please enter your phone number.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <textarea class="form-control" id="address" name="address" rows="2" 
                                          placeholder="Enter your address (optional)"></textarea>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter password (min. 6 characters)" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                                <div class="invalid-feedback">
                                    Password must be at least 6 characters long.
                                </div>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="form-text text-muted" id="passwordStrengthText">Password strength</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm your password" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="bi bi-eye" id="eyeIconConfirm"></i>
                                </button>
                                <div class="invalid-feedback">
                                    Passwords do not match.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms and Conditions</a>
                            </label>
                            <div class="invalid-feedback">
                                You must agree to the terms and conditions.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" id="registerBtn">
                            <i class="bi bi-person-plus"></i> Create Account
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <small class="text-muted">
                            Already have an account? 
                            <a href="<?php echo baseUrl('login.php'); ?>" class="text-decoration-none">Login here</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i> Registration Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="errorModalBody">
                <!-- Error message will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeIconConfirm = document.getElementById('eyeIconConfirm');
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordStrengthText = document.getElementById('passwordStrengthText');
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    });
    
    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        eyeIconConfirm.classList.toggle('bi-eye');
        eyeIconConfirm.classList.toggle('bi-eye-slash');
    });
    
    // Email validation
    email.addEventListener('input', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value && !emailRegex.test(this.value)) {
            this.setCustomValidity('Please enter a valid email address.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Password strength checker
    password.addEventListener('input', function() {
        const pwd = this.value;
        let strength = 0;
        let strengthText = 'Very Weak';
        let strengthColor = 'danger';
        
        if (pwd.length >= 6) strength++;
        if (pwd.length >= 8) strength++;
        if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) strength++;
        if (/\d/.test(pwd)) strength++;
        if (/[^a-zA-Z\d]/.test(pwd)) strength++;
        
        switch(strength) {
            case 0:
            case 1:
                strengthText = 'Very Weak';
                strengthColor = 'danger';
                break;
            case 2:
                strengthText = 'Weak';
                strengthColor = 'warning';
                break;
            case 3:
                strengthText = 'Medium';
                strengthColor = 'info';
                break;
            case 4:
                strengthText = 'Strong';
                strengthColor = 'success';
                break;
            case 5:
                strengthText = 'Very Strong';
                strengthColor = 'success';
                break;
        }
        
        const percentage = (strength / 5) * 100;
        passwordStrength.style.width = percentage + '%';
        passwordStrength.className = 'progress-bar bg-' + strengthColor;
        passwordStrengthText.textContent = 'Password strength: ' + strengthText;
        passwordStrengthText.className = 'form-text text-' + strengthColor;
    });
    
    // Confirm password validation
    confirmPassword.addEventListener('input', function() {
        if (this.value !== password.value) {
            this.setCustomValidity('Passwords do not match.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    password.addEventListener('input', function() {
        if (confirmPassword.value && confirmPassword.value !== this.value) {
            confirmPassword.setCustomValidity('Passwords do not match.');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        // Custom validation
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            e.stopPropagation();
            confirmPassword.setCustomValidity('Passwords do not match.');
            confirmPassword.classList.add('is-invalid');
        } else {
            confirmPassword.setCustomValidity('');
        }
        
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
    
    // Show error modal if error exists
    <?php if ($error): ?>
    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    document.getElementById('errorModalBody').textContent = <?php echo json_encode($error); ?>;
    errorModal.show();
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

