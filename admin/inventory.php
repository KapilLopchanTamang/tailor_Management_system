<?php
/**
 * Admin - Inventory Management
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$itemId = $_GET['id'] ?? 0;
$filter = $_GET['filter'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    validateCSRF();
    
    if (isset($_POST['add_item'])) {
        $itemName = sanitize($_POST['item_name'] ?? '');
        $type = sanitize($_POST['type'] ?? 'fabric');
        $description = sanitize($_POST['description'] ?? '');
        $quantity = (float)($_POST['quantity'] ?? 0);
        $unit = sanitize($_POST['unit'] ?? 'meters');
        $price = (float)($_POST['price'] ?? 0);
        $lowStockThreshold = (float)($_POST['low_stock_threshold'] ?? 10);
        $supplier = sanitize($_POST['supplier'] ?? '');
        $color = sanitize($_POST['color'] ?? '');
        $status = sanitize($_POST['status'] ?? 'available');
        
        if (empty($itemName)) {
            $error = 'Item name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO inventory (item_name, type, description, quantity, unit, price, low_stock_threshold, supplier, color, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$itemName, $type, $description, $quantity, $unit, $price, $lowStockThreshold, $supplier, $color, $status]);
                $message = 'Inventory item added successfully.';
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Error adding item: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['edit_item'])) {
        $itemId = (int)$_POST['item_id'];
        $itemName = sanitize($_POST['item_name'] ?? '');
        $type = sanitize($_POST['type'] ?? 'fabric');
        $description = sanitize($_POST['description'] ?? '');
        $quantity = (float)($_POST['quantity'] ?? 0);
        $unit = sanitize($_POST['unit'] ?? 'meters');
        $price = (float)($_POST['price'] ?? 0);
        $lowStockThreshold = (float)($_POST['low_stock_threshold'] ?? 10);
        $supplier = sanitize($_POST['supplier'] ?? '');
        $color = sanitize($_POST['color'] ?? '');
        $status = sanitize($_POST['status'] ?? 'available');
        
        try {
            $stmt = $pdo->prepare("UPDATE inventory SET item_name = ?, type = ?, description = ?, quantity = ?, unit = ?, price = ?, low_stock_threshold = ?, supplier = ?, color = ?, status = ? WHERE id = ?");
            $stmt->execute([$itemName, $type, $description, $quantity, $unit, $price, $lowStockThreshold, $supplier, $color, $status, $itemId]);
            $message = 'Inventory item updated successfully.';
            $action = 'list';
        } catch (PDOException $e) {
            $error = 'Error updating item: ' . $e->getMessage();
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $itemId > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$itemId]);
        $message = 'Inventory item deleted successfully.';
    } catch (PDOException $e) {
        $error = 'Error deleting item: ' . $e->getMessage();
    }
}

// Get inventory items
$items = [];
$whereClause = '';
$params = [];

if ($filter === 'low_stock') {
    $whereClause = "WHERE quantity <= low_stock_threshold AND status = 'available'";
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inventory $whereClause");
    $stmt->execute($params);
    $totalItems = $stmt->fetch()['total'];
    $totalPages = ceil($totalItems / $perPage);
    
    $stmt = $pdo->prepare("SELECT * FROM inventory $whereClause ORDER BY item_name ASC LIMIT ? OFFSET ?");
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    // Count low stock items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= low_stock_threshold AND status = 'available'");
    $lowStockCount = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    $error = 'Error fetching inventory: ' . $e->getMessage();
}

// Get item for edit
$editItem = null;
if ($action === 'edit' && $itemId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute([$itemId]);
        $editItem = $stmt->fetch();
        if (!$editItem) {
            $error = 'Item not found.';
            $action = 'list';
        }
    } catch (PDOException $e) {
        $error = 'Error fetching item: ' . $e->getMessage();
        $action = 'list';
    }
}

$pageTitle = "Inventory Management";
$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-box-seam"></i> Inventory Management
                    </h1>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="bi bi-plus-circle"></i> Add Item
                    </button>
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
    
    <?php if ($lowStockCount > 0 && $filter !== 'low_stock'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> 
            <strong>Low Stock Alert:</strong> <?php echo $lowStockCount; ?> item(s) are below stock threshold.
            <a href="?filter=low_stock" class="alert-link">View low stock items</a>
        </div>
    <?php endif; ?>
    
    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="<?php echo baseUrl('admin/inventory.php'); ?>" 
                       class="btn btn-<?php echo $filter === '' ? 'primary' : 'outline-primary'; ?>">
                        All Items
                    </a>
                    <a href="<?php echo baseUrl('admin/inventory.php?filter=low_stock'); ?>" 
                       class="btn btn-<?php echo $filter === 'low_stock' ? 'warning' : 'outline-warning'; ?>">
                        Low Stock (<?php echo $lowStockCount; ?>)
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inventory Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="inventoryTable">
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
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
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
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="<?php echo baseUrl('admin/inventory.php?delete=1&id=' . $item['id']); ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this item?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No inventory items found.</td>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $filter ? '&filter=' . $filter : ''; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $filter ? '&filter=' . $filter : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $filter ? '&filter=' . $filter : ''; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_item" value="1">
                    <?php echo csrfField(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="item_name" class="form-label">Item Name *</label>
                            <input type="text" class="form-control" id="item_name" name="item_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Type *</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="fabric">Fabric</option>
                                <option value="material">Material</option>
                                <option value="accessory">Accessory</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="unit" class="form-label">Unit *</label>
                            <input type="text" class="form-control" id="unit" name="unit" value="meters" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label">Price *</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="low_stock_threshold" class="form-label">Low Stock Threshold *</label>
                            <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" step="0.01" min="0" value="10" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available">Available</option>
                                <option value="out_of_stock">Out of Stock</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier" class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="supplier" name="supplier">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_item" value="1">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <?php echo csrfField(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_item_name" class="form-label">Item Name *</label>
                            <input type="text" class="form-control" id="edit_item_name" name="item_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_type" class="form-label">Type *</label>
                            <select class="form-select" id="edit_type" name="type" required>
                                <option value="fabric">Fabric</option>
                                <option value="material">Material</option>
                                <option value="accessory">Accessory</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" step="0.01" min="0" required>
                            <div class="form-text text-danger" id="lowStockWarning" style="display:none;">
                                <i class="bi bi-exclamation-triangle"></i> Quantity is below threshold!
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_unit" class="form-label">Unit *</label>
                            <input type="text" class="form-control" id="edit_unit" name="unit" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_price" class="form-label">Price *</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_low_stock_threshold" class="form-label">Low Stock Threshold *</label>
                            <input type="number" class="form-control" id="edit_low_stock_threshold" name="low_stock_threshold" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Status *</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="available">Available</option>
                                <option value="out_of_stock">Out of Stock</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier" class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="edit_supplier" name="supplier">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="edit_color" name="color">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editItem(item) {
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_item_name').value = item.item_name;
    document.getElementById('edit_type').value = item.type;
    document.getElementById('edit_description').value = item.description || '';
    document.getElementById('edit_quantity').value = item.quantity;
    document.getElementById('edit_unit').value = item.unit;
    document.getElementById('edit_price').value = item.price;
    document.getElementById('edit_low_stock_threshold').value = item.low_stock_threshold;
    document.getElementById('edit_status').value = item.status;
    document.getElementById('edit_supplier').value = item.supplier || '';
    document.getElementById('edit_color').value = item.color || '';
    
    // Check for low stock
    checkLowStock();
    
    const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
    editModal.show();
}

function checkLowStock() {
    const quantity = parseFloat(document.getElementById('edit_quantity').value) || 0;
    const threshold = parseFloat(document.getElementById('edit_low_stock_threshold').value) || 0;
    const warning = document.getElementById('lowStockWarning');
    
    if (quantity <= threshold && quantity > 0) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

// Check low stock on quantity or threshold change
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('edit_quantity');
    const thresholdInput = document.getElementById('edit_low_stock_threshold');
    
    if (quantityInput) {
        quantityInput.addEventListener('input', checkLowStock);
    }
    if (thresholdInput) {
        thresholdInput.addEventListener('input', checkLowStock);
    }
    
    // Check all rows for low stock on page load
    const tableRows = document.querySelectorAll('#inventoryTable tbody tr');
    tableRows.forEach(row => {
        if (row.classList.contains('table-warning')) {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#fff3cd';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

