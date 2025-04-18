<?php
session_start();

// Vérifie si l'utilisateur est connecté et admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php"); // Redirect non-admin users
    exit(); // Stop script execution
}

// --- Modification Start ---

// 1. Include the Reclamation model. 
//    Make sure the path is correct relative to this admin script file.
//    If Reclamation.php is in the same directory, 'Reclamation.php' is fine.
//    If it's in a 'models' or 'classes' directory, use 'models/Reclamation.php' or 'classes/Reclamation.php'.
//    This file already includes 'config/database.php' for you.
require_once './models/Reclamation.php'; // Adjust path if needed

// 2. Instantiate the Reclamation model. 
//    The constructor automatically handles the database connection via the Database class.
$reclamationModel = new Reclamation();

// 3. Get statistics using the model method
$stat = $reclamationModel->getStatistiquesParStatut();

// 4. Get all reclamations using the model method
$reclamations = $reclamationModel->getToutesLesReclamations();

// --- Modification End ---

// The direct PDO connection below is no longer needed and should be removed or commented out:
// $pdo = new PDO("mysql:host=localhost;dbname=green_tn", "root", ""); // <<< REMOVE THIS LINE
// The direct queries below are also replaced by the model calls above:
// $stat = $pdo->query("SELECT statut, COUNT(*) as total FROM reclamations GROUP BY statut")->fetchAll(PDO::FETCH_ASSOC); // <<< REMOVE THIS LINE
// $reclamations = $pdo->query("SELECT * FROM reclamations ORDER BY date_creation DESC")->fetchAll(PDO::FETCH_ASSOC); // <<< REMOVE THIS LINE

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Dashboard</title>
    <style>
        /* Your existing CSS styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f6fa;
            padding: 20px;
            margin: 0; /* Add margin: 0 to prevent default body margin */
        }
        h1, h2 {
            color: #2C3E50;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        .stat {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            flex: 1;
            min-width: 120px; /* Minimum width for stat boxes */
            text-align: center;
            font-weight: bold;
            border: 1px solid #bdc3c7; /* Add subtle border */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Add subtle shadow */
            margin-top: 20px; /* Add margin */
        }
        th, td {
            padding: 12px 15px; /* Adjust padding */
            border: 1px solid #ddd; /* Lighter border */
            text-align: left; /* Align text left */
        }
        th {
            background-color: #3498db;
            color: white;
            text-transform: uppercase; /* Uppercase headers */
            font-size: 0.9em; /* Slightly smaller header font */
        }
        tr:nth-child(even) {
             background-color: #f2f2f2; /* Zebra striping */
        }
        tr:hover {
            background-color: #e8f4fd; /* Hover effect */
        }
        a {
            text-decoration: none;
            color: #2980b9; /* Slightly darker blue */
        }
        a:hover {
            text-decoration: underline; /* Underline on hover */
        }
        .nav {
            background-color: #34495e; /* Darker background for nav */
            padding: 10px 20px;
            margin: -20px -20px 20px -20px; /* Extend nav to edges */
            border-bottom: 3px solid #3498db;
        }
        .nav a {
            margin-right: 15px;
            color: #ecf0f1; /* Light text color */
            font-weight: bold;
        }
        .nav a:hover {
            color: #ffffff; /* White on hover */
            text-decoration: none; /* Remove underline */
        }
        .action-links a {
            margin: 0 5px; /* Space out action links */
        }
        .message { /* Style for messages when table is empty */
            padding: 15px;
            background-color: #eaf4ff;
            border: 1px solid #b8d4f1;
            border-radius: 5px;
            color: #34495e;
            margin-top: 20px;
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
        <?php if (!empty($stat)): ?>
            <?php foreach ($stat as $s): ?>
                <div class="stat">
                    <?php // Use htmlspecialchars for security, even if coming from DB ?>
                    <?= ucfirst(htmlspecialchars($s['statut'])) ?> : <?= (int)$s['total'] ?> 
                </div>
            <?php endforeach; ?>
        <?php else: ?>
             <div class="stat">Aucune donnée</div>
        <?php endif; ?>
    </div>

    <h2>📋 Toutes les réclamations</h2>

    <?php if (!empty($reclamations)): ?>
        <table>
            <thead> <tr>
                    <th>ID</th>
                    <th>Titre</th>
                    <th>Lieu</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Date Création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody> <?php foreach ($reclamations as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] // Cast to int ?></td>
                        <td><?= htmlspecialchars($r['titre']) // Always escape output ?></td>
                        <td><?= htmlspecialchars($r['lieu']) ?></td>
                        <td><?= htmlspecialchars($r['type_probleme']) ?></td>
                        <td><?= htmlspecialchars($r['statut']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['date_creation'])) // Format date ?></td>
                        <td class="action-links">
                            <a href="voir_reclamation.php?id=<?= $r['id'] ?>" title="Voir Détails">👁️ Voir</a> |
                            <?php // Consider adding an edit link if needed ?>
                            <a href="./controllers/supprimer_reclamation.php?id=<?= $r['id'] ?>" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation (ID: <?= $r['id'] ?>) ? Cette action est irréversible.')" 
                               title="Supprimer">❌ Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="message">Il n'y a actuellement aucune réclamation enregistrée.</p>
    <?php endif; ?>

</body>
</html>