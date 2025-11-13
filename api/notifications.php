<?php
/**
 * Notifications API - AJAX Endpoint
 * Tailoring Management System
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($pdo === null) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'count':
            // Get unread notification count
            $count = getUnreadNotificationCount($pdo, $userId);
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'list':
            // Get notifications list
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';
            
            $notifications = getNotifications($pdo, $userId, $limit, $offset, $unreadOnly);
            
            // Format notifications for display
            $formatted = [];
            foreach ($notifications as $notification) {
                $formatted[] = [
                    'id' => $notification['id'],
                    'message' => $notification['message'],
                    'type' => $notification['type'],
                    'related_id' => $notification['related_id'],
                    'is_read' => (bool)$notification['is_read'],
                    'created_at' => $notification['created_at'],
                    'time_ago' => timeAgo($notification['created_at'])
                ];
            }
            
            echo json_encode(['success' => true, 'notifications' => $formatted]);
            break;
            
        case 'read':
            // Mark notification as read
            $notificationId = (int)($_POST['notification_id'] ?? 0);
            if ($notificationId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
                exit();
            }
            
            $result = markNotificationAsRead($pdo, $notificationId, $userId);
            echo json_encode($result);
            break;
            
        case 'read_all':
            // Mark all notifications as read
            $result = markAllNotificationsAsRead($pdo, $userId);
            echo json_encode($result);
            break;
            
        case 'delete':
            // Delete notification
            $notificationId = (int)($_POST['notification_id'] ?? 0);
            if ($notificationId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
                exit();
            }
            
            $result = deleteNotification($pdo, $notificationId, $userId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}
?>

