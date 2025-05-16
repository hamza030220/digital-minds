<?php
// models/Avis.php

class Avis {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($user_id, $titre, $description, $note) {
        try {
            // Débogage : Afficher les valeurs des paramètres
            error_log("Creating avis with user_id: $user_id, titre: $titre, description: $description, note: $note");

            // Insert the review into the avis table
            $query = "INSERT INTO avis (user_id, titre, description, note, date_creation) VALUES (:user_id, :titre, :description, :note, NOW())";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':titre', $titre, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':note', $note, PDO::PARAM_INT);
            $success = $stmt->execute();

            if (!$success) {
                error_log("Failed to insert avis into database.");
                return false;
            }

            // If the review was successfully created, generate a notification for admins
            try {
                // Get the ID of the newly created review (optional, if you want to link the notification to the review)
                $avis_id = $this->pdo->lastInsertId();

                // Find all users with role 'admin'
                $adminQuery = "SELECT id FROM users WHERE role = 'admin'";
                $adminStmt = $this->pdo->prepare($adminQuery);
                $adminStmt->execute();
                $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($admins)) {
                    error_log("No admins found to notify.");
                } else {
                    // Insert a notification for each admin
                    $notificationQuery = "INSERT INTO notification (user_id, reclamation_id, message, created_at) VALUES (:user_id, NULL, :message, NOW())";
                    $notificationStmt = $this->pdo->prepare($notificationQuery);
                    foreach ($admins as $admin) {
                        $notificationStmt->execute([
                            ':user_id' => $admin['id'],
                            ':message' => 'new_review_submitted'
                        ]);
                    }
                }
            } catch (PDOException $e) {
                // Loguer l'erreur de notification, mais ne pas échouer l'opération principale
                error_log("Error creating notification: " . $e->getMessage());
            }

            return $success;
        } catch (PDOException $e) {
            error_log("Error creating avis: " . $e->getMessage());
            return false;
        }
    }
}
?>