<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
requireLogin();
$error = '';
$success = '';

try {
    $pdo = getDBConnection();
    
    // Fetch city as well
    $stmt = $pdo->query("SELECT id, name, city, location, status FROM stations ");
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
        </div>
        <div class="d-flex flex-column align-items-end mb-3">
            <a href="add.php" class="btn btn-success mb-2">
                <i class="bi bi-plus-circle"></i> Nouvelle Station
            </a>
            <button id="showStationsTableModalBtn" class="btn btn-success">
                <i class="bi bi-table"></i> Afficher le tableau complet
            </button>
        </div>
        <!-- Search bar for main table (moved below buttons) -->
        <div class="mb-3">
            <input type="text" id="mainStationSearchInput" class="form-control" placeholder="Rechercher par nom ou gouvernorat...">
            <div id="mainStationSearchMessage" class="mt-2" style="display:none;"></div>
        </div>
        <!-- Modal for full stations table -->
        <div class="modal fade" id="stationsTableModal" tabindex="-1" aria-labelledby="stationsTableModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <!-- Sort Dropdown Button (now on the left) -->
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="sortStationsBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-funnel"></i> Trier
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="sortStationsBtn">
                                        <li><a class="dropdown-item" href="#" id="sortByName">Nom (A-Z)</a></li>
                                        <li><a class="dropdown-item" href="#" id="sortByLocation">Localisation (A-Z)</a></li>
                                        <li><a class="dropdown-item" href="#" id="sortByStatus">Statut (A-Z)</a></li>
                                    </ul>
                                </div>
                                <button id="exportPdfBtn" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
                                </button>
                            </div>
                            <h5 class="modal-title mb-0" id="stationsTableModalLabel">
                                <i class="bi bi-table"></i> Tableau complet des stations
                            </h5>
                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                    </div>
                    <div class="modal-body" style="max-height:70vh; overflow:auto;">
                        <div class="mb-3">
                            <input type="text" id="stationSearchInput" class="form-control" placeholder="Rechercher par nom ou gouvernorat...">
                            <div id="stationSearchMessage" class="mt-2" style="display:none;"></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0" id="stationsFullTable">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">
                                            <input type="checkbox" id="checkAllRows" />
                                        </th>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Localisation</th>
                                        <th>Statut</th>
                                        <th style="display:none;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($stations)): ?>
                                        <?php foreach ($stations as $station): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="row-checkbox" />
                                                </td>
                                                <td><?php echo htmlspecialchars($station['id']); ?></td>
                                                <td><?php echo htmlspecialchars($station['name']); ?></td>
                                                <!-- Show city instead of coordinates -->
                                                <td data-city="<?php echo htmlspecialchars($station['city']); ?>">
                                                    <?php echo htmlspecialchars($station['city']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($station['status']); ?></td>
                                                <td style="display:none;">
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
                                            <td colspan="6" class="text-center">Aucune station trouvée</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- jsPDF & autotable CDN -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
        <script src="../assets/js/scripts_station_list.js"></script>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="width:100%;">
                        <thead style="display: table; width: 100%; table-layout: fixed;">
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Localisation</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="stationsTableBody" style="display: block; max-height: 320px; overflow-y: auto; width: 100%;">
                            <?php if (!empty($stations)): ?>
                                <?php foreach ($stations as $station): ?>
                                    <tr style="display: table; width: 100%; table-layout: fixed;">
                                        <td><?php echo htmlspecialchars($station['id']); ?></td>
                                        <td><?php echo htmlspecialchars($station['name']); ?></td>
                                        <!-- Show city instead of coordinates -->
                                        <td><?php echo htmlspecialchars($station['city']); ?></td>
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
                                <tr style="display: table; width: 100%;">
                                    <td colspan="5" class="text-center">Aucune station trouvée</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <style>
                    #stationsTableBody {
                        max-height: 320px;
                        overflow-y: auto;
                        scroll-behavior: smooth;
                    }
                    #stationsTableBody::-webkit-scrollbar {
                        width: 8px;
                        background: #f1f1f1;
                    }
                    #stationsTableBody::-webkit-scrollbar-thumb {
                        background: #60BA97;
                        border-radius: 4px;
                    }
                </style>
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

        // Define custom icons
        const blueIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        const greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        const redIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        // Add markers for each station with color based on status
        let bounds = [];
        stations.forEach(station => {
            if (station.location && station.location.includes(',')) {
                const [lat, lng] = station.location.split(',').map(coord => parseFloat(coord.trim()));
                if (!isNaN(lat) && !isNaN(lng)) {
                    let icon = blueIcon;
                    if (station.status && station.status.toLowerCase() === 'active') {
                        icon = greenIcon;
                    } else if (station.status && station.status.toLowerCase() === 'inactive') {
                        icon = redIcon;
                    }
                    const marker = L.marker([lat, lng], { icon: icon }).addTo(map)
                        .bindPopup(`<b>${station.name}</b><br>${station.location}<br>Statut: ${station.status}`);
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