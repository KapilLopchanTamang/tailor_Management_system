# Admin Dashboard Documentation

## Overview

The Admin Dashboard provides comprehensive management capabilities for the Tailoring Management System. All admin pages require admin role authentication.

## Authentication

All admin pages use `requireRole('admin')` from `includes/auth.php` to ensure only administrators can access these pages. Users without admin role are automatically redirected to the login page.

## Admin Pages

### 1. Dashboard (`admin/dashboard.php`)

**Features:**
- Statistics cards showing:
  - Total Customers
  - Pending Orders
  - Low Inventory Items
  - Total Revenue
- Recent Orders table (last 10 orders)
- Quick Actions panel
- Low Stock Alert (if applicable)

**SQL Queries:**
- Uses JOINs to fetch customer names with orders
- Aggregates data for statistics
- Real-time data from database

### 2. Users Management (`admin/users.php`)

**Features:**
- List all users (admin, staff, customer)
- Filter by role
- Add new users (all roles)
- Edit existing users
- Delete users (with confirmation)
- Pagination (10 users per page)

**CRUD Operations:**
- **Create**: Add user form with username, email, password, role, status
- **Read**: Display users in Bootstrap table
- **Update**: Edit user modal (password optional)
- **Delete**: Delete with JavaScript confirmation

**Security:**
- All inputs sanitized
- Password hashing with bcrypt
- Prepared statements for SQL queries
- Email/username uniqueness validation

### 3. Orders Management (`admin/orders.php`)

**Features:**
- List all orders with customer information
- Search by order number, customer name, or email
- Filter by status (pending, in-progress, completed, delivered, cancelled)
- Update order status
- Assign staff to orders
- Create staff tasks when assigning
- Pagination (15 orders per page)

**SQL Queries:**
- Uses JOINs to get customer information
- Subqueries for item count and total paid
- Complex WHERE clauses for search and filter

**Actions:**
- **View Order**: View order details
- **Update Status**: Change order status via modal
- **Assign Staff**: Assign staff member and create task

### 4. Inventory Management (`admin/inventory.php`)

**Features:**
- List all inventory items
- Filter by low stock items
- Add new inventory items
- Edit existing items
- Delete items (with confirmation)
- Low stock alerts (JS check)
- Visual indicators for low stock (yellow rows)
- Pagination (15 items per page)

**CRUD Operations:**
- **Create**: Add item with all details (name, type, quantity, price, threshold, etc.)
- **Read**: Display items in table with status indicators
- **Update**: Edit item modal with low stock warning
- **Delete**: Delete with confirmation

**Low Stock Detection:**
- JavaScript checks quantity vs threshold
- Visual warning in edit modal
- Yellow table rows for low stock items
- Alert banner on dashboard

### 5. Staff Management (`admin/staff.php`)

**Features:**
- List all staff members with task statistics
- View staff task counts (assigned, in-progress, completed)
- Assign tasks to staff for orders
- Update task status
- View recent tasks (last 50)
- Task details with order and customer information

**Task Assignment:**
- Select staff member
- Select order
- Enter task description
- Set priority (low, medium, high, urgent)
- Set due date (optional)
- Automatically creates staff_tasks record
- Updates order status to 'in-progress'

**SQL Queries:**
- Uses JOINs to get staff, orders, and customer data
- Aggregates task counts per staff
- Groups by staff member for statistics

### 6. Reports (`admin/reports.php`)

**Features:**
- Sales Summary Report
- Pending Orders Report
- Date range filtering (for sales report)
- CSV Export functionality
- Print functionality
- Sales by date breakdown
- Top customers list

**Report Types:**
- **Sales Summary**:
  - Total orders
  - Total revenue
  - Completed revenue
  - Pending orders count
  - Sales by date
  - Top 10 customers

- **Pending Orders**:
  - List of pending/in-progress orders
  - Customer information
  - Order details
  - Status information

**Export Features:**
- CSV export with proper headers
- Print-friendly styling
- Date range filtering
- Real-time data from database

## Navigation

All admin pages include a dedicated admin navigation bar (`includes/admin_nav.php`) with:
- Dashboard
- Users
- Orders
- Inventory
- Staff
- Reports
- Profile dropdown
- Logout

## Security Features

1. **Authentication**: All pages require admin role
2. **Input Sanitization**: All user inputs are sanitized
3. **Prepared Statements**: All SQL queries use PDO prepared statements
4. **Password Hashing**: Bcrypt password hashing
5. **CSRF Protection**: Forms use POST method with validation
6. **SQL Injection Prevention**: Prepared statements with parameter binding
7. **XSS Prevention**: All outputs are escaped with `htmlspecialchars()`

## Bootstrap Modals

All forms use Bootstrap 5 modals for:
- Add User
- Edit User
- Update Order Status
- Assign Staff
- Add Inventory Item
- Edit Inventory Item
- Assign Task
- Update Task Status

## JavaScript Features

1. **Form Validation**: Real-time validation
2. **Confirmations**: Delete confirmations
3. **Low Stock Alerts**: Visual warnings
4. **Modal Management**: Bootstrap modal handling
5. **Password Strength**: Visual indicators
6. **Search/Filter**: Real-time filtering

## Pagination

All list pages include pagination:
- **Users**: 10 per page
- **Orders**: 15 per page
- **Inventory**: 15 per page

Pagination includes:
- Previous/Next buttons
- Page numbers
- Active page highlighting
- Preserves search/filter parameters

## Database Queries

All pages use efficient SQL queries with:
- JOINs for related data
- Subqueries for aggregations
- Indexed columns for performance
- Prepared statements for security

## Responsive Design

All pages are fully responsive:
- Mobile-friendly tables
- Responsive modals
- Collapsible navigation
- Touch-friendly buttons

## URLs

- Dashboard: `/TMS/admin/dashboard.php`
- Users: `/TMS/admin/users.php`
- Orders: `/TMS/admin/orders.php`
- Inventory: `/TMS/admin/inventory.php`
- Staff: `/TMS/admin/staff.php`
- Reports: `/TMS/admin/reports.php`
- Profile: `/TMS/admin/profile.php`

## Usage Examples

### Adding a User
1. Go to Users page
2. Click "Add User" button
3. Fill in the form (username, email, password, role, status)
4. Click "Add User"
5. User is created and appears in the list

### Assigning Staff to Order
1. Go to Orders page
2. Click "Assign Staff" button on an order
3. Select staff member
4. Enter task description
5. Set priority and due date
6. Click "Assign Staff"
7. Task is created and order status updates to 'in-progress'

### Managing Inventory
1. Go to Inventory page
2. View low stock alert (if any)
3. Click "Add Item" to add new inventory
4. Click edit icon to modify item
5. JavaScript alerts if quantity is below threshold
6. Delete items with confirmation

### Generating Reports
1. Go to Reports page
2. Select report type (Sales Summary or Pending Orders)
3. Set date range (for sales report)
4. Click "Generate"
5. View report data
6. Click "Export CSV" to download
7. Click "Print" to print report

## Error Handling

All pages include:
- Error messages in alerts
- Success messages for operations
- Database error handling
- Validation error display
- User-friendly error messages

## Best Practices

1. **Always use prepared statements** - Prevents SQL injection
2. **Sanitize all inputs** - Prevents XSS attacks
3. **Validate on server-side** - Don't rely only on client-side validation
4. **Use transactions** - For multi-step operations
5. **Handle errors gracefully** - Show user-friendly messages
6. **Log errors** - For debugging and monitoring
7. **Use pagination** - For large datasets
8. **Optimize queries** - Use indexes and efficient joins

## Future Enhancements

1. **Advanced Search** - More search options
2. **Bulk Operations** - Bulk delete, update
3. **Export to PDF** - PDF report generation
4. **Email Notifications** - Notify on important events
5. **Audit Logging** - Track all admin actions
6. **Role Permissions** - Fine-grained permissions
7. **Dashboard Widgets** - Customizable dashboard
8. **Charts and Graphs** - Visual data representation

