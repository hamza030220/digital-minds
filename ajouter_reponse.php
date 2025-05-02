<?php // ajouter_reponse.php

// Start session if needed (e.g., for user authentication or flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Database Connection ---
// Include the database configuration file (defines the Database class).
// Using __DIR__ makes the path relative to *this* file, which is more reliable.
require_once __DIR__ . '/config/database.php';

// 1. Instantiate the Database class
$database = new Database();

// 2. Get the PDO connection object by calling the method
$pdo = $database->getConnection(); // <-- This line actually creates $pdo

// 3. Check if the connection was successful (highly recommended)
if (!$pdo) {
    // Log the error for the admin
    error_log("Database connection failed in ajouter_reponse.php");
    // Show a generic error to the user
    die("Database connection failed. Please try again later or contact support.");
    // Or redirect with an error message if using sessions
    // $_SESSION['error_message'] = "Database connection failed.";
    // header("Location: some_error_page.php"); // Or back to the form
    // exit;
}
// --------------------------


// --- Input Handling ---
// Use null coalescing operator (??) for safer access to POST data
$reclamation_id = $_POST['reclamation_id'] ?? null;
$contenu = $_POST['contenu'] ?? null;
$role = $_POST['role'] ?? null; // Expecting 'utilisateur' or 'admin'

// Basic Validation (add more robust validation as needed)
// Trim contenu to ensure it's not just whitespace
if (empty($reclamation_id) || !is_numeric($reclamation_id) || empty(trim($contenu ?? '')) || empty($role)) {
    // Handle error - redirect back with an error message or display an error
    // Example: Store error in session and redirect
    // session_start(); // Make sure session is started if you use this
    // $_SESSION['error_message'] = "Données invalides. Veuillez remplir tous les champs.";
    // // Redirect back to the specific reclamation page if possible
    // $redirect_url = $reclamation_id ? "voir_reclamation.php?id=" . urlencode($reclamation_id) : "liste_reclamations.php";
    // header("Location: " . $redirect_url);
    // exit;

    // Or simply die for now (not recommended for production)
    die("Erreur : Données manquantes ou invalides.");
}
// Ensure role is one of the expected values (optional but good)
if (!in_array($role, ['utilisateur', 'admin'])) {
     die("Erreur : Rôle invalide.");
}
// ----------------------


// --- Database Operation ---
// This block should now work because $pdo is correctly initialized above
try {
    // Prepare the SQL statement using the valid $pdo object
    $sql = "INSERT INTO reponses (reclamation_id, contenu, role, date_creation) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql); // Line 37 (approx) should now work

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
            $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, reclamation_id, message) VALUES (?, ?, ?)");
            $notificationStmt->execute([$reclamation['utilisateur_id'], $reclamation_id, "admin_responded"]);
        }
    }

    // Redirect on success
    // Use urlencode for the ID in the URL
    // Add a success message (optional, using session)
    // session_start(); // Make sure session is started
    // $_SESSION['success_message'] = "Réponse ajoutée avec succès.";
    header("Location: voir_reclamation.php?id=" . urlencode($reclamation_id));
    exit; // Always exit after a header redirect

} catch (PDOException $e) {
    // Handle potential database errors during the query execution
    // In production, log the error:
    error_log("Error inserting response for reclamation ID $reclamation_id: " . $e->getMessage());

    // Display a generic error to the user or redirect with an error flag:
    // session_start(); // Make sure session is started
    // $_SESSION['error_message'] = "Impossible d'enregistrer votre réponse. Veuillez réessayer.";
    // header("Location: voir_reclamation.php?id=" . urlencode($reclamation_id));
    // exit;

    // For development, show the error:
    die("La requête a échoué : " . $e->getMessage());
}
// ----------------------

?>