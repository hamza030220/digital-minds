<?php
require_once '../../../CONFIG/db.php';

// Require login


// Initialize variables
$error = '';
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$description = '';
$route_description = '';
$start_point_name = '';
$end_point_name = '';
$start_point = '';
$end_point = '';
$distance = '';
$route_coordinates = '';
$co2_saved = '';
$battery_energy = '';
$fuel_saved = '';

try {
    $pdo = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get existing trajet data
        $stmt = $pdo->prepare("SELECT * FROM trajets WHERE id = ?");
        $stmt->execute([$id]);
        $trajet = $stmt->fetch();
        
        if (!$trajet) {
            header("Location: list.php");
            exit();
        }
        
        $description = $trajet['description'];
        $route_description = $trajet['route_description'] ?? '';
        $start_point_name = $trajet['start_point_name'] ?? '';
        $end_point_name = $trajet['end_point_name'] ?? '';
        $start_point = $trajet['start_point'] ?? '';
        $end_point = $trajet['end_point'] ?? '';
        $distance = $trajet['distance'];
        $route_coordinates = json_decode($trajet['route_coordinates'], true);
        $co2_saved = $trajet['co2_saved'] ?? '';
        $battery_energy = $trajet['battery_energy'] ?? '';
        $fuel_saved = $trajet['fuel_saved'] ?? '';
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token

        
        $id = (int)$_POST['id'];
        $description = trim($_POST['description']);
        $route_description = trim($_POST['route_description']);
        $start_point_name = trim($_POST['start_point_name']);
        $end_point_name = trim($_POST['end_point_name']);
        $start_point = trim($_POST['start_point']);
        $end_point = trim($_POST['end_point']);
        $distance = trim($_POST['distance']);
        $route_coordinates = trim($_POST['route_coordinates']);

        // Calculate the values server-side to ensure correctness
        $distance_float = floatval($distance);
        $co2_saved = $distance_float > 0 ? $distance_float * 160 : 0;
        $battery_energy = $distance_float > 0 ? $distance_float * 5.6 : 0;
        $fuel_saved = $distance_float > 0 ? $distance_float * 0.075 : 0;

        // Validate input
        if (empty($description)) {
            $error = "Le titre du trajet est requis.";
        } elseif (empty($route_description)) {
            $error = "La description du trajet est requise.";
        } elseif (empty($start_point_name)) {
            $error = "Le nom du point de départ est requis.";
        } elseif (empty($end_point_name)) {
            $error = "Le nom du point d'arrivée est requis.";
        } elseif (empty($start_point)) {
            $error = "Veuillez placer le point de départ sur la carte.";
        } elseif (empty($end_point)) {
            $error = "Veuillez placer le point d'arrivée sur la carte.";
        } elseif (!is_numeric($distance) || $distance <= 0) {
            $error = "La distance doit être un nombre positif.";
        } elseif (empty($route_coordinates)) {
            $error = "Veuillez tracer l'itinéraire sur la carte.";
        } else {
            // Update trajet
            $stmt = $pdo->prepare("
                UPDATE trajets SET 
                description = ?, 
                route_description = ?,
                start_point_name = ?,
                end_point_name = ?,
                start_point = ?,
                end_point = ?,
                distance = ?, 
                route_coordinates = ?,
                co2_saved = ?,
                battery_energy = ?,
                fuel_saved = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $description,
                $route_description,
                $start_point_name,
                $end_point_name,
                $start_point,
                $end_point,
                $distance,
                $route_coordinates,
                $co2_saved,
                $battery_energy,
                $fuel_saved,
                $id
            ]);
            
            $success = "Trajet mis à jour avec succès.";
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Une erreur est survenue lors de la modification du trajet.";
}

// Generate new CSRF token

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Trajet - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    

    <!-- Leaflet CSS and JS for OpenStreetMap -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Leaflet Draw plugin for drawing routes -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

<!-- Axios for making HTTP requests -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<link href="../assets/css/edit_trajet.css" rel="stylesheet">

   
</head>
<body>
    <?php
        $basePath = '../';
        $currentPage = 'trajets';

    ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h2 class="h5 mb-0">Modifier le Trajet</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="trajetForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                            <input type="hidden" name="route_coordinates" id="route_coordinates" value="<?php echo htmlspecialchars(json_encode($route_coordinates)); ?>">
                            <input type="hidden" name="start_point" id="start_point" value="<?php echo htmlspecialchars($start_point); ?>">
                            <input type="hidden" name="end_point" id="end_point" value="<?php echo htmlspecialchars($end_point); ?>">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="description" class="form-label">Titre du trajet</label>
                                    <input type="text" class="form-control" id="description" name="description" 
                                           value="<?php echo htmlspecialchars($description); ?>">
                                    <div class="form-text">Exemple: "Trajet Plage - Centre-ville"</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="distance" class="form-label">Distance (km)</label>
                                    <input type="number" class="form-control" id="distance" name="distance" 
                                           value="<?php echo htmlspecialchars($distance); ?>">
                                </div>
                            </div>
                            <!-- Add this block for the calculated fields -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Diminution estimée de CO₂ (g)</label>
                                    <input type="text" class="form-control colored-field border-co2" name="co2_saved" id="co2_saved" value="<?php echo htmlspecialchars($co2_saved); ?>" readonly>
                                    <div class="form-text">Calculé automatiquement (~160g/km)</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Énergie stockée dans la batterie (Wh)</label>
                                    <input type="text" class="form-control colored-field border-battery" name="battery_energy" id="battery_energy" value="<?php echo htmlspecialchars($battery_energy); ?>" readonly>
                                    <div class="form-text">Calculé automatiquement (5,6 Wh/km)</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Carburant économisé (L)</label>
                                    <input type="text" class="form-control colored-field border-fuel" name="fuel_saved" id="fuel_saved" value="<?php echo htmlspecialchars($fuel_saved); ?>" readonly>
                                    <div class="form-text">Calculé automatiquement (~0,075 L/km)</div>
                                </div>
                            </div>
                            <!-- End of added block -->
                            <div class="col-md-6">
                                <label for="start_point_name" class="form-label">Nom du point de départ</label>
                                <input type="text" class="form-control" id="start_point_name" name="start_point_name" 
                                    value="<?php echo htmlspecialchars($start_point_name); ?>" required>
                                <div class="form-text">Exemple: "Plage El Kantaoui"</div>
                                <div class="marker-info" id="start_point_info">
                                    <?php if ($start_point): ?>
                                        Point placé: <?php echo htmlspecialchars($start_point); ?>
                                    <?php else: ?>
                                        Aucun point de départ placé sur la carte
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="end_point_name" class="form-label">Nom du point d'arrivée</label>
                                <input type="text" class="form-control" id="end_point_name" name="end_point_name" 
                                    value="<?php echo htmlspecialchars($end_point_name); ?>" required>
                                <div class="form-text">Exemple: "Centre-ville de Sousse"</div>
                                <div class="marker-info" id="end_point_info">
                                    <?php if ($end_point): ?>
                                        Point placé: <?php echo htmlspecialchars($end_point); ?>
                                    <?php else: ?>
                                        Aucun point d'arrivée placé sur la carte
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="route_description" class="form-label">Description détaillée du trajet</label>
                                <textarea class="form-control" id="route_description" name="route_description" rows="4" required><?php echo htmlspecialchars($route_description); ?></textarea>
                                <div class="form-text">Détaillez le parcours, les points d'intérêt, difficultés, etc.</div>
                            </div>
                            
                            <div class="mb-1">
                                <label class="form-label">Tracer l'itinéraire sur la carte</label>
                            </div>
                            <div class="map-instructions">
                                <p class="mb-0"><i class="bi bi-info-circle"></i> <strong>Instructions:</strong></p>
                                <ol class="mb-0 mt-2">
                                    <li>Placez d'abord le point de départ et d'arrivée en utilisant les boutons ci-dessous.</li>
                                    <li>Ensuite, utilisez l'outil de dessin pour tracer l'itinéraire entre ces points.</li>
                                    <li>Double-cliquez pour terminer le traçage d'une ligne.</li>
                                </ol>
                            </div>
                            
                            <div class="map-control-buttons">
                                <button type="button" id="start_point_btn" class="btn">
                                    <i class="bi bi-geo-alt-fill text-success"></i> Placer le point de départ
                                </button>
                                <button type="button" id="end_point_btn" class="btn">
                                    <i class="bi bi-geo-alt-fill text-danger"></i> Placer le point d'arrivée
                                </button>
                                <button type="button" id="draw_route_btn" class="btn">
                                    <i class="bi bi-pencil"></i> Tracer l'itinéraire
                                </button>
                            </div>
                            
                            <div id="map"></div>
                            
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../assets/js/scripts_trajets_edit.js"></script>

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
</div>
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
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>stations/list.php">
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
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
