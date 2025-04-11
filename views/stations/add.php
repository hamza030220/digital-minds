<?php
require_once '../includes/config.php';

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
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';

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
                // Insert new station
                $stmt = $pdo->prepare("INSERT INTO stations (name, location, status) VALUES (?, ?, ?)");
                $stmt->execute([$name, $location, $status]);
                
                $success = "Station ajoutée avec succès.";
                // Reset form
                $name = '';
                $location = '';
                $status = 'active';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #60BA97;">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php"><img src="../public/image/logobackend.png" alt="Green Admin" style="height: 30px;"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="list.php">Stations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../trajets/list.php">Trajets</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-outline-light">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
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
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom de la station</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Emplacement</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($location); ?>" required>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Log form submission
        console.log('Form submitted successfully');
        
        // Add success message with animation
        function showSuccessMessage(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'alert alert-success fade show';
            successDiv.innerHTML = `
                <strong>Succès!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.card-body').prepend(successDiv);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                const alert = new bootstrap.Alert(successDiv);
                alert.close();
            }, 3000);
        }

        // Add form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const nameInput = document.querySelector('#name');
            const addressInput = document.querySelector('#location');
            
            if (nameInput.value.trim() === '') {
                e.preventDefault();
                nameInput.classList.add('is-invalid');
                showErrorMessage('Le nom de la station est requis');
            }
            
            if (addressInput.value.trim() === '') {
                e.preventDefault();
                addressInput.classList.add('is-invalid');
                showErrorMessage('L\'adresse de la station est requise');
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

        // Add real-time validation
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

