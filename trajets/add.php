<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
requireLogin();

// Set secure headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Initialize variables
$error = '';
$success = '';
$description = '';
$route_description = '';
$start_point_name = '';
$end_point_name = '';
$start_point = '';
$end_point = '';
$distance = '';
$route_coordinates = '';
// Ajout des nouvelles variables
$co2_saved = '';
$battery_energy = '';
$fuel_saved = '';

// Check and update database structure if needed
function ensureTableStructure($pdo) {
    $columnCheckStmt = $pdo->query("SHOW COLUMNS FROM trajets LIKE 'start_point'");
    if ($columnCheckStmt->rowCount() == 0) {
        $pdo->exec("
            ALTER TABLE trajets 
            ADD COLUMN start_point VARCHAR(255) NULL,
            ADD COLUMN end_point VARCHAR(255) NULL,
            ADD COLUMN start_point_name VARCHAR(255) NULL,
            ADD COLUMN end_point_name VARCHAR(255) NULL
        ");
    }
    // Vérifier et ajouter les nouvelles colonnes si besoin
    $columns = ['co2_saved', 'battery_energy', 'fuel_saved'];
    foreach ($columns as $col) {
        $check = $pdo->query("SHOW COLUMNS FROM trajets LIKE '$col'");
        if ($check->rowCount() == 0) {
            if ($col == 'co2_saved') {
                $pdo->exec("ALTER TABLE trajets ADD COLUMN co2_saved FLOAT NULL");
            } elseif ($col == 'battery_energy') {
                $pdo->exec("ALTER TABLE trajets ADD COLUMN battery_energy FLOAT NULL");
            } elseif ($col == 'fuel_saved') {
                $pdo->exec("ALTER TABLE trajets ADD COLUMN fuel_saved FLOAT NULL");
            }
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize input
        $description = trim($_POST['description']);
        $route_description = trim($_POST['route_description']);
        $start_point_name = isset($_POST['start_point_name']) ? trim($_POST['start_point_name']) : '';
        $end_point_name = isset($_POST['end_point_name']) ? trim($_POST['end_point_name']) : '';
        $start_point = isset($_POST['start_point']) ? trim($_POST['start_point']) : '';
        $end_point = isset($_POST['end_point']) ? trim($_POST['end_point']) : '';
        $distance = isset($_POST['distance']) ? trim($_POST['distance']) : '';
        $route_coordinates = isset($_POST['route_coordinates']) ? trim($_POST['route_coordinates']) : '';

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
            $pdo = getDBConnection();
            ensureTableStructure($pdo);

            // Calculate the new values based on distance
            $distance_float = floatval($distance);
            $co2_saved = $distance_float * 160; // CO2 saved in grams
            $battery_energy = $distance_float * 5.6; // Battery energy in Wh
            $fuel_saved = $distance_float * 0.075; // Fuel saved in liters

            $pdo->exec("ALTER TABLE trajets MODIFY start_station_id INT NULL, MODIFY end_station_id INT NULL");
            
            $stmt = $pdo->prepare("
                INSERT INTO trajets 
                (distance, description, route_coordinates, route_description, 
                 start_point, end_point, start_point_name, end_point_name,
                 start_station_id, end_station_id,
                 co2_saved, battery_energy, fuel_saved) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?)
            ");
            $stmt->execute([
                $distance, 
                $description, 
                $route_coordinates, 
                $route_description,
                $start_point,
                $end_point,
                $start_point_name,
                $end_point_name,
                $co2_saved,
                $battery_energy,
                $fuel_saved
            ]);
            $success = "Trajet ajouté avec succès.";

            // Reset form
            $description = '';
            $route_description = '';
            $start_point_name = '';
            $end_point_name = '';
            $start_point = '';
            $end_point = '';
            $route_coordinates = '';
            $co2_saved = '';
            $battery_energy = '';
            $fuel_saved = '';
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "Une erreur est survenue lors de l'ajout du trajet: " . $e->getMessage();
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Trajet - Green Admin</title>
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
    
    <style>
        body {
            background: #f4f6f9;
        }
        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        .card {
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        #map {
            height: 500px;
            width: 100%;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid #60BA97;
        }
        .map-instructions {
            margin-bottom: 10px;
            padding: 12px 18px;
            background-color: #eafaf3;
            border-radius: 6px;
            border-left: 5px solid #60BA97;
            font-size: 1rem;
        }
        .coords-preview {
            font-family: monospace;
            font-size: 0.9em;
            color: #6c757d;
            max-height: 100px;
            overflow-y: auto;
        }
        .map-control-buttons {
            margin-bottom: 15px;
            display: flex;
            gap: 12px;
        }
        .map-control-buttons button {
            padding: 10px 20px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        .map-control-buttons button.active, .map-control-buttons button:hover {
            background-color: #60BA97;
            color: white;
            border-color: #60BA97;
        }
        .marker-info {
            margin-top: 5px;
            font-size: 0.95em;
            color: #198754;
        }
        .form-label {
            font-weight: 500;
        }
        .form-text {
            font-size: 0.95em;
        }
        .alert {
            font-size: 1.05em;
        }
        .section-title {
            font-size: 1.15em;
            font-weight: 600;
            color: #198754;
            margin-bottom: 0.5em;
        }
        .colored-field:read-only {
            background: #fff;
            font-weight: bold;
            border-width: 2.5px;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            font-size: 1.15em;
            box-shadow: none;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .border-co2:read-only {
            border-color: #ffe066 !important;
            box-shadow: 0 2px 8px 0 #ffe06633;
        }
        .border-battery:read-only {
            border-color: #63c2de !important;
            box-shadow: 0 2px 8px 0 #63c2de33;
        }
        .border-fuel:read-only {
            border-color: #ff6f6f !important;
            box-shadow: 0 2px 8px 0 #ff6f6f33;
        }
        .colored-field.bg-warning:read-only {
            background: linear-gradient(90deg, #fffbe6 60%, #ffe066 100%);
            color: #856404;
        }
        .colored-field.bg-info:read-only {
            background: linear-gradient(90deg, #e3f6fd 60%, #63c2de 100%);
            color: #0c5460;
        }
        .colored-field.bg-danger:read-only {
            background: linear-gradient(90deg, #ffeaea 60%, #ff6f6f 100%);
            color: #721c24;
        }
    </style>
</head>

<body>
<?php 
        $basePath = '../';
        $currentPage = 'trajets';
        include '../includes/sidbar.php';
    ?>
    <div class="container mt-4">
        <!-- Breadcrumb navigation -->

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-success">
                    <div class="card-header bg-success text-white d-flex align-items-center">
                        <i class="bi bi-plus-circle me-2" style="font-size:1.5em"></i>
                        <h2 class="h5 mb-0">Ajouter un Trajet</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="trajetForm" novalidate>
                            <div class="section-title"><i class="bi bi-info-circle"></i> Informations générales</div>
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
                            <div class="section-title"><i class="bi bi-geo-alt"></i> Points de départ et d'arrivée</div>
                            <div class="row mb-3">
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
                            </div>
                            <div class="section-title"><i class="bi bi-map"></i> Tracer l'itinéraire</div>
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
                            <input type="hidden" name="route_coordinates" id="route_coordinates" value="<?php echo htmlspecialchars($route_coordinates); ?>">
                            <input type="hidden" name="start_point" id="start_point" value="<?php echo htmlspecialchars($start_point); ?>">
                            <input type="hidden" name="end_point" id="end_point" value="<?php echo htmlspecialchars($end_point); ?>">
                            <div class="d-flex justify-content-between mt-4">
                                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour à la liste</a>
                                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Enregistrer le trajet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/scripts_trajets_add.js"></script>
    
</body>
</html>

