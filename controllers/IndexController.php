<?php
// controllers/HomeController.php
// NOTE: session_start() is removed from here. It should be in the entry script (the view).

// 🔐 Simulation TEMPORAIRE - Role setting remains for now
// This will only work if session_start() was called BEFORE this file is included.
if (isset($_SESSION) && !isset($_SESSION['role'])) {
    $_SESSION['role'] = 'admin'; // or 'utilisateur'
}

// Include Database configuration
// Path is relative to THIS file (HomeController.php)
require('./config/database.php'); 

// --- Database Connection ---
$database = new Database();
$pdo = $database->getConnection();

// Initialize variables to prevent errors if DB connection fails or in catch block
$reclamations_ouvertes = 0;
$reclamations_resolues = 0;
$autres_reclamations = 0;
$types_stats = [];
$db_error = null; // No error initially

// Check if connection was successful
if (!$pdo) {
    // Set an error message if connection failed
    $db_error = "Erreur de connexion à la base de données.";
    // No exit() here, let the view decide how to handle the lack of data / error.
} else {
    // --- Data Fetching (Only if $pdo is valid) ---
    try {
        $stmt_total = $pdo->query("SELECT COUNT(*) AS total FROM reclamations");
        $total_reclamations = $stmt_total->fetchColumn();

        $stmt_ouvert = $pdo->query("SELECT COUNT(*) AS ouvert FROM reclamations WHERE statut = 'ouverte'");
        $reclamations_ouvertes = $stmt_ouvert->fetchColumn();

        $stmt_resolu = $pdo->query("SELECT COUNT(*) AS resolu FROM reclamations WHERE statut = 'resolue'");
        $reclamations_resolues = $stmt_resolu->fetchColumn();

        // Calculate 'autres' status count
        $autres_reclamations = $total_reclamations - ($reclamations_ouvertes + $reclamations_resolues);
        $autres_reclamations = max(0, $autres_reclamations); // Ensure non-negative

        // Stats by Type
        $stmt_type = $pdo->query("SELECT type_probleme AS type, COUNT(*) AS count FROM reclamations GROUP BY type_probleme");
        $types_stats = $stmt_type->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Handle potential query errors gracefully
        error_log("Database Query Error: " . $e->getMessage()); // Log the error
        // Set default values (already done above) and set the error message
        $db_error = "Impossible de récupérer les statistiques pour le moment.";
        // Reset potentially partially fetched data
        $reclamations_ouvertes = 0;
        $reclamations_resolues = 0;
        $autres_reclamations = 0;
        $types_stats = [];
    }
}

// --- Data is Prepared ---
// The variables ($reclamations_ouvertes, $reclamations_resolues, etc.) are now
// defined in the scope where this file was included.

// --- DO NOT Load the View Here ---
// The view file is the one that included this controller.

?>