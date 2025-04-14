// Graphique 1 : Statut des réclamations
window.addEventListener("DOMContentLoaded", () => {
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: ['Ouvertes', 'Résolues', 'Autres'],
            datasets: [{
                data: [stats.ouvert, stats.resolu, stats.autres],
                backgroundColor: ['#ff9999', '#66b3ff', '#99ff99'],
                borderColor: ['#ff6666', '#3399ff', '#66cc66'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.label + ': ' + tooltipItem.raw + ' réclamation(s)';
                        }
                    }
                }
            }
        }
    });

    // Graphique 2 : Type de panne
    const ctxType = document.getElementById('typeChart').getContext('2d');
    const typesLabels = stats.types.map(t => t.type);
    const typesData = stats.types.map(t => t.count);

    new Chart(ctxType, {
        type: 'pie',
        data: {
            labels: typesLabels,
            datasets: [{
                data: typesData,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#8AFFC1',
                    '#FFA07A', '#BA55D3', '#00CED1', '#A52A2A'
                ],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.label + ': ' + tooltipItem.raw + ' réclamation(s)';
                        }
                    }
                }
            }
        }
    });
});
