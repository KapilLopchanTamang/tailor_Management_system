<?php
/**
 * Admin - Orders Management
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/billing.php';
require_once __DIR__ . '/../includes/notifications.php';

requireRole('admin');

$message = '';
$error = '';
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$orderId = $_GET['id'] ?? 0;

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = sanitize($_POST['status'] ?? '');
    $sendNotification = isset($_POST['send_notification']) && $_POST['send_notification'] == '1';
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        
        // Send notification to customer
        if ($sendNotification) {
            notifyOrderStatusUpdate($pdo, $orderId, $status, false);
        }
        
        $message = 'Order status updated successfully.';
    } catch (PDOException $e) {
        $error = 'Error updating order: ' . $e->getMessage();
    }
}

// Handle delivery date update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_delivery_date'])) {
    $orderId = (int)$_POST['order_id'];
    $deliveryDate = sanitize($_POST['delivery_date'] ?? '');
    $sendNotification = isset($_POST['send_notification']) && $_POST['send_notification'] == '1';
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET delivery_date = ? WHERE id = ?");
        $stmt->execute([$deliveryDate ?: null, $orderId]);
        
        // Send notification to customer
        if ($sendNotification && $deliveryDate) {
            notifyDeliveryScheduled($pdo, $orderId, $deliveryDate, false);
        }
        
        $message = 'Delivery date updated successfully.';
    } catch (PDOException $e) {
        $error = 'Error updating delivery date: ' . $e->getMessage();
    }
}

// Handle staff assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_staff'])) {
    $orderId = (int)$_POST['order_id'];
    $staffId = (int)$_POST['staff_id'];
    $taskDescription = sanitize($_POST['task_description'] ?? '');
    $priority = sanitize($_POST['priority'] ?? 'medium');
    $dueDate = sanitize($_POST['due_date'] ?? '');
    
    if (empty($taskDescription)) {
        $error = 'Task description is required.';
    } else {
        try {
            // Update order status to in-progress
            $stmt = $pdo->prepare("UPDATE orders SET status = 'in-progress' WHERE id = ?");
            $stmt->execute([$orderId]);
            
            // Create staff task
            $stmt = $pdo->prepare("INSERT INTO staff_tasks (staff_id, order_id, task_description, priority, due_date, status) VALUES (?, ?, ?, ?, ?, 'assigned')");
            $stmt->execute([$staffId, $orderId, $taskDescription, $priority, $dueDate ?: null]);
            $taskId = $pdo->lastInsertId();
            
            // Notify staff about task assignment
            notifyTaskAssignment($pdo, $taskId, $staffId, false);
            
            // Notify customer about status update
            notifyOrderStatusUpdate($pdo, $orderId, 'in-progress', false);
            
            $message = 'Staff assigned and task created successfully.';
        } catch (PDOException $e) {
            $error = 'Error assigning staff: ' . $e->getMessage();
        }
    }
}

// Get orders with filters
$orders = [];
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(o.order_number LIKE ? OR c.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($statusFilter) {
    $whereConditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Get total count
    $countParams = $params;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        $whereClause
    ");
    $stmt->execute($countParams);
    $totalOrders = $stmt->fetch()['total'];
    $totalPages = ceil($totalOrders / $perPage);
    
    // Get orders
    $stmt = $pdo->prepare("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone,
               u.email as customer_email,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
               (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id) as total_paid
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Get staff members for assignment
    $stmt = $pdo->query("SELECT id, username, email FROM users WHERE role = 'staff' AND status = 'active'");
    $staffMembers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Error fetching orders: ' . $e->getMessage();
}

$pageTitle = "Orders Management";
$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-bag"></i> Orders Management
            </h1>
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
    
    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Order number, customer name, email...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in-progress" <?php echo $statusFilter === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="<?php echo baseUrl('admin/orders.php'); ?>" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Remaining</th>
                            <th>Delivery Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></small>
                                    </td>
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
                                    <td>Rs <?php echo number_format($order['total_paid'], 2); ?></td>
                                    <td>
                                        Rs <?php echo number_format($order['remaining_amount'], 2); ?>
                                        <?php if ($order['remaining_amount'] <= 0): ?>
                                            <span class="badge bg-success ms-2">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-primary" 
                                                    onclick="viewOrder(<?php echo $order['id']; ?>)"
                                                    title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-info" 
                                                    onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')"
                                                    title="Update Status">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="assignStaff(<?php echo $order['id']; ?>)"
                                                    title="Assign Staff">
                                                <i class="bi bi-person-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-info" 
                                                    onclick="updateDeliveryDate(<?php echo $order['id']; ?>, '<?php echo $order['delivery_date'] ?? ''; ?>')"
                                                    title="Update Delivery Date">
                                                <i class="bi bi-calendar-event"></i>
                                            </button>
                                            <?php if ($order['remaining_amount'] > 0): ?>
                                                <button type="button" class="btn btn-warning" 
                                                        onclick="recordPayment(<?php echo $order['id']; ?>, <?php echo $order['total_amount']; ?>, <?php echo $order['remaining_amount']; ?>)"
                                                        title="Record Payment">
                                                    <i class="bi bi-cash-coin"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?php echo baseUrl('api/invoice.php?id=' . $order['id']); ?>" 
                                               target="_blank" 
                                               class="btn btn-outline-secondary" 
                                               title="View Invoice">
                                                <i class="bi bi-receipt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Order Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="order_id" id="status_order_id">
                        <?php echo csrfField(); ?>
                        
                        <div class="mb-3">
                        <label for="status_select" class="form-label">Status *</label>
                        <select class="form-select" id="status_select" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send_notification_status" name="send_notification" value="1" checked>
                            <label class="form-check-label" for="send_notification_status">
                                Notify customer
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Delivery Date Modal -->
<div class="modal fade" id="updateDeliveryDateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Delivery Date</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="update_delivery_date" value="1">
                        <input type="hidden" name="order_id" id="delivery_order_id">
                        <?php echo csrfField(); ?>
                        
                        <div class="mb-3">
                        <label for="delivery_date_input" class="form-label">Delivery Date *</label>
                        <input type="date" class="form-control" id="delivery_date_input" name="delivery_date" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send_notification_delivery" name="send_notification" value="1" checked>
                            <label class="form-check-label" for="send_notification_delivery">
                                Notify customer
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Delivery Date</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Staff Modal -->
<div class="modal fade" id="assignStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Staff to Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="assign_staff" value="1">
                    <input type="hidden" name="order_id" id="assign_order_id">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="staff_id" class="form-label">Staff Member *</label>
                        <select class="form-select" id="staff_id" name="staff_id" required>
                            <option value="">Select Staff</option>
                            <?php foreach ($staffMembers as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>">
                                    <?php echo htmlspecialchars($staff['username']); ?> (<?php echo htmlspecialchars($staff['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="task_description" class="form-label">Task Description *</label>
                        <textarea class="form-control" id="task_description" name="task_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="recordPaymentForm">
                    <input type="hidden" id="payment_order_id" name="order_id">
                    
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs</span>
                            <input type="number" class="form-control" id="payment_amount" name="amount" 
                                   step="0.01" min="0.01" required>
                        </div>
                        <small class="form-text text-muted">
                            Remaining: <span id="remaining_amount_display">Rs 0.00</span>
                        </small>
                        <div class="text-danger" id="amount_error" style="display: none;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method *</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_payment">Mobile Payment</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="transaction_id" class="form-label">Transaction ID (Optional)</label>
                        <input type="text" class="form-control" id="transaction_id" name="transaction_id">
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="payment_notes" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePaymentBtn">Record Payment</button>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(orderId, currentStatus) {
    document.getElementById('status_order_id').value = orderId;
    document.getElementById('status_select').value = currentStatus;
    
    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

function assignStaff(orderId) {
    document.getElementById('assign_order_id').value = orderId;
    
    const modal = new bootstrap.Modal(document.getElementById('assignStaffModal'));
    modal.show();
}

function updateDeliveryDate(orderId, currentDate) {
    document.getElementById('delivery_order_id').value = orderId;
    document.getElementById('delivery_date_input').value = currentDate;
    
    const modal = new bootstrap.Modal(document.getElementById('updateDeliveryDateModal'));
    modal.show();
}

function viewOrder(orderId) {
    // Redirect to order view page or show order details
    window.location.href = '?view=' + orderId;
}

function recordPayment(orderId, totalAmount, remainingAmount) {
    document.getElementById('payment_order_id').value = orderId;
    document.getElementById('payment_amount').value = '';
    document.getElementById('payment_amount').max = remainingAmount;
    document.getElementById('remaining_amount_display').textContent = 'Rs ' + parseFloat(remainingAmount).toFixed(2);
    document.getElementById('payment_method').value = 'cash';
    document.getElementById('transaction_id').value = '';
    document.getElementById('payment_notes').value = '';
    document.getElementById('amount_error').style.display = 'none';
    
    const modal = new bootstrap.Modal(document.getElementById('recordPaymentModal'));
    modal.show();
}

// Validate amount on input
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('payment_amount');
    if (amountInput) {
            amountInput.addEventListener('input', function() {
                const amount = parseFloat(this.value) || 0;
                const remainingText = document.getElementById('remaining_amount_display').textContent;
                const remaining = parseFloat(remainingText.replace('Rs ', '')) || 0;
                const errorDiv = document.getElementById('amount_error');
                
                if (amount > remaining) {
                    errorDiv.textContent = 'Amount cannot exceed remaining amount (Rs ' + remaining.toFixed(2) + ')';
                    errorDiv.style.display = 'block';
                    this.setCustomValidity('Amount exceeds remaining amount');
                } else {
                    errorDiv.style.display = 'none';
                    this.setCustomValidity('');
                }
            });
    }
    
    // Record payment
    const savePaymentBtn = document.getElementById('savePaymentBtn');
    if (savePaymentBtn) {
        savePaymentBtn.addEventListener('click', function() {
            const form = document.getElementById('recordPaymentForm');
            const formData = new FormData(form);
            
                const amount = parseFloat(formData.get('amount')) || 0;
                const remainingText = document.getElementById('remaining_amount_display').textContent;
                const remaining = parseFloat(remainingText.replace('Rs ', '')) || 0;
            
            if (amount <= 0) {
                alert('Please enter a valid amount.');
                return;
            }
            
            if (amount > remaining) {
                alert('Amount cannot exceed remaining amount.');
                return;
            }
            
            // Disable button during submission
            this.disabled = true;
            this.textContent = 'Processing...';
            
            fetch('<?php echo baseUrl('api/record_payment.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment recorded successfully! Payment Number: ' + data.payment_number);
                    // Option to view receipt
                    if (confirm('Payment recorded! Would you like to view the receipt?')) {
                        window.open('<?php echo baseUrl('api/receipt.php?id='); ?>' + data.payment_id, '_blank');
                    }
                    // Reload page to show updated data
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.textContent = 'Record Payment';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                this.disabled = false;
                this.textContent = 'Record Payment';
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

