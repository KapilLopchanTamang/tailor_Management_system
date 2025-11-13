<?php
/**
 * Admin Index - Redirect to Dashboard
 * Tailoring Management System
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

header('Location: ' . baseUrl('admin/dashboard.php'));
exit();
?>

