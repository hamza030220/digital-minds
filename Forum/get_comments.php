<?php
/**
 * Get Comments API
 * 
 * Fetches all non-deleted comments for a specific post
 * Returns JSON response with comments data
 */

// Start session to maintain user state
session_start();

// Include database connection
require_once 'db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Prevent caching of API responses
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Validate post_id parameter
if (!isset($_GET['post_id']) || !preg_match('/^\d+$/', $_GET['post_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or missing post_id parameter'
    ]);
    exit;
}

// Sanitize input
$post_id = intval($_GET['post_id']);

// Additional validation
if ($post_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid post ID'
    ]);
    exit;
}

try {
    // First verify post exists and is not deleted
    $checkQuery = "SELECT post_id FROM post WHERE post_id = :post_id AND is_deleted = 0";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Post not found or has been deleted'
        ]);
        exit;
    }
    
    // Fetch comments for the specified post
    // Only select necessary fields
    $query = "SELECT c.comment_id, c.post_id, c.user_id, c.content, c.created_at, c.updated_at, 
                     c.is_reported, u.username, u.is_admin 
              FROM commentaire c 
              INNER JOIN users u ON c.user_id = u.id 
              WHERE c.post_id = :post_id AND c.is_deleted = 0 
              ORDER BY c.created_at ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch all comments for this post
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success with comments data
    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'count' => count($comments)
    ]);
    
} catch(PDOException $e) {
    // Log error (in production, you'd use a proper logging system)
    error_log('Database error in get_comments.php: ' . $e->getMessage());
    
    // Return error message without exposing database details
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred. Please try again later.'
    ]);
}
?>

