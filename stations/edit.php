<?php
require_once '../includes/config.php';

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
requireLogin();

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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
<?php 
        $basePath = '../';
        $currentPage = 'stations';
        include '../includes/sidbar.php';
    ?>

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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Configure Leaflet to use CDN-hosted marker icons
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
            iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png'
        });

        // Initialize map with existing location if available
        const map = L.map('map').setView([36.8065, 10.1815], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let marker = null;
        const locationInput = document.getElementById('location');
        
        // Set initial marker if location exists
        if (locationInput.value) {
            const [lat, lng] = locationInput.value.split(',').map(Number);
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup(`Emplacement actuel: ${locationInput.value}`)
                .openPopup();
            map.setView([lat, lng], 13);
        }

        // Handle map click
        map.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            const coordinates = `${lat},${lng}`;
            
            locationInput.value = coordinates;
            
            if (marker) {
                map.removeLayer(marker);
            }
            
            marker = L.marker(e.latlng).addTo(map)
                .bindPopup(`Nouvel emplacement: ${coordinates}`)
                .openPopup();
        });

    
    
    // Add JS validation (disable HTML5 validation)
    document.querySelector('form').addEventListener('submit', function(e) {
        let valid = true;
        const nameInput = document.querySelector('#name');
        const locationInput = document.querySelector('#location');
    
        // Remove previous validation states
        nameInput.classList.remove('is-invalid');
        locationInput.classList.remove('is-invalid');
    
        if (nameInput.value.trim() === '') {
            e.preventDefault();
            valid = false;
            nameInput.classList.add('is-invalid');
            showErrorMessage('Le nom de la station est requis');
        }
        if (locationInput.value.trim() === '') {
            e.preventDefault();
            valid = false;
            locationInput.classList.add('is-invalid');
            showErrorMessage('L\'emplacement de la station est requis');
        }
    });

    // Show error message
    function showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger fade show';
        errorDiv.innerHTML = `
            <strong>Erreur!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.card-body').prepend(errorDiv);
    }

    // Real-time validation
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });
</script>
</body>
</html>

