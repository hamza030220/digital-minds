<?php
require_once '../../includes/config.php';

// Add function to get city name from coordinates
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
    if (isset($data['address']['state'])) {
        return $data['address']['state'];
    }
    if (isset($data['address']['county'])) {
        return $data['address']['county'];
    }
    return 'Inconnu';
}

// Require login
requireLogin();

// Initialize variables
$error = '';
$success = '';
$name = '';
$location = '';
$status = 'active';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Get and sanitize input
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
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
        try {
            $pdo = getDBConnection();
            // Check if station name already exists
            $stmt = $pdo->prepare("SELECT id FROM stations WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                $error = "Une station avec ce nom existe déjà.";
            } else {
                // Insert new station with city
                $stmt = $pdo->prepare("INSERT INTO stations (name, location, city, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $location, $city, $status]);
                $success = "Station ajoutée avec succès.";
                $name = '';
                $location = '';
                $status = 'active';
            }
        } catch (PDOException $e) {
            error_log("Error adding station: " . $e->getMessage());
            $error = "Une erreur est survenue lors de l'ajout de la station.";
        }
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Station - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/assets/css/styles.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
</head>
<body class="d-flex">
    <?php 
    $basePath = '../../';
    $currentPage = 'stations';
    include '../../includes/sidbar.php';
    ?>

    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Ajouter une Station</h1>
                <a href="list.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h2 class="h5 mb-0">Ajouter une Station</h2>
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
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de la station</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($name); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="location" class="form-label">Emplacement (lat,lng)</label>
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
                                    <button type="submit" class="btn btn-success">Ajouter la station</button>
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
    <script src="../../public/assets/js/script_stations_add.js"></script>
</body>
</html>