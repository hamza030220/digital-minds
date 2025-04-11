<?php
/**
 * Green Admin MVC - Deployment Checklist
 * 
 * This script performs pre-deployment checks to ensure the system is ready for production:
 * 1. Verify environment settings
 * 2. Check security configurations
 * 3. Validate database migrations
 * 4. Test core functionality
 * 5. Generate a deployment report
 * 
 * Important: Run this script before deploying to production.
 * Delete after successful deployment verification.
 */

// Define base path
define('BASE_PATH', __DIR__);

// Start output buffering for cleaner output
ob_start();

// Set a flag to determine if this is running in development or production
$isProduction = false;

// Functions to check file readability/writability
function checkFileReadable($path, $relative = true) {
    $fullPath = $relative ? BASE_PATH . '/' . $path : $path;
    return is_readable($fullPath);
}

function checkFileWritable($path, $relative = true) {
    $fullPath = $relative ? BASE_PATH . '/' . $path : $path;
    return is_writable($fullPath);
}

function getFilePermissions($path, $relative = true) {
    $fullPath = $relative ? BASE_PATH . '/' . $path : $path;
    return substr(sprintf('%o', fileperms($fullPath)), -4);
}

// HTML header for nicer output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Green Admin MVC - Deployment Checklist</title>
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
        .check-section { border-bottom: 1px solid #ddd; margin-bottom: 20px; padding-bottom: 10px; }
        .summary { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .recommendation { background-color: #ffffd0; padding: 10px; border-left: 4px solid #ffcc00; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Green Admin MVC - Deployment Checklist</h1>
        <p>Use this tool to verify your system is ready for production deployment.</p>";

// Track results
$totalChecks = 0;
$passedChecks = 0;
$warnings = 0;
$criticalIssues = 0;
$recommendations = [];

/**
 * Helper function to report check results
 */
function reportCheck($name, $result, $critical = true, $message = '') {
    global $totalChecks, $passedChecks, $warnings, $criticalIssues, $recommendations;
    $totalChecks++;
    
    if ($result === true) {
        $passedChecks++;
        echo "<p><strong>$name:</strong> <span class='success'>PASSED</span>";
    } else if ($result === 'warning') {
        $warnings++;
        echo "<p><strong>$name:</strong> <span class='warning'>WARNING</span>";
        $recommendations[] = "$name: $message";
    } else {
        if ($critical) {
            $criticalIssues++;
            echo "<p><strong>$name:</strong> <span class='error'>FAILED</span>";
            $recommendations[] = "CRITICAL - $name: $message";
        } else {
            $warnings++;
            echo "<p><strong>$name:</strong> <span class='warning'>FAILED</span>";
            $recommendations[] = "$name: $message";
        }
    }
    
    if (!empty($message)) {
        echo " - $message";
    }
    
    echo "</p>";
}

// =============================================
// Section 1: Verify Environment Settings
// =============================================
echo "<div class='check-section'><h2>1. Environment Settings</h2>";

// Check if config.php exists and is readable
$configExists = checkFileReadable('includes/config.php');
reportCheck("Configuration File", $configExists, true, 
            $configExists ? "File exists and is readable" : "includes/config.php does not exist or is not readable");

// If config file exists, include it and check settings
if ($configExists) {
    require_once BASE_PATH . '/includes/config.php';
    
    // Check database configuration
    $dbConfigured = defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS');
    reportCheck("Database Configuration", $dbConfigured, true, 
                $dbConfigured ? "Database config values are defined" : "One or more database configuration constants missing");
    
    // Check environment setting
    $envDefined = defined('ENVIRONMENT');
    $envValue = $envDefined ? ENVIRONMENT : 'undefined';
    $isProduction = $envDefined && ENVIRONMENT === 'production';
    
    if ($envDefined) {
        reportCheck("Environment Setting", true, false, 
                    "Environment is set to '$envValue'");
    } else {
        reportCheck("Environment Setting", 'warning', false, 
                    "ENVIRONMENT constant is not defined, assuming development");
    }
    
    // Check for debug settings in production
    if ($isProduction) {
        $displayErrors = ini_get('display_errors');
        if ($displayErrors == 1) {
            reportCheck("Error Reporting", false, true, 
                        "display_errors is enabled in production");
        } else {
            reportCheck("Error Reporting", true, false, 
                        "display_errors is disabled");
        }
    } else {
        reportCheck("Error Reporting", 'warning', false, 
                    "Not checking error reporting settings in development environment");
    }
}

echo "</div>";

// =============================================
// Section 2: Check Security Configurations
// =============================================
echo "<div class='check-section'><h2>2. Security Configurations</h2>";

// Check .htaccess files
$mainHtaccessExists = checkFileReadable('.htaccess');
reportCheck("Main .htaccess", $mainHtaccessExists, true, 
            $mainHtaccessExists ? "File exists" : "Main .htaccess file is missing");

// Check critical file permissions
$filesToCheck = [
    'includes/config.php' => '0644',
    'public/index.php' => '0644',
    'includes/Controller.php' => '0644'
];

$dirToCheck = [
    'includes' => '0755',
    'models' => '0755',
    'controllers' => '0755',
    'views' => '0755'
];

echo "<h3>File Permissions</h3><table><tr><th>File/Directory</th><th>Current</th><th>Recommended</th><th>Status</th></tr>";

foreach ($filesToCheck as $file => $recommendedPerms) {
    if (file_exists(BASE_PATH . '/' . $file)) {
        $currentPerms = getFilePermissions($file);
        $isOk = ($currentPerms <= $recommendedPerms) || !$isProduction;
        echo "<tr>
            <td>$file</td>
            <td>$currentPerms</td>
            <td>$recommendedPerms</td>
            <td>" . ($isOk ? "<span class='success'>OK</span>" : "<span class='error'>Too permissive</span>") . "</td>
        </tr>";
        
        if (!$isOk) {
            $criticalIssues++;
            $recommendations[] = "CRITICAL - $file has permission $currentPerms, should be $recommendedPerms";
        }
    } else {
        echo "<tr>
            <td>$file</td>
            <td colspan='2'>File does not exist</td>
            <td><span class='error'>Missing</span></td>
        </tr>";
        $criticalIssues++;
        $recommendations[] = "CRITICAL - $file is missing";
    }
}

foreach ($dirToCheck as $dir => $recommendedPerms) {
    if (is_dir(BASE_PATH . '/' . $dir)) {
        $currentPerms = getFilePermissions($dir);
        $isOk = ($currentPerms <= $recommendedPerms) || !$isProduction;
        echo "<tr>
            <td>$dir/</td>
            <td>$currentPerms</td>
            <td>$recommendedPerms</td>
            <td>" . ($isOk ? "<span class='success'>OK</span>" : "<span class='error'>Too permissive</span>") . "</td>
        </tr>";
        
        if (!$isOk) {
            $criticalIssues++;
            $recommendations[] = "CRITICAL - $dir/ has permission $currentPerms, should be $recommendedPerms";
        }
    } else {
        echo "<tr>
            <td>$dir/</td>
            <td colspan='2'>Directory does not exist</td>
            <td><span class='error'>Missing</span></td>
        </tr>";
        $criticalIssues++;
        $recommendations[] = "CRITICAL - $dir/ directory is missing";
    }
}

echo "</table>";

// Check for test.php security
$testPhpExists = file_exists(BASE_PATH . '/test.php');
$testHtaccessExists = file_exists(BASE_PATH . '/test.htaccess') || file_exists(BASE_PATH . '/.htaccess.test');

if ($testPhpExists && $isProduction) {
    reportCheck("test.php security", $testHtaccessExists, true, 
                $testHtaccessExists ? "test.php is protected by .htaccess" : "test.php exists in production without protection");
} else if ($testPhpExists) {
    reportCheck("test.php security", 'warning', false, 
                "test.php exists, ensure it's removed or secured before deployment");
} else {
    reportCheck("test.php security", true, false, "test.php not found");
}

echo "</div>";

// =============================================
// Section 3: Validate Database Migrations
// =============================================
echo "<div class='check-section'><h2>3. Database Migrations</h2>";

// Check if we can connect to the database
try {
    $pdo = getDBConnection();
    reportCheck("Database Connection", true, true, "Successfully connected to database " . DB_NAME);

    // Check required tables
    $requiredTables = ['users', 'password_resets', 'stations', 'trajets'];
    
    // Get all tables in the database
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $existingTables);
        reportCheck("Table: $table", $exists, true, 
                    $exists ? "Table exists" : "Table '$table' does not exist");
        
        if ($exists) {
            // Check key columns in each table
            $stmt = $pdo->query("SHOW COLUMNS FROM $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            switch ($table) {
                case 'users':
                    $hasRequired = in_array('username', $columns) && 
                                   in_array('password', $columns) && 
                                   in_array('email', $columns);
                    reportCheck("$table structure", $hasRequired, true, 
                                $hasRequired ? "Has required columns" : "Missing required columns");
                    break;
                    
                case 'stations':
                    $hasRequired = in_array('name', $columns) && 
                                   in_array('location', $columns) && 
                                   in_array('status', $columns);
                    reportCheck("$table structure", $hasRequired, true, 
                                $hasRequired ? "Has required columns" : "Missing required columns");
                    break;
                    
                case 'trajets':
                    $hasRequired = in_array('start_station_id', $columns) && 
                                   in_array('end_station_id', $columns) && 
                                   in_array('distance', $columns) &&
                                   in_array('route_coordinates', $columns);
                    reportCheck("$table structure", $hasRequired, true, 
                                $hasRequired ? "Has required columns" : "Missing required columns");
                    
                    // Check foreign keys
                    $stmt = $pdo->prepare("
                        SELECT REFERENCED_TABLE_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    $stmt->execute([DB_NAME, $table]);
                    $foreignKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $hasForeignKeys = in_array('stations', $foreignKeys);
                    reportCheck("$table foreign keys", $hasForeignKeys, false, 
                                $hasForeignKeys ? "Foreign keys properly set up" : "Missing foreign key to stations table");
                    break;
            }
        }
    }
    
    // Check for admin user
    if (in_array('users', $existingTables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        reportCheck("Admin user", $adminCount > 0, true, 
                    $adminCount > 0 ? "Admin user(s) found: $adminCount" : "No admin users defined");
                    
        if ($adminCount > 0 && $isProduction) {
            // Check for default admin credentials in production
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM users 
                WHERE username = 'admin' AND 
                      password = '$2y$10$mL7VZpnvOGN3aTvqHEoF9e.Uy4R/qfm3gR67HZgK0C0J8gAFo.xVK'
            ");
            $stmt->execute();
            $defaultAdminCount = $stmt->fetchColumn();
            
            if ($defaultAdminCount > 0) {
                reportCheck("Default admin password", false, true, 
                            "Default admin password is being used in production");
            } else {
                reportCheck("Default admin password", true, false, 
                            "Default admin password has been changed");
            }
        }
    }
} catch (PDOException $e) {
    reportCheck("Database Validation", false, true, "Database connection error: " . $e->getMessage());
}

echo "</div>";

// =============================================
// Section 4: Test Core Functionality
// =============================================
echo "<div class='check-section'><h2>4. Test Core Functionality</h2>";

// Test authentication
try {
    echo "<h3>Authentication System</h3>";
    
    // Include UserModel if not already included
    if (!class_exists('UserModel')) {
        if (file_exists(BASE_PATH . '/models/UserModel.php')) {
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            
            // Test user retrieval
            $testUser = $userModel->getByUsername('admin');
            reportCheck("User Retrieval", $testUser !== false, false,
                        $testUser !== false ? "User retrieval functions are working" : "Failed to retrieve user: " . $userModel->getError());
            
            // Only test authentication if not in production to avoid security concerns
            if (!$isProduction) {
                reportCheck("Authentication Testing", 'warning', false,
                            "Authentication tests skipped in production mode for security");
            }
        } else {
            reportCheck("UserModel", false, true, "UserModel.php file not found");
        }
    }
    
    // Test station operations
    echo "<h3>Station Management</h3>";
    
    if (file_exists(BASE_PATH . '/models/StationModel.php')) {
        require_once BASE_PATH . '/models/StationModel.php';
        $stationModel = new StationModel();
        
        // Test station retrieval
        $stations = $stationModel->getAll(1, 1);
        reportCheck("Station Retrieval", isset($stations['stations']), false,
                    isset($stations['stations']) ? "Station retrieval is working" : "Failed to retrieve stations: " . $stationModel->getError());
        
        // Test station existence
        $stationCount = isset($stations['pagination']['totalStations']) ? $stations['pagination']['totalStations'] : 0;
        reportCheck("Station Data", $stationCount > 0, false,
                   "Found $stationCount stations in database");
    } else {
        reportCheck("StationModel", false, true, "StationModel.php file not found");
    }
    
    // Test trajet operations
    echo "<h3>Trajet Management</h3>";
    
    if (file_exists(BASE_PATH . '/models/TrajetModel.php')) {
        require_once BASE_PATH . '/models/TrajetModel.php';
        $trajetModel = new TrajetModel();
        
        // Test trajet retrieval
        $trajets = $trajetModel->getAll(1, 1);
        reportCheck("Trajet Retrieval", isset($trajets['trajets']), false,
                    isset($trajets['trajets']) ? "Trajet retrieval is working" : "Failed to retrieve trajets: " . $trajetModel->getError());
        
        // Test trajet existence
        $trajetCount = isset($trajets['pagination']['totalTrajets']) ? $trajets['pagination']['totalTrajets'] : 0;
        reportCheck("Trajet Data", $trajetCount > 0, false,
                   "Found $trajetCount trajets in database");
    } else {
        reportCheck("TrajetModel", false, true, "TrajetModel.php file not found");
    }
    
    // Test routing
    echo "<h3>Routing System</h3>";
    
    $frontControllerExists = file_exists(BASE_PATH . '/public/index.php');
    reportCheck("Front Controller", $frontControllerExists, true,
                $frontControllerExists ? "Front controller exists" : "Front controller (public/index.php) not found");
    
    if ($frontControllerExists) {
        $frontControllerContent = file_get_contents(BASE_PATH . '/public/index.php');
        $hasRouting = strpos($frontControllerContent, 'controller') !== false && 
                      strpos($frontControllerContent, 'action') !== false;
        
        reportCheck("Routing Logic", $hasRouting, true,
                    $hasRouting ? "Routing logic detected in front controller" : "Routing logic missing in front controller");
    }
    
    // Check controller files
    $controllerPath = BASE_PATH . '/controllers';
    $requiredControllers = ['stationController.php', 'trajetController.php', 'authController.php'];
    
    if (is_dir($controllerPath)) {
        $missingControllers = [];
        foreach ($requiredControllers as $controller) {
            if (!file_exists($controllerPath . '/' . $controller)) {
                $missingControllers[] = $controller;
            }
        }
        
        reportCheck("Controllers", count($missingControllers) === 0, true,
                    count($missingControllers) === 0 ? "All required controllers exist" : "Missing controllers: " . implode(', ', $missingControllers));
    } else {
        reportCheck("Controllers Directory", false, true, "Controllers directory not found");
    }
    
} catch (Exception $e) {
    reportCheck("Core Functionality", false, true, "Error testing core functionality: " . $e->getMessage());
}

echo "</div>";

// =============================================
// Section 5: Deployment Report
// =============================================
echo "<div class='check-section'><h2>5. Deployment Report</h2>";

// Generate summary
$passRate = ($totalChecks > 0) ? round(($passedChecks / $totalChecks) * 100) : 0;
$deploymentReady = ($criticalIssues === 0);

echo "<div class='summary'>";
echo "<h3>Summary</h3>";
echo "<p>Total checks performed: $totalChecks</p>";
echo "<p>Checks passed: $passedChecks ($passRate%)</p>";
echo "<p>Warnings: $warnings</p>";
echo "<p>Critical issues: $criticalIssues</p>";

// Overall status
if ($deploymentReady) {
    echo "<h3 class='success'>DEPLOYMENT READY: No critical issues found</h3>";
    if ($warnings > 0) {
        echo "<p class='warning'>Note: There are $warnings non-critical warnings that should be addressed.</p>";
    }
} else {
    echo "<h3 class='error'>NOT READY FOR DEPLOYMENT: $criticalIssues critical issues found</h3>";
    echo "<p>Please fix all critical issues before deploying to production.</p>";
}
echo "</div>";

// Display recommendations
if (count($recommendations) > 0) {
    echo "<h3>Recommendations</h3>";
    echo "<div class='recommendation'>";
    echo "<ul>";
    foreach ($recommendations as $recommendation) {
        echo "<li>$recommendation</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Pre-deployment checklist
echo "<h3>Pre-Deployment Checklist</h3>";
echo "<div class='recommendation'>";
echo "<ol>";
echo "<li>Backup your database</li>";
echo "<li>Set ENVIRONMENT to 'production' in config.php</li>";
echo "<li>Disable display_errors in production</li>";
echo "<li>Change the default admin password</li>";
echo "<li>Secure or remove test.php and deploy-checklist.php</li>";
echo "<li>Check file permissions on all server files</li>";
echo "<li>Verify .htaccess files are properly set up</li>";
echo "<li>Enable error logging to file</li>";
echo "</ol>";
echo "</div>";

echo "</div>";

// Cleanup
if (isset($pdo)) {
    $pdo = null; // Close database connection
}

// =============================================
// Closing HTML
// =============================================
echo "<p><em>Report generated on " . date('Y-m-d H:i:s') . "</em></p>";
echo "<p><strong>Important:</strong> Delete this file from your production server after deployment verification.</p>";
echo "</div></body></html>";

// Output the buffer
ob_end_flush();
