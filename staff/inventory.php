<?php
/**
 * Staff Inventory - View Stock and Request Low Stock Alerts
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('staff');

$message = '';
$error = '';
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Handle low stock alert request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_alert'])) {
    $inventoryId = (int)$_POST['inventory_id'];
    $messageText = sanitize($_POST['message'] ?? '');
    
    try {
        // Get inventory item
        $stmt = $pdo->prepare("SELECT id, item_name, quantity, low_stock_threshold FROM inventory WHERE id = ?");
        $stmt->execute([$inventoryId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            $error = 'Inventory item not found.';
        } else {
            // Get admin user IDs
            $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $admins = $stmt->fetchAll();
            
            if (empty($admins)) {
                $error = 'No admin users found.';
            } else {
                $pdo->beginTransaction();
                
                try {
                    $alertMessage = $messageText ?: "Low stock alert for {$item['item_name']}. Current quantity: {$item['quantity']}, Threshold: {$item['low_stock_threshold']}";
                    
                    // Create notification for each admin
                    foreach ($admins as $admin) {
                        $stmt = $pdo->prepare("
                            INSERT INTO notifications (user_id, message, type, related_id) 
                            VALUES (?, ?, 'system', ?)
                        ");
                        $stmt->execute([$admin['id'], $alertMessage, $inventoryId]);
                    }
                    
                    $pdo->commit();
                    $message = 'Low stock alert requested successfully. Admin has been notified.';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
        }
    } catch (PDOException $e) {
        $error = 'Error requesting alert: ' . $e->getMessage();
    }
}

// Get inventory items
$inventoryItems = [];
$lowStockCount = 0;

try {
    $whereConditions = [];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(item_name LIKE ? OR description LIKE ? OR color LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($typeFilter) {
        $whereConditions[] = "type = ?";
        $params[] = $typeFilter;
    }
    
    if ($statusFilter) {
        $whereConditions[] = "status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get inventory items
    $stmt = $pdo->prepare("
        SELECT * FROM inventory
        $whereClause
        ORDER BY item_name ASC
    ");
    $stmt->execute($params);
    $inventoryItems = $stmt->fetchAll();
    
    // Count low stock items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= low_stock_threshold AND status = 'available'");
    $lowStockCount = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    $error = 'Error fetching inventory: ' . $e->getMessage();
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;
$totalItems = count($inventoryItems);
$totalPages = ceil($totalItems / $perPage);
$inventoryItems = array_slice($inventoryItems, $offset, $perPage);

$pageTitle = "Inventory";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-box-seam"></i> Inventory
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
    
    <?php if ($lowStockCount > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> 
            <strong>Low Stock Alert:</strong> <?php echo $lowStockCount; ?> item(s) are below stock threshold.
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search items...">
                </div>
                
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="fabric" <?php echo $typeFilter === 'fabric' ? 'selected' : ''; ?>>Fabric</option>
                        <option value="material" <?php echo $typeFilter === 'material' ? 'selected' : ''; ?>>Material</option>
                        <option value="accessory" <?php echo $typeFilter === 'accessory' ? 'selected' : ''; ?>>Accessory</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="out_of_stock" <?php echo $statusFilter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        <option value="discontinued" <?php echo $statusFilter === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Inventory Table -->
    <div class="card">
        <div class="card-body">
            <?php if (count($inventoryItems) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Threshold</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventoryItems as $item): ?>
                                <tr class="<?php echo $item['quantity'] <= $item['low_stock_threshold'] && $item['status'] == 'available' ? 'table-warning' : ''; ?>">
                                    <td><?php echo $item['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                        <?php if ($item['color']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($item['color']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo ucfirst($item['type']); ?></span>
                                    </td>
                                    <td>
                                        <span class="<?php echo $item['quantity'] <= $item['low_stock_threshold'] && $item['status'] == 'available' ? 'text-danger fw-bold' : ''; ?>">
                                            <?php echo number_format($item['quantity'], 2); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td>Rs <?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo number_format($item['low_stock_threshold'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $item['status'] == 'available' ? 'success' : 
                                                ($item['status'] == 'out_of_stock' ? 'danger' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['quantity'] <= $item['low_stock_threshold'] && $item['status'] == 'available'): ?>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="requestLowStockAlert(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>')">
                                                <i class="bi bi-bell"></i> Alert Admin
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $typeFilter ? '&type=' . $typeFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $typeFilter ? '&type=' . $typeFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $typeFilter ? '&type=' . $typeFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted text-center py-4">No inventory items found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Low Stock Alert Modal -->
<div class="modal fade" id="lowStockAlertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="lowStockAlertForm">
                <div class="modal-header">
                    <h5 class="modal-title">Request Low Stock Alert</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_alert" value="1">
                    <input type="hidden" name="inventory_id" id="alertInventoryId">
                    
                    <div class="mb-3">
                        <label for="alertItemName" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="alertItemName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="alertMessage" class="form-label">Message (Optional)</label>
                        <textarea class="form-control" id="alertMessage" name="message" rows="3" 
                                  placeholder="Additional message for admin..."></textarea>
                        <small class="form-text text-muted">Leave blank to use default message.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-bell"></i> Send Alert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function requestLowStockAlert(inventoryId, itemName) {
    document.getElementById('alertInventoryId').value = inventoryId;
    document.getElementById('alertItemName').value = itemName;
    document.getElementById('alertMessage').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('lowStockAlertModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

