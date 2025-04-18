<?php
// Simple comment addition handler
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log the request
error_log('Comment request received: ' . json_encode($_POST));

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

// Check required parameters
if (!isset($_POST['post_id']) || !isset($_POST['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Get and validate inputs
$postId = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
$content = trim($_POST['content']);
$userId = $_SESSION['user_id'];

if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

if (empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Comment content is required']);
    exit;
}

// Include database connection
require_once 'db_connect.php';

try {
    // Check if post exists
    $checkStmt = $conn->prepare("SELECT post_id FROM post WHERE post_id = ? AND is_deleted = 0");
    $checkStmt->execute([$postId]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    // Insert comment with simple query
    $stmt = $conn->prepare("INSERT INTO comment (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $success = $stmt->execute([$postId, $userId, $content]);
    
    if ($success) {
        $commentId = $conn->lastInsertId();
        
        // Get username for the response if needed
        $usernameStmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $usernameStmt->execute([$userId]);
        $username = $usernameStmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment' => [
                'comment_id' => $commentId,
                'post_id' => $postId,
                'content' => $content,
                'username' => $username,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Failed to add comment');
    }
    
} catch (PDOException $e) {
    error_log('Database error in add_comment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error in add_comment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding comment: ' . $e->getMessage()
    ]);
}
?>
