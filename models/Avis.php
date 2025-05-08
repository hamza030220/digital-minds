<?php
// models/Avis.php

class Avis {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($user_id, $titre, $description, $note) {
        try {
            $query = "INSERT INTO avis (user_id, titre, description, note, date_creation) VALUES (:user_id, :titre, :description, :note, NOW())";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':titre', $titre, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':note', $note, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating avis: " . $e->getMessage());
            return false;
        }
    }
}
?>