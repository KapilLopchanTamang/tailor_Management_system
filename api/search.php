<?php
/**
 * Global Search API Endpoint
 * Tailoring Management System
 * Returns JSON results for orders, customers, and inventory
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all'; // all, orders, customers, inventory

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit();
}

$results = [
    'orders' => [],
    'customers' => [],
    'inventory' => []
];

try {
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    
    // Search Orders
    if ($type === 'all' || $type === 'orders') {
        if ($userRole === 'admin') {
            // Admin can see all orders
            $stmt = $pdo->prepare("
                SELECT o.id, o.order_number, o.description, o.status, o.total_amount, o.created_at,
                       c.name as customer_name, c.phone as customer_phone
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.order_number LIKE ? 
                   OR o.description LIKE ?
                   OR c.name LIKE ?
                ORDER BY o.created_at DESC
                LIMIT 10
            ");
            $searchTerm = '%' . $query . '%';
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        } elseif ($userRole === 'staff') {
            // Staff can see assigned orders
            $stmt = $pdo->prepare("
                SELECT DISTINCT o.id, o.order_number, o.description, o.status, o.total_amount, o.created_at,
                       c.name as customer_name, c.phone as customer_phone
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN staff_tasks st ON o.id = st.order_id
                WHERE st.staff_id = ?
                  AND (o.order_number LIKE ? 
                   OR o.description LIKE ?
                   OR c.name LIKE ?)
                ORDER BY o.created_at DESC
                LIMIT 10
            ");
            $searchTerm = '%' . $query . '%';
            $stmt->execute([$userId, $searchTerm, $searchTerm, $searchTerm]);
        } elseif ($userRole === 'customer') {
            // Customer can see only their orders
            $stmt = $pdo->prepare("
                SELECT o.id, o.order_number, o.description, o.status, o.total_amount, o.created_at,
                       c.name as customer_name, c.phone as customer_phone
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.customer_id = (
                    SELECT id FROM customers WHERE user_id = ?
                )
                  AND (o.order_number LIKE ? 
                   OR o.description LIKE ?)
                ORDER BY o.created_at DESC
                LIMIT 10
            ");
            $searchTerm = '%' . $query . '%';
            $stmt->execute([$userId, $searchTerm, $searchTerm]);
        }
        
        $orders = $stmt->fetchAll();
        foreach ($orders as $order) {
            $results['orders'][] = [
                'id' => $order['id'],
                'type' => 'order',
                'title' => 'Order #' . $order['order_number'],
                'description' => $order['description'] ?: 'No description',
                'customer' => $order['customer_name'] ?? 'N/A',
                'status' => $order['status'],
                'amount' => $order['total_amount'],
                'date' => date('M d, Y', strtotime($order['created_at'])),
                'url' => baseUrl($userRole . '/orders.php?view=' . $order['id'])
            ];
        }
    }
    
    // Search Customers (Admin only)
    if (($type === 'all' || $type === 'customers') && $userRole === 'admin') {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.phone, c.address, u.email,
                   (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as order_count
            FROM customers c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.name LIKE ?
               OR c.phone LIKE ?
               OR u.email LIKE ?
            ORDER BY c.name
            LIMIT 10
        ");
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        
        $customers = $stmt->fetchAll();
        foreach ($customers as $customer) {
            $results['customers'][] = [
                'id' => $customer['id'],
                'type' => 'customer',
                'title' => $customer['name'],
                'description' => $customer['email'] . ' | ' . $customer['phone'],
                'orders' => $customer['order_count'],
                'url' => baseUrl('admin/users.php?view=customer&id=' . $customer['id'])
            ];
        }
    }
    
    // Search Inventory (Admin and Staff)
    if (($type === 'all' || $type === 'inventory') && ($userRole === 'admin' || $userRole === 'staff')) {
        $stmt = $pdo->prepare("
            SELECT id, item_name, type, quantity, price, unit, status
            FROM inventory
            WHERE item_name LIKE ?
               OR type LIKE ?
            ORDER BY item_name
            LIMIT 10
        ");
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm]);
        
        $inventory = $stmt->fetchAll();
        foreach ($inventory as $item) {
            $results['inventory'][] = [
                'id' => $item['id'],
                'type' => 'inventory',
                'title' => $item['item_name'],
                'description' => ucfirst($item['type']) . ' | Qty: ' . $item['quantity'] . ' ' . $item['unit'] . ' | Rs ' . number_format($item['price'], 2),
                'status' => $item['status'],
                'url' => baseUrl($userRole . '/inventory.php?view=' . $item['id'])
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $results,
        'total' => count($results['orders']) + count($results['customers']) + count($results['inventory'])
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

