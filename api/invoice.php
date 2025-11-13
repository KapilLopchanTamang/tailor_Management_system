<?php
/**
 * Invoice Generation API
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/billing.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . baseUrl('login.php'));
    exit();
}

$orderId = (int)($_GET['id'] ?? 0);
$download = isset($_GET['download']) && $_GET['download'] == '1';

if ($orderId <= 0) {
    die('Invalid order ID');
}

// Check authorization
if (hasRole('customer')) {
    // Customer can only view their own orders
    try {
        $stmt = $pdo->prepare("
            SELECT o.id 
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$orderId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            die('Unauthorized');
        }
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
}

// Generate invoice HTML
$invoiceHTML = generateInvoiceHTML($pdo, $orderId);

if (!$invoiceHTML) {
    die('Error generating invoice');
}

// Output invoice
if ($download) {
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="invoice_' . $orderId . '_' . date('Ymd') . '.html"');
} else {
    header('Content-Type: text/html');
}

echo $invoiceHTML;
?>

