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
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 4;

// Additional validation
if ($post_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid post ID'
    ]);
    exit;
}

// Ensure offset and limit are valid
if ($offset < 0) {
    $offset = 0;
}
if ($limit <= 0) {
    $limit = 4; // Default to 4 comments per batch
} else if ($limit > 20) {
    $limit = 20; // Cap maximum comments per request
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
              ORDER BY c.created_at ASC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch all comments for this post
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total comment count for the post (useful for pagination)
    $countQuery = "SELECT COUNT(*) as total FROM commentaire WHERE post_id = :post_id AND is_deleted = 0";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get current user info for action button permissions
    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1;
    
    // Return success with comments data and user context
    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'total_count' => $totalCount,
        'current_count' => count($comments),
        'current_user_id' => $current_user_id,
        'is_admin' => $is_admin,
        'offset' => $offset,
        'limit' => $limit
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

