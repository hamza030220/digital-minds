<?php
// ajouter_avis.php

// Start session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Include translation helper
require_once '../../translate.php';

// --- Retrieve Flash Message and Form Data (if any) ---
$message = '';
$message_type = 'error'; // Default
$form_data = []; // Default empty form data

// Check for flash message from session
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_message_type'] ?? 'error';
    // Clear the flash message from session
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_message_type']);
}

// Check for form data from session (used on validation errors)
if (isset($_SESSION['form_data_flash'])) {
    $form_data = $_SESSION['form_data_flash'];
    // Clear the saved form data
    unset($_SESSION['form_data_flash']);
}
// --- End Flash Message/Data Retrieval ---

// Check login status
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Include Database connection (for notification logic)
require_once '../../CONFIG/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Define page title
$pageTitle = t('submit_review');
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Green.tn</title>
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
            background-color: #4CAF50 !important;
            border-color: #4CAF50 !important;
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
            max-width: 500px;
            margin: 0 auto;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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

        .message.success a {
            color: #2e7d32;
            text-decoration: none;
        }

        .message.success a:hover {
            text-decoration: underline;
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

        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        form label {
            color: #2e7d32;
            font-weight: bold;
            font-size: 16px;
            text-align: left;
        }

        form input,
        form textarea,
        form select {
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #fff;
            width: 100%;
            box-sizing: border-box;
        }

        form input::placeholder,
        form textarea::placeholder,
        form select::placeholder {
            color: transparent;
        }

        form textarea {
            height: 150px;
            resize: none;
        }

        .rating-container {
            display: flex;
            justify-content: flex-start;
            gap: 5px;
            margin-top: 5px;
        }

        .rating-container input[type="radio"] {
            display: none;
        }

        .rating-container label {
            font-size: 24px;
            color: #ccc;
            cursor: pointer;
        }

        .rating-container input[type="radio"]:checked ~ label,
        .rating-container label:hover,
        .rating-container label:hover ~ label {
            color: #FFD700;
        }

        .rating-container input[type="radio"]:checked + label {
            color: #FFD700;
        }

        form button {
            padding: 10px 20px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            align-self: flex-end;
            font-size: 16px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .error-message {
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
            border-color: #28a745;
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
                <img src="../../image/ve.png" alt="Green.tn Logo">
            </div>
            <nav class="nav-left">
                <ul>
                    <li><a href="../FRONT/info2.php"><?php echo t('home'); ?></a></li>
                    <li><a href="../reclamation/ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="../reclamation/liste_reclamations.php"><?php echo t('view_reclamations'); ?></a></li>
                    <li><a href="../reclamation/ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
                    <li><a href="../reclamation/mes_avis.php"><?php echo t('my_reviews'); ?></a></li>
                    <li><a href="../reclamation/chatbot.php"><?php echo t('chatbot'); ?></a></li>
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <li>
                    <form action="../reclamation/ajouter_avis.php" method="POST">
                        <input type="hidden" name="lang" value="<?php echo $_SESSION['lang'] === 'en' ? 'fr' : 'en'; ?>">
                        <button type="submit" class="lang-toggle"><?php echo $_SESSION['lang'] === 'en' ? t('toggle_language') : t('toggle_language_en'); ?></button>
                    </form>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="../BACK/logout.php" class="login"><?php echo t('logout'); ?></a></li>
                <?php else: ?>
                    <li><a href="../BACK/login.php" class="login"><?php echo t('login'); ?></a></li>
                    <li><a href="../BACK/signup.php" class="signin"><?php echo t('signup'); ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
        <div class="container">
            <?php
            // Display feedback message if it exists
            if (!empty($message)) {
                $msg_class = ($message_type === 'success') ? 'success' : 'error';
                echo "<div class='message " . $msg_class . "'>" . htmlspecialchars($message);
                if ($message_type === 'success') {
                    echo " <a href='../reclamation/ajouter_avis.php'>" . t('submit_another_review') . "</a>";
                } elseif ($message === t('login_required')) {
                    echo " <a href='../BACK/login.php'>" . t('login') . "</a>.";
                }
                echo "</div>";
            }
            ?>

            <?php if ($isLoggedIn && $message_type !== 'success'): ?>
                <form action="../../CONTROLLER/AvisController.php" method="POST" id="avisForm" novalidate>
                    <label for="titre"><?php echo t('title'); ?>:</label>
                    <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($form_data['titre'] ?? ''); ?>">
                    <span class="error-message" id="titre-error"></span>

                    <label for="description"><?php echo t('description'); ?>:</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                    <span class="error-message" id="description-error"></span>

                    <label for="note"><?php echo t('rating'); ?>:</label>
                    <div class="rating-container">
                        <?php
                        $current_note = $form_data['note'] ?? 0;
                        for ($i = 5; $i >= 1; $i--):
                        ?>
                            <input type="radio" id="note<?php echo $i; ?>" name="note" value="<?php echo $i; ?>" <?php echo $current_note == $i ? 'checked' : ''; ?>>
                            <label for="note<?php echo $i; ?>" style="margin-right: 5px;">★</label>
                        <?php endfor; ?>
                    </div>
                    <span class="error-message" id="note-error"></span>

                    <button type="submit"><?php echo t('submit_review'); ?></button>
                </form>
            <?php elseif (!$isLoggedIn && empty($message)): ?>
                <p class="message error"><?php echo t('login_required'); ?> <a href="../BACK/login.php"><?php echo t('login'); ?></a>.</p>
            <?php endif; ?>
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
                    <li><a href="../FRONT/info2.php"><?php echo t('home'); ?></a></li>
                    <li><a href="../reclamation/ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="../reclamation/liste_reclamations.php"><?php echo t('view_reclamations'); ?></a></li>
                    <li><a href="../reclamation/ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
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
            titre_required: '<?php echo t('title_required'); ?>',
            titre_length: '<?php echo t('title_length'); ?>',
            description_required: '<?php echo t('description_required'); ?>',
            description_length: '<?php echo t('description_length'); ?>',
            rating_required: '<?php echo t('rating_required'); ?>'
        };

        document.getElementById('avisForm')?.addEventListener('submit', function(event) {
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

            const note = document.querySelector('input[name="note"]:checked')?.value;
            if (!note) {
                errors.note = translations.rating_required;
                isValid = false;
            }

            ['titre', 'description', 'note'].forEach(field => {
                const errorElement = document.getElementById(`${field}-error`);
                const inputElement = document.getElementById(field);
                if (errors[field]) {
                    errorElement.textContent = errors[field];
                    errorElement.style.display = 'block';
                    if (field !== 'note') {
                        inputElement.classList.add('input-error');
                        inputElement.classList.remove('input-valid');
                    }
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                    if (field !== 'note') {
                        inputElement.classList.remove('input-error');
                        inputElement.classList.add('input-valid');
                    }
                }
            });

            if (isValid) {
                this.submit();
            } else {
                const firstInvalidField = ['titre', 'description'].find(field => errors[field]) || (errors.note ? 'note5' : null);
                if (firstInvalidField) {
                    document.getElementById(firstInvalidField).focus();
                    document.getElementById(firstInvalidField).scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        ['titre', 'description'].forEach(field => {
            document.getElementById(field).addEventListener('input', function() {
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

        document.querySelectorAll('input[name="note"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const errorElement = document.getElementById('note-error');
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            });
        });
    </script>
</body>
</html>