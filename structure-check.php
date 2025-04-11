<?php
/**
 * Green Admin MVC - Structure Check Script
 * 
 * This script verifies that all required files and directories
 * for the MVC architecture are present and properly organized.
 */

// Define base path
define('BASE_PATH', __DIR__);

// Start output buffering
ob_start();

// HTML header
echo "<!DOCTYPE html>
<html>
<head>
    <title>Green Admin MVC - Structure Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #60BA97; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { border-bottom: 1px solid #ddd; margin-bottom: 20px; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .btn { 
            display: inline-block; 
            padding: 8px 16px; 
            background-color: #60BA97; 
            color: white; 
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Green Admin MVC - Structure Check</h1>
        <p>This tool verifies that all required files and directories are present in your MVC implementation.</p>";

// Function to check if file exists
function checkFile($path, $required = true) {
    $exists = file_exists(BASE_PATH . '/' . $path);
    $class = $exists ? 'success' : ($required ? 'error' : 'warning');
    $status = $exists ? '✓' : '✗';
    $message = $exists ? 'Present' : ($required ? 'Missing (Required)' : 'Missing (Optional)');
    
    echo "<tr>
        <td>{$path}</td>
        <td class='{$class}'>{$status} {$message}</td>
    </tr>";
    
    return $exists;
}

// Function to check if directory exists
function checkDir($path, $required = true) {
    $exists = is_dir(BASE_PATH . '/' . $path);
    $class = $exists ? 'success' : ($required ? 'error' : 'warning');
    $status = $exists ? '✓' : '✗';
    $message = $exists ? 'Present' : ($required ? 'Missing (Required)' : 'Missing (Optional)');
    
    echo "<tr>
        <td>{$path}/</td>
        <td class='{$class}'>{$status} {$message}</td>
    </tr>";
    
    return $exists;
}

// Track results
$totalItems = 0;
$missingRequired = 0;
$missingOptional = 0;

// Section 1: Core Directories
echo "<div class='section'><h2>1. Core Directories</h2>";
echo "<table><tr><th>Directory</th><th>Status</th></tr>";

$totalItems += 7;
if (!checkDir('controllers', true)) $missingRequired++;
if (!checkDir('models', true)) $missingRequired++;
if (!checkDir('views', true)) $missingRequired++;
if (!checkDir('includes', true)) $missingRequired++;
if (!checkDir('public', true)) $missingRequired++;
if (!checkDir('assets', false)) $missingOptional++;
if (!checkDir('database', true)) $missingRequired++;

echo "</table></div>";

// Section 2: MVC Components
echo "<div class='section'><h2>2. MVC Components</h2>";

// Controllers
echo "<h3>Controllers</h3>";
echo "<table><tr><th>File</th><th>Status</th></tr>";

$totalItems += 4;
if (!checkFile('controllers/stationController.php', true)) $missingRequired++;
if (!checkFile('controllers/trajetController.php', true)) $missingRequired++;
if (!checkFile('controllers/authController.php', true)) $missingRequired++;
if (!checkFile('controllers/dashboardController.php', true)) $missingRequired++;

echo "</table>";

// Models
echo "<h3>Models</h3>";
echo "<table><tr><th>File</th><th>Status</th></tr>";

$totalItems += 3;
if (!checkFile('models/StationModel.php', true)) $missingRequired++;
if (!checkFile('models/TrajetModel.php', true)) $missingRequired++;
if (!checkFile('models/UserModel.php', true)) $missingRequired++;

echo "</table>";

// Views
echo "<h3>Views</h3>";
echo "<table><tr><th>File</th><th>Status</th></tr>";

$totalItems += 11;
if (!checkDir('views/stations', true)) $missingRequired++;
if (!checkDir('views/trajets', true)) $missingRequired++;
if (!checkFile('views/404.php', true)) $missingRequired++;
if (!checkFile('views/500.php', true)) $missingRequired++;
if (!checkFile('views/dashboard.php', true)) $missingRequired++;
if (!checkFile('views/login.php', true)) $missingRequired++;
if (!checkFile('views/stations/list.php', true)) $missingRequired++;
if (!checkFile('views/stations/add.php', true)) $missingRequired++;
if (!checkFile('views/stations/edit.php', true)) $missingRequired++;
if (!checkFile('views/stations/delete.php', true)) $missingRequired++;
if (!checkFile('views/trajets/list.php', true)) $missingRequired++;

echo "</table></div>";

// Section 3: Core Files
echo "<div class='section'><h2>3. Core Files</h2>";
echo "<table><tr><th>File</th><th>Status</th></tr>";

$totalItems += 5;
if (!checkFile('includes/config.php', true)) $missingRequired++;
if (!checkFile('includes/Controller.php', true)) $missingRequired++;
if (!checkFile('public/index.php', true)) $missingRequired++;
if (!checkFile('.htaccess', true)) $missingRequired++;
if (!checkFile('database/auth.sql', true)) $missingRequired++;

echo "</table></div>";

// Section 4: Helper Files
echo "<div class='section'><h2>4. Helper Files</h2>";
echo "<table><tr><th>File</th><th>Status</th></tr>";

$totalItems += 7;
if (!checkFile('test.php', false)) $missingOptional++;
if (!checkFile('db-test.php', false)) $missingOptional++;
if (!checkFile('auto-setup.php', false)) $missingOptional++;
if (!checkFile('deploy-checklist.php', false)) $missingOptional++;
if (!checkFile('README.md', false)) $missingOptional++;
if (!checkFile('CHANGELOG.md', false)) $missingOptional++;
if (!checkFile('SETUP_CHECKLIST.md', false)) $missingOptional++;

echo "</table></div>";

// Summary
echo "<div class='section'><h2>Structure Check Summary</h2>";

$percentComplete = floor(($totalItems - ($missingRequired + $missingOptional)) / $totalItems * 100);
$statusClass = ($missingRequired > 0) ? 'error' : (($missingOptional > 0) ? 'warning' : 'success');
$statusText = ($missingRequired > 0) ? 'Incomplete' : (($missingOptional > 0) ? 'Mostly Complete' : 'Complete');

echo "<h3>Status: <span class='{$statusClass}'>{$statusText}</span></h3>";
echo "<p>Checked <strong>{$totalItems}</strong> files and directories:</p>";
echo "<ul>";
echo "<li><span class='success'>✓ Present: " . ($totalItems - ($missingRequired + $missingOptional)) . "</span></li>";
echo "<li><span class='error'>✗ Missing Required: {$missingRequired}</span></li>";
echo "<li><span class='warning'>✗ Missing Optional: {$missingOptional}</span></li>";
echo "</ul>";

echo "<div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>MVC Implementation: {$percentComplete}% Complete</h3>";
echo "<div style='background-color: #ddd; height: 20px; border-radius: 10px; overflow: hidden;'>";
echo "<div style='background-color: " . ($percentComplete > 80 ? '#60BA97' : ($percentComplete > 50 ? '#FFA500' : '#FF0000')) . "; width: {$percentComplete}%; height: 100%;'></div>";
echo "</div></div>";

// Next steps
if ($missingRequired > 0) {
    echo "<h3>Required Actions</h3>";
    echo "<p>Please create the missing required files and directories listed above with red ✗ symbols.</p>";
} elseif ($missingOptional > 0) {
    echo "<h3>Suggested Actions</h3>";
    echo "<p>Your MVC structure is complete with all required components, but you may want to add the optional files marked with orange ✗ symbols for better functionality.</p>";
} else {
    echo "<h3>Congratulations!</h3>";
    echo "<p>Your MVC structure is complete with all required and optional components in place.</p>";
}

// Additional checks
echo "<h3>Additional Checks</h3>";
echo "<p>After ensuring your file structure is complete, verify these components:</p>";
echo "<ol>";
echo "<li>Database Connection: Run <a href='db-test.php'>db-test.php</a> to verify database setup</li>";
echo "<li>Deployment Readiness: Run <a href='deploy-checklist.php'>deploy-checklist.php</a> to check deployment readiness</li>";
echo "<li>Auto Setup: Run <a href='auto-setup.php'>auto-setup.php</a> to configure your database automatically</li>";
echo "</ol>";

echo "<a href='/green-admin-mvc/' class='btn'>Go to Application</a>";

echo "</div></div></body></html>";

// Output the buffer
ob_end_flush();

