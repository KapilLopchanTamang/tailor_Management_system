<?php
/**
 * Customer Feedback - Submit and View Feedback
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('customer');

$message = '';
$error = '';
$orderId = $_GET['order_id'] ?? 0;

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

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback']) && $customerId) {
    // Validate CSRF token
    validateCSRF();
    
    $orderId = (int)$_POST['order_id'];
    $rating = (int)$_POST['rating'];
    $comment = sanitize($_POST['comment'] ?? '');
    
    if ($orderId <= 0) {
        $error = 'Please select an order.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5.';
    } else {
        try {
            // Check if order belongs to customer
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND customer_id = ? AND status = 'completed'");
            $stmt->execute([$orderId, $customerId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                $error = 'Order not found or not eligible for feedback.';
            } else {
                // Check if feedback already exists
                $stmt = $pdo->prepare("SELECT id FROM feedback WHERE order_id = ? AND customer_id = ?");
                $stmt->execute([$orderId, $customerId]);
                if ($stmt->fetch()) {
                    $error = 'You have already submitted feedback for this order.';
                } else {
                    $pdo->beginTransaction();
                    try {
                        // Insert feedback
                        $stmt = $pdo->prepare("INSERT INTO feedback (customer_id, order_id, rating, comment, status) VALUES (?, ?, ?, ?, 'approved')");
                        $stmt->execute([$customerId, $orderId, $rating, $comment]);
                        
                        // Update average rating in orders table
                        $stmt = $pdo->prepare("
                            UPDATE orders 
                            SET average_rating = (
                                SELECT AVG(rating) 
                                FROM feedback 
                                WHERE order_id = ? AND status = 'approved'
                            )
                            WHERE id = ?
                        ");
                        $stmt->execute([$orderId, $orderId]);
                        
                        $pdo->commit();
                        $message = 'Feedback submitted successfully! Thank you for your feedback.';
                        $orderId = 0; // Reset order ID
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Error submitting feedback: ' . $e->getMessage();
        }
    }
}

// Get completed orders for feedback
$completedOrders = [];
if ($customerId) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.id, o.order_number, o.description, o.created_at,
                   (SELECT id FROM feedback WHERE order_id = o.id AND customer_id = ?) as has_feedback
            FROM orders o
            WHERE o.customer_id = ? AND o.status = 'completed'
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$customerId, $customerId]);
        $completedOrders = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Error fetching orders: ' . $e->getMessage();
    }
}

// Get previous feedback
$previousFeedback = [];
if ($customerId) {
    try {
        $stmt = $pdo->prepare("
            SELECT f.*, o.order_number, o.description
            FROM feedback f
            LEFT JOIN orders o ON f.order_id = o.id
            WHERE f.customer_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$customerId]);
        $previousFeedback = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Ignore error for previous feedback
    }
}

// Get admin_response field (handle case where column doesn't exist yet)
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

$pageTitle = "Feedback";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-chat-dots"></i> Feedback
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
    
    <!-- Submit Feedback Form -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-star"></i> Submit Feedback</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="feedbackForm">
                        <input type="hidden" name="submit_feedback" value="1">
                        <?php echo csrfField(); ?>
                        
                        <div class="mb-3">
                            <label for="order_id" class="form-label">Select Order *</label>
                            <select class="form-select" id="order_id" name="order_id" required>
                                <option value="">-- Select an order --</option>
                                <?php foreach ($completedOrders as $order): ?>
                                    <?php if (!$order['has_feedback']): ?>
                                        <option value="<?php echo $order['id']; ?>" <?php echo $orderId == $order['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($order['order_number']); ?> - 
                                            <?php echo htmlspecialchars(substr($order['description'] ?? 'N/A', 0, 50)); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Only completed orders without feedback are shown.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating *</label>
                            <div class="rating-input">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="rating<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                    <label for="rating<?php echo $i; ?>" class="rating-star">
                                        <i class="bi bi-star-fill"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <small class="form-text text-muted">Select a rating from 1 (poor) to 5 (excellent)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comment" class="form-label">Comment</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" 
                                      placeholder="Share your experience with this order..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Previous Feedback -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> My Feedback</h5>
                </div>
                <div class="card-body">
                    <?php if (count($previousFeedback) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Admin Response</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($previousFeedback as $feedback): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($feedback['order_number']); ?></td>
                                            <td>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $feedback['rating'] ? '-fill text-warning' : ''; ?>"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2">(<?php echo $feedback['rating']; ?>/5)</span>
                                            </td>
                                            <td><?php echo htmlspecialchars($feedback['comment'] ?? 'No comment'); ?></td>
                                            <td>
                                                <?php if (!empty($feedback['admin_response'])): ?>
                                                    <div class="alert alert-info mb-0 p-2">
                                                        <strong>Admin Response:</strong><br>
                                                        <?php echo htmlspecialchars($feedback['admin_response']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No response yet</span>
                                                <?php endif; ?>
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
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No feedback submitted yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-input input[type="radio"]:checked ~ label,
.rating-input label:hover,
.rating-input label:hover ~ label {
    color: #ffc107;
}

.rating-input input[type="radio"]:checked ~ label {
    color: #ffc107;
}
</style>

<script>
// Rating star interaction
document.querySelectorAll('.rating-input input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const rating = parseInt(this.value);
        // Visual feedback is handled by CSS
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

