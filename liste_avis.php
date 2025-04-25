<?php
// liste_avis.php

session_start();
require_once __DIR__ . '/config/database.php';

// Connexion à la base de données
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Erreur de connexion à la base de données.");
}

// Vérifier si l'utilisateur est connecté et est un admin
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$isAdmin = false;
if ($isLoggedIn) {
    $query = "SELECT role FROM utilisateurs WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $user && $user['role'] === 'admin';
}

// Récupérer tous les avis avec les informations de l'utilisateur
$avis = [];
$average_rating = 0;
if ($isAdmin) {
    $query = "
        SELECT a.*, u.nom 
        FROM avis a 
        JOIN utilisateurs u ON a.user_id = u.id 
        ORDER BY a.date_creation DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la note moyenne
    if (!empty($avis)) {
        $total_rating = array_sum(array_column($avis, 'note'));
        $average_rating = $total_rating / count($avis);
    }
}

// Définir le titre de la page
$pageTitle = "Voir les avis";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Green.tn</title>
    <link rel="icon" href="image/ve.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #60BA97;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            width: 200px;
            background-color: #2e7d32;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            padding-top: 20px;
            color: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar .logo img {
            width: 150px;
            height: auto;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            font-size: 1em;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
        }

        .sidebar ul li a:hover {
            background-color: #1b5e20;
            border-radius: 0 20px 20px 0;
        }

        .container {
            margin-left: 220px;
            width: calc(90% - 220px);
            max-width: 1200px;
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: #F9F5E8;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #4CAF50;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        main {
            padding: 20px;
            text-align: center;
            background-color: #60BA97;
        }

        main h2 {
            margin-bottom: 30px;
            color: #2e7d32;
            font-size: 24px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
            background-color: #F9F5E8;
            padding: 10px 20px;
            display: inline-block;
            border-radius: 5px;
        }

        .average-rating {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #2e7d32;
        }

        .average-rating .stars {
            color: #FFD700;
            font-size: 20px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            border: 1px solid transparent;
            text-align: center;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .message.error a {
            color: #2e7d32;
            text-decoration: none;
        }

        .message.error a:hover {
            text-decoration: underline;
        }

        .avis {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #fff;
        }

        .avis h3 {
            font-size: 18px;
            color: #2e7d32;
            margin-bottom: 5px;
        }

        .avis .meta {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }

        .avis .stars {
            color: #FFD700;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .avis p {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }

        footer {
            background-color: #F9F5E8;
            padding: 20px 0;
            border-top: none;
            font-family: "Berlin Sans FB", Arial, sans-serif;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-left {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-logo img {
            width: 200px;
            height: auto;
            margin-bottom: 15px;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .social-icons a img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transition: opacity 0.3s ease;
        }

        .social-icons a img:hover {
            opacity: 0.8;
        }

        .footer-section {
            margin-left: 40px;
        }

        .footer-section h3 {
            font-size: 18px;
            color: #333;
            margin-top: 20px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 8px;
        }

        .footer-section ul li a {
            text-decoration: none;
            color: #555;
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #4CAF50;
        }

        .footer-section p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .footer-section p img {
            margin-right: 8px;
            width: 16px;
            height: 16px;
        }

        .footer-section p a {
            color: #555;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section p a:hover {
            color: #4CAF50;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                padding-bottom: 20px;
            }

            .container {
                margin-left: 0;
                width: 90%;
                margin: 20px auto;
            }

            main {
                padding: 20px;
            }

            .footer-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .footer-left {
                margin-bottom: 20px;
            }

            .footer-section {
                margin-left: 0;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="image/ve.png" alt="Green.tn Logo">
        </div>
        <ul>
            <li><a href="stats.php">🏠 Dashboard</a></li>
            <li><a href="">🚲 Reservation</a></li>
            <li><a href="reclamations_utilisateur.php">📋 Reclamation</a></li>
            <li><a href="liste_avis.php">⭐ Avis</a></li>
            <li><a href="logout.php">🔓 Déconnexion</a></li>
        </ul>
    </div>

    <main>
        <div class="container">
            <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
            <?php if (!$isLoggedIn): ?>
                <p class="message error">Vous devez vous connecter pour accéder à cette page. <a href="login.php">Connexion</a>.</p>
            <?php elseif (!$isAdmin): ?>
                <p class="message error">Accès refusé. Réservé aux admins.</p>
            <?php else: ?>
                <div class="average-rating">
                    Note moyenne : 
                    <?php
                    $rounded_average = round($average_rating, 1);
                    echo $rounded_average . '/5 ';
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= round($average_rating) ? '★' : '☆';
                    }
                    ?>
                </div>
                <?php if (empty($avis)): ?>
                    <p class="message">Aucun avis disponible.</p>
                <?php else: ?>
                    <?php foreach ($avis as $avi): ?>
                        <div class="avis">
                            <h3><?php echo htmlspecialchars($avi['titre']); ?></h3>
                            <div class="meta">
                                Soumis par <?php echo htmlspecialchars($avi['nom']); ?> 
                                le <?php echo date('d/m/Y H:i', strtotime($avi['date_creation'])); ?>
                            </div>
                            <div class="stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $avi['note'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <p><?php echo htmlspecialchars($avi['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    
           
</body>
</html>