<?php
// controllers/ListeReclamationsController.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the Model - Adjust path if needed
require_once __DIR__ . '/../models/Reclamation.php'; // Assumes models/ is sibling to controllers/

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?error=Session required');
    exit;
}

// --- Instantiate Model ---
try {
    $reclamationModel = new Reclamation(); // Instantiates your existing model
} catch (Exception $e) { // Catch potential errors during model instantiation (like DB connection)
    error_log("Controller Error: Failed to instantiate Reclamation model - " . $e->getMessage());
    die("A critical error occurred. Please try again later.");
}


// --- Get Data from Model ---
$user_id = $_SESSION['user_id'];
$reclamations = []; // Initialize

try {
    // Calls the NEW getByUserId method you just added to the model
    $reclamations = $reclamationModel->getByUserId($user_id);
} catch (PDOException $e) { // Catch potential database errors during fetch
    error_log("Controller Error: Database error fetching reclamations for user {$user_id} - " . $e->getMessage());
    $errorMessage = "Could not load your reclamations due to a database issue.";
} catch (Exception $e) { // Catch other unexpected errors
    error_log("Controller Error: Unexpected error fetching reclamations for user {$user_id} - " . $e->getMessage());
    $errorMessage = "An unexpected error occurred while loading reclamations.";
}


// --- Prepare Data for View ---
$pageTitle = "Mes Réclamations - Green.tn";


// --- Load the View ---
// Adjust path as needed
require_once __DIR__ . '/../views/liste_reclamations_view.php';

?>