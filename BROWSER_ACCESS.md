# Accessing Green Admin MVC in Your Browser

This guide provides step-by-step instructions for accessing and using your Green Admin MVC application.

## Prerequisites

- XAMPP installed on your computer
- Green Admin MVC files in the correct directory (C:\xampp\htdocs\green-admin-mvc\)
- Basic understanding of web applications

## Step 1: Start XAMPP Services

1. Open XAMPP Control Panel:
   - Start menu → XAMPP → XAMPP Control Panel
   - Or navigate to C:\xampp and run `xampp-control.exe`

2. Start Apache and MySQL services:
   - Click the "Start" button next to Apache
   - Click the "Start" button next to MySQL
   - Both services should show a green background when running

## Step 2: Set Up the Database

1. Open phpMyAdmin:
   - In your browser, navigate to http://localhost/phpmyadmin/
   
2. Create a new database:
   - Click "New" in the left sidebar
   - Enter "green_db" as the database name
   - Select "utf8mb4_unicode_ci" as the collation
   - Click "Create"

3. Import the database structure:
   - Select the "green_db" database from the left sidebar
   - Click the "Import" tab at the top
   - Click "Browse" and navigate to:
     * C:\xampp\htdocs\green-admin-mvc\database\auth.sql
   - Scroll down and click "Go"
   - Repeat this process for:
     * C:\xampp\htdocs\green-admin-mvc\database\database.sql
     * C:\xampp\htdocs\green-admin-mvc\database\update_trajets_table.sql

## Step 3: Access the Application

1. Open your web browser (Chrome, Firefox, Edge, etc.)

2. Navigate to the application URL:
   ```
   http://localhost/green-admin-mvc/
   ```

3. You should be redirected to the login page:
   ```
   http://localhost/green-admin-mvc/login
   ```

4. Log in with the default credentials:
   - Username: `admin`
   - Password: `admin123`

5. After successful login, you'll be redirected to the dashboard

## Step 4: Test the Application

1. Navigate through the main sections:
   - Dashboard: Overview statistics
   - Stations: Manage station data
   - Trajets: Manage route information

2. Try creating a new station:
   - Go to Stations → Add New Station
   - Fill in the form and submit
   - Verify the station appears in the listing

## Troubleshooting

If you encounter issues accessing the application:

1. **Apache or MySQL won't start**:
   - Check if another program is using ports 80 (Apache) or 3306 (MySQL)
   - Look in the XAMPP logs for specific error messages

2. **"Not Found" or 404 error**:
   - Verify that all files are in the correct location (C:\xampp\htdocs\green-admin-mvc\)
   - Check that the .htaccess file exists in the root directory
   - Make sure mod_rewrite is enabled in Apache

3. **Database connection error**:
   - Check that MySQL is running
   - Verify that database credentials in `includes/config.php` match your setup
   - Ensure the green_db database exists

4. **Login issues**:
   - Verify that the auth.sql file was imported correctly
   - Check that the users table exists and contains an admin user

## Advanced: Running the Test and Deployment Scripts

For testing and verifying your installation:

1. Run the test script:
   ```
   http://localhost/green-admin-mvc/test.php
   ```

2. Run the deployment checklist:
   ```
   http://localhost/green-admin-mvc/deploy-checklist.php
   ```

> **Note**: In a production environment, these scripts should be secured or removed.

## Next Steps

After successful access:

1. Change the default admin password immediately
2. Update the configuration in `includes/config.php` if needed
3. Add your own stations and routes
4. Customize the UI if desired by modifying CSS files

