<?php
/**
 * TrajetModel Class
 * 
 * Handles all database operations related to trajets (routes)
 */
class TrajetModel {
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
     * Get all trajets with optional pagination
     * 
     * @param int $page Current page number
     * @param int $itemsPerPage Number of items per page
     * @return array Array with trajets data and pagination info
     */
    public function getAll($page = 1, $itemsPerPage = 10) {
        try {
            // Calculate offset for pagination
            $offset = ($page - 1) * $itemsPerPage;
            
            // Get total number of trajets for pagination
            $totalStmt = $this->pdo->query("SELECT COUNT(*) FROM trajets");
            $totalTrajets = $totalStmt->fetchColumn();
            $totalPages = ceil($totalTrajets / $itemsPerPage);
            
            // Get trajets with station names for current page
            $stmt = $this->pdo->prepare("
                SELECT t.*, 
                    start_station.name AS start_station_name, 
                    end_station.name AS end_station_name
                FROM trajets t
                JOIN stations start_station ON t.start_station_id = start_station.id
                JOIN stations end_station ON t.end_station_id = end_station.id
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, $itemsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $trajets = $stmt->fetchAll();
            
            // Return trajets data along with pagination info
            return [
                'trajets' => $trajets,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalTrajets' => $totalTrajets,
                    'itemsPerPage' => $itemsPerPage
                ]
            ];
        } catch (PDOException $e) {
            $this->error = "Error retrieving trajets: " . $e->getMessage();
            error_log($this->error);
            return [
                'error' => $this->error
            ];
        }
    }

    /**
     * Get trajet by ID with station information
     * 
     * @param int $id Trajet ID
     * @return array|false Trajet data or false if not found
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, 
                    start_station.name AS start_station_name, 
                    end_station.name AS end_station_name
                FROM trajets t
                JOIN stations start_station ON t.start_station_id = start_station.id
                JOIN stations end_station ON t.end_station_id = end_station.id
                WHERE t.id = ?
            ");
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $trajet = $stmt->fetch();
            if (!$trajet) {
                $this->error = "Trajet not found";
                return false;
            }
            
            return $trajet;
        } catch (PDOException $e) {
            $this->error = "Error retrieving trajet: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Validate station existence
     * 
     * @param int $stationId Station ID to check
     * @return boolean True if station exists and is active
     */
    private function validateStation($stationId) {
        try {
            $stmt = $this->pdo->prepare("SELECT status FROM stations WHERE id = ?");
            $stmt->bindValue(1, $stationId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            if (!$result) {
                $this->error = "Station with ID $stationId does not exist";
                return false;
            }
            
            if ($result['status'] !== 'active') {
                $this->error = "Station with ID $stationId is not active";
                return false;
            }
            
            return true;
        } catch (PDOException $e) {
            $this->error = "Error validating station: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Validate and prepare route coordinates for storage
     * 
     * @param mixed $coordinates Route coordinates data
     * @return string|null JSON string or null
     */
    private function prepareCoordinates($coordinates) {
        // If empty, return null
        if (empty($coordinates)) {
            return null;
        }
        
        // If already a JSON string, validate it
        if (is_string($coordinates)) {
            $decoded = json_decode($coordinates, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error = "Invalid JSON format for route coordinates";
                return false;
            }
            return $coordinates;
        }
        
        // If array, encode to JSON
        if (is_array($coordinates)) {
            return json_encode($coordinates);
        }
        
        $this->error = "Invalid format for route coordinates";
        return false;
    }

    /**
     * Create a new trajet
     * 
     * @param array $data Trajet data
     * @return int|false New trajet ID or false on failure
     */
    public function create($data) {
        try {
            // Validate required fields
            $requiredFields = ['start_station_id', 'end_station_id', 'distance', 'description'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->error = "Field '$field' is required";
                    return false;
                }
            }
            
            // Validate start and end stations
            if (!$this->validateStation($data['start_station_id'])) {
                return false;
            }
            
            if (!$this->validateStation($data['end_station_id'])) {
                return false;
            }
            
            // Validate start and end stations are different
            if ($data['start_station_id'] == $data['end_station_id']) {
                $this->error = "Start and end stations cannot be the same";
                return false;
            }
            
            // Validate distance is numeric and positive
            if (!is_numeric($data['distance']) || $data['distance'] <= 0) {
                $this->error = "Distance must be a positive number";
                return false;
            }
            
            // Prepare route coordinates
            $routeCoordinates = null;
            if (isset($data['route_coordinates']) && !empty($data['route_coordinates'])) {
                $routeCoordinates = $this->prepareCoordinates($data['route_coordinates']);
                if ($routeCoordinates === false) {
                    return false;
                }
            }
            
            // Prepare route description
            $routeDescription = isset($data['route_description']) ? $data['route_description'] : null;
            
            // Insert trajet
            $stmt = $this->pdo->prepare("
                INSERT INTO trajets (
                    start_station_id, 
                    end_station_id, 
                    distance, 
                    description, 
                    route_coordinates,
                    route_description
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bindValue(1, $data['start_station_id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $data['end_station_id'], PDO::PARAM_INT);
            $stmt->bindValue(3, $data['distance'], PDO::PARAM_STR);
            $stmt->bindValue(4, $data['description'], PDO::PARAM_STR);
            $stmt->bindValue(5, $routeCoordinates, PDO::PARAM_STR);
            $stmt->bindValue(6, $routeDescription, PDO::PARAM_STR);
            $stmt->execute();
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->error = "Error creating trajet: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Update existing trajet
     * 
     * @param int $id Trajet ID
     * @param array $data Trajet data to update
     * @return boolean Success status
     */
    public function update($id, $data) {
        try {
            // Validate trajet exists
            if (!$this->getById($id)) {
                return false;
            }
            
            // Validate required fields
            $requiredFields = ['start_station_id', 'end_station_id', 'distance', 'description'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->error = "Field '$field' is required";
                    return false;
                }
            }
            
            // Validate start and end stations
            if (!$this->validateStation($data['start_station_id'])) {
                return false;
            }
            
            if (!$this->validateStation($data['end_station_id'])) {
                return false;
            }
            
            // Validate start and end stations are different
            if ($data['start_station_id'] == $data['end_station_id']) {
                $this->error = "Start and end stations cannot be the same";
                return false;
            }
            
            // Validate distance is numeric and positive
            if (!is_numeric($data['distance']) || $data['distance'] <= 0) {
                $this->error = "Distance must be a positive number";
                return false;
            }
            
            // Prepare route coordinates
            $routeCoordinates = null;
            if (isset($data['route_coordinates']) && !empty($data['route_coordinates'])) {
                $routeCoordinates = $this->prepareCoordinates($data['route_coordinates']);
                if ($routeCoordinates === false) {
                    return false;
                }
            }
            
            // Prepare route description
            $routeDescription = isset($data['route_description']) ? $data['route_description'] : null;
            
            // Update trajet
            $stmt = $this->pdo->prepare("
                UPDATE trajets 
                SET start_station_id = ?, 
                    end_station_id = ?, 
                    distance = ?, 
                    description = ?, 
                    route_coordinates = ?,
                    route_description = ?
                WHERE id = ?
            ");
            $stmt->bindValue(1, $data['start_station_id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $data['end_station_id'], PDO::PARAM_INT);
            $stmt->bindValue(3, $data['distance'], PDO::PARAM_STR);
            $stmt->bindValue(4, $data['description'], PDO::PARAM_STR);
            $stmt->bindValue(5, $routeCoordinates, PDO::PARAM_STR);
            $stmt->bindValue(6, $routeDescription, PDO::PARAM_STR);
            $stmt->bindValue(7, $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error updating trajet: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Delete a trajet
     * 
     * @param int $id Trajet ID
     * @return boolean Success status
     */
    public function delete($id) {
        try {
            // Verify trajet exists
            if (!$this->getById($id)) {
                return false;
            }
            
            // Delete trajet
            $stmt = $this->pdo->prepare("DELETE FROM trajets WHERE id = ?");
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error = "Error deleting trajet: " . $e->getMessage();
            error_log($this->error);
            return false;
        }
    }

    /**
     * Get statistics about trajets
     * 
     * @return array Trajet statistics
     */
    public function getStatistics() {
        try {
            // Get total number of trajets
            $totalStmt = $this->pdo->query("SELECT COUNT(*) as total_trajets FROM trajets");
            $totalTrajets = $totalStmt->fetch();
            
            // Get total distance of all trajets
            $distanceStmt = $this->pdo->query("SELECT SUM(distance) as total_distance FROM trajets");
            $totalDistance = $distanceStmt->fetch();
            
            // Get average distance
            $avgStmt = $this->pdo->query("SELECT AVG(distance) as avg_distance FROM trajets");
            $avgDistance = $avgStmt->fetch();
            
            return [
                'total_trajets' => $totalTrajets['total_trajets'] ?? 0,
                'total_distance' => $totalDistance['total_distance'] ?? 0,
                'avg_distance' => $avgDistance['avg_distance'] ?? 0
            ];
        } catch (PDOException $e) {
            $this->error = "Error retrieving trajet statistics: " . $e->getMessage();
            error_log($this->error);
            return [
                'total_trajets' => 0,
                'total_distance' => 0,
                'avg_distance' => 0
            ];
        }
    }

    /**
     * Get trajets by station ID
     * 
     * @param int $stationId Station ID
     * @return array Trajets associated with the station
     */
    public function getByStation($stationId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, 
                    start_station.name AS start_station_name, 
                    end_station.name AS end_station_name
                FROM trajets t
                JOIN stations start_station ON t.start_station_id = start_station.id
                JOIN stations end_station ON t.end_station_id = end_station.id
                WHERE t.start_station_id = ? OR t.end_station_id = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->bindValue(1, $stationId, PDO::PARAM_INT);
            $stmt->bindValue(2, $stationId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->error = "Error retrieving trajets for station: " . $e->getMessage();
            error_log($this->error);
            return [];
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
