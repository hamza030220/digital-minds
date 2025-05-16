<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../CONFIG/db.php';


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
    
    <link href="../assets/css/add_trajet.css" rel="stylesheet">
</head>

<body>
<?php 
        $basePath = '../';
        $currentPage = 'trajets';

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
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="description" name="description" 
                                               value="<?php echo htmlspecialchars($description); ?>">

                                    </div>
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
                                <div class="input-group mb-2">
                                    <textarea class="form-control" id="route_description" name="route_description" rows="4" required><?php echo htmlspecialchars($route_description); ?></textarea>
                                    <button type="button" class="btn btn-outline-primary" id="generate_ai_route_btn" title="Générer la description détaillée par IA">
                                        <i class="bi bi-robot"></i>
                                    </button>
                                </div>
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
    <script>
document.getElementById('generate_ai_route_btn').addEventListener('click', function() {
    const startName = document.getElementById('start_point_name').value;
    const endName = document.getElementById('end_point_name').value;
    const routeCoords = document.getElementById('route_coordinates').value;

    if (!startName || !endName || !routeCoords) {
        alert('Veuillez d\'abord renseigner le point de départ, d\'arrivée et tracer l\'itinéraire.');
        return;
    }

    try {
        const fallbacks = [
            `Ce trajet entre ${startName} et ${endName} met en avant la mobilité durable. Parcourez des paysages naturels, découvrez des points d'intérêt locaux et profitez d'un itinéraire respectueux de l'environnement.`,
            `Découvrez un itinéraire écologique reliant ${startName} à ${endName}, ponctué de paysages remarquables et d'initiatives vertes.`,
            `Ce parcours entre ${startName} et ${endName} valorise la nature, la biodiversité et la mobilité douce.`,
            `Voyagez de ${startName} à ${endName} en empruntant un itinéraire pensé pour minimiser l'empreinte carbone et maximiser la découverte.`,
            `Entre ${startName} et ${endName}, ce trajet propose une expérience immersive au cœur de la nature et des solutions de mobilité verte.`,
            `L'itinéraire entre ${startName} et ${endName} traverse des zones naturelles protégées et met en lumière les efforts locaux pour la durabilité.`,
            `Ce trajet relie ${startName} à ${endName} en passant par des paysages variés, des espaces verts et des initiatives écologiques.`,
            `De ${startName} à ${endName}, profitez d'un parcours où chaque kilomètre favorise la préservation de l'environnement.`,
            `Laissez-vous guider de ${startName} à ${endName} sur un chemin où la mobilité douce et la découverte de la biodiversité sont à l'honneur.`,
            `Ce trajet entre ${startName} et ${endName} est conçu pour sensibiliser à l'écologie tout en offrant une expérience agréable et enrichissante.`,
            `Partez de ${startName} vers ${endName} en explorant des sentiers pittoresques et des initiatives écologiques locales.`,
            `L'itinéraire reliant ${startName} à ${endName} favorise la découverte de la faune et de la flore régionales.`,
            `Ce trajet de ${startName} à ${endName} met l'accent sur la mobilité verte et la préservation des espaces naturels.`,
            `Découvrez la beauté naturelle entre ${startName} et ${endName} à travers un parcours respectueux de l'environnement.`,
            `De ${startName} à ${endName}, ce trajet vous invite à adopter une démarche écoresponsable tout en profitant du paysage.`,
            `Laissez-vous surprendre par la diversité des paysages entre ${startName} et ${endName}, tout en réduisant votre empreinte carbone.`,
            `Ce parcours entre ${startName} et ${endName} est idéal pour les amateurs de nature et de mobilité douce.`,
            `Voyagez de façon durable entre ${startName} et ${endName} grâce à cet itinéraire pensé pour l'écologie.`,
            `Entre ${startName} et ${endName}, profitez d'un trajet ponctué de points d'intérêt naturels et d'initiatives vertes.`,
            `Ce trajet relie ${startName} à ${endName} en mettant en avant la protection de l'environnement et la découverte locale.`
        ];

        // Sélection aléatoire d'une description
        const randomIndex = Math.floor(Math.random() * fallbacks.length);
        const generated = fallbacks[randomIndex];
        document.getElementById('route_description').value = generated;
    } catch (error) {
        console.error('Erreur lors de la génération IA :', error);
        // Vous pouvez afficher un message plus discret ou ne rien afficher
    }
});
</script>
</body>
</html>

