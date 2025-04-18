<?php
// views/liste_reclamations_view.php
// ****** THIS FILE NOW ACTS AS BOTH CONTROLLER LOGIC & VIEW ******

// --- Step 1: Controller-like Responsibilities (at the TOP) ---

// Start session if not already started (needed for user ID)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the Model - Adjust path: relative to THIS view file!
// Assumes models/ is a sibling directory to views/ in the parent directory
// Using require_once avoids including it multiple times if this file structure changes
// __DIR__ gives the directory of the current file
require_once __DIR__ . './models/Reclamation.php'; // Adjusted path assuming models/ is sibling to views/

// Authentication Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page (adjust path relative to this view file's location)
    // Assuming login.php is in the same directory or root? Adjust as needed.
    header('Location: ./login.php?error=Session required'); // Use ./ if in same dir, or adjust
    exit; // Stop script execution immediately after redirect
}

// Instantiate the Model
$reclamationModel = null; // Initialize
$reclamations = [];     // Initialize
$errorMessage = null;   // Initialize
$pageTitle = "Mes Réclamations - Green.tn"; // Set default title

try {
    // The model handles its own DB connection
    $reclamationModel = new Reclamation(); // Assumes constructor connects or prepares connection

    // Get Data from Model for the logged-in user
    $user_id = $_SESSION['user_id'];
    $reclamations = $reclamationModel->getByUserId($user_id); // Assuming this method exists

} catch (PDOException $e) { // Catch potential database errors from model/connection
    error_log("View/Controller Error: Database error fetching reclamations for user {$user_id} - " . $e->getMessage());
    $errorMessage = "Could not load your reclamations due to a database issue.";
    // Don't die, let the HTML render with the error message
} catch (Exception $e) { // Catch other errors (e.g., model instantiation, method not found)
    error_log("View/Controller Error: Unexpected error - " . $e->getMessage());
    $errorMessage = "An unexpected error occurred while retrieving reclamations.";
    // Don't die, let the HTML render with the error message
}

// --- Step 2: The View / Presentation Logic (The rest of the file) ---
// Variables $reclamations, $pageTitle, $errorMessage are now set (or default)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Adjust the path relative to this view file's location -->
    <!-- If views/liste_reclamations_view.php, then ../assets/css/style.css is correct for assets/css/style.css -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Your existing styles or link to an external stylesheet -->
    
</head>
<body>

    <!-- En-tête du site -->
   <?php
    include('menu.php') ?>
    <!-- Section principale -->
     
    <main>
        <div class="content-container">
            <h2><?php echo htmlspecialchars($pageTitle); // Display title safely ?></h2>

            <!-- Display error message if one occurred -->
            <?php if ($errorMessage): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Filtres de recherche (Basic structure) -->
            <div class="search-bar">
                <label for="search">Recherche par lieu:</label>
                <input type="text" id="search" placeholder="Entrez un lieu">
                <button type="button" onclick="searchReclamation()">Rechercher</button>
            </div>

            <!-- Table des réclamations -->
            <div class="table-container">
                <?php if (!$errorMessage && empty($reclamations)): // Show only if no error and no data ?>
                    <p class="info-message">Vous n'avez soumis aucune réclamation pour le moment. <a href="../ajouter_reclamation.php">Soumettre une nouvelle réclamation ?</a></p>
                <?php elseif (!empty($reclamations)): // Display table only if reclamations were fetched ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Description (Extrait)</th>
                                <th>Lieu</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <!-- ******* ADDED HEADER ******* -->
                                <th>Répondre</th>
                                <!-- ************************** -->
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reclamations as $reclamation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reclamation['titre']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($reclamation['description'], 0, 100)) . (strlen($reclamation['description']) > 100 ? '...' : ''); // Shorten description ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['lieu']); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['type_probleme']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($reclamation['statut'])); ?></td>
                                    <!-- ******* ADDED DATA CELL ******* -->
                                    <td>
                                        <!-- Adjust path if repondre_reclamation.php is not in the same directory -->
                                        <!-- Assuming it might be in the root or a dedicated controller/action directory -->
                                        <a href="./repondre_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn">Répondre</a>
                                    </td>
                                    <!-- ***************************** -->
                                    <td>
                                        <!-- Adjust links relative to the ROOT or use absolute paths -->
                                        <!-- If view is in /views/ these might need ../ -->
                                        <a href="./modifier_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn">Modifier</a>
                                        <!-- Make sure the path to the delete controller is correct -->
                                        <a href="./controllers/supprimer_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ? Cette action est irréversible.');">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div> <!-- /table-container -->
        </div> <!-- /content-container -->
    </main>

    <!-- Pied de page -->
    <footer>
        <p>© <?php echo date('Y'); ?> Green.tn - Tous droits réservés.</p>
    </footer>

    <!-- Basic JavaScript for Search Button Placeholder -->
    <script>
        function searchReclamation() {
            const searchValue = document.getElementById('search').value;
            alert('Fonction de recherche non implémentée. Recherche pour : ' + encodeURIComponent(searchValue));
            // Example: redirect with search parameter (requires PHP handling)
            // window.location.href = 'liste_reclamations_view.php?search=' + encodeURIComponent(searchValue);
        }
    </script>

</body>
</html>