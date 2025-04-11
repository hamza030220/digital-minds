# Green Admin MVC - Quick Start Guide

This guide provides the quickest way to get your Green Admin MVC application up and running.

## Prerequisites
- XAMPP installed
- Apache and MySQL services running

## One-Click Setup

For the fastest setup experience, follow these steps:

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

2. **Run Auto-Setup**
   - Open your browser
   - Visit: [http://localhost/green-admin-mvc/auto-setup.php](http://localhost/green-admin-mvc/auto-setup.php)
   - This script will:
     * Create the database if needed
     * Import all required SQL files
     * Set up the admin user
     * Verify database connectivity

3. **Access Your Application**
   - After auto-setup completes, click the "Go to Application" button
   - Or visit: [http://localhost/green-admin-mvc/](http://localhost/green-admin-mvc/)
   - Login with:
     * Username: `admin`
     * Password: `admin123`

## Verification URLs

- **Structure Check**: [http://localhost/green-admin-mvc/structure-check.php](http://localhost/green-admin-mvc/structure-check.php)
- **Database Test**: [http://localhost/green-admin-mvc/db-test.php](http://localhost/green-admin-mvc/db-test.php)
- **Auto Setup**: [http://localhost/green-admin-mvc/auto-setup.php](http://localhost/green-admin-mvc/auto-setup.php)
- **Deployment Checklist**: [http://localhost/green-admin-mvc/deploy-checklist.php](http://localhost/green-admin-mvc/deploy-checklist.php)
- **Main Application**: [http://localhost/green-admin-mvc/](http://localhost/green-admin-mvc/)

## Testing the Application

1. **Create a Station**
   - Navigate to Stations → Add New
   - Enter a name and location
   - Set status to "active"
   - Click Save

2. **Create a Trajet (Route)**
   - Navigate to Trajets → Add New
   - Select start and end stations
   - Enter distance and description
   - Add route coordinates (optional)
   - Click Save

3. **Edit and Delete**
   - Use the action buttons in the list views to edit or delete records
   - Confirm that changes are saved correctly

## Security Note

For security in production environments:
- Change the default admin password immediately
- Remove or secure the test scripts (auto-setup.php, db-test.php, etc.)
- Set ENVIRONMENT to 'production' in config.php

Enjoy your new Green Admin MVC application!

