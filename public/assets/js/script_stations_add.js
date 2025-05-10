// Initialize Leaflet map with proper marker handling
document.addEventListener('DOMContentLoaded', function() {
    // Configure Leaflet icon URLs
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png'
    });

    // Map initialization
    const map = L.map('map').setView([36.8065, 10.1815], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker = null;

    // Map click handler
    map.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);
        document.getElementById('location').value = `${lat},${lng}`;

        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng)
            .addTo(map)
            .bindPopup(`Emplacement sélectionné: ${lat},${lng}`)
            .openPopup();
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const inputs = {
            name: document.getElementById('name'),
            location: document.getElementById('location')
        };

        if (!inputs.name.value.trim() || !inputs.location.value.trim()) {
            e.preventDefault();
            inputs.name.classList.toggle('is-invalid', !inputs.name.value.trim());
            inputs.location.classList.toggle('is-invalid', !inputs.location.value.trim());
            showErrorMessage('Veuillez remplir tous les champs requis');
        }
    });

    // Real-time input validation
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.toggle('is-valid', !!input.value.trim());
            input.classList.toggle('is-invalid', !input.value.trim());
        });
    });
});

// Helper functions
function showErrorMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.card-body').prepend(alert);
}