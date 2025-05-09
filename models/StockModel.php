<?php
class StockModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getStockById($id) {
        try {
            $query = "SELECT * FROM stock WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("StockModel::getStockById error: " . $e->getMessage());
            return false;
        }
    }

    public function updateStockQuantity($id, $quantityChange) {
        try {
            $query = "UPDATE stock SET quantity = quantity + :change WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':change' => $quantityChange, ':id' => $id]);
        } catch (Exception $e) {
            error_log("StockModel::updateStockQuantity error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllStock() {
        try {
            $query = "SELECT * FROM stock";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("StockModel::getAllStock error: " . $e->getMessage());
            return [];
        }
    }
}
?>