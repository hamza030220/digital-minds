<?php
require_once '../../../CONFIG/db.php';

// Add the function definition here
function getCityNameFromCoordsOSM($coords) {
    list($lat, $lng) = explode(',', $coords);
    $lat = trim($lat);
    $lng = trim($lng);

    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=10&addressdetails=1&accept-language=fr";
    $opts = [
        "http" => [
            "header" => "User-Agent: GreenAdmin/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);

    $json = @file_get_contents($url, false, $context);
    if ($json === false) {
        return 'Inconnu';
    }
    $data = json_decode($json, true);
    // Return the governorate (state) if available
    if (isset($data['address']['state'])) {
        return $data['address']['state'];
    }
    // Fallbacks if state is not available
    if (isset($data['address']['county'])) {
        return $data['address']['county'];
    }
    return 'Inconnu';
}

// Require login


// Initialize variables
$error = '';
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify station exists
try {
    $pdo = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
        $stmt->execute([$id]);
        $station = $stmt->fetch();
        
        if (!$station) {
            header("Location: list.php");
            exit();
        }
        
        $name = $station['name'];
        $location = $station['location'];
        $status = $station['status'];
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token

        
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $location = trim($_POST['location']);
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        
        // Get city name from coordinates
        $city = getCityNameFromCoordsOSM($location);

        // Validate input
        if (empty($name)) {
            $error = "Le nom de la station est requis.";
        } elseif (empty($location)) {
            $error = "L'emplacement de la station est requis.";
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $error = "Statut invalide.";
        } else {
            // Check if name exists for other stations
            $stmt = $pdo->prepare("SELECT id FROM stations WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                $error = "Une autre station avec ce nom existe déjà.";
            } else {
                // Update station with city
                $stmt = $pdo->prepare("UPDATE stations SET name = ?, location = ?, city = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $location, $city, $status, $id]);
                
                $success = "Station mise à jour avec succès.";
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Une erreur est survenue lors de la modification de la station.";
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Station - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <script src="/projetweb/theme.js" defer></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
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

        /* Main Content */
        .main-content {
            margin-left: 0;
            padding: 40px;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            transition: margin-left 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .main-content-expanded {
            margin-left: 250px;
        }

        /* Sidebar Toggle Button */
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

        /* Responsive */
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
        }

        /* Dark Mode */
        body.dark-mode {
            background: linear-gradient(45deg, #1a3c34, #2c3e50, #34495e, #2e7d32);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            color: #e0e0e0;
        }

        body.dark-mode .sidebar {
            background-color: rgba(51, 51, 51, 0.9);
        }

        body.dark-mode .sidebar-nav-link {
            color: #ffffff;
        }

        body.dark-mode .sidebar-nav-link:hover {
            background-color: #444444;
        }

        body.dark-mode .main-content {
            background: rgba(30, 30, 30, 0.8);
        }

        body.dark-mode .main-content h1 {
            color: #4caf50;
        }

        body.dark-mode .section-content,
        body.dark-mode .stat-card,
        body.dark-mode #userChart,
        body.dark-mode #ageChart {
            background-color: rgba(50, 50, 50, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .task-bar {
            background-color: #1b5e20;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .translate-btn {
            background-color: #388e3c;
        }

        body.dark-mode .translate-btn:hover {
            background-color: #4caf50;
        }

        body.dark-mode .user-table {
            background-color: rgba(50, 50, 50, 0.9);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }

        body.dark-mode .user-table th {
            background-color: rgba(56, 142, 60, 0.95);
            color: #ffffff;
        }

        body.dark-mode .user-table td {
            border-color: rgba(76, 175, 80, 0.5);
            color: #ffffff;
        }
    </style>
</head>
<body>
    <?php 
        $basePath = '../';
        $currentPage = 'stations';
    ?>

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
                    <a class="sidebar-nav-link" href="?page=gestion_utilisateurs" data-translate="profile_management">
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
                <a class="sidebar-nav-link" href="<?php echo $basePath; ?>../../VIEW/reclamation/reclamations_utilisateur.php" data-translate="complaints">
                    <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                    <span class="sidebar-nav-text">Réclamations</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="../../reclamation/liste_avis.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-star"></i></span>
                        <span class="sidebar-nav-text"> Avis</span>
                    </a>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="velos.php?super_admin=1" data-translate="bikes_batteries">
                        <span class="sidebar-nav-icon"><i class="bi bi-bicycle"></i></span>
                        <span class="sidebar-nav-text">Vélos & Batteries</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>forum_admin.php" data-translate="forum">
                        <span class="sidebar-nav-icon"><i class="bi bi-chat"></i></span>
                        <span class="sidebar-nav-text">Forum</span>
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
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link" href="repairs.html" data-translate="repair_issues">
                            <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                            <span class="sidebar-nav-text">Réparer les pannes</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link" href="update_profil_admin.php" data-translate="profile_management">
                            <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                            <span class="sidebar-nav-text">Editer mon profil</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="sidebar-footer">
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
    <div id="main" class="main-content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h2 class="h5 mb-0">Modifier la Station</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de la station</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($name); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="location" class="form-label">Emplacement</label>
                                    <div id="map" style="height: 300px; margin-bottom: 10px;"></div>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($location); ?>" readonly>
                                    <small class="text-muted">Cliquez sur la carte pour sélectionner l'emplacement</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Statut</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Actif</option>
                                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                                    </select>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="list.php" class="btn btn-secondary">Retour à la liste</a>
                                    <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/scripts_station_edit.js"></script>
</body>
</html>

