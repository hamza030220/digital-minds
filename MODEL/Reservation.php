<?php
class Reservation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new reservation
    public function create($id_client, $id_velo, $date_debut, $date_fin, $gouvernorat, $telephone, $statut = 'en_attente') {
        $stmt = $this->pdo->prepare("
            INSERT INTO reservations (id_client, id_velo, date_debut, date_fin, gouvernorat, telephone, duree_reservation, date_reservation, statut)
            VALUES (?, ?, ?, ?, ?, ?, DATEDIFF(?, ?), NOW(), ?)
        ");
        return $stmt->execute([$id_client, $id_velo, $date_debut, $date_fin, $gouvernorat, $telephone, $date_fin, $date_debut, $statut]);
    }

    // Get all reservations
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM reservations ORDER BY date_reservation DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get reservations by client ID
    public function getByClientId($id_client) {
        $stmt = $this->pdo->prepare("SELECT * FROM reservations WHERE id_client = ? ORDER BY date_reservation DESC");
        $stmt->execute([$id_client]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update reservation status
    public function updateStatus($id_reservation, $statut) {
        $stmt = $this->pdo->prepare("UPDATE reservations SET statut = ? WHERE id_reservation = ?");
        return $stmt->execute([$statut, $id_reservation]);
    }

    // Delete a reservation
    public function delete($id_reservation) {
        $stmt = $this->pdo->prepare("DELETE FROM reservations WHERE id_reservation = ?");
        return $stmt->execute([$id_reservation]);
    }
}
?>