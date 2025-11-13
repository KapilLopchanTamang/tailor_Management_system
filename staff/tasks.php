<?php
/**
 * Staff Tasks - View, Claim, and Update Tasks
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('staff');

$staffId = $_SESSION['user_id'];
$message = '';
$error = '';
$taskId = (int)($_GET['id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

// Handle task claim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_task'])) {
    // Validate CSRF token
    validateCSRF();
    
    $taskId = (int)$_POST['task_id'];
    try {
        // Check if task is available (no staff assigned or assigned to this staff)
        $stmt = $pdo->prepare("SELECT id, staff_id FROM staff_tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            $error = 'Task not found.';
        } elseif ($task['staff_id'] != $staffId && $task['staff_id'] != 0) {
            $error = 'Task is already assigned to another staff member.';
        } else {
            // Claim task
            $stmt = $pdo->prepare("UPDATE staff_tasks SET staff_id = ?, status = 'assigned' WHERE id = ?");
            $stmt->execute([$staffId, $taskId]);
            $message = 'Task claimed successfully.';
        }
    } catch (PDOException $e) {
        $error = 'Error claiming task: ' . $e->getMessage();
    }
}

// Get tasks
$tasks = [];
$task = null;
$availableTasks = []; // Initialize to avoid undefined variable error

try {
    $whereConditions = ["st.staff_id = ?"];
    $params = [$staffId];
    
    if ($statusFilter) {
        $whereConditions[] = "st.status = ?"; // Specify table alias to avoid ambiguity
        $params[] = $statusFilter;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Get all tasks for this staff
    $stmt = $pdo->prepare("
        SELECT st.*, o.order_number, o.description as order_description, o.status as order_status, 
               o.delivery_date, o.total_amount,
               c.name as customer_name
        FROM staff_tasks st
        LEFT JOIN orders o ON st.order_id = o.id
        LEFT JOIN customers c ON o.customer_id = c.id
        $whereClause
        ORDER BY 
            CASE st.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            st.assigned_at DESC
    ");
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    
    // Get specific task if ID provided
    if ($taskId > 0) {
        $stmt = $pdo->prepare("
            SELECT st.*, o.order_number, o.description as order_description, o.status as order_status, 
                   o.delivery_date, o.total_amount, o.created_at as order_created_at,
                   c.name as customer_name,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM staff_tasks st
            LEFT JOIN orders o ON st.order_id = o.id
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE st.id = ? AND st.staff_id = ?
        ");
        $stmt->execute([$taskId, $staffId]);
        $task = $stmt->fetch();
    }
    
    // Get available tasks (unassigned or assigned to this staff)
    $stmt = $pdo->query("
        SELECT st.*, o.order_number, o.description as order_description,
               c.name as customer_name
        FROM staff_tasks st
        LEFT JOIN orders o ON st.order_id = o.id
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE st.staff_id = 0 OR st.staff_id IS NULL
        ORDER BY 
            CASE st.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            st.assigned_at DESC
        LIMIT 10
    ");
    $availableTasks = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Error fetching tasks: ' . $e->getMessage();
    $tasks = []; // Ensure arrays are initialized even on error
    $availableTasks = [];
}

$pageTitle = "My Tasks";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-list-task"></i> My Tasks
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
    
    <!-- Status Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="<?php echo baseUrl('staff/tasks.php'); ?>" 
                   class="btn btn-<?php echo $statusFilter === '' ? 'primary' : 'outline-primary'; ?>">
                    All
                </a>
                <a href="<?php echo baseUrl('staff/tasks.php?status=assigned'); ?>" 
                   class="btn btn-<?php echo $statusFilter === 'assigned' ? 'warning' : 'outline-warning'; ?>">
                    Assigned
                </a>
                <a href="<?php echo baseUrl('staff/tasks.php?status=in-progress'); ?>" 
                   class="btn btn-<?php echo $statusFilter === 'in-progress' ? 'info' : 'outline-info'; ?>">
                    In Progress
                </a>
                <a href="<?php echo baseUrl('staff/tasks.php?status=completed'); ?>" 
                   class="btn btn-<?php echo $statusFilter === 'completed' ? 'success' : 'outline-success'; ?>">
                    Completed
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Tasks List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">My Tasks</h5>
                </div>
                <div class="card-body">
                    <?php if (count($tasks) > 0): ?>
                        <div id="tasksList" class="list-group">
                            <?php foreach ($tasks as $taskItem): ?>
                                <div class="list-group-item task-item" data-task-id="<?php echo $taskItem['id']; ?>" data-order-id="<?php echo $taskItem['order_id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <h6 class="mb-0 me-2">Task #<?php echo $taskItem['id']; ?></h6>
                                                <span class="badge bg-<?php 
                                                    echo $taskItem['priority'] == 'urgent' ? 'danger' : 
                                                        ($taskItem['priority'] == 'high' ? 'warning' : 
                                                        ($taskItem['priority'] == 'medium' ? 'info' : 'secondary')); 
                                                ?>">
                                                    <?php echo ucfirst($taskItem['priority']); ?>
                                                </span>
                                                <span class="badge bg-<?php 
                                                    echo $taskItem['status'] == 'completed' ? 'success' : 
                                                        ($taskItem['status'] == 'in-progress' ? 'info' : 'warning'); 
                                                ?> ms-2">
                                                    <?php echo ucfirst(str_replace('-', ' ', $taskItem['status'])); ?>
                                                </span>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($taskItem['task_description']); ?></p>
                                            <small class="text-muted">
                                                Order: <?php echo htmlspecialchars($taskItem['order_number']); ?> | 
                                                Customer: <?php echo htmlspecialchars($taskItem['customer_name'] ?? 'N/A'); ?>
                                                <?php if ($taskItem['due_date']): ?>
                                                    | Due: <?php echo date('M d, Y', strtotime($taskItem['due_date'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <div class="btn-group-vertical btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary update-status-btn" 
                                                        data-task-id="<?php echo $taskItem['id']; ?>"
                                                        data-current-status="<?php echo $taskItem['status']; ?>">
                                                    <i class="bi bi-arrow-repeat"></i> Update Status
                                                </button>
                                                <a href="<?php echo baseUrl('staff/tasks.php?id=' . $taskItem['id']); ?>" 
                                                   class="btn btn-outline-info">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No tasks found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Task Details / Available Tasks -->
        <div class="col-md-4">
            <?php if ($task): ?>
                <!-- Task Details -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Task Details</h5>
                    </div>
                    <div class="card-body">
                        <h6>Task #<?php echo $task['id']; ?></h6>
                        <p><?php echo htmlspecialchars($task['task_description']); ?></p>
                        
                        <div class="mb-3">
                            <strong>Priority:</strong>
                            <span class="badge bg-<?php 
                                echo $task['priority'] == 'urgent' ? 'danger' : 
                                    ($task['priority'] == 'high' ? 'warning' : 
                                    ($task['priority'] == 'medium' ? 'info' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst($task['priority']); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Status:</strong>
                            <span class="badge bg-<?php 
                                echo $task['status'] == 'completed' ? 'success' : 
                                    ($task['status'] == 'in-progress' ? 'info' : 'warning'); 
                            ?>" id="taskStatusBadge">
                                <?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Order:</strong> <?php echo htmlspecialchars($task['order_number']); ?><br>
                            <strong>Customer:</strong> <?php echo htmlspecialchars($task['customer_name'] ?? 'N/A'); ?><br>
                            <?php if ($task['due_date']): ?>
                                <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary update-status-btn" 
                                    data-task-id="<?php echo $task['id']; ?>"
                                    data-current-status="<?php echo $task['status']; ?>">
                                <i class="bi bi-arrow-repeat"></i> Update Status
                            </button>
                            <a href="<?php echo baseUrl('staff/orders.php?id=' . $task['order_id']); ?>" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-bag"></i> View Order
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Available Tasks -->
            <?php if (!empty($availableTasks) && count($availableTasks) > 0): ?>
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Available Tasks</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($availableTasks as $availTask): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <h6>Task #<?php echo $availTask['id']; ?></h6>
                                <p class="small mb-2"><?php echo htmlspecialchars(substr($availTask['task_description'], 0, 100)) . '...'; ?></p>
                                <small class="text-muted">
                                    Order: <?php echo htmlspecialchars($availTask['order_number']); ?>
                                </small>
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="claim_task" value="1">
                                    <input type="hidden" name="task_id" value="<?php echo $availTask['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-hand-thumbs-up"></i> Claim Task
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Task Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    <input type="hidden" id="updateTaskId" name="task_id">
                    <input type="hidden" id="updateOrderId" name="order_id">
                    
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">New Status</label>
                        <select class="form-select" id="newStatus" name="status" required>
                            <option value="assigned">Assigned</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="taskNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="taskNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="updateOrderStatus" name="update_order_status" value="1">
                        <label class="form-check-label" for="updateOrderStatus">
                            Update order status to match task status
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveStatusBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Sortable.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
// Update status button click
document.querySelectorAll('.update-status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const taskId = this.dataset.taskId;
        const currentStatus = this.dataset.currentStatus;
        const taskItem = document.querySelector(`[data-task-id="${taskId}"]`);
        const orderId = taskItem ? taskItem.dataset.orderId : '';
        
        document.getElementById('updateTaskId').value = taskId;
        document.getElementById('updateOrderId').value = orderId;
        document.getElementById('newStatus').value = currentStatus;
        document.getElementById('taskNotes').value = '';
        document.getElementById('updateOrderStatus').checked = false;
        
        const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
        modal.show();
    });
});

// Save status update
document.getElementById('saveStatusBtn')?.addEventListener('click', function() {
    const form = document.getElementById('updateStatusForm');
    const formData = new FormData(form);
    formData.append('update_task_status', '1');
    
    fetch('<?php echo baseUrl('api/update_task_status.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

// Optional: Drag and drop for task prioritization (using Sortable.js)
<?php if (count($tasks) > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    const tasksList = document.getElementById('tasksList');
    if (tasksList && typeof Sortable !== 'undefined') {
        new Sortable(tasksList, {
            animation: 150,
            handle: '.task-item',
            onEnd: function(evt) {
                // Optional: Save new order to database
                // This is just for visual reordering
                console.log('Task order changed');
            }
        });
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

