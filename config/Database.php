<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'green_tn';
    private $username = 'root'; // Adjust as needed
    private $password = ''; // Adjust as needed
    private $conn;

    /**
     * Establishes and returns a PDO database connection
     * @return PDO
     * @throws PDOException
     */
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            // Log error to a file for debugging
            file_put_contents(__DIR__ . '/../database_error.log', "Connection failed: " . $e->getMessage() . " at " . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
        return $this->conn;
    }
}
?>