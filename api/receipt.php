<?php
/**
 * Receipt Generation API
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

$paymentId = (int)($_GET['id'] ?? 0);
$download = isset($_GET['download']) && $_GET['download'] == '1';

if ($paymentId <= 0) {
    die('Invalid payment ID');
}

// Check authorization
if (hasRole('customer')) {
    // Customer can only view their own receipts
    try {
        $stmt = $pdo->prepare("
            SELECT p.id 
            FROM payments p
            LEFT JOIN orders o ON p.order_id = o.id
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE p.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$paymentId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            die('Unauthorized');
        }
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
}

// Generate receipt HTML
$receiptHTML = generateReceiptHTML($pdo, $paymentId);

if (!$receiptHTML) {
    die('Error generating receipt');
}

// Output receipt
if ($download) {
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="receipt_' . $paymentId . '_' . date('Ymd') . '.html"');
} else {
    header('Content-Type: text/html');
}

echo $receiptHTML;
?>

