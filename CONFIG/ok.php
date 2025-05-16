<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Database {
    private $host = 'localhost';
    private $db_name = 'projet';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }
}

// Create PDO instance for API scripts
$db = new Database();
$pdo = $db->getConnection();
?>