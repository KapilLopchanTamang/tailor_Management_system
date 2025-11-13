# Deployment Guide
## Tailoring Management System

## 📦 Deployment Package Structure

```
TMS/
├── admin/                  # Admin dashboard pages
├── api/                    # API endpoints
├── assets/                 # CSS, JS, images
│   ├── css/
│   │   ├── style.css
│   │   ├── style.min.css  # Minified CSS
│   │   └── responsive.css
│   ├── js/
│   │   ├── main.js
│   │   ├── main.min.js    # Minified JS
│   │   ├── auth.js
│   │   ├── validation.js
│   │   ├── search.js
│   │   ├── security.js
│   │   └── notifications.js
│   └── images/
├── config/                 # Configuration files
│   └── db_config.php
├── customer/               # Customer dashboard pages
├── includes/               # Shared components
│   ├── header.php
│   ├── footer.php
│   ├── navbar.php          # Unified navbar
│   ├── admin_nav.php
│   ├── functions.php
│   ├── auth.php
│   ├── billing.php
│   ├── notifications.php
│   └── error_handler.php
├── staff/                  # Staff dashboard pages
├── tests/                  # Unit tests
├── uploads/                # File uploads directory
├── logs/                   # Error and security logs
├── database_setup.sql      # Database schema
├── database_indexes.sql    # Database indexes
├── database_updates_feedback_search.sql  # Database updates
├── sample_data.sql         # Sample data
├── sitemap.xml             # Sitemap
├── robots.txt              # Robots file
├── .htaccess               # Apache configuration
├── index.php               # Login page
├── login.php               # Login page
├── register.php            # Registration page
├── forgot_password.php     # Password reset
├── 404.php                 # Custom 404 error page
├── 500.php                 # Custom 500 error page
└── README.md               # Documentation
```

## 🚀 Deployment Steps

### 1. Pre-Deployment Checklist

- [ ] Change default passwords
- [ ] Update database credentials in `config/db_config.php`
- [ ] Set `ENVIRONMENT` to 'production' in `config/db_config.php`
- [ ] Review `.htaccess` settings
- [ ] Verify all file permissions
- [ ] Test all functionality
- [ ] Run unit tests
- [ ] Review security logs
- [ ] Backup database

### 2. Server Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher
- **mod_rewrite**: Enabled
- **mod_headers**: Enabled (for security headers)
- **PDO**: Enabled
- **Session**: Enabled

### 3. Database Setup

```bash
# 1. Create database
mysql -u root -p
CREATE DATABASE tms_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 2. Import schema
mysql -u root -p tms_database < database_setup.sql

# 3. Add indexes
mysql -u root -p tms_database < database_indexes.sql

# 4. Apply updates (if any)
mysql -u root -p tms_database < database_updates_feedback_search.sql

# 5. Load sample data (optional)
mysql -u root -p tms_database < sample_data.sql
```

### 4. File Permissions

```bash
# Set directory permissions
chmod 755 uploads/
chmod 755 logs/
chmod 644 .htaccess
chmod 644 config/db_config.php

# Set file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
```

### 5. Configuration

#### Update `config/db_config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'tms_database');
define('BASE_URL', '/TMS/'); // Update to your path
define('ENVIRONMENT', 'production'); // Set to production
```

#### Update `.htaccess`:
- Update `RewriteBase` if needed
- Update error document paths if needed
- Verify security headers are enabled

### 6. Security Configuration

#### Production Settings:
- Set `ENVIRONMENT` to 'production' in `config/db_config.php`
- Review error logging settings
- Enable security headers in `.htaccess`
- Verify CSRF protection is enabled
- Verify all forms have CSRF tokens
- Verify all queries use prepared statements

### 7. Testing

#### Test Flow:
1. **Register Customer** → `register.php`
2. **Place Order** → `customer/orders.php`
3. **Admin Assign Staff** → `admin/orders.php`
4. **Staff Update Order** → `staff/orders.php`
5. **Record Payment** → `admin/orders.php` or `customer/orders.php`
6. **Submit Feedback** → `customer/feedback.php`

#### Test Checklist:
- [ ] User registration works
- [ ] User login works
- [ ] Customer can place orders
- [ ] Admin can assign staff
- [ ] Staff can update orders
- [ ] Payments can be recorded
- [ ] Feedback can be submitted
- [ ] Notifications work
- [ ] Search works
- [ ] Reports work
- [ ] Mobile responsiveness works
- [ ] CSRF protection works
- [ ] Error handling works

### 8. Optimization

#### Database:
- Indexes added via `database_indexes.sql`
- Query optimization verified
- Connection pooling (if applicable)

#### Assets:
- CSS minified (optional)
- JS minified (optional)
- Images optimized (if any)
- CDN for Bootstrap/jQuery (already using CDN)

#### Caching:
- Browser caching enabled in `.htaccess`
- GZIP compression enabled in `.htaccess`

### 9. Monitoring

#### Logs:
- Error logs: `logs/php_errors.log`
- Security logs: `logs/security.log`
- Apache logs: Server logs

#### Monitoring:
- Monitor error logs regularly
- Monitor security logs regularly
- Monitor database performance
- Monitor server resources

### 10. Backup

#### Regular Backups:
- Database backup (daily)
- File backup (weekly)
- Configuration backup (monthly)

#### Backup Script:
```bash
#!/bin/bash
# Backup database
mysqldump -u root -p tms_database > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/TMS/
```

## 🔒 Security Checklist

- [x] CSRF protection enabled
- [x] SQL injection prevention verified
- [x] XSS prevention verified
- [x] Password hashing verified
- [x] Session security enabled
- [x] Input validation enabled
- [x] Error handling enabled
- [x] Security headers enabled
- [x] File protection enabled
- [x] Access control verified

## 📊 Performance Optimization

### Database:
- ✅ Indexes added for frequently queried columns
- ✅ Composite indexes for common query patterns
- ✅ Query optimization verified

### Assets:
- ✅ CDN for Bootstrap and jQuery
- ✅ Minified CSS/JS (optional)
- ✅ Browser caching enabled
- ✅ GZIP compression enabled

### Code:
- ✅ Prepared statements for all queries
- ✅ Efficient database queries
- ✅ Optimized PHP code
- ✅ Error handling optimized

## 🌐 SEO Configuration

### Sitemap:
- `sitemap.xml` created
- Public pages included
- Protected pages excluded

### Robots.txt:
- Admin, staff, customer pages disallowed
- API endpoints disallowed
- Config and logs disallowed
- Public pages allowed

## 📝 Post-Deployment

### Immediate Actions:
1. Change default passwords
2. Test all functionality
3. Monitor error logs
4. Monitor security logs
5. Verify backups

### Regular Maintenance:
1. Update dependencies
2. Review security logs
3. Backup database
4. Update passwords
5. Review access controls
6. Monitor performance
7. Update documentation

## 🆘 Troubleshooting

### Common Issues:
1. **Database Connection Error**: Check credentials in `config/db_config.php`
2. **404 Errors**: Check `.htaccess` and mod_rewrite
3. **Session Errors**: Check PHP session configuration
4. **CSRF Errors**: Verify sessions are working
5. **Permission Errors**: Check file permissions

### Support:
- Check `TROUBLESHOOTING.md` for detailed solutions
- Review error logs in `logs/php_errors.log`
- Review security logs in `logs/security.log`
- Check Apache error logs

## ✅ Deployment Verification

### Verify Deployment:
1. ✅ Database connection works
2. ✅ User login works
3. ✅ All pages load correctly
4. ✅ Forms submit correctly
5. ✅ CSRF protection works
6. ✅ Error handling works
7. ✅ Security headers work
8. ✅ Mobile responsiveness works
9. ✅ Search works
10. ✅ Notifications work

## 🎉 Deployment Complete

The Tailoring Management System is now deployed and ready for use!

---

**Deployment Date**: 2024  
**Status**: ✅ **READY FOR PRODUCTION**

