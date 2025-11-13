# Installation Guide
## Tailoring Management System

### Quick Start

1. **Install XAMPP**
   - Download and install XAMPP from https://www.apachefriends.org/
   - Ensure PHP 8+ is included

2. **Start Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

3. **Import Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Click "Import" tab
   - Select `database_setup.sql` file
   - Click "Go" to import
   - Database `tms_database` will be created with all tables

4. **Configure Database**
   - Edit `config/db_config.php`
   - Update database credentials if needed (default: root, no password)

5. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 logs/
   ```

6. **Access Application**
   - Open browser: `http://localhost/TMS/`
   - Login with default credentials (see README.md)

### Default Login Credentials

- **Admin**: admin@tms.com / admin123
- **Staff**: staff@tms.com / staff123
- **Customer**: customer@tms.com / customer123

**⚠️ Change these passwords after first login!**

### Troubleshooting

**Database Connection Error:**
- Check MySQL is running in XAMPP
- Verify credentials in `config/db_config.php`
- Ensure database exists

**404 Errors:**
- Check `.htaccess` file exists
- Verify Apache mod_rewrite is enabled
- Check file paths

**Session Errors:**
- Check PHP session configuration
- Verify session directory is writable
- Check `session_start()` is called before output

**CSRF Token Errors:**
- Ensure sessions are working
- Check `csrfField()` is in all forms
- Verify token validation in form handlers

### Security Checklist

- [ ] Change default passwords
- [ ] Update database credentials
- [ ] Set `ENVIRONMENT` to 'production' in `config/db_config.php`
- [ ] Review error logging settings
- [ ] Test all features
- [ ] Run unit tests: `php tests/auth_test.php`
- [ ] Review security logs

### Production Deployment

1. Set `ENVIRONMENT` to 'production' in `config/db_config.php`
2. Update database credentials
3. Change default passwords
4. Review `.htaccess` settings
5. Enable error logging
6. Test all functionality
7. Backup database regularly

---

**For detailed information, see README.md**

