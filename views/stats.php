<?php
session_start();

// Define the root path for consistent includes
define('ROOT_PATH', realpath(__DIR__ . '/..'));
require_once ROOT_PATH . '/config/database.php';

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Erreur: Impossible de se connecter à la base de données pour le moment.");
}

// Fetch statistics
$stmt_total = $pdo->query("SELECT COUNT(*) AS total FROM reclamations");
$total_reclamations = $stmt_total ? $stmt_total->fetch()['total'] : 0;

$stmt_ouvert = $pdo->query("SELECT COUNT(*) AS ouvert FROM reclamations WHERE statut = 'ouverte'");
$reclamations_ouvertes = $stmt_ouvert ? $stmt_ouvert->fetch()['ouvert'] : 0;

$stmt_resolu = $pdo->query("SELECT COUNT(*) AS resolu FROM reclamations WHERE statut = 'resolue'");
$reclamations_resolues = $stmt_resolu ? $stmt_resolu->fetch()['resolu'] : 0;

$stmt_type = $pdo->query("SELECT type_probleme AS type, COUNT(*) AS count FROM reclamations GROUP BY type_probleme");
$types = $stmt_type ? $stmt_type->fetchAll(PDO::FETCH_ASSOC) : [];

$total_reclamations = (int)$total_reclamations;
$reclamations_ouvertes = (int)$reclamations_ouvertes;
$reclamations_resolues = (int)$reclamations_resolues;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Green.tn</title>
    <link rel="icon" href="../image/ve.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #60BA97;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            width: 200px;
            background-color: #60BA97;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            padding-top: 20px;
            color: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar .logo img {
            width: 150px;
            height: auto;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            font-size: 1em;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
        }

        .sidebar ul li a:hover {
            background-color: #1b5e20;
            border-radius: 0 20px 20px 0;
        }

        .container {
            margin-left: 220px;
            width: calc(90% - 220px);
            max-width: 1200px;
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: #F9F5E8;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #4CAF50;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h3, .chart-container h4 {
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-around;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff;
            border: 1px solid #4CAF50;
            border-radius: 5px;
        }

        .stats canvas {
            width: 300px !important;
            height: 300px !important;
            max-width: 300px;
            max-height: 300px;
        }

        .chart-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .chart-container h4 {
            margin: 0 0 10px 0;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                padding-bottom: 20px;
            }

            .container {
                margin-left: 0;
                width: 90%;
                margin: 20px auto;
            }

            .stats {
                flex-direction: column;
                align-items: center;
            }

            .stats canvas {
                width: 100% !important;
                max-width: 250px;
                height: auto !important;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../image/ve.png" alt="Green.tn Logo">
        </div>
        <ul>
            <li><a href="../views/stats.php">🏠 Dashboard</a></li>
            <li><a href="">🚲 Reservation</a></li>
            <li><a href="../views/reclamations_utilisateur.php">📋 Reclamation</a></li>
            <li><a href="../views/liste_avis.php">⭐ Avis</a></li>
            <li><a href="../logout.php">🔓 Déconnexion</a></li>
        </ul>
    </div>
    <main>
        <div class="container">
            <h3>Statistiques des réclamations</h3>
            <div class="stats">
                <div class="chart-container">
                    <canvas id="statusChart" width="300" height="300"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="typeChart" width="300" height="300"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Data from PHP
        let autres_count = <?= $total_reclamations ?> - (<?= $reclamations_ouvertes ?> + <?= $reclamations_resolues ?>);
        autres_count = Math.max(0, autres_count);
        window.stats = {
            ouvert: <?= $reclamations_ouvertes ?>,
            resolu: <?= $reclamations_resolues ?>,
            autres: autres_count,
            types: <?= json_encode($types) ?>
        };
        console.log('window.stats:', window.stats);

        // Render charts
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.stats) {
                console.error('window.stats is not defined.');
                return;
            }

            // Status Chart (Pie chart)
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            if (!statusCtx) {
                console.error('statusChart canvas not found');
                return;
            }
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Ouvert', 'Résolu', 'Autres'],
                    datasets: [{
                        data: [window.stats.ouvert, window.stats.resolu, window.stats.autres],
                        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Répartition par Statut' }
                    }
                }
            });

            // Type Chart (Bar chart)
            const typeCtx = document.getElementById('typeChart').getContext('2d');
            if (!typeCtx) {
                console.error('typeChart canvas not found');
                return;
            }
            new Chart(typeCtx, {
                type: 'bar',
                data: {
                    labels: window.stats.types.map(type => type.type || 'Inconnu'),
                    datasets: [{
                        label: 'Nombre de réclamations',
                        data: window.stats.types.map(type => type.count || 0),
                        backgroundColor: '#36A2EB',
                        borderColor: '#2980b9',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Nombre' } },
                        x: { title: { display: true, text: 'Type de Problème' } }
                    },
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Réclamations par Type' }
                    }
                }
            });
        });
    </script>
</body>
</html>