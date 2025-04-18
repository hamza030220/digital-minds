<?php
// Start the session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
function isAdminLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Not logged in or not an admin
if (!isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Check if action and type parameters are provided
if (!isset($_GET['action']) || !isset($_GET['type']) || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: action, type, and id'
    ]);
    exit;
}

$action = $_GET['action'];
$type = $_GET['type'];
$id = intval($_GET['id']);

// Validate action
if ($action !== 'delete' && $action !== 'report') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action. Only "delete" and "report" are supported.'
    ]);
    exit;
}

// Validate type
if ($type !== 'post' && $type !== 'comment') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid type. Only "post" and "comment" are supported.'
    ]);
    exit;
}

try {
    // Handling delete action
    if ($action === 'delete') {
        if ($type === 'post') {
            // Check if post exists
            $checkStmt = $conn->prepare("SELECT post_id FROM post WHERE post_id = :id");
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Post not found'
                ]);
                exit;
            }
            
            // Mark post as deleted
            $stmt = $conn->prepare("UPDATE post SET is_deleted = 1 WHERE post_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
        } else {
            // Check if comment exists
            $checkStmt = $conn->prepare("SELECT comment_id FROM commentaire WHERE comment_id = :id");
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Comment not found'
                ]);
                exit;
            }
            
            // Mark comment as deleted
            $stmt = $conn->prepare("UPDATE commentaire SET is_deleted = 1 WHERE comment_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
        }
    }
    // Handling report action
    else if ($action === 'report') {
        if ($type === 'post') {
            // Check if post exists and isn't already reported
            $checkStmt = $conn->prepare("SELECT post_id, is_reported FROM post WHERE post_id = :id");
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Post not found'
                ]);
                exit;
            }
            
            $post = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($post['is_reported']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Post is already reported'
                ]);
                exit;
            }
            
            // Mark post as reported
            $stmt = $conn->prepare("UPDATE post SET is_reported = 1 WHERE post_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Post reported successfully'
            ]);
        } else {
            // Check if comment exists and isn't already reported
            $checkStmt = $conn->prepare("SELECT comment_id, is_reported FROM commentaire WHERE comment_id = :id");
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Comment not found'
                ]);
                exit;
            }
            
            $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($comment['is_reported']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Comment is already reported'
                ]);
                exit;
            }
            
            // Mark comment as reported
            $stmt = $conn->prepare("UPDATE commentaire SET is_reported = 1 WHERE comment_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Comment reported successfully'
            ]);
        }
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

