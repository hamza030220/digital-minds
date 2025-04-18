<?php
// Start session if needed for admin checks later
// session_start();

// Connexion à la base de données using Database class
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Erreur critique: Connexion à la base de données échouée.");
}

// Statistiques par statut
$statStmt = $pdo->query("SELECT statut, COUNT(*) as total FROM reclamations GROUP BY statut");
$stat = $statStmt ? $statStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Filtres
$lieu = $_GET['lieu'] ?? '';
$type_probleme = $_GET['type_probleme'] ?? '';

$sql = "SELECT * FROM reclamations WHERE 1";
$params = [];

if (!empty($lieu)) {
    $sql .= " AND lieu LIKE ?";
    $params[] = "%$lieu%";
}

if (!empty($type_probleme)) {
    $sql .= " AND type_probleme = ?";
    $params[] = $type_probleme;
}

$sql .= " ORDER BY date_creation DESC";

$stmt = $pdo->prepare($sql);
if ($stmt) {
    $stmt->execute($params);
    $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    error_log("Failed to prepare reclamation query in liste_reclamations.php. SQL: " . $sql);
    $reclamations = [];
    echo "<p style='color:red; text-align:center;'>Erreur lors de la préparation de la liste des réclamations.</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tableau de bord Réclamations</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 200px;
            background-color: rgb(10, 73, 15);
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            padding-top: 20px;
            color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar .logo h1 {
            margin: 0;
            font-size: 1.8em;
            color: #28a745;
        }

        .sidebar .logo p {
            margin: 0;
            font-size: 0.8em;
            color: #adb5bd;
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
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            font-size: 1em;
        }

        .sidebar ul li a:hover {
            background-color: #28a745;
            border-radius: 0 20px 20px 0;
        }

        /* Adjust container to account for sidebar */
        .container {
            margin-left: 220px;
            width: calc(90% - 220px);
            max-width: 1200px;
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: #2C3E50;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 10px 0;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        input, select {
            padding: 5px;
            margin: 10px 0;
            margin-right: 5px;
            min-width: 150px;
        }

        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-around;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .stat {
            background-color: #ecf0f1;
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            flex-grow: 1;
            min-width: 100px;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }

        .search-form input, .search-form select {
            flex-grow: 1;
            margin: 0;
        }

        .search-form button {
            padding: 8px 15px;
            background-color: #2ecc71;
            border: none;
            color: white;
            cursor: pointer;
            margin: 0;
        }

        .search-form button:hover {
            background-color: #27ae60;
        }

        .export-form {
            margin-top: 20px;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 5px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .export-form button {
            background-color: #16a085;
        }

        .export-form button:hover {
            background-color: #117a65;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">
            <h1>Green.tn</h1>
            <p>Mobilité durable, énergie propre</p>
        </div>
        <ul>
            <li><a href="">🏠 Accueil</a></li>
            <li><a href="">🚲 Reservation</a></li>
            <li><a href="reclamations_utilisateur.php">📋 Reclamation</a></li>
            <li><a href="statistique.php">📊 Statistique</a></li>
            <li><a href="logout.php">🔓 Déconnexion</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="container">
        <h1>📊 Tableau de bord - Admin</h1>
        <h2>📋 Toutes les réclamations</h2>

        <!-- Stats Display -->
        <div class="stats">
            <?php if (!empty($stat)): ?>
                <?php foreach ($stat as $s): ?>
                    <div class="stat">
                        <strong><?= ucfirst(htmlspecialchars($s['statut'])) ?> :</strong> <?= htmlspecialchars($s['total']) ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Statistiques non disponibles.</p>
            <?php endif; ?>
        </div>

        <!-- Search Form -->
        <form method="get" class="search-form">
            <input type="text" name="lieu" placeholder="🔎 Rechercher par lieu" value="<?= htmlspecialchars($lieu) ?>">
            <select name="type_probleme">
                <option value="">🔖 Tous les types</option>
                <option value="mécanique" <?= $type_probleme === 'mécanique' ? 'selected' : '' ?>>Mécanique</option>
                <option value="batterie" <?= $type_probleme === 'batterie' ? 'selected' : '' ?>>Batterie</option>
                <option value="écran" <?= $type_probleme === 'écran' ? 'selected' : '' ?>>Écran</option>
                <option value="pneu" <?= $type_probleme === 'pneu' ? 'selected' : '' ?>>Pneu</option>
                <option value="Infrastructure" <?= $type_probleme === 'Infrastructure' ? 'selected' : '' ?>>Infrastructure</option>
                <option value="Autre" <?= $type_probleme === 'Autre' ? 'selected' : '' ?>>Autre</option>
            </select>
            <button type="submit">Rechercher</button>
            <a href="?" style="padding: 8px 15px; background-color:#f39c12; color:white; text-decoration:none; border-radius:5px; margin-left: 5px;">Reset</a>
        </form>

        <!-- Table Display -->
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Description</th>
                    <th>Lieu</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reclamations)): ?>
                    <?php foreach ($reclamations as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['titre']) ?></td>
                        <td><?= htmlspecialchars(substr($r['description'], 0, 50)) . (strlen($r['description']) > 50 ? '...' : '') ?></td>
                        <td><?= htmlspecialchars($r['lieu']) ?></td>
                        <td><?= htmlspecialchars($r['type_probleme']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($r['statut'])) ?></td>
                        <td>
                            <a href="voir_reclamation.php?id=<?= $r['id'] ?>" title="Voir détails">👁️Voir</a> |
                            <a href="./controllers/supprimer_reclamation.php?id=<?= $r['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?')" title="Supprimer">❌Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Aucune réclamation trouvée correspondant aux critères.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Export PDF Form -->
        <form method="get" action="./controllers/export_pdf.php" class="export-form">
            <input type="text" name="lieu" placeholder="Lieu" value="<?= htmlspecialchars($lieu) ?>">
            <select name="type_probleme">
                <option value="">Tous les types</option>
                <option value="mécanique" <?= $type_probleme === 'mécanique' ? 'selected' : '' ?>>Mécanique</option>
                <option value="batterie" <?= $type_probleme === 'batterie' ? 'selected' : '' ?>>Batterie</option>
                <option value="écran" <?= $type_probleme === 'écran' ? 'selected' : '' ?>>Écran</option>
                <option value="pneu" <?= $type_probleme === 'pneu' ? 'selected' : '' ?>>Pneu</option>
            </select>
            <select name="statut">
                <option value="">Tous les statuts</option>
                <option value="ouverte">Ouverte</option>
                <option value="en cours">En cours</option>
                <option value="resolue">Résolue</option>
            </select>
            <button type="submit">📤 Exporter PDF</button>
        </form>
    </div>
</body>
</html>