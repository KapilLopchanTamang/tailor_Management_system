<?php
/**
 * Inventory API - AJAX Endpoint
 * Tailoring Management System
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';

try {
    if ($pdo === null) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    $whereConditions = ["status = 'available'"];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(item_name LIKE ? OR description LIKE ? OR color LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($type) {
        $whereConditions[] = "type = ?";
        $params[] = $type;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    $stmt = $pdo->prepare("
        SELECT id, item_name, type, description, quantity, unit, price, color, status
        FROM inventory
        $whereClause
        ORDER BY item_name ASC
        LIMIT 50
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching inventory: ' . $e->getMessage()
    ]);
}
?>

