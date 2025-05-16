<?php
// Démarrer la session
session_start();

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/../../'));

// Connexion à la base de données using Database class
require_once '../../CONFIG/database.php';

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Erreur critique: Connexion à la base de données échouée.");
}

// Vérifier si l'utilisateur est connecté et est un admin
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$isAdmin = false;
if ($isLoggedIn) {
    $query = "SELECT role FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $user && $user['role'] === 'admin';
}

// Statistiques par statut
$statStmt = $pdo->query("SELECT statut, COUNT(*) as total FROM reclamations GROUP BY statut");
$stat = $statStmt ? $statStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Filtres
$lieu = $_GET['lieu'] ?? '';
$type_probleme = $_GET['type_probleme'] ?? '';

// Pagination settings
$itemsPerPage = 4;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure page is at least 1
$offset = ($currentPage - 1) * $itemsPerPage;

// Compter le nombre total de réclamations pour la pagination
$countSql = "SELECT COUNT(*) as total FROM reclamations WHERE 1";
$countParams = [];

if (!empty($lieu)) {
    $countSql .= " AND lieu LIKE ?";
    $countParams[] = "%$lieu%";
}

if (!empty($type_probleme)) {
    $countSql .= " AND type_probleme = ?";
    $countParams[] = $type_probleme;
}

$countStmt = $pdo->prepare($countSql);
if ($countStmt) {
    $countStmt->execute($countParams);
    $totalReclamations = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalReclamations / $itemsPerPage);
    $currentPage = min($currentPage, max(1, $totalPages)); // Adjust current page if it exceeds total pages
    $offset = ($currentPage - 1) * $itemsPerPage; // Recalculate offset
} else {
    $totalReclamations = 0;
    $totalPages = 1;
    $error_message = "Erreur lors du calcul du nombre total de réclamations.";
}

// Récupérer les réclamations avec pagination
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

$sql .= " ORDER BY date_creation DESC LIMIT ? OFFSET ?";

// Préparer la requête
$stmt = $pdo->prepare($sql);
if ($stmt) {
    // Lier les paramètres
    $paramIndex = 1;
    
    // Lier les paramètres de lieu et type_probleme (si présents)
    if (!empty($lieu)) {
        $stmt->bindValue($paramIndex++, "%$lieu%", PDO::PARAM_STR);
    }
    if (!empty($type_probleme)) {
        $stmt->bindValue($paramIndex++, $type_probleme, PDO::PARAM_STR);
    }
    
    // Lier les paramètres LIMIT et OFFSET comme des entiers
    $stmt->bindValue($paramIndex++, $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    
    // Exécuter la requête
    $stmt->execute();
    $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    error_log("Failed to prepare reclamation query in reclamations_utilisateur.php. SQL: " . $sql);
    $reclamations = [];
    $error_message = "Erreur lors de la préparation de la liste des réclamations.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Voir les réclamations - Green.tn</title>
    <link rel="icon" href="../../image/ve.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #60BA97;
            margin: 0;
            padding: 0;
        }

        /* Sidebar Styles */
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

        /* Adjust container to account for sidebar */
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

        h1, h2 {
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .error-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            border: 1px solid #f5c6cb;
            background-color: #f8d7da;
            color: #721c24;
            text-align: center;
        }

        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-around;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff;
            border: 1px solid #4CAF50;
            border-radius: 5px;
        }

        .stat {
            background-color: #F9F5E8;
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            flex-grow: 1;
            min-width: 100px;
            border: 1px solid #4CAF50;
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff;
            border: 1px solid #4CAF50;
            border-radius: 5px;
        }

        .search-form input,
        .search-form select {
            padding: 5px;
            margin: 0;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            flex-grow: 1;
            min-width: 150px;
        }

        .search-form input:focus,
        .search-form select:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .search-form button {
            padding: 8px 15px;
            background-color: #2e7d32;
            border: none;
            color: #fff;
            cursor: pointer;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
        }

        .search-form button:hover {
            background-color: #1b5e20;
        }

        .search-form a {
            padding: 8px 15px;
            background-color: #f39c12;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
        }

        .search-form a:hover {
            background-color: #e67e22;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #fff;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            overflow: hidden;
        }

        table, th, td {
            border: 1px solid #4CAF50;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #F9F5E8;
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        td {
            color: #333;
        }

        table a {
            color: #2e7d32;
            text-decoration: none;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: color 0.3s ease;
        }

        table a:hover {
            color: #1b5e20;
        }

        .export-form {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .export-form input,
        .export-form select {
            padding: 5px;
            margin: 0;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            flex-grow: 1;
            min-width: 150px;
        }

        .export-form input:focus,
        .export-form select:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .export-form button {
            padding: 8px 15px;
            background-color: #2e7d32;
            border: none;
            color: #fff;
            cursor: pointer;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
        }

        .export-form button:hover {
            background-color: #1b5e20;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 10px 20px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
        }

        .pagination a:hover {
            background-color: #1b5e20;
        }

        .pagination a.disabled {
            background-color: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination span {
            font-size: 16px;
            color: #2e7d32;
            font-weight: bold;
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

            .search-form,
            .export-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form input,
            .search-form select,
            .export-form input,
            .export-form select {
                margin: 5px 0;
            }

            .search-form button,
            .search-form a,
            .export-form button {
                margin: 5px 0;
            }

            .stats {
                flex-direction: column;
                align-items: center;
            }

            .stat {
                width: 100%;
            }

            .pagination {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">
            <img src="../../image/ve.png" alt="Green.tn Logo">
        </div>
        <ul>
            <li><a href="../BACK/dashboard.php">🏠 Dashboard</a></li>
<li><a href="../BACK/reservation.php">🚲 Reservation</a></li>
<li><a href="./reclamations_utilisateur.php">📋 Reclamation</a></li>
<li><a href="../../VIEW/reclamation/liste_avis.php">⭐ Avis</a></li>
<li><a href="../BACK/forum.php">💬 Forum</a></li>
<li><a href="../BACK/profil.php">👤 Gestion de votre profil</a></li>
<li><a href="../BACK/station.php">📍 Station</a></li>
<li><a href="../BACK/trajet.php">🛤️ Trajet</a></li>
<li><a href="../BACK/logout.php">🔓 Déconnexion</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="container">
        <h1>📊 Tableau de bord</h1>
        <h2>📋 Toutes les réclamations</h2>

        <?php if (!$isLoggedIn): ?>
            <div class="error-message">Vous devez vous connecter pour accéder à cette page. <a href="../../VIEW/BACK/login.php">Connexion</a>.</div>
        <?php elseif (!$isAdmin): ?>
            <div class="error-message">Accès refusé. Réservé aux admins.</div>
        <?php else: ?>
            <!-- Stats Display -->
            <div class="stats">
                <?php if (!empty($stat)): ?>
                    <?php foreach ($stat as $s): ?>
                        <div class="stat">
                            <strong><?php echo ucfirst(htmlspecialchars($s['statut'])); ?> :</strong> <?php echo htmlspecialchars($s['total']); ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Statistiques non disponibles.</p>
                <?php endif; ?>
            </div>

            <!-- Search Form -->
            <form method="get" class="search-form">
                <input type="text" name="lieu" placeholder="🔎 Rechercher par lieu" value="<?php echo htmlspecialchars($lieu); ?>">
                <select name="type_probleme">
                    <option value="">🔖 Tous les types</option>
                    <option value="mécanique" <?php echo $type_probleme === 'mécanique' ? 'selected' : ''; ?>>Mécanique</option>
                    <option value="batterie" <?php echo $type_probleme === 'batterie' ? 'selected' : ''; ?>>Batterie</option>
                    <option value="écran" <?php echo $type_probleme === 'écran' ? 'selected' : ''; ?>>Écran</option>
                    <option value="pneu" <?php echo $type_probleme === 'pneu' ? 'selected' : ''; ?>>Pneu</option>
                    <option value="autre" <?php echo $type_probleme === 'autre' ? 'selected' : ''; ?>>Autre</option>
                </select>
                <button type="submit">Rechercher</button>
                <a href="./reclamations_utilisateur.php" style="padding: 8px 15px; background-color:#f39c12; color:white; text-decoration:none; border-radius:5px; margin-left: 5px;">Reset</a>
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
                            <td><?php echo htmlspecialchars($r['titre']); ?></td>
                            <td><?php echo htmlspecialchars(substr($r['description'], 0, 50)) . (strlen($r['description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($r['lieu']); ?></td>
                            <td><?php echo htmlspecialchars($r['type_probleme']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($r['statut'])); ?></td>
                            <td>
                                <a href="./voir_reclamation.php?id=<?php echo $r['id']; ?>" title="Voir détails">👁️Voir</a> |
                                <a href="../../CONTROLLER/supprimer_reclamation.php?id=<?php echo $r['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?')" title="Supprimer">❌Supprimer</a>
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

            <!-- Pagination Controls -->
            <div class="pagination">
                <a href="./reclamations_utilisateur.php?page=<?php echo $currentPage - 1; ?>&lieu=<?php echo urlencode($lieu); ?>&type_probleme=<?php echo urlencode($type_probleme); ?>" class="<?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                    Précédent
                </a>
                <span>Page <?php echo $currentPage; ?> / <?php echo $totalPages; ?></span>
                <a href="./reclamations_utilisateur.php?page=<?php echo $currentPage + 1; ?>&lieu=<?php echo urlencode($lieu); ?>&type_probleme=<?php echo urlencode($type_probleme); ?>" class="<?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                    Suivant
                </a>
            </div>

            <!-- Export PDF Form -->
            <form method="get" action="../../CONTROLLER/export_pdf.php" class="export-form">
                <input type="text" name="lieu" placeholder="Lieu" value="<?php echo htmlspecialchars($lieu); ?>">
                <select name="type_probleme">
                    <option value="">Tous les types</option>
                    <option value="mécanique" <?php echo $type_probleme === 'mécanique' ? 'selected' : ''; ?>>Mécanique</option>
                    <option value="batterie" <?php echo $type_probleme === 'batterie' ? 'selected' : ''; ?>>Batterie</option>
                    <option value="écran" <?php echo $type_probleme === 'écran' ? 'selected' : ''; ?>>Écran</option>
                    <option value="pneu" <?php echo $type_probleme === 'pneu' ? 'selected' : ''; ?>>Pneu</option>
                </select>
                <select name="statut">
                    <option value="">Tous les statuts</option>
                    <option value="ouverte">Ouverte</option>
                    <option value="en cours">En cours</option>
                    <option value="résolue">Résolue</option>
                </select>
                <button type="submit">📤 Exporter PDF</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>