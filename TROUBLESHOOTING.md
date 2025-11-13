# Troubleshooting Guide - Tailoring Management System

## Can't Open the Project?

Follow these steps to diagnose and fix the issue:

### Step 1: Check XAMPP Services
1. Open XAMPP Control Panel
2. Make sure **Apache** is running (should show green "Running")
3. Make sure **MySQL** is running (should show green "Running")
4. If not running, click "Start" for both services

### Step 2: Check the URL
Try accessing the project using these URLs:
- `http://localhost/TMS/`
- `http://localhost/TMS/index.php`
- `http://127.0.0.1/TMS/`

### Step 3: Test PHP
1. Access: `http://localhost/TMS/test.php`
2. This will show if PHP is working and database connection status

### Step 4: Check File Permissions
Make sure files have correct permissions (usually 644 for files, 755 for directories)

### Step 5: Check Apache Error Log
1. Open XAMPP Control Panel
2. Click "Logs" next to Apache
3. Check for any error messages
4. Common location: `/Applications/XAMPP/xamppfiles/logs/error_log`

### Step 6: Common Issues and Solutions

#### Issue: "404 Not Found"
**Solution:**
- Make sure you're using the correct URL: `http://localhost/TMS/`
- Check that the TMS folder is in: `/Applications/XAMPP/xamppfiles/htdocs/TMS/`
- Verify Apache is running

#### Issue: "500 Internal Server Error"
**Solution:**
- Check Apache error log (see Step 5)
- Temporarily rename `.htaccess` to `.htaccess.bak` to test
- Check file permissions

#### Issue: "Database connection failed"
**Solution:**
- Make sure MySQL is running in XAMPP
- Import the database: `database_setup.sql` in phpMyAdmin
- Check database credentials in `config/db_config.php`

#### Issue: "Blank White Page"
**Solution:**
- Enable error display in PHP
- Check Apache error log
- Verify all required files exist
- Test with `test.php` file

#### Issue: "CSS/JS not loading"
**Solution:**
- Check browser console for 404 errors
- Verify files exist in `assets/css/` and `assets/js/`
- Clear browser cache
- Check `.htaccess` file

### Step 7: Verify Database Setup
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Check if database `tms_database` exists
3. If not, import `database_setup.sql`
4. Verify tables are created

### Step 8: Check PHP Version
1. Access: `http://localhost/dashboard/` (XAMPP dashboard)
2. Check PHP version (should be 7.4 or higher)
3. Verify PHP extensions: PDO, PDO_MySQL, JSON

### Quick Test Commands

Open Terminal and run:
```bash
# Check if Apache is running
sudo lsof -i :80

# Check if MySQL is running
sudo lsof -i :3306

# Test PHP syntax
cd /Applications/XAMPP/xamppfiles/htdocs/TMS
php -l index.php
```

### Still Having Issues?

1. **Disable .htaccess temporarily:**
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/TMS
   mv .htaccess .htaccess.bak
   ```
   Then try accessing the site again.

2. **Check Apache modules:**
   - Make sure `mod_rewrite` is enabled
   - In XAMPP, this is usually enabled by default

3. **Verify XAMPP Installation:**
   - Make sure XAMPP is properly installed
   - Try accessing: `http://localhost/dashboard/`

4. **Check Port Conflicts:**
   - Make sure port 80 is not used by another application
   - Check XAMPP Control Panel for port conflicts

### Contact Information
If you're still experiencing issues, check:
- Apache error logs
- PHP error logs
- Browser console (F12)
- Network tab in browser developer tools

