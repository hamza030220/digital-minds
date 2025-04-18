<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// If a session cookie is used, clear it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Delete remember_me token if exists
if (isset($_COOKIE['remember_me_token'])) {
    // Try to remove from database
    require_once 'db_connect.php';
    $token = $_COOKIE['remember_me_token'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
    } catch(PDOException $e) {
        // Fail silently - we'll delete the cookie anyway
    }
    
    // Delete the cookie
    setcookie('remember_me_token', '', time() - 3600, "/");
}

// Destroy the session
session_destroy();

// Determine where to redirect based on AJAX or traditional request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // AJAX request
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
} else {
    // Regular request - redirect to login page
    header("Location: signin.php?logout=1");
}
exit;
?>

