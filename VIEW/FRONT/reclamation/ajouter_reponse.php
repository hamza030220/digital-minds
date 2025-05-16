<?php
// ajouter_reponse.php

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/'));

// --- Database Connection ---
// Include the database configuration file (defines the Database class).
require_once '../../CONFIG/database.php';

// 1. Instantiate the Database class
$database = new Database();

// 2. Get the PDO connection object by calling the method
$pdo = $database->getConnection();

// 3. Check if the connection was successful (highly recommended)
if (!$pdo) {
    // Log the error for the admin
    error_log("Database connection failed in VIEW/reclamation/ajouter_reponse.php");
    // Show a generic error to the user
    die("Database connection failed. Please try again later or contact support.");
}
// --------------------------

// --- Session and Role Handling ---
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    die("Session required. Please log in.");
}

// Determine user role
$query = "SELECT role FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$role = $user ? $user['role'] : 'user'; // Default to 'user' if role not found
// ---------------------------------

// --- Input Handling ---
// Use null coalescing operator (??) for safer access to POST data
$reclamation_id = $_POST['reclamation_id'] ?? null;
$contenu = $_POST['contenu'] ?? null;

// Basic Validation (add more robust validation as needed)
// Trim contenu to ensure it's not just whitespace
if (empty($reclamation_id) || !is_numeric($reclamation_id) || empty(trim($contenu ?? ''))) {
    // Handle error - redirect back with an error message or display an error
    die("Erreur : Données manquantes ou invalides.");
}
// ----------------------

// --- Database Operation ---
try {
    // Prepare the SQL statement using the valid $pdo object
    $sql = "INSERT INTO reponses (reclamation_id, contenu, role, date_creation) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);

    // Execute the statement with the validated data
    // Trim content again just before insertion (optional)
    $stmt->execute([$reclamation_id, trim($contenu), $role]);

    // If the response is from an admin, add a notification for the user
    if ($role === 'admin') {
        // Get the user ID associated with the reclamation
        $reclamationStmt = $pdo->prepare("SELECT utilisateur_id FROM reclamations WHERE id = ?");
        $reclamationStmt->execute([$reclamation_id]);
        $reclamation = $reclamationStmt->fetch(PDO::FETCH_ASSOC);

        if ($reclamation && $reclamation['utilisateur_id']) {
            $notificationStmt = $pdo->prepare("INSERT INTO notification (user_id, reclamation_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $notificationStmt->execute([$reclamation['utilisateur_id'], $reclamation_id, "admin_responded"]);
        }
    }

    // Redirect on success
    // Use urlencode for the ID in the URL
    header("Location: ./voir_reclamation.php?id=" . urlencode($reclamation_id));
    exit; // Always exit after a header redirect

} catch (PDOException $e) {
    // Handle potential database errors during the query execution
    // In production, log the error:
    error_log("Error inserting response for reclamation ID $reclamation_id: " . $e->getMessage());

    // For development, show the error:
    die("La requête a échoué : " . $e->getMessage());
}
// ----------------------
?>