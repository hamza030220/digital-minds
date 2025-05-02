<?php
require_once __DIR__ . '/../config/db.php';

class User {
    // Obtenir un utilisateur par ID
    public static function getById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Obtenir tous les utilisateurs, avec filtres (nom, tri par âge)
    public static function getAll($filters = []) {
        global $pdo;
        $sql = "SELECT id, nom, prenom, email, telephone, role, age, gouvernorats FROM users WHERE 1=1";
        $params = [];

        if (!empty($filters['nom'])) {
            $sql .= " AND nom LIKE :nom";
            $params[':nom'] = '%' . $filters['nom'] . '%';
        }

        if (!empty($filters['age_sort']) && in_array($filters['age_sort'], ['asc', 'desc'])) {
            $sql .= " ORDER BY age " . strtoupper($filters['age_sort']);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Obtenir les statistiques par rôle
    public static function getRoleStats() {
        global $pdo;
        $stmt = $pdo->query("SELECT role, COUNT(*) AS count FROM users GROUP BY role");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Trouver un utilisateur par email
    public static function findByEmail($email) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Vérifier un mot de passe (avec hash)
    public static function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }

    // Créer un utilisateur
    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, mot_de_passe, telephone, role, age, gouvernorats)
                               VALUES (:nom, :prenom, :email, :mot_de_passe, :telephone, :role, :age, :gouvernorats)");
        $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':mot_de_passe' => password_hash($data['mot_de_passe'], PASSWORD_DEFAULT),
            ':telephone'    => $data['telephone'],
            ':role'         => strtolower(trim($data['role'])),
            ':age'          => $data['age'],
            ':gouvernorats' => $data['gouvernorats']
        ]);
        return $pdo->lastInsertId();
    }

    // Mettre à jour un utilisateur
    public static function update($id, $data) {
        global $pdo;
        $sql = "UPDATE users SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, 
                role = :role, age = :age, gouvernorats = :gouvernorats WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'           => $id,
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':telephone'    => $data['telephone'],
            ':role'         => strtolower(trim($data['role'])),
            ':age'          => $data['age'],
            ':gouvernorats' => $data['gouvernorats']
        ]);
    }

    // Supprimer un utilisateur
    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
