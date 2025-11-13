<?php
/**
 * Billing Functions
 * Tailoring Management System
 */

require_once __DIR__ . '/functions.php';

/**
 * Generate invoice HTML for an order
 */
function generateInvoiceHTML($pdo, $orderId) {
    try {
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
                   u.email as customer_email
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            return false;
        }
        
        // Get order items
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll();
        
        // Get payments
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY paid_at DESC");
        $stmt->execute([$orderId]);
        $payments = $stmt->fetchAll();
        
        $totalPaid = 0;
        foreach ($payments as $payment) {
            $totalPaid += $payment['amount'];
        }
        
        $remainingAmount = $order['total_amount'] - $totalPaid;
        
        // Generate HTML
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Invoice - <?php echo htmlspecialchars($order['order_number']); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                .invoice-header {
                    border-bottom: 2px solid #333;
                    padding-bottom: 20px;
                    margin-bottom: 20px;
                }
                .invoice-header h1 {
                    margin: 0;
                    color: #333;
                }
                .invoice-info {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 30px;
                }
                .invoice-info div {
                    flex: 1;
                }
                .invoice-info h3 {
                    margin-top: 0;
                    color: #666;
                    font-size: 14px;
                    text-transform: uppercase;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                table th, table td {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                table th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }
                .text-right {
                    text-align: right;
                }
                .total-row {
                    font-weight: bold;
                    font-size: 16px;
                }
                .payment-section {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 2px solid #333;
                }
                .payment-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 5px 0;
                }
                .payment-total {
                    font-weight: bold;
                    font-size: 18px;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 2px solid #333;
                }
                .status-badge {
                    display: inline-block;
                    padding: 5px 10px;
                    border-radius: 3px;
                    font-weight: bold;
                }
                .status-paid {
                    background-color: #28a745;
                    color: white;
                }
                .status-pending {
                    background-color: #ffc107;
                    color: #333;
                }
                @media print {
                    body {
                        margin: 0;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="invoice-header">
                <h1>INVOICE</h1>
                <p>Tailoring Management System</p>
            </div>
            
            <div class="invoice-info">
                <div>
                    <h3>Bill To:</h3>
                    <p>
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                        <?php if ($order['customer_address']): ?>
                            <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?><br>
                        <?php endif; ?>
                        <?php if ($order['customer_phone']): ?>
                            Phone: <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                        <?php endif; ?>
                        <?php if ($order['customer_email']): ?>
                            Email: <?php echo htmlspecialchars($order['customer_email']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-right">
                    <h3>Invoice Details:</h3>
                    <p>
                        <strong>Invoice #:</strong> <?php echo htmlspecialchars($order['order_number']); ?><br>
                        <strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['created_at'])); ?><br>
                        <strong>Status:</strong> 
                        <span class="status-badge <?php echo $remainingAmount <= 0 ? 'status-paid' : 'status-pending'; ?>">
                            <?php echo $remainingAmount <= 0 ? 'PAID' : 'PENDING'; ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <?php if ($order['description']): ?>
                <div style="margin-bottom: 20px;">
                    <strong>Order Description:</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['description'])); ?>
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                            <td class="text-right"><?php echo $item['quantity']; ?></td>
                            <td class="text-right">Rs <?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-right">Rs <?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" class="text-right">Total Amount:</td>
                        <td class="text-right">Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <?php if (count($payments) > 0): ?>
                <div class="payment-section">
                    <h3>Payment History</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Payment #</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($payment['paid_at'])); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td class="text-right">Rs <?php echo number_format($payment['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Total Paid:</strong></td>
                                <td class="text-right"><strong>Rs <?php echo number_format($totalPaid, 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Remaining:</strong></td>
                                <td class="text-right"><strong>Rs <?php echo number_format($remainingAmount, 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="no-print" style="margin-top: 30px; text-align: center;">
                <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
                    Print Invoice
                </button>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Generate receipt HTML for a payment
 */
function generateReceiptHTML($pdo, $paymentId) {
    try {
        // Get payment details
        $stmt = $pdo->prepare("
            SELECT p.*, o.order_number, o.total_amount, o.description as order_description,
                   c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
                   u.email as customer_email
            FROM payments p
            LEFT JOIN orders o ON p.order_id = o.id
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            return false;
        }
        
        // Get total paid for this order
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE order_id = ?");
        $stmt->execute([$payment['order_id']]);
        $totalPaid = $stmt->fetch()['total_paid'];
        $remainingAmount = $payment['total_amount'] - $totalPaid;
        
        // Generate HTML
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Receipt - <?php echo htmlspecialchars($payment['payment_number']); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: 20px auto;
                    padding: 20px;
                    color: #333;
                }
                .receipt-header {
                    text-align: center;
                    border-bottom: 2px solid #333;
                    padding-bottom: 20px;
                    margin-bottom: 20px;
                }
                .receipt-header h1 {
                    margin: 0;
                    color: #333;
                }
                .receipt-info {
                    margin-bottom: 30px;
                }
                .receipt-info p {
                    margin: 5px 0;
                }
                .receipt-details {
                    background-color: #f5f5f5;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .receipt-details table {
                    width: 100%;
                }
                .receipt-details td {
                    padding: 5px 0;
                }
                .receipt-details td:first-child {
                    font-weight: bold;
                    width: 40%;
                }
                .thank-you {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 2px solid #333;
                    font-style: italic;
                }
                @media print {
                    body {
                        margin: 0;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="receipt-header">
                <h1>PAYMENT RECEIPT</h1>
                <p>Tailoring Management System</p>
            </div>
            
            <div class="receipt-info">
                <p><strong>Receipt #:</strong> <?php echo htmlspecialchars($payment['payment_number']); ?></p>
                <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($payment['paid_at'])); ?></p>
                <p><strong>Order #:</strong> <?php echo htmlspecialchars($payment['order_number']); ?></p>
            </div>
            
            <div class="receipt-details">
                <table>
                    <tr>
                        <td>Customer:</td>
                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <td>Payment Method:</td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                    </tr>
                    <tr>
                        <td>Amount Paid:</td>
                        <td><strong>Rs <?php echo number_format($payment['amount'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Order Total:</td>
                        <td>Rs <?php echo number_format($payment['total_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Total Paid:</td>
                        <td>Rs <?php echo number_format($totalPaid, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Remaining:</td>
                        <td>Rs <?php echo number_format($remainingAmount, 2); ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if ($payment['notes']): ?>
                <div style="margin-bottom: 20px;">
                    <strong>Notes:</strong><br>
                    <?php echo nl2br(htmlspecialchars($payment['notes'])); ?>
                </div>
            <?php endif; ?>
            
            <div class="thank-you">
                <p>Thank you for your payment!</p>
            </div>
            
            <div class="no-print" style="margin-top: 30px; text-align: center;">
                <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
                    Print Receipt
                </button>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Record a payment
 */
function recordPayment($pdo, $orderId, $amount, $paymentMethod, $transactionId = null, $notes = null) {
    try {
        $pdo->beginTransaction();
        
        // Generate payment number
        $paymentNumber = generatePaymentNumber($pdo);
        
        // Insert payment
        $stmt = $pdo->prepare("
            INSERT INTO payments (order_id, payment_number, amount, payment_method, transaction_id, notes) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$orderId, $paymentNumber, $amount, $paymentMethod, $transactionId, $notes]);
        $paymentId = $pdo->lastInsertId();
        
        // Update order remaining amount (trigger should handle this, but we'll update it manually too)
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET remaining_amount = total_amount - (
                SELECT COALESCE(SUM(amount), 0) 
                FROM payments 
                WHERE order_id = ?
            )
            WHERE id = ?
        ");
        $stmt->execute([$orderId, $orderId]);
        
        // Check if order is fully paid and update status if needed
        $stmt = $pdo->prepare("SELECT total_amount, remaining_amount FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        // If fully paid, we can add a note or update status (but status enum doesn't have 'paid')
        // So we'll just ensure remaining_amount is 0
        if ($order && $order['remaining_amount'] <= 0) {
            // Order is fully paid - you might want to update status or add a note
            // For now, we'll just ensure the remaining_amount is set to 0
            $stmt = $pdo->prepare("UPDATE orders SET remaining_amount = 0 WHERE id = ?");
            $stmt->execute([$orderId]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'payment_id' => $paymentId,
            'payment_number' => $paymentNumber,
            'message' => 'Payment recorded successfully'
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'message' => 'Error recording payment: ' . $e->getMessage()
        ];
    }
}

/**
 * Check if order is fully paid
 */
function isOrderFullyPaid($pdo, $orderId) {
    try {
        $stmt = $pdo->prepare("SELECT remaining_amount FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        return $order && $order['remaining_amount'] <= 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>

