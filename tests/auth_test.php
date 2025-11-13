<?php
/**
 * Unit Tests for Authentication Functions
 * Tailoring Management System
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Suppress output for CLI mode
if (php_sapi_name() === 'cli') {
    ob_start();
}

// Include necessary files
require_once __DIR__ . '/../config/db_config.php';

// Start session for tests (only if not in CLI or if CLI supports sessions)
if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    // For CLI, initialize session array manually
    $_SESSION = [];
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Clear output buffer in CLI
if (php_sapi_name() === 'cli') {
    ob_end_clean();
}

/**
 * Test helper function
 */
function assertTrue($condition, $message = '') {
    if ($condition) {
        echo "✓ PASS: $message\n";
        return true;
    } else {
        echo "✗ FAIL: $message\n";
        return false;
    }
}

function assertFalse($condition, $message = '') {
    return assertTrue(!$condition, $message);
}

function assertEquals($expected, $actual, $message = '') {
    return assertTrue($expected === $actual, $message . " (Expected: $expected, Actual: $actual)");
}

/**
 * Test CSRF Token Generation
 */
function testCSRFTokenGeneration() {
    echo "\n=== Testing CSRF Token Generation ===\n";
    
    // Clear test session
    if (isset($GLOBALS['_TEST_SESSION'])) {
        unset($GLOBALS['_TEST_SESSION']);
    }
    
    // Generate token
    $token1 = generateCSRFToken();
    assertTrue(!empty($token1), 'CSRF token should not be empty');
    assertTrue(strlen($token1) === 64, 'CSRF token should be 64 characters (32 bytes hex)');
    
    // Generate again - should return same token
    $token2 = generateCSRFToken();
    assertEquals($token1, $token2, 'CSRF token should be same on subsequent calls');
    
    // Verify token
    assertTrue(verifyCSRFToken($token1), 'CSRF token verification should pass');
    assertFalse(verifyCSRFToken('invalid_token'), 'Invalid CSRF token should fail verification');
}

/**
 * Test Sanitize Function
 */
function testSanitize() {
    echo "\n=== Testing Sanitize Function ===\n";
    
    $input = '<script>alert("xss")</script>Hello World';
    $sanitized = sanitize($input);
    assertFalse(strpos($sanitized, '<script>') !== false, 'Script tags should be removed');
    assertTrue(strpos($sanitized, 'Hello World') !== false, 'Valid content should be preserved');
    
    $input2 = '   Test   ';
    $sanitized2 = sanitize($input2);
    assertEquals('Test', $sanitized2, 'Whitespace should be trimmed');
}

/**
 * Test Login Function (Mock test - requires database)
 */
function testLoginFunction() {
    echo "\n=== Testing Login Function ===\n";
    
    if ($pdo === null) {
        echo "⚠ SKIP: Database connection not available\n";
        return;
    }
    
    // Test empty credentials
    $result = loginUser('', '');
    assertFalse($result['success'], 'Login should fail with empty credentials');
    
    // Test invalid credentials
    $result = loginUser('nonexistent@test.com', 'wrongpassword');
    assertFalse($result['success'], 'Login should fail with invalid credentials');
}

/**
 * Test Register Function (Mock test - requires database)
 */
function testRegisterFunction() {
    echo "\n=== Testing Register Function ===\n";
    
    if ($pdo === null) {
        echo "⚠ SKIP: Database connection not available\n";
        return;
    }
    
    // Test empty fields
    $result = registerCustomer('', '', '', '');
    assertFalse($result['success'], 'Registration should fail with empty fields');
    
    // Test invalid email
    $result = registerCustomer('Test User', 'invalid-email', '1234567890', 'password123');
    assertFalse($result['success'], 'Registration should fail with invalid email');
    
    // Test short password
    $result = registerCustomer('Test User', 'test@example.com', '1234567890', '12345');
    assertFalse($result['success'], 'Registration should fail with password less than 6 characters');
}

/**
 * Test Password Hashing
 */
function testPasswordHashing() {
    echo "\n=== Testing Password Hashing ===\n";
    
    $password = 'testpassword123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    assertTrue(!empty($hash), 'Password hash should not be empty');
    assertTrue(password_verify($password, $hash), 'Password verification should pass');
    assertFalse(password_verify('wrongpassword', $hash), 'Wrong password should fail verification');
}

/**
 * Test Base URL Function
 */
function testBaseUrl() {
    echo "\n=== Testing Base URL Function ===\n";
    
    $url1 = baseUrl();
    assertTrue(!empty($url1), 'Base URL should not be empty');
    
    $url2 = baseUrl('admin/dashboard.php');
    assertTrue(strpos($url2, 'admin/dashboard.php') !== false, 'Base URL should include path');
}

/**
 * Run all tests
 */
function runAllTests() {
    echo "========================================\n";
    echo "Tailoring Management System - Unit Tests\n";
    echo "========================================\n";
    
    testCSRFTokenGeneration();
    testSanitize();
    testPasswordHashing();
    testBaseUrl();
    testLoginFunction();
    testRegisterFunction();
    
    echo "\n========================================\n";
    echo "Tests completed!\n";
    echo "========================================\n";
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    runAllTests();
} else {
    // If accessed via web, show results
    header('Content-Type: text/plain');
    runAllTests();
}

