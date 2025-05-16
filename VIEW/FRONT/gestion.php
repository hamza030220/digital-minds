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
        <a href="?page=gestion_utilisateurs">👥 se connecter</a>
        <a href="reservation.php">📅 Réservations</a>
        <a href="reclamation.php">📨 Réclamations</a>
        <a href="velonet.php">🚲 Vélos & Batteries</a>
        <a href="logout.php">🚪 Déconnexion</a>
    </div>
</div>

<!-- CONTENU -->
<div class="main-content">

    <?php
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
        echo '<div class="buttons-container">
                <a href="login.php" class="btn">🔐 Se connecter</a>
                <a href="sign_up.php" class="btn">➕ Créer un nouvel utilisateur</a>
              </div>';
    }
    ?>

</div>

</body>
</html>
