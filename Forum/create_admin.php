<?php
// This is a utility script to create a new admin user
// Run this once to create an admin user, then remove or secure this file

require_once 'db_connect.php';

// Change these values to your desired admin credentials
$admin_username = "admin";
$admin_password = "admin123"; // This should be a strong password in production
$admin_email = "admin@example.com";

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $admin_username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Admin user already exists!";
    } else {
        // Insert the new admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (:username, :password, :email, TRUE)");
        $stmt->bindParam(':username', $admin_username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $admin_email);
        $stmt->execute();
        
        echo "Admin user created successfully!";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

