<?php
// This view is called from the controller, no direct access
// The controller already checked for login and provided the data
// No database operations in the view

// Extract data provided by the controller
$stations = $result['stations'] ?? [];
$pagination = $result['pagination'] ?? [
    'currentPage' => 1,
    'totalPages' => 1,
    'totalStations' => 0,
    'itemsPerPage' => 10
];

// Variables for template
$page = $pagination['currentPage'];
$totalPages = $pagination['totalPages'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Stations - Green Admin</title>
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
                        <a class="nav-link" href="/green-admin-mvc/">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/green-admin-mvc/stations">Stations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/green-admin-mvc/trajets">Trajets</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Stations</h1>
            <a href="/green-admin-mvc/stations/add" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nouvelle Station
            </a>
        </div>
        
        <!-- Display session message if exists -->
        <?php if (isset($_SESSION['message']) && !empty($_SESSION['message'])): ?>
            <?php $messageType = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info'; ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
            <?php 
            // Clear the message
            $_SESSION['message'] = '';
            $_SESSION['message_type'] = '';
            ?>
        <?php endif; ?>
        
        <?php if (isset($result['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($result['error']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Emplacement</th>
                                <th>Statut</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($stations) && !empty($stations)): ?>
                                <?php foreach ($stations as $station): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($station['id']); ?></td>
                                        <td><?php echo htmlspecialchars($station['name']); ?></td>
                                        <td><?php echo htmlspecialchars($station['location']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $station['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo htmlspecialchars($station['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($station['created_at']); ?></td>
                                        <td>
                                            <a href="/green-admin-mvc/stations/edit?id=<?php echo $station['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/green-admin-mvc/stations/delete?id=<?php echo $station['id']; ?>" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Aucune station trouvée</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="/green-admin-mvc/stations?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

