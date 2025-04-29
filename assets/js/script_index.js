// Chart.js logic and modal/slider logic for dashboard and trajets statistics
// Uses window.statusStats, window.locationStats, window.trajetCo2, window.trajetEnergy, window.trajetDistances

document.addEventListener('DOMContentLoaded', function() {
    // Modal logic
    const statModal = document.getElementById('statModal');
    const showStatsBtn = document.getElementById('showStatsBtn');
    const closeStatModal = document.getElementById('closeStatModal');

    showStatsBtn.addEventListener('click', () => {
        statModal.classList.add('show');
        setTimeout(() => {
            drawCharts();
            showSlide(0, null);
        }, 100);
    });
    closeStatModal.addEventListener('click', () => {
        statModal.classList.remove('show');
    });
    statModal.addEventListener('click', (e) => {
        if (e.target === statModal) statModal.classList.remove('show');
    });

    // Chart.js logic
    let pieChart, barChart;
    function drawCharts() {
        const statusStats = window.statusStats || [];
        const locationStats = window.locationStats || [];
        // Pie chart: Active vs Inactive
        const pieLabels = statusStats.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1));
        const pieData = statusStats.map(s => s.count);
        const pieColors = ['#60BA97', '#f44336', '#ffc107', '#2196f3'];
        if (pieChart) pieChart.destroy();
        pieChart = new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{ data: pieData, backgroundColor: pieColors, borderWidth: 2 }]
            },
            options: {
                animation: { animateScale: true },
                plugins: { legend: { display: true, position: 'bottom' } }
            }
        });
        // Bar chart: Stations per city
        const barLabels = locationStats.map(l => l.city);
        const barData = locationStats.map(l => l.count);
        if (barChart) barChart.destroy();
        barChart = new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{ label: 'Nombre de stations', data: barData, backgroundColor: '#60BA97', borderRadius: 6, maxBarThickness: 32 }]
            },
            options: {
                animation: { duration: 900, easing: 'easeOutBounce' },
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#198754', font: { weight: 'bold' } } },
                    y: { beginAtZero: true }
                }
            }
        });
    }
    // Slider logic
    const slides = [
        document.getElementById('slide-0'),
        document.getElementById('slide-1')
    ];
    const slideLeftBtn = document.getElementById('slideLeft');
    const slideRightBtn = document.getElementById('slideRight');
    const chartTitle = document.getElementById('chartTitle');
    let currentSlide = 0;
    function showSlide(index, direction) {
        slides.forEach((slide, i) => {
            slide.style.display = 'none';
            slide.classList.remove('slide-in-left', 'slide-in-right', 'slide-active');
        });
        if (direction === 'left') {
            slides[index].classList.add('slide-in-left');
            setTimeout(() => {
                slides[index].classList.remove('slide-in-left');
                slides[index].classList.add('slide-active');
            }, 10);
        } else if (direction === 'right') {
            slides[index].classList.add('slide-in-right');
            setTimeout(() => {
                slides[index].classList.remove('slide-in-right');
                slides[index].classList.add('slide-active');
            }, 10);
        } else {
            slides[index].classList.add('slide-active');
        }
        slides[index].style.display = 'flex';
        slideLeftBtn.style.display = index === 0 ? 'none' : '';
        slideRightBtn.style.display = index === slides.length - 1 ? 'none' : '';
        if (index === 0) {
            chartTitle.innerHTML = '<i class="bi bi-pie-chart-fill"></i> Actives vs Inactives';
        } else {
            chartTitle.innerHTML = '<i class="bi bi-bar-chart-fill"></i> Stations par emplacement';
        }
        currentSlide = index;
    }
    slideLeftBtn.addEventListener('click', () => {
        if (currentSlide > 0) showSlide(currentSlide - 1, 'left');
    });
    slideRightBtn.addEventListener('click', () => {
        if (currentSlide < slides.length - 1) showSlide(currentSlide + 1, 'right');
    });

    // --- Trajets statistics ---
    const trajetStatModal = document.getElementById('trajetStatModal');
    const showTrajetStatsBtn = document.getElementById('showTrajetStatsBtn');
    const closeTrajetStatModal = document.getElementById('closeTrajetStatModal');
    showTrajetStatsBtn.addEventListener('click', () => {
        trajetStatModal.classList.add('show');
        setTimeout(() => {
            drawTrajetCharts();
            showTrajetSlide(0, null);
        }, 100);
    });
    closeTrajetStatModal.addEventListener('click', () => {
        trajetStatModal.classList.remove('show');
    });
    trajetStatModal.addEventListener('click', (e) => {
        if (e.target === trajetStatModal) trajetStatModal.classList.remove('show');
    });
    let trajetCo2Chart, trajetEnergyChart, trajetDistanceChart;
    function drawTrajetCharts() {
        const trajetCo2 = window.trajetCo2 || [];
        const trajetEnergy = window.trajetEnergy || [];
        const trajetDistances = window.trajetDistances || [];
        // 1. Bar chart: CO2 économisé par trajet
        const co2Labels = trajetCo2.map(t => t.description || "Trajet " + t.id);
        const co2Data = trajetCo2.map(t => t.co2_saved);
        if (trajetCo2Chart) trajetCo2Chart.destroy();
        trajetCo2Chart = new Chart(document.getElementById('trajetCo2Chart'), {
            type: 'bar',
            data: {
                labels: co2Labels,
                datasets: [{ label: 'CO₂ économisé (kg)', data: co2Data, backgroundColor: '#60BA97', borderRadius: 6, maxBarThickness: 32 }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#198754', font: { weight: 'bold' }, autoSkip: false, maxRotation: 45, minRotation: 0 } },
                    y: { beginAtZero: true }
                }
            }
        });
        // 2. Stacked bar chart: Consommation d’énergie par trajet
        const energyLabels = trajetEnergy.map(t => t.description || "Trajet " + t.id);
        const batteryData = trajetEnergy.map(t => t.battery_energy);
        const fuelData = trajetEnergy.map(t => t.fuel_saved);
        if (trajetEnergyChart) trajetEnergyChart.destroy();
        trajetEnergyChart = new Chart(document.getElementById('trajetEnergyChart'), {
            type: 'bar',
            data: {
                labels: energyLabels,
                datasets: [
                    { label: 'Batterie (kWh)', data: batteryData, backgroundColor: '#2196f3' },
                    { label: 'Carburant (L)', data: fuelData, backgroundColor: '#ffc107' }
                ]
            },
            options: {
                plugins: { legend: { display: true, position: 'bottom' } },
                responsive: true,
                scales: {
                    x: { stacked: true, ticks: { color: '#198754', font: { weight: 'bold' }, autoSkip: false, maxRotation: 45, minRotation: 0 } },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
        // 3. Pie chart: Répartition des distances
        const ranges = [
            { label: '<5m', min: 0, max: 0.5 },
            { label: '500m-1km', min: 0.5, max: 1 },
            { label: '1km-2km', min: 1, max: 2 },
            { label: '>2km', min: 2, max: Infinity }
        ];
        const rangeCounts = [0, 0, 0, 0];
        trajetDistances.forEach(t => {
            const d = parseFloat(t.distance);
            if (d < 0.5) rangeCounts[0]++;
            else if (d < 1) rangeCounts[1]++;
            else if (d < 2) rangeCounts[2]++;
            else rangeCounts[3]++;
        });
        if (trajetDistanceChart) trajetDistanceChart.destroy();
        trajetDistanceChart = new Chart(document.getElementById('trajetDistanceChart'), {
            type: 'pie',
            data: {
                labels: ranges.map(r => r.label),
                datasets: [{ data: rangeCounts, backgroundColor: ['#60BA97', '#2196f3', '#ffc107', '#f44336'], borderWidth: 2 }]
            },
            options: { plugins: { legend: { display: true, position: 'bottom' } } }
        });
    }
    // Slider logic for trajets
    const trajetSlides = [
        document.getElementById('trajet-slide-0'),
        document.getElementById('trajet-slide-1'),
        document.getElementById('trajet-slide-2')
    ];
    const trajetSlideLeftBtn = document.getElementById('trajetSlideLeft');
    const trajetSlideRightBtn = document.getElementById('trajetSlideRight');
    const trajetChartTitle = document.getElementById('trajetChartTitle');
    let currentTrajetSlide = 0;
    function showTrajetSlide(index, direction) {
        trajetSlides.forEach((slide, i) => {
            slide.style.display = 'none';
            slide.classList.remove('slide-in-left', 'slide-in-right', 'slide-active');
        });
        if (direction === 'left') {
            trajetSlides[index].classList.add('slide-in-left');
            setTimeout(() => {
                trajetSlides[index].classList.remove('slide-in-left');
                trajetSlides[index].classList.add('slide-active');
            }, 10);
        } else if (direction === 'right') {
            trajetSlides[index].classList.add('slide-in-right');
            setTimeout(() => {
                trajetSlides[index].classList.remove('slide-in-right');
                trajetSlides[index].classList.add('slide-active');
            }, 10);
        } else {
            trajetSlides[index].classList.add('slide-active');
        }
        trajetSlides[index].style.display = 'flex';
        trajetSlideLeftBtn.style.display = index === 0 ? 'none' : '';
        trajetSlideRightBtn.style.display = index === trajetSlides.length - 1 ? 'none' : '';
        if (index === 0) {
            trajetChartTitle.innerHTML = '<i class="bi bi-bar-chart-fill"></i> CO₂ économisé par trajet';
        } else if (index === 1) {
            trajetChartTitle.innerHTML = '<i class="bi bi-bar-chart-fill"></i> Consommation d’énergie par trajet';
        } else {
            trajetChartTitle.innerHTML = '<i class="bi bi-pie-chart-fill"></i> Répartition des distances';
        }
        currentTrajetSlide = index;
    }
    trajetSlideLeftBtn.addEventListener('click', () => {
        if (currentTrajetSlide > 0) showTrajetSlide(currentTrajetSlide - 1, 'left');
    });
    trajetSlideRightBtn.addEventListener('click', () => {
        if (currentTrajetSlide < trajetSlides.length - 1) showTrajetSlide(currentTrajetSlide + 1, 'right');
    });
});