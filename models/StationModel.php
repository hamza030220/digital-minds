<?php
/**
 * StationModel Class
 * 
 * Handles all database operations related to stations
 */
class StationModel {
    private $pdo;
    private $error;

    /**
     * Constructor - establishes database connection
     */
    public function __construct() {
        try {
            $this->pdo = getDBConnection();
        } catch (PDOException $e) {
            $this->error = "Database connection failed: " . $e->getMessage();
            error_log($this->error);
        }
    }

    /**
     * Get all stations with optional pagination
     * 
     * @param int $page Current page number
     * @param int $itemsPerPage Number of items per page
     * @return array Array with stations data and pagination info
     */
    public function getAll($page = 1, $itemsPerPage = 10) {
        try {
            // Calculate offset for pagination
            $offset = ($page - 1) * $itemsPerPage;
            
            // Get total number of stations for pagination
            $totalStmt = $this->pdo->query("SELECT COUNT(*) FROM stations");
            $totalStations = $totalStmt->fetchColumn();
            $totalPages = ceil($totalStations / $itemsPerPage);
            
            // Get stations for current page
            $stmt = $this->pdo->prepare("SELECT * FROM stations ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bindValue(1, $itemsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $stations = $stmt->fetchAll();
            
            // Return stations data along with pagination info
            return [
                'stations' => $stations,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalStations' => $totalStations,
                    'itemsPerPage' => $itemsPerPage
                ]
            ];
        } catch (PDOException $e) {
            $this->error = "Error retrieving stations: " . $e->getMessage();
            error_log($this->error);
            return [
                'error' => $this->error
            ];
        }
    }

    /**
     * Get station by ID
     * 
     * @param int $id Station ID
     * @return array|false Station data or false if not found
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM stations WHERE id = ?");
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $station = $stmt->fetch();
            if (!$station) {
                $this->error = "Station not found";
                return false;
            }
            
            return $station;
        } catch (PDOException $e) {
            $this->error = "Error retrieving station: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Create a new station
     * 
     * @param array $data Station data (name, location, status)
     * @return int|false New station ID or false on failure
     */
    public function create($data) {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['location'])) {
                $this->error = "Name and location are required";
                return false;
            }
            
            // Use default status if not provided
            $status = isset($data['status']) ? $data['status'] : 'active';
            
            // Insert station
            $stmt = $this->pdo->prepare("
                INSERT INTO stations (name, location, status) 
                VALUES (?, ?, ?)
            ");
            $stmt->bindValue(1, $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(2, $data['location'], PDO::PARAM_STR);
            $stmt->bindValue(3, $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->error = "Error creating station: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Update existing station
     * 
     * @param int $id Station ID
     * @param array $data Station data to update
     * @return boolean Success status
     */
    public function update($id, $data) {
        try {
            // Validate station exists
            if (!$this->getById($id)) {
                return false;
            }
            
            // Validate required fields
            if (empty($data['name']) || empty($data['location'])) {
                $this->error = "Name and location are required";
                return false;
            }
            
            // Update station
            $stmt = $this->pdo->prepare("
                UPDATE stations 
                SET name = ?, location = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->bindValue(1, $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(2, $data['location'], PDO::PARAM_STR);
            $stmt->bindValue(3, $data['status'], PDO::PARAM_STR);
            $stmt->bindValue(4, $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error updating station: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Delete a station
     * 
     * @param int $id Station ID
     * @return boolean Success status
     */
    public function delete($id) {
        try {
            // Check if station is used in trajets
            $checkStmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM trajets 
                WHERE start_station_id = ? OR end_station_id = ?
            ");
            $checkStmt->bindValue(1, $id, PDO::PARAM_INT);
            $checkStmt->bindValue(2, $id, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                $this->error = "Cannot delete: Station is used in one or more trajets";
                return false;
            }
            
            // Delete station
            $stmt = $this->pdo->prepare("DELETE FROM stations WHERE id = ?");
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error deleting station: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Get statistics about stations
     * 
     * @return array Station statistics
     */
    public function getStatistics() {
        try {
            $stmt = $this->pdo->query("SELECT 
                COUNT(*) as total_stations,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_stations
                FROM stations");
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->error = "Error retrieving station statistics: " . $e->getMessage();
            error_log($this->error);
            return [
                'total_stations' => 0,
                'active_stations' => 0
            ];
        }
    }

    /**
     * Toggle station status between active and inactive
     * 
     * @param int $id Station ID
     * @return boolean Success status
     */
    public function toggleStatus($id) {
        try {
            $station = $this->getById($id);
            if (!$station) {
                return false;
            }
            
            $newStatus = ($station['status'] === 'active') ? 'inactive' : 'active';
            
            $stmt = $this->pdo->prepare("UPDATE stations SET status = ? WHERE id = ?");
            $stmt->bindValue(1, $newStatus, PDO::PARAM_STR);
            $stmt->bindValue(2, $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error toggling station status: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Get error message
     * 
     * @return string Error message
     */
    public function getError() {
        return $this->error;
    }
}

