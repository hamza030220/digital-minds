

<?php
require_once 'includes/config.php';

// Require login
requireLogin();
// Set current page for sidebar highlighting
$currentPage = 'dashboard';
$basePath = '';

// Get statistics
try {
    $pdo = getDBConnection();
    
    // Count active and total stations
    $stmtStations = $pdo->query("SELECT 
        COUNT(*) as total_stations,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_stations
        FROM stations");
    $stationsStats = $stmtStations->fetch();
    
    // Count total trajets
    $stmtTrajets = $pdo->query("SELECT COUNT(*) as total_trajets FROM trajets");
    $trajetsStats = $stmtTrajets->fetch();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Erreur lors de la récupération des statistiques.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
   
    <?php include 'includes/sidbar.php'; ?>
    <!-- Main Content -->
    <div class="main-content" id="main">
        <div class="container-fluid p-4">
        <h1 class="mb-4">Tableau de bord</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Stations</h5>
                        <p class="card-text">
                            Total: <?php echo isset($stationsStats) ? $stationsStats['total_stations'] : '0'; ?><br>
                            Actives: <?php echo isset($stationsStats) ? $stationsStats['active_stations'] : '0'; ?>
                        </p>
                        <a href="stations/list.php" class="btn btn-primary">Gérer les stations</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Trajets</h5>
                        <p class="card-text">
                            Total: <?php echo isset($trajetsStats) ? $trajetsStats['total_trajets'] : '0'; ?>
                        </p>
                        <a href="trajets/list.php" class="btn btn-primary">Gérer les trajets</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <h2>Actions rapides</h2>
                <div class="list-group">
                    <a href="stations/add.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-plus-circle"></i> Ajouter une station
                    </a>
                    <a href="trajets/add.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-plus-circle"></i> Ajouter un trajet
                    </a>
                </div>
            </div>
        </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
</body>
</html>

