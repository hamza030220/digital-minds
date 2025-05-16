<?php
session_start();
require_once  'C:\xampp\htdocs\projetweb\CONFIG\db.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

// Handle report/unreport actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        if ($_GET['action'] === 'report_post') {
            $stmt = $pdo->prepare("UPDATE posts SET is_reported = 1 WHERE post_id = ?");
            $stmt->execute([$id]);
        } elseif ($_GET['action'] === 'unreport_post') {
            $stmt = $pdo->prepare("UPDATE posts SET is_reported = 0 WHERE post_id = ?");
            $stmt->execute([$id]);
        } elseif ($_GET['action'] === 'report_comment') {
            $stmt = $pdo->prepare("UPDATE comments SET is_reported = 1 WHERE comment_id = ?");
            $stmt->execute([$id]);
        } elseif ($_GET['action'] === 'unreport_comment') {
            $stmt = $pdo->prepare("UPDATE comments SET is_reported = 0 WHERE comment_id = ?");
            $stmt->execute([$id]);
        }
    } catch (Exception $e) {
        error_log("Action failed: {$_GET['action']} for id $id: " . $e->getMessage());
    }
    header("Location: forum_admin.php");
    exit();
}

// Pagination settings
$posts_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Handle search, sort, and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$valid_sort_columns = ['created_at', 'title'];
$valid_statuses = ['', 'reported', 'not_reported'];
if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'created_at';
}
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = '';
}

// Count total posts for pagination
try {
    $sql_count = "SELECT COUNT(*) FROM posts p LEFT JOIN users u ON p.id = u.id WHERE p.is_deleted = 0 AND (u.nom LIKE :search OR u.prenom LIKE :search)";
    if ($status_filter === 'reported') {
        $sql_count .= " AND p.is_reported = 1";
    } elseif ($status_filter === 'not_reported') {
        $sql_count .= " AND p.is_reported = 0";
    }
    $stmt = $pdo->prepare($sql_count);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->execute();
    $total_posts = $stmt->fetchColumn();
    $total_pages = ceil($total_posts / $posts_per_page);
} catch (PDOException $e) {
    error_log("Error counting posts: " . $e->getMessage());
    echo "Erreur lors du chargement des publications.";
    exit();
}

// Fetch posts with pagination, excluding deleted posts
try {
    $sql = "
        SELECT p.*, u.nom, u.prenom, u.email
        FROM posts p
        LEFT JOIN users u ON p.id = u.id
        WHERE p.is_deleted = 0 AND (u.nom LIKE :search OR u.prenom LIKE :search)";
    if ($status_filter === 'reported') {
        $sql .= " AND p.is_reported = 1";
    } elseif ($status_filter === 'not_reported') {
        $sql .= " AND p.is_reported = 0";
    }
    $sql .= " ORDER BY p.$sort_by DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$posts_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching posts: " . $e->getMessage());
    echo "Erreur lors du chargement des publications.";
    exit();
}

// Fetch all comments and group by post_id, excluding deleted comments
$comments = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, u.email
        FROM comments c
        LEFT JOIN users u ON c.id = u.id
        WHERE c.is_deleted = 0
        ORDER BY c.created_at ASC
    ");
    $stmt->execute();
    $all_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_comments as $comment) {
        $comments[$comment['post_id']][] = $comment;
    }
} catch (PDOException $e) {
    error_log("Error fetching comments: " . $e->getMessage());
    echo "Erreur lors du chargement des commentaires.";
    exit();
}

$basePath = '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title data-translate="forum_admin">Forum Admin - Green.tn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(45deg, #e9f5ec, #a8e6a3, #60c26d, #4a90e2);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            color: #333;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

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
            font/size: 15px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .sidebar-nav-link:hover {
            background-color: #1b5e20;
            color: white;
        }

        .sidebar-nav-link.active {
            background-color: #388e3c;
            color: white;
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

        .header-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-header {
            width: 110px;
            height: auto;
        }

        .main-content h1 {
            color: #2e7d32;
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }

        .section-content {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideIn 0.5s ease;
            margin-bottom: 40px;
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .task-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            flex-wrap: wrap;
            gap: 10px;
        }

        .search-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }

        .search-input {
            padding: 8px;
            font-size: 14px;
            width: 150px;
            border-radius: 6px;
            border: 1px solid #ffffff;
            background-color: #ffffff;
            color: #333;
        }

        .sort-container select {
            padding: 8px;
            font-size: 14px;
            border-radius: 6px;
            border:  miracles1px solid #ffffff;
            background-color: #ffffff;
            color: #333;
        }

        .translate-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .translate-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            font-size: 14px;
            color: #ffffff;
            background-color: #1b5e20;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .translate-btn:hover {
            background-color: #4caf50;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            font-size: 14px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin-right: 8px;
        }

        .btn:hover {
            background-color: #388e3c;
        }

        .btn.report {
            background-color: #e74c3c;
        }

        .btn.report:hover {
            background-color: #c0392b;
        }

        .btn.unreport {
            background-color: #7f8c8d;
        }

        .btn.unreport:hover {
            background-color: #95a5a6;
        }

        .btn.details {
            background-color: #3498db;
        }

        .btn.details:hover {
            background-color: #2980b9;
        }

        .post-card, .comment-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            border: 1px solid rgba(76, 175, 80, 0.5);
            transition: transform 0.2s ease;
        }

        .post-card:hover {
            transform: translateY(-5px);
        }

        .post-card.hidden, .comment-card.hidden {
            display: none;
        }

        .post-title {
            font-size: 20px;
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 10px;
        }

        .post-header, .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .post-meta, .comment-meta {
            font-size: 14px;
            color: #555;
            margin-right: 15px;
        }

        .post-meta .author, .comment-meta .author {
            font-weight: 600;
        }

        .post-content, .comment-content {
            font-size: 15px;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .post-content.truncated, .comment-content.truncated {
            display: inline;
        }

        .read-more {
            color: #3498db;
            cursor: pointer;
            text-decoration: underline;
            margin-left: 5px;
        }

        .read-more:hover {
            color: #2980b9;
        }

        .status-badges {
            margin-bottom: 15px;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 12px;
            margin-right: 8px;
        }

        .badge.reported {
            background-color: #e74c3c;
            color: white;
        }

        .badge.deleted {
            background-color: #7f8c8d;
            color: white;
        }

        .post-actions, .comment-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .comment-section {
            margin-top: 20px;
            padding-left: 20px;
            border-left: 3px solid #4caf50;
        }

        .comment-card {
            background: rgba(245, 245, 245, 0.95);
            padding: 15px;
            margin-bottom: 15px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-content h3 {
            margin: 0 0 20px;
            color: #2e7d32;
            font-size: 20px;
            text-align: center;
        }

        .modal-content p {
            margin: 10px 0;
            font-size: 14px;
        }

        .modal-content img {
            max-width: 100px;
            border-radius: 6px;
        }

        .modal-content .btn {
            padding: 10px 20px;
            font-size: 14px;
        }

        .dark-mode {
            background: linear-gradient(45deg, #1a3c34, #2c3e50, #34495e, #2e7d32);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            color: #e0e0e0;
        }

        .dark-mode .sidebar {
            background-color: rgba(51, 51, 51, 0.9);
        }

        .dark-mode .sidebar-nav-link {
            color: #ffffff;
        }

        .dark-mode .main-content {
            background: rgba(30, 30, 30, 0.8);
        }

        .dark-mode .main-content h1,
        .dark-mode .post-title,
        .dark-mode .modal-content h3 {
            color: #4caf50;
        }

        .dark-mode .section-content,
        .dark-mode .post-card,
        .dark-mode .comment-card {
            background-color: rgba(50, 50, 50, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .dark-mode .post-meta,
        .dark-mode .comment-meta {
            color: #b0b0b0;
        }

        .dark-mode .post-content,
        .dark-mode .comment-content {
            color: #e0e0e0;
        }

        .dark-mode .modal-content {
            background-color: rgba(50, 50, 50, 0.95);
            color: #e0e0e0;
        }

        .dark-mode .read-more {
            color: #66b0ff;
        }

        .dark-mode .read-more:hover {
            color: #4b8cd4;
        }

        .dark-mode .search-input, .dark-mode .sort-container select {
            background-color: rgba(70, 70, 70, 0.9);
            color: #e0e0e0;
            border: 1px solid #ffffff;
        }

        .dark-mode .search-input:focus, .dark-mode .sort-container select:focus {
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.7);
        }

        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .task-bar {
                flex-direction: column;
                gap: 10px;
            }

            .search-container, .translate-container {
                width: 100%;
                justify-content: center;
            }

            .search-input, .sort-container select {
                width: 100%;
                max-width: 200px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .post-card, .comment-card {
                padding: 15px;
            }

            .post-title {
                font-size: 18px;
            }

            .post-meta, .comment-meta {
                font-size: 13px;
            }

            .post-content, .comment-content {
                font-size: 14px;
            }

            .modal-content {
                width: 95%;
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .post-header, .comment-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .post-meta, .comment-meta {
                margin-right: 0;
                margin-bottom: 5px;
            }

            .post-actions, .comment-actions {
                flex-direction: column;
                gap: 8px;
            }

            .search-input, .sort-container select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="<?php echo $basePath; ?>dashboard.php?section=stats">
            <img src="<?php echo $basePath; ?>logo.jpg" alt="Green.tn">
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
                    <a class="sidebar-nav-link" href="../reclamation/liste_avis.php">
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
                <a class="sidebar-nav-link" href="repairs.html" data-translate="repair_issues">
                    <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                    <span class="sidebar-nav-text">Réparer les pannes</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link active" href="<?php echo $basePath; ?>forum_admin.php" data-translate="forum_admin">
                    <span class="sidebar-nav-icon"><i class="bi bi-chat"></i></span>
                    <span class="sidebar-nav-text">Gestion du Forum</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?php echo $currentPage === 'stations' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>stations/list.php">
                    <span class="sidebar-nav-icon"><i class="bi bi-geo-alt"></i></span>
                    <span class="sidebar-nav-text">Stations</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?php echo $currentPage === 'trajets' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>trajets/list.php">
                    <span class="sidebar-nav-icon"><i class="bi bi-map"></i></span>
                    <span class="sidebar-nav-text">Trajets</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <div class="mb-2">
            <span class="text-white">Bienvenue, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
        </div>
        <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-light" data-translate="logout">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>
        <a href="#" id="darkModeToggle" class="btn btn-outline-light mt-2" data-translate="dark_mode">
            <i class="bi bi-moon"></i> Mode Sombre
        </a>
    </div>
</div>

<!-- Sidebar Toggle Button -->
<button class="sidebar-toggler" type="button" id="sidebarToggle">
    <i class="bi bi-list"></i>
</button>

<!-- Main Content -->
<div class="main-content" id="main">
    <div class="header-logo">
        <img src="logo.jpg" alt="Logo Green.tn" class="logo-header">
    </div>
    <h1 data-translate="forum_admin">Gestion du Forum</h1>

    <div class="section-content">
        <!-- Task Bar -->
        <div class="task-bar">
            <form method="get" class="search-container">
                <label for="search" class="search-label" data-translate="search"><i class="bi bi-search"></i> Rechercher :</label>
                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher par nom..." class="search-input" data-translate-placeholder="search_placeholder">
                <label for="sort_by" class="search-label" data-translate="sort_by"><i class="bi bi-sort-alpha-down"></i> Trier par :</label>
                <select name="sort_by" id="sort_by" class="search-input" onchange="this.form.submit()">
                    <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?> data-translate="created_at">Date de création</option>
                    <option value="title" <?php echo $sort_by == 'title' ? 'selected' : ''; ?> data-translate="title">Titre</option>
                </select>
                <label for="status" class="search-label" data-translate="filter_by_status"><i class="bi bi-filter"></i> Filtrer par statut :</label>
                <select name="status" id="status" class="search-input" onchange="this.form.submit()">
                    <option value="" <?php echo $status_filter == '' ? 'selected' : ''; ?> data-translate="all_statuses">Tous</option>
                    <option value="reported" <?php echo $status_filter == 'reported' ? 'selected' : ''; ?> data-translate="reported">Signalé</option>
                    <option value="not_reported" <?php echo $status_filter == 'not_reported' ? 'selected' : ''; ?> data-translate="not_reported">Non signalé</option>
                </select>
                <button type="submit" class="btn" data-translate="search"><i class="bi bi-search"></i> Rechercher</button>
            </form>
            <div class="translate-container">
                <button class="translate-btn" id="toggle-language" data-translate="language"><i class="fas fa-globe"></i> Français</button>
            </div>
        </div>

        <!-- Posts and Comments -->
        <h2 data-translate="posts">Publications</h2>
        <?php foreach ($posts as $post): ?>
            <div class="post-card" data-user-id="<?php echo $post['id']; ?>">
                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                <div class="post-header">
                    <span class="post-meta">
                        <span data-translate="author">Auteur</span>: 
                        <span class="author"><?php echo $post['is_anonymous'] ? 'Anonyme' : htmlspecialchars($post['prenom'] . ' ' . $post['nom']); ?></span>
                    </span>
                    <span class="post-meta">
                        <span data-translate="created_at">Date</span>: <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                    </span>
                </div>
                <div class="status-badges">
                    <?php if ($post['is_reported']): ?>
                        <span class="badge reported" data-translate="reported">Signalé</span>
                    <?php endif; ?>
                </div>
                <p class="post-content <?php echo strlen($post['content']) > 100 ? 'truncated' : ''; ?>" data-full-content="<?php echo htmlspecialchars($post['content']); ?>">
                    <?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>
                    <?php if (strlen($post['content']) > 100): ?>
                        <span class="read-more" data-translate="read_more">Lire plus</span>
                    <?php endif; ?>
                </p>
                <div class="post-actions">
                    <a href="?action=<?php echo $post['is_reported'] ? 'unreport_post' : 'report_post'; ?>&id=<?php echo $post['post_id']; ?>" 
                       class="btn <?php echo $post['is_reported'] ? 'unreport' : 'report'; ?>" 
                       data-translate="<?php echo $post['is_reported'] ? 'unreport' : 'report'; ?>">
                        <i class="bi bi-exclamation-circle"></i> <?php echo $post['is_reported'] ? 'Annuler signalement' : 'Signaler'; ?>
                    </a>
                    <button class="btn details view-details" data-user-id="<?php echo $post['id']; ?>" data-translate="details">
                        <i class="bi bi-info-circle"></i> Détails
                    </button>
                </div>
                <!-- Comments Section -->
                <?php if (isset($comments[$post['post_id']]) && !empty($comments[$post['post_id']])): ?>
                    <div class="comment-section">
                        <h4 data-translate="comments">Commentaires</h4>
                        <?php foreach ($comments[$post['post_id']] as $comment): ?>
                            <div class="comment-card" data-user-id="<?php echo $comment['id']; ?>">
                                <div class="comment-header">
                                    <span class="comment-meta">
                                        <span data-translate="author">Auteur</span>: 
                                        <span class="author"><?php echo htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']); ?></span>
                                    </span>
                                    <span class="comment-meta">
                                        <span data-translate="created_at">Date</span>: <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="status-badges">
                                    <?php if ($comment['is_reported']): ?>
                                        <span class="badge reported" data-translate="reported">Signalé</span>
                                    <?php endif; ?>
                                </div>
                                <p class="comment-content <?php echo strlen($comment['content']) > 100 ? 'truncated' : ''; ?>" data-full-content="<?php echo htmlspecialchars($comment['content']); ?>">
                                    <?php echo htmlspecialchars(substr($comment['content'], 0, 100)); ?>
                                    <?php if (strlen($comment['content']) > 100): ?>
                                        <span class="read-more" data-translate="read_more">Lire plus</span>
                                    <?php endif; ?>
                                </p>
                                <div class="comment-actions">
                                    <a href="?action=<?php echo $comment['is_reported'] ? 'unreport_comment' : 'report_comment'; ?>&id=<?php echo $comment['comment_id']; ?>" 
                                       class="btn <?php echo $comment['is_reported'] ? 'unreport' : 'report'; ?>" 
                                       data-translate="<?php echo $comment['is_reported'] ? 'unreport' : 'report'; ?>">
                                        <i class="bi bi-exclamation-circle"></i> <?php echo $comment['is_reported'] ? 'Annuler signalement' : 'Signaler'; ?>
                                    </a>
                                    <button class="btn details view-details" data-user-id="<?php echo $comment['id']; ?>" data-translate="details">
                                        <i class="bi bi-info-circle"></i> Détails
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" title="Page précédente"><i class="bi bi-arrow-left"></i></a>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" title="Page suivante"><i class="bi bi-arrow-right"></i></a>
            </div>
        <?php endif; ?>
    </div>

    <!-- User Details Modal -->
    <div class="modal" id="userDetailsModal">
        <div class="modal-content">
            <h3 data-translate="user_details">Détails de l'utilisateur</h3>
            <div id="user-details-content">
                <p data-translate="loading">Chargement...</p>
            </div>
            <div class="btn-container">
                <button type="button" class="btn cancel" id="closeDetails" data-translate="close"><i class="bi bi-x"></i> Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');
    const sidebarToggle = document.getElementById('sidebarToggle');

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        main.classList.toggle('main-content-expanded');
    });

    function handleResize() {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('show');
            main.classList.remove('main-content-expanded');
        } else {
            sidebar.classList.add('show');
            main.classList.add('main-content-expanded');
        }
    }

    handleResize();
    window.addEventListener('resize', handleResize);

    // Dark Mode Toggle
    const toggleButton = document.getElementById('darkModeToggle');
    const body = document.body;

    if (localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark-mode');
    }

    toggleButton.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : '');
        updateTranslations();
    });

    // User Details Modal
    const detailsModal = document.getElementById('userDetailsModal');
    const detailsContent = document.getElementById('user-details-content');
    const closeDetails = document.getElementById('closeDetails');

    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            detailsContent.innerHTML = '<p data-translate="loading">Chargement...</p>';

            // AJAX request to fetch user details
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'get_user_details.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        detailsContent.innerHTML = `
                            <p><strong data-translate="name">Nom:</strong> ${response.user.nom}</p>
                            <p><strong data-translate="first_name">Prénom:</strong> ${response.user.prenom}</p>
                            <p><strong data-translate="email">Email:</strong> ${response.user.email}</p>
                            <p><strong data-translate="phone">Téléphone:</strong> ${response.user.telephone}</p>
                            <p><strong data-translate="age">Âge:</strong> ${response.user.age}</p>
                            <p><strong data-translate="governorate">Gouvernorat:</strong> ${response.user.gouvernorats || 'Non spécifié'}</p>
                            <p><strong data-translate="cin">CIN:</strong> ${response.user.cin}</p>
                            ${response.user.photo ? `<p><strong data-translate="profile_picture">Photo:</strong> <img src="user_images/${response.user.photo}" alt="Photo" style="max-width: 100px; border-radius: 6px;"></p>` : ''}
                        `;
                        updateTranslations();
                    } else {
                        detailsContent.innerHTML = '<p data-translate="error">Erreur lors du chargement des détails.</p>';
                    }
                }
            };
            xhr.send('user_id=' + encodeURIComponent(userId));
            detailsModal.style.display = 'flex';
        });
    });

    closeDetails.addEventListener('click', () => {
        detailsModal.style.display = 'none';
    });

    detailsModal.addEventListener('click', (e) => {
        if (e.target === detailsModal) {
            detailsModal.style.display = 'none';
        }
    });

    // Read More functionality
    document.querySelectorAll('.read-more').forEach(link => {
        link.addEventListener('click', function() {
            const contentElement = this.parentElement;
            const fullContent = contentElement.getAttribute('data-full-content');
            contentElement.classList.remove('truncated');
            contentElement.innerHTML = fullContent;
        });
    });

    // Search functionality
    const searchInput = document.getElementById('search');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        document.querySelectorAll('.post-card, .comment-card').forEach(card => {
            const author = card.querySelector('.author').textContent.toLowerCase();
            if (searchTerm === '' || author.includes(searchTerm)) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    });

    // Translation functionality
    const translations = {
        fr: {
            home: "Accueil",
            profile_management: "Gestion de votre profil",
            reservations: "Réservations",
            complaints: "Réclamations",
            bikes_batteries: "Vélos & Batteries",
            repair_issues: "Réparer les pannes",
            forum_admin: "Gestion du Forum",
            logout: "Déconnexion",
            dark_mode: "Mode Sombre",
            language: "Français",
            posts: "Publications",
            comments: "Commentaires",
            title: "Titre",
            author: "Auteur",
            content: "Contenu",
            created_at: "Date de création",
            reported: "Signalé",
            not_reported: "Non signalé",
            deleted: "Supprimé",
            actions: "Actions",
            report: "Signaler",
            unreport: "Annuler signalement",
            details: "Détails",
            user_details: "Détails de l'utilisateur",
            close: "Fermer",
            loading: "Chargement...",
            error: "Erreur",
            name: "Nom",
            first_name: "Prénom",
            email: "Email",
            phone: "Téléphone",
            age: "Âge",
            governorate: "Gouvernorat",
            cin: "CIN",
            profile_picture: "Photo de profil",
            read_more: "Lire plus",
            search_placeholder: "Rechercher par nom...",
            search: "Rechercher",
            sort_by: "Trier par :",
            filter_by_status: "Filtrer par statut :",
            all_statuses: "Tous"
        },
        en: {
            home: "Home",
            profile_management: "Profile Management",
            reservations: "Reservations",
            complaints: "Complaints",
            bikes_batteries: "Bikes & Batteries",
            repair_issues: "Repair Issues",
            forum_admin: "Forum Management",
            logout: "Logout",
            dark_mode: "Dark Mode",
            language: "English",
            posts: "Posts",
            comments: "Comments",
            title: "Title",
            author: "Author",
            content: "Content",
            created_at: "Creation Date",
            reported: "Reported",
            not_reported: "Not Reported",
            deleted: "Deleted",
            actions: "Actions",
            report: "Report",
            unreport: "Unreport",
            details: "Details",
            user_details: "User Details",
            close: "Close",
            loading: "Loading...",
            error: "Error",
            name: "Name",
            first_name: "First Name",
            email: "Email",
            phone: "Phone",
            age: "Age",
            governorate: "Governorate",
            cin: "ID Card",
            profile_picture: "Profile Picture",
            read_more: "Read More",
            search_placeholder: "Search by name...",
            search: "Search",
            sort_by: "Sort by:",
            filter_by_status: "Filter by status:",
            all_statuses: "All"
        }
    };

    let currentLanguage = localStorage.getItem('language') || 'fr';

    function updateTranslations() {
        document.querySelectorAll('[data-translate]').forEach(element => {
            const key = element.getAttribute('data-translate');
            if (translations[currentLanguage][key]) {
                if (element.tagName === 'BUTTON' || element.tagName === 'A') {
                    const icon = element.querySelector('i') ? element.querySelector('i').outerHTML + ' ' : '';
                    element.innerHTML = icon + translations[currentLanguage][key];
                } else if (element.classList.contains('read-more')) {
                    element.textContent = translations[currentLanguage][key];
                } else {
                    element.textContent = translations[currentLanguage][key];
                }
            }
        });
        document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
            const key = element.getAttribute('data-translate-placeholder');
            if (translations[currentLanguage][key]) {
                element.placeholder = translations[currentLanguage][key];
            }
        });
        document.getElementById('toggle-language').innerHTML = `<i class="fas fa-globe"></i> ${translations[currentLanguage].language}`;
        document.title = translations[currentLanguage]['forum_admin'] || 'Forum Admin - Green.tn';
    }

    document.getElementById('toggle-language').addEventListener('click', () => {
        currentLanguage = currentLanguage === 'fr' ? 'en' : 'fr';
        localStorage.setItem('language', currentLanguage);
        updateTranslations();
    });

    updateTranslations();
});
</script>
</body>
</html>