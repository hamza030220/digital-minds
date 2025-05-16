<?php
// repondre_reclamation.php

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/'));

// Include translation helper
require_once '../../translate.php';

// Include Database connection
require_once '../../CONFIG/database.php';

// Include ResponseController
require_once '../../CONTROLLER/ResponseController.php';

// Initialize database and controller
$database = new Database();
$db = $database->getConnection();
$responseController = new ResponseController();

// Check if user is logged in and not an admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../../VIEW/BACK/login.php?error=' . urlencode(t('error_session_required')));
    exit;
}

// Determine user role
$isAdmin = false;
$query = "SELECT role FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && $user['role'] === 'admin') {
    $isAdmin = true;
}

if ($isAdmin) {
    header('Location: ../../VIEW/reclamation/ajouter_reclamation.php?error=' . urlencode(t('access_restricted_non_admin')));
    exit;
}

// Vérifier si l'ID de la réclamation est passé
if (!isset($_GET['id'])) {
    echo t('missing_reclamation_id');
    exit;
}

$reclamation_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
if (!$reclamation_id) {
    echo t('invalid_reclamation_id');
    exit;
}

// Récupérer les informations de la réclamation
$stmt = $db->prepare("SELECT * FROM reclamations WHERE id = ?");
$stmt->execute([$reclamation_id]);
$reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reclamation) {
    echo t('reclamation_not_found');
    exit;
}

// Check if an admin has responded
$admin_has_responded = false;
$stmt = $db->prepare("SELECT COUNT(*) FROM reponses WHERE reclamation_id = ? AND role = 'admin'");
$stmt->execute([$reclamation_id]);
$admin_response_count = $stmt->fetchColumn();
if ($admin_response_count > 0) {
    $admin_has_responded = true;
}

// Handle form submission
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin_has_responded) {
        $result = $responseController->createResponse(
            $reclamation_id,
            $_SESSION['user_id'],
            $_POST['reponse'] ?? '',
            'user' // Force role to 'user' since only non-admins can reach this point
        );
        
        if ($result['status'] === 'success') {
            $success_message = $result['message'];
            
            // Insert a notification for the user who created the reclamation
            $notification_message = 'new_response_to_your_reclamation'; // Translation key for the notification message
            $stmt = $db->prepare("INSERT INTO notification (user_id, reclamation_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$reclamation['utilisateur_id'], $reclamation_id, $notification_message]);
        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = t('admin_response_required');
    }
}

// Pagination for responses
$reponses_per_page = 3; // 3 responses per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Current page
$offset = ($page - 1) * $reponses_per_page; // Calculate offset

// Count total responses
$total_reponses_query = "SELECT COUNT(*) FROM reponses WHERE reclamation_id = ?";
$stmt = $db->prepare($total_reponses_query);
$stmt->execute([$reclamation_id]);
$total_reponses = $stmt->fetchColumn();
$total_pages = ceil($total_reponses / $reponses_per_page);

// Get existing responses with pagination
$reponses_query = "
    SELECT * FROM reponses 
    WHERE reclamation_id = ? 
    ORDER BY date_creation ASC 
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($reponses_query);
$stmt->bindValue(1, $reclamation_id, PDO::PARAM_INT);
$stmt->bindValue(2, $reponses_per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$pageTitle = t('respond_reclamation') . ' - Green.tn';
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" href="../../image/ve.png" type="image/png">
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
            text-align: left;
        }

        h3 {
            color: #2e7d32;
            font-size: 20px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        p {
            line-height: 1.6;
            margin-bottom: 15px;
            color: #333;
        }

        .success-message,
        .error-message,
        .info-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            border: 1px solid transparent;
            text-align: center;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .info-message {
            background-color: #e7f3fe;
            color: #31708f;
            border-color: #d6e9f7;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #fff;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.2s;
        }

        textarea:focus {
            border-color: #2e7d32;
            outline: none;
        }

        button {
            padding: 10px 20px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #1b5e20;
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

        hr {
            border: 0;
            border-top: 1px solid #4CAF50;
            margin: 20px 0;
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

        a {
            color: #2e7d32;
            text-decoration: none;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #1b5e20;
        }

        .error-message.form-error {
            color: #721c24;
            font-size: 0.85em;
            margin-top: 5px;
            display: none;
            text-align: left;
        }

        .input-error {
            border-color: #721c24;
        }

        .input-valid {
            border-color: #155724;
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
                <img src="../../image/ve.png" alt="Green.tn Logo">
            </div>
            <nav class="nav-left">
                <ul>
                    <li><a href="../BACK/info2.php"><?php echo t('home'); ?></a></li>
                    <li><a href="../../VIEW/reclamation/ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="../../VIEW/reclamation/liste_reclamations.php"><?php echo t('view_reclamations'); ?></a></li>
                    <li><a href="../../VIEW/reclamation/ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
                    <li><a href="../../VIEW/reclamation/mes_avis.php"><?php echo t('my_reviews'); ?></a></li>
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <li>
                    <form action="../../VIEW/reclamation/repondre_reclamation.php?id=<?php echo htmlspecialchars($reclamation['id']); ?>" method="POST" id="lang-toggle-form">
                        <input type="hidden" name="lang" value="<?php echo $_SESSION['lang'] === 'en' ? 'fr' : 'en'; ?>">
                        <button type="submit" class="lang-toggle"><?php echo $_SESSION['lang'] === 'en' ? t('toggle_language') : t('toggle_language_en'); ?></button>
                    </form>
                </li>
                <li><a href=".../BACK/logout.php" class="login"><?php echo t('logout'); ?></a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2><?php echo t('respond_reclamation'); ?></h2>

        <div class="content-container">
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <h3><?php echo t('reclamation'); ?>: <?php echo htmlspecialchars($reclamation['titre']); ?></h3>
            <p><strong><?php echo t('description'); ?>:</strong> <?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>

            <?php if ($admin_has_responded): ?>
                <form action="../../VIEW/reclamation/repondre_reclamation.php?id=<?php echo htmlspecialchars($reclamation['id']); ?>" method="POST" id="responseForm" novalidate>
                    <label for="reponse"><?php echo t('your_response'); ?>:</label>
                    <textarea id="reponse" name="reponse"></textarea>
                    <div class="error-message form-error" id="reponse-error"></div>
                    <button type="submit"><?php echo t('submit_response'); ?></button>
                </form>
            <?php else: ?>
                <p class="error-message"><?php echo t('admin_response_required'); ?></p>
            <?php endif; ?>

            <h3><?php echo t('existing_responses'); ?>:</h3>
            <?php if ($total_reponses == 0): ?>
                <p class="info-message"><?php echo t('no_responses'); ?></p>
            <?php else: ?>
                <?php foreach ($reponses as $reponse): ?>
                    <div class='reponse'>
                        <p><strong><?php echo t('response_by'); ?> <?php echo htmlspecialchars(t($reponse['role'])); ?>:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($reponse['contenu'])); ?></p>
                        <p><i><?php echo t('response_date'); ?> <?php echo htmlspecialchars($reponse['date_creation']); ?></i></p>
                    </div><hr>
                <?php endforeach; ?>

                <!-- Pagination for responses -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="../../VIEW/reclamation/repondre_reclamation.php?id=<?php echo $reclamation_id; ?>&page=<?php echo $page - 1; ?>"><?php echo t('previous', 'Précédent'); ?></a>
                    <?php else: ?>
                        <a href="#" class="disabled"><?php echo t('previous', 'Précédent'); ?></a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="../../VIEW/reclamation/reclamation/repondre_reclamation.php?id=<?php echo $reclamation_id; ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'current' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="../../VIEW/reclamation/reclamation/repondre_reclamation.php?id=<?php echo $reclamation_id; ?>&page=<?php echo $page + 1; ?>"><?php echo t('next', 'Suivant'); ?></a>
                    <?php else: ?>
                        <a href="#" class="disabled"><?php echo t('next', 'Suivant'); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p><a href="../../VIEW/reclamation/liste_reclamations.php"><?php echo t('back_to_list'); ?></a></p>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-logo">
                    <img src="../../image/ho.png" alt="Green.tn Logo">
                </div>
                <div class="social-icons">
                    <a href="https://instagram.com"><img src="../../image/insta.png" alt="Instagram"></a>
                    <a href="https://facebook.com"><img src="../../image/fb.png" alt="Facebook"></a>
                    <a href="https://twitter.com"><img src="../../image/x.png" alt="Twitter"></a>
                </div>
            </div>
            <div class="footer-section">
                <h3><?php echo t('navigation'); ?></h3>
                <ul>
                    <li><a href="../BACK/info2.php"><?php echo t('home'); ?></a></li>
                    <li><a href="../../VIEW/reclamation/ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="#a-propos-de-nous"><?php echo t('about_us'); ?></a></li>
                    <li><a href="#contact"><?php echo t('contact'); ?></a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3><?php echo t('contact'); ?></h3>
                <p>
                    <img src="../../image/location.png" alt="Location Icon">
                    <?php echo t('address'); ?>
                </p>
                <p>
                    <img src="../../image/telephone.png" alt="Phone Icon">
                    <?php echo t('phone'); ?>
                </p>
                <p>
                    <img src="../../image/mail.png" alt="Email Icon">
                    <a href="mailto:Green@green.com"><?php echo t('email'); ?></a>
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Pass PHP translations to JavaScript
        const translations = {
            response_required: '<?php echo t('response_required'); ?>',
            response_length: '<?php echo t('response_length'); ?>'
        };

        const responseForm = document.getElementById('responseForm');
        if (responseForm) {
            responseForm.addEventListener('submit', function(event) {
                event.preventDefault();
                let isValid = true;
                const errors = {};

                const reponse = document.getElementById('reponse').value.trim();
                if (!reponse) {
                    errors.reponse = translations.response_required;
                    isValid = false;
                } else if (reponse.length < 10 || reponse.length > 1000) {
                    errors.reponse = translations.response_length;
                    isValid = false;
                }

                const errorElement = document.getElementById('reponse-error');
                const inputElement = document.getElementById('reponse');
                if (errors.reponse) {
                    errorElement.textContent = errors.reponse;
                    errorElement.style.display = 'block';
                    inputElement.classList.add('input-error');
                    inputElement.classList.remove('input-valid');
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                    inputElement.classList.remove('input-error');
                    inputElement.classList.add('input-valid');
                }

                if (isValid) {
                    this.submit();
                } else {
                    inputElement.focus();
                    inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            document.getElementById('reponse').addEventListener('input', function() {
                const errorElement = document.getElementById('reponse-error');
                let error = '';

                const value = this.value.trim();
                if (!value) error = translations.response_required;
                else if (value.length < 10 || value.length > 1000) error = translations.response_length;

                if (error) {
                    errorElement.textContent = error;
                    errorElement.style.display = 'block';
                    this.classList.add('input-error');
                    this.classList.remove('input-valid');
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                    this.classList.remove('input-error');
                    this.classList.add('input-valid');
                }
            });
        }
    </script>
</body>
</html>