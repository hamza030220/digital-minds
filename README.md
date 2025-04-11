# Green Admin MVC

A PHP-based admin panel for managing stations and routes with an MVC architecture.

## Table of Contents
- [Overview](#overview)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Authentication Setup](#authentication-setup)
- [File Structure](#file-structure)
- [Security Considerations](#security-considerations)
- [Configuration](#configuration)
- [Development Guidelines](#development-guidelines)

## Overview

Green Admin MVC is a complete management system for transportation stations and routes. It features:

- MVC architecture for clean code separation
- Station management (CRUD operations)
- Route management with coordinates and descriptions
- User authentication and authorization
- Responsive UI with Bootstrap

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server
- mod_rewrite enabled

### Step-by-Step Installation

1. **Clone or download the repository**
   ```
   git clone https://your-repository-url/green-admin-mvc.git
   ```
   or extract the ZIP archive to your web server's document root.

2. **Configure Apache**
   
   Ensure your virtual host configuration has mod_rewrite enabled:
   ```apache
   <VirtualHost *:80>
       ServerName greenadmin.local
       DocumentRoot /path/to/green-admin-mvc
       
       <Directory /path/to/green-admin-mvc>
           Options Indexes FollowSymLinks MultiViews
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Create a .htaccess file in the root directory**
   ```apache
   RewriteEngine On
   RewriteBase /green-admin-mvc/
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ public/index.php [QSA,L]
   ```

4. **Set file permissions**
   ```
   chmod 755 -R /path/to/green-admin-mvc
   chmod 777 -R /path/to/green-admin-mvc/public/uploads (if using file uploads)
   ```

5. **Update configuration**
   
   Edit `includes/config.php` to set your database connection parameters.

## Database Setup

1. **Create a new database**
   ```sql
   CREATE DATABASE green_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Set up the tables**
   
   Execute the SQL files in the following order:
   ```bash
   mysql -u username -p green_db < database/auth.sql
   mysql -u username -p green_db < database/database.sql
   mysql -u username -p green_db < database/update_trajets_table.sql
   ```

3. **Verify the tables**
   
   Ensure the following tables have been created:
   - users
   - password_resets
   - stations
   - trajets

## Authentication Setup

1. **Default admin account**
   
   The system comes with a default admin account:
   - Username: `admin`
   - Password: `admin123`
   
   **Important:** Change the default password immediately after installation.

2. **Adding new users**
   
   New users can be added directly to the `users` table with properly hashed passwords:
   ```php
   // Example PHP code for adding a user
   $hashedPassword = password_hash('newpassword', PASSWORD_DEFAULT);
   $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
   ```

3. **Password reset**
   
   The password reset functionality uses the `password_resets` table to store tokens.

## File Structure

```
green-admin-mvc/
│
├── assets/                 # CSS, JS, and images
│   ├── css/
│   ├── js/
│   └── images/
│
├── controllers/            # Controller classes
│   ├── authController.php
│   ├── stationController.php
│   └── trajetController.php
│
├── database/               # SQL files and database utilities
│   ├── auth.sql
│   ├── database.sql
│   └── update_trajets_table.sql
│
├── includes/               # Core application files
│   ├── config.php          # Configuration
│   └── Controller.php      # Base controller class
│
├── models/                 # Model classes
│   ├── StationModel.php
│   ├── TrajetModel.php
│   └── UserModel.php
│
├── public/                 # Publicly accessible files
│   ├── index.php           # Front controller
│   └── uploads/            # User-uploaded files
│
├── views/                  # View templates
│   ├── stations/
│   ├── trajets/
│   ├── 404.php
│   ├── 500.php
│   └── login.php
│
├── .htaccess               # Apache configuration
└── README.md               # This file
```

## Security Considerations

1. **File Permissions**
   - Set restrictive file permissions (755 for directories, 644 for files)
   - Make sure configuration files with sensitive data are not web-accessible

2. **Password Security**
   - Passwords are hashed using PHP's password_hash() function
   - Always use strong passwords
   - The system enforces password complexity requirements

3. **Session Security**
   - Sessions are configured with HttpOnly, SameSite, and secure flags
   - Session timeout is set to 30 minutes of inactivity
   - CSRF protection is implemented using tokens

4. **Database Security**
   - All database queries use prepared statements
   - Error messages do not expose database details
   - Connection details are stored securely in config.php

5. **Input Validation**
   - All user inputs are sanitized before processing
   - Form submissions include CSRF tokens
   - Input validation is performed on both client and server sides

## Configuration

The main configuration file is `includes/config.php`. You need to update the following:

1. **Database Configuration**
   ```php
   define('DB_HOST', 'localhost');      // Database host
   define('DB_NAME', 'green_db');       // Database name
   define('DB_USER', 'username');       // Database username
   define('DB_PASS', 'password');       // Database password
   define('DB_CHARSET', 'utf8mb4');     // Database charset
   ```

2. **Application Configuration**
   ```php
   define('ENVIRONMENT', 'development'); // Set to 'production' for production use
   define('SITE_URL', 'http://localhost/green-admin-mvc'); // Base URL
   define('UPLOAD_DIR', '/path/to/uploads'); // Upload directory
   ```

3. **Email Configuration (for password reset)**
   ```php
   define('MAIL_HOST', 'smtp.example.com');
   define('MAIL_USERNAME', 'username');
   define('MAIL_PASSWORD', 'password');
   define('MAIL_FROM', 'noreply@example.com');
   ```

## Development Guidelines

1. **MVC Pattern**
   - Controllers handle request processing and data manipulation
   - Models encapsulate database operations and business logic
   - Views are responsible only for presentation

2. **Coding Standards**
   - Follow PSR-12 coding standards
   - Use meaningful variable and function names
   - Comment your code adequately

3. **Error Handling**
   - Use try-catch blocks for error handling
   - Log errors to files instead of displaying them in production
   - Display user-friendly error messages

4. **Database Operations**
   - Use prepared statements for all database operations
   - Implement proper transaction handling for critical operations
   - Keep database operations in model classes only

5. **Adding New Features**
   - Create appropriate model classes for new data entities
   - Implement controllers for handling operations
   - Add views for user interaction
   - Update routing in the front controller if needed
