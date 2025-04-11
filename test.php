<?php
/**
 * Green Admin MVC - Test Script
 * 
 * This script tests the entire system by:
 * - Verifying database connectivity
 * - Checking table structures
 * - Testing authentication
 * - Verifying CRUD operations
 * - Generating sample data
 * 
 * Important: This script should only be run in development environments
 * Delete or secure this file before deploying to production
 */

// Define base path
define('BASE_PATH', __DIR__);

// Include configuration
require_once BASE_PATH . '/includes/config.php';

// Start output buffering for cleaner output
ob_start();

// HTML header for nicer output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Green Admin MVC - System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #60BA97; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .test-section { border-bottom: 1px solid #ddd; margin-bottom: 20px; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Green Admin MVC - System Test</h1>";

// Track test results
$testsRun = 0;
$testsPassed = 0;
$testsFailed = 0;

/**
 * Helper function to report test results
 */
function reportTest($name, $result, $message = '') {
    global $testsRun, $testsPassed, $testsFailed;
    $testsRun++;
    
    if ($result) {
        $testsPassed++;
        echo "<p><strong>$name:</strong> <span class='success'>PASSED</span>";
    } else {
        $testsFailed++;
        echo "<p><strong>$name:</strong> <span class='error'>FAILED</span>";
    }
    
    if (!empty($message)) {
        echo " - $message";
    }
    
    echo "</p>";
}

// =============================================
// Test 1: Database Connectivity
// =============================================
echo "<div class='test-section'><h2>1. Database Connectivity</h2>";

try {
    $pdo = getDBConnection();
    reportTest("Database Connection", true, "Successfully connected to database " . DB_NAME);
} catch (PDOException $e) {
    reportTest("Database Connection", false, "Failed to connect: " . $e->getMessage());
    // Exit early if we can't connect to the database
    echo "</div></div></body></html>";
    exit;
}

// =============================================
// Test 2: Table Structure Verification
// =============================================
echo "</div><div class='test-section'><h2>2. Table Structure Verification</h2>";

$requiredTables = ['users', 'password_resets', 'stations', 'trajets'];

// Get all tables in the database
try {
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $existingTables);
        reportTest("Table: $table", $exists, $exists ? "Table exists" : "Table does not exist");
        
        if ($exists) {
            // Check table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<p>Columns in $table: " . implode(', ', $columns) . "</p>";
            
            // Perform specific checks based on table
            switch ($table) {
                case 'users':
                    $hasRequired = in_array('username', $columns) && 
                                  in_array('password', $columns) && 
                                  in_array('email', $columns) && 
                                  in_array('role', $columns);
                    reportTest("$table structure", $hasRequired, $hasRequired ? "Has required columns" : "Missing required columns");
                    break;
                    
                case 'stations':
                    $hasRequired = in_array('name', $columns) && 
                                  in_array('location', $columns) && 
                                  in_array('status', $columns);
                    reportTest("$table structure", $hasRequired, $hasRequired ? "Has required columns" : "Missing required columns");
                    break;
                    
                case 'trajets':
                    $hasRequired = in_array('start_station_id', $columns) && 
                                  in_array('end_station_id', $columns) && 
                                  in_array('distance', $columns) &&
                                  in_array('route_coordinates', $columns);
                    reportTest("$table structure", $hasRequired, $hasRequired ? "Has required columns" : "Missing required columns");
                    break;
            }
        }
    }
} catch (PDOException $e) {
    reportTest("Table Structure Verification", false, "Error: " . $e->getMessage());
}

// =============================================
// Test 3: Authentication System
// =============================================
echo "</div><div class='test-section'><h2>3. Authentication System</h2>";

// Include user model
require_once BASE_PATH . '/models/UserModel.php';
$userModel = new UserModel();

// Check if admin user exists
try {
    $admin = $userModel->getByUsername('admin');
    $adminExists = !empty($admin);
    reportTest("Admin User", $adminExists, $adminExists ? "Admin user exists" : "Admin user not found");
    
    // Test authentication with default credentials
    $authenticated = $userModel->authenticate('admin', 'admin123');
    reportTest("Authentication", $authenticated !== false, $authenticated !== false ? "Authentication works" : "Authentication failed: " . $userModel->getError());
    
    // Test password hashing
    $testPassword = "testPassword123";
    $hash = $userModel->hashPassword($testPassword);
    $verifies = $userModel->verifyPassword($testPassword, $hash);
    reportTest("Password Hashing", $verifies, $verifies ? "Password hashing and verification work" : "Password verification failed");
    
    // Test token generation (if user exists)
    if ($adminExists) {
        $token = $userModel->generateResetToken($admin['email']);
        $tokenGenerated = !empty($token);
        reportTest("Reset Token Generation", $tokenGenerated, $tokenGenerated ? "Token generated: " . substr($token, 0, 10) . "..." : "Token generation failed: " . $userModel->getError());
    }
} catch (Exception $e) {
    reportTest("Authentication System", false, "Error: " . $e->getMessage());
}

// =============================================
// Test 4: Station CRUD Operations
// =============================================
echo "</div><div class='test-section'><h2>4. Station CRUD Operations</h2>";

// Include station model
require_once BASE_PATH . '/models/StationModel.php';
$stationModel = new StationModel();

try {
    // Test Create
    $testStation = [
        'name' => 'Test Station ' . rand(1000, 9999),
        'location' => 'Test Location ' . rand(100, 999),
        'status' => 'active'
    ];
    
    $stationId = $stationModel->create($testStation);
    $createSuccess = $stationId !== false;
    reportTest("Create Station", $createSuccess, $createSuccess ? "Created station with ID: $stationId" : "Failed to create station: " . $stationModel->getError());
    
    if ($createSuccess) {
        // Test Read
        $station = $stationModel->getById($stationId);
        $readSuccess = !empty($station);
        reportTest("Read Station", $readSuccess, $readSuccess ? "Retrieved station: {$station['name']}" : "Failed to retrieve station: " . $stationModel->getError());
        
        // Test Update
        $updateData = [
            'name' => $testStation['name'] . ' (Updated)',
            'location' => $testStation['location'] . ' (Updated)',
            'status' => 'inactive'
        ];
        
        $updateSuccess = $stationModel->update($stationId, $updateData);
        reportTest("Update Station", $updateSuccess, $updateSuccess ? "Updated station successfully" : "Failed to update station: " . $stationModel->getError());
        
        // Verify update
        if ($updateSuccess) {
            $updatedStation = $stationModel->getById($stationId);
            $verifyUpdate = ($updatedStation['name'] === $updateData['name'] && 
                            $updatedStation['status'] === $updateData['status']);
            reportTest("Verify Update", $verifyUpdate, $verifyUpdate ? "Update verified" : "Update could not be verified");
        }
        
        // Test Delete
        $deleteSuccess = $stationModel->delete($stationId);
        reportTest("Delete Station", $deleteSuccess, $deleteSuccess ? "Deleted station successfully" : "Failed to delete station: " . $stationModel->getError());
        
        // Verify deletion
        if ($deleteSuccess) {
            $deletedStation = $stationModel->getById($stationId);
            $verifyDelete = ($deletedStation === false);
            reportTest("Verify Deletion", $verifyDelete, $verifyDelete ? "Deletion verified" : "Deletion could not be verified");
        }
    }
} catch (Exception $e) {
    reportTest("Station CRUD Operations", false, "Error: " . $e->getMessage());
}

// =============================================
// Test 5: Generate Sample Data
// =============================================
echo "</div><div class='test-section'><h2>5. Generate Sample Data</h2>";

try {
    // Create sample stations
    $sampleStations = [
        ['name' => 'Central Station', 'location' => 'Downtown', 'status' => 'active'],
        ['name' => 'North Terminal', 'location' => 'North District', 'status' => 'active'],
        ['name' => 'South Terminal', 'location' => 'South District', 'status' => 'active'],
        ['name' => 'East Gateway', 'location' => 'East Side', 'status' => 'active'],
        ['name' => 'West Portal', 'location' => 'West Side', 'status' => 'inactive']
    ];
    
    $createdStations = [];
    foreach ($sampleStations as $station) {
        $id = $stationModel->create($station);
        if ($id !== false) {
            $createdStations[] = $id;
        }
    }
    
    reportTest("Create Sample Stations", count($createdStations) > 0, "Created " . count($createdStations) . " sample stations");
    
    // Create sample trajets if we have stations
    if (count($createdStations) >= 2) {
        // Include trajet model
        require_once BASE_PATH . '/models/TrajetModel.php';
        $trajetModel = new TrajetModel();
        
        $sampleTrajets = [
            [
                'start_station_id' => $createdStations[0],
                'end_station_id' => $createdStations[1],
                'distance' => 5.2,
                'description' => 'Direct route between Central and North',
                'route_coordinates' => json_encode([[48.8566, 2.3522], [48.85, 2.34], [48.84, 2.35]]),
                'route_description' => 'Follow the main boulevard north'
            ],
            [
                'start_station_id' => $createdStations[1],
                'end_station_id' => $createdStations[2],
                'distance' => 7.8,
                'description' => 'North to South express route',
                'route_coordinates' => json_encode([[48.85, 2.34], [48.84, 2.33], [48.83, 2.32]]),
                'route_description' => 'Take the highway south'
            ],
            [
                'start_station_id' => $createdStations[0],
                'end_station_id' => $createdStations[3],
                'distance' => 3.4,
                'description' => 'Central to East connector',
                'route_coordinates' => json_encode([[48.8566, 2.3522], [48.86, 2.36], [48.87, 2.37]]),
                'route_description' => 'Use the eastern boulevard'
            ]
        ];
        
        $createdTrajets = [];
        foreach ($sampleTrajets as $trajet) {
            $id = $trajetModel->create($trajet);
            if ($id !== false) {
                $createdTrajets[] = $id;
            }
        }
        
        reportTest("Create Sample Trajets", count($createdTrajets) > 0, "Created " . count($createdTrajets) . " sample trajets");
    } else {
        reportTest("Create Sample Trajets", false, "Not enough stations to create trajets");
    }
} catch (Exception $e) {
    reportTest("Generate Sample Data", false, "Error: " . $e->getMessage());
}

// =============================================
// Summary
// =============================================
echo "</div><div class='test-section'><h2>Test Summary</h2>";
echo "<p>Total tests run: $testsRun</p>";
echo "<p class='success'>Tests passed: $testsPassed</p>";
echo "<p class='error'>Tests failed: $testsFailed</p>";

if ($testsFailed === 0) {
    echo "<h3 class='success'>All tests passed! The system is working correctly.</h3>";
} else {
    echo "<h3 class='warning'>Some tests failed. Please review the issues above.</h3>";
}

echo "</div></div></body></html>";

// Output the buffer
ob_end_flush();

