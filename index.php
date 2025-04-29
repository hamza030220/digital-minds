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
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_stations,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_stations
        FROM stations");
    $stationsStats = $stmtStations->fetch();

    // Get stations per city for bar chart
    $stmtCities = $pdo->query("SELECT city, COUNT(*) as count FROM stations GROUP BY city");
    $locationStats = $stmtCities->fetchAll();



    // Get all stations with status for pie chart
    $stmtStatus = $pdo->query("SELECT status, COUNT(*) as count FROM stations GROUP BY status");
    $statusStats = $stmtStatus->fetchAll();

    // Count total trajets
    $stmtTrajets = $pdo->query("SELECT COUNT(*) as total_trajets FROM trajets");
    $trajetsStats = $stmtTrajets->fetch();

    // --- NEW: Fetch data for trajet statistics ---
    // 1. CO2 saved per trajet (id, description, co2_saved)
    $trajetCo2 = $pdo->query("SELECT id, description, COALESCE(co2_saved,0) as co2_saved FROM trajets ORDER BY id DESC LIMIT 20")->fetchAll();

    // 2. Energy consumption per trajet (id, description, battery_energy, fuel_saved)
    $trajetEnergy = $pdo->query("SELECT id, description, COALESCE(battery_energy,0) as battery_energy, COALESCE(fuel_saved,0) as fuel_saved FROM trajets ORDER BY id DESC LIMIT 20")->fetchAll();

    // 3. Distance distribution (id, distance)
    $trajetDistances = $pdo->query("SELECT id, COALESCE(distance,0) as distance FROM trajets ORDER BY id DESC")->fetchAll();

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
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Modal overlay styling */
        .modal-stat-overlay {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.45);
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s;
        }
        .modal-stat-overlay.show {
            display: flex;
        }
        .modal-stat-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            padding: 2.5rem 3.5rem; /* Increased padding */
            max-width: 1100px;      /* Increased max-width */
            width: 98vw;
            animation: slideDown 0.5s cubic-bezier(.68,-0.55,.27,1.55);
        }
        .modal-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-stat-header h4 {
            margin: 0;
            color: #198754;
            font-weight: 600;
        }
        .modal-stat-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
        }
        .modal-stat-close:hover {
            color: #dc3545;
        }
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2.5rem; /* Slightly more gap */
            justify-content: center;
        }
        .chart-box {
            flex: 1 1 400px;        /* Allow chart to grow larger */
            min-width: 350px;       /* Increased min-width */
            max-width: 480px;       /* Increased max-width */
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.8rem;        /* Increased padding */
            box-shadow: 0 2px 8px rgba(60,186,151,0.07);
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: popIn 0.7s;
        }
        @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
        @keyframes slideDown { from {transform:translateY(-40px); opacity:0;} to {transform:translateY(0); opacity:1;} }
        @keyframes popIn { from {transform:scale(0.8); opacity:0;} to {transform:scale(1); opacity:1;} }
    </style>
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
                        <div class="d-flex">
                            <a href="stations/list.php" class="btn btn-primary">Gérer les stations</a>
                            <button id="showStatsBtn" class="btn btn-success ms-2" style="box-shadow:0 2px 8px #60BA9733;">
                                <i class="bi bi-bar-chart-fill"></i> Voir statistiques
                            </button>
                        </div>
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
                        <div class="d-flex">
                            <a href="trajets/list.php" class="btn btn-primary">Gérer les trajets</a>
                            <button id="showTrajetStatsBtn" class="btn btn-success ms-2" style="box-shadow:0 2px 8px #60BA9733;">
                                <i class="bi bi-bar-chart-fill"></i> Voir statistiques
                            </button>
                        </div>
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

    <!-- Modal for statistics -->
    <div class="modal-stat-overlay" id="statModal">
        <div class="modal-stat-content">
            <div class="modal-stat-header">
                <h4 id="chartTitle"><i class="bi bi-bar-chart-fill"></i> Statistiques des stations</h4>
                <button class="modal-stat-close" id="closeStatModal" aria-label="Fermer">&times;</button>
            </div>
            <div class="chart-slider-container position-relative" style="min-height:420px;">
                <button id="slideLeft" class="btn btn-outline-secondary position-absolute top-50 start-0 translate-middle-y" style="z-index:10;display:none;">
                    <i class="bi bi-arrow-left-circle" style="font-size:2rem;"></i>
                </button>
                <div class="chart-slider" style="overflow:hidden; width:100%;">
                    <div class="chart-slide chart-box" id="slide-0" style="width:100%; display:flex; flex-direction:column; align-items:center;">
                        <canvas id="pieChart" width="420" height="320" style="max-width:420px; max-height:320px; width:100%; height:320px;"></canvas>
                        <div class="mt-2 text-center fw-bold">Actives vs Inactives</div>
                    </div>
                    <div class="chart-slide chart-box" id="slide-1" style="width:100%; display:none; flex-direction:column; align-items:center;">
                        <canvas id="barChart" width="420" height="320" style="max-width:420px; max-height:320px; width:100%; height:320px;"></canvas>
                        <div class="mt-2 text-center fw-bold">Stations par emplacement</div>
                    </div>
                </div>
                <button id="slideRight" class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y" style="z-index:10;">
                    <i class="bi bi-arrow-right-circle" style="font-size:2rem;"></i>
                </button>
            </div>
            <style>
                .chart-slider-container {
                    position: relative;
                    min-height: 420px;
                }
                .chart-slide {
                    transition: transform 0.5s cubic-bezier(.68,-0.55,.27,1.55), opacity 0.5s;
                    /* Remove individual padding/background, handled by chart-box */
                }
                .chart-box {
                    background: #f8f9fa;
                    border-radius: 10px;
                    padding: 1.8rem;
                    box-shadow: 0 2px 8px rgba(60,186,151,0.07);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    width: 100%;
                    max-width: 480px;
                    min-width: 350px;
                    margin: 0 auto;
                }
                .chart-slide.slide-in-left {
                    transform: translateX(-100%);
                    opacity: 0;
                }
                .chart-slide.slide-in-right {
                    transform: translateX(100%);
                    opacity: 0;
                }
                .chart-slide.slide-active {
                    transform: translateX(0);
                    opacity: 1;
                }
            </style>
        </div>
    </div>
    <!-- Modal for trajets statistics -->
    <div class="modal-stat-overlay" id="trajetStatModal">
        <div class="modal-stat-content">
            <div class="modal-stat-header">
                <h4 id="trajetChartTitle"><i class="bi bi-bar-chart-fill"></i> Statistiques des trajets</h4>
                <button class="modal-stat-close" id="closeTrajetStatModal" aria-label="Fermer">&times;</button>
            </div>
            <div class="chart-slider-container position-relative" style="min-height:420px;">
                <button id="trajetSlideLeft" class="btn btn-outline-secondary position-absolute top-50 start-0 translate-middle-y" style="z-index:10;display:none;">
                    <i class="bi bi-arrow-left-circle" style="font-size:2rem;"></i>
                </button>
                <div class="chart-slider" style="overflow:hidden; width:100%;">
                    <div class="chart-slide chart-box" id="trajet-slide-0" style="width:100%; display:flex; flex-direction:column; align-items:center;">
                        <canvas id="trajetCo2Chart" width="420" height="320"></canvas>
                        <div class="mt-2 text-center fw-bold">CO₂ économisé par trajet</div>
                    </div>
                    <div class="chart-slide chart-box" id="trajet-slide-1" style="width:100%; display:none; flex-direction:column; align-items:center;">
                        <canvas id="trajetEnergyChart" width="420" height="320"></canvas>
                        <div class="mt-2 text-center fw-bold">Consommation d’énergie par trajet</div>
                    </div>
                    <div class="chart-slide chart-box" id="trajet-slide-2" style="width:100%; display:none; flex-direction:column; align-items:center;">
                        <canvas id="trajetDistanceChart" width="420" height="320"></canvas>
                        <div class="mt-2 text-center fw-bold">Répartition des distances</div>
                    </div>
                </div>
                <button id="trajetSlideRight" class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y" style="z-index:10;">
                    <i class="bi bi-arrow-right-circle" style="font-size:2rem;"></i>
                </button>
            </div>
        </div>
    </div>
    <script>
        window.statusStats = <?php echo json_encode($statusStats); ?>;
        window.locationStats = <?php echo json_encode($locationStats); ?>;
        window.trajetCo2 = <?php echo json_encode($trajetCo2); ?>;
        window.trajetEnergy = <?php echo json_encode($trajetEnergy); ?>;
        window.trajetDistances = <?php echo json_encode($trajetDistances); ?>;
    </script>
    <script src="assets/js/script_index.js"></script>
    


    
</body>
</html>
<?php
/**
 * Gets city name from coordinates using OpenStreetMap Nominatim API
 * @param string $coords Coordinates in "lat,lng" format
 * @return string City name or 'Inconnu' if not found
 */
function getCityNameFromCoordsOSM($coords) {
    list($lat, $lng) = explode(',', $coords);
    $lat = trim($lat);
    $lng = trim($lng);

    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=10&addressdetails=1&accept-language=fr";
    $opts = [
        "http" => [
            "header" => "User-Agent: GreenAdmin/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);

    $json = @file_get_contents($url, false, $context);
    if ($json === false) {
        return 'Inconnu';
    }
    $data = json_decode($json, true);
    // Return the governorate (state) if available
    if (isset($data['address']['state'])) {
        return $data['address']['state'];
    }
    // Fallbacks if state is not available
    if (isset($data['address']['county'])) {
        return $data['address']['county'];
    }
    return 'Inconnu';
}
?>
