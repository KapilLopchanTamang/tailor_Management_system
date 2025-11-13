<?php
/**
 * Customer Orders - View and Place Orders
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/billing.php';

requireRole('customer');

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';

// Get customer ID
$customerId = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
    $customerId = $customer['id'] ?? null;
    
    if (!$customerId) {
        $error = 'Customer profile not found. Please complete your profile first.';
        $action = 'list';
    }
} catch (PDOException $e) {
    $error = 'Error fetching customer data.';
    $action = 'list';
}

// Handle new order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && $customerId) {
    // Validate CSRF token
    validateCSRF();
    
    $description = sanitize($_POST['description'] ?? '');
    $deliveryDate = sanitize($_POST['delivery_date'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $orderItems = $_POST['order_items'] ?? [];
    
    if (empty($description)) {
        $error = 'Order description is required.';
    } elseif (empty($orderItems) || !is_array($orderItems)) {
        $error = 'Please add at least one item to your order.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Generate order number
            $orderNumber = generateOrderNumber($pdo);
            
            // Calculate total amount from items
            $totalAmount = 0;
            foreach ($orderItems as $item) {
                $itemId = (int)($item['inventory_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);
                $price = (float)($item['price'] ?? 0);
                
                if ($itemId > 0 && $quantity > 0) {
                    $totalAmount += $quantity * $price;
                }
            }
            
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, order_number, description, delivery_date, total_amount, remaining_amount, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $customerId, 
                $orderNumber, 
                $description, 
                $deliveryDate ?: null, 
                $totalAmount, 
                $totalAmount, 
                $notes
            ]);
            $orderId = $pdo->lastInsertId();
            
            // Insert order items and update inventory
            foreach ($orderItems as $item) {
                $itemId = (int)($item['inventory_id'] ?? 0);
                $itemName = sanitize($item['item_name'] ?? '');
                $quantity = (int)($item['quantity'] ?? 0);
                $price = (float)($item['price'] ?? 0);
                
                if ($itemId > 0 && $quantity > 0 && $price > 0) {
                    // Insert order item
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, item_name, quantity, price, subtotal) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $subtotal = $quantity * $price;
                    $stmt->execute([$orderId, $itemName, $quantity, $price, $subtotal]);
                    
                    // Update inventory quantity (if inventory_id is provided and exists)
                    if ($itemId > 0) {
                        $stmt = $pdo->prepare("
                            UPDATE inventory 
                            SET quantity = quantity - ? 
                            WHERE id = ? AND quantity >= ?
                        ");
                        $stmt->execute([$quantity, $itemId, $quantity]);
                        
                        // Check if inventory is low and update status
                        $stmt = $pdo->prepare("
                            UPDATE inventory 
                            SET status = CASE 
                                WHEN quantity <= 0 THEN 'out_of_stock'
                                WHEN quantity <= low_stock_threshold THEN 'available'
                                ELSE status
                            END
                            WHERE id = ?
                        ");
                        $stmt->execute([$itemId]);
                    }
                }
            }
            
            // Update order total manually (stored procedure may not work in all MySQL versions)
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET total_amount = (
                    SELECT COALESCE(SUM(subtotal), 0) 
                    FROM order_items 
                    WHERE order_id = ?
                ),
                remaining_amount = total_amount - (
                    SELECT COALESCE(SUM(amount), 0) 
                    FROM payments 
                    WHERE order_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$orderId, $orderId, $orderId]);
            
            $pdo->commit();
            $message = 'Order placed successfully! Order Number: ' . $orderNumber;
            $action = 'list';
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error placing order: ' . $e->getMessage();
        }
    }
}

// Get orders list
$orders = [];
if ($customerId && $action === 'list') {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                   (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id) as total_paid
            FROM orders o
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$customerId]);
        $orders = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Error fetching orders: ' . $e->getMessage();
    }
}

$pageTitle = $action === 'new' ? "Place New Order" : "My Orders";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-bag"></i> <?php echo $action === 'new' ? 'Place New Order' : 'My Orders'; ?>
                    </h1>
                </div>
                <?php if ($action === 'list'): ?>
                    <div>
                        <a href="<?php echo baseUrl('customer/orders.php?action=new'); ?>" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> New Order
                        </a>
                    </div>
                <?php endif; ?>
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
    
    <?php if ($action === 'new'): ?>
        <!-- New Order Form -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Place New Order</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="newOrderForm">
                    <input type="hidden" name="place_order" value="1">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Order Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Describe your order (e.g., Custom suit, Wedding dress, etc.)" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="delivery_date" class="form-label">Expected Delivery Date</label>
                            <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                  placeholder="Any special instructions or requirements"></textarea>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Order Items</h5>
                    
                    <!-- Item Selection -->
                    <div class="mb-3">
                        <label for="inventory_search" class="form-label">Search Inventory</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="inventory_search" 
                                   placeholder="Search for fabrics, materials...">
                            <button type="button" class="btn btn-outline-secondary" id="searchInventoryBtn">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                    
                    <!-- Inventory Results -->
                    <div id="inventoryResults" class="mb-3"></div>
                    
                    <!-- Selected Items -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Selected Items</h6>
                        </div>
                        <div class="card-body">
                            <div id="selectedItems">
                                <p class="text-muted text-center py-3">No items selected. Search and add items from inventory.</p>
                            </div>
                            <div class="mt-3">
                                <strong>Total Amount: <span id="orderTotal">Rs 0.00</span></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo baseUrl('customer/orders.php'); ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitOrderBtn" disabled>
                            <i class="bi bi-check-circle"></i> Place Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Orders List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Paid</th>
                                    <th>Remaining</th>
                                    <th>Delivery Date</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($order['description'] ?? 'N/A', 0, 50)) . (strlen($order['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] == 'completed' ? 'success' : 
                                                    ($order['status'] == 'pending' ? 'warning' : 
                                                    ($order['status'] == 'cancelled' ? 'danger' : 'info')); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $order['item_count']; ?></td>
                                        <td>Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>Rs <?php echo number_format($order['total_paid'], 2); ?></td>
                                        <td>
                                            Rs <?php echo number_format($order['remaining_amount'], 2); ?>
                                            <?php if ($order['remaining_amount'] <= 0): ?>
                                                <span class="badge bg-success ms-2">Paid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : 'Not set'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo baseUrl('customer/track.php?id=' . $order['id']); ?>" 
                                                   class="btn btn-outline-primary" title="Track">
                                                    <i class="bi bi-geo-alt"></i>
                                                </a>
                                                <a href="<?php echo baseUrl('api/invoice.php?id=' . $order['id']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-outline-secondary" 
                                                   title="View Invoice">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                                <?php if ($order['status'] === 'completed'): ?>
                                                    <a href="<?php echo baseUrl('customer/feedback.php?order_id=' . $order['id']); ?>" 
                                                       class="btn btn-outline-info" title="Feedback">
                                                        <i class="bi bi-star"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">
                        No orders yet. 
                        <a href="<?php echo baseUrl('customer/orders.php?action=new'); ?>" class="text-decoration-none">Place your first order</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>


<script>
let selectedItems = [];
let inventoryData = [];

// Search inventory
document.getElementById('searchInventoryBtn')?.addEventListener('click', searchInventory);
document.getElementById('inventory_search')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchInventory();
    }
});

function searchInventory() {
    const searchTerm = document.getElementById('inventory_search').value;
    const resultsDiv = document.getElementById('inventoryResults');
    
    if (!searchTerm.trim()) {
        resultsDiv.innerHTML = '<div class="alert alert-warning">Please enter a search term.</div>';
        return;
    }
    
    // Fetch inventory from API
    fetch('<?php echo baseUrl('api/inventory.php'); ?>?search=' + encodeURIComponent(searchTerm))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                inventoryData = data.items;
                displayInventoryResults(data.items);
            } else {
                resultsDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML = '<div class="alert alert-danger">Error fetching inventory. Please try again.</div>';
        });
}

function displayInventoryResults(items) {
    const resultsDiv = document.getElementById('inventoryResults');
    
    if (items.length === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-info">No items found.</div>';
        return;
    }
    
    let html = '<div class="row g-3">';
    items.forEach(item => {
        if (item.status === 'available' && item.quantity > 0) {
            html += `
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">${item.item_name}</h6>
                            <p class="card-text small text-muted">${item.type} - ${item.color || 'N/A'}</p>
                            <p class="card-text">
                                <strong>Price:</strong> Rs ${parseFloat(item.price).toFixed(2)}/${item.unit}<br>
                                <strong>Available:</strong> ${parseFloat(item.quantity).toFixed(2)} ${item.unit}
                            </p>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addItem(${item.id}, '${item.item_name.replace(/'/g, "\\'")}', ${item.price}, '${item.unit}')">
                                <i class="bi bi-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    });
    html += '</div>';
    resultsDiv.innerHTML = html;
}

function addItem(inventoryId, itemName, price, unit) {
    // Check if item already exists
    const existingItem = selectedItems.find(item => item.inventory_id === inventoryId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        selectedItems.push({
            inventory_id: inventoryId,
            item_name: itemName,
            price: price,
            unit: unit,
            quantity: 1
        });
    }
    
    updateSelectedItems();
    updateOrderTotal();
}

function removeItem(index) {
    selectedItems.splice(index, 1);
    updateSelectedItems();
    updateOrderTotal();
}

function updateQuantity(index, quantity) {
    if (quantity > 0) {
        selectedItems[index].quantity = parseInt(quantity);
        updateSelectedItems();
        updateOrderTotal();
    }
}

function updateSelectedItems() {
    const selectedDiv = document.getElementById('selectedItems');
    const submitBtn = document.getElementById('submitOrderBtn');
    
    if (selectedItems.length === 0) {
        selectedDiv.innerHTML = '<p class="text-muted text-center py-3">No items selected. Search and add items from inventory.</p>';
        submitBtn.disabled = true;
        return;
    }
    
    submitBtn.disabled = false;
    
    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>Item</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr></thead><tbody>';
    
    selectedItems.forEach((item, index) => {
        const subtotal = item.price * item.quantity;
        html += `
            <tr>
                <td>${item.item_name}</td>
                <td>Rs ${parseFloat(item.price).toFixed(2)}/${item.unit}</td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           value="${item.quantity}" min="1" 
                           onchange="updateQuantity(${index}, this.value)" style="width: 80px;">
                </td>
                <td>Rs ${subtotal.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    selectedDiv.innerHTML = html;
    
    // Update hidden input for form submission
    document.getElementById('orderItemsInput').value = JSON.stringify(selectedItems);
}

function updateOrderTotal() {
    let total = 0;
    selectedItems.forEach(item => {
        total += item.price * item.quantity;
    });
    document.getElementById('orderTotal').textContent = 'Rs ' + total.toFixed(2);
}

// Form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('newOrderForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (selectedItems.length === 0) {
                e.preventDefault();
                alert('Please add at least one item to your order.');
                return false;
            }
            
            // Clear any existing hidden inputs
            const existingInputs = form.querySelectorAll('input[name^="order_items"]');
            existingInputs.forEach(input => input.remove());
            
            // Add hidden inputs for each item
            selectedItems.forEach((item, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `order_items[${index}][inventory_id]`;
                input.value = item.inventory_id;
                form.appendChild(input);
                
                const inputName = document.createElement('input');
                inputName.type = 'hidden';
                inputName.name = `order_items[${index}][item_name]`;
                inputName.value = item.item_name;
                form.appendChild(inputName);
                
                const inputQty = document.createElement('input');
                inputQty.type = 'hidden';
                inputQty.name = `order_items[${index}][quantity]`;
                inputQty.value = item.quantity;
                form.appendChild(inputQty);
                
                const inputPrice = document.createElement('input');
                inputPrice.type = 'hidden';
                inputPrice.name = `order_items[${index}][price]`;
                inputPrice.value = item.price;
                form.appendChild(inputPrice);
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

