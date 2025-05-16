<?php
require_once __DIR__ . '/../MODEL/Velo.php'; // Include the Velo model

if (!class_exists('Velo')) {
    die('The Velo class could not be found. Check the file path and class definition.');
}

class VeloController {
    private $veloModel;

    public function __construct($pdo) {
        $this->veloModel = new Velo($pdo); // Initialize the Velo model
    }

    // Get all available velos
    public function getAvailableVelos() {
        return $this->veloModel->getAllAvailable();
    }

    // Get velo by ID
    public function getVeloById($id_velo) {
        return $this->veloModel->getById($id_velo);
    }

    // Update velo availability
    public function updateVeloAvailability($id_velo, $disponibilite) {
        return $this->veloModel->updateAvailability($id_velo, $disponibilite);
    }
}
?>