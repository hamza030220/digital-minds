<?php
class ResponseController {
    private $db;
    private $response;

    public function __construct() {
        // Initialize database connection
        require_once __DIR__ . '/../CONFIG/database.php';
        $database = new Database();
        $this->db = $database->getConnection();

        // Initialize Response model
        require_once __DIR__ . '/../MODEL/Response.php';
        $this->response = new Response($this->db);
    }

    // Handle response creation
    public function createResponse($reclamation_id, $utilisateur_id, $contenu, $role = null) {
        // Validate inputs
        $reclamation_id = filter_var($reclamation_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $utilisateur_id = filter_var($utilisateur_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if (!$reclamation_id || !$utilisateur_id) {
            return [
                'status' => 'error',
                'message' => 'ID de réclamation ou d\'utilisateur invalide'
            ];
        }

        if (empty($contenu)) {
            return [
                'status' => 'error',
                'message' => 'Le contenu de la réponse est requis'
            ];
        }

        // Fetch the role from the users table using utilisateur_id
        $query = "SELECT role FROM users WHERE id = :utilisateur_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || empty($result['role'])) {
            return [
                'status' => 'error',
                'message' => 'Utilisateur non trouvé ou rôle non défini'
            ];
        }

        $role = $role ?? $result['role']; // Use provided role if exists, otherwise use fetched role

        // Assign values to the response object
        $this->response->reclamation_id = $reclamation_id;
        $this->response->utilisateur_id = $utilisateur_id;
        $this->response->contenu = $contenu;
        $this->response->role = $role;
        $this->response->date_creation = date('Y-m-d H:i:s'); // e.g., 2025-05-15 01:13:00

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
?>

