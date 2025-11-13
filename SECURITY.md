# Security Implementation Guide

## Tailoring Management System - Security Features

This document outlines the security measures implemented in the TMS application.

## 🔒 Security Features

### 1. CSRF Protection

**Implementation:**
- All forms include CSRF tokens using `csrfField()` function
- Tokens are validated on form submission using `validateCSRF()`
- Tokens are stored in session and regenerated on each page load

**Usage:**
```php
// In forms
<?php echo csrfField(); ?>

// In form handlers
validateCSRF();
```

**Files with CSRF Protection:**
- `login.php`
- `register.php`
- `forgot_password.php`
- All admin forms (users, orders, inventory, staff, feedback)
- All customer forms (orders, feedback, profile)
- All staff forms (tasks, orders)

### 2. SQL Injection Prevention

**Implementation:**
- All database queries use PDO prepared statements
- No string concatenation in SQL queries
- All user input is passed as parameters to prepared statements

**Safe Query Example:**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = ?");
$stmt->execute([$email, $status]);
```

**Unsafe Query (Never Use):**
```php
// NEVER DO THIS
$query = "SELECT * FROM users WHERE email = '$email'";
$pdo->query($query);
```

**Review Status:**
- ✅ All queries in `includes/auth.php` use prepared statements
- ✅ All queries in admin pages use prepared statements
- ✅ All queries in customer pages use prepared statements
- ✅ All queries in staff pages use prepared statements
- ✅ All queries in API endpoints use prepared statements
- ⚠️ Some `$pdo->query()` calls exist but are safe (no user input)

### 3. XSS Prevention

**Implementation:**
- All user input is sanitized using `sanitize()` function
- All output is escaped using `htmlspecialchars()`
- JSON output is properly encoded

**Safe Output Example:**
```php
echo htmlspecialchars($userInput);
```

**Unsafe Output (Never Use):**
```php
// NEVER DO THIS
echo $userInput; // Vulnerable to XSS
```

### 4. Password Security

**Implementation:**
- Passwords are hashed using bcrypt (`password_hash()`)
- Password verification uses `password_verify()`
- Minimum password length: 6 characters
- Password reset tokens with expiration (1 hour)

**Password Hashing:**
```php
$hash = password_hash($password, PASSWORD_BCRYPT);
$isValid = password_verify($password, $hash);
```

### 5. Session Security

**Implementation:**
- Secure session handling
- Session timeout: 30 minutes of inactivity
- Auto-logout with 5-minute warning
- Session regeneration on login

**Session Configuration:**
- `session_start()` called in `includes/functions.php`
- Session cookies are HttpOnly
- Session data is validated on each request

### 6. Input Validation

**Server-Side Validation:**
- All inputs are validated in PHP
- Email format validation
- Phone number validation
- Required field validation
- Data type validation

**Client-Side Validation:**
- jQuery Validation Plugin
- HTML5 form validation
- Real-time validation feedback

### 7. Role-Based Access Control

**Implementation:**
- `requireRole()` function checks user role
- Access denied if user doesn't have required role
- Redirects to appropriate dashboard based on role

**Usage:**
```php
requireRole('admin'); // Only admin can access
requireRole('staff'); // Only staff can access
requireRole('customer'); // Only customer can access
```

### 8. Error Handling

**Implementation:**
- Custom error handler in `includes/error_handler.php`
- Try-catch blocks in all database operations
- Custom error pages (404.php, 500.php)
- Error logging to files

**Error Logging:**
- PHP errors: `logs/php_errors.log`
- Security events: `logs/security.log`
- Database errors: Logged with context

### 9. Security Headers

**Implementation:**
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin

**Configuration:**
- Set in `.htaccess` file
- Apache mod_headers required

### 10. File Upload Security

**Implementation:**
- File type validation
- File size limits
- Secure file storage in `uploads/` directory
- File name sanitization

### 11. Idle Detection

**Implementation:**
- JavaScript idle detection in `assets/js/security.js`
- 30-minute inactivity timeout
- 5-minute warning before logout
- Auto-logout on timeout

**Configuration:**
- Idle timeout: 30 minutes
- Warning time: 5 minutes before logout
- Reset on user activity (mouse, keyboard, touch)

## 🔍 Security Audit Checklist

### SQL Injection
- [x] All queries use prepared statements
- [x] No string concatenation in SQL
- [x] All user input is parameterized
- [x] Database queries reviewed

### XSS Prevention
- [x] All output is escaped
- [x] User input is sanitized
- [x] JSON output is encoded
- [x] No inline JavaScript with user data

### CSRF Protection
- [x] All forms have CSRF tokens
- [x] Tokens are validated on submission
- [x] Tokens are stored in session
- [x] Tokens are regenerated properly

### Authentication
- [x] Passwords are hashed
- [x] Session management is secure
- [x] Role-based access control
- [x] Password reset is secure

### Authorization
- [x] Role checks on all protected pages
- [x] User can only access their own data
- [x] Admin can access all data
- [x] Staff can access assigned tasks only

### Input Validation
- [x] Server-side validation
- [x] Client-side validation
- [x] Email format validation
- [x] Phone number validation
- [x] Required field validation

### Error Handling
- [x] Custom error pages
- [x] Error logging
- [x] Try-catch blocks
- [x] User-friendly error messages

### Security Headers
- [x] X-Content-Type-Options
- [x] X-Frame-Options
- [x] X-XSS-Protection
- [x] Referrer-Policy

## 🛡️ Security Best Practices

### For Developers

1. **Always use prepared statements:**
   ```php
   // Good
   $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
   $stmt->execute([$id]);
   
   // Bad
   $query = "SELECT * FROM users WHERE id = $id";
   $pdo->query($query);
   ```

2. **Always escape output:**
   ```php
   // Good
   echo htmlspecialchars($userInput);
   
   // Bad
   echo $userInput;
   ```

3. **Always validate input:**
   ```php
   // Good
   $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
   if (!$email) {
       die('Invalid email');
   }
   
   // Bad
   $email = $_POST['email'];
   ```

4. **Always use CSRF tokens:**
   ```php
   // Good
   <?php echo csrfField(); ?>
   validateCSRF();
   
   // Bad
   // No CSRF protection
   ```

5. **Always hash passwords:**
   ```php
   // Good
   $hash = password_hash($password, PASSWORD_BCRYPT);
   
   // Bad
   $hash = md5($password); // Never use MD5
   ```

## 🧪 Security Testing

### Test CSRF Protection
1. Try submitting forms without CSRF token
2. Try submitting forms with invalid CSRF token
3. Verify tokens are validated correctly

### Test SQL Injection
1. Try SQL injection in search fields
2. Try SQL injection in form inputs
3. Verify all queries use prepared statements

### Test XSS Prevention
1. Try injecting script tags in input fields
2. Try injecting JavaScript in URLs
3. Verify all output is escaped

### Test Authentication
1. Try accessing protected pages without login
2. Try accessing other users' data
3. Verify role-based access control

### Test Session Security
1. Test session timeout
2. Test auto-logout
3. Test session regeneration

## 📝 Security Logging

Security events are logged to `logs/security.log`:
- Failed login attempts
- CSRF token validation failures
- Unauthorized access attempts
- SQL injection attempts
- XSS attempts

## 🔐 Default Passwords

**⚠️ IMPORTANT:** Change these passwords after first login!

- Admin: `admin123`
- Staff: `staff123`
- Customer: `customer123`

## 🚨 Security Incident Response

If a security incident is detected:

1. **Immediate Actions:**
   - Review security logs
   - Identify the vulnerability
   - Patch the vulnerability
   - Notify affected users

2. **Investigation:**
   - Review access logs
   - Review error logs
   - Review security logs
   - Identify affected data

3. **Remediation:**
   - Fix the vulnerability
   - Update security measures
   - Reset affected passwords
   - Notify users

## 📚 Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [Password Hashing](https://www.php.net/manual/en/password.hashing.php)

## ✅ Security Compliance

This application follows:
- OWASP security guidelines
- PHP security best practices
- PDO security guidelines
- Session security best practices
- Password security best practices

---

**Last Updated:** 2024  
**Security Version:** 2.0

