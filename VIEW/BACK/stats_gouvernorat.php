<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

// Load translations
$translations_file = __DIR__ . '/assets/translations.json';
$translations = file_exists($translations_file) ? json_decode(file_get_contents($translations_file), true) : [];

// Determine current language
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'fr';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
    $language = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

// Function to get translated text
function getTranslation($key, $lang = 'fr', $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_user_not_found', $language, $translations)];
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = (int)$_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
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

// Fetch reservation statistics by gouvernorat
try {
    $stmt = $pdo->query("SELECT gouvernorat, COUNT(*) as reservation_count FROM reservation GROUP BY gouvernorat ORDER BY reservation_count DESC");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Statistiques par gouvernorat récupérées - " . count($stats) . " gouvernorats");
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="title_stats_gouvernorat">Green.tn - Statistiques par Gouvernorat</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif; /* Updated from Poppins */
}

body {
    background-color: #60BA97; /* Updated from #e8f5e9 */
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
    background-color: #F9F5E8; /* Updated from #f5f5f5 */
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
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.nav-links a:hover, .nav-links .active {
    background-color: #4CAF50; /* Updated from #e8f5e9 */
    color: #fff; /* Updated from #1b5e20 */
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
    border: 2px solid #4CAF50; /* Updated from #e8f5e9 */
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
    background-color: #F9F5E8; /* Updated from #fff */
    border: 1px solid #4CAF50; /* Updated from #e0e0e0 */
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
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.profile-menu-item:hover {
    background-color: #4CAF50; /* Updated from #f5f5f5 */
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
    background-color: #4CAF50; /* Updated from #e8f5e9 */
    color: #fff;
}

.show-topbar-btn {
    position: fixed;
    top: 1rem;
    right: 1rem;
    background-color: #F9F5E8; /* Updated from #f5f5f5 */
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
    background-color: #F9F5E8; /* Updated from #f9fafb */
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
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.nav-menu a:hover {
    background-color: #4CAF50; /* Updated from #e8f5e9 */
    color: #fff;
}

.nav-menu a#toggle-language-mobile {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.main-content {
    padding: 5rem 2rem 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.stats-card {
    background: #F9F5E8; /* Updated from rgba(255, 255, 255, 0.95) */
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.stats-card h2 {
    color: #2e7d32;
    font-size: 1.8rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 1.5rem;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.stats-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.stats-table th, .stats-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #4CAF50; /* Updated from #e0e0e0 */
}

.stats-table th {
    background-color: #F9F5E8; /* Updated from #f5f5f5 */
    color: #2e7d32;
    font-weight: 600;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.stats-table td {
    color: #333;
}

.chart-container {
    margin-top: 2rem;
    max-width: 100%;
}

.back-btn {
    background-color: #2e7d32; /* Updated from #4caf50 */
    color: white;
    padding: 8px 12px;
    font-size: 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-bottom: 1rem;
    transition: background-color 0.3s;
    text-decoration: none;
    display: inline-block;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.back-btn:hover {
    background-color: #4CAF50; /* Updated from #388e3c */
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
    border: 1px solid #4CAF50; /* Updated from #1b5e20 */
}

.alert.error {
    background-color: #FF0000; /* Updated from #e74c3c */
    border: 1px solid #CC0000; /* Updated from #c0392b */
}

@keyframes slideInAlert {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

.footer {
    background-color: #F9F5E8; /* Updated from #f5f5f5 */
    color: #333; /* Updated from #4b5563 */
    padding: 2rem;
    text-align: center;
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated from Poppins */
}

.footer-container {
    max-width: 1000px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.footer-column h3 {
    font-size: 1.2rem;
    color: #2e7d32;
    margin-bottom: 0.5rem;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.footer-column p, .footer-column a {
    font-size: 0.9rem;
    color: #555; /* Updated from #2e7d32 */
    text-decoration: none;
    margin-bottom: 0.5rem;
    display: block;
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated from Poppins */
}

.footer-column a:hover {
    color: #4CAF50; /* Updated from #1b5e20 */
}

.footer-bottom {
    margin-top: 1rem;
    font-size: 0.9rem;
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated from Poppins */
}

body.dark-mode {
    background-color: #2e7d32; /* Updated from #1a1a1a */
    color: #F9F5E8; /* Updated from #e0e0e0 */
}

body.dark-mode .topbar,
body.dark-mode .show-topbar-btn,
body.dark-mode .nav-menu,
body.dark-mode .footer {
    background-color: #F9F5E8; /* Updated from #2a2a2a */
}

body.dark-mode .stats-card {
    background: #F9F5E8; /* Updated from rgba(50, 50, 50, 0.95) */
}

body.dark-mode .stats-card h2 {
    color: #4CAF50;
}

body.dark-mode .stats-table th {
    background-color: #F9F5E8; /* Updated from #444 */
    color: #4CAF50;
}

body.dark-mode .stats-table td {
    color: #333; /* Updated from #e0e0e0 */
}

body.dark-mode .back-btn {
    background-color: #2e7d32; /* Updated from #4caf50 */
}

body.dark-mode .back-btn:hover {
    background-color: #4CAF50; /* Updated from #388e3c */
}

@media (max-width: 768px) {
    .topbar {
        display: none;
    }

    .hamburger-menu {
        display: block;
    }

    .main-content {
        padding: 4rem 1.5rem 1.5rem;
    }

    .stats-card {
        padding: 1.5rem;
    }

    .stats-table {
        display: block;
        overflow-x: auto;
    }

    .nav-menu {
        width: 100%;
    }

    .show-topbar-btn {
        top: 0.5rem;
        right: 3.5rem;
    }
}

@media (max-width: 480px) {
    .stats-card {
        padding: 1rem;
    }

    .stats-card h2 {
        font-size: 1.5rem;
    }
}
    </style>
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <img src="logo.jpg" alt="Logo Green.tn" class="logo">
        <div class="nav-links">
            <a href="info2.php?page=accueil" class="<?php echo basename($_SERVER['PHP_SELF']) == 'info2.php' ? 'active' : ''; ?>" data-translate="home"><i class="fas fa-home"></i> Accueil</a>
            <a href="info2.php#a-nos-velos" data-translate="bikes"><i class="fas fa-bicycle"></i> Nos vélos</a>
            <a href="info2.php#a-propos-de-nous" data-translate="about"><i class="fas fa-info-circle"></i> À propos de nous</a>
            <a href="info2.php#pricing" data-translate="pricing"><i class="fas fa-dollar-sign"></i> Tarifs</a>
            <a href="../reclamation/liste_reclamations.php" data-translate="Reclamation"><i class="fas fa-envelope"></i> Reclamation</a>
            <a href="reservationuser.php" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
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
        <a href="info2.php?page=accueil" data-translate="home"><i class="fas fa-home"></i> Accueil</a>
        <a href="info2.php#a-nos-velos" data-translate="bikes"><i class="fas fa-bicycle"></i> Nos vélos</a>
        <a href="info2.php#a-propos-de-nous" data-translate="about"><i class="fas fa-info-circle"></i> À propos de nous</a>
        <a href="info2.php#pricing" data-translate="pricing"><i class="fas fa-dollar-sign"></i> Tarifs</a>
        <a href="../reclamation/liste_reclamations.php" data-translate="Reclamation"><i class="fas fa-envelope"></i> Reclamation</a>
        <a href="reservationuser.php" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
        <a href="javascript:void(0)" id="toggle-dark-mode-mobile" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
        <a href="javascript:void(0)" id="toggle-language-mobile" data-translate="language">🌐 <?php echo $language === 'fr' ? 'Français' : 'English'; ?></a>
        <a href="info2.php?page=gestion_utilisateurs&action=infos" data-translate="profile_info">📄 Mes informations</a>
        <a href="logout.php" data-translate="logout">🚪 Déconnexion</a>
    </div>

    <!-- Alert Container -->
    <div class="alert-container"></div>

    <!-- Main Content -->
    <div class="main-content">
        <?php
        if (isset($_SESSION['alert'])) {
            $alert = $_SESSION['alert'];
            echo "<script>showAlert('{$alert['type']}', '{$alert['message']}');</script>";
            unset($_SESSION['alert']);
        }
        ?>

        <div class="stats-card">
            <a href="reservationuser.php" class="back-btn" data-translate="back">Retour</a>
            <h2 data-translate="stats_gouvernorat">Statistiques par Gouvernorat</h2>
            <?php if (empty($stats)): ?>
                <p data-translate="no_stats">Aucune statistique disponible.</p>
            <?php else: ?>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th data-translate="gouvernorat">Gouvernorat</th>
                            <th data-translate="reservation_count">Nombre de Réservations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['gouvernorat']); ?></td>
                                <td><?php echo htmlspecialchars($stat['reservation_count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="chart-container">
                    <canvas id="gouvernoratChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3 data-translate="footer_about">À propos</h3>
                <p data-translate="footer_about_text">Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.</p>
            </div>
            <div class="footer-column">
                <h3 data-translate="footer_contact">Contact</h3>
                <p><a href="tel:+21624531890" title="Appeler +216 24 531 890" class="phone-link">📞 +216 24 531 890</a></p>
                <p><a href="mailto:contact@green.tn">📧 contact@green.tn</a></p>
                <p><a href="https://www.facebook.com/GreenTN" target="_blank">📱 Facebook</a></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p data-translate="footer_copyright">© 2025 Green.tn – Tous droits réservés</p>
        </div>
    </footer>

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

        document.querySelectorAll('.phone-link').forEach(link => {
            link.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        });

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }

        document.getElementById('toggle-dark-mode').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
            updateChartColors();
        });

        document.getElementById('toggle-dark-mode-mobile').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
            updateChartColors();
        });

        let currentLanguage = localStorage.getItem('language') || '<?php echo $language; ?>';
        let translations = {
            fr: {
                home: "Accueil",
                bikes: "Nos vélos",
                about: "À propos de nous",
                pricing: "Tarifs",
                contact: "Contact",
                reservations: "Réservations",
                dark_mode: "Mode Sombre",
                language: "Français",
                profile_info: "Mes informations",
                logout: "Déconnexion",
                title_stats_gouvernorat: "Green.tn - Statistiques par Gouvernorat",
                stats_gouvernorat: "Statistiques par Gouvernorat",
                no_stats: "Aucune statistique disponible.",
                gouvernorat: "Gouvernorat",
                reservation_count: "Nombre de Réservations",
                back: "Retour",
                footer_about: "À propos",
                footer_about_text: "Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.",
                footer_contact: "Contact",
                footer_copyright: "© 2025 Green.tn – Tous droits réservés",
                error_user_not_found: "Utilisateur non trouvé.",
                error_database: "Erreur de base de données. Veuillez réessayer plus tard."
            },
            en: {
                home: "Home",
                bikes: "Bikes",
                about: "About Us",
                pricing: "Pricing",
                contact: "Contact",
                reservations: "Reservations",
                dark_mode: "Dark Mode",
                language: "English",
                profile_info: "My Information",
                logout: "Logout",
                title_stats_gouvernorat: "Green.tn - Statistics by Governorate",
                stats_gouvernorat: "Statistics by Governorate",
                no_stats: "No statistics available.",
                gouvernorat: "Governorate",
                reservation_count: "Number of Reservations",
                back: "Back",
                footer_about: "About",
                footer_about_text: "Green.tn promotes sustainable mobility through eco-friendly bike rental solutions in Tunisia.",
                footer_contact: "Contact",
                footer_copyright: "© 2025 Green.tn – All rights reserved",
                error_user_not_found: "User not found.",
                error_database: "Database error. Please try again later."
            }
        };

        fetch('/assets/translations.json')
            .then(response => response.json())
            .then(data => {
                translations = { ...translations, ...data };
                applyTranslations(currentLanguage);
            })
            .catch(error => {
                console.error('Error loading translations:', error);
                applyTranslations(currentLanguage);
            });

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

            document.title = translations[lang]['title_stats_gouvernorat'] || 'Green.tn';
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

        // Chart initialization
        const ctx = document.getElementById('gouvernoratChart')?.getContext('2d');
        let chartInstance = null;

        function updateChartColors() {
            const isDarkMode = document.body.classList.contains('dark-mode');
            const backgroundColors = <?php echo json_encode(array_map(function($stat) { return '#2e7d32'; }, $stats)); ?>;
            const borderColors = <?php echo json_encode(array_map(function($stat) { return '#1b5e20'; }, $stats)); ?>;

            if (chartInstance) {
                chartInstance.data.datasets[0].backgroundColor = backgroundColors;
                chartInstance.data.datasets[0].borderColor = borderColors;
                chartInstance.options.scales.x.grid.color = isDarkMode ? '#666' : '#e0e0e0';
                chartInstance.options.scales.y.grid.color = isDarkMode ? '#666' : '#e0e0e0';
                chartInstance.options.scales.x.ticks.color = isDarkMode ? '#e0e0e0' : '#333';
                chartInstance.options.scales.y.ticks.color = isDarkMode ? '#e0e0e0' : '#333';
                chartInstance.update();
            }
        }

        if (ctx) {
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($stats, 'gouvernorat')); ?>,
                    datasets: [{
                        label: '<?php echo getTranslation('reservation_count', $language, $translations); ?>',
                        data: <?php echo json_encode(array_column($stats, 'reservation_count')); ?>,
                        backgroundColor: <?php echo json_encode(array_map(function($stat) { return '#2e7d32'; }, $stats)); ?>,
                        borderColor: <?php echo json_encode(array_map(function($stat) { return '#1b5e20'; }, $stats)); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                color: '#e0e0e0'
                            },
                            ticks: {
                                color: '#333'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e0e0e0'
                            },
                            ticks: {
                                color: '#333',
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#333'
                            }
                        }
                    }
                }
            });

            updateChartColors();
        }
    </script>
</body>
</html>