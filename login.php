<?php
/**
 * Login Page
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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Validate CSRF token
    validateCSRF();
    
    $emailOrUsername = sanitize($_POST['email_or_username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    try {
        $result = loginUser($emailOrUsername, $password);
        
        if ($result['success']) {
            // Redirect based on role
            header('Location: ' . getRoleRedirectUrl($result['user']['role']));
            exit();
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        $error = 'An error occurred during login. Please try again.';
    }
}

// Check for success messages from registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Registration successful! Please login with your credentials.';
}

// Check for password reset success
if (isset($_GET['reset']) && $_GET['reset'] == 'success') {
    $success = 'Password has been reset successfully! Please login with your new password.';
}

$pageTitle = "Login";
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-scissors display-1 text-primary"></i>
                        <h2 class="mt-3 mb-1">Tailoring Management</h2>
                        <p class="text-muted">Sign in to your account</p>
                    </div>
                    
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
                    
                    <form method="POST" action="" id="loginForm" novalidate>
                        <input type="hidden" name="login" value="1">
                        <?php echo csrfField(); ?>
                        
                        <div class="mb-3">
                            <label for="email_or_username" class="form-label">Email or Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="email_or_username" name="email_or_username" 
                                       placeholder="Enter your email or username" required autofocus>
                                <div class="invalid-feedback">
                                    Please enter your email or username.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                                <div class="invalid-feedback">
                                    Please enter your password.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" id="loginBtn">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <a href="<?php echo baseUrl('forgot_password.php'); ?>" class="text-decoration-none">Forgot password?</a>
                        </small>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <small class="text-muted">
                            Don't have an account? 
                            <a href="<?php echo baseUrl('register.php'); ?>" class="text-decoration-none">Register here</a>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    &copy; <?php echo date('Y'); ?> Tailoring Management System. All rights reserved.
                </small>
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
// Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const emailOrUsername = document.getElementById('email_or_username');
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
    
    // Real-time validation
    emailOrUsername.addEventListener('input', function() {
        if (this.value.trim() === '') {
            this.setCustomValidity('Please enter your email or username.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    password.addEventListener('input', function() {
        if (this.value.trim() === '') {
            this.setCustomValidity('Please enter your password.');
        } else {
            this.setCustomValidity('');
        }
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

