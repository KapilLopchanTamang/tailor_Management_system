# Feedback System and Global Search Guide

## Overview
This guide explains the feedback system enhancements and global search functionality added to the Tailoring Management System.

## Feedback System Enhancements

### Database Changes

1. **Orders Table - Average Rating Field**
   - Added `average_rating` DECIMAL(3,2) field to store average rating from customer feedback
   - Automatically calculated and updated when feedback is submitted or modified
   - Range: 1.00 to 5.00

2. **Feedback Table - Admin Response Field**
   - Added `admin_response` TEXT field to store admin responses to customer feedback
   - Allows admins to respond directly to customer feedback

### Customer Feedback Features

1. **Submit Feedback** (`customer/feedback.php`)
   - Customers can submit feedback for completed orders
   - Rating system: 1-5 stars
   - Optional comments
   - Automatically updates order's average rating
   - Status set to 'approved' by default

2. **View Feedback History**
   - Customers can view all their submitted feedback
   - Shows admin responses if available
   - Displays feedback status (pending/approved/rejected)

### Admin Feedback Management (`admin/feedback.php`)

1. **View All Feedback**
   - List all customer feedback with filters (status, rating)
   - Statistics dashboard (total, pending, approved, average rating)
   - Pagination support

2. **Respond to Feedback**
   - Admin can add responses to customer feedback
   - Update feedback status (pending/approved/rejected)
   - Responses are visible to customers

3. **Delete Feedback**
   - Admin can delete feedback if needed
   - Automatically updates order's average rating after deletion

## Global Search System

### Features

1. **Search Bar in Navbar**
   - Available in all user role navbars (admin, staff, customer)
   - Real-time search with 300ms debounce
   - Bootstrap dropdown with results

2. **Search Scope by Role**

   **Admin:**
   - Orders (all orders)
   - Customers (name, email, phone)
   - Inventory (item name, type)

   **Staff:**
   - Orders (assigned orders only)
   - Inventory (item name, type)

   **Customer:**
   - Orders (own orders only)

3. **Search Results**
   - Grouped by type (Orders, Customers, Inventory)
   - Clickable results that navigate to relevant pages
   - Status badges and relevant information
   - Maximum 10 results per category

### API Endpoint

**`api/search.php`**
- Method: GET
- Parameters:
  - `q` (required): Search query (minimum 2 characters)
  - `type` (optional): Filter by type (all, orders, customers, inventory)
- Returns: JSON response with search results

### JavaScript Implementation

**`assets/js/search.js`**
- Handles search input events
- Debounced search (300ms delay)
- AJAX calls to search API
- Displays results in dropdown
- Handles click outside to close dropdown
- Escape key to close dropdown

## Installation

### 1. Run Database Migration

```sql
-- Run the database_updates_feedback_search.sql file
SOURCE database_updates_feedback_search.sql;
```

Or manually execute the SQL statements to add:
- `average_rating` column to `orders` table
- `admin_response` column to `feedback` table
- Index on `average_rating` column

### 2. Verify Files

Ensure these files are in place:
- `admin/feedback.php` - Admin feedback management
- `api/search.php` - Search API endpoint
- `assets/js/search.js` - Search JavaScript
- Updated `customer/feedback.php` - Enhanced feedback submission
- Updated `includes/header.php` - Search bar for customer/staff
- Updated `includes/admin_nav.php` - Search bar for admin
- Updated `includes/footer.php` - Search JavaScript inclusion

### 3. Test the System

1. **Feedback System:**
   - Login as customer
   - Complete an order
   - Submit feedback for the order
   - Check that average rating is updated in orders table
   - Login as admin
   - View feedback in admin/feedback.php
   - Respond to feedback
   - Verify customer can see admin response

2. **Global Search:**
   - Login as any user role
   - Type in search bar (minimum 2 characters)
   - Verify results appear in dropdown
   - Click on a result to navigate
   - Test search for orders, customers (admin only), inventory

## Usage

### For Customers

1. Navigate to "Feedback" from dashboard
2. Select a completed order
3. Rate the order (1-5 stars)
4. Add optional comment
5. Submit feedback
6. View admin responses in feedback history

### For Admins

1. Navigate to "Feedback" from admin navigation
2. View all feedback with filters
3. Click "Respond" button to add response
4. Update feedback status if needed
5. Delete feedback if necessary

### For All Users

1. Use search bar in navbar
2. Type at least 2 characters
3. View results in dropdown
4. Click on result to navigate

## Technical Notes

### Average Rating Calculation

The average rating is automatically calculated using:
```sql
AVG(rating) FROM feedback WHERE order_id = ? AND status = 'approved'
```

This ensures only approved feedback is included in the average.

### Search Performance

- Search uses LIKE queries with indexes
- Results limited to 10 per category
- Debounced to reduce server load
- Cached results not implemented (can be added for production)

### Security

- All user inputs are sanitized
- Role-based access control for search results
- Prepared statements prevent SQL injection
- XSS prevention in search results display

## Troubleshooting

### Search not working
- Check browser console for JavaScript errors
- Verify `api/search.php` is accessible
- Check that user is logged in
- Verify database connection

### Feedback not updating rating
- Check database migration was run
- Verify `average_rating` column exists in orders table
- Check PHP error logs for SQL errors
- Verify feedback status is 'approved'

### Admin response not showing
- Check `admin_response` column exists in feedback table
- Verify admin_response is being saved in database
- Check customer feedback query includes admin_response field

## Future Enhancements

1. Email notifications for admin responses
2. Feedback analytics and charts
3. Search result caching
4. Advanced search filters
5. Feedback export functionality
6. Rating trends over time

