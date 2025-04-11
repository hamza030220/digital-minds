# phpMyAdmin Database Setup Guide

This guide provides detailed steps with visual descriptions to help you set up the database for Green Admin MVC.

## Step 1: Access phpMyAdmin

1. **Start XAMPP Services**
   - Make sure Apache and MySQL are running (green background in XAMPP Control Panel)
   - If not running, click the "Start" buttons next to each service

2. **Open phpMyAdmin**
   - Open your web browser
   - Navigate to: http://localhost/phpmyadmin/
   - You should see the phpMyAdmin interface
   
   *The phpMyAdmin interface has a dark blue header, navigation sidebar on the left, and main content area.*

## Step 2: Create the Database

1. **Click "New" in Left Sidebar**
   - Look for "New" link in the left sidebar
   - It's typically near the top of the sidebar
   
   *New link appears as text with a + icon next to it.*

2. **Enter Database Details**
   - In the "Create database" field, type: `green_db`
   - From the collation dropdown, select: `utf8mb4_unicode_ci`
   - Click the "Create" button
   
   *The create database form has a text field and a dropdown menu below it.*

3. **Verify Database Creation**
   - After creation, you should see the new database in the left sidebar
   - The main content area will show the empty database structure
   
   *Your new "green_db" database will appear in the left sidebar list.*

## Step 3: Import SQL Files

### First File - Auth Schema

1. **Select the Database**
   - Click on `green_db` in the left sidebar
   
2. **Open Import Tab**
   - Click the "Import" tab in the top navigation menu
   - It's located between "Export" and "Settings"
   
   *The Import tab has a file upload form and import options.*

3. **Select the File**
   - Click the "Browse" or "Choose File" button
   - Navigate to: `C:\xampp\htdocs\green-admin-mvc\database\auth.sql`
   - Select the file and click "Open"
   
4. **Import the File**
   - Leave default settings as they are
   - Scroll down and click the "Go" or "Import" button at the bottom
   
   *After successful import, you should see a success message.*

### Second File - Main Database Schema

1. **Repeat the Import Process**
   - Make sure `green_db` is still selected
   - Click the "Import" tab again
   
2. **Select the Second File**
   - Click "Browse" or "Choose File"
   - Navigate to: `C:\xampp\htdocs\green-admin-mvc\database\database.sql`
   - Select and open the file
   
3. **Import the File**
   - Click the "Go" or "Import" button at the bottom
   
   *This will create the stations and trajets tables.*

### Third File - Schema Update

1. **Repeat the Import Process Again**
   - Click the "Import" tab once more
   
2. **Select the Third File**
   - Browse to: `C:\xampp\htdocs\green-admin-mvc\database\update_trajets_table.sql`
   - Select and open the file
   
3. **Import the File**
   - Click the "Go" or "Import" button
   
   *This updates the trajets table with additional columns.*

## Step 4: Verify Database Structure

1. **View Database Structure**
   - Click on `green_db` in the left sidebar
   - You should see a list of tables in the database
   
2. **Check Tables**
   - You should see the following tables:
     * `users`
     * `password_resets`
     * `stations`
     * `trajets`
   
   *The tables are listed with information about rows, storage, and other details.*

3. **Verify Admin User**
   - Click on the `users` table
   - Click the "Browse" tab to view records
   - Verify there's a user with username "admin"
   
   *The admin user should have the role set to "admin".*

## Step 5: Run the Database Test Script

1. **Access the Test Script**
   - Open your browser
   - Navigate to: http://localhost/green-admin-mvc/db-test.php
   
2. **Check Test Results**
   - The page will display test results for:
     * Database connection
     * Table structure
     * User accounts
   
   *All tests should show green checkmarks if successful.*

## Troubleshooting

### Common Issues:

1. **"Access denied" Error**
   - Check your MySQL username and password in `includes/config.php`
   - Default XAMPP settings are username "root" with empty password

2. **"Table already exists" Error**
   - You may have already imported some of the files
   - You can continue with the remaining files

3. **No Tables Showing After Import**
   - Make sure you selected `green_db` before importing
   - Check for error messages during import
   - Try reimporting the files

4. **Connection Failed**
   - Ensure MySQL is running in XAMPP Control Panel
   - Check your database credentials in config.php
   - Make sure the database name is correct

If you continue to experience issues, check the XAMPP error logs or run the diagnostic script.

