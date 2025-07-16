# Invoicing System - Live Server Deployment Guide

## Prerequisites
- Web hosting with PHP 7.4+ support
- MySQL/MariaDB database
- cPanel or similar hosting control panel

## Step 1: Prepare Your Files

### 1.1 Upload Files
1. Upload all files to your web hosting's `public_html` folder (or your domain's root directory)
2. Ensure the following structure:
```
public_html/
├── config/
│   ├── database.php (local development)
│   └── database_production.php (production)
├── includes/
│   └── company_helper.php
├── uploads/ (create this folder)
├── index.php
├── dashboard.php
├── invoices.php
├── offers.php
├── clients.php
├── company_settings.php
├── view_invoice.php
├── view_offer.php
├── download_invoice.php
├── download_offer.php
├── logout.php
└── database.sql
```

### 1.2 Set Permissions
Set the following folder permissions:
- `uploads/` folder: 755 or 777 (for logo uploads)
- All other folders: 755
- All PHP files: 644

## Step 2: Database Setup

### 2.1 Create Database
1. Log into your hosting control panel (cPanel)
2. Go to "MySQL Databases" or "Databases"
3. Create a new database
4. Create a database user
5. Assign the user to the database with full privileges

### 2.2 Import Database Structure
1. Go to phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Choose the `database.sql` file
5. Click "Go" to import

### 2.3 Create Admin User
Run this SQL query in phpMyAdmin:
```sql
INSERT INTO users (username, password, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@yourdomain.com');
```
This creates:
- Username: `admin`
- Password: `password`
- Email: `admin@yourdomain.com`

## Step 3: Configuration

### 3.1 Update Database Configuration
1. Rename `config/database_production.php` to `config/database.php`
2. Update the database credentials:
```php
$host = 'localhost';
$dbname = 'your_actual_database_name';
$username = 'your_actual_database_username';
$password = 'your_actual_database_password';
```

### 3.2 Security Settings
1. Change the default admin password after first login
2. Update company settings with your actual business information
3. Consider enabling HTTPS (SSL certificate)

## Step 4: Testing

### 4.1 Test Login
1. Go to your domain: `https://yourdomain.com`
2. Login with:
   - Username: `admin`
   - Password: `password`

### 4.2 Test Features
1. Add a client
2. Create an invoice
3. Create an offer
4. Test logo upload
5. Test PDF download (requires TCPDF)

## Step 5: TCPDF Setup (for PDF downloads)

### 5.1 Download TCPDF
1. Download TCPDF from: https://github.com/tecnickcom/TCPDF
2. Extract the files
3. Upload the `tcpdf` folder to your website root

### 5.2 Verify Installation
The PDF download should work after TCPDF is installed.

## Security Recommendations

### 1. Change Default Credentials
- Change admin password immediately
- Use a strong password
- Consider creating additional users

### 2. Enable HTTPS
- Install SSL certificate
- Force HTTPS redirects
- Secure all data transmission

### 3. Regular Backups
- Backup database regularly
- Backup uploaded files
- Keep local copies

### 4. File Permissions
- Ensure proper file permissions
- Restrict access to config files
- Secure uploads directory

## Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Check database credentials
   - Verify database exists
   - Check database user permissions

2. **Upload Errors**
   - Check uploads folder permissions
   - Verify PHP upload settings
   - Check file size limits

3. **PDF Download Not Working**
   - Verify TCPDF is installed
   - Check file permissions
   - Test with smaller files first

4. **Session Issues**
   - Check PHP session settings
   - Verify session storage permissions
   - Clear browser cache

## Maintenance

### Regular Tasks:
1. **Backup Database**: Weekly automated backups
2. **Update PHP**: Keep PHP version updated
3. **Monitor Logs**: Check error logs regularly
4. **Clean Old Files**: Remove old uploads periodically

### Performance Tips:
1. **Enable Caching**: Use browser caching
2. **Optimize Images**: Compress uploaded logos
3. **Database Optimization**: Regular database maintenance
4. **CDN**: Consider using a CDN for static files

## Support

If you encounter issues:
1. Check error logs in your hosting control panel
2. Verify all file permissions
3. Test database connection
4. Contact your hosting provider for server-specific issues

## File Structure for Upload

When uploading to your server, ensure this structure:
```
public_html/
├── config/
│   └── database.php (updated with your credentials)
├── includes/
│   └── company_helper.php
├── uploads/ (empty folder, 755 permissions)
├── tcpdf/ (if using PDF downloads)
├── index.php
├── dashboard.php
├── invoices.php
├── offers.php
├── clients.php
├── company_settings.php
├── view_invoice.php
├── view_offer.php
├── download_invoice.php
├── download_offer.php
├── logout.php
└── database.sql
```

## Quick Checklist

- [ ] Files uploaded to server
- [ ] Database created and imported
- [ ] Admin user created
- [ ] Database credentials updated
- [ ] Uploads folder permissions set
- [ ] Login tested
- [ ] Basic functionality tested
- [ ] Company settings configured
- [ ] Logo uploaded (optional)
- [ ] TCPDF installed (for PDF downloads)
- [ ] HTTPS enabled (recommended)
- [ ] Default password changed
- [ ] Regular backup schedule set 