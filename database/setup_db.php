<?php
// Database connection settings
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'green_db';

// Read SQL file
$sqlFile = __DIR__ . './green_db.sql';
$sql = file_get_contents($sqlFile);

try {
    // Connect to MySQL using PDO
    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    // Find all CREATE TABLE statements and their table names
    preg_match_all('/CREATE TABLE\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $matches);
    $tables = $matches[1];

    echo "<h2>Database Setup Checklist</h2>";
    echo "<ul style='list-style:none;padding:0;'>";

    foreach ($tables as $table) {
        try {
            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->fetch()) {
                echo "<li style='color:#007bff;'><b>&#9679; $table</b> already exists</li>";
                continue;
            }

            // Extract the CREATE TABLE statement for this table
            $pattern = "/(CREATE TABLE\s+`?$table`?.*?;)/is";
            if (preg_match($pattern, $sql, $tableSqlMatch)) {
                $createTableSql = $tableSqlMatch[1];
                $pdo->exec($createTableSql);
                echo "<li style='color:#28a745;'><b>&#9679; $table</b> created successfully</li>";
            } else {
                echo "<li style='color:#dc3545;'><b>&#9679; $table</b> CREATE statement not found in SQL file</li>";
            }
        } catch (PDOException $e) {
            echo "<li style='color:#dc3545;'><b>&#9679; $table</b> error: " . htmlspecialchars($e->getMessage()) . "</li>";
        }
    }
    echo "</ul>";

    echo "<p>Checklist completed.</p>";
} catch (PDOException $e) {
    echo "<div style='color:#dc3545;'><b>Database error:</b> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>