<?php
// ajouter_reclamation.php (View - Now also the entry point)

// Start session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin racine du projet
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Include translation helper
require_once ROOT_PATH . '/translate.php';

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

// Define page title
$pageTitle = t('new_reclamation');
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

        .voice-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            margin-bottom: 15px;
            transition: background-color 0.3s ease;
        }

        .voice-btn:hover {
            background-color: #1b5e20;
        }

        .voice-btn img {
            width: 20px;
            height: 20px;
        }

        .voice-status {
            margin-top: 10px;
            font-style: italic;
            color: #555;
            text-align: center;
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
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <li>
                    <form action="../views/ajouter_reclamation.php" method="POST" id="lang-toggle-form">
                        <input type="hidden" name="lang" value="<?php echo $_SESSION['lang'] === 'en' ? 'fr' : 'en'; ?>">
                        <button type="submit" class="lang-toggle"><?php echo $_SESSION['lang'] === 'en' ? t('toggle_language') : t('toggle_language_en'); ?></button>
                    </form>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="../logout.php" class="login"><?php echo t('logout'); ?></a></li>
                <?php else: ?>
                    <li><a href="../login.php" class="login"><?php echo t('login'); ?></a></li>
                    <li><a href="../signup.php" class="signin"><?php echo t('signup'); ?></a></li>
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
                echo "<div class='message " . $msg_class . "'>" . htmlspecialchars($message) . "</div>";
            }
            ?>

            <?php if ($isLoggedIn): ?>
                <button class="voice-btn" onclick="startVoiceRecognition()">
                    <img src="../image/mic.png" alt="Microphone Icon">
                    <?php echo t('voice_submit'); ?>
                </button>
                <div class="voice-status" id="voiceStatus"><?php echo t('voice_status_ready'); ?></div>

                <form action="../controllers/ReclamationController.php" method="POST" id="reclamationForm" novalidate>
                    <label for="titre"><?php echo t('Title'); ?>:</label>
                    <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($form_data['titre'] ?? ''); ?>">
                    <span class="error-message" id="titre-error"></span>

                    <label for="description"><?php echo t('description'); ?>:</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                    <span class="error-message" id="description-error"></span>

                    <label for="lieu"><?php echo t('location'); ?>:</label>
                    <input type="text" id="lieu" name="lieu" value="<?php echo htmlspecialchars($form_data['lieu'] ?? ''); ?>">
                    <span class="error-message" id="lieu-error"></span>

                    <label for="type_probleme"><?php echo t('type'); ?>:</label>
                    <select id="type_probleme" name="type_probleme">
                        <option value=""><?php echo t('select_option'); ?></option>
                        <?php
                        $current_type = $form_data['type_probleme'] ?? '';
                        $options = [
                            'mecanique' => t('mechanical'),
                            'batterie' => t('battery'),
                            'ecran' => t('screen'),
                            'pneu' => t('tire'),
                            'autre' => t('other')
                        ];
                        foreach ($options as $value => $label) {
                            $selected = ($value === $current_type) ? ' selected' : '';
                            echo "<option value=\"" . htmlspecialchars($value) . "\"$selected>" . htmlspecialchars($label) . "</option>";
                        }
                        ?>
                    </select>
                    <span class="error-message" id="type_probleme-error"></span>

                    <button type="submit"><?php echo t('submit_reclamation'); ?></button>
                </form>
            <?php elseif (empty($message)): ?>
                <p class="message error"><?php echo t('login_required'); ?> <a href="../login.php"><?php echo t('login'); ?></a>.</p>
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
            type_required: '<?php echo t('type_required'); ?>',
            voice_status_listening: '<?php echo t('voice_status_listening'); ?>',
            voice_status_processing: '<?php echo t('voice_status_processing'); ?>',
            voice_status_completed: '<?php echo t('voice_status_completed'); ?>',
            voice_status_error: '<?php echo t('voice_status_error'); ?>',
            voice_not_supported: '<?php echo t('voice_not_supported'); ?>'
        };

        // Speech Recognition Setup
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        let recognition;

        if (SpeechRecognition) {
            recognition = new SpeechRecognition();
            recognition.lang = '<?php echo $_SESSION['lang'] === 'fr' ? 'fr-FR' : 'en-US'; ?>';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            recognition.onstart = function() {
                document.getElementById('voiceStatus').textContent = translations.voice_status_listening;
            };

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript.toLowerCase();
                document.getElementById('voiceStatus').textContent = translations.voice_status_processing;

                // Simple parsing of the transcribed text
                let titre = '';
                let description = transcript;
                let lieu = '';
                let type_probleme = '';

                // Detect location (e.g., "à Tunis" or "in Tunis")
                const lieuKeywords = ['à', 'at', 'in'];
                lieuKeywords.forEach(keyword => {
                    const index = transcript.indexOf(keyword + ' ');
                    if (index !== -1) {
                        lieu = transcript.substring(index + keyword.length + 1).split(' ')[0];
                        lieu = lieu.charAt(0).toUpperCase() + lieu.slice(1);
                    }
                });

                // Detect type of problem
                const types = ['mecanique', 'batterie', 'ecran', 'pneu', 'autre'];
                types.forEach(type => {
                    if (transcript.includes(type)) {
                        type_probleme = type;
                    }
                });

                // Create a simple title based on the description
                titre = description.split(' ').slice(0, 3).join(' ');

                // Fill the form fields
                const titreField = document.getElementById('titre');
                const descriptionField = document.getElementById('description');
                const lieuField = document.getElementById('lieu');
                const typeProblemeField = document.getElementById('type_probleme');

                titreField.value = titre.charAt(0).toUpperCase() + titre.slice(1);
                descriptionField.value = description.charAt(0).toUpperCase() + description.slice(1);
                if (lieu) lieuField.value = lieu;
                if (type_probleme) typeProblemeField.value = type_probleme;

                // Trigger input events to validate fields
                [titreField, descriptionField, lieuField, typeProblemeField].forEach(field => {
                    field.dispatchEvent(new Event('input'));
                });

                document.getElementById('voiceStatus').textContent = translations.voice_status_completed;
            };

            recognition.onerror = function(event) {
                document.getElementById('voiceStatus').textContent = translations.voice_status_error + ' ' + event.error;
            };

            recognition.onend = function() {
                if (document.getElementById('voiceStatus').textContent === translations.voice_status_listening) {
                    document.getElementById('voiceStatus').textContent = translations.voice_status_completed;
                }
            };
        }

        function startVoiceRecognition() {
            if (!SpeechRecognition) {
                alert(translations.voice_not_supported);
                return;
            }

            try {
                recognition.start();
            } catch (e) {
                console.error(e);
                document.getElementById('voiceStatus').textContent = translations.voice_status_error;
            }
        }

        // Form validation
        document.getElementById('reclamationForm').addEventListener('submit', function(event) {
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
            const validTypes = ['mecanique', 'batterie', 'ecran', 'pneu', 'autre'];
            if (!typeProbleme || !validTypes.includes(typeProbleme)) {
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
                } else if (field === 'lieu') {
                    const value = this.value.trim();
                    if (!value) error = translations.lieu_required;
                    else if (value.length < 3 || value.length > 100) error = translations.lieu_length;
                    else if (!/^[a-zA-Z\s]+$/.test(value)) error = translations.lieu_invalid;
                } else if (field === 'type_probleme') {
                    const value = this.value;
                    const validTypes = ['mecanique', 'batterie', 'ecran', 'pneu', 'autre'];
                    if (!value || !validTypes.includes(value)) error = translations.type_required;
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