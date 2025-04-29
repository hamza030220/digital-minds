// Configure Leaflet to use CDN-hosted marker icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png'
});

// Initialize map with existing location if available
const map = L.map('map').setView([36.8065, 10.1815], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

let marker = null;
const locationInput = document.getElementById('location');

// Set initial marker if location exists
if (locationInput.value) {
    const [lat, lng] = locationInput.value.split(',').map(Number);
    marker = L.marker([lat, lng]).addTo(map)
        .bindPopup(`Emplacement actuel: ${locationInput.value}`)
        .openPopup();
    map.setView([lat, lng], 13);
}

// Handle map click
map.on('click', function(e) {
    const lat = e.latlng.lat.toFixed(6);
    const lng = e.latlng.lng.toFixed(6);
    const coordinates = `${lat},${lng}`;
    locationInput.value = coordinates;
    if (marker) {
        map.removeLayer(marker);
    }
    marker = L.marker(e.latlng).addTo(map)
        .bindPopup(`Nouvel emplacement: ${coordinates}`)
        .openPopup();
});

// Add JS validation (disable HTML5 validation)
document.querySelector('form').addEventListener('submit', function(e) {
    let valid = true;
    const nameInput = document.querySelector('#name');
    const locationInput = document.querySelector('#location');
    // Remove previous validation states
    nameInput.classList.remove('is-invalid');
    locationInput.classList.remove('is-invalid');
    if (nameInput.value.trim() === '') {
        e.preventDefault();
        valid = false;
        nameInput.classList.add('is-invalid');
        showErrorMessage('Le nom de la station est requis');
    }
    if (locationInput.value.trim() === '') {
        e.preventDefault();
        valid = false;
        locationInput.classList.add('is-invalid');
        showErrorMessage('L\'emplacement de la station est requis');
    }
});

// Show error message
function showErrorMessage(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger fade show';
    errorDiv.innerHTML = `\n        <strong>Erreur!</strong> ${message}\n        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>\n    `;
    document.querySelector('.card-body').prepend(errorDiv);
}

// Real-time validation
const inputs = document.querySelectorAll('input, textarea');
inputs.forEach(input => {
    input.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    });
});