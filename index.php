<?php
session_start();


// Connexion à la base de données using Database class
// Make sure the path 'config/database.php' is correct relative to index.php
require_once 'config/database.php'; // Changed from include 'connexion.php';

$database = new Database();
$pdo = $database->getConnection(); // Get PDO object from the Database class

// Check if connection failed - Exit cleanly if it does
if (!$pdo) {
    // You might want to log this error as well
    // error_log("Database connection failed in index.php");
    die("Erreur: Impossible de se connecter à la base de données pour le moment."); // Stop script execution
}

/*  // --- KEEP THE FOLLOWING STATISTICS LOGIC EXACTLY THE SAME ---

// Statistiques globales
$stmt_total = $pdo->query("SELECT COUNT(*) AS total FROM reclamations");
// Add check if query failed before fetching
$total_reclamations = $stmt_total ? $stmt_total->fetch()['total'] : 0;

$stmt_ouvert = $pdo->query("SELECT COUNT(*) AS ouvert FROM reclamations WHERE statut = 'ouverte'");
// Add check if query failed
$reclamations_ouvertes = $stmt_ouvert ? $stmt_ouvert->fetch()['ouvert'] : 0;

// Use 'résolue' to match potential values elsewhere - KEEP 'resolue' if that's what your DB uses
$stmt_resolu = $pdo->query("SELECT COUNT(*) AS resolu FROM reclamations WHERE statut = 'resolue'");
// Add check if query failed
$reclamations_resolues = $stmt_resolu ? $stmt_resolu->fetch()['resolu'] : 0;

// Statistiques par type de panne
// KEEP 'type' alias if your JS depends on it
$stmt_type = $pdo->query("SELECT type_probleme AS type, COUNT(*) AS count FROM reclamations GROUP BY type_probleme");
// Add check if query failed
$types = $stmt_type ? $stmt_type->fetchAll(PDO::FETCH_ASSOC) : [];

// Ensure variables are integers for calculations/JS
$total_reclamations = (int)$total_reclamations;
$reclamations_ouvertes = (int)$reclamations_ouvertes;
$reclamations_resolues = (int)$reclamations_resolues;*/

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green.tn - Accueil</title>
    <link rel="stylesheet" href="style.css"> <!-- Ensure path is correct -->
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<!-- KEEP THE SCRIPT TAG HERE IF IT WAS ORIGINALLY HERE -->
<script src="scripts.js"></script>

<body>

<!-- KEEP THE MENU INCLUDE EXACTLY THE SAME -->
<?php include("menu.php") ?>

<!-- KEEP THE MAIN HTML CONTENT EXACTLY THE SAME -->
<main>
    <h2>Bienvenue sur Green.tn</h2>
    <p>Encouragez l'utilisation de vélos générateurs d'énergie et aidez-nous à construire un avenir durable.</p>

    <div class="stats">
        
        <canvas id="statusChart" width="300" height="300"></canvas>
        <canvas id="typeChart" width="300" height="300"></canvas>
    </div>


</main>

<!-- KEEP THE JAVASCRIPT VARIABLES AND SCRIPT INCLUDE EXACTLY THE SAME -->
<!-- Variables PHP envoyées à JS -->
<script>
    // Make sure the calculation for 'autres' still makes sense with your fetched data
    // It might be better to explicitly query for 'en cours' status count if needed
    let autres_count = <?= $total_reclamations ?> - (<?= $reclamations_ouvertes ?> + <?= $reclamations_resolues ?>);
    autres_count = Math.max(0, autres_count); // Ensure it's not negative

    window.stats = {
        ouvert: <?= $reclamations_ouvertes ?>,
        resolu: <?= $reclamations_resolues ?>,
        autres: autres_count, // Use calculated value
        // Ensure $types is correctly encoded - json_encode handles arrays/objects
        types: <?= json_encode($types) ?>
    };
</script>

<!-- KEEP THE FINAL SCRIPT INCLUDE EXACTLY THE SAME -->
<script src="scripts.js"></script>
</body>
</html>