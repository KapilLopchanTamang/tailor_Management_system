#!/bin/bash
# Deployment Package Creation Script
# Tailoring Management System

echo "=========================================="
echo "TMS Deployment Package Creator"
echo "=========================================="

# Set variables
PACKAGE_NAME="TMS_Deployment_$(date +%Y%m%d_%H%M%S)"
PACKAGE_DIR="./deployment_package"
SOURCE_DIR="."

# Create package directory
echo "Creating package directory..."
mkdir -p "$PACKAGE_DIR"

# Copy files (exclude unnecessary files)
echo "Copying files..."
rsync -av --exclude='.git' \
          --exclude='.DS_Store' \
          --exclude='*.log' \
          --exclude='deployment_package' \
          --exclude='*.zip' \
          --exclude='*.tar.gz' \
          --exclude='check_login.php' \
          --exclude='check.html' \
          --exclude='test.php' \
          --exclude='reset_password.php' \
          --exclude='fix_passwords.sql' \
          --exclude='TROUBLESHOOTING.md' \
          --exclude='*.pdf' \
          "$SOURCE_DIR" "$PACKAGE_DIR/"

# Set permissions
echo "Setting permissions..."
find "$PACKAGE_DIR" -type f -exec chmod 644 {} \;
find "$PACKAGE_DIR" -type d -exec chmod 755 {} \;
chmod 755 "$PACKAGE_DIR/uploads"
chmod 755 "$PACKAGE_DIR/logs"

# Create deployment instructions
echo "Creating deployment instructions..."
cat > "$PACKAGE_DIR/DEPLOYMENT_INSTRUCTIONS.txt" << EOF
Tailoring Management System - Deployment Instructions
====================================================

1. Upload all files to your web server

2. Set file permissions:
   chmod 755 uploads/
   chmod 755 logs/
   chmod 644 .htaccess
   chmod 644 config/db_config.php

3. Create database and import:
   mysql -u root -p
   CREATE DATABASE tms_database;
   USE tms_database;
   SOURCE database_setup.sql;
   SOURCE database_indexes.sql;
   SOURCE database_updates_feedback_search.sql;
   SOURCE sample_data.sql;

4. Configure database in config/db_config.php

5. Set ENVIRONMENT to 'production' in config/db_config.php

6. Test the application

7. Change default passwords

For detailed instructions, see DEPLOYMENT.md
EOF

# Create ZIP package
echo "Creating ZIP package..."
cd "$(dirname "$PACKAGE_DIR")"
zip -r "${PACKAGE_NAME}.zip" "$(basename "$PACKAGE_DIR")" -x "*.git*" "*.DS_Store" "*.log"

echo "=========================================="
echo "Package created: ${PACKAGE_NAME}.zip"
echo "=========================================="
echo "Package location: $(pwd)/${PACKAGE_NAME}.zip"
echo "Package size: $(du -sh "${PACKAGE_NAME}.zip" | cut -f1)"
echo "=========================================="

