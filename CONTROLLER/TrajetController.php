<?php
require_once '../../includes/config.php';
require_once '../../models/modal_trajets.php';

class TrajetController {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    // List all trajets
    public function list() {
        global $basePath, $currentPage;
        $currentPage = 'trajets';
        $basePath = '../..'; // Root from views/trajets/

        try {
            $stmt = $this->pdo->query("SELECT * FROM trajets ORDER BY id DESC");
            $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            include '../../views/trajets/list.php';
        } catch (PDOException $e) {
            error_log("TrajetController::list Error: " . $e->getMessage());
            $error = "Erreur lors de la récupération des trajets.";
            include 'views/trajets/list.php';
        }
    }

    // Show add trajet form
    public function add() {
        global $basePath, $currentPage;
        $currentPage = 'trajets';
        $basePath = '../..';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
                $distance = filter_input(INPUT_POST, 'distance', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $co2_saved = filter_input(INPUT_POST, 'co2_saved', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $battery_energy = filter_input(INPUT_POST, 'battery_energy', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $fuel_saved = filter_input(INPUT_POST, 'fuel_saved', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $stmt = $this->pdo->prepare("INSERT INTO trajets (description, distance, co2_saved, battery_energy, fuel_saved) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$description, $distance, $co2_saved, $battery_energy, $fuel_saved]);

                header("Location: {$basePath}views/trajets/list.php?success=Trajet ajouté avec succès");
                exit;
            } catch (PDOException $e) {
                error_log("TrajetController::add Error: " . $e->getMessage());
                $error = "Erreur lors de l'ajout du trajet.";
            }
        }
        include 'views/trajets/add.php';
    }

    // Show edit trajet form
    public function edit() {
        global $basePath, $currentPage;
        $currentPage = 'trajets';
        $basePath = '../..';

        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$id) {
            header("Location: {$basePath}views/trajets/list.php?error=ID invalide");
            exit;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM trajets WHERE id = ?");
            $stmt->execute([$id]);
            $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$trajet) {
                header("Location: {$basePath}views/trajets/list.php?error=Trajet non trouvé");
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
                $distance = filter_input(INPUT_POST, 'distance', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $co2_saved = filter_input(INPUT_POST, 'co2_saved', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $battery_energy = filter_input(INPUT_POST, 'battery_energy', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $fuel_saved = filter_input(INPUT_POST, 'fuel_saved', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $stmt = $this->pdo->prepare("UPDATE trajets SET description = ?, distance = ?, co2_saved = ?, battery_energy = ?, fuel_saved = ? WHERE id = ?");
                $stmt->execute([$description, $distance, $co2_saved, $battery_energy, $fuel_saved, $id]);

                header("Location: {$basePath}views/trajets/list.php?success=Trajet mis à jour avec succès");
                exit;
            }
            include 'views/trajets/edit.php';
        } catch (PDOException $e) {
            error_log("TrajetController::edit Error: " . $e->getMessage());
            $error = "Erreur lors de la mise à jour du trajet.";
            include 'views/trajets/edit.php';
        }
    }

    // Delete a trajet
    public function delete() {
        global $basePath;
        $basePath = '../..';

        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$id) {
            header("Location: {$basePath}views/trajets/list.php?error=ID invalide");
            exit;
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM trajets WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: {$basePath}views/trajets/list.php?success=Trajet supprimé avec succès");
            exit;
        } catch (PDOException $e) {
            error_log("TrajetController::delete Error: " . $e->getMessage());
            header("Location: {$basePath}views/trajets/list.php?error=Erreur lors de la suppression du trajet");
            exit;
        }
    }
}

// Instantiate and route actions
$controller = new TrajetController();
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