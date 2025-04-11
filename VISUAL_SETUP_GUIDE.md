# Visual Setup Guide for Green Admin MVC

This step-by-step guide includes visual descriptions to help you get your Green Admin MVC application up and running.

## Step 1: Start XAMPP Services

1. **Open XAMPP Control Panel**
   - Navigate to your XAMPP installation folder (typically C:\xampp)
   - Find and double-click on `xampp-control.exe`
   - You should see a control panel with modules listed (Apache, MySQL, etc.)

   ![XAMPP Control Panel Description]
   *The XAMPP Control Panel shows modules like Apache and MySQL with Start/Stop buttons*

2. **Start Required Services**
   - Find the row for "Apache" and click the "Start" button
   - Find the row for "MySQL" and click the "Start" button
   - When running, both services will show:
     * A green background or indicator
     * "Running" status text
     * The ports they're using (typically 80, 443 for Apache and 3306 for MySQL)

   ![Services Running Description]
   *When services are running correctly, they'll have a green background with "Running" status*

## Step 2: Create and Set Up the Database

1. **Access phpMyAdmin**
   - Open your web browser
   - Enter the URL: `http://localhost/phpmyadmin`
   - You should see the phpMyAdmin interface with a sidebar on the left
   
   ![phpMyAdmin Description]
   *The phpMyAdmin interface has a dark sidebar on the left and main content area*

2. **Create the Database**
   - In the left sidebar, look for "New" or a "+" icon
   - In the "Create database" form:
     * Enter `green_db` as the database name
     * Select `utf8mb4_unicode_ci` from the collation dropdown
     * Click the "Create" button
   
   ![Create Database Description]
   *Enter "green_db" in the database name field and select utf8mb4_unicode_ci from the dropdown*

3. **Import Database Structure**
   - In the top menu bar, click the "Import" tab
   - Click the "Browse" button to open the file selector
   - Navigate to `C:\xampp\htdocs\green-admin-mvc\database\`
   - Select `auth.sql` and click "Open"
   - Scroll down and click the "Go" or "Import" button
   - Repeat these steps for `database.sql` and `update_trajets_table.sql`
   
   ![Import SQL Description]
   *On the Import tab, use the Browse button to select SQL files and click Go to import them*

4. **Verify Database Setup**
   - In the left sidebar, click on "green_db"
   - You should see several tables listed:
     * users
     * password_resets
     * stations
     * trajets
   
   ![Database Tables Description]
   *After successful import, you'll see all tables listed when clicking on the green_db database*

## Step 3: Access the Application

1. **Open Your Web Browser**
   - Launch any modern web browser (Chrome, Firefox, Edge, etc.)

2. **Navigate to the Application URL**
   - In the address bar, enter: `http://localhost/green-admin-mvc/`
   - You should be redirected to the login page
   
   ![Login Page Description]
   *The login page has Green Admin branding, username and password fields, and a login button*

3. **Log In with Default Credentials**
   - Enter the following credentials:
     * Username: `admin`
     * Password: `admin123`
   - Click the "Login" button
   
   ![Login Credentials Description]
   *Enter "admin" as username and "admin123" as password in the respective fields*

4. **Dashboard Access**
   - After successful login, you'll see the dashboard
   - The dashboard includes:
     * A green navigation bar at the top
     * Statistics cards for stations and routes
     * Quick action links
   
   ![Dashboard Description]
   *The dashboard has a green header, statistics cards, and navigation options*

## Step 4: Troubleshooting Common Issues

### Issue 1: XAMPP Services Won't Start

**Symptoms:**
- Error messages when starting Apache or MySQL
- Services immediately stop after starting
- Port conflict warnings

**Solutions:**
1. **Check for port conflicts:**
   - In XAMPP Control Panel, click "Config" for the problematic service
   - Look for the port configuration (httpd.conf for Apache, my.ini for MySQL)
   - Change the ports if needed (Apache typically uses 80 and 443, MySQL uses 3306)
   
2. **Check Windows Services:**
   - Some Windows services like IIS or SQL Server might use the same ports
   - Open Windows Services (services.msc) and check for these services
   - Temporarily stop them or configure XAMPP to use different ports

### Issue 2: Database Connection Errors

**Symptoms:**
- "Database connection failed" error messages
- Application shows database errors after login

**Solutions:**
1. **Verify Database Credentials:**
   - Open `C:\xampp\htdocs\green-admin-mvc\includes\config.php`
   - Check that database settings match your environment:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'green_db');
     define('DB_USER', 'root');  // Default XAMPP MySQL username
     define('DB_PASS', '');      // Default XAMPP MySQL password (empty)
     ```

2. **Check MySQL Service:**
   - Ensure MySQL is running in XAMPP Control Panel
   - Try restarting the MySQL service

### Issue 3: 404 Not Found Errors

**Symptoms:**
- "Page not found" errors when accessing the application
- 404 error page displayed

**Solutions:**
1. **Check .htaccess Configuration:**
   - Ensure `C:\xampp\htdocs\green-admin-mvc\.htaccess` exists and is correct
   - Verify mod_rewrite is enabled in Apache

2. **Enable mod_rewrite:**
   - In XAMPP Control Panel, click "Config" for Apache
   - Select "Apache (httpd.conf)"
   - Find and uncomment the line: `LoadModule rewrite_module modules/mod_rewrite.so`
   - Save and restart Apache

## Next Steps

After successfully accessing the application:

1. **Change Default Password:**
   - Go to your user profile or settings
   - Change the default admin password immediately for security

2. **Create Sample Data:**
   - Add stations from the Stations menu
   - Add routes from the Trajets menu
   - Use the test.php script to generate sample data

3. **Explore Features:**
   - Try out all the CRUD operations
   - Test the search and filter functionality
   - Experiment with route coordinates

