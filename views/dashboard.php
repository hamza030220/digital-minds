<?php
// Dashboard view
// Data available: $stationsStats, $trajetsStats
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/green-admin-mvc/assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #60BA97;">
        <div class="container-fluid">
            <a class="navbar-brand" href="/green-admin-mvc/"><img src="/green-admin-mvc/assets/images/logobackend.png" alt="Green Admin" style="height: 30px;"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/green-admin-mvc/">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/green-admin-mvc/stations">Stations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/green-admin-mvc/trajets">Trajets</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        Bienvenue, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </span>
                    <a href="/green-admin-mvc/logout" class="btn btn-outline-light">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Tableau de bord</h1>
        
        <!-- Display message if exists -->
        <?php if (isset($_SESSION['message']) && !empty($_SESSION['message'])): ?>
            <?php $messageType = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info'; ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
            <?php 
            // Clear the message
            $_SESSION['message'] = '';
            $_SESSION['message_type'] = '';
            ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Stations</h5>
                        <p class="card-text">
                            Total: <?php echo $stationsStats['total_stations'] ?? '0'; ?><br>
                            Actives: <?php echo $stationsStats['active_stations'] ?? '0'; ?>
                        </p>
                        <a href="/green-admin-mvc/stations" class="btn btn-primary">Gérer les stations</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Trajets</h5>
                        <p class="card-text">
                            Total: <?php echo $trajetsStats['total_trajets'] ?? '0'; ?>
                        </p>
                        <a href="/green-admin-mvc/trajets" class="btn btn-primary">Gérer les trajets</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <h2>Actions rapides</h2>
                <div class="list-group">
                    <a href="/green-admin-mvc/stations/add" class="list-group-item list-group-item-action">
                        <i class="bi bi-plus-circle"></i> Ajouter une station
                    </a>
                    <a href="/green-admin-mvc/trajets/add" class="list-group-item list-group-item-action">
                        <i class="bi bi-plus-circle"></i> Ajouter un trajet
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

