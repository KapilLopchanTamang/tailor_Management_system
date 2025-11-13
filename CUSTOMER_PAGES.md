# Customer Pages Documentation

## Overview

The customer-facing pages provide a complete interface for customers to manage their orders, profile, measurements, and feedback in the Tailoring Management System.

## Authentication

All customer pages use `requireRole('customer')` from `includes/auth.php` to ensure only customers can access these pages. Users without customer role are automatically redirected to the login page.

## Customer Pages

### 1. Dashboard (`customer/dashboard.php`)

**Features:**
- Welcome message with customer name
- Statistics cards showing:
  - Total Orders
  - In Progress Orders
  - Completed Orders
- Order History table (last 10 orders)
- Quick Actions panel with links to:
  - New Order
  - My Measurements
  - Feedback
  - Order History

**Data:**
- Fetches real-time statistics from database
- Shows order status with color-coded badges
- Displays order number, status, items count, total amount, delivery date
- Links to track individual orders

### 2. Profile (`customer/profile.php`)

**Features:**
- Edit personal information:
  - Full Name (required)
  - Phone Number
  - Address
  - Notes
- Edit measurements (stored as JSON):
  - Bust
  - Waist
  - Hips
  - Shoulder
  - Sleeve Length
  - Shirt Length
  - Pants Length
  - Measurement Notes
- Form validation
- Success/error messages

**Measurements:**
- Stored as JSON in `customers.measurements` field
- Form fields for each measurement type
- Empty values are filtered out before saving
- Measurements are displayed when editing

### 3. Orders (`customer/orders.php`)

**Features:**
- View all orders in a table
- Place new orders with:
  - Order description (required)
  - Expected delivery date
  - Additional notes
  - Multiple items from inventory
- Search inventory via AJAX
- Dynamic total calculation
- Inventory deduction on order placement

**Order Placement:**
1. Customer enters order description
2. Searches and selects items from inventory
3. Adds items to cart with quantities
4. System calculates total automatically
5. On submit:
   - Creates order with unique order number
   - Inserts order items
   - Deducts inventory quantities
   - Updates inventory status (low stock/out of stock)

**JavaScript Features:**
- Real-time inventory search via AJAX
- Dynamic item addition/removal
- Quantity updates
- Automatic total calculation
- Form validation

### 4. Feedback (`customer/feedback.php`)

**Features:**
- Submit feedback for completed orders
- Star rating system (1-5 stars)
- Comment field
- View previous feedback
- Only completed orders without feedback are shown

**Feedback Submission:**
- Select order from dropdown
- Rate order (1-5 stars)
- Add optional comment
- Feedback status (pending/approved/rejected)
- Prevents duplicate feedback for same order

**Feedback Display:**
- List of all previous feedback
- Shows rating with stars
- Displays comment
- Shows feedback status
- Shows submission date

### 5. Track Order (`customer/track.php`)

**Features:**
- Real-time order status tracking
- Order information display
- Order items list
- Payment history
- Staff tasks (if assigned)
- Status timeline visualization
- Auto-refresh every 10 seconds

**Real-time Updates:**
- Polls order status every 10 seconds via AJAX
- Updates order status, amounts, delivery date
- Updates timeline visualization
- Stops polling when page is hidden
- Resumes polling when page is visible

**Timeline:**
- Visual progress indicator
- Shows order status progression:
  - Pending
  - In Progress
  - Completed
  - Delivered
- Color-coded status indicators

## API Endpoints

### 1. Inventory API (`api/inventory.php`)

**Purpose:** AJAX endpoint for inventory search

**Parameters:**
- `search` (GET): Search term for items
- `type` (GET, optional): Filter by type (fabric/material/accessory)

**Response:**
```json
{
    "success": true,
    "items": [
        {
            "id": 1,
            "item_name": "Cotton Fabric",
            "type": "fabric",
            "quantity": 100,
            "unit": "meters",
            "price": 25.00,
            "status": "available"
        }
    ],
    "count": 1
}
```

**Authentication:** Requires logged-in user

### 2. Order Status API (`api/order_status.php`)

**Purpose:** AJAX endpoint for order status polling

**Parameters:**
- `id` (GET): Order ID

**Response:**
```json
{
    "success": true,
    "order": {
        "id": 1,
        "order_number": "ORD-2024-001",
        "status": "in-progress",
        "total_amount": 150.00,
        "total_paid": 50.00,
        "remaining_amount": 100.00,
        "delivery_date": "2024-12-01"
    }
}
```

**Authentication:** Requires logged-in customer (order must belong to customer)

## Security Features

1. **Authentication:** All pages require customer role
2. **Authorization:** Customers can only view their own orders
3. **Input Sanitization:** All inputs are sanitized
4. **Prepared Statements:** All SQL queries use PDO prepared statements
5. **XSS Prevention:** All outputs are escaped with `htmlspecialchars()`
6. **CSRF Protection:** Forms use POST method with validation

## Database Operations

### Order Placement
1. Generate unique order number
2. Calculate total from items
3. Insert order record
4. Insert order items
5. Update inventory quantities
6. Update inventory status
7. Update order totals

### Inventory Deduction
- Checks available quantity before deduction
- Updates inventory status based on remaining quantity
- Sets status to 'out_of_stock' if quantity <= 0
- Maintains low stock threshold

## JavaScript Features

### Inventory Search
- Real-time search via AJAX
- Displays available items only
- Shows item details (name, type, price, quantity)
- Add items to cart

### Order Form
- Dynamic item management
- Quantity updates
- Automatic total calculation
- Form validation
- Prevents submission without items

### Order Tracking
- Polls order status every 10 seconds
- Updates UI dynamically
- Stops polling when page hidden
- Resumes polling when page visible

## URL Structure

- Dashboard: `/TMS/customer/dashboard.php`
- Profile: `/TMS/customer/profile.php`
- Orders: `/TMS/customer/orders.php`
- New Order: `/TMS/customer/orders.php?action=new`
- Feedback: `/TMS/customer/feedback.php`
- Track Order: `/TMS/customer/track.php?id={order_id}`

## User Flow

1. **Customer Login** → Redirected to dashboard
2. **View Orders** → See order history and statistics
3. **Place Order** → Search inventory, add items, submit order
4. **Track Order** → Real-time status updates
5. **Submit Feedback** → Rate and comment on completed orders
6. **Update Profile** → Edit personal info and measurements

## Error Handling

- Database errors are caught and displayed
- Form validation errors are shown
- AJAX errors are handled gracefully
- User-friendly error messages
- Fallback UI when data is unavailable

## Responsive Design

- All pages are mobile-friendly
- Bootstrap 5 responsive components
- Touch-friendly buttons and inputs
- Responsive tables
- Mobile-optimized forms

## Future Enhancements

1. **Order Cancellation** - Allow customers to cancel pending orders
2. **Payment Integration** - Online payment processing
3. **Email Notifications** - Order status updates via email
4. **Order Modifications** - Allow changes to pending orders
5. **Measurement Templates** - Save measurement profiles
6. **Order History Export** - Download order history as PDF/CSV
7. **Wishlist** - Save favorite items
8. **Order Sharing** - Share order details with others

## Testing

To test the customer pages:

1. **Login as Customer:**
   - Email: `customer@tms.com`
   - Password: `customer123`

2. **Test Order Placement:**
   - Go to Orders page
   - Click "New Order"
   - Search for inventory items
   - Add items to cart
   - Submit order

3. **Test Order Tracking:**
   - Go to Orders page
   - Click track icon on an order
   - Verify real-time updates (wait 10 seconds)

4. **Test Feedback:**
   - Complete an order (via admin)
   - Go to Feedback page
   - Select order and submit feedback

5. **Test Profile:**
   - Go to Profile page
   - Update personal information
   - Update measurements
   - Save changes

## Notes

- Inventory search is case-insensitive
- Only available inventory items are shown
- Order numbers are auto-generated
- Measurements are stored as JSON
- Order status polling stops when page is hidden
- Feedback can only be submitted once per order
- Only completed orders are eligible for feedback

