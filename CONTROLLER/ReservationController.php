<?php
require_once __DIR__ . '/../MODEL/Reservation.php';

class ReservationController {
    private $reservationModel;

    public function __construct($pdo) {
        $this->reservationModel = new Reservation($pdo);
    }

    // Create a new reservation
    public function createReservation($id_client, $id_velo, $date_debut, $date_fin, $gouvernorat, $telephone) {
        return $this->reservationModel->create($id_client, $id_velo, $date_debut, $date_fin, $gouvernorat, $telephone);
    }

    // Get all reservations
    public function getAllReservations() {
        return $this->reservationModel->getAll();
    }

    // Get reservations by client ID
    public function getReservationsByClient($id_client) {
        return $this->reservationModel->getByClientId($id_client);
    }

    // Update reservation status
    public function updateReservationStatus($id_reservation, $statut) {
        return $this->reservationModel->updateStatus($id_reservation, $statut);
    }

    // Delete a reservation
    public function deleteReservation($id_reservation) {
        return $this->reservationModel->delete($id_reservation);
    }
}
?>