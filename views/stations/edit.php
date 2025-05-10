<?php
require_once '../../includes/config.php';
requireLogin();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

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

$error = '';
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$name = '';
$location = '';
$status = '';

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
        
        $name = $station['name'] ?? '';
        $location = $station['location'] ?? '';
        $status = $station['status'] ?? 'active';
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed');
        }
        
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        
        $city = getCityNameFromCoordsOSM($location);

        if (empty($name)) {
            $error = "Le nom de la station est requis.";
        } elseif (empty($location)) {
            $error = "L'emplacement de la station est requis.";
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $error = "Statut invalide.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM stations WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                $error = "Une autre station avec ce nom existe déjà.";
            } else {
                $stmt = $pdo->prepare("UPDATE stations SET name = ?, location = ?, city = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $location, $city, $status, $id]);
                
                $success = "Station mise à jour avec succès.";
            }
        }
    }
} catch (PDOException $e) {
    error_log("Error updating station: " . $e->getMessage());
    $error = "Une erreur est survenue lors de la modification de la station.";
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Station - Green Admin</title>
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
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-success">
                        <div class="card-header bg-success text-white d-flex align-items-center">
                            <i class="bi bi-pencil me-2" style="font-size:1.5em"></i>
                            <h2 class="h5 mb-0">Modifier la Station</h2>
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
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de la station</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($name); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="location" class="form-label">Emplacement</label>
                                    <div id="map" style="height: 300px; margin-bottom: 10px;"></div>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($location); ?>" readonly>
                                    <small class="form-text text-muted">Cliquez sur la carte pour sélectionner l'emplacement</small>
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Statut</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Actif</option>
                                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-between">
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
    <script src="../../public/assets/js/scripts_station_edit.js"></script>
</body>
</html>