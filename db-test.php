<?php
/**
 * Database Test Script for Green Admin MVC
 * 
 * This script tests the database connection and verifies tables exist
 * Run this script directly in the browser at:
 * http://localhost/green-admin-mvc/db-test.php
 */

// Define base path
define('BASE_PATH', __DIR__);

// Include configuration
require_once BASE_PATH . '/includes/config.php';

// HTML header for nicer output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Green Admin MVC - Database Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #60BA97; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .container { max-width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .section { border-bottom: 1px solid #ddd; margin-bottom: 20px; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Green Admin MVC - Database Connection Test</h1>";

// Test database connection
echo "<div class='section'><h2>1. Database Connection</h2>";

try {
    $pdo = getDBConnection();
    echo "<p class='success'>✓ Successfully connected to database: " . DB_NAME . "</p>";
    
    // Get database information
    $stmt = $pdo->query("SELECT version() as version");
    $version = $stmt->fetch()['version'];
    echo "<p>MySQL Version: $version</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ ERROR: Could not connect to database: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in includes/config.php</p>";
    
    echo "<p>Current settings:</p>";
    echo "<ul>";
    echo "<li>DB_HOST: " . DB_HOST . "</li>";
    echo "<li>DB_NAME: " . DB_NAME . "</li>";
    echo "<li>DB_USER: " . DB_USER . "</li>";
    echo "<li>DB_PASS: " . (empty(DB_PASS) ? "(empty)" : "(set)") . "</li>";
    echo "</ul>";
    
    echo "</div></div></body></html>";
    exit;
}

// Check for required tables
echo "</div><div class='section'><h2>2. Table Structure</h2>";

$requiredTables = ['users', 'password_resets', 'stations', 'trajets'];

try {
    // Get all tables in the database
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table>
        <tr>
            <th>Table Name</th>
            <th>Status</th>
            <th>Records</th>
        </tr>";
    
    $missingTables = [];
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $existingTables);
        $countRecords = 0;
        
        if ($exists) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $countRecords = $stmt->fetchColumn();
        } else {
            $missingTables[] = $table;
        }
        
        echo "<tr>
            <td>$table</td>
            <td>" . ($exists ? "<span class='success'>✓ Exists</span>" : "<span class='error'>✗ Missing</span>") . "</td>
            <td>" . ($exists ? $countRecords : "N/A") . "</td>
        </tr>";
    }
    echo "</table>";
    
    if (count($missingTables) > 0) {
        echo "<p class='error'>Missing tables: " . implode(", ", $missingTables) . "</p>";
        echo "<p>Please import the required SQL files:</p>";
        echo "<ol>";
        echo "<li>database/auth.sql - Creates users and password_resets tables</li>";
        echo "<li>database/database.sql - Creates stations and trajets tables</li>";
        echo "<li>database/update_trajets_table.sql - Updates trajets table schema</li>";
        echo "</ol>";
    } else {
        echo "<p class='success'>✓ All required tables exist</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ ERROR: " . $e->getMessage() . "</p>";
}

// Check user accounts (if users table exists)
echo "</div><div class='section'><h2>3. User Accounts</h2>";

if (in_array('users', $existingTables)) {
    try {
        $stmt = $pdo->query("SELECT username, role FROM users");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            echo "<p class='success'>✓ " . count($users) . " user account(s) found</p>";
            echo "<table>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                </tr>";
            
            $adminFound = false;
            foreach ($users as $user) {
                echo "<tr>
                    <td>" . $user['username'] . "</td>
                    <td>" . $user['role'] . "</td>
                </tr>";
                
                if ($user['username'] === 'admin') {
                    $adminFound = true;
                }
            }
            echo "</table>";
            
            if (!$adminFound) {
                echo "<p class='warning'>⚠ No admin user found. You may want to add one.</p>";
            }
        } else {
            echo "<p class='error'>✗ No user accounts found in the database</p>";
            echo "<p>Import the auth.sql file or create an admin user:</p>";
            echo "<pre>
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin');
            </pre>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>✗ ERROR: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>✗ Users table not found - cannot check user accounts</p>";
}

// Next steps
echo "</div><div class='section'><h2>Next Steps</h2>";

if (count($missingTables) > 0) {
    echo "<p>Please create the missing tables before continuing.</p>";
} else {
    echo "<p class='success'>Your database appears to be set up correctly!</p>";
    echo "<p>You can now:</p>";
    echo "<ol>";
    echo "<li>Access your application at <a href='http://greenadmin.local'>http://greenadmin.local</a> or <a href='/green-admin-mvc/'>http://localhost/green-admin-mvc/</a></li>";
    echo "<li>Login with the admin account</li>";
    echo "<li>Start adding stations and routes</li>";
    echo "</ol>";
}

// Close the database connection
$pdo = null;

echo "</div></div></body></html>";

