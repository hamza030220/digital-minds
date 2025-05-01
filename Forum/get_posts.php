<?php
/**
 * Get Posts API
 * 
 * Fetches all non-deleted posts from the database with their comments
 * Returns JSON response with posts and comments data
 */

// Start session to maintain user state
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? $_SESSION['user_id'] : null;
$isAdmin = $isLoggedIn && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1;

// Include database connection
require_once 'db_connect.php';
// Set content type to JSON
header('Content-Type: application/json');

// Prevent caching of API responses
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

try {
    error_log("Starting get_posts.php execution");
    
    // Debug: Log connection status
    error_log("Database connection status: " . ($conn ? "Connected" : "Not connected"));
    
    // Simple query without joins first to verify posts exist
    $checkQuery = "SELECT COUNT(*) FROM post WHERE is_deleted = 0";
    $count = $conn->query($checkQuery)->fetchColumn();
    error_log("Found {$count} posts in database");

    if ($count > 0) {
        // Limit to prevent overloading (optional)
        $limit = 100;
        
        // Query to get posts with user info
        $query = "SELECT p.post_id, p.user_id, p.title, p.content, p.created_at, 
                   p.is_anonymous, u.username, u.is_admin
            FROM post p 
            INNER JOIN users u ON p.user_id = u.id 
            WHERE p.is_deleted = 0 
            ORDER BY p.created_at DESC
            LIMIT :limit";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters before executing
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute(); // Execute after binding parameters
        
        // Fetch all posts
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Fetched " . count($posts) . " posts after JOIN");
    
        // Get comments for each post
        foreach ($posts as &$post) {
            // Add user login status and current user ID to each post
            $post['is_logged_in'] = $isLoggedIn;
            $post['current_user_id'] = $currentUserId;
            
            try {
                $commentQuery = "SELECT c.comment_id, c.user_id, c.content, c.created_at, 
                                u.username, u.is_admin
                           FROM commentaire c 
                           INNER JOIN users u ON c.user_id = u.id 
                           WHERE c.post_id = ? AND (c.is_deleted = 0 OR c.is_deleted IS NULL)
                           ORDER BY c.created_at ASC";
                
                $commentStmt = $conn->prepare($commentQuery);
                $commentStmt->execute([$post['post_id']]);
                $post['comments'] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($post['comments']) . " comments for post ID " . $post['post_id']);
            } catch (PDOException $e) {
                error_log("Error fetching comments for post ID " . $post['post_id'] . ": " . $e->getMessage());
                $post['comments'] = [];
            }
        }
    
        error_log("Successfully prepared posts data with comments");
        echo json_encode([
            'success' => true,
            'posts' => $posts,
            'count' => count($posts)
        ]);
    } else {
        // No posts found
        error_log("No posts found in database");
        echo json_encode([
            'success' => true,
            'posts' => [],
            'count' => 0
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in get_posts.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
