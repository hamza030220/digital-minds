/**
 * Route Map Module
 * Handles map initialization, route drawing, and coordinate calculations
 */

// Global variables
let map;
let currentRoute;
let markers = [];
let startPoint = null;
let endPoint = null;
/**
 * Map Initialization
 */

// Initialize the map
function initMap() {
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('Map element not found');
        return;
    }
    map = L.map(mapElement).setView([48.8566, 2.3522], 12); // Paris coordinates, zoom level 12

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add event listeners for route buttons
    document.getElementById('draw-route').addEventListener('click', startRouteDrawing);
    document.getElementById('optimize-route').addEventListener('click', optimizeRoute);
    document.getElementById('reset-route').addEventListener('click', resetRoute);
    
    // Create and add finish drawing button to the map
    createFinishDrawingButton();
    
    // Setup form validation
    setupFormValidation();
}

/**
 * Marker Management Functions
 */
///------------------------------------------------------------------------------
// Function to get reverse geocoding data from Nominatim
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
//------------------------------------------------------------------------------------
// Add a marker to the map
function addMarker(latlng, popupText, markerType) {
    const marker = L.marker(latlng).addTo(map);
    if (popupText) {
        marker.bindPopup(popupText);
    }
    if (markerType === 'start') {
        getReverseGeocode(latlng, 'start_point_name');
    } else if (markerType === 'end') {
        getReverseGeocode(latlng, 'end_point_name');
    }
    markers.push(marker);
    return marker;
}

// Clear all markers from the map
function clearMarkers() {
    markers.forEach(marker => {
        map.removeLayer(marker);
    });
    markers = [];
}
/**
 * Route Drawing Functions
 */

// Start the route drawing process
function startRouteDrawing() {
    resetRoute();
    alert('Cliquez sur un point de départ sur la carte');
    
    map.on('click', function handleStartClick(event) {
        startPoint = event.latlng;
        map.off('click', handleStartClick); // Remove the event listener after the start point is selected
        
        // Add marker for start point
        addMarker(startPoint, 'Point de départ', 'start');
        
        // Get location name for start point
        getReverseGeocode(startPoint, 'start_point_name');
        
        // Proceed to select end point
        selectEndPoint();
    });
    
    // Show finish drawing button when drawing starts
    showFinishDrawingButton();
}

// Handle selection of end point
function selectEndPoint() {
    alert('Cliquez sur un point d\'arrivée sur la carte');
    
    map.on('click', function handleEndClick(event) {
        endPoint = event.latlng;
        
        // Check if end point is too close to start point
        if (L.latLng(startPoint).distanceTo(L.latLng(endPoint)) < 10) {
            alert('Veuillez sélectionner un point d\'arrivée plus éloigné du point de départ.');
            return;
        }
        
        // Remove the event listener after the end point is selected
        map.off('click', handleEndClick);
        
        // Add marker for end point
        addMarker(endPoint, 'Point d\'arrivée', 'end');
        
        // Get location name for end point
        getReverseGeocode(endPoint, 'end_point_name');
        
        // Calculate route between points
        calculateRoute(startPoint, endPoint);
    });
}

/**
 * Route Calculation Functions
 */
// Calculate route using OSRM
async function calculateRoute(startLatLng, endLatLng) {
    if (!startLatLng || !endLatLng) {
        alert('Erreur: Points de départ ou d\'arrivée manquants');
        return;
    }
    
    try {
        // OSRM expects coordinates in [lng, lat] format
        const response = await axios.get(`https://router.project-osrm.org/route/v1/driving/${startLatLng.lng},${startLatLng.lat};${endLatLng.lng},${endLatLng.lat}?geometries=geojson&steps=true`);
        const routeData = response.data;

        if (routeData.code === 'Ok') {
            handleSuccessfulRoute(routeData, startLatLng, endLatLng);
        } else {
            throw new Error('Route calculation failed');
        }
    } catch (error) {
        console.error('Error calculating route:', error);
        calculateDirectRoute(startLatLng, endLatLng);
    }
}
// Handle successful route calculation
function handleSuccessfulRoute(routeData, startLatLng, endLatLng) {
    const routeGeometry = routeData.routes[0].geometry.coordinates;
    const distance = routeData.routes[0].distance / 1000; // Convert to kilometers
    const duration = routeData.routes[0].duration / 60; // Convert to minutes

    // Draw the route on the map
    if (currentRoute) {
        map.removeLayer(currentRoute); // Remove previous route
    }
    
    // Create a proper GeoJSON object
    const geojsonFeature = {
        "type": "Feature",
        "properties": {},
        "geometry": {
            "type": "LineString",
            "coordinates": routeGeometry
        }
    };
    
    currentRoute = L.geoJSON(geojsonFeature, {
        style: { color: '#60BA97', weight: 4 }
    }).addTo(map);

    // Fit map to show the entire route
    const bounds = L.latLngBounds([startLatLng, endLatLng]);
    map.fitBounds(bounds, { padding: [50, 50] });

    // Update route info with coordinates
    const startCoordinates = `${startLatLng.lat.toFixed(6)}, ${startLatLng.lng.toFixed(6)}`;
    const endCoordinates = `${endLatLng.lat.toFixed(6)}, ${endLatLng.lng.toFixed(6)}`;
    
    // Update the form fields with the route coordinates
    updateRouteFields(startCoordinates, endCoordinates);
    if (document.getElementById('distance')) {
        document.getElementById('distance').value = distance.toFixed(1);
    }
    
    if (document.getElementById('route-distance')) {
        document.getElementById('route-distance').textContent = distance.toFixed(1);
    }
    
    if (document.getElementById('route-duration')) {
        document.getElementById('route-duration').textContent = Math.round(duration);
    }

    // Show route info
    document.getElementById('route-info').classList.remove('d-none');
    
    // Hide finish drawing button once route is complete
    hideFinishDrawingButton();
    
    // Save route coordinates
    saveRouteCoordinates(routeGeometry);
}

// Calculate direct route when normal routing fails
function calculateDirectRoute(startLatLng, endLatLng) {
    const directLine = L.polyline([startLatLng, endLatLng], { color: '#60BA97', weight: 4 }).addTo(map);
    const distance = L.latLng(startLatLng).distanceTo(L.latLng(endLatLng)) / 1000; // Distance in kilometers
    const duration = distance * 5; // Assume 5 minutes per kilometer

    // Update route info with coordinates
    const startCoordinates = `${startLatLng.lat.toFixed(6)}, ${startLatLng.lng.toFixed(6)}`;
    const endCoordinates = `${endLatLng.lat.toFixed(6)}, ${endLatLng.lng.toFixed(6)}`;
    
    // Update the form fields with the route coordinates
    updateRouteFields(startCoordinates, endCoordinates);
    
    if (document.getElementById('distance')) {
        document.getElementById('distance').value = distance.toFixed(1);
    }
    
    if (document.getElementById('route-distance')) {
        document.getElementById('route-distance').textContent = distance.toFixed(1);
    }
    
    if (document.getElementById('route-duration')) {
        document.getElementById('route-duration').textContent = Math.round(duration);
    }
    // Show route info
    document.getElementById('route-info').classList.remove('d-none');

    // Store the direct line
    currentRoute = directLine;
    
    // Create a simple set of coordinates for a straight line
    const coordinates = [
        [startLatLng.lng, startLatLng.lat],
        [endLatLng.lng, endLatLng.lat]
    ];
    
    // Save the coordinates
    saveRouteCoordinates(coordinates);
}

// Optimize the current route
function optimizeRoute() {
    alert('L\'optimisation n\'est pas encore implémentée.');
}

/**
 * Finish Drawing Button Functions
 */

// Create the finish drawing button
function createFinishDrawingButton() {
    // Create a custom control button for Leaflet
    const finishDrawingButton = L.control({ position: 'bottomright' });
    
    finishDrawingButton.onAdd = function(map) {
        const button = L.DomUtil.create('button', 'finish-drawing-btn');
        button.innerHTML = 'Terminer le tracé';
        button.id = 'finish-drawing-btn';
        button.style.display = 'none'; // Hidden by default
        button.style.backgroundColor = '#60BA97';
        button.style.color = 'white';
        button.style.border = 'none';
        button.style.padding = '10px 15px';
        button.style.borderRadius = '4px';
        button.style.cursor = 'pointer';
        button.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        button.style.fontWeight = 'bold';
        
        // Add event listener
        L.DomEvent.on(button, 'click', function(e) {
            L.DomEvent.stopPropagation(e);
            finishDrawing();
        });
        
        return button;
    };
    
    finishDrawingButton.addTo(map);
}

// Show the finish drawing button
function showFinishDrawingButton() {
    const button = document.getElementById('finish-drawing-btn');
    if (button) {
        button.style.display = 'block';
    }
}

// Hide the finish drawing button
function hideFinishDrawingButton() {
    const button = document.getElementById('finish-drawing-btn');
    if (button) {
        button.style.display = 'none';
    }
}

// Finish the route drawing process
function finishDrawing() {
    // Check if we have both start and end points
    if (!startPoint) {
        alert('Veuillez d\'abord sélectionner un point de départ.');
        return;
    }
    
    // If we already have an end point and a route, just hide the button
    if (endPoint && currentRoute) {
        hideFinishDrawingButton();
        return;
    }
    
    // If we only have a start point, create an interim end point at current mouse position or map center
    if (!endPoint) {
        const mapCenter = map.getCenter();
        endPoint = mapCenter;
        
        // Check if end point is too close to start point
        if (L.latLng(startPoint).distanceTo(L.latLng(endPoint)) < 100) {
            // If too close, move the end point farther away
            const bearingDirection = Math.random() * 360; // Random direction
            const distance = 1000; // 1km away
            
            // Calculate new end point
            const startLatRad = startPoint.lat * Math.PI / 180;
            const startLngRad = startPoint.lng * Math.PI / 180;
            const bearingRad = bearingDirection * Math.PI / 180;
            const earthRadius = 6371000; // meters
            
            const distanceRad = distance / earthRadius;
            
            const endLatRad = Math.asin(
                Math.sin(startLatRad) * Math.cos(distanceRad) +
                Math.cos(startLatRad) * Math.sin(distanceRad) * Math.cos(bearingRad)
            );
            
            const endLngRad = startLngRad + Math.atan2(
                Math.sin(bearingRad) * Math.sin(distanceRad) * Math.cos(startLatRad),
                Math.cos(distanceRad) - Math.sin(startLatRad) * Math.sin(endLatRad)
            );
            
            endPoint = L.latLng(endLatRad * 180 / Math.PI, endLngRad * 180 / Math.PI);
        }
        }
        
        // Add marker for the end point
        addMarker(endPoint, 'Point d\'arrivée');
        
        // Calculate route between points
        calculateRoute(startPoint, endPoint);
        
        // Hide finish drawing button when route is complete
        hideFinishDrawingButton();
    }
// Helper function to update route fields with coordinates
function updateRouteFields(startCoordinates, endCoordinates) {
    // Update display fields
    if (document.getElementById('start_point_display')) {
        document.getElementById('start_point_display').value = startCoordinates;
    }
    
    if (document.getElementById('end_point_display')) {
        document.getElementById('end_point_display').value = endCoordinates;
    }
    
    // Update hidden form fields
    if (document.getElementById('start_coordinates')) {
        document.getElementById('start_coordinates').value = startCoordinates;
    }
    
    if (document.getElementById('end_coordinates')) {
        document.getElementById('end_coordinates').value = endCoordinates;
    }
}

// Reset route
function resetRoute() {
    // Clear the current route
    if (currentRoute) {
        map.removeLayer(currentRoute);
    }
    currentRoute = null;
    
    // Clear all markers
    clearMarkers();
    
    // Reset global variables
    startPoint = null;
    endPoint = null;
    
    // Hide finish drawing button
    hideFinishDrawingButton();
    
    // Remove any optimization status message
    const statusElement = document.getElementById('optimization-status');
    if (statusElement && statusElement.parentNode) {
        statusElement.parentNode.removeChild(statusElement);
    }
    
    // Reset form fields
    updateRouteFields('', '');
    
    // Reset other form fields
    if (document.getElementById('distance')) {
        document.getElementById('distance').value = '';
    }
    
    if (document.getElementById('route_coordinates')) {
        document.getElementById('route_coordinates').value = '';
    }
    
    if (document.getElementById('route_description')) {
        document.getElementById('route_description').value = '';
    }
    
    // Hide route info
    if (document.getElementById('route-info')) {
        document.getElementById('route-info').classList.add('d-none');
    }
}

// Save route coordinates to hidden input
function saveRouteCoordinates(routeGeometry) {
    let coordinates = [];
    
    try {
        // Handle different input formats
        if (Array.isArray(routeGeometry)) {
            if (routeGeometry.length > 0) {
                if (Array.isArray(routeGeometry[0])) {
                    // Format: [[lng, lat], [lng, lat], ...]
                    coordinates = routeGeometry.map(point => {
                        if (point.length >= 2) {
                            return {
                                lat: point[1],
                                lng: point[0]
                            };
                        }
                        throw new Error('Invalid coordinate point structure');
                    });
                } else if (typeof routeGeometry[0] === 'object' && 
                           'lat' in routeGeometry[0] && 
                           'lng' in routeGeometry[0]) {
                    // Format: [{lat, lng}, {lat, lng}, ...]
                    coordinates = routeGeometry;
                } else {
                    throw new Error('Unrecognized coordinate format');
                }
            }
        } else {
            throw new Error('Route geometry is not an array');
        }
    } catch (error) {
        console.error('Error processing route geometry:', error.message, routeGeometry);
        coordinates = [];
    }
    
    // Store the coordinates as JSON in the hidden input
    const routeCoordinatesElement = document.getElementById('route_coordinates');
    if (routeCoordinatesElement) {
        routeCoordinatesElement.value = JSON.stringify(coordinates);
    } else {
        console.error('Could not find route_coordinates element');
    }
}

/**
 * Form Validation
 */

// Setup form validation
function setupFormValidation() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            const routeCoords = document.getElementById('route_coordinates')?.value || '';
            const startCoordinates = document.getElementById('start_coordinates')?.value || '';
            const endCoordinates = document.getElementById('end_coordinates')?.value || '';
            const distance = document.getElementById('distance')?.value || '';

            if (!routeCoords || !startCoordinates || !endCoordinates || !distance) {
                event.preventDefault();
                alert('Veuillez tracer un itinéraire complet en sélectionnant deux points sur la carte avant de soumettre le formulaire.');
                
                // Highlight form issues
                if (!startCoordinates || !endCoordinates) {
                    document.getElementById('draw-route').style.backgroundColor = '#ff7700';
                    setTimeout(() => {
                        document.getElementById('draw-route').style.backgroundColor = '';
                    }, 2000);
                }
                
                return false;
            }

            return true;
        });
    }
}

// Show toast message
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Initialize map when the page loads
window.onload = initMap;
