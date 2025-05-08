<?php
// modifier_reclamation.php

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin racineSweet du projet
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Include translation helper
require_once ROOT_PATH . '/translate.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?error=' . urlencode(t('error_session_required')));
    exit;
}

// Connexion à la base de données using Database class
require_once ROOT_PATH . '/config/database.php';

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die(t('error_database_connection'));
}

// Initialize variables
$reclamation_id = null;
$reclamation = null;
$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'
$can_modify = true; // Flag to determine if modification is allowed

if (isset($_GET['id'])) {
    $reclamation_id_temp = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

    if ($reclamation_id_temp !== false) {
        $reclamation_id = $reclamation_id_temp;

        try {
            // Fetch reclamation details
            $stmt = $pdo->prepare("SELECT * FROM reclamations WHERE id = ?");
            $stmt->execute([$reclamation_id]);
            $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reclamation) {
                $feedback_message = t('reclamation_not_found');
                $feedback_type = 'error';
            } else {
                // Check if an admin has responded
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM reponses WHERE reclamation_id = ? AND role = 'admin'");
                $stmt->execute([$reclamation_id]);
                $admin_response_count = $stmt->fetchColumn();

                if ($admin_response_count > 0) {
                    $can_modify = false;
                    $feedback_message = t('modification_not_allowed');
                    $feedback_type = 'error';
                }
            }
        } catch (PDOException $e) {
            error_log("Error fetching reclamation ID {$reclamation_id}: " . $e->getMessage());
            $feedback_message = t('error_fetching_reclamation');
            $feedback_type = 'error';
            $reclamation = null;
        }

        // Handle form submission if modification is allowed
        if ($can_modify && $reclamation && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $lieu = trim($_POST['lieu'] ?? '');
            $type_probleme = $_POST['type_probleme'] ?? '';

            $errors = [];
            if (empty($titre)) {
                $errors['titre'] = t('titre_required');
            } elseif (strlen($titre) < 5 || strlen($titre) > 100) {
                $errors['titre'] = t('titre_length');
            }

            if (empty($description)) {
                $errors['description'] = t('description_required');
            } elseif (strlen($description) < 10 || strlen($description) > 500) {
                $errors['description'] = t('description_length');
            }

            if (empty($lieu)) {
                $errors['lieu'] = t('lieu_required');
            } elseif (strlen($lieu) < 3 || strlen($lieu) > 100) {
                $errors['lieu'] = t('lieu_length');
            } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lieu)) {
                $errors['lieu'] = t('lieu_invalid');
            }

            $valid_types = ['mecanique', 'batterie', 'ecran', 'pneu', 'infrastructure', 'autre'];
            if (!in_array($type_probleme, $valid_types)) {
                $errors['type_probleme'] = t('type_required');
            }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE reclamations SET titre = ?, description = ?, lieu = ?, type_probleme = ? WHERE id = ?");
                    $success = $stmt->execute([$titre, $description, $lieu, $type_probleme, $reclamation_id]);

                    if ($success) {
                        $feedback_message = t('reclamation_updated');
                        $feedback_type = 'success';
                        $stmt = $pdo->prepare("SELECT * FROM reclamations WHERE id = ?");
                        $stmt->execute([$reclamation_id]);
                        $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $feedback_message = t('error_updating_reclamation');
                        $feedback_type = 'error';
                    }
                } catch (PDOException $e) {
                    error_log("Error updating reclamation ID {$reclamation_id}: " . $e->getMessage());
                    $feedback_message = t('error_database_update');
                    $feedback_type = 'error';
                }
            } else {
                $feedback_message = t('validation_errors') . implode(', ', $errors);
                $feedback_type = 'error';
            }
        }
    } else {
        $feedback_message = t('invalid_reclamation_id');
        $feedback_type = 'error';
    }
} else {
    $feedback_message = t('missing_reclamation_id');
    $feedback_type = 'error';
}

$titre_value = $reclamation ? htmlspecialchars($reclamation['titre']) : '';
$description_value = $reclamation ? htmlspecialchars($reclamation['description']) : '';
$lieu_value = $reclamation ? htmlspecialchars($reclamation['lieu']) : '';
$type_probleme_value = $reclamation ? $reclamation['type_probleme'] : '';
$pageTitle = t('edit_reclamation') . ' - Green.tn';
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #fff;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
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
                    <form action="../views/modifier_reclamation.php?id=<?php echo htmlspecialchars($reclamation_id); ?>" method="POST" id="lang-toggle-form">
                        <input type="hidden" name="lang" value="<?php echo $_SESSION['lang'] === 'en' ? 'fr' : 'en'; ?>">
                        <button type="submit" class="lang-toggle"><?php echo $_SESSION['lang'] === 'en' ? t('toggle_language') : t('toggle_language_en'); ?></button>
                    </form>
                </li>
                <li><a href="../logout.php" class="login"><?php echo t('logout'); ?></a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2><?php echo t('edit_reclamation'); ?></h2>

        <div class="content-container">
            <?php if ($feedback_message): ?>
                <div class="<?php echo $feedback_type === 'success' ? 'success-message' : 'error-message'; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($reclamation && $can_modify): ?>
                <form action="../views/modifier_reclamation.php?id=<?php echo htmlspecialchars($reclamation_id); ?>" method="POST" id="reclamationForm" novalidate>
                    <div class="form-group">
                        <label for="titre"><?php echo t('title'); ?>:</label>
                        <input type="text" id="titre" name="titre" value="<?php echo $titre_value; ?>">
                        <div class="error-message form-error" id="titre-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="description"><?php echo t('description'); ?>:</label>
                        <textarea id="description" name="description"><?php echo $description_value; ?></textarea>
                        <div class="error-message form-error" id="description-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="lieu"><?php echo t('location'); ?>:</label>
                        <input type="text" id="lieu" name="lieu" value="<?php echo $lieu_value; ?>">
                        <div class="error-message form-error" id="lieu-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="type_probleme"><?php echo t('type'); ?>:</label>
                        <select id="type_probleme" name="type_probleme">
                            <option value=""><?php echo t('select_option'); ?></option>
                            <option value="mecanique" <?php if ($type_probleme_value == 'mecanique') echo 'selected'; ?>><?php echo t('mechanical'); ?></option>
                            <option value="batterie" <?php if ($type_probleme_value == 'batterie') echo 'selected'; ?>><?php echo t('battery'); ?></option>
                            <option value="ecran" <?php if ($type_probleme_value == 'ecran') echo 'selected'; ?>><?php echo t('screen'); ?></option>
                            <option value="pneu" <?php if ($type_probleme_value == 'pneu') echo 'selected'; ?>><?php echo t('tire'); ?></option>
                            <option value="infrastructure" <?php if ($type_probleme_value == 'infrastructure' || $type_probleme_value == 'Infrastructure') echo 'selected'; ?>><?php echo t('infrastructure'); ?></option>
                            <option value="autre" <?php if ($type_probleme_value == 'autre' || $type_probleme_value == 'Autre') echo 'selected'; ?>><?php echo t('other'); ?></option>
                        </select>
                        <div class="error-message form-error" id="type_probleme-error"></div>
                    </div>

                    <button type="submit"><?php echo t('update_reclamation'); ?></button>
                    <a href="../views/liste_reclamations.php" style="margin-left: 10px;"><?php echo t('cancel'); ?></a>
                    <p><a href="../views/liste_reclamations.php"><?php echo t('back_to_list'); ?></a></p>
                </form>
            <?php elseif ($reclamation && !$can_modify): ?>
                <p class="error-message"><a href="../views/liste_reclamations.php"><?php echo t('back_to_list'); ?></a></p>
            <?php elseif (!$feedback_message): ?>
                <p class="info-message"><?php echo t('loading'); ?></p>
            <?php endif; ?>
            <?php if ($reclamation === null && $feedback_message): ?>
                <p class="error-message"><a href="../views/liste_reclamations.php"><?php echo t('back_to_list'); ?></a></p>
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

    <script>
        // Pass PHP translations to JavaScript
        const translations = {
            titre_required: '<?php echo t('titre_required'); ?>',
            titre_length: '<?php echo t('titre_length'); ?>',
            description_required: '<?php echo t('description_required'); ?>',
            description_length: '<?php echo t('description_length'); ?>',
            lieu_required: '<?php echo t('lieu_required'); ?>',
            lieu_length: '<?php echo t('lieu_length'); ?>',
            lieu_invalid: '<?php echo t('lieu_invalid'); ?>',
            type_required: '<?php echo t('type_required'); ?>'
        };

        document.getElementById('reclamationForm')?.addEventListener('submit', function(event) {
            event.preventDefault();
            let isValid = true;
            const errors = {};

            const titre = document.getElementById('titre').value.trim();
            if (!titre) {
                errors.titre = translations.titre_required;
                isValid = false;
            } else if (titre.length < 5 || titre.length > 100) {
                errors.titre = translations.titre_length;
                isValid = false;
            }

            const description = document.getElementById('description').value.trim();
            if (!description) {
                errors.description = translations.description_required;
                isValid = false;
            } else if (description.length < 10 || description.length > 500) {
                errors.description = translations.description_length;
                isValid = false;
            }

            const lieu = document.getElementById('lieu').value.trim();
            if (!lieu) {
                errors.lieu = translations.lieu_required;
                isValid = false;
            } else if (lieu.length < 3 || lieu.length > 100) {
                errors.lieu = translations.lieu_length;
                isValid = false;
            } else if (!/^[a-zA-Z\s]+$/.test(lieu)) {
                errors.lieu = translations.lieu_invalid;
                isValid = false;
            }

            const typeProbleme = document.getElementById('type_probleme').value;
            const validTypes = ['mecanique', 'batterie', 'ecran', 'pneu', 'infrastructure', 'autre'];
            if (!validTypes.includes(typeProbleme)) {
                errors.type_probleme = translations.type_required;
                isValid = false;
            }

            ['titre', 'description', 'lieu', 'type_probleme'].forEach(field => {
                const errorElement = document.getElementById(`${field}-error`);
                const inputElement = document.getElementById(field);
                if (errors[field]) {
                    errorElement.textContent = errors[field];
                    errorElement.style.display = 'block';
                    inputElement.classList.add('input-error');
                    inputElement.classList.remove('input-valid');
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                    inputElement.classList.remove('input-error');
                    inputElement.classList.add('input-valid');
                }
            });

            if (isValid) {
                this.submit();
            } else {
                const firstInvalidField = ['titre', 'description', 'lieu', 'type_probleme'].find(field => errors[field]);
                if (firstInvalidField) {
                    document.getElementById(firstInvalidField).focus();
                    document.getElementById(firstInvalidField).scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        ['titre', 'description', 'lieu', 'type_probleme'].forEach(field => {
            document.getElementById(field)?.addEventListener('input', function() {
                const errorElement = document.getElementById(`${field}-error`);
                let error = '';

                if (field === 'titre') {
                    const value = this.value.trim();
                    if (!value) error = translations.titre_required;
                    else if (value.length < 5 || value.length > 100) error = translations.titre_length;
                } else if (field === 'description') {
                    const value = this.value.trim();
                    if (!value) error = translations.description_required;
                    else if (value.length < 10 || value.length > 500) error = translations.description_length;
                } else if (field === 'lieu') {
                    const value = this.value.trim();
                    if (!value) error = translations.lieu_required;
                    else if (value.length < 3 || value.length > 100) error = translations.lieu_length;
                    else if (!/^[a-zA-Z\s]+$/.test(value)) error = translations.lieu_invalid;
                } else if (field === 'type_probleme') {
                    const value = this.value;
                    const validTypes = ['mecanique', 'batterie', 'ecran', 'pneu', 'infrastructure', 'autre'];
                    if (!validTypes.includes(value)) error = translations.type_required;
                }

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
        });
    </script>
</body>
</html>