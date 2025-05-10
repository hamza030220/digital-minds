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

        /* Weather Button and Cart Styles */
        .weather-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #3a9856;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, background-color 0.3s ease;
            z-index: 1000;
        }

        .weather-button:hover {
            transform: scale(1.1);
            background-color: #2a7240;
        }

        .weather-button i {
            color: white;
            font-size: 24px;
        }

        .weather-cart {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 300px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 20px;
            display: none;
            z-index: 999;
        }

        .weather-cart.active {
            display: block;
        }

        .weather-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .weather-header h3 {
            margin: 0;
            color: #3a9856;
        }

        .weather-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }

        .weather-content {
            text-align: center;
        }

        .weather-icon {
            font-size: 48px;
            margin: 10px 0;
        }

        .weather-temp {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }

        .weather-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }

        .weather-detail {
            text-align: center;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 8px;
        }

        .weather-detail span {
            display: block;
            font-size: 14px;
            color: #666;
        }

        .weather-detail strong {
            display: block;
            font-size: 18px;
            color: #333;
            margin-top: 5px;
        }

        .nearest-station-btn {
            position: fixed;
            bottom: 30px;
            right: 100px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #3a9856;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, background-color 0.3s ease;
            z-index: 1000;
        }

        .nearest-station-btn:hover {
            transform: scale(1.1);
            background-color: #2a7240;
        }

        .nearest-station-btn i {
            color: white;
            font-size: 24px;
        }

        .nearest-station-cart {
            position: fixed;
            bottom: 100px;
            right: 100px;
            width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 20px;
            display: none;
            z-index: 999;
        }

        .nearest-station-cart.active {
            display: block;
        }

        .nearest-station-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .nearest-station-header h3 {
            margin: 0;
            color: #3a9856;
        }

        .nearest-station-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }

        .nearest-station-map {
            height: 300px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .nearest-station-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .nearest-station-info p {
            margin: 5px 0;
            color: #333;
        }

        .nearest-station-info strong {
            color: #3a9856;
        }

        .nearest-station-actions {
            display: flex;
            gap: 10px;
        }

        .nearest-station-actions button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .directions-btn {
            background-color: #3a9856;
            color: white;
        }

        .directions-btn:hover {
            background-color: #2a7240;
        }
    </style>
    <!-- Add Font Awesome for weather icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stations-pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            margin: 0 4px;
            border-radius: 50%;
            border: none;
            background: #fff;
            color: #3a9856;
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 2px 6px rgba(58,152,86,0.08);
            cursor: pointer;
            transition: 
                background 0.3s, 
                color 0.3s, 
                transform 0.2s cubic-bezier(.4,2,.6,1);
            outline: none;
        }
        .stations-pagination-btn.active,
        .stations-pagination-btn:focus {
            background: #3a9856;
            color: #fff;
        }
        .stations-pagination-btn:hover:not(:disabled) {
            background: #3a9856;
            color: #fff;
            transform: scale(1.18) rotate(-6deg);
            box-shadow: 0 4px 16px rgba(58,152,86,0.18);
        }
        .stations-pagination-btn:disabled {
            background: #e0e0e0;
            color: #bdbdbd;
            cursor: not-allowed;
            box-shadow: none;
        }
        .station-locate-btn {
          display: inline-flex;
          align-items: center;
           justify-content: center;
           margin-left: 10px;
          cursor: pointer;
          border-radius: 50%;
          transition: background 0.2s, transform 0.2s;
           padding: 2px;
          }
        .station-locate-btn:hover {
         background: #e6f7ec;
         transform: scale(1.15);
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
                <h2 class="section-title" style="display:inline-block;">Nos stations</h2>

                <section class="stations" id="stations-list">
                    <?php if (isset($error)): ?>
                        <div class="error-message">
                            Une erreur est survenue lors du chargement des stations.
                        </div>
                    <?php else: ?>
                        <?php foreach ($stations as $i => $station): ?>
                            <button class="station-btn"
                                    data-location="<?php echo htmlspecialchars($station['location']); ?>"
                                    data-index="<?php echo $i; ?>"
                                    style="display: none;">
                                <?php echo htmlspecialchars($station['name']); ?>
                            </button>
                        <?php endforeach; ?>
                        <?php if (empty($stations)): ?>
                            <p class="no-stations">Aucune station n'est disponible pour le moment.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
                <div id="stations-pagination" style="text-align:center; margin-top:15px;"></div>
                <div style="text-align:center; margin-top:10px;"></div>

                </div>
            </section>
        </section>

        <!-- Trajet Section -->
        <section class="trajet-section">
            <h2 class="section-title">Nos trajets</h2>
            <section class="trajet-container" id="trajets-list">
                <?php if (isset($trajets) && !empty($trajets)): ?>
                    <?php foreach ($trajets as $i => $trajet): ?>
                        <section class="trajet-card improved-trajet-card" data-index="<?php echo $i; ?>" style="display: none;">
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
            <div id="trajets-pagination" style="text-align:center; margin-top:15px;"></div>
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

    </main>

    <!-- Weather Button and Cart -->
    <button class="weather-button" id="weatherButton">
        <i class="fas fa-cloud-sun"></i>
    </button>

    <div class="weather-cart" id="weatherCart">
        <div class="weather-header">
            <h3>Weather Information</h3>
            <button class="weather-close" id="weatherClose">&times;</button>
        </div>
        <div class="weather-content">
            <div class="weather-icon" id="weatherIcon"></div>
            <div class="weather-temp" id="weatherTemp"></div>
            <div class="weather-desc" id="weatherDesc"></div>
            <div class="weather-details">
                <div class="weather-detail">
                    <span>Humidity</span>
                    <strong id="weatherHumidity"></strong>
                </div>
                <div class="weather-detail">
                    <span>Wind Speed</span>
                    <strong id="weatherWind"></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Nearest Station Button and Cart -->
    <button class="nearest-station-btn" id="nearestStationBtn">
        <i class="fas fa-map-marker-alt"></i>
    </button>

    <div class="nearest-station-cart" id="nearestStationCart">
        <div class="nearest-station-header">
            <h3>Station la plus proche</h3>
            <button class="nearest-station-close" id="nearestStationClose">&times;</button>
        </div>
        <div id="nearestStationMap" class="nearest-station-map"></div>
        <div class="nearest-station-info" id="nearestStationInfo">
            <p><strong>Station:</strong> <span id="stationName"></span></p>
            <p><strong>Distance:</strong> <span id="stationDistance"></span></p>
            <p><strong>Durée estimée:</strong> <span id="stationDuration"></span></p>
        </div>
        <div class="nearest-station-actions">
            <button class="directions-btn" id="getDirectionsBtn">
                <i class="fas fa-directions"></i> Obtenir l'itinéraire
            </button>
        </div>
    </div>

    <script>
    // --- Station Modal, Pagination, and Trajet Map Logic ---
    function openInMaps(location, stationName) {
        try {
            if (!location) throw new Error("Location data is missing for station");
            if (typeof L === 'undefined') throw new Error("Map library not loaded");
            const coordPattern = /^-?\d{1,3}\.\d+,\s*-?\d{1,3}\.\d+$/;
            if (!coordPattern.test(location)) throw new Error(`Invalid location format: ${location}. Expected 'lat,lng'`);
            const [lat, lng] = location.split(',').map(coord => parseFloat(coord.trim()));
            if (Math.abs(lat) > 90 || Math.abs(lng) > 180) throw new Error(`Coordinates out of range (${lat},${lng})`);
            if (!map || !map.setView) {
                initMap();
                if (!map) throw new Error("Map initialization failed");
            }
            if (routeLayer && routeLayer.clearLayers) {
                routeLayer.clearLayers();
            } else {
                routeLayer = L.layerGroup().addTo(map);
            }
            map.setView([lat, lng], 15);
            L.marker([lat, lng], {
                title: stationName || "Station",
                alt: stationName || "Station Location"
            })
            .addTo(routeLayer)
            .bindPopup(stationName || "Station Location")
            .openPopup();
            // Correction : forcer le recalcul de la taille de la carte après affichage
            setTimeout(function() { if(map && map.invalidateSize) map.invalidateSize(); }, 200);
            return true;
        } catch (error) {
            console.error("Station Map Error:", { error: error.message, location: location, station: stationName });
            const errorMsg = `Cannot display station: ${error.message.replace(/^Error:\s*/i, '')}`;
            document.getElementById('route-details').innerHTML = `
                <div class="error-message">
                    ${errorMsg}<br>
                    Location: ${location || 'N/A'}
                </div>
            `;
            return false;
        }
    }

    function displayRoute(trajet) {
        try {
            routeLayer.clearLayers();
            const routeCoords = JSON.parse(trajet.routeCoordinates);
            const latLngs = routeCoords.map(coord => L.latLng(coord.lat, coord.lng));
            L.polyline(latLngs, {
                color: '#3a9856',
                weight: 5,
                opacity: 0.7,
                smoothFactor: 1
            }).addTo(routeLayer);
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

    let map;
    let routeLayer;
    let currentTrajet = null;
    const modal = document.getElementById('mapModal');

    function initMap() {
        if (!map) {
            map = L.map('map-container').setView([36.8065, 10.1815], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            routeLayer = L.layerGroup().addTo(map);
        }
    }
    function closeModal() {
        modal.style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // --- Pagination for stations ---
        const stationsPerPage = 5;
        const stationButtons = document.querySelectorAll('.station-btn');
        const paginationDiv = document.getElementById('stations-pagination');
        let currentPage = 1;
        const totalStations = stationButtons.length;
        const totalPages = Math.ceil(totalStations / stationsPerPage);

        // Make the function global BEFORE rendering pagination
        window.changeStationsPage = function(page) {
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            showStationsPage(page);
        };

        function showStationsPage(page) {
            stationButtons.forEach(btn => btn.style.display = 'none');
            const start = (page - 1) * stationsPerPage;
            const end = start + stationsPerPage;
            for (let i = start; i < end && i < totalStations; i++) {
                stationButtons[i].style.display = '';
            }
            renderPagination(page);
        }

        function renderPagination(page) {
            if (totalStations === 0) {
                paginationDiv.innerHTML = '';
                return;
            }
            let html = '';
            html += `<button class="stations-pagination-btn" ${page === 1 ? 'disabled' : ''} onclick="changeStationsPage(${page-1})">&laquo;</button>`;
            for (let i = 1; i <= totalPages; i++) {
                html += `<button class="stations-pagination-btn${i === page ? ' active' : ''}" onclick="changeStationsPage(${i})">${i}</button>`;
            }
            html += `<button class="stations-pagination-btn" ${page === totalPages ? 'disabled' : ''} onclick="changeStationsPage(${page+1})">&raquo;</button>`;
            paginationDiv.innerHTML = html;
        }

        // Initial display (after global assignment)
        showStationsPage(currentPage);

        // --- Station modal logic ---
        stationButtons.forEach(button => {
            button.addEventListener('click', function() {
                const location = this.getAttribute('data-location');
                const stationName = this.textContent.trim();
                modal.style.display = 'block';
                if (location) {
                    openInMaps(location, stationName);
                }
                document.getElementById('modal-title').textContent = stationName;
                document.getElementById('route-details').innerHTML = `
                    <p><strong>Station:</strong> ${stationName}</p>
                    <p><strong>Location:</strong> ${location}</p>
                `;
                document.getElementById('open-osm').textContent = 'Ouvrir dans OpenStreetMap';
                document.getElementById('open-osm').onclick = function() {
                    window.open(`https://www.openstreetmap.org/search?query=${encodeURIComponent(location)}`, '_blank');
                };
            });
        });
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    });


        // --- Pagination for stations ---
        const stationsPerPage = 5;
        const stationButtons = document.querySelectorAll('.station-btn');
        const paginationDiv = document.getElementById('stations-pagination');
        let currentPage = 1;
        const totalStations = stationButtons.length;
        const totalPages = Math.ceil(totalStations / stationsPerPage);

        // --- Pagination for trajets ---
        const trajetsPerPage = 3;
        const trajetCards = document.querySelectorAll('.trajet-card');
        const trajetsPaginationDiv = document.getElementById('trajets-pagination');
        let currentTrajetPage = 1;
        const totalTrajets = trajetCards.length;
        const totalTrajetPages = Math.ceil(totalTrajets / trajetsPerPage);

        window.changeTrajetsPage = function(page) {
            if (page < 1 || page > totalTrajetPages) return;
            currentTrajetPage = page;
            showTrajetsPage(page);
        };

        function showTrajetsPage(page) {
            trajetCards.forEach(card => card.style.display = 'none');
            const start = (page - 1) * trajetsPerPage;
            const end = start + trajetsPerPage;
            for (let i = start; i < end && i < totalTrajets; i++) {
                trajetCards[i].style.display = '';
            }
            renderTrajetsPagination(page);
        }

        function renderTrajetsPagination(page) {
            if (totalTrajets === 0) {
                trajetsPaginationDiv.innerHTML = '';
                return;
            }
            let html = '';
            html += `<button class="stations-pagination-btn" ${page === 1 ? 'disabled' : ''} onclick="changeTrajetsPage(${page-1})">&laquo;</button>`;
            for (let i = 1; i <= totalTrajetPages; i++) {
                html += `<button class="stations-pagination-btn${i === page ? ' active' : ''}" onclick="changeTrajetsPage(${i})">${i}</button>`;
            }
            html += `<button class="stations-pagination-btn" ${page === totalTrajetPages ? 'disabled' : ''} onclick="changeTrajetsPage(${page+1})">&raquo;</button>`;
            trajetsPaginationDiv.innerHTML = html;
        }

        // Initial display for trajets
        showTrajetsPage(currentTrajetPage);



            
            </script>

    <!-- Weather functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const weatherButton = document.getElementById('weatherButton');
        const weatherCart = document.getElementById('weatherCart');
        const weatherClose = document.getElementById('weatherClose');
        const weatherIcon = document.getElementById('weatherIcon');
        const weatherTemp = document.getElementById('weatherTemp');
        const weatherDesc = document.getElementById('weatherDesc');
        const weatherHumidity = document.getElementById('weatherHumidity');
        const weatherWind = document.getElementById('weatherWind');

        // Fixed position coordinates
        const fixedPosition = {
            lat: 36.898139,  // 36°53'53.3"N
            lng: 10.189639  // 10°11'22.7"E
        };

        weatherButton.addEventListener('click', () => {
            weatherCart.classList.toggle('active');
            if (weatherCart.classList.contains('active')) {
                fetchWeatherData(fixedPosition.lat, fixedPosition.lng);
            }
        });

        weatherClose.addEventListener('click', () => {
            weatherCart.classList.remove('active');
        });

        function fetchWeatherData(lat, lon) {
            const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code&timezone=auto`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    updateWeatherUI(data);
                })
                .catch(error => {
                    console.error('Error fetching weather:', error);
                    weatherDesc.textContent = 'Error fetching weather data';
                });
        }

        function updateWeatherUI(data) {
            const current = data.current;
            const temp = Math.round(current.temperature_2m);
            const humidity = current.relative_humidity_2m;
            const windSpeed = current.wind_speed_10m;
            const weatherCode = current.weather_code;

            weatherTemp.textContent = `${temp}°C`;
            weatherHumidity.textContent = `${humidity}%`;
            weatherWind.textContent = `${windSpeed} km/h`;

            // Set weather description and icon based on WMO Weather interpretation codes
            let description = '';
            let iconClass = '';

            switch (weatherCode) {
                case 0:
                    description = 'Clear sky';
                    iconClass = 'fa-sun';
                    break;
                case 1:
                case 2:
                case 3:
                    description = 'Partly cloudy';
                    iconClass = 'fa-cloud-sun';
                    break;
                case 45:
                case 48:
                    description = 'Foggy';
                    iconClass = 'fa-smog';
                    break;
                case 51:
                case 53:
                case 55:
                    description = 'Drizzle';
                    iconClass = 'fa-cloud-rain';
                    break;
                case 61:
                case 63:
                case 65:
                    description = 'Rain';
                    iconClass = 'fa-cloud-showers-heavy';
                    break;
                case 71:
                case 73:
                case 75:
                    description = 'Snow';
                    iconClass = 'fa-snowflake';
                    break;
                case 77:
                    description = 'Snow grains';
                    iconClass = 'fa-snowflake';
                    break;
                case 80:
                case 81:
                case 82:
                    description = 'Rain showers';
                    iconClass = 'fa-cloud-showers-heavy';
                    break;
                case 85:
                case 86:
                    description = 'Snow showers';
                    iconClass = 'fa-snowflake';
                    break;
                case 95:
                    description = 'Thunderstorm';
                    iconClass = 'fa-bolt';
                    break;
                case 96:
                case 99:
                    description = 'Thunderstorm with hail';
                    iconClass = 'fa-bolt';
                    break;
                default:
                    description = 'Unknown';
                    iconClass = 'fa-cloud';
            }

            weatherDesc.textContent = description;
            weatherIcon.innerHTML = `<i class="fas ${iconClass}"></i>`;
        }
    });
    </script>

    <!-- Nearest Station functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const nearestStationBtn = document.getElementById('nearestStationBtn');
        const nearestStationCart = document.getElementById('nearestStationCart');
        const nearestStationClose = document.getElementById('nearestStationClose');
        const nearestStationMap = document.getElementById('nearestStationMap');
        const getDirectionsBtn = document.getElementById('getDirectionsBtn');
        
        let nearestStationMapInstance = null;
        let userMarker = null;
        let stationMarker = null;
        let routeLayer = null;
        let currentUserPosition = null;
        let nearestStation = null;

        nearestStationBtn.addEventListener('click', () => {
            nearestStationCart.classList.toggle('active');
            if (nearestStationCart.classList.contains('active')) {
                findNearestStation();
            }
        });

        nearestStationClose.addEventListener('click', () => {
            nearestStationCart.classList.remove('active');
        });

        function findNearestStation() {
            // Convert coordinates from DMS to decimal
            // 36°53'53.3"N 10°11'22.7"E
            currentUserPosition = {
                lat: 36.898139,  // 36°53'53.3"N
                lng: 10.189639  // 10°11'22.7"E
            };
            calculateNearestStation();
        }

        function calculateNearestStation() {
            const stations = Array.from(document.querySelectorAll('.station-btn'));
            let nearestDistance = Infinity;
            let nearest = null;

            stations.forEach(station => {
                const location = station.getAttribute('data-location');
                if (location) {
                    const [lat, lng] = location.split(',').map(Number);
                    const distance = calculateDistance(
                        currentUserPosition.lat,
                        currentUserPosition.lng,
                        lat,
                        lng
                    );
                    if (distance < nearestDistance) {
                        nearestDistance = distance;
                        nearest = {
                            name: station.textContent.trim(),
                            location: location,
                            distance: distance
                        };
                    }
                }
            });

            if (nearest) {
                nearestStation = nearest;
                displayNearestStation();
            } else {
                document.getElementById('nearestStationInfo').innerHTML = 
                    '<p class="error">Aucune station trouvée</p>';
            }
        }

        function displayNearestStation() {
            // Initialize map if not already done
            if (!nearestStationMapInstance) {
                nearestStationMapInstance = L.map('nearestStationMap').setView(
                    [currentUserPosition.lat, currentUserPosition.lng],
                    15
                );
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(nearestStationMapInstance);
                routeLayer = L.layerGroup().addTo(nearestStationMapInstance);
            }

            // Clear previous markers
            routeLayer.clearLayers();

            // Add user marker
            userMarker = L.marker([currentUserPosition.lat, currentUserPosition.lng], {
                icon: L.divIcon({
                    className: 'user-marker',
                    html: '<i class="fas fa-user-circle" style="color: #3a9856; font-size: 24px;"></i>'
                })
            }).addTo(routeLayer);

            // Add station marker
            const [stationLat, stationLng] = nearestStation.location.split(',').map(Number);
            stationMarker = L.marker([stationLat, stationLng], {
                icon: L.divIcon({
                    className: 'station-marker',
                    html: '<i class="fas fa-map-marker-alt" style="color: #3a9856; font-size: 24px;"></i>'
                })
            }).addTo(routeLayer);

            // Create bounds to show both markers
            const bounds = L.latLngBounds([
                [currentUserPosition.lat, currentUserPosition.lng],
                [stationLat, stationLng]
            ]);
            nearestStationMapInstance.fitBounds(bounds);

            // Update info
            document.getElementById('stationName').textContent = nearestStation.name;
            document.getElementById('stationDistance').textContent = 
                `${nearestStation.distance.toFixed(1)} km`;

            // Calculate estimated duration (assuming average speed of 15 km/h)
            const durationMinutes = Math.round((nearestStation.distance / 15) * 60);
            document.getElementById('stationDuration').textContent = 
                `${durationMinutes} minutes`;

            // Update directions button
            getDirectionsBtn.onclick = () => {
                const url = `https://www.openstreetmap.org/directions?engine=fossgis_osrm_bike&route=${currentUserPosition.lat},${currentUserPosition.lng};${stationLat},${stationLng}`;
                window.open(url, '_blank');
            };
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the earth in km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c; // Distance in km
        }

        function deg2rad(deg) {
            return deg * (Math.PI/180);
        }
    });
    </script>
</body>
</html>
   