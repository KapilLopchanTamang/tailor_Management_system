<?php
/**
 * Staff Orders - Process Assigned Orders
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

requireRole('staff');

$staffId = $_SESSION['user_id'];
$message = '';
$error = '';
$orderId = (int)($_GET['id'] ?? 0);

// Handle delivery date update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_delivery_date'])) {
    // Validate CSRF token
    validateCSRF();
    
    $orderId = (int)$_POST['order_id'];
    $deliveryDate = sanitize($_POST['delivery_date'] ?? '');
    $notificationMessage = sanitize($_POST['notification_message'] ?? '');
    
    try {
        // Verify staff has access to this order
        $stmt = $pdo->prepare("
            SELECT o.id, o.customer_id, c.user_id as customer_user_id
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN staff_tasks st ON o.id = st.order_id
            WHERE o.id = ? AND st.staff_id = ?
        ");
        $stmt->execute([$orderId, $staffId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $error = 'Order not found or not assigned to you.';
        } else {
            $pdo->beginTransaction();
            
            // Update delivery date
            $stmt = $pdo->prepare("UPDATE orders SET delivery_date = ? WHERE id = ?");
            $stmt->execute([$deliveryDate ?: null, $orderId]);
            
            // Send notification to customer
            if ($deliveryDate && $order['customer_user_id']) {
                notifyDeliveryScheduled($pdo, $orderId, $deliveryDate, false);
            }
            
            $pdo->commit();
            $message = 'Delivery date updated successfully.';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Error updating delivery date: ' . $e->getMessage();
    }
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    // Validate CSRF token
    validateCSRF();
    
    $orderId = (int)$_POST['order_id'];
    $orderStatus = sanitize($_POST['order_status'] ?? '');
    $notificationMessage = sanitize($_POST['notification_message'] ?? '');
    
    try {
        // Verify staff has access to this order
        $stmt = $pdo->prepare("
            SELECT o.id, o.customer_id, c.user_id as customer_user_id, o.status
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN staff_tasks st ON o.id = st.order_id
            WHERE o.id = ? AND st.staff_id = ?
        ");
        $stmt->execute([$orderId, $staffId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $error = 'Order not found or not assigned to you.';
        } else {
            $pdo->beginTransaction();
            
            $now = date('Y-m-d H:i:s');
            
            // Update order status
            if ($orderStatus === 'completed') {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, completed_at = ? WHERE id = ?");
                $stmt->execute([$orderStatus, $now, $orderId]);
            } else {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$orderStatus, $orderId]);
            }
            
            // Send notification to customer
            if ($order['customer_user_id']) {
                notifyOrderStatusUpdate($pdo, $orderId, $orderStatus, false);
            }
            
            $pdo->commit();
            $message = 'Order status updated successfully.';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Error updating order status: ' . $e->getMessage();
    }
}

// Get assigned orders
$assignedOrders = [];
$order = null;
$orderItems = [];
$customerMeasurements = [];

if ($orderId > 0) {
    try {
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, c.name as customer_name, c.user_id as customer_user_id,
                   c.measurements, c.phone, c.address,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                   (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id) as total_paid
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN staff_tasks st ON o.id = st.order_id
            WHERE o.id = ? AND st.staff_id = ?
        ");
        $stmt->execute([$orderId, $staffId]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Get order items
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll();
            
            // Parse measurements
            if ($order['measurements']) {
                $customerMeasurements = json_decode($order['measurements'], true) ?? [];
            }
        }
    } catch (PDOException $e) {
        $error = 'Error fetching order data: ' . $e->getMessage();
    }
} else {
    // Get all assigned orders
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT o.*, c.name as customer_name,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                   (SELECT COUNT(*) FROM staff_tasks WHERE order_id = o.id AND staff_id = ?) as task_count
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN staff_tasks st ON o.id = st.order_id
            WHERE st.staff_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$staffId, $staffId]);
        $assignedOrders = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Error fetching orders: ' . $e->getMessage();
    }
}

$pageTitle = $orderId > 0 ? "Process Order" : "My Orders";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-bag"></i> <?php echo $orderId > 0 ? 'Process Order' : 'My Orders'; ?>
                    </h1>
                </div>
                <div>
                    <a href="<?php echo baseUrl('staff/dashboard.php'); ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($orderId > 0 && $order): ?>
        <!-- Order Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Order Number:</strong><br>
                                <?php echo htmlspecialchars($order['order_number']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-<?php 
                                    echo $order['status'] == 'completed' ? 'success' : 
                                        ($order['status'] == 'pending' ? 'warning' : 'info'); 
                                ?> fs-6" id="orderStatusBadge">
                                    <?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Customer:</strong><br>
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Phone:</strong><br>
                                <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Description:</strong><br>
                            <?php echo htmlspecialchars($order['description'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Total Amount:</strong><br>
                                Rs <?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Total Paid:</strong><br>
                                Rs <?php echo number_format($order['total_paid'], 2); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Remaining:</strong><br>
                                Rs <?php echo number_format($order['remaining_amount'], 2); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>Rs <?php echo number_format($item['price'], 2); ?></td>
                                            <td>Rs <?php echo number_format($item['subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>Rs <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Customer Measurements -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-rulers"></i> Customer Measurements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($customerMeasurements)): ?>
                            <ul class="list-unstyled">
                                <?php foreach ($customerMeasurements as $key => $value): ?>
                                    <?php if ($key !== 'notes' && !empty($value)): ?>
                                        <li class="mb-2">
                                            <strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong>
                                            <?php echo htmlspecialchars($value); ?> inches
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (isset($customerMeasurements['notes']) && !empty($customerMeasurements['notes'])): ?>
                                    <li class="mt-3">
                                        <strong>Notes:</strong><br>
                                        <small><?php echo htmlspecialchars($customerMeasurements['notes']); ?></small>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No measurements available.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Update Delivery Date -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Update Delivery Date</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="updateDeliveryDateForm">
                            <input type="hidden" name="update_delivery_date" value="1">
                            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                            <?php echo csrfField(); ?>
                            
                            <div class="mb-3">
                                <label for="delivery_date" class="form-label">Delivery Date</label>
                                <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                       value="<?php echo $order['delivery_date'] ? date('Y-m-d', strtotime($order['delivery_date'])) : ''; ?>"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="notification_message" class="form-label">Notification Message (Optional)</label>
                                <textarea class="form-control" id="notification_message" name="notification_message" rows="2" 
                                          placeholder="Message to send to customer..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-calendar-check"></i> Update Delivery Date
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Update Order Status -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Update Order Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="updateOrderStatusForm">
                            <input type="hidden" name="update_order_status" value="1">
                            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                            <?php echo csrfField(); ?>
                            
                            <div class="mb-3">
                                <label for="order_status" class="form-label">Status</label>
                                <select class="form-select" id="order_status" name="order_status" required>
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in-progress" <?php echo $order['status'] == 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status_notification_message" class="form-label">Notification Message (Optional)</label>
                                <textarea class="form-control" id="status_notification_message" name="notification_message" rows="2" 
                                          placeholder="Message to send to customer..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Assigned Orders List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($assignedOrders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Delivery Date</th>
                                    <th>Tasks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignedOrders as $ord): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ord['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $ord['status'] == 'completed' ? 'success' : 
                                                    ($ord['status'] == 'pending' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('-', ' ', $ord['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $ord['item_count']; ?></td>
                                        <td>Rs <?php echo number_format($ord['total_amount'], 2); ?></td>
                                        <td><?php echo $ord['delivery_date'] ? date('M d, Y', strtotime($ord['delivery_date'])) : 'Not set'; ?></td>
                                        <td><?php echo $ord['task_count']; ?></td>
                                        <td>
                                            <a href="<?php echo baseUrl('staff/orders.php?id=' . $ord['id']); ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Process
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No orders assigned yet.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// AJAX form submissions for better UX
document.getElementById('updateDeliveryDateForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo baseUrl('staff/orders.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Reload page to show updated data
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

document.getElementById('updateOrderStatusForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo baseUrl('staff/orders.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Reload page to show updated data
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

