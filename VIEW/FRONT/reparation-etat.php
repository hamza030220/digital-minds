<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Determine current language
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'fr';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
    $language = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

// Inline translations
$translations = [
    'fr' => [
        'home' => 'Accueil',
        'bikes' => 'Nos vélos',
        'about' => 'À propos de nous',
        'pricing' => 'Tarifs',
        'contact' => 'Contact',
        'reparation' => 'Réparation État',
        'reservations' => 'Réservations',
        'trajets' => 'Trajets et Stations',
        'forum' => 'Forum',
        'dark_mode' => 'Mode Sombre',
        'language' => 'Français',
        'profile_info' => 'Mes informations',
        'logout' => 'Déconnexion',
        'title_reparation' => 'Green.tn - Réparation État',
    ],
    'en' => [
        'home' => 'Home',
        'bikes' => 'Bikes',
        'about' => 'About Us',
        'pricing' => 'Pricing',
        'contact' => 'Contact',
        'reparation' => 'Repair Status',
        'reservations' => 'Reservations',
        'trajets' => 'Routes and Stations',
        'forum' => 'Forum',
        'dark_mode' => 'Dark Mode',
        'language' => 'English',
        'profile_info' => 'My Information',
        'logout' => 'Logout',
        'title_reparation' => 'Green.tn - Repair Status',
    ]
];

// Function to get translated text
function getTranslation($key, $lang, $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Get user information
$user_id = (int)$_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT prenom, nom, photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        error_log("Utilisateur introuvable pour ID: $user_id");
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_user_not_found', $language, $translations)];
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="title_reparation">Green.tn - Réparation État</title>
    <link rel="stylesheet" href="/projetweb/public/css/green.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Topbar and related styles from reservationuser.php */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #60BA97;
            color: #333;
            min-height: 100vh;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .topbar {
            width: 100%;
            background-color: #F9F5E8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .topbar.hidden {
            transform: translateY(-100%);
        }

        .topbar .logo {
            height: 40px;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: #2e7d32;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .nav-links a:hover, .nav-links .active {
            background-color: #4CAF50;
            color: #fff;
        }

        .nav-links a#toggle-language {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-icon {
            position: relative;
        }

        .top-profile-pic {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4CAF50;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .top-profile-pic:hover {
            transform: scale(1.1);
        }

        .profile-menu {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            border-radius: 6px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            min-width: 180px;
            z-index: 999;
        }

        .profile-menu.show {
            display: block;
        }

        .profile-menu-item {
            padding: 0.75rem 1rem;
            color: #2e7d32;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .profile-menu-item:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .toggle-topbar {
            cursor: pointer;
            font-size: 1.2rem;
            color: #2e7d32;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .toggle-topbar:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .show-topbar-btn {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background-color: #F9F5E8;
            padding: 0.5rem;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 1001;
            display: none;
        }

        .show-topbar-btn.show {
            display: block;
        }

        .show-topbar-btn span {
            font-size: 1.5rem;
            color: #2e7d32;
        }

        .hamburger-menu {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            cursor: pointer;
        }

        .hamburger-icon {
            width: 30px;
            height: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hamburger-icon span {
            width: 100%;
            height: 3px;
            background-color: #2e7d32;
            transition: all 0.3s ease;
        }

        .hamburger-icon.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger-icon.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-icon.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        .nav-menu {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            width: 250px;
            height: 100%;
            background-color: #F9F5E8;
            box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
            padding: 2rem 1rem;
            z-index: 999;
            flex-direction: column;
            gap: 1rem;
        }

        .nav-menu.show {
            display: flex;
        }

        .nav-menu a {
            color: #2e7d32;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .nav-menu a:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .nav-menu a#toggle-language-mobile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            max-width: 400px;
        }

        .alert {
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-size: 14px;
            animation: slideInAlert 0.3s ease;
            color: white;
        }

        .alert.success {
            background-color: #2e7d32;
            border: 1px solid #4CAF50;
        }

        .alert.error {
            background-color: #FF0000;
            border: 1px solid #CC0000;
        }

        @keyframes slideInAlert {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Existing modal styles from reparation-etat.php */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            max-width: 350px;
            width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            overflow: hidden;
            max-height: 80vh;
        }
        .modal-content h3 {
            margin-top: 0;
            color: #2e7d32;
            font-size: 1.5em;
        }
        .modal-content p {
            margin: 10px 0;
            font-size: 16px;
        }
        .modal-bike-image, .modal-stock-image {
            width: 100%;
            max-width: 150px;
            max-height: 150px;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .close-btn {
            background-color: #2e7d32;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
        }
        .close-btn:hover {
            background-color: #4CAF50;
        }
        .error-message {
            color: red;
            text-align: center;
            margin: 10px 0;
        }
        .success-message {
            color: green;
            text-align: center;
            margin: 10px 0;
        }

        /* Dark mode styles */
        body.dark-mode {
            background-color: #2e7d32;
            color: #F9F5E8;
        }

        body.dark-mode .topbar,
        body.dark-mode .show-topbar-btn,
        body.dark-mode .nav-menu {
            background-color: #F9F5E8;
        }

        @media (max-width: 768px) {
            .topbar {
                display: none;
            }

            .hamburger-menu {
                display: block;
            }

            .nav-menu {
                width: 100%;
            }

            .show-topbar-btn {
                top: 0.5rem;
                right: 3.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <img src="/projetweb/images/ve.png" alt="Logo Green.tn" class="logo">
        <div class="nav-links">
            <a href="/projetweb/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" data-translate="home"><i class="fas fa-home"></i> Accueil</a>
            <a href="/projetweb/index.php#a-nos-velos" data-translate="bikes"><i class="fas fa-bicycle"></i> Nos vélos</a>
            <a href="/projetweb/index.php#a-propos-de-nous" data-translate="about"><i class="fas fa-info-circle"></i> À propos de nous</a>
            <a href="/projetweb/index.php#pricing" data-translate="pricing"><i class="fas fa-dollar-sign"></i> Tarifs</a>
            <a href="/projetweb/VIEW/FRONT/reparation-etat.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reparation-etat.php' ? 'active' : ''; ?>" data-translate="reparation"><i class="fas fa-wrench"></i> Réparation État</a>
            <a href="/projetweb/index.php#contact" data-translate="contact"><i class="fas fa-envelope"></i> Contact</a>
            <a href="trajet-et-station.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'trajet-et-station.php' ? 'active' : ''; ?>" data-translate="trajets"><i class="fas fa-route"></i> Trajets et Stations</a>
            <a href="reservationuser.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reservationuser.php' ? 'active' : ''; ?>" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
            <a href="forum.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'forum.php' ? 'active' : ''; ?>" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>
            <a href="javascript:void(0)" id="toggle-dark-mode" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
            <a href="javascript:void(0)" id="toggle-language" data-translate="language">🌐 <?php echo $language === 'fr' ? 'Français' : 'English'; ?></a>
        </div>
        <div class="profile-icon">
            <a href="javascript:void(0)">
                <img src="user_images/<?php echo htmlspecialchars(isset($user['photo']) ? $user['photo'] : 'default.jpg'); ?>" alt="Profil" class="top-profile-pic">
            </a>
            <div class="profile-menu">
                <a href="info2.php?page=gestion_utilisateurs&action=infos" class="profile-menu-item" data-translate="profile_info">📄 Mes informations</a>
                <a href="logout.php" class="profile-menu-item logout" data-translate="logout">🚪 Déconnexion</a>
            </div>
        </div>
        <div class="toggle-topbar" onclick="toggleTopbar()">▼</div>
    </div>

    <!-- Show Topbar Button -->
    <div class="show-topbar-btn" onclick="toggleTopbar()">
        <span>▲</span>
    </div>

    <!-- Hamburger Menu -->
    <div class="hamburger-menu">
        <div class="hamburger-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    <div class="nav-menu">
        <a href="/projetweb/index.php" data-translate="home"><i class="fas fa-home"></i> Accueil</a>
        <a href="/projetweb/index.php#a-nos-velos" data-translate="bikes"><i class="fas fa-bicycle"></i> Nos vélos</a>
        <a href="/projetweb/index.php#a-propos-de-nous" data-translate="about"><i class="fas fa-info-circle"></i> À propos de nous</a>
        <a href="/projetweb/index.php#pricing" data-translate="pricing"><i class="fas fa-dollar-sign"></i> Tarifs</a>
        <a href="/projetweb/VIEW/FRONT/reparation-etat.php" data-translate="reparation"><i class="fas fa-wrench"></i> Réparation État</a>
        <a href="/projetweb/index.php#contact" data-translate="contact"><i class="fas fa-envelope"></i> Contact</a>
        <a href="trajet-et-station.php" data-translate="trajets"><i class="fas fa-route"></i> Trajets et Stations</a>
        <a href="reservationuser.php" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
        <a href="forum.php" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>
        <a href="javascript:void(0)" id="toggle-dark-mode-mobile" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
        <a href="javascript:void(0)" id="toggle-language-mobile" data-translate="language">🌐 <?php echo $language === 'fr' ? 'Français' : 'English'; ?></a>
        <a href="info2.php?page=gestion_utilisateurs&action=infos" data-translate="profile_info">📄 Mes informations</a>
        <a href="logout.php" data-translate="logout">🚪 Déconnexion</a>
    </div>

   

        <section class="reparation-section" id="reparation-etat">
            <h2>Vélos en Réparation</h2>
            <div class="search-bar">
                <input type="text" id="search-input" oninput="searchBikes()" placeholder="Rechercher un vélo...">
                <button onclick="searchBikes()">Rechercher</button>
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">Tous</button>
                <button class="filter-btn" data-filter="en-cours">En cours</button>
                <button class="filter-btn" data-filter="en-attente-de-pièces">En attente de pièces</button>
                <button class="filter-btn" data-filter="terminé">Terminé</button>
            </div>
            <div class="reparation-grid" id="reparation-grid"></div>
        </section>
    </main>

    <div class="modal" id="stock-modal">
        <div class="modal-content">
            <h3>Détails du Stock</h3>
            <div id="stock-details"></div>
            <button class="close-btn" onclick="closeModal('stock-modal')">Fermer</button>
        </div>
    </div>

    <div class="modal" id="details-modal">
        <div class="modal-content">
            <h3>Détails de la Réparation</h3>
            <div id="repair-details"></div>
            <div id="review-form" style="display: none;">
                <p>Évaluer cette réparation</p>
                <div class="star-rating">
                    <span class="star" data-value="1" role="button" aria-label="1 star">★</span>
                    <span class="star" data-value="2" role="button" aria-label="2 stars">★</span>
                    <span class="star" data-value="3" role="button" aria-label="3 stars">★</span>
                    <span class="star" data-value="4" role="button" aria-label="4 stars">★</span>
                    <span class="star" data-value="5" role="button" aria-label="5 stars">★</span>
                </div>
                <p class="rating-display" id="rating-display"></p>
                <button class="close-btn" onclick="submitReview()">Soumettre l'évaluation</button>
            </div>
            <p id="review-average" style="display: none;">Évaluation moyenne: <span></span></p>
            <button class="close-btn" onclick="closeModal('details-modal')">Fermer</button>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-logo">
                    <img src="/projetweb/images/ho.png" alt="Green.tn Logo">
                </div>
                <div class="social-icons">
                    <a href="https://instagram.com"><img src="/projetweb/images/insta.png" alt="Instagram"></a>
                    <a href="https://facebook.com"><img src="/projetweb/images/fb.png" alt="Facebook"></a>
                    <a href="https://twitter.com"><img src="/projetweb/images/x.png" alt="Twitter"></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="/projetweb/index.php" data-translate="home">Accueil</a></li>
                    <li><a href="/projetweb/index.php#a-nos-velos" data-translate="bikes">Nos vélos</a></li>
                    <li><a href="/projetweb/index.php#a-propos-de-nous" data-translate="about">À propos de nous</a></li>
                    <li><a href="/projetweb/index.php#pricing" data-translate="pricing">Tarifs</a></li>
                    <li><a href="/projetweb/index.php#contact" data-translate="contact">Contact</a></li>
                    <li><a href="/projetweb/VIEW/FRONT/reparation-etat.php" data-translate="reparation">Réparation État</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 data-translate="contact">Contact</h3>
                <p><img src="/projetweb/images/location.png" alt="Location Icon"> Eprit technopole</p>
                <p><img src="/projetweb/images/telephone.png" alt="Phone Icon"> +216245678</p>
                <p><img src="/projetweb/images/mail.png" alt="Email Icon"> <a href="mailto:Green@green.com">Green@green.com</a></p>
            </div>
        </div>
    </footer>

    <script src="/projetweb/public/js/reparation-etat.js"></script>
    <script>
        let isTopbarVisible = true;
        function toggleTopbar() {
            const topbar = document.querySelector('.topbar');
            const showBtn = document.querySelector('.show-topbar-btn');
            if (isTopbarVisible) {
                topbar.classList.add('hidden');
                showBtn.classList.add('show');
            } else {
                topbar.classList.remove('hidden');
                showBtn.classList.remove('show');
            }
            isTopbarVisible = !isTopbarVisible;
        }

        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            let currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            const topbar = document.querySelector('.topbar');
            const showBtn = document.querySelector('.show-topbar-btn');
            if (currentScroll > lastScrollTop && currentScroll > 100) {
                topbar.classList.add('hidden');
                showBtn.classList.add('show');
                isTopbarVisible = false;
            } else if (currentScroll < lastScrollTop) {
                topbar.classList.remove('hidden');
                showBtn.classList.remove('show');
                isTopbarVisible = true;
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        });

        document.querySelector('.top-profile-pic').addEventListener('click', function(event) {
            event.stopPropagation();
            document.querySelector('.profile-menu').classList.toggle('show');
        });

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.profile-icon')) {
                document.querySelector('.profile-menu').classList.remove('show');
            }
        });

        document.querySelector('.hamburger-menu').addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('show');
            document.querySelector('.hamburger-icon').classList.toggle('active');
        });

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }

        document.getElementById('toggle-dark-mode').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
        });

        document.getElementById('toggle-dark-mode-mobile').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
        });

        let currentLanguage = '<?php echo $language; ?>';
        let translations = <?php echo json_encode($translations); ?>;

        function applyTranslations(lang) {
            document.querySelectorAll('[data-translate]').forEach(element => {
                const key = element.getAttribute('data-translate');
                if (translations[lang] && translations[lang][key]) {
                    element.textContent = translations[lang][key];
                }
            });

            const langButton = document.getElementById('toggle-language');
            const langButtonMobile = document.getElementById('toggle-language-mobile');
            if (langButton) {
                langButton.textContent = `🌐 ${translations[lang]['language']}`;
            }
            if (langButtonMobile) {
                langButtonMobile.textContent = `🌐 ${translations[lang]['language']}`;
            }

            document.title = translations[lang]['title_reparation'] || 'Green.tn';
            localStorage.setItem('language', lang);
        }

        function toggleLanguage() {
            currentLanguage = currentLanguage === 'fr' ? 'en' : 'fr';
            applyTranslations(currentLanguage);
            window.location.href = `?lang=${currentLanguage}${window.location.search.replace(/lang=[a-z]{2}/, '')}`;
        }

        document.getElementById('toggle-language')?.addEventListener('click', toggleLanguage);
        document.getElementById('toggle-language-mobile')?.addEventListener('click', toggleLanguage);

        applyTranslations(currentLanguage);

        function showAlert(type, message) {
            const alertContainer = document.querySelector('.alert-container');
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            alert.innerHTML = message;
            alertContainer.appendChild(alert);
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        <?php
        if (isset($_SESSION['alert'])) {
            $alert = $_SESSION['alert'];
            echo "showAlert('{$alert['type']}', '{$alert['message']}');";
            unset($_SESSION['alert']);
        }
        ?>
    </script>
</body>
</html>