<?php
// Start session and setup headers
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log request for debugging
error_log('Edit request received: ' . json_encode($_POST));

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

// Check required parameters
if (!isset($_POST['post_id']) || !isset($_POST['title']) || !isset($_POST['content'])) {
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
$title = trim($_POST['title']);
$content = trim($_POST['content']);
$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Validate post ID
if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

// Validate title and content
if (empty($title) || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
    exit;
}

// Include database connection
require_once 'db_connect.php';

try {
    // Check if post exists and get ownership info
    $checkStmt = $conn->prepare("SELECT user_id FROM post WHERE post_id = ? AND is_deleted = 0");
    $checkStmt->execute([$postId]);
    $post = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    // Check if user owns the post or is admin
    if ($post['user_id'] != $userId && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authorized to edit this post']);
        exit;
    }

    // Update the post
    $updateStmt = $conn->prepare("UPDATE post SET title = ?, content = ? WHERE post_id = ?");
    $success = $updateStmt->execute([$title, $content, $postId]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Post updated successfully',
            'post' => [
                'post_id' => $postId,
                'title' => $title,
                'content' => $content
            ]
        ]);
    } else {
        $errorInfo = $updateStmt->errorInfo();
        throw new Exception('Failed to update post: ' . $errorInfo[2]);
    }

} catch (PDOException $e) {
    error_log('Database error in edit_post.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error in edit_post.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating post: ' . $e->getMessage()
    ]);
}
?>
