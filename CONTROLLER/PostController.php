<?php
require_once __DIR__ . '/../MODEL/Post.php';

class PostController {
    private $postModel;

    public function __construct($pdo) {
        $this->postModel = new Post($pdo);
    }

    // Create a new post
    public function createPost($userId, $title, $content, $isAnonymous = 0) {
        if (strlen($title) < 5 || strlen($content) < 10) {
            return ['error' => 'Le titre ou le contenu est trop court.'];
        }
        $result = $this->postModel->create($userId, $title, $content, $isAnonymous);
        return $result ? ['success' => 'Post créé avec succès.'] : ['error' => 'Erreur lors de la création du post.'];
    }

    // Get all posts with pagination
    public function getPosts($page, $postsPerPage) {
        $offset = ($page - 1) * $postsPerPage;
        return $this->postModel->getAll($offset, $postsPerPage);
    }

    // Update a post
    public function updatePost($postId, $title, $content, $isAnonymous) {
        return $this->postModel->update($postId, $title, $content, $isAnonymous);
    }

    // Delete a post
    public function deletePost($postId) {
        return $this->postModel->delete($postId);
    }
}
?>