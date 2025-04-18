<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
requireLogin();
$error = '';
$success = '';

try {
    $pdo = getDBConnection();
    
    // Simple query to get all stations
    $stmt = $pdo->query("SELECT id, name, location, status FROM stations ORDER BY id DESC");
    $stations = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Erreur lors de la récupération des stations.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Stations - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
<?php 
    $basePath = '../';
    $currentPage = 'stations';
    include '../includes/sidbar.php';
?>
<div id="main" class="main-content">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Liste des Stations</h1>
            <a href="add.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nouvelle Station
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
                                <th>Nom</th>
                                <th>Localisation</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stations)): ?>
                                <?php foreach ($stations as $station): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($station['id']); ?></td>
                                        <td><?php echo htmlspecialchars($station['name']); ?></td>
                                        <td><?php echo htmlspecialchars($station['location']); ?></td>
                                        <td><?php echo htmlspecialchars($station['status']); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $station['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $station['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette station ?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Aucune station trouvée</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-map"></i> Carte des Stations
            </div>
            <div class="card-body" style="height: 400px;">
                <div id="stationsMap" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Prepare stations data for JS
        const stations = <?php echo json_encode($stations); ?>;
        // Initialize map
        const map = L.map('stationsMap').setView([36.8, 10.18], 8); // Centered on Tunisia

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add markers for each station
        let bounds = [];
        stations.forEach(station => {
            if (station.location && station.location.includes(',')) {
                const [lat, lng] = station.location.split(',').map(coord => parseFloat(coord.trim()));
                if (!isNaN(lat) && !isNaN(lng)) {
                    const marker = L.marker([lat, lng]).addTo(map)
                        .bindPopup(`<b>${station.name}</b><br>${station.location}`);
                    bounds.push([lat, lng]);
                }
            }
        });
        if (bounds.length > 0) {
            map.fitBounds(bounds, {padding: [30, 30]});
        }
    </script>
</div>
</body>
</html>