<?php
/**
 * Admin - Feedback Management
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Ensure admin_response column exists
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM feedback LIKE 'admin_response'");
    $columnExists = $stmt->fetch();
    if (!$columnExists) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE feedback ADD COLUMN admin_response TEXT DEFAULT NULL AFTER comment");
    }
} catch (PDOException $e) {
    // Ignore if column already exists or other error
}

// Ensure average_rating column exists in orders table
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'average_rating'");
    $columnExists = $stmt->fetch();
    if (!$columnExists) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE orders ADD COLUMN average_rating DECIMAL(3,2) DEFAULT NULL AFTER remaining_amount");
    }
} catch (PDOException $e) {
    // Ignore if column already exists or other error
}

$message = '';
$error = '';

// Handle feedback response/status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    validateCSRF();
    
    if (isset($_POST['respond_feedback'])) {
        $feedbackId = (int)$_POST['feedback_id'];
        $adminResponse = sanitize($_POST['admin_response'] ?? '');
        $status = sanitize($_POST['status'] ?? 'approved');
        
        try {
            $stmt = $pdo->prepare("UPDATE feedback SET admin_response = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$adminResponse, $status, $feedbackId]);
            $message = 'Feedback response saved successfully.';
        } catch (PDOException $e) {
            $error = 'Error updating feedback: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete_feedback'])) {
        $feedbackId = (int)$_POST['feedback_id'];
        try {
            // Get order_id before deleting to update rating
            $stmt = $pdo->prepare("SELECT order_id FROM feedback WHERE id = ?");
            $stmt->execute([$feedbackId]);
            $feedback = $stmt->fetch();
            
            if ($feedback) {
                $pdo->beginTransaction();
                
                // Delete feedback
                $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
                $stmt->execute([$feedbackId]);
                
                // Update average rating in orders table
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET average_rating = (
                        SELECT COALESCE(AVG(rating), NULL)
                        FROM feedback 
                        WHERE order_id = ? AND status = 'approved'
                    )
                    WHERE id = ?
                ");
                $stmt->execute([$feedback['order_id'], $feedback['order_id']]);
                
                $pdo->commit();
                $message = 'Feedback deleted successfully.';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Error deleting feedback: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$ratingFilter = $_GET['rating'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "f.status = ?";
    $params[] = $statusFilter;
}

if ($ratingFilter !== 'all') {
    $whereConditions[] = "f.rating = ?";
    $params[] = (int)$ratingFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM feedback f $whereClause");
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch()['total'];
    $totalPages = ceil($totalCount / $perPage);
} catch (PDOException $e) {
    $error = 'Error fetching feedback count: ' . $e->getMessage();
    $totalCount = 0;
    $totalPages = 0;
}

// Get feedback list
$feedbackList = [];
try {
    $stmt = $pdo->prepare("
        SELECT f.*, 
               c.name as customer_name, c.phone as customer_phone,
               o.order_number, o.description as order_description, o.total_amount,
               u.email as customer_email
        FROM feedback f
        LEFT JOIN customers c ON f.customer_id = c.id
        LEFT JOIN orders o ON f.order_id = o.id
        LEFT JOIN users u ON c.user_id = u.id
        $whereClause
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $feedbackList = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching feedback: ' . $e->getMessage();
}

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'average_rating' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM feedback");
    $stats['total'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'");
    $stats['pending'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'approved'");
    $stats['approved'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'rejected'");
    $stats['rejected'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT AVG(rating) as avg FROM feedback WHERE status = 'approved'");
    $stats['average_rating'] = round($stmt->fetch()['avg'] ?? 0, 2);
} catch (PDOException $e) {
    // Ignore stats errors
}

$pageTitle = "Feedback Management";
$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-chat-dots"></i> Feedback Management
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
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Feedback</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Pending</h6>
                    <h2 class="mb-0 text-warning"><?php echo number_format($stats['pending']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Approved</h6>
                    <h2 class="mb-0 text-success"><?php echo number_format($stats['approved']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Average Rating</h6>
                    <h2 class="mb-0">
                        <?php if ($stats['average_rating'] > 0): ?>
                            <?php echo number_format($stats['average_rating'], 1); ?>/5.0
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="rating" class="form-label">Rating</label>
                    <select class="form-select" id="rating" name="rating" onchange="this.form.submit()">
                        <option value="all" <?php echo $ratingFilter === 'all' ? 'selected' : ''; ?>>All Ratings</option>
                        <option value="5" <?php echo $ratingFilter === '5' ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $ratingFilter === '4' ? 'selected' : ''; ?>>4 Stars</option>
                        <option value="3" <?php echo $ratingFilter === '3' ? 'selected' : ''; ?>>3 Stars</option>
                        <option value="2" <?php echo $ratingFilter === '2' ? 'selected' : ''; ?>>2 Stars</option>
                        <option value="1" <?php echo $ratingFilter === '1' ? 'selected' : ''; ?>>1 Star</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="<?php echo baseUrl('admin/feedback.php'); ?>" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Feedback Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Order #</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Admin Response</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($feedbackList) > 0): ?>
                            <?php foreach ($feedbackList as $feedback): ?>
                                <tr>
                                    <td><?php echo $feedback['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($feedback['customer_name'] ?? 'N/A'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($feedback['customer_email'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <a href="<?php echo baseUrl('admin/orders.php?view=' . $feedback['order_id']); ?>">
                                            <?php echo htmlspecialchars($feedback['order_number']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= $feedback['rating'] ? '-fill text-warning' : ''; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-1">(<?php echo $feedback['rating']; ?>/5)</span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($feedback['comment'] ?? 'No comment'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($feedback['admin_response'] ?? 'No response'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $feedback['status'] == 'approved' ? 'success' : 
                                                ($feedback['status'] == 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($feedback['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-primary" 
                                                    onclick="respondFeedback(<?php echo $feedback['id']; ?>, '<?php echo htmlspecialchars($feedback['admin_response'] ?? '', ENT_QUOTES); ?>', '<?php echo $feedback['status']; ?>')"
                                                    title="Respond">
                                                <i class="bi bi-reply"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="deleteFeedback(<?php echo $feedback['id']; ?>)"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No feedback found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Feedback pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>&rating=<?php echo $ratingFilter; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&rating=<?php echo $ratingFilter; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>&rating=<?php echo $ratingFilter; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Respond to Feedback Modal -->
<div class="modal fade" id="respondFeedbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Respond to Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="respond_feedback" value="1">
                    <input type="hidden" name="feedback_id" id="respond_feedback_id">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="status_select" class="form-label">Status *</label>
                        <select class="form-select" id="status_select" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_response" class="form-label">Admin Response</label>
                        <textarea class="form-control" id="admin_response" name="admin_response" rows="4" 
                                  placeholder="Enter your response to the customer..."></textarea>
                        <small class="form-text text-muted">This response will be visible to the customer.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Feedback Modal -->
<div class="modal fade" id="deleteFeedbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delete_feedback" value="1">
                    <input type="hidden" name="feedback_id" id="delete_feedback_id">
                    <?php echo csrfField(); ?>
                    <p>Are you sure you want to delete this feedback? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function respondFeedback(feedbackId, adminResponse, status) {
    document.getElementById('respond_feedback_id').value = feedbackId;
    document.getElementById('admin_response').value = adminResponse || '';
    document.getElementById('status_select').value = status;
    
    const modal = new bootstrap.Modal(document.getElementById('respondFeedbackModal'));
    modal.show();
}

function deleteFeedback(feedbackId) {
    document.getElementById('delete_feedback_id').value = feedbackId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteFeedbackModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

