<?php
/**
 * Error Handler
 * Tailoring Management System
 */

// Ensure logs directory exists
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

// Set error reporting based on environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', $logsDir . '/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', $logsDir . '/php_errors.log');
}

/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorMessage = "Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($errorMessage, 3, __DIR__ . '/../logs/php_errors.log');
    
    // Don't execute PHP internal error handler
    return true;
}

/**
 * Custom exception handler
 */
function customExceptionHandler($exception) {
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        // Show user-friendly error page
        http_response_code(500);
        include __DIR__ . '/../500.php';
    } else {
        // Show detailed error in development
        echo "<h1>Uncaught Exception</h1>";
        echo "<p>Message: " . $exception->getMessage() . "</p>";
        echo "<p>File: " . $exception->getFile() . "</p>";
        echo "<p>Line: " . $exception->getLine() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    }
}

// Set error and exception handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

/**
 * Safe database query execution with error handling
 */
function safeQuery($pdo, $query, $params = []) {
    try {
        if (empty($params)) {
            $stmt = $pdo->query($query);
        } else {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
        }
        return ['success' => true, 'stmt' => $stmt, 'error' => null];
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return ['success' => false, 'stmt' => null, 'error' => $e->getMessage()];
    }
}

/**
 * Log security events
 */
function logSecurityEvent($event, $details = '') {
    $logMessage = date('Y-m-d H:i:s') . " - Security Event: $event";
    if (!empty($details)) {
        $logMessage .= " - Details: $details";
    }
    $logMessage .= "\n";
    error_log($logMessage, 3, __DIR__ . '/../logs/security.log');
}

?>

