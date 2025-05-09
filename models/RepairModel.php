<?php
require_once __DIR__ . '/../config/Database.php';

class RepairModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllRepairs() {
        $query = "SELECT id, bike_id, bike_type, problem, status, progression, stock_id FROM repairs";
        try {
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getAllRepairs error: " . $e->getMessage());
            throw new Exception('Query failed: ' . $e->getMessage());
        }
    }

    public function addRepair($bike_id, $bike_type, $problem, $status, $progression, $stock_id) {
        $query = "INSERT INTO repairs (bike_id, bike_type, problem, status, progression, stock_id) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$bike_id, $bike_type, $problem, $status, $progression, $stock_id]);
        } catch (PDOException $e) {
            error_log("addRepair error: " . $e->getMessage());
            throw new Exception('Insert failed: ' . $e->getMessage());
        }
    }

    public function updateRepair($id, $bike_id, $bike_type, $problem, $status, $progression, $stock_id) {
        $query = "UPDATE repairs SET bike_id = ?, bike_type = ?, problem = ?, status = ?, progression = ?, stock_id = ? 
                  WHERE id = ?";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$bike_id, $bike_type, $problem, $status, $progression, $stock_id, $id]);
        } catch (PDOException $e) {
            error_log("updateRepair error: " . $e->getMessage());
            throw new Exception('Update failed: ' . $e->getMessage());
        }
    }

    public function deleteRepair($id) {
        $query = "DELETE FROM repairs WHERE id = ?";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("deleteRepair error for id $id: " . $e->getMessage());
            throw new Exception('Delete failed: ' . $e->getMessage());
        }
    }
}