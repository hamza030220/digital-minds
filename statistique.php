```php
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

// --- KEEP THE FOLLOWING STATISTICS LOGIC EXACTLY THE SAME ---

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
$reclamations_resolues = (int)$reclamations_resolues;

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
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    /* Sidebar Styles */
    .sidebar {
        width: 200px;
        background-color: rgb(10, 73, 15);
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        padding-top: 20px;
        color: white;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .sidebar .logo {
        text-align: center;
        margin-bottom: 20px;
    }

    .sidebar .logo h1 {
        margin: 0;
        font-size: 1.8em;
        color: #28a745;
    }

    .sidebar .logo p {
        margin: 0;
        font-size: 0.8em;
        color: #adb5bd;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar ul li {
        margin: 10px 0;
    }

    .sidebar ul li a {
        color: white;
        text-decoration: none;
        padding: 10px 20px;
        display: block;
        font-size: 1em;
    }

    .sidebar ul li a:hover {
        background-color: #28a745;
        border-radius: 0 20px 20px 0;
    }

    /* Adjust container to account for sidebar */
    .container {
        margin-left: 220px;
        width: calc(90% - 220px);
        max-width: 1200px;
        margin-top: 20px;
        margin-bottom: 20px;
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
    }

    h1, h2 {
        color: #2C3E50;
    }

    button {
        background-color: #3498db;
        color: white;
        border: none;
        padding: 10px 20px;
        margin: 10px 0;
        cursor: pointer;
        border-radius: 5px;
    }

    button:hover {
        background-color: #2980b9;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    table, th, td {
        border: 1px solid #ddd;
    }

    th, td {
        padding: 10px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
    }

    input, select {
        padding: 5px;
        margin: 10px 0;
        margin-right: 5px;
        min-width: 150px;
    }

    .stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-around;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    /* Ensure consistent canvas sizes */
    .stats canvas {
        width: 300px !important;
        height: 300px !important;
        max-width: 300px;
        max-height: 300px;
    }

    /* Style for chart labels */
    .chart-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .chart-container h4 {
        margin: 0 0 10px 0;
        color: #2C3E50;
        font-size: 1.1em;
    }

    .stat {
        background-color: #ecf0f1;
        padding: 10px 15px;
        border-radius: 5px;
        text-align: center;
        flex-grow: 1;
        min-width: 100px;
    }

    .search-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f0f0f0;
        border-radius: 5px;
    }

    .search-form input, .search-form select {
        flex-grow: 1;
        margin: 0;
    }

    .search-form button {
        padding: 8px 15px;
        background-color: #2ecc71;
        border: none;
        color: white;
        cursor: pointer;
        margin: 0;
    }

    .search-form button:hover {
        background-color: #27ae60;
    }

    .export-form {
        margin-top: 20px;
        padding: 15px;
        background-color: #f0f0f0;
        border-radius: 5px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }

    .export-form button {
        background-color: #16a085;
    }

    .export-form button:hover {
        background-color: #117a65;
    }
</style>
<!-- KEEP THE SCRIPT TAG HERE IF IT WAS ORIGINALLY HERE -->
<script src="scripts.js"></script>
<!-- Sidebar Navigation -->
<div class="sidebar">
    <div class="logo">
        <h1>Green.tn</h1>
        <p>Mobilité durable, énergie propre</p>
    </div>
    <ul>
        <li><a href="">🏠 Accueil</a></li>
        <li><a href="">🚲 Reservation</a></li>
        <li><a href="reclamations as_utilisateur.php">📋 Reclamation</a></li>
        <li><a href="statistique.php">📊 Statistique</a></li>
        <li><a href="logout.php">🔓 Déconnexion</a></li>
    </ul>
</div>
<body>
<!-- KEEP THE MAIN HTML CONTENT EXACTLY THE SAME -->
<main>
    <h3>Statistiques des réclamations</h3>
    <div class="stats">
        <div class="chart-container">
            <h4>Par statut</h4>
            <canvas id="statusChart" width="300" height="300"></canvas>
        </div>
        <div class="chart-container">
            <h4>Par type</h4>
            <canvas id="typeChart" width="300" height="300"></canvas>
        </div>
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
```