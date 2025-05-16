<?php
require_once __DIR__ . '/../config/ok.php';

class RepairModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllRepairs() {
        $query = "SELECT id, bike_id, bike_type, problem, status, progression, stock_id 
                  FROM repairs";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("RepairModel::getAllRepairs error: " . $e->getMessage());
            throw new Exception('Query failed: ' . $e->getMessage());
        }
    }

    public function addRepair($bike_id, $bike_type, $problem, $status, $progression, $stock_id) {
        $query = "INSERT INTO repairs (bike_id, bike_type, problem, status, progression, stock_id) 
                  VALUES (:bike_id, :bike_type, :problem, :status, :progression, :stock_id)";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':bike_id' => $bike_id,
                ':bike_type' => $bike_type,
                ':problem' => $problem,
                ':status' => $status,
                ':progression' => (int)$progression,
                ':stock_id' => (int)$stock_id
            ]);
        } catch (PDOException $e) {
            error_log("RepairModel::addRepair error: " . $e->getMessage());
            throw new Exception('Insert failed: ' . $e->getMessage());
        }
    }

    public function updateRepair($id, $bike_id, $bike_type, $problem, $status, $progression, $stock_id) {
        $query = "UPDATE repairs SET bike_id = :bike_id, bike_type = :bike_type, problem = :problem, 
                  status = :status, progression = :progression, stock_id = :stock_id 
                  WHERE id = :id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':id' => (int)$id,
                ':bike_id' => $bike_id,
                ':bike_type' => $bike_type,
                ':problem' => $problem,
                ':status' => $status,
                ':progression' => (int)$progression,
                ':stock_id' => (int)$stock_id
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("RepairModel::updateRepair error: " . $e->getMessage());
            throw new Exception('Update failed: ' . $e->getMessage());
        }
    }

    public function deleteRepair($id) {
    $query = "DELETE FROM repairs WHERE id = :id";
    try {
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("RepairModel::deleteRepair error for id $id: " . $e->getMessage());
        throw new Exception('Delete failed: ' . $e->getMessage());
    }
}
}
?>