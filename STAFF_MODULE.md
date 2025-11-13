# Staff Module Documentation

## Overview

The Staff Module provides comprehensive task and order management capabilities for staff members in the Tailoring Management System. All staff pages require staff role authentication.

## Authentication

All staff pages use `requireRole('staff')` from `includes/auth.php` to ensure only staff members can access these pages. Users without staff role are automatically redirected to the login page.

## Staff Pages

### 1. Dashboard (`staff/dashboard.php`)

**Features:**
- Statistics cards showing:
  - Total Tasks
  - Pending Tasks
  - In Progress Tasks
  - Completed Tasks
- Assigned tasks table (last 20 tasks)
- Order progress timeline visualization
- Task details with order information
- Priority-based task sorting

**Timeline:**
- Visual progress indicator for each order
- Shows order status progression:
  - Pending
  - In Progress
  - Completed
  - Delivered
- Color-coded status indicators
- Real-time status updates

**Data:**
- Fetches tasks assigned to the logged-in staff member
- Joins with orders and customers for complete information
- Sorted by priority (urgent → high → medium → low)

### 2. Tasks (`staff/tasks.php`)

**Features:**
- View all assigned tasks
- Claim available tasks (unassigned tasks)
- Update task status via AJAX
- Filter tasks by status (assigned, in-progress, completed)
- Task details view
- Optional drag-drop prioritization (using Sortable.js)

**Task Management:**
- **Claim Task**: Staff can claim unassigned tasks
- **Update Status**: Change task status (assigned → in-progress → completed)
- **Update Order Status**: Option to update order status when task status changes
- **Notes**: Add notes when updating task status

**AJAX Features:**
- Update task status without page refresh
- Real-time status updates
- Modal-based status update form
- Automatic order status update option

**Drag-Drop:**
- Optional task prioritization using Sortable.js
- Visual reordering of tasks
- Currently for display only (can be extended to save order)

### 3. Orders (`staff/orders.php`)

**Features:**
- View assigned orders
- Process individual orders
- View customer measurements
- Update delivery date
- Update order status
- Send notifications to customers
- View order items and details

**Order Processing:**
- **View Measurements**: Display customer measurements (JSON format)
- **Update Delivery Date**: Set or update delivery date
- **Update Order Status**: Change order status (pending → in-progress → completed → delivered)
- **Notify Customer**: Send notifications when updating delivery date or status
- **View Order Items**: See all items in the order
- **View Payment Info**: See total amount, paid amount, remaining amount

**Notifications:**
- Automatically creates notifications when:
  - Delivery date is updated
  - Order status is updated
- Notifications are sent to the customer's user account
- Custom notification messages supported

### 4. Inventory (`staff/inventory.php`)

**Features:**
- View current stock levels
- Search inventory items
- Filter by type (fabric, material, accessory)
- Filter by status (available, out of stock, discontinued)
- Request low stock alerts to admin
- Pagination support

**Low Stock Alerts:**
- Staff can request alerts for low stock items
- Alerts are sent to all admin users
- Custom alert messages supported
- Automatic detection of low stock items (quantity ≤ threshold)

**Inventory View:**
- Table view of all inventory items
- Color-coded status indicators
- Low stock items highlighted (yellow background)
- Item details: name, type, quantity, unit, price, threshold, status

## API Endpoints

### 1. Update Task Status API (`api/update_task_status.php`)

**Purpose:** AJAX endpoint for updating task status

**Method:** POST

**Parameters:**
- `task_id` (required): Task ID
- `status` (required): New status (assigned, in-progress, completed, cancelled)
- `notes` (optional): Task notes
- `update_order_status` (optional): Whether to update order status (1 or 0)
- `order_id` (optional): Order ID (auto-detected if not provided)

**Response:**
```json
{
    "success": true,
    "message": "Task status updated successfully"
}
```

**Features:**
- Verifies task belongs to the staff member
- Updates task status with timestamps
- Optionally updates order status
- Automatically sets `started_at` when status changes to 'in-progress'
- Automatically sets `completed_at` when status changes to 'completed'
- Checks if all tasks are completed before updating order to 'completed'

**Security:**
- Requires staff authentication
- Verifies task ownership
- Uses transactions for data integrity

## Security Features

1. **Authentication:** All pages require staff role
2. **Authorization:** Staff can only view/update their own tasks
3. **Input Sanitization:** All inputs are sanitized
4. **Prepared Statements:** All SQL queries use PDO prepared statements
5. **XSS Prevention:** All outputs are escaped with `htmlspecialchars()`
6. **CSRF Protection:** Forms use POST method with validation

## Database Operations

### Task Management
1. Claim task (assign to staff)
2. Update task status
3. Update task notes
4. Update order status (optional)
5. Create notifications for customers

### Order Processing
1. Update delivery date
2. Update order status
3. Create customer notifications
4. View customer measurements
5. View order items and payments

### Inventory Management
1. View inventory items
2. Search and filter inventory
3. Request low stock alerts
4. Create admin notifications

## JavaScript Features

### AJAX Updates
- Update task status without page refresh
- Real-time status updates
- Form submissions via AJAX
- Error handling and user feedback

### Drag-Drop (Optional)
- Task prioritization using Sortable.js
- Visual reordering
- Can be extended to save order to database

### Modal Forms
- Status update modal
- Low stock alert modal
- Better UX for form interactions

## Notification System

### Notification Types
- `order_update`: Order status or delivery date updates
- `system`: System notifications (low stock alerts)
- `task_assigned`: Task assignment notifications
- `delivery_scheduled`: Delivery date notifications

### Notification Creation
- Automatically created when:
  - Delivery date is updated
  - Order status is updated
  - Low stock alert is requested
- Sent to relevant users (customers or admins)
- Stored in `notifications` table
- Can be marked as read/unread

## URL Structure

- Dashboard: `/TMS/staff/dashboard.php`
- Tasks: `/TMS/staff/tasks.php`
- Tasks (Filtered): `/TMS/staff/tasks.php?status=assigned`
- Task Details: `/TMS/staff/tasks.php?id={task_id}`
- Orders: `/TMS/staff/orders.php`
- Order Details: `/TMS/staff/orders.php?id={order_id}`
- Inventory: `/TMS/staff/inventory.php`

## User Flow

1. **Staff Login** → Redirected to dashboard
2. **View Tasks** → See assigned tasks and available tasks
3. **Claim Task** → Claim unassigned tasks
4. **Update Task Status** → Change task status via AJAX
5. **Process Order** → View order details and measurements
6. **Update Delivery Date** → Set delivery date and notify customer
7. **Update Order Status** → Change order status and notify customer
8. **View Inventory** → Check stock levels
9. **Request Low Stock Alert** → Alert admin about low stock

## Error Handling

- Database errors are caught and displayed
- Form validation errors are shown
- AJAX errors are handled gracefully
- User-friendly error messages
- Transaction rollback on errors

## Responsive Design

- All pages are mobile-friendly
- Bootstrap 5 responsive components
- Touch-friendly buttons and inputs
- Responsive tables
- Mobile-optimized forms

## Future Enhancements

1. **Task Comments** - Add comments to tasks
2. **Task Time Tracking** - Track time spent on tasks
3. **Task Attachments** - Upload files to tasks
4. **Bulk Operations** - Update multiple tasks at once
5. **Task Templates** - Create task templates
6. **Order History** - View order processing history
7. **Inventory Alerts** - Automatic low stock alerts
8. **Performance Metrics** - Track staff performance
9. **Task Dependencies** - Set task dependencies
10. **Calendar View** - View tasks in calendar format

## Testing

To test the staff module:

1. **Login as Staff:**
   - Email: `staff@tms.com`
   - Password: `staff123`

2. **Test Task Management:**
   - Go to Tasks page
   - Claim an available task
   - Update task status via AJAX
   - Verify status updates without page refresh

3. **Test Order Processing:**
   - Go to Orders page
   - Click on an order
   - View customer measurements
   - Update delivery date
   - Update order status
   - Verify notifications are created

4. **Test Inventory:**
   - Go to Inventory page
   - Search for items
   - Filter by type/status
   - Request low stock alert
   - Verify admin receives notification

5. **Test Dashboard:**
   - View statistics
   - Check assigned tasks
   - View order progress timeline
   - Verify real-time updates

## Notes

- Tasks are sorted by priority (urgent → high → medium → low)
- Order status updates are optional when updating task status
- Notifications are automatically created for customers
- Low stock alerts are sent to all active admin users
- Drag-drop prioritization is optional and can be extended
- All AJAX updates include error handling
- Transactions ensure data integrity

