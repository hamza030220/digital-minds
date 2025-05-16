<?php
class Velo {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all available velos
    public function getAllAvailable() {
        $stmt = $this->pdo->query("SELECT * FROM velos WHERE disponibilite = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get velo by ID
    public function getById($id_velo) {
        $stmt = $this->pdo->prepare("SELECT * FROM velos WHERE id_velo = ?");
        $stmt->execute([$id_velo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update velo availability
    public function updateAvailability($id_velo, $disponibilite) {
        $stmt = $this->pdo->prepare("UPDATE velos SET disponibilite = ? WHERE id_velo = ?");
        return $stmt->execute([$disponibilite, $id_velo]);
    }
}
?>