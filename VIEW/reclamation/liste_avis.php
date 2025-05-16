<?php
// liste_avis.php

session_start();

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Include database configuration
require_once ROOT_PATH . '../../CONFIG/database.php';

// Connexion à la base de données
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Erreur de connexion à la base de données.");
}

// Vérifier si l'utilisateur est connecté et est un admin
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$isAdmin = false;
$user_info = [];
if ($isLoggedIn) {
    $query = "SELECT role, prenom, nom FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $user && $user['role'] === 'admin';
    $user_info = $user ? $user : [];
}

// Pagination
$avis_per_page = 3; // 3 avis par page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Page actuelle
$offset = ($page - 1) * $avis_per_page; // Calculer l'offset

// Compter le nombre total d'avis
$total_avis_query = "SELECT COUNT(*) FROM avis";
$stmt = $pdo->prepare($total_avis_query);
$stmt->execute();
$total_avis = $stmt->fetchColumn();
$total_pages = ceil($total_avis / $avis_per_page); // Calculer le nombre total de pages

// Récupérer tous les avis avec les informations de l'utilisateur avec pagination
$avis = [];
$average_rating = 0;
if ($isAdmin) {
    // Note: Changé de 'utilisateurs' à 'users'
    $query = "
        SELECT a.*, u.nom 
        FROM avis a 
        JOIN users u ON a.user_id = u.id
        ORDER BY a.date_creation DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':limit', $avis_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la note moyenne (sur tous les avis)
    $query = "SELECT note FROM avis";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $all_ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($all_ratings)) {
        $total_rating = array_sum(array_column($all_ratings, 'note'));
        $average_rating = $total_rating / count($all_ratings);
    }
}

// Définir le titre de la page
$pageTitle = "Voir les avis";
$currentPageSidebar = basename($_SERVER['PHP_SELF']);
$basePath = '../BACK/'; // Ajusté pour pointer vers VIEW/BACK/
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Green.tn</title>
    <link rel="icon" href="../../image/ve.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #60BA97;
            color: #333;
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
            background: transparent; /* Changé pour rendre l'arrière-plan transparent */
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
            text-align: left;
        }

        main {
            padding: 20px;
            text-align: center;
            background-color: transparent; /* Changé pour rendre l'arrière-plan transparent */
        }

        main h2 {
            margin-bottom: 30px;
            color: #2e7d32;
            font-size: 24px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
            background-color: #F9F5E8;
            padding: 10px 20px;
            display: inline-block;
            border-radius: 5px;
        }

        .average-rating {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #2e7d32;
        }

        .average-rating .stars {
            color: #FFD700;
            font-size: 20px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            border: 1px solid transparent;
            text-align: center;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .message.error a {
            color: #2e7d32;
            text-decoration: none;
        }

        .message.error a:hover {
            text-decoration: underline;
        }

        .avis {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #fff;
        }

        .avis h3 {
            font-size: 18px;
            color: #2e7d32;
            margin-bottom: 5px;
        }

        .avis .meta {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }

        .avis .stars {
            color: #FFD700;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .avis p {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            background-color: #4CAF50;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .pagination a:hover {
            background-color: #2e7d32;
        }

        .pagination a.disabled {
            background-color: #ccc;
            pointer-events: none;
        }

        .pagination a.current {
            background-color: #2e7d32;
        }

        footer {
            background-color: #F9F5E8;
            padding: 20px 0;
            border-top: none;
            font-family: "Berlin Sans FB", Arial, sans-serif;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-left {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-logo img {
            width: 200px;
            height: auto;
            margin-bottom: 15px;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .social-icons a img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transition: opacity 0.3s ease;
        }

        .social-icons a img:hover {
            opacity: 0.8;
        }

        .footer-section {
            margin-left: 40px;
        }

        .footer-section h3 {
            font-size: 18px;
            color: #333;
            margin-top: 20px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 8px;
        }

        .footer-section ul li a {
            text-decoration: none;
            color: #555;
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #4CAF50;
        }

        .footer-section p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .footer-section p img {
            margin-right: 8px;
            width: 16px;
            height: 16px;
        }

        .footer-section p a {
            color: #555;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section p a:hover {
            color: #4CAF50;
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

            main {
                padding: 20px;
            }

            .footer-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .footer-left {
                margin-bottom: 20px;
            }

            .footer-section {
                margin-left: 0;
                margin-bottom: 20px;
            }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $basePath; ?>theme.js" defer></script>
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
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>dashboard.php?section=stats" data-translate="home">
                        <span class="sidebar-nav-icon"><i class="bi bi-house-door"></i></span>
                        <span class="sidebar-nav-text">Accueil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>dashboard.php?page=gestion_utilisateurs" data-translate="profile_management">
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
                    <a class="sidebar-nav-link <?php echo $currentPageSidebar === 'liste_avis.php' ? 'active' : ''; ?>" href="../reclamation/liste_avis.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-star"></i></span>
                        <span class="sidebar-nav-text">Avis</span>
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

    <div class="main-content" id="main">
        <main>
            <div class="container">
                <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
                <?php if (!$isLoggedIn): ?>
                    <p class="message error">Vous devez vous connecter pour accéder à cette page. <a href="../BACK/login.php">Connexion</a>.</p>
                <?php elseif (!$isAdmin): ?>
                    <p class="message error">Accès refusé. Réservé aux admins.</p>
                <?php else: ?>
                    <div class="average-rating">
                        Note moyenne : 
                        <?php
                        $rounded_average = round($average_rating, 1);
                        echo $rounded_average . '/5 ';
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= round($average_rating) ? '★' : '☆';
                        }
                        ?>
                    </div>
                    <?php if (empty($avis) && $total_avis == 0): ?>
                        <p class="message">Aucun avis disponible.</p>
                    <?php else: ?>
                        <?php foreach ($avis as $avi): ?>
                            <div class="avis">
                                <h3><?php echo htmlspecialchars($avi['titre']); ?></h3>
                                <div class="meta">
                                    Soumis par <?php echo htmlspecialchars($avi['nom']); ?> 
                                    le <?php echo date('d/m/Y H:i', strtotime($avi['date_creation'])); ?>
                                </div>
                                <div class="stars">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $avi['note'] ? '★' : '☆';
                                    }
                                    ?>
                                </div>
                                <p><?php echo htmlspecialchars($avi['description']); ?></p>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="../reclamation/liste_avis.php?page=<?php echo $page - 1; ?>">Précédent</a>
                            <?php else: ?>
                                <a href="#" class="disabled">Précédent</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="../reclamation/liste_avis.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'current' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="../reclamation/liste_avis.php?page=<?php echo $page + 1; ?>">Suivant</a>
                            <?php else: ?>
                                <a href="#" class="disabled">Suivant</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
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