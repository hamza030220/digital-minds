<?php
class Comment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new comment
    public function create($postId, $userId, $content) {
        $stmt = $this->pdo->prepare("INSERT INTO comments (post_id, id, content, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$postId, $userId, $content]);
    }

    // Get all comments for a specific post
    public function getByPostId($postId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nom, u.prenom, u.photo
            FROM comments c
            LEFT JOIN users u ON c.id = u.id
            WHERE c.post_id = ? AND c.is_deleted = 0
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete a comment (soft delete)
    public function delete($commentId) {
        $stmt = $this->pdo->prepare("UPDATE comments SET is_deleted = 1 WHERE comment_id = ?");
        return $stmt->execute([$commentId]);
    }
}
?>