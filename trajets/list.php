<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';

// Debug check - remove this after fixing


requireLogin();
$error = '';
$success = '';

try {
    // Initialize database connection
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new PDOException("Failed to connect to database");
    }
    
    // Debug: Vérifier les stations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stations");
    $stationsCount = $stmt->fetch()['count'];
    error_log("Nombre de stations dans la base: " . $stationsCount);

    // Récupérer tous les trajets
    $stmt = $pdo->query("SELECT t.id, t.route_coordinates FROM trajets t WHERE t.route_coordinates IS NOT NULL LIMIT 1");
    $sampleTrajet = $stmt->fetch();
    //error_log("Exemple de coordonnées de trajet: " . print_r($sampleTrajet['route_coordinates'], true));

    // Récupérer tous les trajets (version originale)
    $stmt = $pdo->query("SELECT t.id, t.distance, t.route_description AS description, 
                         t.start_point_name, t.end_point_name, t.route_coordinates,
                         t.co2_saved, t.battery_energy, t.fuel_saved
                         FROM trajets t ORDER BY t.id DESC");
    $trajets = $stmt->fetchAll();

    // Get all stations with coordinates
    $stmt = $pdo->query("SELECT id, name, 
                     CAST(TRIM(SUBSTRING_INDEX(location, ',', 1)) AS DECIMAL(10,6)) as latitude,
                     CAST(TRIM(SUBSTRING_INDEX(location, ',', -1)) AS DECIMAL(10,6)) as longitude 
                     FROM stations");
    $stations = $stmt->fetchAll();

    // Calculate nearest station for each route with Haversine formula
    foreach ($trajets as &$trajet) {
        $nearestStation = null;
        $minDistance = PHP_FLOAT_MAX;
        
        if (!empty($trajet['route_coordinates'])) {
            $routeCoords = json_decode($trajet['route_coordinates'], true);
            $startPoint = $routeCoords[0];
            
            foreach ($stations as $station) {
                // Haversine formula for accurate distance
                $lat1 = deg2rad($startPoint['lat']);
                $lon1 = deg2rad($startPoint['lng']);
                $lat2 = deg2rad($station['latitude']);
                $lon2 = deg2rad($station['longitude']);
                
                $dlat = $lat2 - $lat1;
                $dlon = $lon2 - $lon1;
                
                $a = sin($dlat/2) * sin($dlat/2) + 
                     cos($lat1) * cos($lat2) * 
                     sin($dlon/2) * sin($dlon/2);
                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                $distance = 6371 * $c; // Earth radius in km
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestStation = $station['name'] . ' (' . round($distance, 2) . ' km)';
                }
            }
        }
        
        $trajet['nearest_station'] = $nearestStation ?? 'N/A';
    }
    unset($trajet);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Erreur lors de la récupération des trajets.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Trajets - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
<?php 
    $basePath = '../';
    $currentPage = 'trajets';
    include '../includes/sidbar.php';
?>
<div id="main" class="main-content">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Liste des Trajets</h1>
            <a href="add.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nouveau Trajet
            </a>
        </div>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Description</th>
                                <th>Départ</th>
                                <th>Arrivée</th>
                                <th>Distance</th>
                                <th>CO₂ (g)</th>
                                <th>Batterie (Wh)</th>
                                <th>Carburant (L)</th>
                                <th>Station la plus proche</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($trajets)): ?>
                                <?php foreach ($trajets as $trajet): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trajet['id']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['description']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['start_point_name']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['end_point_name']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['distance']); ?> km</td>
                                        <td><?php echo htmlspecialchars($trajet['co2_saved']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['battery_energy']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['fuel_saved']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['nearest_station']); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $trajet['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $trajet['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce trajet ?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucun trajet trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</div>
</body>
</html>
