<?php
class User {
    private $conn;
    private $table = "utilisateurs";

    public $id;
    public $nom;
    public $email;
    public $mot_de_passe;
    public $role;
    public $date_creation;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Méthode pour récupérer un utilisateur par email
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->email = $row['email'];
            $this->mot_de_passe = $row['mot_de_passe'];
            $this->role = $row['role'];
            $this->date_creation = $row['date_creation'];
        }

        return $row;
    }
}
