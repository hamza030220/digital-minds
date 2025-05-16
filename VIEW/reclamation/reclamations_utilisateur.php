<?php
// Démarrer la session
session_start();

// Définir le chemin racine du projet
$basePath = '../BACK/'; // Ajusté pour pointer vers VIEW/BACK/

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

// Récupérer les informations de l'utilisateur connecté pour le sidebar
$user_info = [];
if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT prenom, nom, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Définir les variables pour le sidebar
$section = isset($_GET['section']) ? $_GET['section'] : 'stats';
$currentPageSidebar = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Voir les réclamations - Green.tn</title>
    <link rel="icon" href="<?php echo $basePath; ?>../logo.jpg" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $basePath; ?>../theme.js" defer></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #60BA97;
            margin: 0;
            padding: 0;
            transition: background-color 0.3s ease;
        }

        body.dark-mode {
            background-color: #2f2f2f;
            color: #e0e0e0;
        }

        /* Sidebar Styles */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: -250px;
            background-color: rgba(96, 186, 151, 0.9);
            backdrop-filter: blur(5px);
            transition: left 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.dark-mode {
            background-color: rgba(51, 51, 51, 0.9);
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
        }

        .sidebar-brand img {
            width: 60%;
            height: auto;
        }

        .sidebar-content {
            padding: 20px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
        }

        .sidebar-nav-item {
            margin-bottom: 10px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #d0f0d6;
            text-decoration: none;
            font-size: 15px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .sidebar-nav-link:hover {
            background-color: #1b5e20;
            color: white;
        }

        .sidebar.dark-mode .sidebar-nav-link {
            color: #ffffff;
        }

        .sidebar.dark-mode .sidebar-nav-link:hover {
            background-color: #444444;
        }

        .sidebar-nav-link.active {
            background-color: #388e3c;
            color: white;
        }

        .sidebar.dark-mode .sidebar-nav-link.active {
            background-color: #388e3c;
        }

        .sidebar-nav-icon {
            margin-right: 10px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 20px;
            text-align: center;
        }

        .sidebar-footer .btn {
            font-size: 14px;
            width: 100%;
            margin-bottom: 10px;
        }

        .sidebar-toggler {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background-color: #60BA97;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sidebar-toggler:hover {
            background-color: #388e3c;
        }

        .main-content {
            margin-left: 0;
            padding: 40px;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            transition: margin-left 0.3s ease;
        }

        .main-content-expanded {
            margin-left: 250px;
        }

        .container {
            margin-left: 0;
            width: calc(90% - 0px);
            max-width: 1200px;
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: #F9F5E8;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #4CAF50;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .container.dark-mode {
            background-color: #424242;
            color: #e0e0e0;
            border-color: #4CAF50;
        }

        h1, h2 {
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .container.dark-mode h1, .container.dark-mode h2 {
            color: #81c784;
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

        .error-message.dark-mode {
            border-color: #ef9a9a;
            background-color: #5c2525;
            color: #ef9a9a;
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

        .stats.dark-mode {
            background-color: #424242;
            border-color: #81c784;
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

        .stat.dark-mode {
            background-color: #616161;
            border-color: #81c784;
            color: #81c784;
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

        .search-form.dark-mode {
            background-color: #424242;
            border-color: #81c784;
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

        .search-form.dark-mode input,
        .search-form.dark-mode select {
            background-color: #616161;
            border-color: #81c784;
            color: #e0e0e0;
        }

        .search-form input:focus,
        .search-form select:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .search-form.dark-mode input:focus,
        .search-form.dark-mode select:focus {
            border-color: #81c784;
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

        .search-form.dark-mode button {
            background-color: #1b5e20;
        }

        .search-form button:hover {
            background-color: #1b5e20;
        }

        .search-form.dark-mode button:hover {
            background-color: #2e7d32;
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

        .search-form.dark-mode a {
            background-color: #e67e22;
        }

        .search-form a:hover {
            background-color: #e67e22;
        }

        .search-form.dark-mode a:hover {
            background-color: #f39c12;
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

        table.dark-mode {
            background-color: #424242;
            border-color: #81c784;
        }

        table, th, td {
            border: 1px solid #4CAF50;
        }

        table.dark-mode th, table.dark-mode td {
            border-color: #81c784;
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

        th.dark-mode {
            background-color: #616161;
            color: #81c784;
        }

        td {
            color: #333;
        }

        td.dark-mode {
            color: #e0e0e0;
        }

        table a {
            color: #2e7d32;
            text-decoration: none;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: color 0.3s ease;
        }

        table.dark-mode a {
            color: #81c784;
        }

        table a:hover {
            color: #1b5e20;
        }

        table.dark-mode a:hover {
            color: #2e7d32;
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

        .export-form.dark-mode {
            background-color: #424242;
            border-color: #81c784;
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

        .export-form.dark-mode input,
        .export-form.dark-mode select {
            background-color: #616161;
            border-color: #81c784;
            color: #e0e0e0;
        }

        .export-form input:focus,
        .export-form select:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .export-form.dark-mode input:focus,
        .export-form.dark-mode select:focus {
            border-color: #81c784;
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

        .export-form.dark-mode button {
            background-color: #1b5e20;
        }

        .export-form button:hover {
            background-color: #1b5e20;
        }

        .export-form.dark-mode button:hover {
            background-color: #2e7d32;
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

        .pagination.dark-mode a {
            background-color: #1b5e20;
        }

        .pagination a:hover {
            background-color: #1b5e20;
        }

        .pagination.dark-mode a:hover {
            background-color: #2e7d32;
        }

        .pagination a.disabled {
            background-color: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination.dark-mode a.disabled {
            background-color: #757575;
        }

        .pagination span {
            font-size: 16px;
            color: #2e7d32;
            font-weight: bold;
        }

        .pagination.dark-mode span {
            color: #81c784;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.show {
                left: 0;
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
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a class="sidebar-brand" href="<?php echo $basePath; ?>dashboard.php?section=stats">
                <img src="<?php echo $basePath; ?>../../image/ve.png" alt="Green.tn">
            </a>
        </div>
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?php echo $section === 'stats' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>dashboard.php?section=stats" data-translate="home">
                        <span class="sidebar-nav-icon"><i class="bi bi-house-door"></i></span>
                        <span class="sidebar-nav-text">Accueil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?php echo isset($_GET['page']) && $_GET['page'] === 'gestion_utilisateurs' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>dashboard.php?page=gestion_utilisateurs" data-translate="profile_management">
                        <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                        <span class="sidebar-nav-text">Gestion de votre profil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>reservation.php" data-translate="reservations">
                        <span class="sidebar-nav-icon"><i class="bi bi-calendar"></i></span>
                        <span class="sidebar-nav-text">Voir réservations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="../reclamation/reclamations_utilisateur.php" data-translate="complaints">
                        <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                        <span class="sidebar-nav-text">Réclamations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="../../VIEW/reclamation/liste_avis.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-star"></i></span>
                        <span class="sidebar-nav-text"> Avis</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>velos.php?super_admin=1" data-translate="bikes_batteries">
                        <span class="sidebar-nav-icon"><i class="bi bi-bicycle"></i></span>
                        <span class="sidebar-nav-text">Vélos & Batteries</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?php echo $currentPageSidebar === 'forum_admin.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>forum_admin.php" data-translate="forum">
                        <span class="sidebar-nav-icon"><i class="bi bi-chat"></i></span>
                        <span class="sidebar-nav-text">Forum</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?php echo $currentPageSidebar === 'list.php' && isset($_GET['section']) && $_GET['section'] === 'stations' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>stations/list.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-geo-alt"></i></span>
                        <span class="sidebar-nav-text">Stations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?php echo $currentPageSidebar === 'list.php' && isset($_GET['section']) && $_GET['section'] === 'trajets' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>trajets/list.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-map"></i></span>
                        <span class="sidebar-nav-text">Trajets</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link" href="<?php echo $basePath; ?>repairs.html" data-translate="repair_issues">
                            <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                            <span class="sidebar-nav-text">Réparer les pannes</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link <?php echo $currentPageSidebar === 'update_profil_admin.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>update_profil_admin.php" data-translate="profile_management">
                            <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                            <span class="sidebar-nav-text">Editer mon profil</span>
                        </a>
                    </li>
                <?php endif; ?>
                
            </ul>
        </div>
        <div class="sidebar-footer">
            <div class="mb-2">
                <span class="text-white">Bienvenue, <?php echo htmlspecialchars($user_info['prenom'] . ' ' . $user_info['nom']); ?></span>
            </div>
            <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-light" data-translate="logout">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
            <a href="#" id="darkModeToggle" class="btn btn-outline-light mt-2" data-translate="dark_mode">
                <i class="bi bi-moon"></i> Mode Sombre
            </a>
        </div>
    </div>

    <button class="sidebar-toggler" type="button" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="main">
        <div class="container">
            <h1>📊 Tableau de bord</h1>
            <h2>📋 Toutes les réclamations</h2>

            <?php if (!$isLoggedIn): ?>
                <div class="error-message">Vous devez vous connecter pour accéder à cette page. <a href="<?php echo $basePath; ?>login.php">Connexion</a>.</div>
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
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.getElementById('main');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('main-content-expanded');
        });

        const darkModeToggle = document.getElementById('darkModeToggle');
        darkModeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
            updateDarkModeText();
        });

        function updateDarkModeText() {
            const isDarkMode = document.body.classList.contains('dark-mode');
            darkModeToggle.innerHTML = `<i class="bi ${isDarkMode ? 'bi-sun' : 'bi-moon'}"></i> ${isDarkMode ? 'Mode Clair' : 'Mode Sombre'}`;
        }

        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            updateDarkModeText();
        }
    </script>
</body>
</html>