// Fetch statistics from statistique.php when the page loads
document.addEventListener('DOMContentLoaded', () => {
    fetch('statistique.php')
        .then(response => response.json())
        .then(data => {
            window.stats = {
                ouvert: data.ouvert,
                resolu: data.resolu,
                autres: data.autres,
                types: data.types
            };
            renderCharts(); // Call the function to render charts
        })
        .catch(error => console.error('Error fetching stats:', error));
});

// Function to render Chart.js charts
function renderCharts() {
    // Status Chart (Pie chart for ouvert, resolu, autres)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Ouvert', 'Résolu', 'Autres'],
            datasets: [{
                data: [window.stats.ouvert, window.stats.resolu, window.stats.autres],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            }
        }
    });

    // Type Chart (Bar chart for types of problems)
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: window.stats.types.map(type => type.type),
            datasets: [{
                label: 'Nombre de réclamations',
                data: window.stats.types.map(type => type.count),
                backgroundColor: '#36A2EB'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// Existing searchReclamation function (if already in scripts.js)
function searchReclamation() {
    const searchValue = document.getElementById('search').value;
    // Add your search logic here
    console.log('Searching for:', searchValue);
}