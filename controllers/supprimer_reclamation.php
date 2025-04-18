<?php
// Start the session
session_start();

// Include the file containing the Database class definition
// Make sure the path is correct relative to supprimer_reclamation.php
// If supprimer_reclamation.php is in the root, the path is 'config/database.php'
// If it's in another folder (e.g., 'actions'), the path is '../config/database.php'
// Based on your original code, '../config/database.php' seems correct.
include '../config/database.php';

// Instantiate the Database class
$database = new Database();
// Get the PDO connection object
$db = $database->getConnection(); // $db now holds the PDO object, equivalent to your old $pdo

// Check if the connection was successful
if (!$db) {
    // You might want to log this error or display a more user-friendly message
    die("Erreur de connexion à la base de données.");
}

// Check if reclamation ID is provided via GET, user is logged in, and user is an admin
if (isset($_GET['id']) && isset($_SESSION['user_role']) ) {
    $reclamation_id = $_GET['id'];

    try {
        // Prepare the DELETE statement using the connection object ($db)
        $stmt = $db->prepare("DELETE FROM reclamations WHERE id = ?");

        // Bind the ID and execute the statement
        $stmt->bindParam(1, $reclamation_id, PDO::PARAM_INT); // Explicitly bind as integer
        $stmt->execute();

        // Redirect to the admin dashboard after successful deletion
        header('Location:  '. $_SERVER['HTTP_REFERER']); // Optional: add a success message
        exit; // Important: stop script execution after redirection

    } catch (PDOException $e) {
        // Handle potential database errors during deletion
        error_log("Error deleting reclamation ID {$reclamation_id}: " . $e->getMessage()); // Log the specific error
        // Redirect back with an error message or display an error page
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        // Or echo "Erreur lors de la suppression de la réclamation : " . $e->getMessage();
        exit;
    }

} else {
    // Handle cases where ID is missing or user is not an authorized admin
    // You could redirect to a login page or show an access denied message
    // For debugging:
    // echo "Accès non autorisé ou ID manquant.";
    // if (isset($_SESSION['user_role'])) {
    //     echo " Rôle actuel : " . htmlspecialchars($_SESSION['user_role']);
    // } else {
    //     echo " Session non démarrée ou rôle non défini.";
    // }

    // For production, maybe redirect:
     header('Location: ../login.php'); // Or to the homepage
    exit;
}
?>