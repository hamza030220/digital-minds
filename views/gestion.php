<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs</title>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif; /* Police moderne */
            background: linear-gradient(135deg, #e9f5ec 0%, #d4e9d9 100%); /* Dégradé subtil */
            animation: fadeIn 1s ease-in;
            position: relative;
            min-height: 100vh;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Image de fond subtile (optionnelle) */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            opacity: 0.1; /* Faible opacité pour subtilité */
            z-index: -1;
        }

        .topbar {
            width: 100%;
            background: linear-gradient(to right, #2e7d32, #4caf50); /* Dégradé pour la topbar */
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Ombre subtile */
            animation: slideDown 0.5s forwards;
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }

        .topbar .logo {
            height: 40px;
            transition: transform 0.3s ease;
        }

        .topbar .logo:hover {
            transform: scale(1.1); /* Effet subtil au survol */
        }

        .nav-links {
            display: flex;
            gap: 20px;
            margin-left: 40px;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            padding: 10px 16px;
            border-radius: 4px;
            opacity: 0;
            animation: fadeInLinks 0.5s forwards;
            transition: all 0.3s ease;
        }

        .nav-links a:hover, .nav-links .active {
            background-color: rgba(255, 255, 255, 0.2); /* Effet glassmorphism */
            color: #ffffff;
            transform: translateY(-2px);
        }

        @keyframes fadeInLinks {
            to { opacity: 1; }
        }

        .nav-links a:nth-child(1) { animation-delay: 0.2s; }
        .nav-links a:nth-child(2) { animation-delay: 0.3s; }
        .nav-links a:nth-child(3) { animation-delay: 0.4s; }
        .nav-links a:nth-child(4) { animation-delay: 0.5s; }
        .nav-links a:nth-child(5) { animation-delay: 0.6s; }
        .nav-links a:nth-child(6) { animation-delay: 0.7s; }

        .main-content {
            padding: 100px 40px 40px 40px;
            background-color: rgba(255, 255, 255, 0.95); /* Fond légèrement transparent */
            min-height: 100vh;
            border-radius: 12px;
            margin: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); /* Ombre douce */
            animation: fadeInContent 0.8s ease-out;
        }

        @keyframes fadeInContent {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-content h1 {
            color: #2e7d32;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .buttons-container {
            margin-top: 30px;
            text-align: center;
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
            transform: translateY(-3px);
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
            margin-bottom: 10px;
        }

        .welcome-image {
            width: 100%;
            max-width: 600px;
            height: auto;
            margin: 20px auto;
            display: block;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            animation: fadeInImage 1s ease-out;
        }

        @keyframes fadeInImage {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .logo-header {
            display: block;
            margin: 0 auto 20px;
            animation: fadeInImage 1s ease-out;
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