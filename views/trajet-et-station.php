<?php
require_once '../includes/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = getDBConnection();
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
            s_start.id AS start_station_id, 
            s_start.name AS start_station_name, 
            s_start.location AS start_station_location,
            s_end.id AS end_station_id, 
            s_end.name AS end_station_name, 
            s_end.location AS end_station_location
        FROM 
            trajets t
        JOIN 
            stations s_start ON t.start_station_id = s_start.id
        JOIN 
            stations s_end ON t.end_station_id = s_end.id
        WHERE 
            s_start.status = 'active' AND s_end.status = 'active'
        ORDER BY 
            s_start.name, s_end.name
    ");
    $trajets = $trajetsStmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trajet et Station</title>
    <link rel="stylesheet" href="/green-admin/public/green.css">
    <link rel="stylesheet" href="/green-admin/public/trajet-et-station.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY" defer></script>
    <style>
        .trajet-section {
            padding: 40px 20px;
            background-color: #f5f5f5;
        }
        
        .trajet-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .trajet-card {
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
                <img src="/green-admin/public/image/ve.png" alt="Green.tn Logo">
            </section>
            <nav class="nav-left">
                <ul>
                    <li><a href="/green-admin/public/green.html">Home</a></li>
                    <li><a href="/green-admin/public/green.html#a-nos-velos">Nos vélos</a></li>
                    <li><a href="/green-admin/public/green.html#a-propos-de-nous">À propos de nous</a></li>
                    <li><a href="/green-admin/public/green.html#pricing">Tarifs</a></li>
                    <li><a href="/green-admin/public/green.html#contact">Contact</a></li>
                    <li><a href="/green-admin/public/trajet-et-station.php">Trajet et Station</a></li>
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
                <img src="/green-admin/public/image/lost.png" alt="Illustration">
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
                        <section class="trajet-card">
                            <h3><?php echo htmlspecialchars($trajet['description']); ?></h3>
                            
                            <div class="trajet-info">
                                <p><strong>De:</strong> <?php echo htmlspecialchars($trajet['start_station_name']); ?></p>
                                <p><strong>À:</strong> <?php echo htmlspecialchars($trajet['end_station_name']); ?></p>
                                <p><strong>Distance:</strong> <?php echo htmlspecialchars($trajet['distance']); ?> km</p>
                            </div>
                            
                            <div class="route-description">
                                <?php echo nl2br(htmlspecialchars($trajet['route_description'])); ?>
                            </div>
                            
                            <button class="maps-btn" 
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
                    <button id="open-google-maps" class="maps-btn">Ouvrir dans Google Maps</button>
                </div>
            </div>
        </div>
    </main>
    <script>
    // Function to open station location in Google Maps
    function openInMaps(location) {
        const encodedLocation = encodeURIComponent(location);
        window.open(`https://www.google.com/maps/search/?api=1&query=${encodedLocation}`, '_blank');
    }
    
    // Variables for modal and map
    let map, directionsService, directionsRenderer;
    let currentTrajet = null;
    const modal = document.getElementById('mapModal');
    
    // Initialize map when modal is opened
    function initMap() {
        if (!map) {
            // Create map instance
            map = new google.maps.Map(document.getElementById('map-container'), {
                zoom: 10,
                center: { lat: 36.8065, lng: 10.1815 }, // Tunisia center
                mapTypeControl: true,
                fullscreenControl: true
            });
            
            // Create directions service and renderer for later use
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: false,
                preserveViewport: false
            });
        }
    }
    
    // Function to display the route on a modal map
    function openRouteInMaps(element) {
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
        
        // Setup Google Maps open button
        document.getElementById('open-google-maps').onclick = function() {
            openInGoogleMaps(currentTrajet);
        };
        
        // Open modal
        modal.style.display = 'block';
        
        // Initialize map and display route
        initMap();
        displayRoute(currentTrajet);
    }
    
    // Function to display route with waypoints
    function displayRoute(trajet) {
        try {
            const startCoords = parseLocation(trajet.startLocation);
            const endCoords = parseLocation(trajet.endLocation);
            
            if (!startCoords || !endCoords) {
                showError("Coordonnées de départ ou d'arrivée invalides");
                return;
            }
            
            // Clear any existing directions
            directionsRenderer.setMap(map);
            
            // Parse saved route coordinates if available
            let waypoints = [];
            if (trajet.routeCoordinates) {
                try {
                    const routeData = JSON.parse(trajet.routeCoordinates);
                    
                    // Skip first and last points (start/end) and use middle points as waypoints
                    if (routeData && routeData.length > 2) {
                        // Take a reasonable number of waypoints to avoid exceeding API limits
                        // Use at most 8 waypoints (Google Maps limit is 23 for the free tier)
                        const step = Math.max(1, Math.floor((routeData.length - 2) / 8));
                        
                        for (let i = 1; i < routeData.length - 1; i += step) {
                            waypoints.push({
                                location: new google.maps.LatLng(
                                    routeData[i].lat,
                                    routeData[i].lng
                                ),
                                stopover: false
                            });
                        }
                    }
                } catch (e) {
                    console.error("Erreur lors de l'analyse des coordonnées du tracé:", e);
                }
            }
            
            // Create route request
            const request = {
                origin: startCoords,
                destination: endCoords,
                waypoints: waypoints,
                optimizeWaypoints: false,
                travelMode: google.maps.TravelMode.DRIVING
            };
            
            // Get directions from the Google Maps API
            directionsService.route(request, function(result, status) {
                if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(result);
                    
                    // Fit map to show the entire route
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend(startCoords);
                    bounds.extend(endCoords);
                    
                    // Add waypoints to bounds
                    waypoints.forEach(waypoint => {
                        bounds.extend(waypoint.location);
                    });
                    
                    map.fitBounds(bounds);
                } else {
                    // If saved route cannot be displayed, show a simple route
                    showSimpleRoute(startCoords, endCoords);
                    console.error("Erreur d'affichage de l'itinéraire:", status);
                }
            });
        } catch (error) {
            console.error("Erreur lors de l'affichage de l'itinéraire:", error);
            showError("Impossible d'afficher l'itinéraire. Veuillez réessayer plus tard.");
        }
    }
    
    // Show a simple route between start and end points if detailed route fails
    function showSimpleRoute(startCoords, endCoords) {
        const request = {
            origin: startCoords,
            destination: endCoords,
            travelMode: google.maps.TravelMode.DRIVING
        };
        
        directionsService.route(request, function(result, status) {
            if (status === google.maps.DirectionsStatus.OK) {
                directionsRenderer.setDirections(result);
                
                // Fit map to show the entire route
                const bounds = new google.maps.LatLngBounds();
                bounds.extend(startCoords);
                bounds.extend(endCoords);
                map.fitBounds(bounds);
            } else {
                showError("Impossible de calculer l'itinéraire. Veuillez réessayer plus tard.");
            }
        });
    }
    
    // Parse location string "lat,lng" to coordinates object
    function parseLocation(locationString) {
        if (!locationString) return null;
        
        try {
            const [lat, lng] = locationString.split(',').map(coord => parseFloat(coord.trim()));
            if (isNaN(lat) || isNaN(lng)) return null;
            return { lat, lng };
        } catch (error) {
            console.error("Erreur d'analyse des coordonnées:", error);
            return null;
        }
    }
    
    // Open route in Google Maps website
    function openInGoogleMaps(trajet) {
        try {
            const startLocation = trajet.startLocation;
            const endLocation = trajet.endLocation;
            
            if (!startLocation || !endLocation) {
                showError("Données de localisation manquantes");
                return;
            }
            
            // Create Google Maps URL with start and end points
            const url = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(startLocation)}&destination=${encodeURIComponent(endLocation)}&travelmode=driving`;
            
            // Open Google Maps in a new tab
            window.open(url, '_blank');
        } catch (error) {
            console.error("Erreur lors de l'ouverture de Google Maps:", error);
            showError("Impossible d'ouvrir Google Maps. Veuillez réessayer plus tard.");
        }
    }
    
    // Close the map modal
    function closeModal() {
        modal.style.display = 'none';
    }
    
    // Show error message
    function showError(message) {
        alert(message);
    }
    
    // Add event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize station buttons
        const stationButtons = document.querySelectorAll('.station-btn');
        stationButtons.forEach(button => {
            button.addEventListener('click', function() {
                const location = this.getAttribute('data-location');
                if (location) {
                    openInMaps(location);
                }
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
</body>
</html>
