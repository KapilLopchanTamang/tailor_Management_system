# Billing and Payment System Documentation

## Overview

The Billing and Payment System provides comprehensive invoice generation, payment recording, and receipt management for the Tailoring Management System. The system uses manual payment entry (no real payment gateway) and generates HTML-based invoices and receipts.

## Features

### 1. Invoice Generation
- HTML-based invoice generation
- Displays order details, items, and payment history
- Print-friendly format
- Downloadable as HTML file
- Shows payment status (Paid/Pending)

### 2. Payment Recording
- Record payments for orders
- Support for multiple payment methods:
  - Cash
  - Card
  - Bank Transfer
  - Mobile Payment
  - Cheque
- Transaction ID tracking
- Payment notes
- Automatic remaining amount calculation
- Order status update when fully paid

### 3. Receipt Generation
- HTML-based receipt generation
- Displays payment details
- Print-friendly format
- Downloadable as HTML file
- Shows order information and payment history

### 4. Payment Tracking
- View payment history for orders
- Track total paid vs remaining amount
- Payment status indicators
- Receipt download links

## Files Created

### 1. `includes/billing.php`
**Functions:**
- `generateInvoiceHTML($pdo, $orderId)` - Generate invoice HTML
- `generateReceiptHTML($pdo, $paymentId)` - Generate receipt HTML
- `recordPayment($pdo, $orderId, $amount, $paymentMethod, $transactionId, $notes)` - Record payment
- `isOrderFullyPaid($pdo, $orderId)` - Check if order is fully paid

### 2. `api/invoice.php`
**Purpose:** Generate and display/download invoices
**Parameters:**
- `id` (GET): Order ID
- `download` (GET, optional): Set to '1' to download instead of display

### 3. `api/receipt.php`
**Purpose:** Generate and display/download receipts
**Parameters:**
- `id` (GET): Payment ID
- `download` (GET, optional): Set to '1' to download instead of display

### 4. `api/record_payment.php`
**Purpose:** AJAX endpoint for recording payments
**Method:** POST
**Parameters:**
- `order_id` (required): Order ID
- `amount` (required): Payment amount
- `payment_method` (required): Payment method
- `transaction_id` (optional): Transaction ID
- `notes` (optional): Payment notes

## Integration

### Admin Orders (`admin/orders.php`)
**Features:**
- "Record Payment" button for orders with remaining balance
- Payment modal with form
- Invoice view link
- Payment status indicators
- AJAX payment recording

### Customer Orders (`customer/orders.php`)
**Features:**
- Invoice view link for all orders
- Payment status indicators
- View payment history
- Receipt download links

### Customer Track Order (`customer/track.php`)
**Features:**
- Payment history table
- Receipt download links
- Invoice view and download links

### Admin Reports (`admin/reports.php`)
**Features:**
- Payment status filter (paid, unpaid, partially paid)
- Payment reports
- Unpaid orders filter
- CSV export with payment information

## Payment Flow

1. **Order Created:**
   - Order has `total_amount`
   - `remaining_amount` = `total_amount`
   - `advance_amount` = 0

2. **Payment Recorded:**
   - Admin/Staff records payment via modal
   - Payment inserted into `payments` table
   - `remaining_amount` updated automatically (via trigger or PHP)
   - Payment number generated (PAY-YYYYMMDD-XXXX)

3. **Fully Paid:**
   - When `remaining_amount` <= 0, order is considered paid
   - UI shows "Paid" badge
   - Order status can be updated (but enum doesn't have 'paid' status)

## Database Operations

### Payment Recording
1. Generate unique payment number
2. Insert payment record
3. Update order `remaining_amount`
4. Check if order is fully paid
5. Set `remaining_amount` to 0 if fully paid

### Invoice Generation
1. Fetch order details
2. Fetch order items
3. Fetch payment history
4. Calculate totals
5. Generate HTML invoice

### Receipt Generation
1. Fetch payment details
2. Fetch order details
3. Calculate payment totals
4. Generate HTML receipt

## Security

1. **Authentication:** All API endpoints require authentication
2. **Authorization:** 
   - Customers can only view their own invoices/receipts
   - Only admin/staff can record payments
3. **Input Validation:** All inputs validated and sanitized
4. **Amount Validation:** Payment amount cannot exceed remaining amount
5. **Prepared Statements:** All SQL queries use prepared statements

## JavaScript Validation

### Payment Amount Validation
- Real-time validation on input
- Checks if amount exceeds remaining amount
- Shows error message if invalid
- Prevents form submission if invalid

### Form Validation
- Required fields validated
- Payment method selection required
- Amount must be greater than 0
- Amount cannot exceed remaining amount

## Invoice/Receipt Features

### Invoice Includes:
- Invoice header with company info
- Bill-to customer information
- Invoice details (number, date, status)
- Order description
- Order items table
- Payment history table
- Total amount, paid amount, remaining amount
- Print button

### Receipt Includes:
- Receipt header
- Receipt number and date
- Order number
- Customer information
- Payment details (amount, method)
- Order total and remaining amount
- Payment notes
- Print button

## Payment Status

### Order Payment Status:
- **Paid:** `remaining_amount` <= 0
- **Unpaid:** `remaining_amount` == `total_amount`
- **Partially Paid:** `remaining_amount` > 0 AND < `total_amount`

### UI Indicators:
- Green "Paid" badge when fully paid
- Yellow/Red indicators for unpaid/partially paid
- Payment history table shows all payments
- Remaining amount displayed prominently

## Reports

### Payment Status Filters:
- **All Orders:** Show all orders
- **Paid Orders:** `remaining_amount` <= 0
- **Unpaid Orders:** `remaining_amount` == `total_amount`
- **Partially Paid:** `remaining_amount` > 0 AND < `total_amount`

### Payment Reports:
- List all payments in date range
- Shows payment number, order number, customer, amount, method, date
- Links to receipts
- CSV export functionality

## URL Structure

- Invoice: `/TMS/api/invoice.php?id={order_id}`
- Invoice Download: `/TMS/api/invoice.php?id={order_id}&download=1`
- Receipt: `/TMS/api/receipt.php?id={payment_id}`
- Receipt Download: `/TMS/api/receipt.php?id={payment_id}&download=1`
- Record Payment API: `/TMS/api/record_payment.php` (POST)

## Usage Examples

### Recording a Payment (Admin/Staff)
1. Go to Orders page
2. Click "Record Payment" button on an order
3. Enter payment amount
4. Select payment method
5. Enter transaction ID (optional)
6. Add notes (optional)
7. Click "Record Payment"
8. Payment is recorded and receipt can be viewed

### Viewing Invoice (Customer/Admin)
1. Go to Orders page
2. Click "View Invoice" button
3. Invoice opens in new tab
4. Can print or download invoice
5. Shows all order details and payment history

### Viewing Receipt (Customer/Admin)
1. Go to Payment History
2. Click "Receipt" button
3. Receipt opens in new tab
4. Can print or download receipt
5. Shows payment details

### Filtering Unpaid Orders (Admin)
1. Go to Reports page
2. Select "Pending Orders" report type
3. Select "Unpaid Orders" from payment status filter
4. View all unpaid orders
5. Export to CSV if needed

## Testing

To test the billing system:

1. **Record Payment:**
   - Login as admin
   - Go to Orders page
   - Click "Record Payment" on an order
   - Enter amount and payment method
   - Submit payment
   - Verify payment is recorded
   - Verify remaining amount is updated
   - Verify "Paid" badge appears when fully paid

2. **View Invoice:**
   - Login as customer or admin
   - Go to Orders page
   - Click "View Invoice" button
   - Verify invoice displays correctly
   - Verify all order items are shown
   - Verify payment history is shown
   - Test print functionality

3. **View Receipt:**
   - After recording a payment
   - Click "Receipt" button
   - Verify receipt displays correctly
   - Verify payment details are correct
   - Test print functionality

4. **Filter Unpaid Orders:**
   - Login as admin
   - Go to Reports page
   - Select "Pending Orders" report
   - Select "Unpaid Orders" filter
   - Verify only unpaid orders are shown
   - Test CSV export

## Notes

- Payment amounts are validated to not exceed remaining amount
- Orders are considered paid when `remaining_amount` <= 0
- Payment numbers are auto-generated (PAY-YYYYMMDD-XXXX)
- Invoices and receipts are HTML-based (no PDF library required)
- Print functionality uses browser's print dialog
- Download functionality saves as HTML file
- Payment recording is restricted to admin/staff only
- Customers can view invoices and receipts for their orders
- All payment operations use transactions for data integrity
- Remaining amount is automatically calculated after each payment

## Future Enhancements

1. **PDF Generation:** Use library like TCPDF or FPDF for PDF invoices/receipts
2. **Email Integration:** Send invoices/receipts via email
3. **Payment Gateway Integration:** Integrate with real payment gateways
4. **Payment Reminders:** Automatically send payment reminders
5. **Payment Plans:** Support for installment payments
6. **Refund Management:** Handle refunds and cancellations
7. **Payment Reports:** More detailed payment analytics
8. **Tax Calculations:** Add tax calculations to invoices
9. **Multi-currency Support:** Support for different currencies
10. **Payment Approval Workflow:** Multi-step payment approval process

