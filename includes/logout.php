<?php
/**
 * Logout Handler
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

logoutUser();

header('Location: ' . baseUrl('login.php'));
exit();
?>

