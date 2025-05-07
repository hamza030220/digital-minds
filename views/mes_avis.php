<?php
// mes_avis.php

session_start();

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/..'));

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/translate.php';

// Connexion à la base de données
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Erreur de connexion à la base de données.");
}

// Vérifier si l'utilisateur est connecté
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

// Gestion de la suppression d'un avis
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_avis_id'])) {
    $avis_id = (int)$_POST['delete_avis_id'];

    // Vérifier que l'avis appartient à l'utilisateur
    $query = "SELECT user_id FROM avis WHERE id = :avis_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':avis_id', $avis_id, PDO::PARAM_INT);
    $stmt->execute();
    $avis = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($avis && $avis['user_id'] == $_SESSION['user_id']) {
        $query = "DELETE FROM avis WHERE id = :avis_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':avis_id', $avis_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $message = t('review_deleted');
        } else {
            $message = t('delete_error');
        }
    } else {
        $message = t('not_authorized');
    }
}

// Pagination
$avis_per_page = 3; // 3 avis par page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Page actuelle
$offset = ($page - 1) * $avis_per_page; // Calculer l'offset

// Compter le nombre total d'avis pour l'utilisateur
$total_avis_query = "SELECT COUNT(*) FROM avis WHERE user_id = :user_id";
$stmt = $pdo->prepare($total_avis_query);
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$total_avis = $stmt->fetchColumn();
$total_pages = ceil($total_avis / $avis_per_page); // Calculer le nombre total de pages

// Récupérer les avis de l'utilisateur connecté avec pagination
$avis = [];
$average_rating = 0;
if ($isLoggedIn) {
    $query = "
        SELECT a.*, u.nom 
        FROM avis a 
        JOIN utilisateurs u ON a.user_id = u.id 
        WHERE a.user_id = :user_id 
        ORDER BY a.date_creation DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':limit', $avis_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la note moyenne (sur tous les avis, pas seulement ceux de la page)
    $query = "SELECT note FROM avis WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $all_ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($all_ratings)) {
        $total_rating = array_sum(array_column($all_ratings, 'note'));
        $average_rating = $total_rating / count($all_ratings);
    }
}

// Définir le titre de la page
$pageTitle = t('my_reviews');
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Green.tn</title>
    <link rel="icon" href="../image/ve.png" type="image/png">
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
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background-color: #F9F5E8;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;
            border-bottom: none;
        }

        .logo-nav-container {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 200px;
            height: auto;
            margin-right: 20px;
        }

        .nav-left ul {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-left ul li a {
            text-decoration: none;
            color: #2e7d32;
            font-weight: 500;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .nav-right ul {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-right ul li a.login {
            color: #fff;
            background-color: #2e7d32;
            border: 1px solid #2e7d32;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            cursor: pointer;
        }

        .nav-right ul li button.lang-toggle {
            color: #fff;
            background-color: #4CAF50 !important;
            border: 1px solid #4CAF50 !important;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            cursor: pointer;
        }

        .nav-right ul li button.lang-toggle:hover {
            background-color: #1b5e20 !important;
            border-color: #1b5e20 !important;
        }

        main {
            padding: 50px;
            text-align: center;
            background-color: #60BA97;
            margin-top: 100px;
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

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: left;
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

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
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
            position: relative;
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

        .avis .delete-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #e74c3c;
            color: #fff;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .avis .delete-btn:hover {
            background-color: #c0392b;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            background-color: #4CAF50;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .pagination a:hover {
            background-color: #2e7d32;
        }

        .pagination a.disabled {
            background-color: #ccc;
            pointer-events: none;
        }

        .pagination a.current {
            background-color: #2e7d32;
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
            header {
                flex-direction: column;
                gap: 10px;
                padding: 15px 20px;
            }

            .logo-nav-container {
                flex-direction: column;
                align-items: center;
            }

            .logo img {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .nav-left ul,
            .nav-right ul {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }

            main {
                padding: 20px;
                margin-top: 150px;
            }

            .container {
                padding: 15px;
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
    <header>
        <div class="logo-nav-container">
            <div class="logo">
                <img src="../image/ve.png" alt="Green.tn Logo">
            </div>
            <nav class="nav-left">
                <ul>
                    <li><a href="../views/index.php"><?php echo t('home'); ?></a></li>
                    <li><a href="../views/ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="../views/liste_reclamations.php"><?php echo t('view_reclamations'); ?></a></li>
                    <li><a href="../views/ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
                    <li><a href="../views/mes_avis.php"><?php echo t('my_reviews'); ?></a></li>
                    <li><a href="../views/chatbot.php"><?php echo t('chatbot'); ?></a></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="../liste_avis.php"><?php echo t('view_reviews'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <?php if ($isLoggedIn): ?>
                    <li><a href="../logout.php" class="login"><?php echo t('logout'); ?></a></li>
                <?php else: ?>
                    <li><a href="../login.php" class="login"><?php echo t('login'); ?></a></li>
                <?php endif; ?>
                <li>
                    <form action="../views/mes_avis.php" method="POST">
                        <input type="hidden" name="lang" value="<?php echo $_SESSION['lang'] === 'fr' ? 'en' : 'fr'; ?>">
                        <button type="submit" class="lang-toggle"><?php echo t($_SESSION['lang'] === 'fr' ? 'toggle_language' : 'toggle_language_en'); ?></button>
                    </form>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
            <?php if (!$isLoggedIn): ?>
                <p class="message error"><?php echo t('login_required'); ?> <a href="../login.php"><?php echo t('login'); ?></a>.</p>
            <?php else: ?>
                <?php if ($message): ?>
                    <p class="message <?php echo strpos($message, t('review_deleted')) !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>
                <div class="average-rating">
                    <?php echo t('average_rating'); ?>: 
                    <?php
                    $rounded_average = round($average_rating, 1);
                    echo $rounded_average . '/5 ';
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= round($average_rating) ? '★' : '☆';
                    }
                    ?>
                </div>
                <?php if (empty($avis) && $total_avis == 0): ?>
                    <p class="message"><?php echo t('no_reviews'); ?></p>
                <?php else: ?>
                    <?php foreach ($avis as $avi): ?>
                        <div class="avis">
                            <h3><?php echo htmlspecialchars($avi['titre']); ?></h3>
                            <div class="meta">
                                <?php echo t('submitted_by'); ?> <?php echo htmlspecialchars($avi['nom']); ?> 
                                <?php echo t('on'); ?> <?php echo date('d/m/Y H:i', strtotime($avi['date_creation'])); ?>
                            </div>
                            <div class="stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $avi['note'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <p><?php echo htmlspecialchars($avi['description']); ?></p>
                            <form method="POST" action="../views/mes_avis.php" style="display:inline;">
                                <input type="hidden" name="delete_avis_id" value="<?php echo $avi['id']; ?>">
                                <button type="submit" class="delete-btn"><?php echo t('delete'); ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="../views/mes_avis.php?page=<?php echo $page - 1; ?>"><?php echo t('previous'); ?></a>
                        <?php else: ?>
                            <a href="#" class="disabled"><?php echo t('previous'); ?></a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="../views/mes_avis.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'current' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="../views/mes_avis.php?page=<?php echo $page + 1; ?>"><?php echo t('next'); ?></a>
                        <?php else: ?>
                            <a href="#" class="disabled"><?php echo t('next'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-logo">
                    <img src="../image/ho.png" alt="Green.tn Logo">
                </div>
                <div class="social-icons">
                    <a href="https://instagram.com"><img src="../image/insta.png" alt="Instagram"></a>
                    <a href="https://facebook.com"><img src="../image/fb.png" alt="Facebook"></a>
                    <a href="https://twitter.com"><img src="../image/x.png" alt="Twitter"></a>
                </div>
            </div>
            <div class="footer-section">
                <h3><?php echo t('navigation'); ?></h3>
                <ul>
                    <li><a href="../views/index.php"><?php echo t('home'); ?></a></li>
                    <li><a href="../views/ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="../views/liste_reclamations.php"><?php echo t('view_reclamations'); ?></a></li>
                    <li><a href="../views/ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
                    <li><a href="../views/mes_avis.php"><?php echo t('my_reviews'); ?></a></li>
                    <li><a href="../views/chatbot.php"><?php echo t('chatbot'); ?></a></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="../liste_avis.php"><?php echo t('view_reviews'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-section">
                <h3><?php echo t('contact'); ?></h3>
                <p>
                    <img src="../image/location.png" alt="Location Icon">
                    <?php echo t('address'); ?>
                </p>
                <p>
                    <img src="../image/telephone.png" alt="Phone Icon">
                    <?php echo t('phone'); ?>
                </p>
                <p>
                    <img src="../image/mail.png" alt="Email Icon">
                    <a href="mailto:Green@green.com"><?php echo t('email'); ?></a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>