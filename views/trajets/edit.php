<?php
require_once '../../includes/config.php';
requireLogin();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

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
        $stmt = $pdo->prepare("SELECT * FROM trajets WHERE id = ?");
        $stmt->execute([$id]);
        $trajet = $stmt->fetch();
        
        if (!$trajet) {
            header("Location: list.php");
            exit();
        }
        
        $description = $trajet['description'] ?? '';
        $route_description = $trajet['route_description'] ?? '';
        $start_point_name = $trajet['start_point_name'] ?? '';
        $end_point_name = $trajet['end_point_name'] ?? '';
        $start_point = $trajet['start_point'] ?? '';
        $end_point = $trajet['end_point'] ?? '';
        $distance = $trajet['distance'] ?? '';
        $route_coordinates = $trajet['route_coordinates'] ?? '';
        $co2_saved = $trajet['co2_saved'] ?? '';
        $battery_energy = $trajet['battery_energy'] ?? '';
        $fuel_saved = $trajet['fuel_saved'] ?? '';
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed');
        }
        
        $id = (int)$_POST['id'];
        $description = trim($_POST['description'] ?? '');
        $route_description = trim($_POST['route_description'] ?? '');
        $start_point_name = trim($_POST['start_point_name'] ?? '');
        $end_point_name = trim($_POST['end_point_name'] ?? '');
        $start_point = trim($_POST['start_point'] ?? '');
        $end_point = trim($_POST['end_point'] ?? '');
        $distance = trim($_POST['distance'] ?? '');
        $route_coordinates = trim($_POST['route_coordinates'] ?? '');

        $distance_float = floatval($distance);
        $co2_saved = $distance_float > 0 ? $distance_float * 160 : 0;
        $battery_energy = $distance_float > 0 ? $distance_float * 5.6 : 0;
        $fuel_saved = $distance_float > 0 ? $distance_float * 0.075 : 0;

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
    error_log("Error updating trajet: " . $e->getMessage());
    $error = "Une erreur est survenue lors de la modification du trajet.";
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Trajet - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/assets/css/styles.css" rel="stylesheet">
    <link href="../../public/assets/css/edit_trajet.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" rel="stylesheet">
</head>
<body class="d-flex">
    <?php
    $basePath = '../../';
    $currentPage = 'trajets';
    include '../../includes/sidbar.php';
    ?>

    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-sm border-success">
                        <div class="card-header bg-success text-white d-flex align-items-center">
                            <i class="bi bi-pencil me-2" style="font-size:1.5em"></i>
                            <h2 class="h5 mb-0">Modifier le Trajet</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success d-flex align-items-center">
                                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="trajetForm" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                <input type="hidden" name="route_coordinates" id="route_coordinates" value="<?php echo htmlspecialchars($route_coordinates); ?>">
                                <input type="hidden" name="start_point" id="start_point" value="<?php echo htmlspecialchars($start_point); ?>">
                                <input type="hidden" name="end_point" id="end_point" value="<?php echo htmlspecialchars($end_point); ?>">
                                <div class="section-title"><i class="bi bi-info-circle me-2"></i>Informations générales</div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="description" class="form-label">Titre du trajet</label>
                                        <input type="text" class="form-control" id="description" name="description" 
                                               value="<?php echo htmlspecialchars($description); ?>" required>
                                        <div class="form-text">Exemple: "Trajet Plage - Centre-ville"</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="distance" class="form-label">Distance (km)</label>
                                        <input type="number" class="form-control" id="distance" name="distance" 
                                               value="<?php echo htmlspecialchars($distance); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Diminution estimée de CO₂ (g)</label>
                                        <input type="text" class="form-control colored-field border-co2" name="co2_saved" id="co2_saved" 
                                               value="<?php echo htmlspecialchars($co2_saved); ?>" readonly>
                                        <div class="form-text">Calculé automatiquement (~160g/km)</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Énergie stockée dans la batterie (Wh)</label>
                                        <input type="text" class="form-control colored-field border-battery" name="battery_energy" id="battery_energy" 
                                               value="<?php echo htmlspecialchars($battery_energy); ?>" readonly>
                                        <div class="form-text">Calculé automatiquement (5,6 Wh/km)</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Carburant économisé (L)</label>
                                        <input type="text" class="form-control colored-field border-fuel" name="fuel_saved" id="fuel_saved" 
                                               value="<?php echo htmlspecialchars($fuel_saved); ?>" readonly>
                                        <div class="form-text">Calculé automatiquement (~0,075 L/km)</div>
                                    </div>
                                </div>
                                <div class="section-title"><i class="bi bi-geo-alt me-2"></i>Points de départ et d'arrivée</div>
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
                                <div class="section-title"><i class="bi bi-map me-2"></i>Tracer l'itinéraire</div>
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
                                    <p class="mb-0"><i class="bi bi-info-circle me-2"></i><strong>Instructions:</strong></p>
                                    <ol class="mb-0 mt-2">
                                        <li>Placez d'abord le point de départ et d'arrivée en utilisant les boutons ci-dessous.</li>
                                        <li>Ensuite, utilisez l'outil de dessin pour tracer l'itinéraire entre ces points.</li>
                                        <li>Double-cliquez pour terminer le traçage d'une ligne.</li>
                                    </ol>
                                </div>
                                <div class="map-control-buttons mt-2">
                                    <button type="button" id="start_point_btn" class="btn btn-outline-success">
                                        <i class="bi bi-geo-alt-fill me-2"></i>Placer le point de départ
                                    </button>
                                    <button type="button" id="end_point_btn" class="btn btn-outline-danger">
                                        <i class="bi bi-geo-alt-fill me-2"></i>Placer le point d'arrivée
                                    </button>
                                    <button type="button" id="draw_route_btn" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil me-2"></i>Tracer l'itinéraire
                                    </button>
                                </div>
                                <div id="map" style="height: 400px; margin-top: 10px;"></div>
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Retour à la liste</a>
                                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-2"></i>Enregistrer les modifications</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="../../public/assets/js/scripts_trajets_edit.js"></script>
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
                    `Entre ${startName} et ${endName}, ce trajet propose une expérience immersive au cœur de la nature et des solutions de mobilité verte.`
                ];

                const randomIndex = Math.floor(Math.random() * fallbacks.length);
                const generated = fallbacks[randomIndex];
                document.getElementById('route_description').value = generated;
            } catch (error) {
                console.error('Erreur lors de la génération IA :', error);
            }
        });
    </script>
    <style>
        .btn-active {
            background-color: #3a9856;
            color: white;
        }
        .map-control-buttons .btn {
            margin-right: 10px;
        }
        .colored-field.border-co2 {
            border-left: 4px solid #28a745;
        }
        .colored-field.border-battery {
            border-left: 4px solid #007bff;
        }
        .colored-field.border-fuel {
            border-left: 4px solid #dc3545;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin: 1.5rem 0 1rem;
            color: #3a9856;
        }
        .map-instructions {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</body>
</html>