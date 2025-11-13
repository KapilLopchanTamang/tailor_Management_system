<?php
/**
 * Order Status API - AJAX Endpoint for Polling
 * Tailoring Management System
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    if ($pdo === null) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    // Get customer ID
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
    $customerId = $customer['id'] ?? null;
    
    if (!$customerId) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit();
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id) as total_paid
        FROM orders o
        WHERE o.id = ? AND o.customer_id = ?
    ");
    $stmt->execute([$orderId, $customerId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching order: ' . $e->getMessage()
    ]);
}
?>

