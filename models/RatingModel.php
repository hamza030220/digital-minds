<?php
require_once __DIR__ . '/../config/Database.php';

class RatingModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Add a rating for a repair
     * @param int $repair_id
     * @param int $rating
     * @return bool
     * @throws Exception
     */
    public function addRating($repair_id, $rating) {
        try {
            $query = "INSERT INTO repair_ratings (repair_id, rating) VALUES (:repair_id, :rating)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':repair_id', $repair_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed to add rating: " . $e->getMessage());
        }
    }

    /**
     * Get the average rating for a repair
     * @param int $repair_id
     * @return float|null
     * @throws Exception
     */
    public function getAverageRating($repair_id) {
        try {
            $query = "SELECT AVG(rating) as average_rating FROM repair_ratings WHERE repair_id = :repair_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':repair_id', $repair_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['average_rating'] ? (float) $result['average_rating'] : null;
        } catch (PDOException $e) {
            throw new Exception("Failed to get average rating: " . $e->getMessage());
        }
    }
}
?>