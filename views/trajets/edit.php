<?php
require_once '../includes/config.php';

// Require login
requireLogin();

// Initialize variables
$error = '';
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = getDBConnection();
    
    // Get all active stations for select lists
    $stationsStmt = $pdo->query("SELECT id, name FROM stations WHERE status = 'active' ORDER BY name");
    $stations = $stationsStmt->fetchAll();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get existing trajet data
        $stmt = $pdo->prepare("SELECT * FROM trajets WHERE id = ?");
        $stmt->execute([$id]);
        $trajet = $stmt->fetch();
        
        if (!$trajet) {
            header("Location: list.php");
            exit();
        }
        
        $start_station_id = $trajet['start_station_id'];
        $end_station_id = $trajet['end_station_id'];
        $distance = $trajet['distance'];
        $description = $trajet['description'];
        $route_coordinates = json_decode($trajet['route_coordinates'], true);
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed');
        }
        
        $id = (int)$_POST['id'];
        $start_station_id = (int)$_POST['start_station_id'];
        $end_station_id = (int)$_POST['end_station_id'];
        $distance = trim($_POST['distance']);
        $description = trim($_POST['description']);
        $route_coordinates = isset($_POST['route_coordinates']) ? trim($_POST['route_coordinates']) : '';

        // Validate input
        if (empty($start_station_id)) {
            $error = "La station de départ est requise.";
        } elseif (empty($end_station_id)) {
            $error = "La station d'arrivée est requise.";
        } elseif ($start_station_id === $end_station_id) {
            $error = "Les stations de départ et d'arrivée doivent être différentes.";
        } elseif (!is_numeric($distance) || $distance <= 0) {
            $error = "La distance doit être un nombre positif.";
        } elseif (empty($description)) {
            $error = "La description est requise.";
        } elseif (empty($route_coordinates)) {
            $error = "Veuillez tracer l'itinéraire sur la carte.";
        } else {
            // Check if stations exist and are active
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM stations WHERE id IN (?, ?) AND status = 'active'");
            $stmt->execute([$start_station_id, $end_station_id]);
            if ($stmt->fetchColumn() !== '2') {
                $error = "Une ou plusieurs stations sélectionnées sont invalides.";
            } else {
                // Check if similar trajet exists (excluding current trajet)
                $stmt = $pdo->prepare("SELECT id FROM trajets WHERE start_station_id = ? AND end_station_id = ? AND id != ?");
                $stmt->execute([$start_station_id, $end_station_id, $id]);
                if ($stmt->fetch()) {
                    $error = "Un trajet existe déjà entre ces stations.";
                } else {
                    // Update trajet
                    $stmt = $pdo->prepare("UPDATE trajets SET start_station_id = ?, end_station_id = ?, distance = ?, description = ?, route_coordinates = ? WHERE id = ?");
                    $stmt->execute([$start_station_id, $end_station_id, $distance, $description, $route_coordinates, $id]);
                    
                    $success = "Trajet mis à jour avec succès.";
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Une erreur est survenue lors de la modification du trajet.";
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Trajet - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    

    <!-- Leaflet CSS and JS for OpenStreetMap -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Leaflet Draw plugin for drawing routes -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

<!-- Axios for making HTTP requests -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<style>
    #map {
        height: 500px;
        width: 100%;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    .map-instructions {
        margin-bottom: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
        border-left: 4px solid #60BA97;
    }
    .coords-preview {
        font-family: monospace;
        font-size: 0.8em;
        color: #6c757d;
        max-height: 100px;
        overflow-y: auto;
    }
    .map-control-buttons {
        margin-bottom: 15px;
        display: flex;
        gap: 10px;
    }
    .map-control-buttons button {
        padding: 8px 15px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
        cursor: pointer;
        transition: all 0.2s;
    }
    .map-control-buttons button.active {
        background-color: #60BA97;
        color: white;
        border-color: #60BA97;
    }
    .marker-info {
        margin-top: 5px;
        font-size: 0.9em;
        color: #6c757d;
    }
</style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #60BA97;">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php"><img src="../public/image/logobackend.png" alt="Green Admin" style="height: 30px;"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../stations/list.php">Stations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="list.php">Trajets</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-outline-light">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h2 class="h5 mb-0">Modifier le Trajet</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <?php if (count($stations) < 2): ?>
                            <div class="alert alert-warning">
                                Il n'y a pas assez de stations actives pour modifier ce trajet.
                                <a href="../stations/list.php" class="alert-link">Gérer les stations</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="trajetForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                <input type="hidden" name="route_coordinates" id="route_coordinates" value="<?php echo htmlspecialchars(json_encode($route_coordinates)); ?>">

                                <div class="mb-3">
                                    <label for="start_station_id" class="form-label">Station de départ</label>
                                    <select class="form-select" id="start_station_id" name="start_station_id" required>
                                        <option value="">Sélectionnez une station</option>
                                        <?php foreach ($stations as $station): ?>
                                            <option value="<?php echo $station['id']; ?>" 
                                                <?php echo $start_station_id == $station['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($station['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="end_station_id" class="form-label">Station d'arrivée</label>
                                    <select class="form-select" id="end_station_id" name="end_station_id" required>
                                        <option value="">Sélectionnez une station</option>
                                        <?php foreach ($stations as $station): ?>
                                            <option value="<?php echo $station['id']; ?>" 
                                                <?php echo $end_station_id == $station['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($station['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="distance" class="form-label">Distance (en km)</label>
                                    <input type="number" step="0.1" min="0.1" class="form-control" id="distance" 
                                           name="distance" value="<?php echo htmlspecialchars($distance); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
                                </div>

                                <div class="mb-1">
                                    <label class="form-label">Tracer l'itinéraire sur la carte</label>
                                </div>
                                <div class="map-instructions">
                                    <p class="mb-0"><i class="bi bi-info-circle"></i> <strong>Instructions:</strong></p>
                                    <ol class="mb-0 mt-2">
                                        <li>Placez d'abord le point de départ et d'arrivée en utilisant les boutons ci-dessous.</li>
                                        <li>Ensuite, utilisez l'outil de dessin pour tracer l'itinéraire entre ces points.</li>
                                        <li>Double-cliquez pour terminer le traçage d'une ligne.</li>
                                    </ol>
                                </div>
                                
                                <div class="map-control-buttons">
                                    <button type="button" id="start_point_btn" class="btn">
                                        <i class="bi bi-geo-alt-fill text-success"></i> Placer le point de départ
                                    </button>
                                    <button type="button" id="end_point_btn" class="btn">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> Placer le point d'arrivée
                                    </button>
                                    <button type="button" id="draw_route_btn" class="btn">
                                        <i class="bi bi-pencil"></i> Tracer l'itinéraire
                                    </button>
                                </div>
                                
                                <div id="map"></div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="list.php" class="btn btn-secondary">Retour à la liste</a>
                                    <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize drawn items layer group
    let drawnItems = new L.FeatureGroup();

    // Initialize the map
    const map = L.map('map').setView([36.8065, 10.1815], 13); // Default to Tunisia center

    // Add the OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Add drawn items to the map
    map.addLayer(drawnItems);

    // Load existing route data
    const routeCoordinates = <?php echo json_encode($route_coordinates); ?>;
    if (routeCoordinates.length > 0) {
        const polyline = L.polyline(routeCoordinates, {
            color: '#60BA97',
            weight: 5
        }).addTo(drawnItems);

        map.fitBounds(polyline.getBounds(), { padding: [50, 50] });
    }

    // Initialize the draw control for polylines only
    const drawControl = new L.Control.Draw({
        draw: {
            polyline: {
                shapeOptions: {
                    color: '#60BA97',
                    weight: 5
                }
            },
            polygon: false,
            circle: false,
            rectangle: false,
            marker: false,
            circlemarker: false
        },
        edit: {
            featureGroup: drawnItems,
            remove: true
        }
    });
    map.addControl(drawControl);

    // Event handlers for draw events
    map.on(L.Draw.Event.CREATED, function(event) {
        // Clear existing routes
        drawnItems.clearLayers();

        // Add the new layer
        const layer = event.layer;
        drawnItems.addLayer(layer);

        // Update route coordinates
        updateRouteCoordinates();
    });

    map.on(L.Draw.Event.EDITED, function() {
        updateRouteCoordinates();
    });

    map.on(L.Draw.Event.DELETED, function() {
        updateRouteCoordinates();
    });

    // Function to update the route coordinates hidden input
    function updateRouteCoordinates() {
        let coordinates = [];

        // Extract coordinates from the drawn items
        drawnItems.eachLayer(function(layer) {
            if (layer instanceof L.Polyline) {
                const latLngs = layer.getLatLngs();
                for (let i = 0; i < latLngs.length; i++) {
                    coordinates.push({
                        lat: latLngs[i].lat,
                        lng: latLngs[i].lng
                    });
                }
            }
        });

        // Update the hidden input with JSON string of coordinates
        if (coordinates.length > 0) {
            document.getElementById('route_coordinates').value = JSON.stringify(coordinates);

            // Calculate and update distance if not manually set
            if (document.getElementById('distance').value === '') {
                calculateDistance(coordinates);
            }
        } else {
            document.getElementById('route_coordinates').value = '';
        }
    }

    // Function to calculate distance between points in kilometers
    function calculateDistance(coordinates) {
        if (coordinates.length < 2) return;

        let totalDistance = 0;
        for (let i = 1; i < coordinates.length; i++) {
            const point1 = L.latLng(coordinates[i-1].lat, coordinates[i-1].lng);
            const point2 = L.latLng(coordinates[i].lat, coordinates[i].lng);
            totalDistance += point1.distanceTo(point2);
        }

        // Convert meters to kilometers and round to 2 decimal places
        const distanceKm = (totalDistance / 1000).toFixed(2);
        document.getElementById('distance').value = distanceKm;
    }
    // Variables to store start and end points
let startPoint = null;
let endPoint = null;

function placePoint(pointType) {
    map.on('click', function(event) {
        const latLng = event.latlng;
        if (pointType === 'start') {
            startPoint = latLng;
            document.getElementById('start_station_id').value = `${latLng.lat},${latLng.lng}`;
            // Add a marker for the start point
            L.marker(latLng, { icon: startIcon }).addTo(map).bindPopup('Point de départ');
        } else if (pointType === 'end') {
            endPoint = latLng;
            document.getElementById('end_station_id').value = `${latLng.lat},${latLng.lng}`;
            // Add a marker for the end point
            L.marker(latLng, { icon: endIcon }).addTo(map).bindPopup('Point d\'arrivée');
        }
        map.off('click'); // Remove the click event listener after placing the point
    });
}
// Define icons for start and end points
const startIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

const endIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});
</script>

  
</body>
</html>

