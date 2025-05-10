<?php
require_once 'includes/config.php';
requireLogin();
$currentPage = 'dashboard';

// Define $basePath as the root directory for consistent navigation
$basePath = defined('BASE_PATH') ? BASE_PATH : '/just in case/';

// Enable debug mode for detailed error messages (set to false in production)
$debugMode = true;

try {
    // Attempt database connection
    $pdo = getDBConnection();
    if (!$pdo instanceof PDO) {
        throw new Exception("La connexion à la base de données a renvoyé une valeur invalide.");
    }

    // Test connection with a lightweight query
    $pdo->query("SELECT 1");

    // Database queries
    $stationsStats = $pdo->query("SELECT 
        COUNT(*) as total_stations,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_stations,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_stations
        FROM stations")->fetch();

    $locationStats = $pdo->query("SELECT city, COUNT(*) as count FROM stations GROUP BY city")->fetchAll();
    $statusStats = $pdo->query("SELECT status, COUNT(*) as count FROM stations GROUP BY status")->fetchAll();
    $trajetsStats = $pdo->query("SELECT COUNT(*) as total_trajets FROM trajets")->fetch();
    $trajetCo2 = $pdo->query("SELECT id, description, COALESCE(co2_saved,0) as co2_saved FROM trajets ORDER BY id DESC LIMIT 20")->fetchAll();
    $trajetEnergy = $pdo->query("SELECT id, description, COALESCE(battery_energy,0) as battery_energy, COALESCE(fuel_saved,0) as fuel_saved FROM trajets ORDER BY id DESC LIMIT 20")->fetchAll();
    $trajetDistances = $pdo->query("SELECT id, COALESCE(distance,0) as distance FROM trajets ORDER BY id DESC")->fetchAll();

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    $error = $debugMode ? "Erreur de base de données : " . htmlspecialchars($e->getMessage()) : "Une erreur est survenue lors de la récupération des données. Veuillez réessayer plus tard.";
} catch (Exception $e) {
    error_log("Connection Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    $error = $debugMode ? "Échec de la connexion à la base de données : " . htmlspecialchars($e->getMessage()) : "Échec de la connexion à la base de données. Veuillez réessayer plus tard.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/assets/css/styles.css" rel="stylesheet">
    <link href="public/assets/css/indexmap.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body class="d-flex">
    <?php include 'includes/sidbar.php'; ?>

    <main class="main-content flex-grow-1 p-4" id="main">
        <div class="container-fluid">
            <h1 class="mb-4">Tableau de bord</h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Stations</h5>
                            <p class="card-text">
                                Total: <?= $stationsStats['total_stations'] ?? '0'; ?><br>
                                Actives: <?= $stationsStats['active_stations'] ?? '0'; ?>
                            </p>
                            <div class="d-flex gap-2">
                                <a href="<?php echo $basePath; ?>views/stations/list.php" class="btn btn-primary">
                                    <i class="bi bi-bicycle me-2"></i>Stations
                                </a>
                                <button id="showStatsBtn" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#stationsStatsModal">
                                    <i class="bi bi-bar-chart-fill me-2"></i>Statistiques
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Trajets</h5>
                            <p class="card-text">Total: <?= $trajetsStats['total_trajets'] ?? '0'; ?></p>
                            <div class="d-flex gap-2">
                                <a href="<?php echo $basePath; ?>views/trajets/list.php" class="btn btn-primary">
                                    <i class="bi bi-car me-2"></i>Trajets
                                </a>
                                <button id="showTrajetStatsBtn" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#trajetsStatsModal">
                                    <i class="bi bi-bar-chart-fill me-2"></i>Statistiques
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="row mt-5">
                <div class="col-md-6">
                    <h2>Actions rapides</h2>
                    <div class="list-group">
                        <a href="<?php echo $basePath; ?>views/stations/add.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-plus-circle me-2"></i>Ajouter une station
                        </a>
                        <a href="<?php echo $basePath; ?>views/trajets/add.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-plus-circle me-2"></i>Ajouter un trajet
                        </a>
                    </div>
                </div>
            </section>


        </div>
    </main>

    <?php include 'models/modal_stations.php'; ?>
    <?php include 'models/modal_trajets.php'; ?>

    <script>
        window.statusStats = <?= json_encode($statusStats ?? []); ?>;
        window.locationStats = <?= json_encode($locationStats ?? []); ?>;
        window.trajetCo2 = <?= json_encode($trajetCo2 ?? []); ?>;
        window.trajetEnergy = <?= json_encode($trajetEnergy ?? []); ?>;
        window.trajetDistances = <?= json_encode($trajetDistances ?? []); ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/chart-logic.js"></script>
</body>
</html>