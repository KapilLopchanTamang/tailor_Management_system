<?php
/**
 * Record Payment API - AJAX Endpoint
 * Tailoring Management System
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/billing.php';
require_once __DIR__ . '/../includes/notifications.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only admin and staff can record payments (customers can view but not record)
if (!hasRole('admin') && !hasRole('staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Only admin and staff can record payments']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$orderId = (int)($_POST['order_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');
$transactionId = sanitize($_POST['transaction_id'] ?? '');
$notes = sanitize($_POST['notes'] ?? '');

if ($orderId <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or amount']);
    exit();
}

// Validate payment method
$validMethods = ['cash', 'card', 'bank_transfer', 'mobile_payment', 'cheque'];
if (!in_array($paymentMethod, $validMethods)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit();
}

try {
    if ($pdo === null) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    // Check if order exists and get remaining amount
    $stmt = $pdo->prepare("SELECT id, total_amount, remaining_amount FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Validate amount doesn't exceed remaining amount
    if ($amount > $order['remaining_amount']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Payment amount cannot exceed remaining amount. Remaining: Rs ' . number_format($order['remaining_amount'], 2)
        ]);
        exit();
    }
    
    // Record payment
    $result = recordPayment($pdo, $orderId, $amount, $paymentMethod, $transactionId ?: null, $notes ?: null);
    
    if ($result['success']) {
        // Get updated order info
        $stmt = $pdo->prepare("SELECT remaining_amount FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $updatedOrder = $stmt->fetch();
        
        $result['remaining_amount'] = $updatedOrder['remaining_amount'];
        $result['is_fully_paid'] = $updatedOrder['remaining_amount'] <= 0;
        $result['payment_id'] = $result['payment_id']; // Already included from recordPayment function
        
        // Notify customer about payment
        if ($result['success']) {
            notifyPaymentReceived($pdo, $result['payment_id'], false);
        }
    }
    
    echo json_encode($result);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error recording payment: ' . $e->getMessage()
    ]);
}
?>

