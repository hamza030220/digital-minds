<?php
class Response {
    private $conn;
    private $table_name = "reponses";

    public $id;
    public $reclamation_id;
    public $utilisateur_id;
    public $contenu;
    public $role;
    public $date_creation;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new response
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (reclamation_id, utilisateur_id, contenu, role, date_creation) 
                 VALUES (:reclamation_id, :utilisateur_id, :contenu, :role, NOW())";

        $stmt = $this->conn->prepare($query);

        // Bind parameters (no need for htmlspecialchars here, PDO handles security)
        $stmt->bindParam(':reclamation_id', $this->reclamation_id, PDO::PARAM_INT);
        $stmt->bindParam(':utilisateur_id', $this->utilisateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':contenu', $this->contenu, PDO::PARAM_STR);
        $stmt->bindParam(':role', $this->role, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return true;
        } else {
            // Optional: Log or debug the error
            // error_log("Failed to create response: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
    }

    // Read responses by reclamation_id
    public function readByReclamation($reclamation_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE reclamation_id = ? ORDER BY date_creation DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$reclamation_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Read single response
    public function readSingle($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

