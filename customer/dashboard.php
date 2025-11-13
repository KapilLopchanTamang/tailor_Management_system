<?php
/**
 * Customer Dashboard
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

requireRole('customer');

// Get customer ID
$customerId = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
    $customerId = $customer['id'] ?? null;
} catch (PDOException $e) {
    $error = 'Error fetching customer data.';
}

// Get statistics
$stats = [
    'total_orders' => 0,
    'in_progress' => 0,
    'completed' => 0
];

$recentOrders = [];

if ($customerId) {
    try {
        // Total Orders
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $stats['total_orders'] = $stmt->fetch()['count'];
        
        // In Progress Orders
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ? AND status IN ('pending', 'in-progress')");
        $stmt->execute([$customerId]);
        $stats['in_progress'] = $stmt->fetch()['count'];
        
        // Completed Orders
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ? AND status = 'completed'");
        $stmt->execute([$customerId]);
        $stats['completed'] = $stmt->fetch()['count'];
        
        // Recent Orders (last 10)
        $stmt = $pdo->prepare("
            SELECT o.id, o.order_number, o.status, o.total_amount, o.delivery_date, o.created_at,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM orders o
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$customerId]);
        $recentOrders = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        $error = 'Error fetching order data.';
    }
}

$pageTitle = "Customer Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-speedometer2"></i> My Dashboard
            </h1>
            <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">My Orders</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-bag fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">In Progress</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['in_progress']); ?></h2>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-hourglass-split fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Completed</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['completed']); ?></h2>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Order History</h5>
                    <a href="<?php echo baseUrl('customer/orders.php'); ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentOrders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Status</th>
                                        <th>Items</th>
                                        <th>Total Amount</th>
                                        <th>Delivery Date</th>
                                        <th>Order Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $order['status'] == 'completed' ? 'success' : 
                                                        ($order['status'] == 'pending' ? 'warning' : 
                                                        ($order['status'] == 'cancelled' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $order['item_count']; ?></td>
                                            <td>Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td><?php echo $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : 'Not set'; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo baseUrl('customer/track.php?id=' . $order['id']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Track Order">
                                                    <i class="bi bi-geo-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">
                            No orders yet. 
                            <a href="<?php echo baseUrl('customer/orders.php'); ?>" class="text-decoration-none">Place your first order</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo baseUrl('customer/orders.php?action=new'); ?>" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> New Order
                        </a>
                        <a href="<?php echo baseUrl('customer/profile.php'); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-rulers"></i> My Measurements
                        </a>
                        <a href="<?php echo baseUrl('customer/feedback.php'); ?>" class="btn btn-outline-info">
                            <i class="bi bi-chat-dots"></i> Feedback
                        </a>
                        <a href="<?php echo baseUrl('customer/orders.php'); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-clock-history"></i> Order History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notifications Modal -->
<div class="modal fade" id="notificationsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-bell"></i> Notifications
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <button class="btn btn-sm btn-primary" onclick="markAllAsRead()">
                        <i class="bi bi-check-all"></i> Mark All as Read
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadNotifications()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div id="notificationsList">
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split"></i> Loading notifications...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
