<?php
session_start();

// Vérifie si l'utilisateur est connecté et admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Connexion à la base de données
$pdo = new PDO("mysql:host=localhost;dbname=green_tn", "root", "");

// Statistiques
$stat = $pdo->query("SELECT statut, COUNT(*) as total FROM reclamations GROUP BY statut")->fetchAll(PDO::FETCH_ASSOC);

// Liste des réclamations
$reclamations = $pdo->query("SELECT * FROM reclamations ORDER BY date_creation DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f6fa;
            padding: 20px;
        }

        h1 {
            color: #2C3E50;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            flex: 1;
            text-align: center;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
        }

        th {
            background-color: #3498db;
            color: white;
        }

        a {
            text-decoration: none;
            color: #3498db;
        }

        .nav {
            margin-bottom: 20px;
        }

        .nav a {
            margin-right: 15px;
        }
    </style>
</head>
<body>

    <div class="nav">
        <a href="index.php">🏠 Accueil</a>
        <a href="logout.php">🔓 Déconnexion</a>
    </div>

    <h1>📊 Tableau de bord - Admin</h1>

    <div class="stats">
        <?php foreach ($stat as $s): ?>
            <div class="stat">
                <?= ucfirst($s['statut']) ?> : <?= $s['total'] ?>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>📋 Toutes les réclamations</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Lieu</th>
            <th>Type</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($reclamations as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['titre']) ?></td>
                <td><?= htmlspecialchars($r['lieu']) ?></td>
                <td><?= htmlspecialchars($r['type_probleme']) ?></td>
                <td><?= htmlspecialchars($r['statut']) ?></td>
                <td><?= $r['date_creation'] ?></td>
                <td>
                    <a href="voir_reclamation.php?id=<?= $r['id'] ?>">Voir</a> |
                    <a href="modifier_reclamation.php?id=<?= $r['id'] ?>">Modifier</a> |
                    <a href="supprimer_reclamation.php?id=<?= $r['id'] ?>" onclick="return confirm('Supprimer ?')">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>
