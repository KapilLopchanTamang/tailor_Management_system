<?php
/**
 * Customer Order Tracking - Real-time Status
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/billing.php';

requireRole('customer');

$orderId = (int)($_GET['id'] ?? 0);
$error = '';

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

// Get order details
$order = null;
$orderItems = [];
$payments = []; // Initialize to avoid undefined variable
$tasks = [];

if ($orderId > 0 && $customerId) {
    try {
        // Get order
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id) as total_paid
            FROM orders o
            WHERE o.id = ? AND o.customer_id = ?
        ");
        $stmt->execute([$orderId, $customerId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $error = 'Order not found.';
        } else {
            // Get order items
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll();
            
            // Get payments
            $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY paid_at DESC");
            $stmt->execute([$orderId]);
            $payments = $stmt->fetchAll();
            
            // Get staff tasks
            $stmt = $pdo->prepare("
                SELECT st.*, u.username as staff_name
                FROM staff_tasks st
                LEFT JOIN users u ON st.staff_id = u.id
                WHERE st.order_id = ?
                ORDER BY st.assigned_at DESC
            ");
            $stmt->execute([$orderId]);
            $tasks = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $error = 'Error fetching order data: ' . $e->getMessage();
    }
} else {
    $error = 'Invalid order ID.';
}

$pageTitle = "Track Order";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-geo-alt"></i> Track Order
                    </h1>
                </div>
                <div>
                    <a href="<?php echo baseUrl('customer/orders.php'); ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php elseif ($order): ?>
        <!-- Order Information -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Order Information</h5>
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
                                        ($order['status'] == 'pending' ? 'warning' : 
                                        ($order['status'] == 'cancelled' ? 'danger' : 'info')); 
                                ?> fs-6" id="orderStatusBadge">
                                    <?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Description:</strong><br>
                                <?php echo htmlspecialchars($order['description'] ?? 'N/A'); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Delivery Date:</strong><br>
                                <span id="deliveryDate">
                                    <?php echo $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : 'Not set'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Total Amount:</strong><br>
                                Rs <span id="totalAmount"><?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Total Paid:</strong><br>
                                Rs <span id="totalPaid"><?php echo number_format($order['total_paid'], 2); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Remaining:</strong><br>
                                Rs <span id="remainingAmount"><?php echo number_format($order['remaining_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Status Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'in-progress', 'completed', 'delivered', 'cancelled']) ? 'active' : ''; ?>">
                                <i class="bi bi-clock-history"></i>
                                <div>Pending</div>
                                <small><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div class="timeline-item <?php echo in_array($order['status'], ['in-progress', 'completed', 'delivered']) ? 'active' : ''; ?>">
                                <i class="bi bi-gear"></i>
                                <div>In Progress</div>
                                <small id="inProgressDate">-</small>
                            </div>
                            <div class="timeline-item <?php echo in_array($order['status'], ['completed', 'delivered']) ? 'active' : ''; ?>">
                                <i class="bi bi-check-circle"></i>
                                <div>Completed</div>
                                <small id="completedDate"><?php echo $order['completed_at'] ? date('M d, Y', strtotime($order['completed_at'])) : '-'; ?></small>
                            </div>
                            <div class="timeline-item <?php echo $order['status'] == 'delivered' ? 'active' : ''; ?>">
                                <i class="bi bi-truck"></i>
                                <div>Delivered</div>
                                <small id="deliveredDate">-</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
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
        </div>
        
        <!-- Staff Tasks -->
        <?php if (count($tasks) > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Tasks & Progress</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Task Description</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($task['staff_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($task['task_description']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $task['priority'] == 'urgent' ? 'danger' : 
                                                            ($task['priority'] == 'high' ? 'warning' : 
                                                            ($task['priority'] == 'medium' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst($task['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $task['status'] == 'completed' ? 'success' : 
                                                            ($task['status'] == 'in-progress' ? 'info' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Payments -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Payment History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($payments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Payment #</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                                                <td>Rs <?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($payment['paid_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo baseUrl('api/receipt.php?id=' . $payment['id']); ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-receipt"></i> Receipt
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No payments recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Invoice Link -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <a href="<?php echo baseUrl('api/invoice.php?id=' . $orderId); ?>" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="bi bi-receipt-cutoff"></i> View Invoice
                        </a>
                        <a href="<?php echo baseUrl('api/invoice.php?id=' . $orderId . '&download=1'); ?>" 
                           class="btn btn-outline-primary">
                            <i class="bi bi-download"></i> Download Invoice
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
    opacity: 0.5;
    transition: opacity 0.3s;
}

.timeline-item.active {
    opacity: 1;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -25px;
    top: 25px;
    width: 2px;
    height: calc(100% - 10px);
    background: #ddd;
}

.timeline-item.active:not(:last-child)::before {
    background: #0d6efd;
}

.timeline-item i {
    position: absolute;
    left: -30px;
    top: 0;
    width: 20px;
    height: 20px;
    background: #ddd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.timeline-item.active i {
    background: #0d6efd;
    color: white;
}
</style>

<script>
// Poll order status every 10 seconds
let pollInterval;

function pollOrderStatus() {
    if (!<?php echo $orderId > 0 ? 'true' : 'false'; ?>) return;
    
    fetch('<?php echo baseUrl('api/order_status.php'); ?>?id=<?php echo $orderId; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.order) {
                updateOrderStatus(data.order);
            }
        })
        .catch(error => {
            console.error('Error polling order status:', error);
        });
}

function updateOrderStatus(order) {
    // Update status badge
    const statusBadge = document.getElementById('orderStatusBadge');
    if (statusBadge) {
        statusBadge.textContent = order.status.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
        statusBadge.className = 'badge fs-6 bg-' + getStatusColor(order.status);
    }
    
    // Update amounts
    if (document.getElementById('totalAmount')) {
        document.getElementById('totalAmount').textContent = parseFloat(order.total_amount).toFixed(2);
    }
    if (document.getElementById('totalPaid')) {
        document.getElementById('totalPaid').textContent = parseFloat(order.total_paid || 0).toFixed(2);
    }
    if (document.getElementById('remainingAmount')) {
        document.getElementById('remainingAmount').textContent = parseFloat(order.remaining_amount).toFixed(2);
    }
    
    // Update delivery date
    if (order.delivery_date && document.getElementById('deliveryDate')) {
        const date = new Date(order.delivery_date);
        document.getElementById('deliveryDate').textContent = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    // Update timeline
    updateTimeline(order.status, order.completed_at);
}

function getStatusColor(status) {
    switch(status) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'info';
    }
}

function updateTimeline(status, completedAt) {
    const items = document.querySelectorAll('.timeline-item');
    items.forEach(item => item.classList.remove('active'));
    
    if (status === 'pending' || status === 'in-progress' || status === 'completed' || status === 'delivered' || status === 'cancelled') {
        items[0].classList.add('active');
    }
    if (status === 'in-progress' || status === 'completed' || status === 'delivered') {
        items[1].classList.add('active');
    }
    if (status === 'completed' || status === 'delivered') {
        items[2].classList.add('active');
        if (completedAt && document.getElementById('completedDate')) {
            const date = new Date(completedAt);
            document.getElementById('completedDate').textContent = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
    if (status === 'delivered') {
        items[3].classList.add('active');
    }
}

// Start polling when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (<?php echo $orderId > 0 ? 'true' : 'false'; ?>) {
        // Poll immediately
        pollOrderStatus();
        
        // Then poll every 10 seconds
        pollInterval = setInterval(pollOrderStatus, 10000);
    }
});

// Clean up interval when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
    } else {
        if (<?php echo $orderId > 0 ? 'true' : 'false'; ?>) {
            pollOrderStatus();
            pollInterval = setInterval(pollOrderStatus, 10000);
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

