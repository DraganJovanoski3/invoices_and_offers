# Invoicing System

A complete web-based invoicing and offers management system built with PHP, MySQL, and Bootstrap.

## Features

### Core Functionality
- **User Authentication**: Secure login system with session management
- **Dashboard**: Overview with statistics and quick access to all features
- **Invoice Management**: Create, view, edit, and delete invoices with automatic numbering
- **Offer Management**: Create, view, edit, and delete offers with automatic numbering
- **Client Management**: Manage client information with CRUD operations
- **Company Settings**: Configure company details, logo, and contact information

### Advanced Features
- **Automatic Numbering**: Invoice and offer numbers reset yearly (e.g., 2024-001, OFF-2024-001)
- **PDF Generation**: Download invoices and offers as PDF files
- **Logo Upload**: Upload and display company logo on documents
- **Responsive Design**: Mobile-friendly interface using Bootstrap 5
- **Database Backup**: Built-in backup functionality
- **Security**: SQL injection protection, XSS prevention, and secure file handling

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Web server (Apache/Nginx)
- TCPDF library (for PDF generation)

## Installation

### Option 1: Automated Installation (Recommended)
1. Upload all files to your web server
2. Navigate to `install.php` in your browser
3. Follow the step-by-step installation wizard
4. The installer will:
   - Test database connection
   - Create database configuration
   - Import database structure
   - Create admin user
   - Set up required directories

### Option 2: Manual Installation
1. **Upload Files**: Upload all files to your web server's document root
2. **Create Database**: Create a MySQL database via your hosting control panel
3. **Configure Database**: Update `config/database.php` with your database credentials
4. **Import Database**: Import `database.sql` via phpMyAdmin
5. **Create Admin User**: Run the SQL command in the deployment guide
6. **Set Permissions**: Ensure `uploads/` directory has write permissions (755 or 777)

## Default Login

After installation, you can login with:
- **Username**: `admin`
- **Password**: `password`

**Important**: Change the default password immediately after first login!

## File Structure

```
/
├── config/
│   ├── database.php              # Database configuration
│   └── database_production.php   # Production database template
├── includes/
│   └── company_helper.php        # Company information helper
├── uploads/                      # Logo uploads directory
├── backups/                      # Database backups (created automatically)
├── index.php                     # Login page
├── dashboard.php                 # Main dashboard
├── invoices.php                  # Invoice management
├── offers.php                    # Offer management
├── clients.php                   # Client management
├── company_settings.php          # Company settings
├── view_invoice.php              # Invoice view page
├── view_offer.php                # Offer view page
├── download_invoice.php          # Invoice PDF download
├── download_offer.php            # Offer PDF download
├── backup.php                    # Database backup tool
├── install.php                   # Installation wizard
├── logout.php                    # Logout script
├── .htaccess                     # Apache configuration
├── database.sql                  # Database structure
├── DEPLOYMENT_GUIDE.md           # Detailed deployment instructions
└── README.md                     # This file
```

## Usage

### Getting Started
1. Login to the system
2. Configure your company settings (name, address, logo, etc.)
3. Add your first client
4. Create an invoice or offer
5. View and download documents

### Invoice/Offer Numbering
- Numbers automatically reset each year
- Format: `YYYY-XXX` for invoices, `OFF-YYYY-XXX` for offers
- Example: `2024-001`, `2024-002`, `OFF-2024-001`

### PDF Downloads
- Requires TCPDF library
- Download from: https://github.com/tecnickcom/TCPDF
- Extract to your website root directory

## Security Features

- **SQL Injection Protection**: All database queries use prepared statements
- **XSS Prevention**: Output is properly escaped
- **File Upload Security**: Only image files allowed, size limits enforced
- **Session Security**: Secure session handling with timeout
- **Directory Protection**: Sensitive directories blocked via .htaccess
- **HTTPS Ready**: Configured for SSL certificate

## Backup and Maintenance

### Database Backups
- Use the built-in backup tool (`backup.php`)
- Backups are stored in the `backups/` directory
- Download backups regularly for safekeeping

### Regular Maintenance
- Monitor error logs
- Keep PHP version updated
- Regular database backups
- Clean old uploaded files periodically

## Customization

### Styling
- Modify Bootstrap classes in PHP files
- Add custom CSS in the `<head>` section
- Update company logo and colors

### Functionality
- Add new fields to forms
- Modify PDF templates in download scripts
- Extend database structure as needed

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Verify database exists and user has permissions

2. **Upload Errors**
   - Check `uploads/` directory permissions (755 or 777)
   - Verify PHP upload settings in hosting control panel

3. **PDF Download Not Working**
   - Ensure TCPDF is installed correctly
   - Check file permissions
   - Verify PHP memory limits

4. **Session Issues**
   - Clear browser cache and cookies
   - Check PHP session settings
   - Verify session storage permissions

### Error Logs
- Check your hosting provider's error logs
- Enable PHP error reporting for debugging
- Monitor application-specific errors

## Support

For issues and questions:
1. Check the troubleshooting section above
2. Review the deployment guide
3. Verify all requirements are met
4. Check hosting provider documentation

## License

This project is open source and available under the MIT License.

## Changelog

### Version 1.0
- Initial release
- Complete invoicing and offers system
- User authentication
- PDF generation
- Database backup functionality
- Responsive design
- Security features

## Contributing

Feel free to submit issues, feature requests, or pull requests to improve the system.

---

**Note**: This system is designed for single-user or small business use. For multi-user environments, additional security and user management features would be needed. 