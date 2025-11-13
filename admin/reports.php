<?php
/**
 * Admin - Reports
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$reportType = $_GET['type'] ?? 'sales';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$paymentFilter = $_GET['payment_status'] ?? '';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    $filename = 'tms_report_' . $reportType . '_' . date('Y-m-d') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    try {
        if ($reportType === 'sales') {
            // Sales Summary with Payment Aggregation
            fputcsv($output, ['Sales Report']);
            fputcsv($output, ['Date Range', $startDate . ' to ' . $endDate]);
            fputcsv($output, []);
            
            // Summary
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    COALESCE(SUM(o.total_amount), 0) as total_revenue,
                    COALESCE(SUM(p.amount), 0) as total_payments
                FROM orders o
                LEFT JOIN payments p ON o.id = p.order_id AND DATE(p.paid_at) BETWEEN ? AND ?
                WHERE DATE(o.created_at) BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
            $summary = $stmt->fetch();
            fputcsv($output, ['Summary']);
            fputcsv($output, ['Total Orders', $summary['total_orders']]);
            fputcsv($output, ['Total Revenue', $summary['total_revenue']]);
            fputcsv($output, ['Total Payments', $summary['total_payments']]);
            fputcsv($output, []);
            
            // Sales by Date (GROUP BY)
            fputcsv($output, ['Sales by Date']);
            fputcsv($output, ['Date', 'Orders', 'Revenue']);
            $stmt = $pdo->prepare("
                SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
                FROM orders
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute([$startDate, $endDate]);
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['date'],
                    $row['orders'],
                    $row['revenue']
                ]);
            }
            fputcsv($output, []);
            
            // Payments by Date (GROUP BY)
            fputcsv($output, ['Payments by Date']);
            fputcsv($output, ['Date', 'Payment Count', 'Total Amount']);
            $stmt = $pdo->prepare("
                SELECT DATE(paid_at) as date, COUNT(*) as payment_count, COALESCE(SUM(amount), 0) as total_amount
                FROM payments
                WHERE DATE(paid_at) BETWEEN ? AND ?
                GROUP BY DATE(paid_at)
                ORDER BY date ASC
            ");
            $stmt->execute([$startDate, $endDate]);
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['date'],
                    $row['payment_count'],
                    $row['total_amount']
                ]);
            }
        } elseif ($reportType === 'orders') {
            // Orders Report
            $paymentFilter = $_GET['payment_status'] ?? '';
            fputcsv($output, ['Orders Report']);
            fputcsv($output, ['Date', date('Y-m-d')]);
            fputcsv($output, ['Payment Filter', $paymentFilter ?: 'All']);
            fputcsv($output, []);
            fputcsv($output, ['Order Number', 'Customer', 'Status', 'Total Amount', 'Paid', 'Remaining', 'Delivery Date']);
            
            $whereConditions = [];
            $params = [];
            
            if ($paymentFilter === 'paid') {
                $whereConditions[] = "o.remaining_amount <= 0";
            } elseif ($paymentFilter === 'unpaid') {
                $whereConditions[] = "o.remaining_amount = o.total_amount";
            } elseif ($paymentFilter === 'partial') {
                $whereConditions[] = "o.remaining_amount > 0 AND o.remaining_amount < o.total_amount";
            } else {
                $whereConditions[] = "o.status IN ('pending', 'in-progress')";
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $stmt = $pdo->prepare("
                SELECT o.order_number, c.name as customer_name, o.status, o.total_amount, o.remaining_amount,
                       (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id) as total_paid,
                       o.delivery_date
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                $whereClause
                ORDER BY o.created_at DESC
            ");
            $stmt->execute($params);
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['order_number'],
                    $row['customer_name'],
                    $row['status'],
                    $row['total_amount'],
                    $row['total_paid'],
                    $row['remaining_amount'],
                    $row['delivery_date']
                ]);
            }
        } elseif ($reportType === 'payments') {
            // Payments Report
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            fputcsv($output, ['Payments Report']);
            fputcsv($output, ['Date Range', $startDate . ' to ' . $endDate]);
            fputcsv($output, []);
            fputcsv($output, ['Payment Number', 'Order Number', 'Customer', 'Amount', 'Method', 'Date']);
            
            $stmt = $pdo->prepare("
                SELECT p.payment_number, o.order_number, c.name as customer_name, 
                       p.amount, p.payment_method, p.paid_at
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE DATE(p.paid_at) BETWEEN ? AND ?
                ORDER BY p.paid_at DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['payment_number'],
                    $row['order_number'],
                    $row['customer_name'],
                    $row['amount'],
                    $row['payment_method'],
                    $row['paid_at']
                ]);
            }
        } elseif ($reportType === 'inventory') {
            // Inventory Report
            fputcsv($output, ['Inventory Report']);
            fputcsv($output, ['Date', date('Y-m-d')]);
            fputcsv($output, []);
            fputcsv($output, ['Item Name', 'Type', 'Quantity', 'Low Stock Threshold', 'Price', 'Status', 'Stock Status']);
            
            $stmt = $pdo->query("
                SELECT i.*, 
                       CASE WHEN i.quantity <= i.low_stock_threshold THEN 'Low Stock' ELSE 'In Stock' END as stock_status
                FROM inventory i
                WHERE i.status = 'available'
                ORDER BY 
                    CASE WHEN i.quantity <= i.low_stock_threshold THEN 0 ELSE 1 END,
                    i.quantity ASC
            ");
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['item_name'],
                    $row['type'],
                    $row['quantity'],
                    $row['low_stock_threshold'],
                    $row['price'],
                    $row['status'],
                    $row['stock_status']
                ]);
            }
        } elseif ($reportType === 'expenses') {
            // Expenses Report (Placeholder)
            fputcsv($output, ['Expenses Report']);
            fputcsv($output, ['Date Range', $startDate . ' to ' . $endDate]);
            fputcsv($output, []);
            fputcsv($output, ['Note', 'Expenses tracking will be implemented in a future update.']);
        }
    } catch (PDOException $e) {
        fputcsv($output, ['Error', $e->getMessage()]);
    }
    
    fclose($output);
    exit();
}

// Get report data
$salesSummary = [];
$salesByDate = [];
$paymentsByDate = [];
$topCustomers = [];
$pendingOrders = [];
$payments = [];
$inventoryItems = [];
$lowStockItems = [];

try {
    if ($reportType === 'sales') {
        // Sales Summary - Sum payments by date range
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_revenue,
                COALESCE(SUM(p.amount), 0) as total_payments,
                COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as completed_revenue,
                COALESCE(SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END), 0) as pending_orders,
                COALESCE(SUM(CASE WHEN o.status = 'in-progress' THEN 1 ELSE 0 END), 0) as in_progress_orders
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id AND DATE(p.paid_at) BETWEEN ? AND ?
            WHERE DATE(o.created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
        $salesSummary = $stmt->fetch();
        
        // Sales by date (orders)
        $stmt = $pdo->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        $salesByDate = $stmt->fetchAll();
        
        // Payments by date (GROUP BY)
        $stmt = $pdo->prepare("
            SELECT DATE(paid_at) as date, COUNT(*) as payment_count, COALESCE(SUM(amount), 0) as total_amount
            FROM payments
            WHERE DATE(paid_at) BETWEEN ? AND ?
            GROUP BY DATE(paid_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        $paymentsByDate = $stmt->fetchAll();
        
        // Top customers
        $stmt = $pdo->prepare("
            SELECT c.name, COUNT(o.id) as order_count, COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM customers c
            LEFT JOIN orders o ON c.id = o.customer_id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY c.id, c.name
            HAVING order_count > 0
            ORDER BY total_spent DESC
            LIMIT 10
        ");
        $stmt->execute([$startDate, $endDate]);
        $topCustomers = $stmt->fetchAll();
        
    } elseif ($reportType === 'orders') {
        // Pending Orders with payment filter
        $whereConditions = [];
        $params = [];
        
        if ($paymentFilter === 'paid') {
            $whereConditions[] = "o.remaining_amount <= 0";
        } elseif ($paymentFilter === 'unpaid') {
            $whereConditions[] = "o.remaining_amount = o.total_amount";
        } elseif ($paymentFilter === 'partial') {
            $whereConditions[] = "o.remaining_amount > 0 AND o.remaining_amount < o.total_amount";
        }
        
        // Default: show pending/in-progress orders if no payment filter
        if (empty($paymentFilter)) {
            $whereConditions[] = "o.status IN ('pending', 'in-progress')";
        }
        
        // Add date range filter if provided
        if ($startDate && $endDate && ($startDate != date('Y-m-01') || $endDate != date('Y-m-d'))) {
            $whereConditions[] = "DATE(o.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $stmt = $pdo->prepare("
            SELECT o.*, c.name as customer_name, u.email as customer_email,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                   (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id) as total_paid
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            $whereClause
            ORDER BY o.created_at DESC
        ");
        $stmt->execute($params);
        $pendingOrders = $stmt->fetchAll();
    } elseif ($reportType === 'payments') {
        // Payment Reports - GROUP BY payment method
        $stmt = $pdo->prepare("
            SELECT p.*, o.order_number, c.name as customer_name,
                   DATE(p.paid_at) as payment_date
            FROM payments p
            LEFT JOIN orders o ON p.order_id = o.id
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE DATE(p.paid_at) BETWEEN ? AND ?
            ORDER BY p.paid_at DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $payments = $stmt->fetchAll();
        
        // Payment summary by method
        $stmt = $pdo->prepare("
            SELECT payment_method, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount
            FROM payments
            WHERE DATE(paid_at) BETWEEN ? AND ?
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $paymentsByMethod = $stmt->fetchAll();
        
    } elseif ($reportType === 'inventory') {
        // Inventory Report
        $stmt = $pdo->query("
            SELECT i.*, 
                   CASE WHEN i.quantity <= i.low_stock_threshold THEN 1 ELSE 0 END as is_low_stock
            FROM inventory i
            WHERE i.status = 'available'
            ORDER BY 
                CASE WHEN i.quantity <= i.low_stock_threshold THEN 0 ELSE 1 END,
                i.quantity ASC
        ");
        $inventoryItems = $stmt->fetchAll();
        
        // Low stock items count
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM inventory 
            WHERE quantity <= low_stock_threshold AND status = 'available'
        ");
        $lowStockItems = $stmt->fetch()['count'];
    }
    
} catch (PDOException $e) {
    $error = 'Error generating report: ' . $e->getMessage();
    $salesSummary = [];
    $salesByDate = [];
    $paymentsByDate = [];
    $topCustomers = [];
    $pendingOrders = [];
    $payments = [];
    $paymentsByMethod = [];
    $inventoryItems = [];
    $lowStockItems = 0;
}

$pageTitle = "Reports";
$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text"></i> Reports
            </h1>
        </div>
    </div>
    
    <!-- Report Type Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="type" class="form-label">Report Type</label>
                        <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                            <option value="sales" <?php echo $reportType === 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                            <option value="orders" <?php echo $reportType === 'orders' ? 'selected' : ''; ?>>Pending Orders</option>
                            <option value="payments" <?php echo $reportType === 'payments' ? 'selected' : ''; ?>>Payment Reports</option>
                            <option value="inventory" <?php echo $reportType === 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                            <option value="expenses" <?php echo $reportType === 'expenses' ? 'selected' : ''; ?>>Expenses</option>
                        </select>
                    </div>
                    <?php if ($reportType === 'orders'): ?>
                        <div class="col-md-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status" name="payment_status" onchange="this.form.submit()">
                                <option value="">All Orders</option>
                                <option value="paid" <?php echo $paymentFilter === 'paid' ? 'selected' : ''; ?>>Paid Orders</option>
                                <option value="unpaid" <?php echo $paymentFilter === 'unpaid' ? 'selected' : ''; ?>>Unpaid Orders</option>
                                <option value="partial" <?php echo $paymentFilter === 'partial' ? 'selected' : ''; ?>>Partially Paid</option>
                            </select>
                        </div>
                    <?php endif; ?>
                <?php if (in_array($reportType, ['sales', 'payments', 'expenses'])): ?>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control datepicker" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control datepicker" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Generate
                        </button>
                        <a href="?type=<?php echo $reportType; ?>&export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?><?php echo $paymentFilter ? '&payment_status=' . $paymentFilter : ''; ?>" 
                           class="btn btn-success" target="_blank">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                    </div>
                <?php elseif ($reportType === 'orders'): ?>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date (Optional)</label>
                        <input type="date" class="form-control datepicker" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date (Optional)</label>
                        <input type="date" class="form-control datepicker" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="?type=orders&export=csv<?php echo $paymentFilter ? '&payment_status=' . $paymentFilter : ''; ?><?php echo $startDate ? '&start_date=' . $startDate . '&end_date=' . $endDate : ''; ?>" 
                           class="btn btn-success" target="_blank">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                    </div>
                <?php elseif ($reportType === 'inventory'): ?>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="?type=inventory&export=csv" 
                           class="btn btn-success" target="_blank">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if ($reportType === 'sales'): ?>
        <!-- Sales Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Orders</h6>
                        <h3><?php echo number_format($salesSummary['total_orders'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Revenue</h6>
                        <h3>Rs <?php echo number_format($salesSummary['total_revenue'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Payments</h6>
                        <h3>Rs <?php echo number_format($salesSummary['total_payments'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Pending Orders</h6>
                        <h3><?php echo number_format($salesSummary['pending_orders'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sales Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sales Revenue by Date</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Payments by Date</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sales by Date Table -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sales by Date</h5>
                <button class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="salesTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($salesByDate) > 0): ?>
                                <?php foreach ($salesByDate as $sale): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($sale['date'])); ?></td>
                                        <td><?php echo $sale['orders']; ?></td>
                                        <td>Rs <?php echo number_format($sale['revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No sales data for selected date range.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Top Customers -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top Customers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($topCustomers) > 0): ?>
                                <?php foreach ($topCustomers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo $customer['order_count']; ?></td>
                                        <td>Rs <?php echo number_format($customer['total_spent'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No customer data for selected date range.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'orders'): ?>
        <!-- Orders Report -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo $paymentFilter ? ucfirst($paymentFilter) . ' ' : ''; ?>Orders Report</h5>
                <button class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Paid</th>
                                <th>Remaining</th>
                                <th>Delivery Date</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($pendingOrders) && count($pendingOrders) > 0): ?>
                                <?php foreach ($pendingOrders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] == 'pending' ? 'warning' : 
                                                    ($order['status'] == 'completed' ? 'success' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $order['item_count']; ?></td>
                                        <td>Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>Rs <?php echo number_format($order['total_paid'] ?? 0, 2); ?></td>
                                        <td>
                                            Rs <?php echo number_format($order['remaining_amount'], 2); ?>
                                            <?php if ($order['remaining_amount'] <= 0): ?>
                                                <span class="badge bg-success ms-1">Paid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : 'N/A'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($reportType === 'payments'): ?>
        <!-- Payments Report -->
        <?php if (!isset($payments)) $payments = []; ?>
        <?php if (!isset($paymentsByMethod)) $paymentsByMethod = []; ?>
        
        <!-- Payment Summary by Method -->
        <div class="row mb-4">
            <?php if (count($paymentsByMethod) > 0): ?>
                <?php foreach ($paymentsByMethod as $method): ?>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $method['payment_method'])); ?></h6>
                                <h3>Rs <?php echo number_format($method['total_amount'], 2); ?></h3>
                                <small class="text-muted"><?php echo $method['count']; ?> payments</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payments Report</h5>
                <button class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment #</th>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payments) > 0): ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['customer_name'] ?? 'N/A'); ?></td>
                                        <td>Rs <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($payment['paid_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo baseUrl('api/receipt.php?id=' . $payment['id']); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-receipt"></i> Receipt
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No payments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'inventory'): ?>
        <!-- Inventory Report -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Inventory Report
                    <?php if ($lowStockItems > 0): ?>
                        <span class="badge bg-danger"><?php echo $lowStockItems; ?> Low Stock Items</span>
                    <?php endif; ?>
                </h5>
                <button class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Low Stock Threshold</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Stock Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($inventoryItems) > 0): ?>
                                <?php foreach ($inventoryItems as $item): ?>
                                    <tr class="<?php echo $item['quantity'] <= $item['low_stock_threshold'] ? 'table-danger' : ''; ?>">
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo ucfirst($item['type']); ?></td>
                                        <td>
                                            <strong class="<?php echo $item['quantity'] <= $item['low_stock_threshold'] ? 'text-danger' : ''; ?>">
                                                <?php echo $item['quantity']; ?>
                                            </strong>
                                        </td>
                                        <td><?php echo $item['low_stock_threshold']; ?></td>
                                        <td>Rs <?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $item['status'] === 'available' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($item['quantity'] <= $item['low_stock_threshold']): ?>
                                                <span class="badge bg-danger">Low Stock</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">In Stock</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No inventory items found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'expenses'): ?>
        <!-- Expenses Report (Placeholder) -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Expenses Report</h5>
                <button class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Expenses tracking will be implemented in a future update.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox"></i> No expenses recorded yet.
                                    <br>
                                    <small>This is a placeholder for future expenses tracking functionality.</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Bootstrap Datepicker (Optional - using native date input) -->
<!-- For enhanced date picking, you can use: https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js -->

<style>
@media print {
    .navbar, .card-header button, .btn, nav {
        display: none !important;
    }
    .card {
        border: none;
        box-shadow: none;
    }
}
.table-danger {
    background-color: #f8d7da !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($reportType === 'sales' && count($salesByDate) > 0): ?>
    // Sales Revenue Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const salesData = <?php echo json_encode($salesByDate); ?>;
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue (Rs)',
                    data: salesData.map(item => parseFloat(item.revenue)),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs ' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Payments Chart
    const paymentsCtx = document.getElementById('paymentsChart');
    if (paymentsCtx) {
        const paymentsData = <?php echo json_encode($paymentsByDate); ?>;
        new Chart(paymentsCtx, {
            type: 'bar',
            data: {
                labels: paymentsData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Payments (Rs)',
                    data: paymentsData.map(item => parseFloat(item.total_amount)),
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs ' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

