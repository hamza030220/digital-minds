// Trajets Edit Page JS
// --- Map and Form Logic ---
const map = L.map('map').setView([36.8065, 10.1815], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
}).addTo(map);
async function getReverseGeocode(latlng, fieldName) {
  try {
    const response = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`);
    const result = response.data;
    document.getElementById(fieldName).value = result.display_name;
  } catch (error) {
    console.error('Error fetching reverse geocode data:', error);
  }
}
let drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);
let startMarker = null;
let endMarker = null;
let routeLine = null;
const drawControl = new L.Control.Draw({
    draw: {
        polyline: { shapeOptions: { color: '#60BA97', weight: 5 } },
        polygon: false, circle: false, rectangle: false, marker: false, circlemarker: false
    },
    edit: { featureGroup: drawnItems, remove: true }
});
map.addControl(drawControl);
function updateRouteCoordinates() {
    let coordinates = [];
    drawnItems.eachLayer(function(layer) {
        if (layer instanceof L.Polyline) {
            const latLngs = layer.getLatLngs();
            for (let i = 0; i < latLngs.length; i++) {
                coordinates.push({ lat: latLngs[i].lat, lng: latLngs[i].lng });
            }
        }
    });
    if (coordinates.length > 0) {
        document.getElementById('route_coordinates').value = JSON.stringify(coordinates);
        if (document.getElementById('distance').value === '') {
            calculateDistance(coordinates);
        }
    } else {
        document.getElementById('route_coordinates').value = '';
    }
}
function calculateDistance(coordinates) {
    if (coordinates.length < 2) return;
    let totalDistance = 0;
    for (let i = 1; i < coordinates.length; i++) {
        const point1 = L.latLng(coordinates[i-1].lat, coordinates[i-1].lng);
        const point2 = L.latLng(coordinates[i].lat, coordinates[i].lng);
        totalDistance += point1.distanceTo(point2);
    }
    const distanceKm = (totalDistance / 1000).toFixed(2);
    document.getElementById('distance').value = distanceKm;
}
const startIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
});
const endIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
});
let currentMode = null;
function placeMarker(latlng, markerType) {
   if (markerType === 'start' && startMarker) { map.removeLayer(startMarker); }
   else if (markerType === 'end' && endMarker) { map.removeLayer(endMarker); }
   const pointName = markerType === 'start'
      ? document.getElementById('start_point_name').value || 'Point de départ'
      : document.getElementById('end_point_name').value || 'Point d\'arrivée';
   const marker = L.marker(latlng, {
      icon: markerType === 'start' ? startIcon : endIcon,
      draggable: true
   }).addTo(map);
   marker.bindPopup(`<b>${markerType === 'start' ? 'Départ' : 'Arrivée'}:</b> ${pointName}`);
   if (markerType === 'start') {
      startMarker = marker;
      document.getElementById('start_point').value = `${latlng.lat},${latlng.lng}`;
      document.getElementById('start_point_info').textContent = `Point placé: ${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
      getReverseGeocode(latlng, 'start_point_name');
   } else {
      endMarker = marker;
      document.getElementById('end_point').value = `${latlng.lat},${latlng.lng}`;
      document.getElementById('end_point_info').textContent = `Point placé: ${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
      getReverseGeocode(latlng, 'end_point_name');
   }
   marker.on('dragend', function(event) {
      const marker = event.target;
      const position = marker.getLatLng();
      if (markerType === 'start') {
          document.getElementById('start_point').value = `${position.lat},${position.lng}`;
          document.getElementById('start_point_info').textContent = `Point placé: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`;
          getReverseGeocode(position, 'start_point_name');
      } else {
          document.getElementById('end_point').value = `${position.lat},${position.lng}`;
          document.getElementById('end_point_info').textContent = `Point placé: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`;
          getReverseGeocode(position, 'end_point_name');
      }
      const pointName = markerType === 'start'
        ? document.getElementById('start_point_name').value || 'Point de départ'
        : document.getElementById('end_point_name').value || 'Point d\'arrivée';
      marker.setPopupContent(`<b>${markerType === 'start' ? 'Départ' : 'Arrivée'}:</b> ${pointName}`);
   });
   if (startMarker && endMarker) {
      const bounds = L.latLngBounds([startMarker.getLatLng(), endMarker.getLatLng()]);
      map.fitBounds(bounds, { padding: [50, 50] });
   }
   return marker;
}
function setMode(mode) {
    currentMode = mode;
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
map.on('click', function(e) {
    if (currentMode === 'start') {
        placeMarker(e.latlng, 'start');
    } else if (currentMode === 'end') {
        placeMarker(e.latlng, 'end');
    }
});
map.on(L.Draw.Event.CREATED, function(event) {
    drawnItems.clearLayers();
    const layer = event.layer;
    drawnItems.addLayer(layer);
    updateRouteCoordinates();
});
map.on(L.Draw.Event.EDITED, function() { updateRouteCoordinates(); });
map.on(L.Draw.Event.DELETED, function() { updateRouteCoordinates(); });
document.getElementById('start_point_btn').addEventListener('click', function() { setMode('start'); });
document.getElementById('end_point_btn').addEventListener('click', function() { setMode('end'); });
document.getElementById('draw_route_btn').addEventListener('click', function() { setMode('draw'); new L.Draw.Polyline(map, drawControl.options.polyline).enable(); });
document.getElementById('start_point_name').addEventListener('input', function() { if (startMarker) { const pointName = this.value || 'Point de départ'; startMarker.setPopupContent(`<b>Départ:</b> ${pointName}`); } });
document.getElementById('end_point_name').addEventListener('input', function() { if (endMarker) { const pointName = this.value || 'Point d\'arrivée'; endMarker.setPopupContent(`<b>Arrivée:</b> ${pointName}`); } });
document.getElementById('trajetForm').addEventListener('submit', function(e) {
    if (document.getElementById('start_point').value === '') {
        e.preventDefault(); alert('Veuillez placer le point de départ sur la carte.'); return false;
    }
    if (document.getElementById('end_point').value === '') {
        e.preventDefault(); alert('Veuillez placer le point d\'arrivée sur la carte.'); return false;
    }
    if (document.getElementById('route_coordinates').value === '') {
        e.preventDefault(); alert('Veuillez tracer l\'itinéraire sur la carte avant de sauvegarder.'); return false;
    }
    const distance = parseFloat(document.getElementById('distance').value);
    if (isNaN(distance) || distance <= 0) {
        e.preventDefault(); alert('Veuillez saisir une distance valide (supérieure à 0).'); return false;
    }
    return true;
});
const savedStartPoint = document.getElementById('start_point').value;
if (savedStartPoint) {
    try {
        const [lat, lng] = savedStartPoint.split(',').map(coord => parseFloat(coord.trim()));
        if (!isNaN(lat) && !isNaN(lng)) { placeMarker(L.latLng(lat, lng), 'start'); }
    } catch (e) { console.error('Error parsing start point coordinates:', e); }
}
const savedEndPoint = document.getElementById('end_point').value;
if (savedEndPoint) {
    try {
        const [lat, lng] = savedEndPoint.split(',').map(coord => parseFloat(coord.trim()));
        if (!isNaN(lat) && !isNaN(lng)) { placeMarker(L.latLng(lat, lng), 'end'); }
    } catch (e) { console.error('Error parsing end point coordinates:', e); }
}
const savedCoordinates = document.getElementById('route_coordinates').value;
if (savedCoordinates) {
    try {
        const coordinates = JSON.parse(savedCoordinates);
        if (coordinates.length > 1) {
            const points = coordinates.map(coord => [coord.lat, coord.lng]);
            const polyline = L.polyline(points, { color: '#60BA97', weight: 5 });
            drawnItems.addLayer(polyline);
            map.fitBounds(polyline.getBounds(), { padding: [50, 50] });
        }
    } catch (e) { console.error('Error parsing saved coordinates:', e); }
}
setMode('draw');
document.getElementById('distance').addEventListener('input', function() {
    const distance = parseFloat(this.value) || 0;
    document.getElementById('co2_saved').value = distance > 0 ? (distance * 160).toFixed(2) : '';
    document.getElementById('battery_energy').value = distance > 0 ? (distance * 5.6).toFixed(2) : '';
    document.getElementById('fuel_saved').value = distance > 0 ? (distance * 0.075).toFixed(3) : '';
});