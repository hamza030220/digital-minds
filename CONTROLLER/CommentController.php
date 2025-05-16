<?php
require_once __DIR__ . '/../MODEL/Comment.php';

class CommentController {
    private $commentModel;

    public function __construct($pdo) {
        $this->commentModel = new Comment($pdo);
    }

    // Create a new comment
    public function createComment($postId, $userId, $content) {
        if (strlen($content) < 5) {
            return ['error' => 'Le contenu du commentaire est trop court.'];
        }
        $result = $this->commentModel->create($postId, $userId, $content);
        return $result ? ['success' => 'Commentaire ajouté avec succès.'] : ['error' => 'Erreur lors de l\'ajout du commentaire.'];
    }

    // Get all comments for a specific post
    public function getComments($postId) {
        return $this->commentModel->getByPostId($postId);
    }

    // Delete a comment
    public function deleteComment($commentId) {
        return $this->commentModel->delete($commentId);
    }
}
?>