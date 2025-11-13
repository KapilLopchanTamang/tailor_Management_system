<?php
/**
 * Admin - Staff Management
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$message = '';
$error = '';

// Handle task assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $staffId = (int)$_POST['staff_id'];
    $orderId = (int)$_POST['order_id'];
    $taskDescription = sanitize($_POST['task_description'] ?? '');
    $priority = sanitize($_POST['priority'] ?? 'medium');
    $dueDate = sanitize($_POST['due_date'] ?? '');
    
    if (empty($taskDescription)) {
        $error = 'Task description is required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO staff_tasks (staff_id, order_id, task_description, priority, due_date, status) VALUES (?, ?, ?, ?, ?, 'assigned')");
            $stmt->execute([$staffId, $orderId, $taskDescription, $priority, $dueDate ?: null]);
            $message = 'Task assigned successfully.';
        } catch (PDOException $e) {
            $error = 'Error assigning task: ' . $e->getMessage();
        }
    }
}

// Handle task status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task_status'])) {
    $taskId = (int)$_POST['task_id'];
    $status = sanitize($_POST['status'] ?? '');
    
    try {
        $now = date('Y-m-d H:i:s');
        if ($status === 'in-progress') {
            $stmt = $pdo->prepare("UPDATE staff_tasks SET status = ?, started_at = ? WHERE id = ?");
            $stmt->execute([$status, $now, $taskId]);
        } elseif ($status === 'completed') {
            $stmt = $pdo->prepare("UPDATE staff_tasks SET status = ?, completed_at = ? WHERE id = ?");
            $stmt->execute([$status, $now, $taskId]);
        } else {
            $stmt = $pdo->prepare("UPDATE staff_tasks SET status = ? WHERE id = ?");
            $stmt->execute([$status, $taskId]);
        }
        $message = 'Task status updated successfully.';
    } catch (PDOException $e) {
        $error = 'Error updating task: ' . $e->getMessage();
    }
}

// Get staff members with their tasks
try {
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, u.status,
               COUNT(st.id) as total_tasks,
               SUM(CASE WHEN st.status = 'assigned' THEN 1 ELSE 0 END) as assigned_tasks,
               SUM(CASE WHEN st.status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_tasks,
               SUM(CASE WHEN st.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM users u
        LEFT JOIN staff_tasks st ON u.id = st.staff_id
        WHERE u.role = 'staff'
        GROUP BY u.id, u.username, u.email, u.status
        ORDER BY u.username ASC
    ");
    $staffMembers = $stmt->fetchAll();
    
    // Get all tasks with order and staff info
    $stmt = $pdo->query("
        SELECT st.*, u.username as staff_name, o.order_number, c.name as customer_name
        FROM staff_tasks st
        LEFT JOIN users u ON st.staff_id = u.id
        LEFT JOIN orders o ON st.order_id = o.id
        LEFT JOIN customers c ON o.customer_id = c.id
        ORDER BY st.assigned_at DESC
        LIMIT 50
    ");
    $tasks = $stmt->fetchAll();
    
    // Get pending orders for assignment
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, c.name as customer_name
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.status IN ('pending', 'in-progress')
        ORDER BY o.created_at DESC
    ");
    $pendingOrders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Error fetching data: ' . $e->getMessage();
    $staffMembers = [];
    $tasks = [];
    $pendingOrders = [];
}

$pageTitle = "Staff Management";
$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-person-badge"></i> Staff Management
                    </h1>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignTaskModal">
                        <i class="bi bi-plus-circle"></i> Assign Task
                    </button>
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
    
    <!-- Staff Members -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Staff Members</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Staff Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Total Tasks</th>
                                    <th>Assigned</th>
                                    <th>In Progress</th>
                                    <th>Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($staffMembers) > 0): ?>
                                    <?php foreach ($staffMembers as $staff): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                            <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $staff['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($staff['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $staff['total_tasks']; ?></td>
                                            <td><span class="badge bg-warning"><?php echo $staff['assigned_tasks']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo $staff['in_progress_tasks']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $staff['completed_tasks']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No staff members found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tasks -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-task"></i> Recent Tasks</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Task ID</th>
                                    <th>Staff</th>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Task Description</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($tasks) > 0): ?>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td><?php echo $task['id']; ?></td>
                                            <td><?php echo htmlspecialchars($task['staff_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($task['order_number'] ?? 'N/A'); ?></td>
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
                                            <td><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="updateTaskStatus(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No tasks found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Task Modal -->
<div class="modal fade" id="assignTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Task to Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="assign_task" value="1">
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
                        <label for="order_id" class="form-label">Order *</label>
                        <select class="form-select" id="order_id" name="order_id" required>
                            <option value="">Select Order</option>
                            <?php foreach ($pendingOrders as $order): ?>
                                <option value="<?php echo $order['id']; ?>">
                                    <?php echo htmlspecialchars($order['order_number']); ?> - <?php echo htmlspecialchars($order['customer_name']); ?>
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
                    <button type="submit" class="btn btn-primary">Assign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Task Status Modal -->
<div class="modal fade" id="updateTaskStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Update Task Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="update_task_status" value="1">
                    <input type="hidden" name="task_id" id="task_id">
                    
                    <div class="mb-3">
                        <label for="task_status" class="form-label">Status *</label>
                        <select class="form-select" id="task_status" name="status" required>
                            <option value="assigned">Assigned</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
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

<script>
function updateTaskStatus(taskId, currentStatus) {
    document.getElementById('task_id').value = taskId;
    document.getElementById('task_status').value = currentStatus;
    
    const modal = new bootstrap.Modal(document.getElementById('updateTaskStatusModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

