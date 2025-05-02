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

// Stocker les informations dans un tableau pour affichage
$users = [$user]; // Permet d'utiliser foreach même pour un seul utilisateur
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #e9f5ec;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .topbar {
            width: 100%;
            background-color: #2e7d32;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            animation: slideDown 0.5s forwards;
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }

        .topbar .logo {
            height: 40px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            margin-left: 40px;
        }

        .nav-links a {
            color: #d0f0d6;
            text-decoration: none;
            font-size: 15px;
            padding: 10px 16px;
            border-radius: 4px;
            opacity: 0;
            animation: fadeInLinks 0.5s forwards;
        }

        .nav-links a:hover, .nav-links .active {
            background-color: #1b5e20;
            color: white;
            transform: translateY(-2px);
            transition: transform 0.2s ease, background-color 0.3s;
        }

        @keyframes fadeInLinks {
            to { opacity: 1; }
        }

        .nav-links a:nth-child(1) { animation-delay: 0.3s; }
        .nav-links a:nth-child(2) { animation-delay: 0.4s; }
        .nav-links a:nth-child(3) { animation-delay: 0.5s; }
        .nav-links a:nth-child(4) { animation-delay: 0.6s; }
        .nav-links a:nth-child(5) { animation-delay: 0.7s; }
        .nav-links a:nth-child(6) { animation-delay: 0.8s; }

.profile-icon {
    height: 55px;
    width: 55px;
    border-radius: 50%;
    object-fit: cover;
    margin-left: 50px;
    margin-right: 25px;
}


        .top-profile-pic {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .top-profile-pic:hover {
            transform: scale(1.1);
        }

        .main-content {
            padding: 100px 40px 40px 40px;
            background-color: #ffffff;
            min-height: 100vh;
            animation: fadeInContent 1s ease-out;
        }

        @keyframes fadeInContent {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .main-content h1 {
            color: #2e7d32;
        }

        .buttons-container {
            margin-top: 30px;
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
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn:hover {
            background-color: #388e3c;
            transform: translateY(-5px);
        }

        .welcome-box {
            background-color: #f0fdf4;
            padding: 20px;
            border-left: 6px solid #4caf50;
            border-radius: 8px;
            margin-top: 40px;
            text-align: center;
            animation: slideInBox 0.5s ease-out;
        }

        @keyframes slideInBox {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-box h2 {
            color: #2e7d32;
            font-size: 24px;
        }

        .welcome-image {
            width: 100%;
            max-width: 600px;
            height: auto;
            margin: 20px auto;
            display: block;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            animation: fadeInImage 1s ease-out;
        }

        .profile-box {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-details {
            flex: 1;
        }

        .profile-details h2 {
            margin: 0;
            color: #2e7d32;
            font-size: 22px;
        }

        .profile-details h3 {
            color: #555;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            text-decoration: none;
            margin: 2px;
            display: inline-block;
        }

        .modifier {
            background-color: #fdd835;
            color: #000;
        }

        .supprimer {
            background-color: #e53935;
            color: white;
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        @keyframes fadeInImage {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <img src="logo.jpg" alt="Logo EcoPedal" class="logo">

    <div class="nav-links">
        <a href="?page=accueil" class="active">🏠 accueil</a>
        <a href="?page=gestion_utilisateurs">👥 mes informations</a>
        <a href="reservation.php">📅 Réservations</a>
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
            <a href="repair_panne.php">🔧 Réparer les vélos</a>
        <?php endif; ?>      
        <a href="velonet.php">🚲 Vélos & Batteries</a>
        <a href="logout.php">🚪 Déconnexion</a>
    </div>

    <div class="profile-icon">
        <a href="?page=gestion_utilisateurs&action=infos">
            <img src="user_images/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profil" class="top-profile-pic">
        </a>
    </div>
</div>

<!-- CONTENU -->
<div class="main-content">
<?php
$utilisateur = $user;

if (!isset($_GET['page']) || $_GET['page'] == 'accueil') {
    echo '<div class="header-logo">
            <img src="logo.jpg" alt="Logo EcoPedal" class="logo-header" width="220px">
          </div>';
    echo '<h1>Bienvenue sur notre accueil</h1>';
    echo '<div class="welcome-box">
            <h2>Bienvenue sur greentn – Gestion de la mobilité verte 🌱</h2>
            <p>Accédez aux fonctionnalités de gestion, suivez les locations en temps réel, et contribuez à un avenir plus durable.</p>
          </div>';
    echo '<img src="l.jpg" alt="Vélo écologique" class="welcome-image">';
} elseif ($_GET['page'] == 'gestion_utilisateurs') {

    echo '<h1>Gestion des Utilisateurs</h1>';

    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        echo '<div class="buttons-container">
                <a href="dashboard.php" class="btn">🔐 consulter mes informations connecté</a>
              </div>';
    } else {
        echo '<div class="buttons-container">
                <a href="?page=gestion_utilisateurs&action=infos" class="btn">🔐 consulter mes informations connecté</a>
              </div>';
    }

    if (isset($_GET['action']) && $_GET['action'] === 'infos') {
        echo '<div class="profile-box">';
        echo '<img src="user_images/' . htmlspecialchars($utilisateur['photo']) . '" alt="Photo de profil" class="profile-pic">';
        echo '<div class="profile-details">';
        echo '<h2>' . htmlspecialchars($utilisateur['nom']) . ' ' . htmlspecialchars($utilisateur['prenom']) . '</h2>';
        echo '<h3>' . htmlspecialchars($utilisateur['email']) . '</h3>';
        echo '<table>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Âge</th>
                    <th>Gouvernorats</th>
                    <th>Actions</th>
                </tr>
                <tr>
                    <td>' . htmlspecialchars($utilisateur['nom']) . '</td>
                    <td>' . htmlspecialchars($utilisateur['prenom']) . '</td>
                    <td>' . htmlspecialchars($utilisateur['email']) . '</td>
                    <td>' . htmlspecialchars($utilisateur['telephone']) . '</td>
                    <td>' . htmlspecialchars($utilisateur['age']) . '</td>
                    <td>' . htmlspecialchars($utilisateur['gouvernorats']) . '</td>
                    <td>
                        <a href="update_user.php" class="action-btn modifier">✏️ Modifier</a>
                        <a href="delete_user.php" class="action-btn supprimer">🗑️ Supprimer</a>
                    </td>
                </tr>
              </table>';
        echo '</div>';
        echo '</div>';
    }
}
?>
</div>
</body>
</html>
