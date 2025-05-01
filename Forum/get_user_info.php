<?php
require_once 'db_connect.php';

if (isset($_GET['user_id'])) {
    $postId = intval($_GET['user_id']); // This should be the post_id, not user_id

    try {
        $stmt = $conn->prepare("SELECT u.id, u.username, u.email, p.is_anonymous 
                                FROM users u 
                                JOIN post p ON u.id = p.user_id 
                                WHERE p.post_id = :post_id");
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Post or user not found.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Post ID is missing.']);
}