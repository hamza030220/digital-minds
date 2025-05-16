<?php
// controllers/AvisController.php

session_start();
require_once '../CONFIG/database.php';
require_once __DIR__ . '/../MODEL/Avis.php'; // Inclure le modèle Avis
require_once __DIR__ . '/../translate.php';

class AvisController {
    private $avisModel;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->avisModel = new Avis($db); // Initialiser le modèle Avis
    }

    public function addAvis() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = t('login_required');
            header("Location: ../VIEW/reclamation/ajouter_avis.php");
            exit();
        }

        // Vérifier si la requête est un POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../VIEW/reclamation/ajouter_avis.php");
            exit();
        }

        // Récupérer les données du formulaire
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $note = isset($_POST['note']) ? (int)$_POST['note'] : 0;
        $user_id = $_SESSION['user_id'];

        // Validation côté serveur
        $errors = [];

        if (empty($titre)) {
            $errors['titre'] = t('title_required');
        } elseif (strlen($titre) < 5 || strlen($titre) > 100) {
            $errors['titre'] = t('title_length');
        }

        if (empty($description)) {
            $errors['description'] = t('description_required');
        } elseif (strlen($description) < 10 || strlen($description) > 500) {
            $errors['description'] = t('description_length');
        }

        if ($note < 1 || $note > 5) {
            $errors['note'] = t('rating_required');
        }

        // Si des erreurs sont détectées, rediriger avec les données du formulaire
        if (!empty($errors)) {
            $_SESSION['form_data_flash'] = [
                'titre' => $titre,
                'description' => $description,
                'note' => $note
            ];
            $_SESSION['flash_message'] = implode('<br>', array_values($errors));
            $_SESSION['flash_message_type'] = 'error';
            header("Location: ../VIEW/reclamation/ajouter_avis.php");
            exit();
        }

        // Utiliser le modèle Avis pour insérer l'avis
        $success = $this->avisModel->create($user_id, $titre, $description, $note);

        if ($success) {
            $_SESSION['flash_message'] = t('review_submitted');
            $_SESSION['flash_message_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = t('error_occurred');
            $_SESSION['flash_message_type'] = 'error';
        }

        header("Location: ../VIEW/reclamation/ajouter_avis.php");
        exit();
    }
}

// Exécuter l'action
$controller = new AvisController();
$controller->addAvis();