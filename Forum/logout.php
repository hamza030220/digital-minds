<?php
session_start();

// Clear session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_me_token'])) {
    // Connect to database to delete the token
    require_once 'db_connect.php';
    
    try {
        $token = $_COOKIE['remember_me_token'];
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
    } catch(PDOException $e) {
        // Silently fail, still remove cookie
    }
    
    // Delete the cookie by setting expiration to past
    setcookie('remember_me_token', '', time() - 3600, '/');
}

// Redirect to login page
header("Location: signin.php");
exit();
?>

<?php
session_start();

// Clear session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_me_token'])) {
    // Connect to database to delete the token
    require_once 'db_connect.php';
    
    try {
        $token = $_COOKIE['remember_me_token'];
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
    } catch(PDOException $e) {
        // Silently fail, still remove cookie
    }
    
    // Delete the cookie by setting expiration to past
    setcookie('remember_me_token', '', time() - 3600, '/');
}

// Redirect to login page
header("Location: signin.php");
exit();
?>

