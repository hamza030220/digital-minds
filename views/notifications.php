<?php
// notifications.php
// Page to display notifications for the user

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Include translation helper
require_once ROOT_PATH . '/translate.php';

// Include Database connection
require_once ROOT_PATH . '/config/database.php';

// Authentication Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?error=' . urlencode(t('error_session_required')));
    exit;
}

$user_id = $_SESSION['user_id'];
$notifications = [];
$errorMessage = null;
$pageTitle = t('notifications') . ' - Green.tn';

// Handle notification deletion feedback
$feedbackMessage = null;
$feedbackType = 'info';
if (isset($_GET['delete_status'])) {
    switch ($_GET['delete_status']) {
        case 'success':
            $feedbackMessage = t('notification_deleted');
            $feedbackType = 'info';
            break;
        case 'error':
            $feedbackMessage = t('error_deleting_notification');
            $feedbackType = 'error';
            break;
    }
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Fetch notifications for the user
    $stmt = $pdo->prepare("
        SELECT n.id, n.reclamation_id, n.message, n.is_read, n.created_at, r.titre
        FROM notifications n
        LEFT JOIN reclamations r ON n.reclamation_id = r.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark notifications as read when the page is viewed
    $updateStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $updateStmt->execute([$user_id]);
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $errorMessage = t('error_database_notifications');
}
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
        .nav-right ul li a.signin,
        .nav-right ul li button.lang-toggle {
            color: #fff;
            background-color: #2e7d32;
            border: 1px solid #2e7d32;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            cursor: pointer;
        }

        .nav-right ul li a.notification {
            position: relative;
            color: #fff;
            background-color: #2e7d32;
            border: 1px solid #2e7d32;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .nav-right ul li a.notification:hover {
            background-color: #1b5e20;
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
            max-width: 1200px;
            margin: 0 auto;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .error-message,
        .info-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            border: 1px solid transparent;
            text-align: center;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .info-message {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .notification-list {
            list-style: none;
            padding: 0;
        }

        .notification-list li {
            background-color: #fff;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #4CAF50;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-list li a {
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
        }

        .notification-list li a:hover {
            text-decoration: underline;
        }

        .notification-list li .date {
            color: #555;
            font-size: 14px;
            margin-right: 10px;
        }

        .notification-list li .delete-btn {
            color: #721c24;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }

        .notification-list li .delete-btn:hover {
            background-color: #f8d7da;
        }

        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #2e7d32;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.2s;
        }

        .back-button:hover {
            background-color: #1b5e20;
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

            .notification-list li {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <li>
                    <a href="../views/notifications.php" class="notification">
                        🔔
                    </a>
                </li>
                <li>
                    <form action="../views/notifications.php" method="POST" id="lang-toggle-form">
                        <input type="hidden" name="lang" value="<?php echo $_SESSION['lang'] === 'en' ? 'fr' : 'en'; ?>">
                        <button type="submit" class="lang-toggle"><?php echo $_SESSION['lang'] === 'en' ? t('toggle_language') : t('toggle_language_en'); ?></button>
                    </form>
                </li>
                <li><a href="../logout.php" class="login"><?php echo t('logout'); ?></a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
        <div class="content-container">
            <?php if ($feedbackMessage): ?>
                <div class="<?php echo $feedbackType === 'info' ? 'info-message' : 'error-message'; ?>">
                    <?php echo htmlspecialchars($feedbackMessage); ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php elseif (empty($notifications)): ?>
                <p class="info-message"><?php echo t('no_notifications'); ?></p>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                        <li>
                            <a href="../views/repondre_reclamation.php?id=<?php echo $notification['reclamation_id']; ?>">
                                <?php echo htmlspecialchars(t($notification['message'])); ?> (<?php echo htmlspecialchars($notification['titre']); ?>)
                            </a>
                            <div>
                                <span class="date"><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></span>
                                <a href="../controllers/supprimer_notification.php?id=<?php echo $notification['id']; ?>" class="delete-btn">✖</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a href="../views/liste_reclamations.php" class="back-button"><?php echo t('back_to_list'); ?></a>
        </div>
    </main>
</body>
</html>