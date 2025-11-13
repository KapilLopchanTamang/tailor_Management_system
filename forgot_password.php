<?php
/**
 * Forgot Password Page
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
$showResetForm = false;
$token = $_GET['token'] ?? '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    // Validate CSRF token
    validateCSRF();
    
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        try {
            $result = generatePasswordResetToken($email);
            
            if ($result['success']) {
                // Send email (placeholder - in production, use proper email service)
                if ($result['token']) {
                    sendPasswordResetEmail($email, $result['token']);
                }
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Handle password reset with token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    // Validate CSRF token
    validateCSRF();
    
    $token = sanitize($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($token) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } else if ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $result = resetPassword($token, $password);
            
            if ($result['success']) {
                header('Location: ' . baseUrl('login.php?reset=success'));
                exit();
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Check if token is provided in URL
if (!empty($token)) {
    $showResetForm = true;
    // Verify token is valid
    try {
        if ($pdo !== null) {
            $stmt = $pdo->prepare("SELECT prt.user_id, prt.expires_at FROM password_reset_tokens prt WHERE prt.token = ? AND prt.expires_at > NOW()");
            $stmt->execute([$token]);
            if (!$stmt->fetch()) {
                $error = 'Invalid or expired reset token.';
                $showResetForm = false;
            }
        }
    } catch (PDOException $e) {
        $error = 'Error verifying token.';
        $showResetForm = false;
    }
}

$pageTitle = $showResetForm ? "Reset Password" : "Forgot Password";
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">
                        <i class="bi bi-key"></i> 
                        <?php echo $showResetForm ? 'Reset Password' : 'Forgot Password'; ?>
                    </h3>
                </div>
                <div class="card-body p-4">
                    <!-- Error Alert -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Success Alert -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($showResetForm): ?>
                        <!-- Reset Password Form -->
                        <form method="POST" action="" id="resetForm" novalidate>
                            <input type="hidden" name="reset_password" value="1">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <?php echo csrfField(); ?>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter new password (min. 6 characters)" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Password must be at least 6 characters long.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm new password" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bi bi-eye" id="eyeIconConfirm"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Passwords do not match.
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3" id="resetBtn">
                                <i class="bi bi-key-fill"></i> Reset Password
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Request Reset Form -->
                        <p class="text-muted mb-4">
                            Enter your email address and we'll send you a link to reset your password.
                        </p>
                        
                        <form method="POST" action="" id="forgotForm" novalidate>
                            <input type="hidden" name="request_reset" value="1">
                            <?php echo csrfField(); ?>
                            
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
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                                <i class="bi bi-send"></i> Send Reset Link
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <small class="text-muted">
                            Remember your password? 
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
                    <i class="bi bi-exclamation-triangle-fill"></i> Error
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

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="bi bi-check-circle-fill"></i> Success
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="successModalBody">
                <!-- Success message will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('<?php echo $showResetForm ? 'resetForm' : 'forgotForm'; ?>');
    
    <?php if ($showResetForm): ?>
    // Reset password form
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeIconConfirm = document.getElementById('eyeIconConfirm');
    
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
    <?php else: ?>
    // Forgot password form
    const email = document.getElementById('email');
    
    // Email validation
    email.addEventListener('input', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value && !emailRegex.test(this.value)) {
            this.setCustomValidity('Please enter a valid email address.');
        } else {
            this.setCustomValidity('');
        }
    });
    <?php endif; ?>
    
    // Form validation
    form.addEventListener('submit', function(e) {
        <?php if ($showResetForm): ?>
        // Custom validation for reset form
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            e.stopPropagation();
            confirmPassword.setCustomValidity('Passwords do not match.');
            confirmPassword.classList.add('is-invalid');
        } else {
            confirmPassword.setCustomValidity('');
        }
        <?php endif; ?>
        
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
    
    // Show success modal if success exists
    <?php if ($success): ?>
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    document.getElementById('successModalBody').textContent = <?php echo json_encode($success); ?>;
    successModal.show();
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

