    document.addEventListener('DOMContentLoaded', () => {
    const renderStationCharts = () => {
        // Status Chart (Pie)
        const statusCtx = document.getElementById('statusChart')?.getContext('2d');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: window.statusStats.map(stat => stat.status === 'active' ? 'Actif' : 'Inactif'),
                    datasets: [{
                        data: window.statusStats.map(stat => stat.count),
                        backgroundColor: ['#28a745', '#dc3545'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Répartition par Statut' }
                    }
                }
            });
        }

        // Location Chart (Bar)
        const locationCtx = document.getElementById('locationChart')?.getContext('2d');
        if (locationCtx) {
            new Chart(locationCtx, {
                type: 'bar',
                data: {
                    labels: window.locationStats.map(stat => stat.city || 'Inconnu'),
                    datasets: [{
                        label: 'Nombre de Stations',
                        data: window.locationStats.map(stat => stat.count),
                        backgroundColor: '#007bff',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Stations par Ville' }
                    },
                    scales: {
                        y: { beginAtZero: true, precision: 0 }
                    }
                }
            });
        }
    };

    const renderTrajetCharts = () => {
        // CO2 Saved Chart (Bar)
        const co2Ctx = document.getElementById('co2Chart')?.getContext('2d');
        if (co2Ctx) {
            new Chart(co2Ctx, {
                type: 'bar',
                data: {
                    labels: window.trajetCo2.map(trajet => trajet.description),
                    datasets: [{
                        label: 'CO₂ Économisé (g)',
                        data: window.trajetCo2.map(trajet => trajet.co2_saved),
                        backgroundColor: '#28a745',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'CO₂ Économisé par Trajet' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Energy and Fuel Chart (Bar)
        const energyCtx = document.getElementById('energyChart')?.getContext('2d');
        if (energyCtx) {
            new Chart(energyCtx, {
                type: 'bar',
                data: {
                    labels: window.trajetEnergy.map(trajet => trajet.description),
                    datasets: [
                        {
                            label: 'Énergie Batterie (Wh)',
                            data: window.trajetEnergy.map(trajet => trajet.battery_energy),
                            backgroundColor: '#007bff',
                        },
                        {
                            label: 'Carburant Économisé (L)',
                            data: window.trajetEnergy.map(trajet => trajet.fuel_saved),
                            backgroundColor: '#dc3545',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Énergie et Carburant par Trajet' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Distance Chart (Line)
        const distanceCtx = document.getElementById('distanceChart')?.getContext('2d');
        if (distanceCtx) {
            new Chart(distanceCtx, {
                type: 'line',
                data: {
                    labels: window.trajetDistances.map(trajet => `Trajet ${trajet.id}`),
                    datasets: [{
                        label: 'Distance (km)',
                        data: window.trajetDistances.map(trajet => trajet.distance),
                        borderColor: '#007bff',
                        fill: false,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Distance des Trajets' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    };

    // Render charts when modals are shown
    const stationsModal = document.getElementById('stationsStatsModal');
    if (stationsModal) {
        stationsModal.addEventListener('shown.bs.modal', renderStationCharts);
    }

    const trajetsModal = document.getElementById('trajetsStatsModal');
    if (trajetsModal) {
        trajetsModal.addEventListener('shown.bs.modal', renderTrajetCharts);
    }
});