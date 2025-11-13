<?php
/**
 * Authentication Functions
 * Tailoring Management System
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db_config.php';

/**
 * Validate user credentials and login
 * @param string $emailOrUsername Email or username
 * @param string $password Plain text password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function loginUser($emailOrUsername, $password) {
    global $pdo;
    
    if ($pdo === null) {
        return ['success' => false, 'message' => 'Database connection failed.', 'user' => null];
    }
    
    if (empty($emailOrUsername) || empty($password)) {
        return ['success' => false, 'message' => 'Please fill in all fields.', 'user' => null];
    }
    
    try {
        // Try to find user by email or username
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role, status FROM users WHERE (email = ? OR username = ?) AND status = 'active'");
        $stmt->execute([$emailOrUsername, $emailOrUsername]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email/username or password.', 'user' => null];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email/username or password.', 'user' => null];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        return ['success' => true, 'message' => 'Login successful!', 'user' => $user];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Login failed. Please try again.', 'user' => null];
    }
}

/**
 * Register a new customer
 * @param string $name Full name
 * @param string $email Email address
 * @param string $phone Phone number
 * @param string $password Plain text password
 * @param string $address Address (optional)
 * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function registerCustomer($name, $email, $phone, $password, $address = '') {
    global $pdo;
    
    if ($pdo === null) {
        return ['success' => false, 'message' => 'Database connection failed.', 'user_id' => null];
    }
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        return ['success' => false, 'message' => 'Please fill in all required fields.', 'user_id' => null];
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format.', 'user_id' => null];
    }
    
    // Validate password strength (minimum 6 characters)
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.', 'user_id' => null];
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered. Please use a different email.', 'user_id' => null];
        }
        
        // Generate username from email (part before @)
        $username = explode('@', $email)[0];
        $baseUsername = $username;
        $counter = 1;
        
        // Ensure username is unique
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if (!$stmt->fetch()) {
                break;
            }
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, 'customer', 'active')");
            $stmt->execute([$username, $email, $passwordHash]);
            $userId = $pdo->lastInsertId();
            
            // Insert into customers table
            $stmt = $pdo->prepare("INSERT INTO customers (user_id, name, phone, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $name, $phone, $address]);
            
            // Commit transaction
            $pdo->commit();
            
            return ['success' => true, 'message' => 'Registration successful! You can now login.', 'user_id' => $userId];
            
        } catch (PDOException $e) {
            // Rollback on error
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed. Please try again.', 'user_id' => null];
    }
}

/**
 * Logout user
 * @return void
 */
function logoutUser() {
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Generate password reset token
 * @param string $email User email
 * @return array ['success' => bool, 'message' => string, 'token' => string|null]
 */
function generatePasswordResetToken($email) {
    global $pdo;
    
    if ($pdo === null) {
        return ['success' => false, 'message' => 'Database connection failed.', 'token' => null];
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Don't reveal if email exists for security
            return ['success' => true, 'message' => 'If the email exists, a password reset link has been sent.', 'token' => null];
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
        
        // Store token in database
        // Delete any existing tokens for this user first
        try {
            $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            // Insert new token
            $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires]);
        } catch (PDOException $e) {
            // If table doesn't exist, it will be created by database_setup.sql
            // For now, just return error
            return ['success' => false, 'message' => 'Database error. Please contact administrator.', 'token' => null];
        }
        
        return ['success' => true, 'message' => 'Password reset link has been sent to your email.', 'token' => $token];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to generate reset token. Please try again.', 'token' => null];
    }
}

/**
 * Reset password using token
 * @param string $token Reset token
 * @param string $newPassword New password
 * @return array ['success' => bool, 'message' => string]
 */
function resetPassword($token, $newPassword) {
    global $pdo;
    
    if ($pdo === null) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }
    
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }
    
    try {
        // Verify token
        $stmt = $pdo->prepare("SELECT prt.user_id, prt.expires_at FROM password_reset_tokens prt WHERE prt.token = ? AND prt.expires_at > NOW()");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch();
        
        if (!$resetData) {
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }
        
        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $resetData['user_id']]);
        
        // Delete used token
        $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
        $stmt->execute([$token]);
        
        return ['success' => true, 'message' => 'Password has been reset successfully. You can now login.'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to reset password. Please try again.'];
    }
}

/**
 * Get user role redirect URL
 * @param string $role User role
 * @return string Redirect URL
 */
function getRoleRedirectUrl($role) {
    return baseUrl($role . '/dashboard.php');
}

/**
 * Send password reset email (placeholder - uses mail() function)
 * @param string $email User email
 * @param string $token Reset token
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $token) {
    $resetUrl = baseUrl('forgot_password.php?token=' . $token);
    $subject = 'Password Reset - Tailoring Management System';
    $message = "Hello,\n\n";
    $message .= "You have requested to reset your password.\n\n";
    $message .= "Click the following link to reset your password:\n";
    $message .= $resetUrl . "\n\n";
    $message .= "This link will expire in 1 hour.\n\n";
    $message .= "If you did not request this, please ignore this email.\n\n";
    $message .= "Best regards,\nTailoring Management System";
    
    $headers = "From: noreply@tms.com\r\n";
    $headers .= "Reply-To: noreply@tms.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Note: mail() function requires proper mail server configuration
    // In production, use a proper email service like PHPMailer, SendGrid, etc.
    return @mail($email, $subject, $message, $headers);
}

?>

