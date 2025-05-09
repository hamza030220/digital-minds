// Stock Stats Chart
function initializeStockChart(stockData) {
    const ctx = document.getElementById('stockStatsChart').getContext('2d');
    const categories = [...new Set(stockData.map(item => item.category))];
    const quantities = categories.map(category => 
    stockData.filter(item => item.category === category)
    .reduce((sum, item) => sum + parseInt(item.quantity), 0)
    );
   
    new Chart(ctx, {
    type: 'bar',
    data: {
    labels: categories,
    datasets: [{
    label: 'Quantité en Stock',
    data: quantities,
    backgroundColor: 'rgba(46, 125, 50, 0.6)',
    borderColor: 'rgba(46, 125, 50, 1)',
    borderWidth: 1
    }]
    },
    options: {
    responsive: true,
    scales: {
    y: {
    beginAtZero: true,
    title: {
    display: true,
    text: 'Quantité'
    }
    },
    x: {
    title: {
    display: true,
    text: 'Catégorie'
    }
    }
    }
    }
    });
   }
   
   // Repairs Stats Chart
   function initializeRepairsChart(repairsData) {
    const ctx = document.getElementById('repairsStatsChart').getContext('2d');
    const statuses = ['En attente', 'En cours', 'Terminé'];
    const counts = statuses.map(status => 
    repairsData.filter(repair => repair.status === status).length
    );
   
    new Chart(ctx, {
    type: 'pie',
    data: {
    labels: statuses,
    datasets: [{
    label: 'Réparations par Statut',
    data: counts,
    backgroundColor: [
    'rgba(255, 99, 132, 0.6)',
    'rgba(54, 162, 235, 0.6)',
    'rgba(46, 125, 50, 0.6)'
    ],
    borderColor: [
    'rgba(255, 99, 132, 1)',
    'rgba(54, 162, 235, 1)',
    'rgba(46, 125, 50, 1)'
    ],
    borderWidth: 1
    }]
    },
    options: {
    responsive: true,
    plugins: {
    legend: {
    position: 'top',
    },
    title: {
    display: true,
    text: 'Répartition des Réparations'
    }
    }
    }
    });
   
    document.getElementById('repairsTotal').textContent = `Total des réparations: ${repairsData.length}`;
   }
   
   // Fetch data for charts
   document.addEventListener('DOMContentLoaded', () => {
    // Fetch stock data
    fetch('../controllers/StockController.php?action=get_all')
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of stock items');
            if (data.length === 0) {
                document.getElementById('stockStatsError').textContent = 'Aucun article en stock';
                return;
            }
            initializeStockChart(data);
        })
        .catch(error => {
            console.error('Error fetching stock data:', error);
            document.getElementById('stockStatsError').textContent = `Erreur: ${error.message}`;
        });

    // Fetch repairs data
    fetch('../controllers/RepairController.php?action=get_all')
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of repairs');
            if (data.length === 0) {
                document.getElementById('repairsStatsError').textContent = 'Aucune réparation trouvée';
                document.getElementById('repairsTotal').textContent = 'Total des réparations: 0';
                return;
            }
            initializeRepairsChart(data);
        })
        .catch(error => {
            console.error('Error fetching repairs data:', error);
            document.getElementById('repairsStatsError').textContent = `Erreur: ${error.message}`;
        });
});