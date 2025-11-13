<?php
/**
 * Common Functions
 * Tailoring Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get base URL
function baseUrl($path = '') {
    $base = defined('BASE_URL') ? BASE_URL : '/TMS/';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

/**
 * Redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl());
        exit();
    }
}

/**
 * Redirect to specific role dashboard
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . baseUrl());
        exit();
    }
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Generate unique order number
 */
function generateOrderNumber($pdo) {
    $date = date('Ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $result = $stmt->fetch();
    $sequence = ($result['count'] ?? 0) + 1;
    return 'ORD-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    // Handle CLI mode for testing
    if (php_sapi_name() === 'cli') {
        if (!isset($GLOBALS['_TEST_SESSION'])) {
            $GLOBALS['_TEST_SESSION'] = [];
        }
        if (!isset($GLOBALS['_TEST_SESSION']['csrf_token'])) {
            $GLOBALS['_TEST_SESSION']['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $GLOBALS['_TEST_SESSION']['csrf_token'];
    }
    
    // Normal web mode
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    // Handle CLI mode for testing
    if (php_sapi_name() === 'cli') {
        if (!isset($GLOBALS['_TEST_SESSION'])) {
            return false;
        }
        return isset($GLOBALS['_TEST_SESSION']['csrf_token']) && 
               hash_equals($GLOBALS['_TEST_SESSION']['csrf_token'], $token);
    }
    
    // Normal web mode
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token field for forms
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST request
 */
function validateCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
}

/**
 * Generate unique payment number
 */
function generatePaymentNumber($pdo) {
    $date = date('Ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $result = $stmt->fetch();
    $sequence = ($result['count'] ?? 0) + 1;
    return 'PAY-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}
?>

