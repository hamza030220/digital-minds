<?php
require_once '../../includes/config.php';
require_once '../../models/modal_stations.php';

class StationController {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    // List all stations
    public function list() {
        global $basePath, $currentPage;
        $currentPage = 'stations';
        $basePath = '../..'; // Root from views/stations/

        try {
            $stmt = $this->pdo->query("SELECT * FROM stations ORDER BY id DESC");
            $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            include '../../views/stations/list.php';
        } catch (PDOException $e) {
            error_log("StationController::list Error: " . $e->getMessage());
            $error = "Erreur lors de la récupération des stations.";
            include 'views/stations/list.php';
        }
    }

    // Show add station form
    public function add() {
        global $basePath, $currentPage;
        $currentPage = 'stations';
        $basePath = '../..';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
                $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
                $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
                $latitude = filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $longitude = filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $stmt = $this->pdo->prepare("INSERT INTO stations (name, city, status, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $city, $status, $latitude, $longitude]);

                header("Location: {$basePath}/views/stations/list.php?success=Station ajoutée avec succès");
                exit;
            } catch (PDOException $e) {
                error_log("StationController::add Error: " . $e->getMessage());
                $error = "Erreur lors de l'ajout de la station.";
            }
        }
        include 'views/stations/add.php';
    }

    // Show edit station form
    public function edit() {
        global $basePath, $currentPage;
        $currentPage = 'stations';
        $basePath = '../..';

        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$id) {
            header("Location: {$basePath}/views/stations/list.php?error=ID invalide");
            exit;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM stations WHERE id = ?");
            $stmt->execute([$id]);
            $station = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$station) {
                header("Location: {$basePath}/views/stations/list.php?error=Station non trouvée");
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
                $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
                $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
                $latitude = filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $longitude = filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $stmt = $this->pdo->prepare("UPDATE stations SET name = ?, city = ?, status = ?, latitude = ?, longitude = ? WHERE id = ?");
                $stmt->execute([$name, $city, $status, $latitude, $longitude, $id]);

                header("Location: {$basePath}/views/stations/list.php?success=Station mise à jour avec succès");
                exit;
            }
            include 'views/stations/edit.php';
        } catch (PDOException $e) {
            error_log("StationController::edit Error: " . $e->getMessage());
            $error = "Erreur lors de la mise à jour de la station.";
            include 'views/stations/edit.php';
        }
    }

    // Delete a station
    public function delete() {
        global $basePath;
        $basePath = '../..';

        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$id) {
            header("Location: {$basePath}/views/stations/list.php?error=ID invalide");
            exit;
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM stations WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: {$basePath}/views/stations/list.php?success=Station supprimée avec succès");
            exit;
        } catch (PDOException $e) {
            error_log("StationController::delete Error: " . $e->getMessage());
            header("Location: {$basePath}/views/stations/list.php?error=Erreur lors de la suppression de la station");
            exit;
        }
    }
}

// Instantiate and route actions
$controller = new StationController();
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) ?: 'list';

switch ($action) {
    case 'add':
        $controller->add();
        break;
    case 'edit':
        $controller->edit();
        break;
    case 'delete':
        $controller->delete();
        break;
    default:
        $controller->list();
        break;
}