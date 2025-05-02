<?php
session_start();
require_once __DIR__ . '/models/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT prenom, nom FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

try {
    // Paramètres de pagination
    $velos_per_page = 5;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $velos_per_page;

    // Récupérer les vélos avec recherche et tri
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id_velo';
    $valid_sort_columns = ['id_velo', 'nom_velo', 'prix_par_jour', 'disponibilite'];
    if (!in_array($sort_by, $valid_sort_columns)) {
        $sort_by = 'id_velo';
    }

    // Compter le nombre total de vélos
    $sql_count = "SELECT COUNT(*) FROM velos WHERE nom_velo LIKE :search OR type_velo LIKE :search";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute(['search' => "%$search%"]);
    $total_velos = $stmt_count->fetchColumn();
    $total_pages = ceil($total_velos / $velos_per_page);

    // Récupérer les vélos
    $sql = "SELECT * FROM velos 
            WHERE nom_velo LIKE :search OR type_velo LIKE :search 
            ORDER BY $sort_by ASC 
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $velos_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $velos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques
    $stat_available = $pdo->query("SELECT COUNT(*) FROM velos WHERE disponibilite = 1")->fetchColumn();
    $stat_unavailable = $pdo->query("SELECT COUNT(*) FROM velos WHERE disponibilite = 0")->fetchColumn();
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vélos - Green.tn</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            font-size: 15px;
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
        }
        .search-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
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
        .sort-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sort-container select {
            padding: 8px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ffffff;
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
            padding: 8px;
            font-size: 14px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #388e3c;
        }
        .btn.supprimer {
            background-color: #e74c3c;
        }
        .btn.supprimer:hover {
            background-color: #c0392b;
        }
        .btn.modifier {
            background-color: #3498db;
        }
        .btn.modifier:hover {
            background-color: #2980b9;
        }
        .btn.details {
            background-color: #60BA97;
        }
        .btn.details:hover {
            background-color: #1b5e20;
        }
        .btn.trash {
            background-color: #f39c12;
        }
        .btn.trash:hover {
            background-color: #e67e22;
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .velo-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }
        .velo-table th, .velo-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(76, 175, 80, 0.5);
            color: #000000;
        }
        .velo-table th {
            background-color: rgba(96, 186, 151, 0.95);
            font-weight: bold;
        }
        .velo-table td {
            background-color: transparent;
        }
        .velo-table tr:last-child td {
            border-bottom: none;
        }
        .status.available {
            color: #4CAF50;
            font-weight: bold;
        }
        .status.unavailable {
            color: #e63946;
            font-weight: bold;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination .btn.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            min-width: 200px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            border-left: 5px solid #4caf50;
            backdrop-filter: blur(5px);
        }
        .stat-card h3 {
            margin: 0;
            font-size: 16px;
            color: #000000;
        }
        .stat-card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: bold;
            color: #000000;
        }
        .chart-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        #velosChart {
            max-width: 300px;
            max-height: 300px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        body.dark-mode {
            background: linear-gradient(45deg, #1a3c34, #2c3e50, #34495e, #2e7d32);
            color: #e0e0e0;
        }
        body.dark-mode .sidebar {
            background-color: rgba(51, 51, 51, 0.9);
        }
        body.dark-mode .sidebar-nav-link {
            color: #ffffff;
        }
        body.dark-mode .main-content, body.dark-mode .section-content, body.dark-mode .stat-card, body.dark-mode #velosChart {
            background-color: rgba(50, 50, 50, 0.9);
        }
        body.dark-mode .task-bar {
            background-color: #1b5e20;
        }
        body.dark-mode .velo-table {
            background-color: rgba(50, 50, 50, 0.9);
        }
        body.dark-mode .velo-table th {
            background-color: rgba(56, 142, 60, 0.95);
            color: #ffffff;
        }
        body.dark-mode .velo-table td {
            color: #ffffff;
        }
        @media (max-width: 992px) {
            .sidebar { left: -250px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
            .task-bar { flex-direction: column; gap: 10px; }
            .search-container, .sort-container, .translate-container { width: 100%; justify-content: center; }
            .search-input, .sort-container select { width: 100%; max-width: 200px; }
        }
        @media (max-width: 768px) {
            .stats-container { flex-direction: column; align-items: center; }
            .stat-card { width: 100%; max-width: 300px; }
            .btn-container { flex-direction: column; align-items: center; }
            .btn-container .btn { width: 100%; max-width: 200px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a class="sidebar-brand" href="dashboard.php?section=stats">
                <img src="logo.jpg" alt="Green.tn">
            </a>
        </div>
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="dashboard.php?section=stats" data-translate="home">
                        <span class="sidebar-nav-icon"><i class="bi bi-house-door"></i></span>
                        <span class="sidebar-nav-text">Accueil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="dashboard.php?page=gestion_utilisateurs" data-translate="profile_management">
                        <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                        <span class="sidebar-nav-text">Gestion de votre profil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="reservation.php" data-translate="reservations">
                        <span class="sidebar-nav-icon"><i class="bi bi-calendar"></i></span>
                        <span class="sidebar-nav-text">voir réservations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="reclamation.php" data-translate="complaints">
                        <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                        <span class="sidebar-nav-text">Réclamations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link active" href="velos.php?super_admin=1" data-translate="bikes_batteries">
                        <span class="sidebar-nav-icon"><i class="bi bi-bicycle"></i></span>
                        <span class="sidebar-nav-text">Vélos & Batteries</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link" href="repair_panne.php" data-translate="repair_issues">
                            <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                            <span class="sidebar-nav-text">Réparer les pannes</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="sidebar-footer">
            <div class="mb-2">
                <span class="text-white">Bienvenue, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
            </div>
            <a href="logout.php" class="btn btn-outline-light" data-translate="logout">
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
        <h1>Vélos</h1>

        <div class="section-content">
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Vélos disponibles</h3>
                    <p><?php echo $stat_available; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Vélos non disponibles</h3>
                    <p><?php echo $stat_unavailable; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total des vélos</h3>
                    <p><?php echo $total_velos; ?></p>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container">
                <canvas id="velosChart"></canvas>
            </div>

            <!-- Task Bar -->
            <div class="task-bar">
                <form method="get" class="search-container">
                    <label for="search" class="search-label" data-translate="search"><i class="bi bi-search"></i> Rechercher :</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom ou type de vélo" class="search-input" data-translate-placeholder="search_velos">
                    <label for="sort_by" class="search-label" data-translate="sort_by"><i class="bi bi-sort-alpha-down"></i> Trier par :</label>
                    <select name="sort_by" id="sort_by" class="search-input" onchange="this.form.submit()">
                        <option value="id_velo" <?php echo $sort_by == 'id_velo' ? 'selected' : ''; ?> data-translate="id">ID Vélo</option>
                        <option value="nom_velo" <?php echo $sort_by == 'nom_velo' ? 'selected' : ''; ?> data-translate="name">Nom</option>
                        <option value="prix_par_jour" <?php echo $sort_by == 'prix_par_jour' ? 'selected' : ''; ?> data-translate="price">Prix par jour</option>
                        <option value="disponibilite" <?php echo $sort_by == 'disponibilite' ? 'selected' : ''; ?> data-translate="availability">Disponibilité</option>
                    </select>
                    <button type="submit" class="btn" data-translate="search"><i class="bi bi-search"></i> Rechercher</button>
                </form>
                <div class="translate-container">
                    <button class="translate-btn" id="toggle-language" data-translate="language"><i class="fas fa-globe"></i> Français</button>
                    <a href="corbeille.php" class="btn trash" data-translate="trash"><i class="bi bi-trash"></i> Corbeille</a>
                </div>
            </div>

            <!-- Buttons: Ajouter un vélo and Voir Réservations -->
            <div class="btn-container">
                <a href="ajouter_velo.php" class="btn" data-translate="add_bike"><i class="bi bi-plus"></i> Ajouter un vélo</a>
                <a href="reservation.php" class="btn" data-translate="view_reservations"><i class="bi bi-calendar"></i> Voir Réservations</a>
            </div>

            <!-- Table -->
            <table class="velo-table">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash"></i> <span data-translate="id">ID</span></th>
                        <th><i class="bi bi-bicycle"></i> <span data-translate="name">Nom</span></th>
                        <th><i class="bi bi-tags"></i> <span data-translate="type">Type</span></th>
                        <th><i class="bi bi-currency-euro"></i> <span data-translate="price">Prix par jour</span></th>
                        <th><i class="bi bi-check-circle"></i> <span data-translate="availability">Disponibilité</span></th>
                        <th><i class="bi bi-gear"></i> <span data-translate="actions">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($velos as $velo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($velo['id_velo']); ?></td>
                        <td><?php echo htmlspecialchars($velo['nom_velo']); ?></td>
                        <td><?php echo htmlspecialchars($velo['type_velo']); ?></td>
                        <td><?php echo number_format($velo['prix_par_jour'], 2, '.', ''); ?> €</td>
                        <td class="status <?php echo $velo['disponibilite'] ? 'available' : 'unavailable'; ?>">
                            <?php echo $velo['disponibilite'] ? 'Disponible' : 'Non disponible'; ?>
                        </td>
                        <td>
                            <a href="modifier_velo.php?id=<?php echo $velo['id_velo']; ?>" class="btn modifier" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <a href="supprimer_velo.php?id=<?php echo $velo['id_velo']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir déplacer ce vélo dans la corbeille ?');" class="btn supprimer" title="Supprimer"><i class="bi bi-trash"></i></a>
                            <a href="details.php?velo=<?php echo $velo['id_velo']; ?>" class="btn details" title="Détails"><i class="bi bi-info-circle"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>" class="btn <?php echo $current_page <= 1 ? 'disabled' : ''; ?>" title="Page précédente"><i class="bi bi-arrow-left"></i></a>
                <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>" class="btn <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>" title="Page suivante"><i class="bi bi-arrow-right"></i></a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Sidebar toggle
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

            // Dark Mode
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

            // Chart
            const ctx = document.getElementById('velosChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Disponibles', 'Non disponibles'],
                    datasets: [{
                        label: 'Statistiques des vélos',
                        data: [<?php echo $stat_available; ?>, <?php echo $stat_unavailable; ?>],
                        backgroundColor: ['#4CAF50', '#e63946']
                    }]
                },
                options: {
                    responsive: true
                }
            });

            // Translation
            const translations = {
                fr: {
                    home: "Accueil",
                    profile_management: "Gestion de votre profil",
                    reservations: "Réservations",
                    complaints: "Réclamations",
                    bikes_batteries: "Vélos & Batteries",
                    repair_issues: "Réparer les pannes",
                    logout: "Déconnexion",
                    dark_mode: "Mode Sombre",
                    search: "Rechercher",
                    sort_by: "Trier par :",
                    language: "Français",
                    id: "ID Vélo",
                    name: "Nom",
                    type: "Type",
                    price: "Prix par jour",
                    availability: "Disponibilité",
                    actions: "Actions",
                    search_velos: "Nom ou type de vélo",
                    trash: "Corbeille",
                    add_bike: "Ajouter un vélo",
                    view_reservations: "Voir Réservations"
                },
                en: {
                    home: "Home",
                    profile_management: "Profile Management",
                    reservations: "Reservations",
                    complaints: "Complaints",
                    bikes_batteries: "Bikes & Batteries",
                    repair_issues: "Repair Issues",
                    logout: "Logout",
                    dark_mode: "Dark Mode",
                    search: "Search",
                    sort_by: "Sort by:",
                    language: "English",
                    id: "Bike ID",
                    name: "Name",
                    type: "Type",
                    price: "Price per day",
                    availability: "Availability",
                    actions: "Actions",
                    search_velos: "Bike name or type",
                    trash: "Trash",
                    add_bike: "Add a Bike",
                    view_reservations: "View Reservations"
                }
            };

            let currentLanguage = localStorage.getItem('language') || 'fr';
            function updateTranslations() {
                document.querySelectorAll('[data-translate]').forEach(element => {
                    const key = element.getAttribute('data-translate');
                    if (translations[currentLanguage][key]) {
                        element.textContent = translations[currentLanguage][key];
                    }
                });
                document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
                    const key = element.getAttribute('data-translate-placeholder');
                    if (translations[currentLanguage][key]) {
                        element.placeholder = translations[currentLanguage][key];
                    }
                });
                document.getElementById('toggle-language').innerHTML = `<i class="fas fa-globe"></i> ${translations[currentLanguage].language}`;
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