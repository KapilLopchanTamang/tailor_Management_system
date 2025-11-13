<?php
/**
 * Admin - Notifications Management
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

requireRole('admin');

$message = '';
$error = '';
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = (int)$_POST['notification_id'];
    $result = markNotificationAsRead($pdo, $notificationId, $_SESSION['user_id']);
    if ($result['success']) {
        $message = 'Notification marked as read.';
    } else {
        $error = $result['message'];
    }
}

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $result = markAllNotificationsAsRead($pdo, $_SESSION['user_id']);
    if ($result['success']) {
        $message = 'All notifications marked as read.';
    } else {
        $error = $result['message'];
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $notificationId = (int)$_POST['notification_id'];
    $result = deleteNotification($pdo, $notificationId, $_SESSION['user_id']);
    if ($result['success']) {
        $message = 'Notification deleted.';
    } else {
        $error = $result['message'];
    }
}

// Get all notifications (admin can see all)
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.username, u.email
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.id
        ORDER BY n.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$perPage, $offset]);
    $notifications = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
    $totalNotifications = $stmt->fetch()['total'];
    $totalPages = ceil($totalNotifications / $perPage);
    
} catch (PDOException $e) {
    $error = 'Error fetching notifications: ' . $e->getMessage();
    $notifications = [];
    $totalPages = 0;
}

$pageTitle = "Notifications Management";
$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-bell"></i> Notifications Management
            </h1>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Notifications</h5>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="mark_all_read" value="1">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-check-all"></i> Mark All as Read
                </button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <tr class="<?php echo $notification['is_read'] ? '' : 'table-primary'; ?>">
                                    <td><?php echo $notification['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($notification['username'] ?? 'N/A'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($notification['email'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($notification['is_read']): ?>
                                            <span class="badge bg-success">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Unread</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (!$notification['is_read']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="mark_read" value="1">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-primary" title="Mark as Read">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                                <input type="hidden" name="delete" value="1">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No notifications found.</td>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

