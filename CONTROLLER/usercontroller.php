<?php
require_once __DIR__ . '/../MODEL/user.php';

class UserController {
    // Delete a user and move them to the trash
    public static function deleteUser($pdo, $userId) {
        // Check if the user exists
        $user = User::findById($pdo, $userId);
        if (!$user) {
            return ['error' => 'Utilisateur introuvable'];
        }

        // Prevent deleting an admin
        if ($user['role'] === 'admin') {
            return ['error' => 'Impossible de supprimer un administrateur'];
        }

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Move user to the trash
            $stmt = $pdo->prepare("
                INSERT INTO trash_users (id, nom, prenom, email, telephone, role, age, gouvernorats, cin, deleted_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user['id'],
                $user['nom'],
                $user['prenom'],
                $user['email'],
                $user['telephone'],
                $user['role'],
                $user['age'],
                $user['gouvernorats'],
                $user['cin']
            ]);

            // Delete the user from the users table
            User::delete($pdo, $userId);

            // Commit transaction
            $pdo->commit();

            return ['success' => 'Utilisateur déplacé vers la corbeille avec succès'];
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            return ['error' => 'Erreur lors de la suppression : ' . $e->getMessage()];
        }
    }

    // Fetch user details by ID
    public static function getUserDetails($pdo, $userId) {
        $user = User::findById($pdo, $userId);
        if (!$user) {
            return ['error' => 'Utilisateur introuvable'];
        }
        return ['success' => true, 'user' => $user];
    }
}
?>