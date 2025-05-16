<?php
class Database {
    private $host = "localhost";
    private $db_name = "projet"; // Mise à jour pour utiliser projet
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name}", 
                                  $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>