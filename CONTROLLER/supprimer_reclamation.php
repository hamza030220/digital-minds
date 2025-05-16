<?php
// Start the session
session_start();

// Include the file containing the Database class definition
// Make sure the path is correct relative to supprimer_reclamation.php
// If supprimer_reclamation.php is in CONTROLLER, the path is '../CONFIG/database.php'
include '../CONFIG/database.php';

// Instantiate the Database class
$database = new Database();
// Get the PDO connection object
$db = $database->getConnection(); // $db now holds the PDO object, equivalent to your old $pdo

// Check if the connection was successful
if (!$db) {
    // You might want to log this error or display a more user-friendly message
    die("Erreur de connexion à la base de données.");
}

// Check if reclamation ID is provided via GET, user is logged in, and user is an admin or owner
if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $reclamation_id = $_GET['id'];

    try {
        // Check if the user is the owner or an admin
        $stmt = $db->prepare("SELECT utilisateur_id FROM reclamations WHERE id = ?");
        $stmt->execute([$reclamation_id]);
        $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reclamation) {
            $user_id = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $isAdmin = $user && $user['role'] === 'admin';

            if ($isAdmin || $reclamation['utilisateur_id'] == $user_id) {
                // Prepare the DELETE statement using the connection object ($db)
                $stmt = $db->prepare("DELETE FROM reclamations WHERE id = ?");

                // Bind the ID and execute the statement
                $stmt->bindParam(1, $reclamation_id, PDO::PARAM_INT); // Explicitly bind as integer
                $stmt->execute();

                // Redirect to the admin dashboard or referring page after successful deletion
                header('Location: ' . $_SERVER['HTTP_REFERER']); // Optional: add a success message
                exit; // Important: stop script execution after redirection
            } else {
                // User is not the owner or an admin
                header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=' . urlencode("Accès non autorisé, vous n'êtes pas le créateur de cette réclamation."));
                exit;
            }
        } else {
            // Reclamation not found
            header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=' . urlencode("Réclamation non trouvée."));
            exit;
        }
    } catch (PDOException $e) {
        // Handle potential database errors during deletion
        error_log("Error deleting reclamation ID {$reclamation_id}: " . $e->getMessage()); // Log the specific error
        // Redirect back with an error message
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=' . urlencode("Erreur lors de la suppression de la réclamation."));
        exit;
    }

} else {
    // Handle cases where ID is missing or user is not logged in
    // For production, redirect to login page or homepage
    header('Location: ../VIEW/BACK/login.php'); // Adjusted path to login.php
    exit;
}
?>