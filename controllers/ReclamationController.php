<?php
// controllers/ReclamationController.php

// Start session at the very beginning ALWAYS
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the Model - Adjust path as needed
require_once '../models/Reclamation.php';

class ReclamationController {

    private $reclamationModel;
    private $userId = null;
    private $isLoggedIn = false;

    public function __construct() {
        $this->reclamationModel = new Reclamation();
        $this->checkLogin();
    }

    private function checkLogin() {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
             $this->userId = $_SESSION['user_id'];
             $this->isLoggedIn = true;
        } else {
             // --- FOR TESTING --- Remove/comment line below when login is implemented
             // $this->userId = 1; $this->isLoggedIn = true; // TEMPORARY: Assume user 1 is logged in
             // --- END TESTING ---

             if(!isset($this->userId)) { // Check if not set by testing override
                $this->isLoggedIn = false;
                $this->userId = null;
             }
        }
    }

    /**
     * Handles ONLY the POST request from the form.
     * Processes data, sets flash messages in session, and redirects.
     */
    public function handlePostRequest() {
        // Only process POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // If accessed directly via GET, redirect to the form page
            header('Location: ../ajouter_reclamation.php');
            exit;
        }

        // Ensure user is logged in to process POST
        if (!$this->isLoggedIn) {
            // Store error message in session
            $_SESSION['flash_message'] = "Accès refusé. Vous devez être connecté pour soumettre.";
            $_SESSION['flash_message_type'] = 'error';
            // Redirect back to the form page
            header('Location: ../ajouter_reclamation.php');
            exit;
        }

        // Keep submitted data in session in case of error to repopulate form
        $_SESSION['form_data_flash'] = $_POST;

        // Retrieve and sanitize/validate form data
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $lieu = trim($_POST['lieu'] ?? '');
        $type_probleme = $_POST['type_probleme'] ?? '';

        // Basic Server-side Validation
        if (empty($titre) || empty($description) || empty($lieu) || empty($type_probleme)) {
            $_SESSION['flash_message'] = "Erreur : Tous les champs sont obligatoires.";
            $_SESSION['flash_message_type'] = 'error';
            // Redirect back to the form page (form data is already in session)
            header('Location: ../ajouter_reclamation.php');
            exit;
        }

        // Validation passed, attempt to add using the model
        $success = $this->reclamationModel->ajouter($titre, $description, $lieu, $type_probleme, $this->userId);

        if ($success) {
            $_SESSION['flash_message'] = "Réclamation ajoutée avec succès!";
            $_SESSION['flash_message_type'] = 'success';
            unset($_SESSION['form_data_flash']); // Clear form data on success
        } else {
            $_SESSION['flash_message'] = "Erreur lors de l'ajout de la réclamation. Veuillez réessayer.";
            $_SESSION['flash_message_type'] = 'error';
            // Keep $_SESSION['form_data_flash'] so the form can be repopulated
        }

        // Redirect back to the form page regardless of success or failure
        header('Location: ../ajouter_reclamation.php');
        exit; // ALWAYS exit after a header redirect

    } // End handlePostRequest

} // End class ReclamationController

// --- Execution starts here ---

// Instantiate the controller
$controller = new ReclamationController();
// Handle the POST request (this will process and redirect)
$controller->handlePostRequest();

?>