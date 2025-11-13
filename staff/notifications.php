<?php
/**
 * Staff - Notifications
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

requireRole('staff');

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

// Get staff notifications
$staffId = $_SESSION['user_id'];
try {
    $notifications = getNotifications($pdo, $staffId, $perPage, $offset, false);
    
    // Get total count
    $unreadCount = getUnreadNotificationCount($pdo, $staffId);
    $totalCount = count($notifications);
    
} catch (PDOException $e) {
    $error = 'Error fetching notifications: ' . $e->getMessage();
    $notifications = [];
    $unreadCount = 0;
}

$pageTitle = "My Notifications";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-bell"></i> My Notifications
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
            <h5 class="mb-0">
                Notifications 
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger"><?php echo $unreadCount; ?> unread</span>
                <?php endif; ?>
            </h5>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="mark_all_read" value="1">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-check-all"></i> Mark All as Read
                </button>
            </form>
        </div>
        <div class="card-body">
            <div class="list-group">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <div class="mb-1">
                                    <?php if (!$notification['is_read']): ?>
                                        <i class="bi bi-circle-fill text-primary"></i>
                                    <?php endif; ?>
                                    <strong><?php echo htmlspecialchars($notification['message']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                        </span>
                                    </small>
                                </div>
                                <small><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></small>
                            </div>
                            <div class="d-flex justify-content-end mt-2">
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="mark_read" value="1">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-check"></i> Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                    <input type="hidden" name="delete" value="1">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-bell-slash"></i> No notifications found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

