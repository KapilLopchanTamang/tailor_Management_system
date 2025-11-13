<?php
/**
 * Admin Dashboard
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Get statistics
$stats = [];

try {
    // Total Customers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
    $stats['total_customers'] = $stmt->fetch()['count'];
    
    // Pending Orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'in-progress')");
    $stats['pending_orders'] = $stmt->fetch()['count'];
    
    // Low Inventory Items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= low_stock_threshold AND status = 'available'");
    $stats['low_inventory'] = $stmt->fetch()['count'];
    
    // Total Staff
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'active'");
    $stats['total_staff'] = $stmt->fetch()['count'];
    
    // Total Revenue (from completed orders)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'");
    $stats['revenue'] = $stmt->fetch()['total'];
    
    // Recent Orders
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, o.status, o.total_amount, o.delivery_date, o.created_at,
               c.name as customer_name
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $recent_orders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $stats = [
        'total_customers' => 0,
        'pending_orders' => 0,
        'low_inventory' => 0,
        'total_staff' => 0,
        'revenue' => 0
    ];
    $recent_orders = [];
}

$pageTitle = "Admin Dashboard";
$hide_main_nav = true; // Admin uses admin_nav instead of unified navbar
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-speedometer2"></i> Admin Dashboard
            </h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Customers</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_customers']); ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                    <a href="<?php echo baseUrl('admin/users.php?role=customer'); ?>" class="text-decoration-none small">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Pending Orders</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['pending_orders']); ?></h2>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-clock-history fs-1"></i>
                        </div>
                    </div>
                    <a href="<?php echo baseUrl('admin/orders.php?status=pending'); ?>" class="text-decoration-none small">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Low Inventory</h6>
                            <h2 class="mb-0 text-danger"><?php echo number_format($stats['low_inventory']); ?></h2>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                        </div>
                    </div>
                    <a href="<?php echo baseUrl('admin/inventory.php?filter=low_stock'); ?>" class="text-decoration-none small">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Revenue</h6>
                            <h2 class="mb-0">Rs <?php echo number_format($stats['revenue'], 2); ?></h2>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-cash-coin fs-1"></i>
                        </div>
                    </div>
                    <a href="<?php echo baseUrl('admin/reports.php'); ?>" class="text-decoration-none small">
                        View reports <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders and Quick Actions -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Orders</h5>
                    <a href="<?php echo baseUrl('admin/orders.php'); ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recent_orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Delivery Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $order['status'] == 'completed' ? 'success' : 
                                                        ($order['status'] == 'pending' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td><?php echo $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <a href="<?php echo baseUrl('admin/orders.php?view=' . $order['id']); ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No orders yet.</p>
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
                        <a href="<?php echo baseUrl('admin/users.php?action=add'); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus"></i> Add User
                        </a>
                        <a href="<?php echo baseUrl('admin/inventory.php?action=add'); ?>" class="btn btn-outline-success">
                            <i class="bi bi-box-seam"></i> Add Inventory Item
                        </a>
                        <a href="<?php echo baseUrl('admin/orders.php'); ?>" class="btn btn-outline-info">
                            <i class="bi bi-bag"></i> View Orders
                        </a>
                        <a href="<?php echo baseUrl('admin/staff.php'); ?>" class="btn btn-outline-warning">
                            <i class="bi bi-person-badge"></i> Manage Staff
                        </a>
                        <a href="<?php echo baseUrl('admin/reports.php'); ?>" class="btn btn-outline-dark">
                            <i class="bi bi-file-earmark-text"></i> Generate Reports
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if ($stats['low_inventory'] > 0): ?>
                <div class="card border-0 shadow-sm mt-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Low Stock Alert</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">
                            <strong><?php echo $stats['low_inventory']; ?></strong> item(s) are below stock threshold.
                        </p>
                        <a href="<?php echo baseUrl('admin/inventory.php?filter=low_stock'); ?>" 
                           class="btn btn-sm btn-danger mt-2">
                            View Items
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
