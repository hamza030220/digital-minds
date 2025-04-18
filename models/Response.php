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

        // Sanitize inputs
        $this->reclamation_id = htmlspecialchars(strip_tags($this->reclamation_id));
        $this->utilisateur_id = htmlspecialchars(strip_tags($this->utilisateur_id));
        $this->contenu = htmlspecialchars(strip_tags($this->contenu));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->date_creation = htmlspecialchars(strip_tags($this->date_creation));

        // Bind parameters
        $stmt->bindParam(':reclamation_id', $this->reclamation_id);
        $stmt->bindParam(':utilisateur_id', $this->utilisateur_id);
        $stmt->bindParam(':contenu', $this->contenu);
        $stmt->bindParam(':role', $this->role);
        

        return $stmt->execute();
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