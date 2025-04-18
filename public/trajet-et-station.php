<?php
require_once '../includes/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = getDBConnection();
    
    // Vérifier si la table trajets existe
    $tables = $pdo->query("SHOW TABLES LIKE 'trajets'")->fetchAll();
    if (empty($tables)) {
        throw new PDOException("La table 'trajets' n'existe pas dans la base de données.");
    }
    
    // Fetch active stations
    $stmt = $pdo->query("SELECT * FROM stations WHERE status = 'active' ORDER BY name");
    $stations = $stmt->fetchAll();
    
    // Fetch trajets with start and end station details
    $trajetsStmt = $pdo->query("
        SELECT 
            t.id, 
            t.distance, 
            t.description, 
            t.route_coordinates, 
            t.route_description,
            t.co2_saved,
            t.battery_energy,
            t.fuel_saved,
            COALESCE(s_start.id, 0) AS start_station_id,
            COALESCE(s_start.name, t.start_point_name) AS start_station_name,
            COALESCE(s_start.location, t.start_point) AS start_station_location,
            COALESCE(s_end.id, 0) AS end_station_id,
            COALESCE(s_end.name, t.end_point_name) AS end_station_name,
            COALESCE(s_end.location, t.end_point) AS end_station_location
        FROM 
            trajets t
        LEFT JOIN 
            stations s_start ON t.start_station_id = s_start.id
        LEFT JOIN 
            stations s_end ON t.end_station_id = s_end.id
        WHERE 
            (s_start.status = 'active' OR t.start_station_id IS NULL)
            AND (s_end.status = 'active' OR t.end_station_id IS NULL)
        ORDER BY 
            t.id
    ");
    $trajets = $trajetsStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur de base de données : " . $e->getMessage());
    $error = "Une erreur est survenue lors de la récupération des données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trajet et Station</title>
    <link rel="stylesheet" href="/just in case/public/green.css">
    <link rel="stylesheet" href="/just in case/public/trajet-et-station.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .trajet-section {
            padding: 40px 20px;
            
            margin: 30px auto;
            max-width: 1200px;
            border-radius: 10px;
            
        }
        
        .trajet-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
            margin-top: 30px;
            padding: 30px;
            
            border-radius: 8px;
            
            width: 100%;
        }
        
        .trajet-card {
            width: 100%;
            box-sizing: border-box;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
        }
        
        .trajet-card:hover {
            transform: translateY(-5px);
        }
        
        .trajet-card h3 {
            color: #3a9856;
            margin-top: 0;
            font-size: 18px;
            border-bottom: 2px solid #3a9856;
            padding-bottom: 10px;
        }
        
        .trajet-info {
            margin: 15px 0;
        }
        
        .trajet-info p {
            margin: 8px 0;
            color: #333;
        }
        
        .trajet-info strong {
            color: #3a9856;
        }
        
        .route-description {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 10px;
            margin: 15px 0;
            max-height: 100px;
            overflow-y: auto;
            font-size: 14px;
        }
        
        .maps-btn {
            background-color: #3a9856;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            font-weight: bold;
        }
        
        .maps-btn:hover {
            background-color: #2a7240;
        }
        
        .route-toggle {
            background-color: #f0f0f0;
            color: #444;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
            text-align: center;
        }
        
        .route-toggle:hover {
            background-color: #e0e0e0;
        }
        
        .hidden {
            display: none;
        }
        
        .stations-section {
            margin-bottom: 40px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            position: relative;
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }
        
        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #555;
        }
        
        .map-container {
            height: 400px;
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .modal-header h3 {
            color: #3a9856;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3a9856;
        }
        
        .modal-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
    <!-- Header -->
    <header>
        <section class="logo-nav-container">
            <section class="logo">
                <img src="/just in case/public/image/ve.png" alt="Green.tn Logo">
            </section>
            <nav class="nav-left">
                <ul>
                    <li><a href="/just in case/public/green.html">Home</a></li>
                    <li><a href="/just in case/public/green.html#a-nos-velos">Nos vélos</a></li>
                    <li><a href="/just in case/public/green.html#a-propos-de-nous">À propos de nous</a></li>
                    <li><a href="/just in case/public/green.html#pricing">Tarifs</a></li>
                    <li><a href="/just in case/public/green.html#contact">Contact</a></li>
                    <li><a href="/just in case/public/trajet-et-station.php">Trajet et Station</a></li>
                </ul>
            </nav>
        </section>
        <nav class="nav-right">
            <ul>
                <li><a href="#" class="login">Login</a></li>
                <li><a href="#" class="signin">Sign in</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Top Container for Illustration and Stations -->
        <section class="container">
            <section class="illustration">
                <img src="/just in case/public/image/lost.png" alt="Illustration">
            </section>
            
            <section class="stations-section">
                <h2 class="section-title">Nos stations</h2>
                <section class="stations">
                    <?php if (isset($error)): ?>
                        <div class="error-message">
                            Une erreur est survenue lors du chargement des stations.
                        </div>
                    <?php else: ?>
                        <?php foreach ($stations as $station): ?>
                            <button class="station-btn" data-location="<?php echo htmlspecialchars($station['location']); ?>">
                                <?php echo htmlspecialchars($station['name']); ?>
                            </button>
                        <?php endforeach; ?>
                        
                        <?php if (empty($stations)): ?>
                            <p class="no-stations">Aucune station n'est disponible pour le moment.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </section>
        </section>
        <!-- Trajet Section -->
<section class="trajet-section">
    <h2 class="section-title">Nos trajets</h2>
    <section class="trajet-container">
        <?php if (isset($trajets) && !empty($trajets)): ?>
            <?php foreach ($trajets as $trajet): ?>
                <section class="trajet-card improved-trajet-card">
                    <div class="trajet-card-header">
                        <h3>
                            <span class="trajet-icon">🚲</span>
                            <?php echo htmlspecialchars($trajet['description']); ?>
                        </h3>
                    </div>
                    <div class="trajet-info improved-trajet-info">
                        <div class="trajet-stations">
                            <span class="trajet-station-label"><strong>De:</strong></span>
                            <span class="trajet-station-value"><?php echo htmlspecialchars($trajet['start_station_name']); ?></span>
                            <span class="trajet-arrow">→</span>
                            <span class="trajet-station-label"><strong>À:</strong></span>
                            <span class="trajet-station-value"><?php echo htmlspecialchars($trajet['end_station_name']); ?></span>
                        </div>
                        <div class="trajet-metrics">
                            <div class="trajet-metric">
                                <span class="trajet-metric-icon">📏</span>
                                <span><?php echo htmlspecialchars($trajet['distance']); ?> km</span>
                            </div>
                            <div class="trajet-metric">
                                <span class="trajet-metric-icon">🌱</span>
                                <span><?php echo htmlspecialchars($trajet['co2_saved']); ?> g CO₂</span>
                            </div>
                            <div class="trajet-metric">
                                <span class="trajet-metric-icon">🔋</span>
                                <span><?php echo htmlspecialchars($trajet['battery_energy']); ?> Wh</span>
                            </div>
                            <div class="trajet-metric">
                                <span class="trajet-metric-icon">⛽</span>
                                <span><?php echo htmlspecialchars($trajet['fuel_saved']); ?> L</span>
                            </div>
                        </div>
                    </div>
                    <div class="route-description improved-route-description">
                        <?php echo nl2br(htmlspecialchars($trajet['route_description'])); ?>
                    </div>
                    <button class="maps-btn improved-maps-btn"
                            data-id="<?php echo $trajet['id']; ?>"
                            data-start="<?php echo htmlspecialchars($trajet['start_station_location']); ?>"
                            data-end="<?php echo htmlspecialchars($trajet['end_station_location']); ?>"
                            data-start-name="<?php echo htmlspecialchars($trajet['start_station_name']); ?>"
                            data-end-name="<?php echo htmlspecialchars($trajet['end_station_name']); ?>"
                            data-route='<?php echo htmlspecialchars($trajet['route_coordinates']); ?>'
                            data-distance="<?php echo htmlspecialchars($trajet['distance']); ?>"
                            data-description="<?php echo htmlspecialchars($trajet['description']); ?>"
                            onclick="openRouteInMaps(this)">
                        Voir sur Maps
                    </button>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-trajets">Aucun trajet n'est disponible pour le moment.</p>
        <?php endif; ?>
    </section>
</section>
        
        <!-- Map Modal -->
        <div id="mapModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <div class="modal-header">
                    <h3 id="modal-title">Trajet</h3>
                </div>
                <div id="map-container" class="map-container"></div>
                <div id="route-details"></div>
                <div class="modal-footer">
                    <button id="open-osm" class="maps-btn">Ouvrir dans OpenStreetMap</button>
                </div>
            </div>
        </div>
    </main>
    <script>
    // Function to open station location in Google Maps
    // Replace the current openInMaps function with this enhanced version
    function openInMaps(location, stationName) {
        try {
            // Validate input
            if (!location) {
                throw new Error("Location data is missing for station");
            }
            
            // Check if Leaflet is loaded
            if (typeof L === 'undefined') {
                throw new Error("Map library not loaded");
            }
            
            // Strict coordinate validation
            const coordPattern = /^-?\d{1,3}\.\d+,\s*-?\d{1,3}\.\d+$/;
            if (!coordPattern.test(location)) {
                throw new Error(`Invalid location format: ${location}. Expected 'lat,lng'`);
            }
            
            const [lat, lng] = location.split(',').map(coord => parseFloat(coord.trim()));
            
            // Validate coordinate ranges
            if (Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                throw new Error(`Coordinates out of range (${lat},${lng})`);
            }
            
            // Initialize map with error handling
            try {
                if (!map || !map.setView) {
                    initMap();
                    if (!map) throw new Error("Map initialization failed");
                }
            } catch (initError) {
                throw new Error(`Map initialization error: ${initError.message}`);
            }
            
            // Clear existing layers
            if (routeLayer && routeLayer.clearLayers) {
                routeLayer.clearLayers();
            } else {
                routeLayer = L.layerGroup().addTo(map);
            }
            
            // Set view with error handling
            try {
                map.setView([lat, lng], 15);
            } catch (viewError) {
                throw new Error(`Failed to set map view: ${viewError.message}`);
            }
            
            // Add marker with error handling
            try {
                L.marker([lat, lng], {
                    title: stationName || "Station",
                    alt: stationName || "Station Location"
                })
                .addTo(map)
                .bindPopup(stationName || "Station Location")
                .openPopup();
                
                return true; // Success
            } catch (markerError) {
                throw new Error(`Failed to add marker: ${markerError.message}`);
            }
            
        } catch (error) {
            console.error("Station Map Error:", {
                error: error.message,
                location: location,
                station: stationName
            });
            
            // Show user-friendly error
            const errorMsg = `Cannot display station: ${error.message.replace(/^Error:\s*/i, '')}`;
            document.getElementById('route-details').innerHTML = `
                <div class="error-message">
                    ${errorMsg}<br>
                    Location: ${location || 'N/A'}
                </div>
            `;
            
            return false; // Failure
        }
    }
    
    // Variables for modal and map
    let map;
    let routeLayer = L.layerGroup();
    let currentTrajet = null;
    const modal = document.getElementById('mapModal');
    
    // Initialize map when modal is opened
    function initMap() {
           if (!map) {
            // Create Leaflet map instance
            map = L.map('map-container').setView([36.8065, 10.1815], 10); // Tunisia center
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Initialize route layer
            routeLayer = L.layerGroup().addTo(map);
        }
    }
    function closeModal() {
    modal.style.display = 'none';
}
    // Function to display the route on a modal map
    function openRouteInMaps(element) {
        try {
            // Store current trajet data
            currentTrajet = {
                id: element.dataset.id,
                startLocation: element.dataset.start,
                endLocation: element.dataset.end,
                startName: element.dataset.startName,
                endName: element.dataset.endName,
                routeCoordinates: element.dataset.route,
                distance: element.dataset.distance,
                description: element.dataset.description
            };
            
            // Update modal title
            document.getElementById('modal-title').textContent = currentTrajet.description;
            
            // Show route details
            document.getElementById('route-details').innerHTML = `
                <p><strong>De:</strong> ${currentTrajet.startName} à <strong>${currentTrajet.endName}</strong></p>
                <p><strong>Distance:</strong> ${currentTrajet.distance} km</p>
            `;
            
            // Setup OpenStreetMap open button
            document.getElementById('open-osm').onclick = function() {
                openInOSM(currentTrajet);
            };
            
            // Open modal
            modal.style.display = 'block';
            
            // Initialize map and display route
            initMap();
            displayRoute(currentTrajet);
        } catch (error) {
            console.error("Error opening route:", error);
            document.getElementById('route-details').innerHTML = `
                <div class="error-message">
                    Could not display route: ${error.message}
                </div>
            `;
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize station buttons
        const stationButtons = document.querySelectorAll('.station-btn');
        stationButtons.forEach(button => {
            button.addEventListener('click', function() {
                const location = this.getAttribute('data-location');
                const stationName = this.textContent.trim();
                
                // Open modal first
                modal.style.display = 'block';
                
                // Then show location
                if (location) {
                    openInMaps(location, stationName);
                }
                
                // Update modal title
                document.getElementById('modal-title').textContent = stationName;
                
                // Hide route details in station view
                document.getElementById('route-details').innerHTML = `
                    <p><strong>Station:</strong> ${stationName}</p>
                    <p><strong>Location:</strong> ${location}</p>
                `;
                
                // Change button text for stations
                document.getElementById('open-osm').textContent = 'Ouvrir dans OpenStreetMap';
                document.getElementById('open-osm').onclick = function() {
                    window.open(`https://www.openstreetmap.org/search?query=${encodeURIComponent(location)}`, '_blank');
                };
            });
        });
        
        // Close modal when clicking outside the content
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    });
    </script>
     <script>
    // Function to display route on map
    function displayRoute(trajet) {
        try {
            // Clear existing route
            routeLayer.clearLayers();
            
            // Parse route coordinates
            const routeCoords = JSON.parse(trajet.routeCoordinates);
            
            // Convert coordinates to Leaflet LatLng objects
            const latLngs = routeCoords.map(coord => L.latLng(coord.lat, coord.lng));
            
            // Draw the route on the map
            L.polyline(latLngs, {
                color: '#3a9856',
                weight: 5,
                opacity: 0.7,
                smoothFactor: 1
            }).addTo(routeLayer);
            
            // Add start and end markers
            if (trajet.startLocation) {
                const [startLat, startLng] = trajet.startLocation.split(',').map(Number);
                L.marker([startLat, startLng], {
                    title: trajet.startName || "Start Point"
                })
                .addTo(routeLayer)
                .bindPopup(`<b>Start:</b> ${trajet.startName}`);
            }
            
            if (trajet.endLocation) {
                const [endLat, endLng] = trajet.endLocation.split(',').map(Number);
                L.marker([endLat, endLng], {
                    title: trajet.endName || "End Point"
                })
                .addTo(routeLayer)
                .bindPopup(`<b>End:</b> ${trajet.endName}`);
            }
            
            // Fit map to route bounds
            if (latLngs.length > 0) {
                map.fitBounds(latLngs);
            }
        } catch (error) {
            console.error("Error displaying route:", error);
            document.getElementById('route-details').innerHTML += `
                <div class="error-message">
                    Could not display route: ${error.message}
                </div>
            `;
        }
    }

function openInOSM(trajet) {
    try {
        if (!trajet.startLocation || !trajet.endLocation) {
            throw new Error("Start or end location missing");
        }
        
        const startCoords = trajet.startLocation.split(',');
        const endCoords = trajet.endLocation.split(',');
        
        const url = `https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=${startCoords[0]}%2C${startCoords[1]}%3B${endCoords[0]}%2C${endCoords[1]}`;
        window.open(url, '_blank');
    } catch (error) {
        console.error("Error opening in OSM:", error);
        alert("Could not open in OpenStreetMap: " + error.message);
    }
}
</script>
</body>
</html>
