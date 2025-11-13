# Authentication System Documentation

## Overview

The Tailoring Management System includes a comprehensive authentication system with login, registration, password reset, and session management.

## Files Created

### Core Authentication Files

1. **`includes/auth.php`** - Authentication functions
   - `loginUser()` - Validate credentials and login
   - `registerCustomer()` - Register new customers
   - `logoutUser()` - Destroy session and logout
   - `generatePasswordResetToken()` - Generate password reset token
   - `resetPassword()` - Reset password using token
   - `sendPasswordResetEmail()` - Send password reset email (placeholder)

2. **`login.php`** - Login page
   - Bootstrap 5 form with email/username and password
   - Role-based redirect after login
   - JavaScript validation
   - Bootstrap modals for errors/success

3. **`register.php`** - Customer registration page
   - Customer-only registration form
   - Fields: name, email, phone, address, password
   - Password strength indicator
   - JavaScript validation
   - Auto-creates user and customer records

4. **`forgot_password.php`** - Password reset page
   - Request password reset form
   - Reset password with token form
   - Email reset functionality (placeholder using mail())

5. **`assets/js/auth.js`** - Authentication JavaScript
   - Email validation
   - Password strength checker
   - Phone validation
   - Form validation helpers
   - Password visibility toggle

### Database Updates

- **`password_reset_tokens` table** - Stores password reset tokens
  - `user_id` - Foreign key to users table
  - `token` - Unique reset token
  - `expires_at` - Token expiration time (1 hour)
  - Auto-deletes expired tokens

## Features

### Security Features

1. **Password Hashing**
   - Uses `password_hash()` with BCRYPT algorithm
   - Minimum 6 characters required
   - Password strength indicator

2. **Prepared Statements**
   - All database queries use PDO prepared statements
   - Prevents SQL injection attacks

3. **Input Sanitization**
   - All user inputs are sanitized using `sanitize()` function
   - Email validation using `filter_var()`

4. **Session Management**
   - Secure session handling
   - Session variables: user_id, user_name, user_email, user_role
   - Proper session destruction on logout

5. **Password Reset Tokens**
   - Cryptographically secure random tokens
   - Token expiration (1 hour)
   - One-time use tokens

### User Roles

- **Admin** - Full system access
- **Staff** - Staff dashboard access
- **Customer** - Customer dashboard access (registration allowed)

### Registration Flow

1. User fills registration form
2. System validates inputs (email format, password strength, etc.)
3. Checks if email already exists
4. Generates unique username from email
5. Hashes password
6. Creates user record with role='customer'
7. Creates customer record
8. Redirects to login page

### Login Flow

1. User enters email/username and password
2. System validates credentials
3. Checks if user exists and is active
4. Verifies password
5. Sets session variables
6. Redirects to role-based dashboard

### Password Reset Flow

1. User requests password reset
2. System generates secure token
3. Token stored in database with expiration
4. Email sent with reset link (placeholder)
5. User clicks link with token
6. System validates token
7. User enters new password
8. Password updated and token deleted
9. User redirected to login

## Usage

### Login

```php
$result = loginUser($emailOrUsername, $password);
if ($result['success']) {
    // User logged in successfully
    // Redirect to dashboard
} else {
    // Show error message
    echo $result['message'];
}
```

### Registration

```php
$result = registerCustomer($name, $email, $phone, $password, $address);
if ($result['success']) {
    // Registration successful
    // Redirect to login
} else {
    // Show error message
    echo $result['message'];
}
```

### Logout

```php
logoutUser();
header('Location: ' . baseUrl('login.php'));
exit();
```

### Password Reset

```php
// Generate token
$result = generatePasswordResetToken($email);
if ($result['success']) {
    sendPasswordResetEmail($email, $result['token']);
}

// Reset password
$result = resetPassword($token, $newPassword);
if ($result['success']) {
    // Password reset successful
}
```

## JavaScript Validation

### Email Validation
- Validates email format using regex
- Real-time validation on input blur

### Password Strength
- Checks length, uppercase, lowercase, numbers, special characters
- Visual strength indicator (progress bar)
- Color-coded feedback (red, yellow, blue, green)

### Phone Validation
- Basic validation (minimum 10 digits)
- Strips non-digit characters for validation

### Form Validation
- HTML5 validation
- Custom validation messages
- Bootstrap validation styles

## Bootstrap Modals

### Error Modal
- Red header with error icon
- Displays error messages
- Dismissible

### Success Modal
- Green header with success icon
- Displays success messages
- Auto-dismissible

## URLs

- **Login**: `/TMS/login.php`
- **Register**: `/TMS/register.php`
- **Forgot Password**: `/TMS/forgot_password.php`
- **Logout**: `/TMS/includes/logout.php`
- **Index**: `/TMS/` (redirects to login)

## Database Schema

### Users Table
- `id` - Primary key
- `username` - Unique username
- `email` - Unique email
- `password_hash` - Bcrypt hashed password
- `role` - Enum: 'admin', 'staff', 'customer'
- `status` - Enum: 'active', 'inactive'

### Customers Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `name` - Customer name
- `phone` - Phone number
- `address` - Address
- `measurements` - JSON measurements

### Password Reset Tokens Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `token` - Unique reset token
- `expires_at` - Token expiration
- `created_at` - Token creation time

## Email Configuration

The password reset email uses PHP's `mail()` function as a placeholder. For production:

1. **Use PHPMailer** - Popular email library
2. **Use SendGrid** - Email service API
3. **Use SMTP** - Configure SMTP server
4. **Use AWS SES** - Amazon Simple Email Service

### Example with PHPMailer:

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendPasswordResetEmail($email, $token) {
    $mail = new PHPMailer(true);
    // Configure SMTP settings
    // Send email
}
```

## Security Best Practices

1. **Always use prepared statements** - Prevents SQL injection
2. **Hash passwords** - Never store plain text passwords
3. **Validate inputs** - Both client-side and server-side
4. **Use HTTPS** - Encrypt data in transit
5. **Set secure session cookies** - Use secure, httponly flags
6. **Implement rate limiting** - Prevent brute force attacks
7. **Log authentication attempts** - Monitor for suspicious activity
8. **Use CSRF tokens** - Prevent cross-site request forgery
9. **Implement account lockout** - After failed login attempts
10. **Regular security updates** - Keep PHP and dependencies updated

## Testing

### Test Login
1. Go to `/TMS/login.php`
2. Enter credentials: admin@tms.com / admin123
3. Should redirect to admin dashboard

### Test Registration
1. Go to `/TMS/register.php`
2. Fill registration form
3. Submit form
4. Should create user and customer
5. Redirect to login

### Test Password Reset
1. Go to `/TMS/forgot_password.php`
2. Enter email address
3. Submit form
4. Check email for reset link (if mail() configured)
5. Click reset link
6. Enter new password
7. Submit form
8. Should update password and redirect to login

## Troubleshooting

### Cannot Login
- Check database connection
- Verify user exists in database
- Check password hash is correct
- Verify user status is 'active'

### Registration Fails
- Check database connection
- Verify email is unique
- Check all required fields are filled
- Verify password meets requirements

### Password Reset Not Working
- Check password_reset_tokens table exists
- Verify email function is configured
- Check token expiration time
- Verify token is valid and not expired

### Session Issues
- Check session configuration in php.ini
- Verify session directory is writable
- Check session cookie settings
- Clear browser cookies and try again

## Future Enhancements

1. **Email Verification** - Verify email before activation
2. **Two-Factor Authentication** - Add 2FA support
3. **Social Login** - OAuth integration
4. **Remember Me** - Persistent login tokens
5. **Account Lockout** - Lock account after failed attempts
6. **Password History** - Prevent password reuse
7. **Audit Logging** - Log all authentication events
8. **CAPTCHA** - Prevent bot registrations

