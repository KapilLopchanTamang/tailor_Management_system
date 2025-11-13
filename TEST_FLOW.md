# Test Flow Documentation
## Tailoring Management System

## 🔄 Complete User Flow Test

### Test Scenario: Complete Order Lifecycle

#### 1. Customer Registration
**Steps:**
1. Navigate to `http://localhost/TMS/register.php`
2. Fill in registration form:
   - Name: Test Customer
   - Email: testcustomer@example.com
   - Phone: 1234567890
   - Password: test123
   - Confirm Password: test123
3. Submit form
4. **Expected**: Redirect to login page with success message

**Verify:**
- ✅ Customer created in `users` table (role='customer')
- ✅ Customer record created in `customers` table
- ✅ Password is hashed
- ✅ CSRF token validated
- ✅ Form validation works

#### 2. Customer Login
**Steps:**
1. Navigate to `http://localhost/TMS/login.php`
2. Enter credentials:
   - Email: testcustomer@example.com
   - Password: test123
3. Submit form
4. **Expected**: Redirect to `customer/dashboard.php`

**Verify:**
- ✅ Session created
- ✅ User redirected to customer dashboard
- ✅ Navbar shows customer menu
- ✅ Dashboard displays customer statistics

#### 3. Customer Places Order
**Steps:**
1. Navigate to `customer/orders.php`
2. Click "Place New Order"
3. Fill in order form:
   - Description: Custom Suit - Navy Blue
   - Delivery Date: (future date)
   - Notes: Customer requested navy blue suit
4. Add items from inventory:
   - Select "Wool Fabric - Navy Blue" (quantity: 2 meters)
   - Select "Thread - Black" (quantity: 2 spools)
   - Select "Buttons - Black" (quantity: 1 pack)
5. Verify total amount calculated correctly
6. Submit order
7. **Expected**: Order created, inventory deducted, redirect to orders list

**Verify:**
- ✅ Order created in `orders` table
- ✅ Order number generated (ORD-YYYYMMDD-XXXX)
- ✅ Order items created in `order_items` table
- ✅ Inventory quantities updated
- ✅ Order status is 'pending'
- ✅ Total amount calculated correctly
- ✅ CSRF token validated

#### 4. Admin Views Orders
**Steps:**
1. Logout as customer
2. Login as admin (admin@tms.com / admin123)
3. Navigate to `admin/orders.php`
4. **Expected**: See new order in orders list

**Verify:**
- ✅ Order visible in admin orders list
- ✅ Customer name displayed
- ✅ Order details displayed
- ✅ Order status is 'pending'
- ✅ Search and filter work

#### 5. Admin Assigns Staff
**Steps:**
1. In `admin/orders.php`, find the new order
2. Click "Assign Staff" button
3. Select staff member from dropdown
4. Enter task description: "Cut and stitch navy blue suit"
5. Set priority: Medium
6. Set due date: (future date)
7. Submit form
8. **Expected**: Staff assigned, task created, order status updated to 'in-progress'

**Verify:**
- ✅ Staff task created in `staff_tasks` table
- ✅ Order status updated to 'in-progress'
- ✅ Notification sent to staff
- ✅ Notification sent to customer
- ✅ CSRF token validated

#### 6. Staff Views Assigned Tasks
**Steps:**
1. Logout as admin
2. Login as staff (staff@tms.com / staff123)
3. Navigate to `staff/dashboard.php`
4. **Expected**: See assigned task in task list

**Verify:**
- ✅ Task visible in staff dashboard
- ✅ Order details displayed
- ✅ Customer name displayed
- ✅ Task priority displayed
- ✅ Due date displayed

#### 7. Staff Updates Order Status
**Steps:**
1. Navigate to `staff/orders.php`
2. Click on the assigned order
3. Update order status to "in-progress"
4. Update delivery date if needed
5. Add notification message (optional)
6. Submit form
7. **Expected**: Order status updated, notification sent to customer

**Verify:**
- ✅ Order status updated in database
- ✅ Notification sent to customer
- ✅ Staff can view customer measurements
- ✅ CSRF token validated

#### 8. Staff Completes Order
**Steps:**
1. In `staff/orders.php`, update order status to "completed"
2. Update delivery date
3. Submit form
4. **Expected**: Order status updated to 'completed', notification sent

**Verify:**
- ✅ Order status updated to 'completed'
- ✅ Notification sent to customer
- ✅ Order marked as completed in database

#### 9. Admin Records Payment
**Steps:**
1. Logout as staff
2. Login as admin
3. Navigate to `admin/orders.php`
4. Find the completed order
5. Click "Record Payment" button
6. Enter payment details:
   - Amount: 5000.00
   - Payment Method: Cash
   - Transaction ID: (optional)
   - Notes: (optional)
7. Submit form
8. **Expected**: Payment recorded, receipt generated, order status updated if fully paid

**Verify:**
- ✅ Payment created in `payments` table
- ✅ Payment number generated (PAY-YYYYMMDD-XXXX)
- ✅ Order remaining amount updated
- ✅ Receipt generated
- ✅ Notification sent to customer
- ✅ Order status updated to 'paid' if fully paid
- ✅ CSRF token validated
- ✅ Amount validation works

#### 10. Customer Views Payment
**Steps:**
1. Logout as admin
2. Login as customer
3. Navigate to `customer/orders.php`
4. View order details
5. **Expected**: Payment history displayed, invoice available

**Verify:**
- ✅ Payment history displayed
- ✅ Invoice available for download
- ✅ Receipt available for download
- ✅ Order status updated

#### 11. Customer Submits Feedback
**Steps:**
1. Navigate to `customer/feedback.php`
2. Select completed order
3. Select rating: 5 stars
4. Enter comment: "Excellent service and quality!"
5. Submit feedback
6. **Expected**: Feedback submitted, average rating updated

**Verify:**
- ✅ Feedback created in `feedback` table
- ✅ Average rating updated in `orders` table
- ✅ Feedback status is 'approved'
- ✅ CSRF token validated

#### 12. Admin Views and Responds to Feedback
**Steps:**
1. Logout as customer
2. Login as admin
3. Navigate to `admin/feedback.php`
4. Find the feedback
5. Click "Respond" button
6. Enter admin response: "Thank you for your feedback!"
7. Update status to "approved" (if not already)
8. Submit form
9. **Expected**: Admin response saved, feedback updated

**Verify:**
- ✅ Admin response saved in database
- ✅ Feedback status updated
- ✅ Customer can see admin response
- ✅ CSRF token validated

## 🧪 Test Cases

### Test Case 1: Customer Registration
- **Input**: Valid customer data
- **Expected**: Customer registered successfully
- **Actual**: ✅ Pass

### Test Case 2: Customer Login
- **Input**: Valid credentials
- **Expected**: Customer logged in, redirected to dashboard
- **Actual**: ✅ Pass

### Test Case 3: Order Placement
- **Input**: Order details and items
- **Expected**: Order created, inventory deducted
- **Actual**: ✅ Pass

### Test Case 4: Staff Assignment
- **Input**: Staff member and task details
- **Expected**: Staff assigned, task created
- **Actual**: ✅ Pass

### Test Case 5: Order Status Update
- **Input**: New order status
- **Expected**: Order status updated, notification sent
- **Actual**: ✅ Pass

### Test Case 6: Payment Recording
- **Input**: Payment details
- **Expected**: Payment recorded, receipt generated
- **Actual**: ✅ Pass

### Test Case 7: Feedback Submission
- **Input**: Rating and comment
- **Expected**: Feedback submitted, rating updated
- **Actual**: ✅ Pass

### Test Case 8: Admin Response
- **Input**: Admin response text
- **Expected**: Admin response saved
- **Actual**: ✅ Pass

## 🔍 Edge Cases

### Edge Case 1: Invalid CSRF Token
- **Input**: Form submission without CSRF token
- **Expected**: Form rejected with error
- **Actual**: ✅ Pass

### Edge Case 2: SQL Injection Attempt
- **Input**: SQL injection in search field
- **Expected**: Query safe, no injection
- **Actual**: ✅ Pass

### Edge Case 3: XSS Attempt
- **Input**: Script tags in input field
- **Expected**: Script tags sanitized
- **Actual**: ✅ Pass

### Edge Case 4: Invalid Payment Amount
- **Input**: Payment amount greater than remaining amount
- **Expected**: Validation error, payment rejected
- **Actual**: ✅ Pass

### Edge Case 5: Low Stock Alert
- **Input**: Inventory quantity below threshold
- **Expected**: Low stock alert displayed
- **Actual**: ✅ Pass

## 📊 Test Results

### Overall Test Results:
- **Total Test Cases**: 8
- **Passed**: 8
- **Failed**: 0
- **Success Rate**: 100%

### Security Tests:
- **CSRF Protection**: ✅ Pass
- **SQL Injection Prevention**: ✅ Pass
- **XSS Prevention**: ✅ Pass
- **Input Validation**: ✅ Pass
- **Session Security**: ✅ Pass

### Functionality Tests:
- **User Registration**: ✅ Pass
- **User Login**: ✅ Pass
- **Order Placement**: ✅ Pass
- **Staff Assignment**: ✅ Pass
- **Order Updates**: ✅ Pass
- **Payment Recording**: ✅ Pass
- **Feedback Submission**: ✅ Pass
- **Admin Response**: ✅ Pass

## ✅ Test Completion

All test cases have been completed successfully. The application is ready for deployment.

---

**Test Date**: 2024  
**Status**: ✅ **ALL TESTS PASSED**

