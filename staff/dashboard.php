<?php
/**
 * Staff Dashboard - Assigned Tasks and Order Progress
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('staff');

$staffId = $_SESSION['user_id'];

// Get statistics
$stats = [
    'total_tasks' => 0,
    'pending_tasks' => 0,
    'in_progress_tasks' => 0,
    'completed_tasks' => 0
];

// Get assigned tasks with order details
$tasks = [];
$orders = [];

try {
    // Statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM staff_tasks WHERE staff_id = ?");
    $stmt->execute([$staffId]);
    $stats['total_tasks'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM staff_tasks WHERE staff_id = ? AND status = 'assigned'");
    $stmt->execute([$staffId]);
    $stats['pending_tasks'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM staff_tasks WHERE staff_id = ? AND status = 'in-progress'");
    $stmt->execute([$staffId]);
    $stats['in_progress_tasks'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM staff_tasks WHERE staff_id = ? AND status = 'completed'");
    $stmt->execute([$staffId]);
    $stats['completed_tasks'] = $stmt->fetch()['count'];
    
    // Get assigned tasks with order details
    $stmt = $pdo->prepare("
        SELECT st.*, o.order_number, o.description as order_description, o.status as order_status, 
               o.delivery_date, o.total_amount, o.created_at as order_created_at,
               c.name as customer_name,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM staff_tasks st
        LEFT JOIN orders o ON st.order_id = o.id
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE st.staff_id = ?
        ORDER BY 
            CASE st.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            st.assigned_at DESC
        LIMIT 20
    ");
    $stmt->execute([$staffId]);
    $tasks = $stmt->fetchAll();
    
    // Get unique orders from tasks
    $orderIds = array_unique(array_column($tasks, 'order_id'));
    if (!empty($orderIds)) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $pdo->prepare("
            SELECT o.*, o.status as order_status, o.created_at as order_created_at, 
                   c.name as customer_name,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                   (SELECT COUNT(*) FROM staff_tasks WHERE order_id = o.id AND staff_id = ?) as task_count
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id IN ($placeholders)
            ORDER BY o.created_at DESC
        ");
        $stmt->execute(array_merge([$staffId], $orderIds));
        $orders = $stmt->fetchAll();
    } else {
        $orders = [];
    }
    
} catch (PDOException $e) {
    $error = 'Error fetching data: ' . $e->getMessage();
}

$pageTitle = "Staff Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-speedometer2"></i> Staff Dashboard
            </h1>
            <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Tasks</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_tasks']); ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-list-task fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Pending</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['pending_tasks']); ?></h2>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-clock-history fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">In Progress</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['in_progress_tasks']); ?></h2>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-gear fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Completed</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['completed_tasks']); ?></h2>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Assigned Tasks -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-task"></i> My Assigned Tasks</h5>
                    <a href="<?php echo baseUrl('staff/tasks.php'); ?>" class="btn btn-sm btn-primary">View All Tasks</a>
                </div>
                <div class="card-body">
                    <?php if (count($tasks) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task ID</th>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Task Description</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td><?php echo $task['id']; ?></td>
                                            <td><?php echo htmlspecialchars($task['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($task['customer_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars(substr($task['task_description'], 0, 50)) . (strlen($task['task_description']) > 50 ? '...' : ''); ?></td>
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
                                            <td><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'Not set'; ?></td>
                                            <td>
                                                <a href="<?php echo baseUrl('staff/tasks.php?id=' . $task['id']); ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No tasks assigned yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Progress Timeline -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Order Progress Timeline</h5>
                </div>
                <div class="card-body">
                    <?php if (count($orders) > 0): ?>
                        <div class="timeline-container">
                            <?php foreach ($orders as $order): ?>
                                <div class="timeline-item mb-4">
                                    <div class="card border">
                                        <div class="card-body">
                                            <?php 
                                            $orderStatus = $order['order_status'] ?? $order['status'] ?? 'pending';
                                            $orderCreatedAt = $order['order_created_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s');
                                            ?>
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($order['order_number']); ?></h6>
                                                    <p class="text-muted mb-0 small">Customer: <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
                                                </div>
                                                <span class="badge bg-<?php 
                                                    echo $orderStatus == 'completed' ? 'success' : 
                                                        ($orderStatus == 'pending' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('-', ' ', $orderStatus)); ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Progress Timeline -->
                                            <div class="timeline-progress">
                                                <div class="timeline-step <?php echo in_array($orderStatus, ['pending', 'in-progress', 'completed', 'delivered']) ? 'completed' : ''; ?>">
                                                    <div class="timeline-step-icon">
                                                        <i class="bi bi-clock-history"></i>
                                                    </div>
                                                    <div class="timeline-step-content">
                                                        <strong>Pending</strong>
                                                        <small><?php echo $orderCreatedAt ? date('M d, Y', strtotime($orderCreatedAt)) : '-'; ?></small>
                                                    </div>
                                                </div>
                                                
                                                <div class="timeline-step <?php echo in_array($orderStatus, ['in-progress', 'completed', 'delivered']) ? 'completed' : ''; ?>">
                                                    <div class="timeline-step-icon">
                                                        <i class="bi bi-gear"></i>
                                                    </div>
                                                    <div class="timeline-step-content">
                                                        <strong>In Progress</strong>
                                                        <small><?php echo $orderStatus == 'in-progress' ? 'Current' : '-'; ?></small>
                                                    </div>
                                                </div>
                                                
                                                <div class="timeline-step <?php echo in_array($orderStatus, ['completed', 'delivered']) ? 'completed' : ''; ?>">
                                                    <div class="timeline-step-icon">
                                                        <i class="bi bi-check-circle"></i>
                                                    </div>
                                                    <div class="timeline-step-content">
                                                        <strong>Completed</strong>
                                                        <small><?php echo $orderStatus == 'completed' ? 'Done' : '-'; ?></small>
                                                    </div>
                                                </div>
                                                
                                                <div class="timeline-step <?php echo $orderStatus == 'delivered' ? 'completed' : ''; ?>">
                                                    <div class="timeline-step-icon">
                                                        <i class="bi bi-truck"></i>
                                                    </div>
                                                    <div class="timeline-step-content">
                                                        <strong>Delivered</strong>
                                                        <small><?php echo $orderStatus == 'delivered' ? 'Delivered' : '-'; ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Items: <?php echo $order['item_count']; ?></small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Total: Rs <?php echo number_format($order['total_amount'], 2); ?></small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Delivery: <?php echo $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : 'Not set'; ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <a href="<?php echo baseUrl('staff/orders.php?id=' . $order['id']); ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View Order
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No orders assigned yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-progress {
    display: flex;
    justify-content: space-between;
    position: relative;
    padding: 20px 0;
}

.timeline-progress::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: #ddd;
    z-index: 0;
}

.timeline-step {
    flex: 1;
    position: relative;
    z-index: 1;
    text-align: center;
}

.timeline-step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ddd;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    transition: all 0.3s;
}

.timeline-step.completed .timeline-step-icon {
    background: #0d6efd;
    color: white;
}

.timeline-step-content {
    font-size: 0.875rem;
}

.timeline-step-content strong {
    display: block;
    margin-bottom: 5px;
}

.timeline-step-content small {
    display: block;
    color: #6c757d;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
