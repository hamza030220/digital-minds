<?php
if (file_exists('../config/database.php')) {
    require_once '../config/database.php';
} elseif (file_exists('config/database.php')) {
    require_once 'config/database.php';
} else {
    die('Could not load database configuration.');
}


class Reclamation {
    private $pdo;

    public $id;
    public $utilisateur_id;
    public $titre;
    public $description;
    public $lieu;
    public $type_probleme;
    public $date_creation;
    public $statut;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection(); // Connexion via la classe Database
    }

    public function getStatistiquesParStatut() {
        $stmt = $this->pdo->query("SELECT statut, COUNT(*) as total FROM reclamations GROUP BY statut");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getToutesLesReclamations() {
        $stmt = $this->pdo->query("SELECT * FROM reclamations ORDER BY date_creation DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getParId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM reclamations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function supprimer($id) {
        $stmt = $this->pdo->prepare("DELETE FROM reclamations WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function ajouter($titre, $description, $lieu, $type_probleme, $utilisateur_id) {
        $date_creation = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("INSERT INTO reclamations (titre, description, lieu, type_probleme, utilisateur_id, date_creation, statut) 
                                     VALUES (?, ?, ?, ?, ?, ?, 'ouverte')");
        return $stmt->execute([$titre, $description, $lieu, $type_probleme, $utilisateur_id, $date_creation]);
    }
    // ***** ADD THIS NEW METHOD *****
    /**
     * Fetches all reclamations for a specific user ID.
     *
     * @param int $userId The ID of the user.
     * @return array An array of reclamation records for the user, or empty array if none/error.
     */
    public function getByUserId($userId) { // Using $userId to avoid conflict with class property
        // Use prepare and execute for security, even with integer IDs
        $stmt = $this->pdo->prepare("SELECT * FROM reclamations WHERE utilisateur_id = ? ORDER BY date_creation DESC");
        // PDO can often infer the type, but binding explicitly is safer
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $reclamations ? $reclamations : []; // Return results or empty array
    }
    
    // ***** END OF NEW METHOD *****
    // Add updateStatus method if you added it previously
    public function updateStatus(int $id, string $newStatus): bool {
        $allowed_statuses = ['ouverte', 'en cours', 'résolue'];
        if (!in_array($newStatus, $allowed_statuses)) {
             error_log("Attempted to set invalid status '{$newStatus}' for reclamation ID {$id}");
             return false;
        }
        $sql = "UPDATE reclamations SET statut = :status WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating status for reclamation ID {$id}: " . $e->getMessage());
            return false;
        }
     }


    // ***** ADD THIS NEW METHOD for Filtered Export *****
    /**
     * Fetches reclamations based on provided filter criteria.
     *
     * @param array $filters Associative array of filters (e.g., ['lieu' => 'Tunis', 'statut' => 'ouverte']).
     * @return array An array of reclamation records matching the filters, or empty array on error/no results.
     */
    public function getFilteredReclamations(array $filters = []): array {
        // Select specific columns needed for the PDF to be efficient
        $sql = "SELECT id, titre, description, lieu, type_probleme, statut, date_creation
                FROM reclamations
                WHERE 1=1"; // Base query, 1=1 makes adding AND clauses easier
        $params = []; // Array to hold parameters for prepared statement

        // Apply filters securely using named placeholders
        if (!empty($filters['lieu'])) {
            $sql .= " AND lieu LIKE :lieu";
            $params[':lieu'] = "%" . $filters['lieu'] . "%"; // Add wildcards for LIKE search
        }
        if (!empty($filters['type_probleme'])) {
            $sql .= " AND type_probleme = :type_probleme";
            $params[':type_probleme'] = $filters['type_probleme'];
        }
        if (!empty($filters['statut'])) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        // Add ordering - useful for reports
        $sql .= " ORDER BY date_creation DESC";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Execute the statement with parameters (if any)
            // execute() can take the associative array directly if using named placeholders
            $stmt->execute($params);

            $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $reclamations ? $reclamations : []; // Return results or empty array

        } catch (PDOException $e) {
            // Log database errors instead of dying
            error_log("Error fetching filtered reclamations: " . $e->getMessage());
            return []; // Return empty array on database error
        }
    }
    // ***** END OF NEW METHOD *****
    // ***** ADD METHODS NEEDED FOR INDEX PAGE *****

    /**
     * Gets the total count of all reclamations.
     * @return int Total count, or 0 on error.
     */
    public function getTotalCount(): int {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM reclamations");
            // fetchColumn is suitable for single value result
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total reclamation count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Gets the count of reclamations for a specific status.
     * @param string $status The status to count ('ouverte', 'en cours', 'résolue').
     * @return int Count for the specified status, or 0 on error.
     */
    public function getCountByStatus(string $status): int {
        // Basic validation of allowed status values
        $allowed_statuses = ['ouverte', 'en cours', 'résolue'];
        if (!in_array($status, $allowed_statuses)) {
            error_log("Invalid status requested in getCountByStatus: " . htmlspecialchars($status));
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM reclamations WHERE statut = :status";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting count for status '{$status}': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Gets the count of reclamations grouped by type_probleme.
     * Renamed to avoid potential confusion with getStatistiquesParStatut.
     * @return array Array of ['type_probleme' => type, 'count' => count], or empty array on error.
     */
    public function getStatsByTypeProbleme(): array {
        $sql = "SELECT type_probleme, COUNT(*) AS count
                FROM reclamations
                GROUP BY type_probleme
                ORDER BY count DESC"; // Optional ordering
        try {
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Ensure it always returns an array
            return $result ?: [];
        } catch (PDOException $e) {
            error_log("Error getting stats by type_probleme: " . $e->getMessage());
            return [];
        }
    }

    // ***** END OF METHODS NEEDED FOR INDEX PAGE *****

    // ***** ADD THIS NEW METHOD for Updating Reclamation Details *****
    /**
     * Updates the details of a specific reclamation.
     *
     * @param int    $id          The ID of the reclamation to update.
     * @param string $titre       The new title.
     * @param string $description The new description.
     * @param string $lieu        The new location.
     * @param string $type_probleme The new problem type.
     * @return bool True on success, false on failure.
     */
    public function updateDetails(int $id, string $titre, string $description, string $lieu, string $type_probleme): bool {
        // Optional: Add validation for $type_probleme against allowed values if needed
        $allowed_types = ['mecanique', 'batterie', 'ecran', 'pneu', 'Infrastructure', 'Autre']; // Example
        if (!in_array($type_probleme, $allowed_types)) {
            error_log("Attempted to update reclamation ID {$id} with invalid type: {$type_probleme}");
            return false;
        }

        $sql = "UPDATE reclamations
                SET titre = :titre,
                    description = :description,
                    lieu = :lieu,
                    type_probleme = :type_probleme
                WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':titre', $titre, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':lieu', $lieu, PDO::PARAM_STR);
            $stmt->bindParam(':type_probleme', $type_probleme, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error updating details for reclamation ID {$id}: " . $e->getMessage());
            return false;
        }
    }
    // ***** END OF NEW UPDATE METHOD *****
 // Nouvelle méthode pour le total des réponses
 public function getTotalResponses($reclamationId): int {
    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reponses WHERE reclamation_id = ?");
    $stmt->execute([$reclamationId]);
    return (int) $stmt->fetchColumn();
}

// Nouvelle méthode pour récupérer les réponses paginées
public function getResponses($reclamationId, $limit, $offset): array {
    $stmt = $this->pdo->prepare("SELECT contenu, date_creation, role FROM reponses WHERE reclamation_id = ? ORDER BY date_creation ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $reclamationId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

} // End of class Reclamation