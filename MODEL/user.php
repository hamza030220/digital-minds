<?php
class User {
    private $id;
    private $nom;
    private $prenom;
    private $email;
    private $telephone;
    private $age;
    private $gouvernorats;
    private $photo;
    private $password;
    private $role;
    private $createdAt;

    public function __construct($nom, $prenom, $email, $password, $role = 'user', $telephone = null, $age = null, $gouvernorats = null, $photo = 'default.jpg') {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_BCRYPT);
        $this->role = $role;
        $this->telephone = $telephone;
        $this->age = $age;
        $this->gouvernorats = $gouvernorats;
        $this->photo = $photo;
        $this->createdAt = date('Y-m-d H:i:s');
    }

    // Save a new user to the database
    public function save($pdo) {
        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, telephone, age, gouvernorats, photo, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$this->nom, $this->prenom, $this->email, $this->telephone, $this->age, $this->gouvernorats, $this->photo, $this->password, $this->role, $this->createdAt]);
    }

    // Find a user by ID
    public static function findById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Delete a user by ID
    public static function delete($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Get all users
    public static function getAllUsers($pdo) {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>