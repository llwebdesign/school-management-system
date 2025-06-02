# School Management System

A PHP-based school management system similar to aula.dk with features for messages, file sharing, news, and calendar management.

## Features

- **Messages System**: Send and receive messages between users
- **File Sharing**: Upload, download, and manage files
- **News**: Post and view school announcements
- **Calendar**: Manage and view school events
- **User Authentication**: Secure login system with admin privileges

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Installation

1. **Set up the database:**
   ```bash
   mysql -u root -p < schema.sql
   ```

2. **Configure the database connection:**
   - Open `config.php`
   - Update the database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'school_system');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

3. **Set up file permissions:**
   ```bash
   chmod 777 uploads/
   ```

4. **Default Admin Login:**
   - Username: admin
   - Password: admin123

## Directory Structure

```
/
├── assets/
│   └── css/
│       └── styles.css
├── uploads/              # File storage directory
├── config.php           # Configuration settings
├── db.php              # Database connection
├── index.php           # Login page
├── dashboard.php       # Main dashboard
├── messages.php        # Messages system
├── filesharing.php     # File sharing system
├── news.php           # News system
├── calendar.php       # Calendar system
└── schema.sql         # Database schema
```

## Usage

1. **Login:**
   - Access the system through `index.php`
   - Use provided admin credentials or create new user accounts

2. **Messages:**
   - Click "Messages" in navigation
   - Use "Compose" to create new messages
   - View inbox/sent messages

3. **File Sharing:**
   - Upload files through file sharing section
   - Download shared files
   - Manage your uploaded files

4. **News:**
   - View school announcements
   - Admin users can create/edit news

5. **Calendar:**
   - View school events
   - Add new events (if authorized)
   - Filter events by date

## Security Features

- Password hashing using bcrypt
- Session-based authentication
- SQL injection prevention using PDO
- XSS protection through output escaping
- File upload validation

## Maintenance

- Regularly backup the database
- Monitor the uploads directory size
- Update user passwords periodically
- Check error logs for issues

## Troubleshooting

1. **Database Connection Issues:**
   - Verify database credentials in config.php
   - Ensure MySQL service is running
   - Check database user permissions

2. **File Upload Problems:**
   - Verify uploads directory permissions
   - Check PHP upload size limits in php.ini
   - Ensure proper file ownership

3. **Session Issues:**
   - Check PHP session configuration
   - Clear browser cookies
   - Verify session directory permissions

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.
