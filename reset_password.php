<?php
require_once 'includes/config.php';

try {
    $pdo = getDBConnection();
    
    // Update admin password to 'admin'
    $password = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
    $stmt->execute([$password, 'admin']);
    
    if ($stmt->rowCount() > 0) {
        echo "Admin password reset successfully! New password is 'admin'";
    } else {
        echo "Error: Admin user not found";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

