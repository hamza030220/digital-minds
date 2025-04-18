<?php
/**
 * Create Post API
 * 
 * Handles new post creation
 * Requires user authentication and CSRF validation
 * Returns JSON response
 */

// Start session to maintain user state
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Prevent caching of API responses
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in trying to create post');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to create posts'
    ]);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log('CSRF token validation failed in create_post.php');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token'
    ]);
    exit;
}

// Debug log to track request parameters
error_log('Create post request received with parameters: ' . json_encode($_POST));

// Validate required parameters
if (!isset($_POST['title']) || !isset($_POST['content'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Clean and validate input
$title = trim($_POST['title']);
$content = trim($_POST['content']);
$userId = $_SESSION['user_id'];

// Validate title and content
if (empty($title)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Post title is required'
    ]);
    exit;
}

if (empty($content)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Post content is required'
    ]);
    exit;
}

// Include database connection
require_once 'db_connect.php';

try {
    // Insert the new post
    $insertQuery = "INSERT INTO post (user_id, title, content, created_at, is_deleted) 
                    VALUES (:user_id, :title, :content, NOW(), 0)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $insertStmt->bindParam(':title', $title, PDO::PARAM_STR);
    $insertStmt->bindParam(':content', $content, PDO::PARAM_STR);
    
    if ($insertStmt->execute()) {
        // Get the new post ID
        $postId = $conn->lastInsertId();
        
        // Get username for response
        $usernameQuery = "SELECT username, is_admin FROM users WHERE id = :user_id";
        $usernameStmt = $conn->prepare($usernameQuery);
        $usernameStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $usernameStmt->execute();
        $userInfo = $usernameStmt->fetch(PDO::FETCH_ASSOC);
        
        // Log the action
        error_log("Post {$postId} created by user {$userId}");
        
        // Return success response with all necessary data for UI update
        echo json_encode([
            'success' => true,
            'message' => 'Post created successfully',
            'post' => [
                'post_id' => $postId,
                'user_id' => $userId,
                'title' => $title,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s'),
                'username' => $userInfo['username'],
                'is_admin' => $userInfo['is_admin']
            ]
        ]);
    } else {
        // If the INSERT failed, throw an exception
        throw new Exception('Failed to create post in database');
    }
    
} catch(PDOException $e) {
    // Log database errors
    error_log('Database error in create_post.php: ' . $e->getMessage());
    
    // Return error response with specific status code
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: Unable to create your post',
        'error_code' => 'DB_ERROR',
        'debug_info' => $e->getMessage() // Only include in development environment
    ]);
} catch(Exception $e) {
    // Log other errors
    error_log('Error in create_post.php: ' . $e->getMessage());
    
    // Return error response with more user-friendly message
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating your post. Please try again.',
        'error_code' => 'GENERAL_ERROR',
        'debug_info' => $e->getMessage() // Only include in development environment
    ]);
}
?>

