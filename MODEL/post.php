<?php
class Post {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new post
    public function create($userId, $title, $content, $isAnonymous = 0) {
        $stmt = $this->pdo->prepare("INSERT INTO posts (id, title, content, created_at, is_anonymous) VALUES (?, ?, ?, NOW(), ?)");
        return $stmt->execute([$userId, $title, $content, $isAnonymous]);
    }

    // Get all posts with pagination
    public function getAll($offset, $limit) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.nom, u.prenom, u.photo
            FROM posts p
            LEFT JOIN users u ON p.id = u.id
            WHERE p.is_deleted = 0
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single post by ID
    public function getById($postId) {
        $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE post_id = ? AND is_deleted = 0");
        $stmt->execute([$postId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update a post
    public function update($postId, $title, $content, $isAnonymous) {
        $stmt = $this->pdo->prepare("UPDATE posts SET title = ?, content = ?, is_anonymous = ?, updated_at = NOW() WHERE post_id = ?");
        return $stmt->execute([$title, $content, $isAnonymous, $postId]);
    }

    // Delete a post (soft delete)
    public function delete($postId) {
        $stmt = $this->pdo->prepare("UPDATE posts SET is_deleted = 1 WHERE post_id = ?");
        return $stmt->execute([$postId]);
    }
}
?>