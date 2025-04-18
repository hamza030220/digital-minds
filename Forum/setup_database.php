<?php
// Database setup helper script

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "green";
$sqlFile = "database_setup.sql";

// Function to display status messages
function displayMessage($message, $isError = false) {
    echo '<div style="padding: 10px; margin: 5px 0; ' .
         'background-color: ' . ($isError ? '#ffebee' : '#e8f5e9') . '; ' .
         'color: ' . ($isError ? '#c62828' : '#2e7d32') . '; ' .
         'border-radius: 4px; font-family: Arial, sans-serif;">' .
         $message . '</div>';
}

// Check if SQL file exists
if (!file_exists($sqlFile)) {
    displayMessage("Error: SQL file not found. Please make sure $sqlFile exists in the same directory.", true);
    exit;
}

try {
    // Create database connection
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    displayMessage("Connected to MySQL server successfully.");

    // Read SQL file
    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        displayMessage("Error: Could not read SQL file.", true);
        exit;
    }
    
    // Execute SQL queries
    displayMessage("Running SQL script...");
    
    // Split SQL commands on semicolons (excluding semicolons in string literals)
    $commands = [];
    $current = '';
    $delimiter = ';';
    $inString = false;
    $stringChar = '';
    
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];
        $prev = ($i > 0) ? $sql[$i-1] : '';
        
        // Handle string literals
        if (($char === "'" || $char === '"') && $prev !== '\\') {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        }
        
        // Handle delimiters
        if ($char === $delimiter && !$inString) {
            $commands[] = $current;
            $current = '';
            continue;
        }
        
        $current .= $char;
    }
    
    // Add the last command if it doesn't end with a delimiter
    if (trim($current) !== '') {
        $commands[] = $current;
    }
    
    // Execute each command
    $count = 0;
    foreach ($commands as $command) {
        $command = trim($command);
        if (!empty($command)) {
            try {
                $conn->exec($command);
                $count++;
            } catch (PDOException $e) {
                displayMessage("Error executing command: " . $e->getMessage(), true);
                // Continue with next command
            }
        }
    }
    
    displayMessage("Executed $count SQL commands successfully.");
    displayMessage("Database setup completed successfully!");
    displayMessage("You can now use the forum application.");
    
    // Provide login instructions
    echo "<hr>";
    displayMessage("Default logins created:");
    echo "<ul style='font-family: Arial, sans-serif;'>";
    echo "<li><strong>Admin:</strong> username: admin, password: admin123</li>";
    echo "<li><strong>User:</strong> username: user, password: user123</li>";
    echo "</ul>";
    echo "<hr>";
    
    // Provide links to main pages
    echo "<div style='margin-top: 20px; font-family: Arial, sans-serif;'>";
    echo "<a href='forum.php' style='display: inline-block; padding: 10px 15px; background-color: #60BA97; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Go to Forum</a>";
    echo "<a href='back.php' style='display: inline-block; padding: 10px 15px; background-color: #2e7d32; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Panel</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    displayMessage("Connection failed: " . $e->getMessage(), true);
}

// Close connection
$conn = null;
?>

