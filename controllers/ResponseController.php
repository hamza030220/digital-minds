<?php
class ResponseController {
    private $db;
    private $response;

    public function __construct() {
        // Initialize database connection
        require_once 'config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();

        // Initialize Response model
        require_once 'models/Response.php';
        $this->response = new Response($this->db);
    }

    // Handle response creation
    public function createResponse($reclamation_id, $utilisateur_id, $contenu) {
        if (empty($contenu)) {
            return [
                'status' => 'error',
                'message' => 'Le contenu de la réponse est requis'
            ];
        }
    
        // Fetch the role from the utilisateurs table using utilisateur_id
        $query = "SELECT role FROM utilisateurs WHERE id = :utilisateur_id";
        $stmt = $this->db->prepare($query); // Use $this->db directly
        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result || empty($result['role'])) {
            return [
                'status' => 'error',
                'message' => 'Utilisateur non trouvé ou rôle non défini'
            ];
        }
    
        $role = $result['role'];
    
        // Assign values to the response object
        $this->response->reclamation_id = $reclamation_id;
        $this->response->utilisateur_id = $utilisateur_id;
        $this->response->contenu = $contenu;
        $this->response->role = $role;
        $this->response->date_creation = date('Y-m-d H:i:s');
    
        if ($this->response->create()) {
            return [
                'status' => 'success',
                'message' => 'Réponse ajoutée avec succès'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Erreur lors de l\'ajout de la réponse'
            ];
        }
    }

    // Get responses for a specific reclamation
    public function getResponsesByReclamation($reclamation_id) {
        return $this->response->readByReclamation($reclamation_id);
    }

    // Get single response
    public function getResponse($id) {
        return $this->response->readSingle($id);
    }
}