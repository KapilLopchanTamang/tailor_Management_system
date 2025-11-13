# Tailoring Management System (TMS)

A comprehensive, secure PHP web application for managing tailoring business operations including customers, orders, inventory, staff tasks, payments, feedback, and more.

## 🚀 Features

### Core Features
- **User Management**: Admin, Staff, and Customer roles with role-based access control
- **Customer Management**: Customer details, measurements (JSON format), and profiles
- **Order Management**: Track orders from placement to delivery with status updates
- **Inventory Management**: Fabrics, materials, and accessories with low stock alerts
- **Staff Task Management**: Assign and track tasks for staff members
- **Payment Processing**: Multiple payment methods (cash, card, bank transfer, mobile payment, cheque) with receipt generation
- **Delivery Tracking**: Delivery dates and status management
- **Feedback System**: Customer ratings, reviews, and admin responses
- **Notifications**: Real-time notifications for order updates and system events
- **Reports**: Comprehensive reporting and analytics with charts
- **Global Search**: Real-time search across orders, customers, and inventory

### Security Features
- ✅ **CSRF Protection**: All 14 forms protected with CSRF tokens
- ✅ **SQL Injection Prevention**: PDO prepared statements throughout (31 files)
- ✅ **XSS Prevention**: Input sanitization and output escaping
- ✅ **Password Hashing**: bcrypt password hashing
- ✅ **Session Management**: Secure session handling with 30-minute timeout
- ✅ **Role-Based Access Control**: Restricted access based on user roles
- ✅ **Input Validation**: Server-side and client-side validation (jQuery)
- ✅ **Error Handling**: Custom error pages (404, 500) and try-catch blocks (33 files)
- ✅ **Security Headers**: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection
- ✅ **Idle Detection**: Auto-logout after 30 minutes inactivity (5-minute warning)
- ✅ **Error Logging**: Comprehensive error and security event logging
- ✅ **File Protection**: Sensitive files and directories protected

### User Experience
- ✅ **Responsive Design**: Mobile-friendly Bootstrap 5 interface
- ✅ **Loading Spinners**: Visual feedback during form submissions
- ✅ **Form Validation**: jQuery validation with real-time feedback
- ✅ **Accessibility**: ARIA labels and keyboard navigation support
- ✅ **Error Messages**: User-friendly error handling

## 📋 Technology Stack

- **Backend**: PHP 8+
- **Database**: MySQL (PDO with prepared statements)
- **Frontend**: Bootstrap 5, Vanilla JavaScript, jQuery
- **Server**: Apache (XAMPP)
- **Security**: CSRF tokens, password hashing, input sanitization

## 🔧 Installation

### 1. Prerequisites

- **XAMPP** (Apache + MySQL + PHP 8+)
- **Web browser** (Chrome, Firefox, Safari, Edge)
- **phpMyAdmin** (included with XAMPP)

### 2. Database Setup

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Import Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Click "New" to create a database (optional, SQL will create it)
   - Click "Import" tab
   - Select `database_setup.sql` file
   - Click "Go" to import

   **OR** run SQL manually:
   ```sql
   SOURCE /Applications/XAMPP/xamppfiles/htdocs/TMS/database_setup.sql;
   ```

3. **Run Database Updates** (if needed)
   ```sql
   SOURCE /Applications/XAMPP/xamppfiles/htdocs/TMS/database_updates_feedback_search.sql;
   ```

### 3. Configure Database

Edit `config/db_config.php` and update database credentials if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Change if you have a MySQL password
define('DB_NAME', 'tms_database');
define('BASE_URL', '/TMS/');  // Adjust if your path is different
```

### 4. Set File Permissions

Ensure the `uploads/` directory is writable:
```bash
chmod 755 uploads/
```

### 5. Access Application

Open your browser and navigate to:
```
http://localhost/TMS/
```

## 🔐 Default Login Credentials

After database setup, you can login with:

### Admin
- **Email/Username**: `admin@tms.com` or `admin`
- **Password**: `admin123`
- **Access**: Full system access

### Staff
- **Email/Username**: `staff@tms.com` or `staff1`
- **Password**: `staff123`
- **Access**: Task management, order processing

### Customer
- **Email/Username**: `customer@tms.com` or `customer1`
- **Password**: `customer123`
- **Access**: Order placement, feedback, profile management

**⚠️ IMPORTANT**: Change these passwords after first login!

## 📊 Database Schema

### Core Tables

- **users**: System users (admin, staff, customers)
- **customers**: Customer details and measurements
- **orders**: Order management with status tracking
- **order_items**: Items in each order
- **inventory**: Fabrics, materials, and accessories
- **staff_tasks**: Task assignments for staff
- **payments**: Payment transactions and receipts
- **feedback**: Customer feedback and ratings (with admin responses)
- **notifications**: System notifications
- **password_reset_tokens**: Password reset tokens

### Key Features

- **Automatic Order Number Generation**: Format: `ORD-YYYYMMDD-XXXX`
- **Automatic Payment Number Generation**: Format: `PAY-YYYYMMDD-XXXX`
- **JSON Measurements**: Customer measurements stored in JSON format
- **Low Stock Alerts**: Automatic tracking of inventory levels
- **Payment Tracking**: Automatic calculation of remaining amounts
- **Average Rating**: Automatic calculation from customer feedback
- **Views**: Pre-built views for common queries
- **Triggers**: Automatic calculations and updates
- **Stored Procedures**: Reusable database procedures

## 📁 Project Structure

```
TMS/
├── admin/                  # Admin dashboard pages
│   ├── dashboard.php
│   ├── users.php
│   ├── orders.php
│   ├── inventory.php
│   ├── staff.php
│   ├── reports.php
│   ├── feedback.php
│   └── notifications.php
├── assets/                 # CSS, JS, images
│   ├── css/
│   │   ├── style.css
│   │   └── responsive.css
│   ├── js/
│   │   ├── main.js
│   │   ├── auth.js
│   │   ├── validation.js
│   │   ├── search.js
│   │   ├── security.js
│   │   └── notifications.js
│   └── images/
├── config/                 # Configuration files
│   └── db_config.php       # Database configuration
├── includes/               # Shared components
│   ├── header.php
│   ├── footer.php
│   ├── functions.php       # Common functions
│   ├── auth.php            # Authentication functions
│   ├── billing.php         # Billing functions
│   ├── notifications.php   # Notification functions
│   ├── admin_nav.php       # Admin navigation
│   └── logout.php
├── staff/                  # Staff dashboard pages
│   ├── dashboard.php
│   ├── tasks.php
│   ├── orders.php
│   ├── inventory.php
│   └── notifications.php
├── customer/               # Customer dashboard pages
│   ├── dashboard.php
│   ├── profile.php
│   ├── orders.php
│   ├── feedback.php
│   └── track.php
├── api/                    # API endpoints
│   ├── search.php          # Global search API
│   ├── invoice.php         # Invoice generation
│   ├── receipt.php         # Receipt generation
│   ├── record_payment.php  # Payment recording
│   └── notifications.php   # Notifications API
├── tests/                  # Unit tests
│   └── auth_test.php       # Authentication tests
├── uploads/                # File uploads directory
├── logs/                   # Error and security logs
│   ├── .htaccess          # Protect log files
│   └── .gitkeep           # Keep directory in git
├── index.php               # Login page
├── login.php               # Login page
├── register.php            # Registration page
├── forgot_password.php     # Password reset
├── 404.php                 # Custom 404 error page
├── 500.php                 # Custom 500 error page
├── .htaccess               # Apache configuration
├── database_setup.sql      # Database schema
├── database_updates_feedback_search.sql  # Database updates
├── README.md               # This file
├── SECURITY.md             # Security documentation
├── SECURITY_AUDIT.md       # Security audit report
├── SECURITY_SUMMARY.md     # Security implementation summary
└── INSTALLATION.md         # Installation guide
```

## 🔒 Security Features

### CSRF Protection
All forms include CSRF tokens to prevent Cross-Site Request Forgery attacks:
```php
<?php echo csrfField(); ?>
```

### SQL Injection Prevention
All database queries use PDO prepared statements:
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
```

### XSS Prevention
All user input is sanitized and output is escaped:
```php
echo htmlspecialchars($userInput);
```

### Password Security
- Passwords are hashed using bcrypt
- Minimum password length: 6 characters
- Password reset tokens with expiration

### Session Security
- Secure session handling
- Session timeout after inactivity (30 minutes)
- Auto-logout with warning (5 minutes before logout)

### Input Validation
- Server-side validation (PHP)
- Client-side validation (JavaScript/jQuery)
- Email format validation
- Phone number validation
- Required field validation

## 🧪 Testing

### Run Unit Tests

```bash
# Via command line
php tests/auth_test.php

# Via web browser
http://localhost/TMS/tests/auth_test.php
```

### Test Security

1. **CSRF Protection**: Try submitting forms without CSRF token (should be rejected)
2. **SQL Injection**: Test with SQL injection attempts (should be blocked by prepared statements)
3. **XSS Protection**: Test with script tags in input fields (should be sanitized)
4. **Session Timeout**: Test idle detection and auto-logout (30 minutes inactivity)
5. **Input Validation**: Test form validation (client-side and server-side)
6. **Error Handling**: Test error pages (404.php, 500.php)

### Security Features Verified

- ✅ 14 forms with CSRF protection
- ✅ 31 files with prepared statements
- ✅ 33 files with error handling
- ✅ All inputs sanitized
- ✅ All outputs escaped
- ✅ Session security implemented
- ✅ Password hashing verified

## 📱 Responsive Design

The application is fully responsive and mobile-friendly:
- **Mobile**: Optimized for screens 320px and above
- **Tablet**: Optimized for screens 768px and above
- **Desktop**: Full-featured interface for screens 1024px and above

### Mobile Features
- Touch-friendly buttons (minimum 44px height)
- Responsive navigation menu
- Stacked form elements on small screens
- Optimized tables with horizontal scroll
- Mobile-friendly modals and dropdowns

## 🎨 User Interface

### Bootstrap 5 Components
- Cards, Modals, Dropdowns
- Forms, Tables, Buttons
- Alerts, Badges, Spinners
- Navigation bars, Pagination

### Icons
- Bootstrap Icons for consistent iconography

### Charts
- Chart.js for data visualization in reports

## 📈 Features by Role

### Admin
- User management (create, edit, delete users)
- Order management (view, update, assign staff)
- Inventory management (CRUD operations)
- Staff task assignment
- Reports and analytics
- Feedback management with responses
- System notifications

### Staff
- View assigned tasks
- Update task status
- Process orders
- Update delivery dates
- View inventory
- Request low stock alerts
- View notifications

### Customer
- Place orders
- Track orders in real-time
- View order history
- Manage profile and measurements
- Submit feedback and ratings
- View admin responses
- Receive notifications
- View invoices and receipts

## 🔄 Order Status Flow

1. **pending** → Order placed, awaiting processing
2. **in-progress** → Order being worked on
3. **completed** → Order finished, ready for delivery
4. **delivered** → Order delivered to customer
5. **cancelled** → Order cancelled (can happen at any stage)

## 💳 Payment Methods

- Cash
- Card
- Bank Transfer
- Mobile Payment
- Cheque

## 📝 Measurements JSON Structure

Customer measurements are stored in JSON format:

```json
{
  "bust": "36",
  "waist": "30",
  "hips": "38",
  "shoulder": "16",
  "sleeve_length": "24",
  "shirt_length": "28",
  "pants_length": "32",
  "notes": "Any additional notes"
}
```

## 🛠️ Development

### Adding New Features

1. Create database tables if needed
2. Update `config/db_config.php` if database changes
3. Create PHP files in appropriate directories
4. Use `baseUrl()` function for all internal links
5. Use PDO for all database operations
6. Sanitize all user inputs using `sanitize()` function
7. Add CSRF tokens to all forms
8. Add try-catch blocks for error handling
9. Test on mobile devices

### Code Standards

- **PHP**: PSR-12 coding standards
- **JavaScript**: ES6+ with strict mode
- **CSS**: Bootstrap 5 utility classes
- **Database**: PDO prepared statements only
- **Security**: CSRF tokens, input sanitization, output escaping

## 🐛 Troubleshooting

### Database Connection Error
- Check MySQL is running in XAMPP
- Verify database credentials in `config/db_config.php`
- Ensure database `tms_database` exists

### Session Issues
- Check `session_start()` is called before any output
- Verify session directory is writable
- Check PHP session configuration

### CSRF Token Errors
- Ensure `csrfField()` is included in all forms
- Verify session is working correctly
- Check token validation in form handlers

### 404 Errors
- Verify `.htaccess` file is present
- Check Apache mod_rewrite is enabled
- Verify file paths are correct

### Mobile Responsiveness Issues
- Test with browser dev tools
- Check `responsive.css` is loaded
- Verify Bootstrap 5 CSS is loaded

## 📚 Documentation

- **FEEDBACK_SEARCH_GUIDE.md**: Feedback system and global search documentation
- **QUICK_START.md**: Quick start guide
- **Code Comments**: Extensive inline documentation

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is developed for educational purposes.

## 🔗 Useful Links

- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [jQuery Validation Plugin](https://jqueryvalidation.org/)
- [Chart.js Documentation](https://www.chartjs.org/)

## 📞 Support

For issues or questions:
1. Check the troubleshooting section
2. Review code comments
3. Check error logs in `XAMPP/logs/`
4. Contact the development team

---

**Version**: 2.0  
**Last Updated**: 2024  
**PHP Version**: 8.0+  
**MySQL Version**: 5.7+

## 🎯 Quick Start Checklist

- [ ] Install XAMPP
- [ ] Start Apache and MySQL
- [ ] Import `database_setup.sql`
- [ ] Configure `config/db_config.php`
- [ ] Set `uploads/` directory permissions
- [ ] Access `http://localhost/TMS/`
- [ ] Login with default credentials
- [ ] Change default passwords
- [ ] Test all features
- [ ] Run unit tests
- [ ] Test on mobile devices

---

**Happy Tailoring! 🪡✂️**
