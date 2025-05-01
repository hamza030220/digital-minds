<?php
// Simple script to set up database tables and test users

// Database connection parameters - matching db_connect.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "green";

// Connect to MySQL
try {
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";
    echo "<h1>Setting up database tables and users</h1>";
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<p style='color: green;'>✓ Database '$dbname' created or already exists.</p>";
    
    // Select the database
    $conn->exec("USE $dbname");
    
    // Drop existing tables to avoid conflicts
    $conn->exec("DROP TABLE IF EXISTS commentaire");
    $conn->exec("DROP TABLE IF EXISTS post");
    $conn->exec("DROP TABLE IF EXISTS remember_tokens");
    $conn->exec("DROP TABLE IF EXISTS users");
    echo "<p style='color: green;'>✓ Removed old tables (if they existed).</p>";
    
    // Create users table
    $sql = "CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Created users table.</p>";
    
    // Create remember_tokens table for "remember me" functionality
    $sql = "CREATE TABLE remember_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id),
        CONSTRAINT fk_remember_user FOREIGN KEY (user_id)
            REFERENCES users (id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Created remember_tokens table.</p>";
    
    // Create post table
    $sql = "CREATE TABLE post (
        post_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_reported BOOLEAN DEFAULT FALSE,
        is_deleted BOOLEAN DEFAULT FALSE,
        INDEX idx_post_user_id (user_id),
        CONSTRAINT fk_post_user FOREIGN KEY (user_id) 
            REFERENCES users (id) 
            ON DELETE CASCADE 
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Created post table.</p>";
    
    // Create commentaire table
    $sql = "CREATE TABLE commentaire (
        comment_id INT PRIMARY KEY AUTO_INCREMENT,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_reported BOOLEAN DEFAULT FALSE,
        is_deleted BOOLEAN DEFAULT FALSE,
        INDEX idx_comment_post_id (post_id),
        INDEX idx_comment_user_id (user_id),
        CONSTRAINT fk_comment_post FOREIGN KEY (post_id) 
            REFERENCES post (post_id) 
            ON DELETE CASCADE 
            ON UPDATE CASCADE,
        CONSTRAINT fk_comment_user FOREIGN KEY (user_id) 
            REFERENCES users (id) 
            ON DELETE CASCADE 
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Created commentaire table.</p>";
    
    // Generate fresh password hashes
    $adminPassword = 'admin123';
    $userPassword = 'user123';
    $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $userHash = password_hash($userPassword, PASSWORD_DEFAULT);
    /*$naamaPassword = 'naama123';
    $naamaHash = password_hash($naamaPassword, PASSWORD_DEFAULT);*/
    // Insert admin user (password is 'admin123')
    $adminSql = "INSERT INTO users (username, email, password, is_admin) 
              VALUES ('admin', 'admin@green.tn', 
                     :admin_hash, 
                     TRUE)";
    $stmt = $conn->prepare($adminSql);
    $stmt->bindParam(':admin_hash', $adminHash);
    $stmt->execute();
    echo "<p style='color: green;'>✓ Created admin user.</p>";
    
    // Insert regular user (password is 'user123')
    $userSql = "INSERT INTO users (username, email, password, is_admin) 
              VALUES ('user', 'user@green.tn',
                     :user_hash,
                     FALSE)";
    $stmt = $conn->prepare($userSql);
    $stmt->bindParam(':user_hash', $userHash);
    $stmt->execute();
    echo "<p style='color: green;'>✓ Created regular user.</p>";

   /* $naamaSql = "INSERT INTO users (username, email, password, is_admin) 
              VALUES ('naama', 'naama@green.tn', 
                     :naama_hash, 
                     FALSE)";
    $stmt = $conn->prepare($naamaSql);
    $stmt->bindParam(':naama_hash', $naamaHash);
    $stmt->execute();
    echo "<p style='color: green;'>✓ Created naama user.</p>";*/
    
    // Add a sample post
    $postSql = "INSERT INTO post (user_id, title, content)
               SELECT id, 'Bienvenue sur le forum Green.tn', 
               'Ceci est un post de bienvenue sur notre forum. N\'hésitez pas à poser vos questions sur nos vélos et services!'
               FROM users WHERE username = 'admin'";
    $conn->exec($postSql);
    echo "<p style='color: green;'>✓ Created sample post.</p>";
    
    // Success message with credentials
    echo "<div style='background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 4px; padding: 15px; margin-top: 20px;'>";
    echo "<h2 style='color: #2e7d32;'>Setup Completed Successfully!</h2>";
    echo "<p>You can now log in with either of these accounts:</p>";
    echo "<h3>Admin User:</h3>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "<li><strong>Generated Hash:</strong> " . htmlspecialchars($adminHash) . "</li>";
    echo "</ul>";
    echo "<h3>Regular User:</h3>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> user</li>";
    echo "<li><strong>Password:</strong> user123</li>";
    echo "<li><strong>Generated Hash:</strong> " . htmlspecialchars($userHash) . "</li>";
    echo "</ul>";
    
    echo "<p><a href='signin.php' style='display: inline-block; background-color: #60BA97; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-top: 10px;'>Go to Login Page</a></p>";
    echo "</div>";
} catch(PDOException $e) {
    echo "<div style='color: red; padding: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Close connection
$conn = null;
?>

