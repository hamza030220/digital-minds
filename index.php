<?php
session_start();

// 🔐 Simulation TEMPORAIRE
$_SESSION['role'] = 'admin'; // ou 'utilisateur'

// Connexion à la base de données
include 'connexion.php';

// Statistiques globales
$stmt_total = $pdo->query("SELECT COUNT(*) AS total FROM reclamations");
$total_reclamations = $stmt_total->fetch()['total'];

$stmt_ouvert = $pdo->query("SELECT COUNT(*) AS ouvert FROM reclamations WHERE statut = 'ouverte'");
$reclamations_ouvertes = $stmt_ouvert->fetch()['ouvert'];

$stmt_resolu = $pdo->query("SELECT COUNT(*) AS resolu FROM reclamations WHERE statut = 'resolue'");
$reclamations_resolues = $stmt_resolu->fetch()['resolu'];

// Statistiques par type de panne
$stmt_type = $pdo->query("SELECT type_probleme AS type, COUNT(*) AS count FROM reclamations GROUP BY type_probleme");
$types = $stmt_type->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green.tn - Accueil</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<script src="scripts.js"></script>

<body>

<header>
    <div class="logo">
        <h1>Green.tn</h1>
        <p>Mobilité durable, énergie propre</p>
    </div>
    <nav>
        <ul>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="ajouter_reclamation.php">Nouvelle réclamation</a></li>
            <li><a href="liste_reclamations.php">Voir réclamations</a></li>
            <li><a href="#">Mon profil</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="admin_dashboard.php">Espace admin</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>

<main>
    <h2>Bienvenue sur Green.tn</h2>
    <p>Encouragez l'utilisation de vélos générateurs d'énergie et aidez-nous à construire un avenir durable.</p>

    <div class="stats">
        <h3>Statistiques des réclamations</h3>
        <canvas id="statusChart" width="300" height="300"></canvas>
        <canvas id="typeChart" width="300" height="300"></canvas>
    </div>

    <div class="search-bar">
        <label for="search">Recherche par lieu:</label>
        <input type="text" id="search" placeholder="Entrez un lieu">
        <button type="button" onclick="searchReclamation()">Rechercher</button>
    </div>
</main>



<!-- Variables PHP envoyées à JS -->
<script>
    window.stats = {
        ouvert: <?= $reclamations_ouvertes ?>,
        resolu: <?= $reclamations_resolues ?>,
        autres: <?= $total_reclamations - ($reclamations_ouvertes + $reclamations_resolues) ?>,
        types: <?= json_encode($types) ?>
    };
</script>

<script src="scripts.js"></script>
</body>
</html>
