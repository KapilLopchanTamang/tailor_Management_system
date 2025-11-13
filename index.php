<?php
/**
 * Index Page - Redirect to Login
 * Tailoring Management System
 */
require_once __DIR__ . '/includes/functions.php';

// Redirect to login page
header('Location: ' . baseUrl('login.php'));
exit();
?>
