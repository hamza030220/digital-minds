<?php
session_start();
require 'db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Vérifier si les données de l'utilisateur sont disponibles
if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

// Vérifier les rôles
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_technicien = isset($_SESSION['role']) && $_SESSION['role'] === 'technicien';
$is_user = isset($_SESSION['role']) && $_SESSION['role'] === 'user'; // selon comment tu l'appelles

// Si l'utilisateur est un administrateur, afficher la gestion des utilisateurs
if ($is_admin) {
    // Initialisation des filtres
$nom_filter = isset($_GET['nom']) ? $_GET['nom'] : '';
$age_sort = isset($_GET['age_sort']) ? $_GET['age_sort'] : '';

// Requête SQL — ici tu remplaces * par les colonnes
$sql = "SELECT id, nom, prenom, email, telephone, role, age, gouvernorats FROM users WHERE 1=1";
$params = [];

// Filtrage par nom
if (!empty($nom_filter)) {
    $sql .= " AND nom LIKE :nom";
    $params[':nom'] = '%' . $nom_filter . '%';
}

// Tri par âge
if (!empty($age_sort)) {
    $sql .= " ORDER BY age " . ($age_sort === 'asc' ? 'ASC' : 'DESC');
}

// Exécution de la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();


}






    
// Initialisation des statistiques
$stat_total = $stat_admin = $stat_user = $stat_technicien = 0;

// Récupération des rôles et du nombre d'utilisateurs pour chaque rôle
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques par rôle
foreach ($roles as $roleStat) {
    $stat_total += $roleStat['count'];

    switch ($roleStat['role']) {
        case 'admin':
            $stat_admin = $roleStat['count'];
            break;
        case 'user':
            $stat_user = $roleStat['count'];
            break;
        case 'technicien':
            $stat_technicien = $roleStat['count'];
            break;
    }
}



// Vérification de la session utilisateur
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Récupération des informations de l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Si l'utilisateur est un simple utilisateur, on ne garde que ses infos
    if ($user['role'] === 'user') {
        $users = [$user];
    }
} else {
    // Si l'utilisateur n'est pas connecté, on peut rediriger ou gérer l'erreur
    // header('Location: login.php');
    exit("Utilisateur non connecté.");
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #e9f5ec;
        }

        .sidebar {
            height: 100vh;
            width: 180px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2e7d32;
            padding-top: 20px;
            padding-left: 20px;
        }

        .sidebar .logo-sidebar {
            width: 60%;
            margin: 0 auto;
            display: block;
            padding-bottom: 20px;
            border-bottom: 1px solid #388e3c;
        }

        .sidebar a {
            padding: 12px 20px;
            text-decoration: none;
            font-size: 15px;
            color: #d0f0d6;
            display: block;
        }

        .sidebar a:hover {
            background-color: #1b5e20;
            color: white;
        }

        .sidebar .active {
            background-color: #1b5e20;
            color: white;
        }

        .main-content {
            margin-left: 240px;
            padding: 40px;
            background-color: #ffffff;
            min-height: 100vh;
        }

        .header-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-header {
            width: 110px;
            height: auto;
            display: inline-block;
        }

        .main-content h1 {
            color: #2e7d32;
        }

        .search-container {
            text-align: center;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px;
            font-size: 16px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }


        .btn:hover {
            background-color: #388e3c;
        }

        /* Style du tableau des utilisateurs */
.user-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 10px; /* Réduction de la taille de la police à 10px pour le rendre plus petit */
}

.user-table th, .user-table td {
    padding: 4px 8px; /* Réduction du padding pour les cellules */
    text-align: left;
    border: 1px solid #ddd;
}

.user-table th {
    background-color: #f4f4f4; /* Couleur de fond pour les en-têtes */
}

.user-table td {
    background-color: #ffffff; /* Couleur de fond pour les lignes du tableau */
}

/* Ajustement des boutons dans le tableau */
.user-table .btn {
    font-size: 12px; /* Réduction de la taille de la police des boutons */
    padding: 4px 8px; /* Réduction de l'espace autour des boutons */
    margin: 2px; /* Réduction de l'espace entre les boutons */
}



        .stats-container {
            margin-top: 30px;
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 12px;
            max-width: 400px;
            margin: 20px auto;
        }

        .stats-container h2 {
            font-size: 18px;
        }

        .stats-container p {
            font-size: 14px;
        }

        #userChart {
            max-width: 200px;
            max-height: 200px;
            margin: 20px auto;
        }

        
    </style>
</head>
<body>


<!-- Sidebar -->
<div class="sidebar">
        <img src="logo.jpg" alt="Logo EcoPedal" class="logo-sidebar">

    <!-- Informations utilisateur -->
    <div class="user-info">
        <?php 
        // Définir le chemin de l'image de l'avatar
        $photo_path = 'uploads/' . htmlspecialchars($user['photo']);
        
        // Vérifier si l'image existe
        if (!empty($user['photo']) && file_exists($photo_path)): ?>
            <img src="<?php echo $photo_path; ?>" alt="Avatar" class="user-avatar">
        <?php else: ?>
            <img src="uploads/emna.jpg" alt="Avatar" class="user-avatar">
        <?php endif; ?>
        <div class="user-name">
            <strong><?php echo htmlspecialchars($user['prenom']); ?> <?php echo htmlspecialchars($user['nom']); ?></strong>
        </div>
    </div>

    <a href="info2.php" class="active">🏠 Accueil</a>
    <a href="?page=gestion_utilisateurs" class="<?php echo !$is_admin ? 'active' : ''; ?>">👥 Gestion de votre profil</a>
    <a href="reservation.php">📅 Réservations</a>
    <a href="reclamation.php">📨 Réclamations</a>
    <a href="velonet.php">🚲 Vélos & Batteries</a>
       <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
    <a href="repair_panne.php">🔧 Réparer les pannes</a>
<?php endif; ?>


    <a href="logout.php">🚪 Déconnexion</a>
</div>



<!-- CONTENU PRINCIPAL -->
<div class="main-content">
    <div class="header-logo">
        <img src="logo.jpg" alt="Logo EcoPedal" class="logo-header">
    </div>
    <h1>Bienvenue, <?php echo htmlspecialchars($user['nom']); ?></h1>

    <?php if ($is_admin): ?>
       <!-- Barre de recherche unifiée -->






<!-- Barre de recherche unifiée (en haut de la page) -->
<form method="get" class="search-container">
    <input type="hidden" name="show" value="users">
    
    <label for="nom" class="search-label">🔍 Nom :</label>
    <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($nom_filter); ?>" placeholder="Entrez un nom" class="search-input">
    
    <button type="submit" class="btn search-btn">Rechercher</button>
</form>







            <!-- Sélection de tri par âge -->
<div class="sort-container" style="margin-top: 20px;">
    <label for="age_sort" style="font-size: 10px;">📊 Trier par âge :</label>
    <select name="age_sort" id="age_sort" onchange="sortByAge()">
        <option value="">Choisir</option>
        <option value="asc" <?php echo ($age_sort == 'asc') ? 'selected' : ''; ?>>Croissant</option>
        <option value="desc" <?php echo ($age_sort == 'desc') ? 'selected' : ''; ?>>Décroissant</option>
    </select>
</div>

<!-- Tableau des utilisateurs -->
<table class="user-table">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Rôle</th>
            <th>Âge</th>
            <th>Gouvernorats</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo htmlspecialchars($user['nom']); ?></td>
            <td><?php echo htmlspecialchars($user['prenom']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['telephone']); ?></td>
            <td><?php echo htmlspecialchars($user['role']); ?></td>
            <td><?php echo htmlspecialchars($user['age']); ?></td>
            <td>
                <?php 
                // Débogage : Afficher gouvernorat tel quel sans htmlspecialchars pour le tester
                echo $user['gouvernorats'] ? $user['gouvernorats'] : 'Non spécifié';
                ?>
            </td>
            <td>
                <a href="update_user.php?id=<?php echo $user['id']; ?>"><button class="btn">Modifier</button></a>
                <a href="delete_user.php?id=<?php echo $user['id']; ?>"><button class="btn">Supprimer</button></a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>



<script>
    function sortByAge() {
        const ageSortValue = document.getElementById('age_sort').value;
        const urlParams = new URLSearchParams(window.location.search);

        if (ageSortValue) {
            urlParams.set('age_sort', ageSortValue);
        } else {
            urlParams.delete('age_sort');
        }

        // Recharger la page avec les nouveaux paramètres
        window.location.search = urlParams.toString();
    }
</script>
<!-- Statistiques pour l'admin -->
<div class="stats-container">
    <h2>Statistiques des utilisateurs</h2>
    <p>Total : <strong><?php echo $stat_total; ?></strong></p>
    <p>Admins : <strong><?php echo $stat_admin; ?></strong></p>
    <p>Techniciens : <strong><?php echo $stat_technicien; ?></strong></p>
    <p>Utilisateurs simples : <strong><?php echo $stat_user; ?></strong></p>
    <canvas id="userChart" width="300" height="300"></canvas>
</div>

<script>
const ctx = document.getElementById('userChart').getContext('2d');
const userChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Admins', 'Techniciens', 'Utilisateurs'],
        datasets: [{
            data: [
                <?php echo $stat_admin; ?>,
                <?php echo $stat_technicien; ?>,
                <?php echo $stat_user; ?>
            ],
            backgroundColor: ['#3498db', '#2ecc71', '#f39c12']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>


        <!-- Export -->
        <div class="export-container" style="text-align:center;">
            <a href="export_csv.php"><button class="btn">Exporter les utilisateurs en CSV</button></a>
        </div>

    
    <?php endif; ?>

    <!-- Déconnexion -->
    <div class="logout-container">
        <a href="logout.php"><button class="btn">Déconnexion</button></a>
    </div>

</div>

</body>
</html>
