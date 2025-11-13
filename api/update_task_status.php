<?php
/**
 * Update Task Status API - AJAX Endpoint
 * Tailoring Management System
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in and is staff
if (!isLoggedIn() || !hasRole('staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$staffId = $_SESSION['user_id'];
$taskId = (int)($_POST['task_id'] ?? 0);
$status = sanitize($_POST['status'] ?? '');
$notes = sanitize($_POST['notes'] ?? '');
$updateOrderStatus = isset($_POST['update_order_status']) && $_POST['update_order_status'] == '1';
$orderId = (int)($_POST['order_id'] ?? 0);

if ($taskId <= 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID or status']);
    exit();
}

try {
    if ($pdo === null) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    // Verify task belongs to this staff
    $stmt = $pdo->prepare("SELECT id, order_id FROM staff_tasks WHERE id = ? AND staff_id = ?");
    $stmt->execute([$taskId, $staffId]);
    $task = $stmt->fetch();
    
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task not found or not assigned to you']);
        exit();
    }
    
    $orderId = $task['order_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update task status
        $now = date('Y-m-d H:i:s');
        if ($status === 'in-progress') {
            $stmt = $pdo->prepare("
                UPDATE staff_tasks 
                SET status = ?, started_at = COALESCE(started_at, ?), notes = COALESCE(?, notes)
                WHERE id = ?
            ");
            $stmt->execute([$status, $now, $notes, $taskId]);
        } elseif ($status === 'completed') {
            $stmt = $pdo->prepare("
                UPDATE staff_tasks 
                SET status = ?, completed_at = ?, notes = COALESCE(?, notes)
                WHERE id = ?
            ");
            $stmt->execute([$status, $now, $notes, $taskId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE staff_tasks 
                SET status = ?, notes = COALESCE(?, notes)
                WHERE id = ?
            ");
            $stmt->execute([$status, $notes, $taskId]);
        }
        
        // Update order status if requested
        if ($updateOrderStatus && $orderId > 0) {
            $orderStatus = $status === 'completed' ? 'completed' : ($status === 'in-progress' ? 'in-progress' : 'pending');
            
            // Check if all tasks for this order are completed
            if ($orderStatus === 'completed') {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total, 
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM staff_tasks
                    WHERE order_id = ?
                ");
                $stmt->execute([$orderId]);
                $taskStats = $stmt->fetch();
                
                // Only update order to completed if all tasks are completed
                if ($taskStats['total'] > 0 && $taskStats['completed'] == $taskStats['total']) {
                    $stmt = $pdo->prepare("
                        UPDATE orders 
                        SET status = 'completed', completed_at = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$now, $orderId]);
                } else {
                    // Update to in-progress if not all tasks are completed
                    $stmt = $pdo->prepare("
                        UPDATE orders 
                        SET status = 'in-progress'
                        WHERE id = ? AND status = 'pending'
                    ");
                    $stmt->execute([$orderId]);
                }
            } else {
                // Update order status based on task status
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = ?
                    WHERE id = ? AND status != 'completed'
                ");
                $stmt->execute([$orderStatus, $orderId]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Task status updated successfully'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating task: ' . $e->getMessage()
    ]);
}
?>

