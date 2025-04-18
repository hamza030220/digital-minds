<?php
// Simple delete post API
// Set error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session and setup headers
session_start();
header('Content-Type: application/json');

// Log request for debugging
error_log('Delete request received: ' . json_encode($_POST));

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

// Check required parameters
if (!isset($_POST['post_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Post ID required']);
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Get and validate post ID
$postId = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid post ID format']);
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Include database connection
require_once 'db_connect.php';

try {
    // Check if database connection exists
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if post exists (simple query)
    $checkStmt = $conn->prepare("SELECT user_id FROM post WHERE post_id = ?");
    $checkStmt->execute([$postId]);
    $post = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    // Check authorization (only if not admin)
    if ($post['user_id'] != $userId && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authorized to delete this post']);
        exit;
    }

    // Simple update with question mark placeholder
    $deleteStmt = $conn->prepare("UPDATE post SET is_deleted = 1 WHERE post_id = ?");
    $success = $deleteStmt->execute([$postId]);

    if ($success) {
        // Success response
        echo json_encode([
            'success' => true, 
            'message' => 'Post deleted successfully',
            'post_id' => $postId
        ]);
    } else {
        // Check for errors
        $errorInfo = $deleteStmt->errorInfo();
        throw new Exception('Database update failed: ' . $errorInfo[2]);
    }

} catch (PDOException $e) {
    // Log and return the specific PDO error
    error_log('PDO Error in delete_post.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Log and return general errors
    error_log('Error in delete_post.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
