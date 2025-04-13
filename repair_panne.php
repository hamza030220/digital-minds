<?php
// Connexion à la base de données
$conn = new PDO("mysql:host=localhost;dbname=greentn", "root", "");

// Récupération de toutes les pannes
$sql = "SELECT * FROM pannes";
$stmt = $conn->query($sql);
$pannes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Pannes</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #e9f5ec;
        }

        .sidebar {
            height: 100vh;
            width: 180px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2e7d32;
            padding-top: 20px;
            padding-left: 20px;
        }

        .sidebar .logo-sidebar {
            width: 60%;
            margin: 0 auto;
            display: block;
            padding-bottom: 20px;
            border-bottom: 1px solid #388e3c;
        }

        .sidebar a {
            padding: 12px 20px;
            text-decoration: none;
            font-size: 15px;
            color: #d0f0d6;
            display: block;
        }

        .sidebar a:hover,
        .sidebar .active {
            background-color: #1b5e20;
            color: white;
        }

        .main-content {
            margin-left: 240px;
            padding: 40px;
            background-color: #ffffff;
            min-height: 100vh;
        }

        .main-content h2 {
            color: #2e7d32;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 30px auto;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            overflow: hidden;
            font-size: 14px;
        }

        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #2e7d32;
            color: white;
        }

        tr:hover {
            background-color: #f1f8f4;
        }

        .status {
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: bold;
            display: inline-block;
        }

        .status.en_cours {
            background-color: #fff3cd;
            color: #856404;
        }

        .status.resolu {
            background-color: #d4edda;
            color: #155724;
        }

        .status.en_attente {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <img src="logo.jpg" alt="Logo EcoPedal" class="logo-sidebar">
    <a href="?page=accueil">🏠 Accueil</a>
    <a href="?page=gestion_utilisateurs">👤 Gestion des utilisateurs</a>
    <a href="?page=reservations">📅 Réservations</a>
    <a href="?page=pannes" class="active">🔧 Pannes</a>
    <a href="?page=velos_batteries">🚲 Vélos & Batteries</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>📋 Liste des Pannes Déclarées</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Description</th>
            <th>Date de déclaration</th>
            <th>Status</th>
        </tr>
        <?php foreach ($pannes as $panne): ?>
            <tr>
                <td><?= htmlspecialchars($panne['id']) ?></td>
                <td><?= htmlspecialchars($panne['description']) ?></td>
                <td><?= htmlspecialchars($panne['date_declaration']) ?></td>
                <td>
                    <?php
                        $status = strtolower($panne['status']);
                        $class = 'status ';
                        if ($status === 'en cours') $class .= 'en_cours';
                        elseif ($status === 'résolu' || $status === 'resolu') $class .= 'resolu';
                        else $class .= 'en_attente';
                    ?>
                    <span class="<?= $class ?>"><?= htmlspecialchars($panne['status']) ?></span>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
