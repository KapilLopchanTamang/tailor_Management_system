<?php
/**
 * Notifications Functions
 * Tailoring Management System
 */

require_once __DIR__ . '/functions.php';

/**
 * Send in-app notification
 */
function sendNotification($pdo, $userId, $message, $type = 'system', $relatedId = null, $sendEmail = false) {
    try {
        if ($pdo === null) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        // Insert notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, type, related_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $message, $type, $relatedId]);
        $notificationId = $pdo->lastInsertId();
        
        // Send email if requested
        if ($sendEmail) {
            $emailSent = sendNotificationEmail($pdo, $userId, $message, $type);
        }
        
        return [
            'success' => true,
            'notification_id' => $notificationId,
            'message' => 'Notification sent successfully'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error sending notification: ' . $e->getMessage()
        ];
    }
}

/**
 * Send notification to multiple users
 */
function sendNotificationToUsers($pdo, $userIds, $message, $type = 'system', $relatedId = null, $sendEmail = false) {
    $results = [];
    foreach ($userIds as $userId) {
        $results[] = sendNotification($pdo, $userId, $message, $type, $relatedId, $sendEmail);
    }
    return $results;
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount($pdo, $userId) {
    try {
        if ($pdo === null) {
            return 0;
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get notifications for a user
 */
function getNotifications($pdo, $userId, $limit = 20, $offset = 0, $unreadOnly = false) {
    try {
        if ($pdo === null) {
            return [];
        }
        
        $whereClause = "WHERE user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $whereClause .= " AND is_read = 0";
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            $whereClause
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($pdo, $notificationId, $userId) {
    try {
        if ($pdo === null) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        // Verify notification belongs to user
        $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Notification not found'];
        }
        
        // Mark as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
        $stmt->execute([$notificationId]);
        
        return ['success' => true, 'message' => 'Notification marked as read'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsAsRead($pdo, $userId) {
    try {
        if ($pdo === null) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        
        return ['success' => true, 'message' => 'All notifications marked as read'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Delete notification
 */
function deleteNotification($pdo, $notificationId, $userId) {
    try {
        if ($pdo === null) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        // Verify notification belongs to user
        $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Notification not found'];
        }
        
        // Delete notification
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$notificationId]);
        
        return ['success' => true, 'message' => 'Notification deleted'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Send notification email (optional)
 */
function sendNotificationEmail($pdo, $userId, $message, $type = 'system') {
    try {
        // Get user email
        $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || empty($user['email'])) {
            return false;
        }
        
        $subject = 'TMS Notification: ' . ucfirst(str_replace('_', ' ', $type));
        $headers = "From: TMS System <noreply@tms.com>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Tailoring Management System</h2>
                </div>
                <div class='content'>
                    <p>Hello " . htmlspecialchars($user['username']) . ",</p>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                <div class='footer'>
                    <p>This is an automated notification from TMS.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return mail($user['email'], $subject, $emailBody, $headers);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Notify customer about order status update
 */
function notifyOrderStatusUpdate($pdo, $orderId, $newStatus, $sendEmail = false) {
    try {
        // Get order and customer info
        $stmt = $pdo->prepare("
            SELECT o.*, c.user_id as customer_user_id, c.name as customer_name
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order || !$order['customer_user_id']) {
            return ['success' => false, 'message' => 'Order or customer not found'];
        }
        
        $statusMessages = [
            'pending' => 'Your order has been placed and is pending.',
            'in-progress' => 'Your order is now in progress.',
            'completed' => 'Your order has been completed!',
            'delivered' => 'Your order has been delivered.',
            'cancelled' => 'Your order has been cancelled.'
        ];
        
        $message = "Order #" . $order['order_number'] . ": " . ($statusMessages[$newStatus] ?? 'Status updated to ' . $newStatus);
        
        return sendNotification(
            $pdo,
            $order['customer_user_id'],
            $message,
            'order_update',
            $orderId,
            $sendEmail
        );
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Notify customer about delivery date
 */
function notifyDeliveryScheduled($pdo, $orderId, $deliveryDate, $sendEmail = false) {
    try {
        // Get order and customer info
        $stmt = $pdo->prepare("
            SELECT o.*, c.user_id as customer_user_id, c.name as customer_name
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order || !$order['customer_user_id']) {
            return ['success' => false, 'message' => 'Order or customer not found'];
        }
        
        $message = "Delivery date scheduled for Order #" . $order['order_number'] . ": " . date('M d, Y', strtotime($deliveryDate));
        
        return sendNotification(
            $pdo,
            $order['customer_user_id'],
            $message,
            'delivery_scheduled',
            $orderId,
            $sendEmail
        );
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Notify staff about task assignment
 */
function notifyTaskAssignment($pdo, $taskId, $staffId, $sendEmail = false) {
    try {
        // Get task and order info
        $stmt = $pdo->prepare("
            SELECT st.*, o.order_number
            FROM staff_tasks st
            LEFT JOIN orders o ON st.order_id = o.id
            WHERE st.id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }
        
        $message = "New task assigned for Order #" . $task['order_number'] . ": " . $task['task_description'];
        
        return sendNotification(
            $pdo,
            $staffId,
            $message,
            'task_assigned',
            $taskId,
            $sendEmail
        );
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Notify customer about payment received
 */
function notifyPaymentReceived($pdo, $paymentId, $sendEmail = false) {
    try {
        // Get payment and order info
        $stmt = $pdo->prepare("
            SELECT p.*, o.order_number, o.customer_id, c.user_id as customer_user_id
            FROM payments p
            LEFT JOIN orders o ON p.order_id = o.id
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment || !$payment['customer_user_id']) {
            return ['success' => false, 'message' => 'Payment or customer not found'];
        }
        
        $message = "Payment of Rs " . number_format($payment['amount'], 2) . " received for Order #" . $payment['order_number'];
        
        return sendNotification(
            $pdo,
            $payment['customer_user_id'],
            $message,
            'payment_received',
            $payment['order_id'],
            $sendEmail
        );
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
?>

