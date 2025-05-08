<?php
// controllers/DetailController.php

// Set timezone to Tunisia (CET, UTC+1)
date_default_timezone_set('Africa/Tunis');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include translation helper
require_once __DIR__ . '/../translate.php';

// Include the Model and Database
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reclamation.php';

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

class DetailController {
    private $reclamationModel;

    public function __construct() {
        $database = new Database();
        $this->reclamationModel = new Reclamation($database->getConnection());
    }

    public function showDetail() {
        // Initialisation des variables avant toute logique
        $reclamation = [];
        $reponses = [];
        $errorMessage = null;
        $total_reponses = 0;
        $total_pages = 1;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $reclamationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $pageTitle = t('reclamation_details') . ' - Green.tn';

        // Authentication Check: Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . ROOT_PATH . 'login.php?error=' . urlencode(t('error_session_required')));
            exit;
        }

        // Validate reclamation ID
        if ($reclamationId <= 0) {
            header('Location: ' . ROOT_PATH . 'views/liste_reclamations.php?error=' . urlencode(t('invalid_reclamation_id')));
            exit;
        }

        // Fetch data
        try {
            $user_id = $_SESSION['user_id'];
            $reclamation = $this->reclamationModel->getParId($reclamationId);
            error_log("Debug: Reclamation fetched for ID {$reclamationId}: " . print_r($reclamation, true));
            if (!$reclamation || $reclamation['utilisateur_id'] != $user_id) {
                header('Location: ' . ROOT_PATH . 'views/liste_reclamations.php?error=' . urlencode(t('reclamation_not_found')));
                exit;
            }

            // Pagination for responses
            $reponses_per_page = 3;
            $offset = ($page - 1) * $reponses_per_page;

            $total_reponses = $this->reclamationModel->getTotalResponses($reclamationId);
            $total_pages = ceil($total_reponses / $reponses_per_page);
            $reponses = $this->reclamationModel->getResponses($reclamationId, $reponses_per_page, $offset);
        } catch (PDOException $e) {
            error_log("detail.php: Database error fetching reclamation ID {$reclamationId} or responses - " . $e->getMessage());
            $errorMessage = t('error_database_reclamations');
        } catch (Exception $e) {
            error_log("detail.php: Unexpected error - " . $e->getMessage());
            $errorMessage = t('error_unexpected_reclamations');
        }

        // Pass data to view
        require_once __DIR__ . '/../views/detail.php';
    }
}
// detail.php
// Controller logic & view to display a single reclamation's details

// Set timezone to Tunisia (CET, UTC+1)
date_default_timezone_set('Africa/Tunis');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include translation helper
require_once __DIR__ . '/../translate.php';

// Authentication Check: Ensure user is logged in


// --- View / Presentation Logic ---

// --- Execution starts here ---
$controller = new DetailController();
$controller->showDetail();
?>