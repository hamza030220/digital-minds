<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
requireLogin();

// Set secure headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Initialize variables
$error = '';
$success = '';
$description = '';
$route_description = '';
$start_point_name = '';
$end_point_name = '';
$start_point = '';
$end_point = '';
$distance = '';
$route_coordinates = '';

// Check and update database structure if needed
function ensureTableStructure($pdo) {
    $columnCheckStmt = $pdo->query("SHOW COLUMNS FROM trajets LIKE 'start_point'");
    if ($columnCheckStmt->rowCount() == 0) {
        // Update table structure to add new columns
        $pdo->exec("
            ALTER TABLE trajets 
            ADD COLUMN start_point VARCHAR(255) NULL,
            ADD COLUMN end_point VARCHAR(255) NULL,
            ADD COLUMN start_point_name VARCHAR(255) NULL,
            ADD COLUMN end_point_name VARCHAR(255) NULL
        ");
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize input
        $description = trim($_POST['description']);
        $route_description = trim($_POST['route_description']);
        $start_point_name = isset($_POST['start_point_name']) ? trim($_POST['start_point_name']) : '';
        $end_point_name = isset($_POST['end_point_name']) ? trim($_POST['end_point_name']) : '';
        $start_point = isset($_POST['start_point']) ? trim($_POST['start_point']) : '';
        $end_point = isset($_POST['end_point']) ? trim($_POST['end_point']) : '';
        $distance = isset($_POST['distance']) ? trim($_POST['distance']) : '';
        $route_coordinates = isset($_POST['route_coordinates']) ? trim($_POST['route_coordinates']) : '';

        // Validate input
        if (empty($description)) {
            $error = "Le titre du trajet est requis.";
        } elseif (empty($route_description)) {
            $error = "La description du trajet est requise.";
        } elseif (empty($start_point_name)) {
            $error = "Le nom du point de départ est requis.";
        } elseif (empty($end_point_name)) {
            $error = "Le nom du point d'arrivée est requis.";
        } elseif (empty($start_point)) {
            $error = "Veuillez placer le point de départ sur la carte.";
        } elseif (empty($end_point)) {
            $error = "Veuillez placer le point d'arrivée sur la carte.";
        } elseif (!is_numeric($distance) || $distance <= 0) {
            $error = "La distance doit être un nombre positif.";
        } elseif (empty($route_coordinates)) {
            $error = "Veuillez tracer l'itinéraire sur la carte.";
        } else {
            $pdo = getDBConnection();
            ensureTableStructure($pdo);
            
            // Insert new trajet
            $stmt = $pdo->prepare("
                INSERT INTO trajets 
                (distance, description, route_coordinates, route_description, 
                 start_point, end_point, start_point_name, end_point_name) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $distance, 
                $description, 
                $route_coordinates, 
                $route_description,
                $start_point,
                $end_point,
                $start_point_name,
                $end_point_name
            ]);
            $success = "Trajet ajouté avec succès.";
            
            // Reset form
            $description = '';
            $route_description = '';
            $start_point_name = '';
            $end_point_name = '';
            $start_point = '';
            $end_point = '';
            $route_coordinates = '';
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "Une erreur est survenue lors de l'ajout du trajet: " . $e->getMessage();
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Trajet - Green Admin</title>
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
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h2 class="h5 mb-0">Ajouter un Trajet</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="trajetForm">
                            <input type="hidden" name="route_coordinates" id="route_coordinates" value="<?php echo htmlspecialchars($route_coordinates); ?>">
                            <input type="hidden" name="start_point" id="start_point" value="<?php echo htmlspecialchars($start_point); ?>">
                            <input type="hidden" name="end_point" id="end_point" value="<?php echo htmlspecialchars($end_point); ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="description" class="form-label">Titre du trajet</label>
                                    <input type="text" class="form-control" id="description" name="description" 
                                           value="<?php echo htmlspecialchars($description); ?>" required>
                                    <div class="form-text">Exemple: "Trajet Plage - Centre-ville"</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="distance" class="form-label">Distance (km)</label>
                                    <input type="number" step="0.01" min="0.1" class="form-control" id="distance" name="distance" 
                                           value="<?php echo htmlspecialchars($distance); ?>" required>
                                </div>
                            </div>
                            
                    <div class="row mb-3">
                        <div class="col-md-6">
                                <label for="start_point_name" class="form-label">Nom du point de départ</label>
                                <input type="text" class="form-control" id="start_point_name" name="start_point_name" 
                                    value="<?php echo htmlspecialchars($start_point_name); ?>" required>
                                <div class="form-text">Exemple: "Plage El Kantaoui"</div>
                                <div class="marker-info" id="start_point_info">
                                     <?php if ($start_point): ?>
                                         Point placé: <?php echo htmlspecialchars($start_point); ?>
                                     <?php else: ?>
                                         Aucun point de départ placé sur la carte
                                     <?php endif; ?>
                                 </div>
                        </div>
                         <div class="col-md-6">
                             <label for="end_point_name" class="form-label">Nom du point d'arrivée</label>
                             <input type="text" class="form-control" id="end_point_name" name="end_point_name" 
                                   value="<?php echo htmlspecialchars($end_point_name); ?>" required>
                             <div class="form-text">Exemple: "Centre-ville de Sousse"</div>
                             <div class="marker-info" id="end_point_info">
                                 <?php if ($end_point): ?>
                                   Point placé: <?php echo htmlspecialchars($end_point); ?>
                                 <?php else: ?>
                                      Aucun point d'arrivée placé sur la carte
                                 <?php endif; ?>
                             </div>
                        </div>
                    </div>
                            
                            <div class="mb-3">
                                <label for="route_description" class="form-label">Description détaillée du trajet</label>
                                <textarea class="form-control" id="route_description" name="route_description" rows="4" required><?php echo htmlspecialchars($route_description); ?></textarea>
                                <div class="form-text">Détaillez le parcours, les points d'intérêt, difficultés, etc.</div>
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
                                <button type="submit" class="btn btn-success">Enregistrer le trajet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize the map
        const map = L.map('map').setView([36.8065, 10.1815], 13); // Default to Tunisia center
        
        // Add the OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        // Reverse Geocoding Function
        
        async function getReverseGeocode(latlng, fieldName) {
          try {
            const response = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`);
            const result = response.data;
            
            // Extract display name
            const displayName = result.display_name;
            
            // Update the form field
            document.getElementById(fieldName).value = displayName;
          } catch (error) {
            console.error('Error fetching reverse geocode data:', error);
           }
        }
        
        // Variables to store map data
        let drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);
        let startMarker = null;
        let endMarker = null;
        let routeLine = null;
        
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
        
        // Icons for start and end markers
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
        
        // Variables to track current map mode
        let currentMode = null; // 'start', 'end', or 'draw'
        
        // Function to add or update a marker
        function placeMarker(latlng, markerType) {
    // Remove existing marker if it exists
       if (markerType === 'start' && startMarker) {
        map.removeLayer(startMarker);
     } else if (markerType === 'end' && endMarker) {
        map.removeLayer(endMarker);
     }
    
     // Get the point name
     const pointName = markerType === 'start' 
        ? document.getElementById('start_point_name').value || 'Point de départ'
        : document.getElementById('end_point_name').value || 'Point d\'arrivée';
    
    // Create a new marker
     const marker = L.marker(latlng, {
        icon: markerType === 'start' ? startIcon : endIcon,
        draggable: true // Allow marker to be dragged
     }).addTo(map);
    
    // Set popup content
     marker.bindPopup(`<b>${markerType === 'start' ? 'Départ' : 'Arrivée'}:</b> ${pointName}`);
    
    // Store the marker
     if (markerType === 'start') {
        startMarker = marker;
        document.getElementById('start_point').value = `${latlng.lat},${latlng.lng}`;
        document.getElementById('start_point_info').textContent = `Point placé: ${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
        getReverseGeocode(latlng, 'start_point_name'); // Fetch and set start point name
     } else {
        endMarker = marker;
        document.getElementById('end_point').value = `${latlng.lat},${latlng.lng}`;
        document.getElementById('end_point_info').textContent = `Point placé: ${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
        getReverseGeocode(latlng, 'end_point_name'); // Fetch and set end point name
     }
    
     // Add dragend event to update coordinates when marker is moved
     marker.on('dragend', function(event) {
        const marker = event.target;
        const position = marker.getLatLng();
        
        if (markerType === 'start') {
            document.getElementById('start_point').value = `${position.lat},${position.lng}`;
            document.getElementById('start_point_info').textContent = `Point placé: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`;
            getReverseGeocode(position, 'start_point_name'); // Update start point name
        } else {
            document.getElementById('end_point').value = `${position.lat},${position.lng}`;
            document.getElementById('end_point_info').textContent = `Point placé: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`;
            getReverseGeocode(position, 'end_point_name'); // Update end point name
        }
        
        // Update popup
        const pointName = markerType === 'start' 
            ? document.getElementById('start_point_name').value || 'Point de départ'
            : document.getElementById('end_point_name').value || 'Point d\'arrivée';
        marker.setPopupContent(`<b>${markerType === 'start' ? 'Départ' : 'Arrivée'}:</b> ${pointName}`);
     });
    
    // If both markers are set, fit map to show both
     if (startMarker && endMarker) {
        const bounds = L.latLngBounds([startMarker.getLatLng(), endMarker.getLatLng()]);
        map.fitBounds(bounds, { padding: [50, 50] });
     }
    
      return marker;
     }
        
        // Function to set the active mode
        function setMode(mode) {
            currentMode = mode;
            
            // Update button states
            document.getElementById('start_point_btn').classList.remove('active');
            document.getElementById('end_point_btn').classList.remove('active');
            document.getElementById('draw_route_btn').classList.remove('active');
            
            if (mode === 'start') {
                document.getElementById('start_point_btn').classList.add('active');
                map.getContainer().style.cursor = 'crosshair';
            } else if (mode === 'end') {
                document.getElementById('end_point_btn').classList.add('active');
                map.getContainer().style.cursor = 'crosshair';
            } else if (mode === 'draw') {
                document.getElementById('draw_route_btn').classList.add('active');
                map.getContainer().style.cursor = '';
            }
        }
        
        // Map click event handler
        map.on('click', function(e) {
            if (currentMode === 'start') {
                placeMarker(e.latlng, 'start');
            } else if (currentMode === 'end') {
                placeMarker(e.latlng, 'end');
            }
            // Do nothing if in draw mode (handled by Leaflet.Draw)
        });
        
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
        
        // Map control button event listeners
        document.getElementById('start_point_btn').addEventListener('click', function() {
            setMode('start');
        });
        
        document.getElementById('end_point_btn').addEventListener('click', function() {
            setMode('end');
        });
        
        document.getElementById('draw_route_btn').addEventListener('click', function() {
            setMode('draw');
            // Enable the drawing tools
            new L.Draw.Polyline(map, drawControl.options.polyline).enable();
        });
        
        // Update marker popups when point names change
        document.getElementById('start_point_name').addEventListener('input', function() {
            if (startMarker) {
                const pointName = this.value || 'Point de départ';
                startMarker.setPopupContent(`<b>Départ:</b> ${pointName}`);
            }
        });
        
        document.getElementById('end_point_name').addEventListener('input', function() {
            if (endMarker) {
                const pointName = this.value || 'Point d\'arrivée';
                endMarker.setPopupContent(`<b>Arrivée:</b> ${pointName}`);
            }
        });
        
        // Form validation
        document.getElementById('trajetForm').addEventListener('submit', function(e) {
            // Check if points have been placed
            if (document.getElementById('start_point').value === '') {
                e.preventDefault();
                alert('Veuillez placer le point de départ sur la carte.');
                return false;
            }
            
            if (document.getElementById('end_point').value === '') {
                e.preventDefault();
                alert('Veuillez placer le point d\'arrivée sur la carte.');
                return false;
            }
            
            // Check if route has been drawn
            if (document.getElementById('route_coordinates').value === '') {
                e.preventDefault();
                alert('Veuillez tracer l\'itinéraire sur la carte avant de sauvegarder.');
                return false;
            }
            
            // Validate distance
            const distance = parseFloat(document.getElementById('distance').value);
            if (isNaN(distance) || distance <= 0) {
                e.preventDefault();
                alert('Veuillez saisir une distance valide (supérieure à 0).');
                return false;
            }
            
            return true;
        });
        
        // Initialize with existing markers if coordinates are present
        const savedStartPoint = document.getElementById('start_point').value;
        if (savedStartPoint) {
            try {
                const [lat, lng] = savedStartPoint.split(',').map(coord => parseFloat(coord.trim()));
                if (!isNaN(lat) && !isNaN(lng)) {
                    placeMarker(L.latLng(lat, lng), 'start');
                }
            } catch (e) {
                console.error('Error parsing start point coordinates:', e);
            }
        }
        
        const savedEndPoint = document.getElementById('end_point').value;
        if (savedEndPoint) {
            try {
                const [lat, lng] = savedEndPoint.split(',').map(coord => parseFloat(coord.trim()));
                if (!isNaN(lat) && !isNaN(lng)) {
                    placeMarker(L.latLng(lat, lng), 'end');
                }
            } catch (e) {
                console.error('Error parsing end point coordinates:', e);
            }
        }
        
        // Initialize with existing route if coordinates are present
        const savedCoordinates = document.getElementById('route_coordinates').value;
        if (savedCoordinates) {
            try {
                const coordinates = JSON.parse(savedCoordinates);
                if (coordinates.length > 1) {
                    // Create polyline from saved coordinates
                    const points = coordinates.map(coord => [coord.lat, coord.lng]);
                    const polyline = L.polyline(points, {
                        color: '#60BA97',
                        weight: 5
                    });
                    
                    // Add to map and drawn items
                    drawnItems.addLayer(polyline);
                    
                    // Fit map to show the route
                    map.fitBounds(polyline.getBounds(), { padding: [50, 50] });
                }
            } catch (e) {
                console.error('Error parsing saved coordinates:', e);
            }
        }
        
        // Set the draw mode as default
        setMode('draw');
    </script>
</body>
</html>
