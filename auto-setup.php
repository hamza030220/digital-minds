<?php
/**
 * Auto-Setup Script for Green Admin MVC
 * 
 * This script automatically:
 * 1. Checks database connection
 * 2. Creates database if missing
 * 3. Imports required SQL files
 * 4. Verifies admin user exists
 * 5. Generates a setup report
 */

// Define base path
define('BASE_PATH', __DIR__);

// Include configuration
require_once BASE_PATH . '/includes/config.php';

// Start output buffering
ob_start();

// HTML header
echo "<!DOCTYPE html>
<html>
<head>
    <title>Green Admin MVC - Auto Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #60BA97; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .step { border-bottom: 1px solid #ddd; margin-bottom: 20px; padding-bottom: 10px; }
        .btn { 
            display: inline-block; 
            padding: 8px 16px; 
            background-color: #60BA97; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background-color: #4a9077; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Green Admin MVC - Auto Setup</h1>
        <p>This script will automatically set up your Green Admin application.</p>";

// Function to log status
function log_status($message, $status = 'info') {
    $class = ($status == 'success') ? 'success' : (($status == 'error') ? 'error' : (($status == 'warning') ? 'warning' : ''));
    echo "<p class='$class'>$message</p>";
}

// Step 1: Check MySQL connection
echo "<div class='step'><h2>Step 1: Check MySQL Connection</h2>";

try {
    // Try connecting without database
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    log_status("✓ Successfully connected to MySQL", "success");
    
    // Get MySQL version
    $stmt = $pdo->query("SELECT version() as version");
    $version = $stmt->fetch()['version'];
    echo "<p>MySQL Version: $version</p>";
    
} catch (PDOException $e) {
    log_status("✗ Failed to connect to MySQL: " . $e->getMessage(), "error");
    echo "</div></div></body></html>";
    exit;
}

echo "</div>";

// Step 2: Check/Create Database
echo "<div class='step'><h2>Step 2: Check/Create Database</h2>";

try {
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $dbExists = $stmt->rowCount() > 0;
    
    if ($dbExists) {
        log_status("✓ Database '" . DB_NAME . "' already exists", "success");
    } else {
        // Create database
        $createSql = "CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($createSql);
        log_status("✓ Created database '" . DB_NAME . "'", "success");
    }
    
    // Connect to the specific database
    $pdo = getDBConnection();
    log_status("✓ Connected to database '" . DB_NAME . "'", "success");
    
} catch (PDOException $e) {
    log_status("✗ Database error: " . $e->getMessage(), "error");
    echo "</div></div></body></html>";
    exit;
}

echo "</div>";

// Step 3: Import SQL Files
echo "<div class='step'><h2>Step 3: Import SQL Files</h2>";

try {
    // Array of SQL files to import
    $sqlFiles = [
        'auth.sql' => BASE_PATH . '/database/auth.sql',
        'database.sql' => BASE_PATH . '/database/database.sql',
        'update_trajets_table.sql' => BASE_PATH . '/database/update_trajets_table.sql'
    ];
    
    // Get existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($sqlFiles as $name => $path) {
        echo "<h3>Importing $name</h3>";
        
        if (!file_exists($path)) {
            log_status("✗ File not found: $path", "error");
            continue;
        }
        
        // Read SQL file
        $sql = file_get_contents($path);
        
        // Split SQL file into individual statements
        $statements = array_filter(
            array_map(
                'trim',
                explode(';', $sql)
            )
        );
        
        // Execute each statement
        $successCount = 0;
        $totalStatements = count($statements);
        
        foreach ($statements as $statement) {
            if (empty($statement)) continue;
            
            try {
                $pdo->exec($statement . ';');
                $successCount++;
            } catch (PDOException $e) {
                // Ignore "already exists" errors, log others
                if (strpos($e->getMessage(), 'already exists') === false) {
                    log_status("Statement error: " . $e->getMessage(), "warning");
                }
            }
        }
        
        log_status("✓ Imported $successCount/$totalStatements statements from $name", "success");
    }
    
    // Get tables after import
    $stmt = $pdo->query("SHOW TABLES");
    $currentTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredTables = ['users', 'password_resets', 'stations', 'trajets'];
    $missingTables = array_diff($requiredTables, $currentTables);
    
    if (empty($missingTables)) {
        log_status("✓ All required tables exist", "success");
    } else {
        log_status("✗ Missing tables: " . implode(', ', $missingTables), "error");
    }
    
} catch (Exception $e) {
    log_status("✗ Import error: " . $e->getMessage(), "error");
}

echo "</div>";

// Step 4: Check/Create Admin User
echo "<div class='step'><h2>Step 4: Check/Create Admin User</h2>";

try {
    // Check if users table exists
    if (in_array('users', $currentTables)) {
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        $adminExists = $stmt->fetchColumn() > 0;
        
        if ($adminExists) {
            log_status("✓ Admin user exists", "success");
        } else {
            // Create admin user
            log_status("Admin user not found, creating one...", "warning");
            
            $password = 'admin123';
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES ('admin', 'admin@example.com', ?, 'admin')
            ");
            $stmt->bindValue(1, $hashedPassword, PDO::PARAM_STR);
            $stmt->execute();
            
            log_status("✓ Created admin user (username: admin, password: admin123)", "success");
        }
    } else {
        log_status("✗ Users table does not exist", "error");
    }
} catch (Exception $e) {
    log_status("✗ Admin user error: " . $e->getMessage(), "error");
}

echo "</div>";

// Summary and next steps
echo "<div class='step'><h2>Setup Summary</h2>";

echo "<h3>Database Status</h3>";
try {
    // Check required tables
    $requiredTables = ['users', 'password_resets', 'stations', 'trajets'];
    
    echo "<ul>";
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $currentTables);
        echo "<li>" . ($exists ? "✓" : "✗") . " $table " . ($exists ? "exists" : "missing") . "</li>";
    }
    echo "</ul>";
    
    // Count records
    if (in_array('users', $currentTables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        echo "<p>User accounts: $userCount</p>";
    }
    
    if (in_array('stations', $currentTables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM stations");
        $stationCount = $stmt->fetchColumn();
        echo "<p>Stations: $stationCount</p>";
    }
    
    if (in_array('trajets', $currentTables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM trajets");
        $trajetCount = $stmt->fetchColumn();
        echo "<p>Trajets: $trajetCount</p>";
    }
} catch (Exception $e) {
    log_status("Error checking statistics: " . $e->getMessage(), "error");
}

echo "<h3>Next Steps</h3>";
echo "<ol>
    <li>Visit <a href='/green-admin-mvc/'>http://localhost/green-admin-mvc/</a> to access your application</li>
    <li>Login with username <strong>admin</strong> and password <strong>admin123</strong></li>
    <li>Change the default admin password immediately</li>
    <li>Start using your Green Admin MVC application!</li>
</ol>";

echo "<p><a href='/green-admin-mvc/' class='btn'>Go to Application</a></p>";

echo "</div></div></body></html>";

// Output the buffer
ob_end_flush();

