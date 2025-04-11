<?php
require_once '../includes/config.php';

// Require login
requireLogin();

// Function to ensure table structure has new columns
function ensureTableStructure($pdo) {
    try {
        $columnCheckStmt = $pdo->query("SHOW COLUMNS FROM trajets LIKE 'start_point'");
        if ($columnCheckStmt->rowCount() == 0) {
            // Update table structure to add new columns
            $pdo->exec("
                ALTER TABLE trajets 
                ADD COLUMN start_point VARCHAR(255) NULL,
                ADD COLUMN end_point VARCHAR(255) NULL,
                ADD COLUMN start_point_name VARCHAR(255) NULL,
                ADD COLUMN end_point_name VARCHAR(255) NULL
            ");
            
            // Log the update
            error_log("Updated trajets table structure to add coordinate columns");
        }
        return true;
    } catch (PDOException $e) {
        error_log("Failed to update table structure: " . $e->getMessage());
        return false;
    }
}

// Initialize session for messages if not exists
if (!isset($_SESSION['message'])) {
    $_SESSION['message'] = '';
    $_SESSION['message_type'] = '';
}

// Pagination settings
$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

try {
    $pdo = getDBConnection();
    
    // Ensure the table has the required structure
    ensureTableStructure($pdo);
    
    // First check if there are columns for the new structure
    $columnsCheck = $pdo->query("SHOW COLUMNS FROM trajets LIKE 'start_point'");
    $hasNewColumns = $columnsCheck->rowCount() > 0;
    
    // Get total number of trajets
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM trajets");
    $totalTrajets = $totalStmt->fetchColumn();
    $totalPages = ceil($totalTrajets / $itemsPerPage);
    
    if ($hasNewColumns) {
        // Query for new structure with direct coordinates
        $stmt = $pdo->prepare("
            SELECT t.id, t.distance, t.description, t.created_at,
                   t.start_point, t.end_point, 
                   COALESCE(t.start_point_name, 'Point de départ') as start_station_name,
                   COALESCE(t.end_point_name, 'Point d\'arrivée') as end_station_name,
                   
                   -- For compatibility with old structure, also get station names if available
                   s1.name as start_station_original,
                   s2.name as end_station_original
            FROM trajets t
            LEFT JOIN stations s1 ON t.start_station_id = s1.id
            LEFT JOIN stations s2 ON t.end_station_id = s2.id
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ");
    } else {
        // Original query for old structure with station IDs
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   s1.name as start_station_name,
                   s2.name as end_station_name
            FROM trajets t
            JOIN stations s1 ON t.start_station_id = s1.id
            JOIN stations s2 ON t.end_station_id = s2.id
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ");
    }
    
    $stmt->bindValue(1, $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $trajets = $stmt->fetchAll();
    
    // Post-process trajets to ensure consistent display format
    foreach ($trajets as $key => $trajet) {
        // If we have old structure data but no new structure data
        if ($hasNewColumns && empty($trajet['start_station_name']) && !empty($trajet['start_station_original'])) {
            $trajets[$key]['start_station_name'] = $trajet['start_station_original'];
        }
        if ($hasNewColumns && empty($trajet['end_station_name']) && !empty($trajet['end_station_original'])) {
            $trajets[$key]['end_station_name'] = $trajet['end_station_original'];
        }
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Une erreur est survenue lors de la récupération des trajets.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Trajets - Green Admin</title>
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
                        <a class="nav-link" href="../stations/list.php">Stations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="list.php">Trajets</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Trajets</h1>
            <a href="add.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nouveau Trajet
            </a>
        </div>
        
        <?php if (isset($_SESSION['message']) && !empty($_SESSION['message'])): ?>
            <?php $messageType = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info'; ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
            <?php 
            // Clear the message
            $_SESSION['message'] = '';
            $_SESSION['message_type'] = '';
            ?>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Station de départ</th>
                                <th>Station d'arrivée</th>
                                <th>Distance</th>
                                <th>Description</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($trajets) && !empty($trajets)): ?>
                                <?php foreach ($trajets as $trajet): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trajet['id']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['start_station_name']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['end_station_name']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['distance']) . ' km'; ?></td>
                                        <td><?php echo htmlspecialchars($trajet['description']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['created_at']); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $trajet['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $trajet['id']; ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce trajet ?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucun trajet trouvé</td>
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
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

