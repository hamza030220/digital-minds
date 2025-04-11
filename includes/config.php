<?php
// Database configuration constants
if (!defined('BASE_PATH')) {
    define('BASE_PATH', rtrim(__DIR__, '/') . '/../');
}


define('DB_HOST', 'localhost');
define('DB_NAME', 'green_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings before starting the session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS only
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_lifetime', 0); // Until browser closes
    
    session_start();
}

// Database connection function
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        die("Connection failed. Please try again later.");
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /green-admin-mvc/login");
        exit();
    }
}
