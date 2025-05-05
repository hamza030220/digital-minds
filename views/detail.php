<?php
// detail.php
// Controller logic & view to display a single reclamation's details

// Set timezone to Tunisia (CET, UTC+1)
date_default_timezone_set('Africa/Tunis');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include translation helper
require_once __DIR__ . '/../translate.php';

// Authentication Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?error=' . urlencode(t('error_session_required')));
    exit;
}

// Include the Model
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../config/database.php';

// Validate reclamation ID
$reclamationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($reclamationId <= 0) {
    header('Location: ../liste_reclamations.php?error=' . urlencode(t('invalid_reclamation_id')));
    exit;
}

// Instantiate the Model
$reclamationModel = null;
$reclamation = null;
$reponses = [];
$errorMessage = null;
$pageTitle = t('reclamation_details') . ' - Green.tn';

try {
    $reclamationModel = new Reclamation();
    $user_id = $_SESSION['user_id'];
    $reclamation = $reclamationModel->getParId($reclamationId);
    // Verify the reclamation exists and belongs to the user
    if (!$reclamation || $reclamation['utilisateur_id'] != $user_id) {
        header('Location: ../liste_reclamations.php?error=' . urlencode(t('reclamation_not_found')));
        exit;
    }

    // Pagination for responses
    $reponses_per_page = 3; // 3 responses per page
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Current page
    $offset = ($page - 1) * $reponses_per_page; // Calculate offset

    // Fetch total number of responses
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM reponses WHERE reclamation_id = ?");
    $stmt->execute([$reclamationId]);
    $total_reponses = $stmt->fetchColumn();
    $total_pages = ceil($total_reponses / $reponses_per_page);

    // Fetch responses with pagination
    $stmt = $db->prepare("SELECT contenu, date_creation, role FROM reponses WHERE reclamation_id = ? ORDER BY date_creation ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $reclamationId, PDO::PARAM_INT);
    $stmt->bindValue(2, $reponses_per_page, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("detail.php: Database error fetching reclamation ID {$reclamationId} or responses - " . $e->getMessage());
    $errorMessage = t('error_database_reclamations');
} catch (Exception $e) {
    error_log("detail.php: Unexpected error - " . $e->getMessage());
    $errorMessage = t('error_unexpected_reclamations');
}

// --- View / Presentation Logic ---
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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

        .nav-right ul li a.login,
        .nav-right ul li button.lang-toggle {
            color: #fff;
            background-color: #2e7d32;
            border: 1px solid #2e7d32;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            cursor: pointer;
        }

        .nav-right ul li button.lang-toggle {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }

        .nav-right ul li button.lang-toggle:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
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

        .content-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            border: 1px solid #f5c6cb;
            background-color: #f8d7da;
            color: #721c24;
            text-align: center;
        }

        .details-container {
            text-align: left;
        }

        .details-container p {
            margin: 10px 0;
            font-size: 16px;
        }

        .details-container p strong {
            color: #2e7d32;
            display: inline-block;
            width: 120px;
        }

        .reponse {
            background-color: #F9F5E8;
            padding: 15px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .reponse p {
            margin: 5px 0;
        }

        .reponse i {
            color: #7f8c8d;
            font-size: 12px;
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

        .btn {
            padding: 10px 20px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #1b5e20;
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

            .content-container {
                padding: 15px;
            }

            .details-container p {
                font-size: 14px;
            }

            .details-container p strong {
                width: 100px;
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
                    <li><a href="../ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="../liste_reclamations.php"><?php echo t('view_reclamations'); ?></a></li>
                    <li><a href="../views/ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
                    <li><a href="../mes_avis.php"><?php echo t('my_reviews'); ?></a></li>
                    <li><a href="../chatbot.php"><?php echo t('chatbot'); ?></a></li>
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <li>
                    <form action="../views/detail.php?id=<?php echo htmlspecialchars($reclamationId); ?>" method="POST" id="lang-toggle-form">
                        <!-- Le contrôleur gérera le changement de langue -->
                    </form>
                </li>
                <li><a href="../logout.php" class="login"><?php echo t('logout'); ?></a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2><?php echo t('reclamation_details'); ?></h2>
        <div class="content-container">
            <?php if ($errorMessage): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php elseif ($reclamation): ?>
                <div class="details-container">
                    <p><strong><?php echo t('title'); ?> :</strong> <?php echo htmlspecialchars($reclamation['titre']); ?></p>
                    <p><strong><?php echo t('description'); ?> :</strong> <?php echo htmlspecialchars($reclamation['description']); ?></p>
                    <p><strong><?php echo t('location'); ?> :</strong> <?php echo htmlspecialchars($reclamation['lieu']); ?></p>
                    <p><strong><?php echo t('type'); ?> :</strong> <?php echo t($reclamation['type_probleme']); ?></p>
                    <p><strong><?php echo t('status'); ?> :</strong> <?php echo t($reclamation['statut']); ?></p>
                    <p><strong><?php echo t('created_at'); ?> :</strong> 
                        <?php 
                        $date = new DateTime($reclamation['date_creation']);
                        $date->modify('-1 hour');
                        echo $date->format('d/m/Y H:i:s');
                        ?>
                    </p>
                    <div class="responses">
                        <strong><?php echo t('responses'); ?> :</strong>
                        <?php if ($total_reponses == 0): ?>
                            <p><?php echo t('no_responses'); ?></p>
                        <?php else: ?>
                            <?php foreach ($reponses as $reponse): ?>
                                <div class="reponse">
                                    <p><strong><?php echo t('response_by'); ?> <?php echo t($reponse['role']); ?> :</strong></p>
                                    <p><?php echo nl2br(htmlspecialchars($reponse['contenu'])); ?></p>
                                    <p><i><?php echo t('response_date'); ?> 
                                        <?php 
                                        $responseDate = new DateTime($reponse['date_creation']);
                                        $responseDate->modify('-1 hour');
                                        echo $responseDate->format('d/m/Y H:i:s');
                                        ?>
                                    </i></p>
                                </div>
                            <?php endforeach; ?>

                            <!-- Pagination for responses -->
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?id=<?php echo $reclamationId; ?>&page=<?php echo $page - 1; ?>"><?php echo t('previous', 'Précédent'); ?></a>
                                <?php else: ?>
                                    <a href="#" class="disabled"><?php echo t('previous', 'Précédent'); ?></a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?id=<?php echo $reclamationId; ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'current' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?id=<?php echo $reclamationId; ?>&page=<?php echo $page + 1; ?>"><?php echo t('next', 'Suivant'); ?></a>
                                <?php else: ?>
                                    <a href="#" class="disabled"><?php echo t('next', 'Suivant'); ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="../liste_reclamations.php" class="btn"><?php echo t('back_to_list'); ?></a>
                </div>
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
                    <li><a href="../ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="#a-propos-de-nous"><?php echo t('about_us'); ?></a></li>
                    <li><a href="#contact"><?php echo t('contact'); ?></a></li>
                    <li><a href="../views/ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
                    <li><a href="../mes_avis.php"><?php echo t('my_reviews'); ?></a></li>
                    <li><a href="../chatbot.php"><?php echo t('chatbot'); ?></a></li>
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