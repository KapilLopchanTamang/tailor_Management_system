# Quick Start Guide - Tailoring Management System

## Step 1: Start XAMPP Services

1. **Open XAMPP Control Panel**
   - On macOS: Applications → XAMPP → XAMPP Control Panel
   - Or search for "XAMPP" in Spotlight

2. **Start Apache**
   - Click the "Start" button next to Apache
   - Wait until it shows "Running" in green

3. **Start MySQL**
   - Click the "Start" button next to MySQL
   - Wait until it shows "Running" in green

## Step 2: Setup Database

1. **Open phpMyAdmin**
   - Go to: `http://localhost/phpmyadmin`
   - Or click "Admin" button next to MySQL in XAMPP Control Panel

2. **Import Database**
   - Click on "Import" tab
   - Click "Choose File" and select `database_setup.sql` from the TMS folder
   - Click "Go" button at the bottom
   - Wait for "Import has been successfully finished" message

3. **Verify Database**
   - Check if database `tms_database` appears in the left sidebar
   - Click on it to see all tables

## Step 3: Access the Application

1. **Open Browser**
   - Open any web browser (Chrome, Firefox, Safari, etc.)

2. **Navigate to the Application**
   - Go to: `http://localhost/TMS/`
   - You should see the login page

3. **Login Credentials**
   - **Admin**: 
     - Email: `admin@tms.com`
     - Password: `admin123`
   - **Staff**: 
     - Email: `staff@tms.com`
     - Password: `staff123`
   - **Customer**: 
     - Email: `customer@tms.com`
     - Password: `customer123`

## Step 4: Test the Application

1. **Test Login**
   - Try logging in with admin credentials
   - You should be redirected to the admin dashboard

2. **Test Navigation**
   - Check if all links work
   - Verify CSS and JavaScript are loading

3. **Test Database Connection**
   - Access: `http://localhost/TMS/test.php`
   - Check if it shows "Database connection: OK"

## Troubleshooting

### If you see "404 Not Found":
- Make sure Apache is running
- Check the URL: `http://localhost/TMS/` (with capital TMS)
- Verify the folder is in: `/Applications/XAMPP/xamppfiles/htdocs/TMS/`

### If you see "500 Internal Server Error":
- Check Apache error log
- Temporarily rename `.htaccess` to `.htaccess.bak`
- Check file permissions

### If you see "Database connection failed":
- Make sure MySQL is running
- Verify database is imported
- Check `config/db_config.php` credentials

### If you see a blank page:
- Enable error display
- Check browser console (F12)
- Check Apache error log

### If CSS/JS not loading:
- Clear browser cache
- Check browser console for errors
- Verify files exist in `assets/` folder

## File Structure

```
TMS/
├── admin/              # Admin pages
├── assets/             # CSS, JS, images
├── config/             # Database config
├── includes/           # Header, footer, functions
├── staff/              # Staff pages
├── customer/           # Customer pages
├── uploads/            # File uploads
├── index.php           # Login page
├── test.php            # Test file
└── database_setup.sql  # Database schema
```

## Default URLs

- **Login Page**: `http://localhost/TMS/`
- **Admin Dashboard**: `http://localhost/TMS/admin/dashboard.php`
- **Staff Dashboard**: `http://localhost/TMS/staff/dashboard.php`
- **Customer Dashboard**: `http://localhost/TMS/customer/dashboard.php`
- **Test Page**: `http://localhost/TMS/test.php`
- **phpMyAdmin**: `http://localhost/phpmyadmin`

## Next Steps

1. Change default passwords after first login
2. Add more users through the admin panel
3. Customize the application as needed
4. Add your business logic

## Support

For more help, check:
- `TROUBLESHOOTING.md` for detailed troubleshooting
- `README.md` for project documentation
- Apache error logs for specific errors

