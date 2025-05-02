<?php
session_start();
require_once __DIR__ . '/models/db.php';

// Load translations
$translations_file = __DIR__ . '/assets/translations.json';
$translations = json_decode(file_get_contents($translations_file), true);

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
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

// Fetch available bikes
try {
    $stmt = $pdo->query("SELECT * FROM bikes WHERE available = 1");
    $bikes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($bikes)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_no_bikes', $language, $translations)];
    }
} catch (PDOException $e) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    $bikes = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($bikes)) {
    $id_velo = (int)$_POST['id_velo'];
    $date_debut = $_POST['date_debut'];
    $duree_reservation = $_POST['duree_reservation'];
    $gouvernorat = trim($_POST['gouvernorat']);
    $telephone = trim($_POST['telephone']);
    $date_fin = date('Y-m-d', strtotime($date_debut . " + $duree_reservation days"));
    $date_reservation = date('Y-m-d H:i:s');

    // Validation
    $errors = [];
    if (empty($id_velo) || !in_array($id_velo, array_column($bikes, 'id'))) {
        $errors[] = getTranslation('error_bike_invalid', $language, $translations);
    }
    if (empty($date_debut) || strtotime($date_debut) < strtotime(date('Y-m-d'))) {
        $errors[] = getTranslation('error_start_date_invalid', $language, $translations);
    }
    if (!in_array($duree_reservation, ['1', '7', '30'])) {
        $errors[] = getTranslation('error_duration_invalid', $language, $translations);
    }
    if (empty($gouvernorat)) {
        $errors[] = getTranslation('error_gouvernorat_empty', $language, $translations);
    }
    if (empty($telephone) || !preg_match('/^[0-9+\-\s]{8,15}$/', $telephone)) {
        $errors[] = getTranslation('error_telephone_invalid', $language, $translations);
    }

    // Check bike availability
    try {
        $stmt = $pdo->prepare("SELECT * FROM reservation WHERE id_velo = ? AND statut = 'confirmed' AND (
            (date_debut <= ? AND date_fin >= ?) OR
            (date_debut <= ? AND date_fin >= ?)
        )");
        $stmt->execute([$id_velo, $date_debut, $date_debut, $date_fin, $date_fin]);
        if ($stmt->rowCount() > 0) {
            $errors[] = getTranslation('error_bike_unavailable', $language, $translations);
        }
    } catch (PDOException $e) {
        $errors[] = getTranslation('error_database', $language, $translations);
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO reservation (id_client, id_velo, date_debut, date_fin, gouvernorat, telephone, duree_reservation, date_reservation, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $id_velo, $date_debut, $date_fin, $gouvernorat, $telephone, $duree_reservation, $date_reservation]);
            $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('success_reservation', $language, $translations)];
            header("Location: reservationuser.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = getTranslation('error_database', $language, $translations);
        }
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="title_add_reservation">Green.tn - Ajouter une Réservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #e8f5e9;
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
            background-color: #f5f5f5;
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
        }

        .nav-links a:hover, .nav-links .active {
            background-color: #e8f5e9;
            color: #1b5e20;
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
            border: 2px solid #e8f5e9;
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
            background-color: #fff;
            border: 1px solid #e0e0e0;
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
        }

        .profile-menu-item:hover {
            background-color: #f5f5f5;
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
            background-color: #e8f5e9;
        }

        .show-topbar-btn {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background-color: #f5f5f5;
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
            background-color: #f9fafb;
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
        }

        .nav-menu a:hover {
            background-color: #e8f5e9;
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

        .reservation-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .reservation-card h2 {
            color: #2e7d32;
            font-size: 1.8rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .reservation-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .reservation-form label {
            font-weight: 600;
            color: #333;
        }

        .reservation-form select,
        .reservation-form input {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            width: 100%;
        }

        .reservation-form .btn-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .reservation-form .btn {
            padding: 10px 20px;
            font-size: 14px;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .reservation-form .btn.submit {
            background-color: #2e7d32;
        }

        .reservation-form .btn.submit:hover {
            background-color: #1b5e20;
        }

        .reservation-form .btn.cancel {
            background-color: #e74c3c;
        }

        .reservation-form .btn.cancel:hover {
            background-color: #c0392b;
        }

        .bike-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 1.5rem;
        }

        .bike-card {
            background-color: #f9f9f9;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .bike-card:hover {
            transform: translateY(-5px);
        }

        .bike-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .bike-card .bike-info {
            padding: 15px;
            text-align: center;
        }

        .bike-card .bike-info h3 {
            font-size: 1.2rem;
            color: #2e7d32;
            margin-bottom: 0.5rem;
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
            border: 1px solid #1b5e20;
        }

        .alert.error {
            background-color: #e74c3c;
            border: 1px solid #c0392b;
        }

        @keyframes slideInAlert {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .footer {
            background-color: #f5f5f5;
            color: #4b5563;
            padding: 2rem;
            text-align: center;
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
        }

        .footer-column p, .footer-column a {
            font-size: 0.9rem;
            color: #2e7d32;
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
        }

        .footer-column a:hover {
            color: #1b5e20;
        }

        .footer-bottom {
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        body.dark-mode .topbar,
        body.dark-mode .show-topbar-btn,
        body.dark-mode .nav-menu,
        body.dark-mode .footer {
            background-color: #2a2a2a;
        }

        body.dark-mode .reservation-card {
            background: rgba(50, 50, 50, 0.95);
        }

        body.dark-mode .reservation-card h2 {
            color: #4caf50;
        }

        body.dark-mode .reservation-form label {
            color: #e0e0e0;
        }

        body.dark-mode .reservation-form input,
        body.dark-mode .reservation-form select {
            background: #444;
            color: #e0e0e0;
            border-color: #666;
        }

        body.dark-mode .bike-card {
            background-color: #3a506b;
        }

        body.dark-mode .bike-card .bike-info h3 {
            color: #4caf50;
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

            .reservation-card {
                padding: 1.5rem;
            }

            .bike-grid {
                grid-template-columns: 1fr;
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
            .reservation-card {
                padding: 1rem;
            }

            .reservation-card h2 {
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
        <a href="info2.php#contact" data-translate="contact"><i class="fas fa-envelope"></i> Contact</a>
        <a href="reservationuser.php" class="active" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
        <a href="javascript:void(0)" id="toggle-dark-mode" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
        <a href="javascript:void(0)" id="toggle-language" data-translate="language">🌐 Français</a>
    </div>
    <div class="profile-icon">
        <a href="javascript:void(0)">
            <img src="user_images/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profil" class="top-profile-pic">
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
    <a href="info2.php#contact" data-translate="contact"><i class="fas fa-envelope"></i> Contact</a>
    <a href="reservationuser.php" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
    <a href="javascript:void(0)" id="toggle-dark-mode-mobile" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
    <a href="javascript:void(0)" id="toggle-language-mobile" data-translate="language">🌐 Français</a>
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

    <div class="reservation-card">
        <h2 data-translate="add_reservation">Ajouter une Réservation</h2>
        <?php if (empty($bikes)): ?>
            <p data-translate="error_no_bikes">Aucun vélo disponible pour le moment.</p>
        <?php else: ?>
            <div class="bike-grid">
                <?php foreach ($bikes as $bike): ?>
                    <div class="bike-card">
                        <img src="<?php echo htmlspecialchars($bike['image']); ?>" alt="<?php echo htmlspecialchars($bike['type']); ?>">
                        <div class="bike-info">
                            <h3><?php echo htmlspecialchars($bike['type']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="reservation-form">
                <label for="id_velo" data-translate="select_bike">Sélectionner un vélo</label>
                <select id="id_velo" name="id_velo" required>
                    <option value="" disabled selected data-translate="select_bike">Choisir un vélo</option>
                    <?php foreach ($bikes as $bike): ?>
                        <option value="<?php echo $bike['id']; ?>"><?php echo htmlspecialchars($bike['type']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="date_debut" data-translate="start_date">Date de début</label>
                <input type="date" id="date_debut" name="date_debut" required min="<?php echo date('Y-m-d'); ?>">
                <label for="duree_reservation" data-translate="duration">Durée</label>
                <select id="duree_reservation" name="duree_reservation" required>
                    <option value="" disabled selected data-translate="select_duration">Choisir une durée</option>
                    <option value="1" data-translate="day">1 Jour</option>
                    <option value="7" data-translate="week">1 Semaine</option>
                    <option value="30" data-translate="month">1 Mois</option>
                </select>
                <label for="gouvernorat" data-translate="gouvernorat">Gouvernorat</label>
                <input type="text" id="gouvernorat" name="gouvernorat" required placeholder="<?php echo getTranslation('gouvernorat_placeholder', $language, $translations); ?>">
                <label for="telephone" data-translate="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" required placeholder="<?php echo getTranslation('telephone_placeholder', $language, $translations); ?>">
                <div class="btn-container">
                    <button type="submit" class="btn submit" data-translate="reserve">Réserver</button>
                    <a href="reservationuser.php" class="btn cancel" data-translate="cancel">Annuler</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    <div class="footer-container">
        <div class="footer-column">
            <h3 data-translate="footer_about">À propos</h3>
            <p data-translate="footer_about_text">Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.</p>
        </div>
        <div class="footer-column">
            <h3 data-translate="footer_contact">Contact</h3>
            <p><a href="tel:+21624531890">📞 2453 1890</a></p>
            <p><a href="mailto:contact@green.tn">📧 contact@green.tn</a></p>
            <p><a href="https://www.facebook.com/GreenTN" target="_blank">📱 Facebook</a></p>
        </div>
    </div>
    <div class="footer-bottom">
        <p data-translate="footer_copyright">© 2025 Green.tn – Tous droits réservés</p>
    </div>
</div>

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
            title_add_reservation: "Green.tn - Ajouter une Réservation",
            add_reservation: "Ajouter une Réservation",
            select_bike: "Sélectionner un vélo",
            start_date: "Date de début",
            duration: "Durée",
            select_duration: "Choisir une durée",
            gouvernorat: "Gouvernorat",
            gouvernorat_placeholder: "Entrez votre gouvernorat",
            telephone: "Téléphone",
            telephone_placeholder: "Entrez votre numéro de téléphone",
            reserve: "Réserver",
            cancel: "Annuler",
            day: "1 Jour",
            week: "1 Semaine",
            month: "1 Mois",
            footer_about: "À propos",
            footer_about_text: "Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.",
            footer_contact: "Contact",
            footer_copyright: "© 2025 Green.tn – Tous droits réservés",
            error_bike_invalid: "Vélo sélectionné invalide.",
            error_start_date_invalid: "La date de début doit être aujourd'hui ou plus tard.",
            error_duration_invalid: "Durée sélectionnée invalide.",
            error_bike_unavailable: "Ce vélo n'est pas disponible pour les dates sélectionnées.",
            error_no_bikes: "Aucun vélo disponible pour le moment.",
            error_database: "Erreur de base de données. Veuillez réessayer plus tard.",
            error_gouvernorat_empty: "Le gouvernorat est requis.",
            error_telephone_invalid: "Numéro de téléphone invalide.",
            success_reservation: "Réservation effectuée avec succès ! En attente de confirmation."
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
            title_add_reservation: "Green.tn - Add a Reservation",
            add_reservation: "Add a Reservation",
            select_bike: "Select a Bike",
            start_date: "Start Date",
            duration: "Duration",
            select_duration: "Choose a Duration",
            gouvernorat: "Governorate",
            gouvernorat_placeholder: "Enter your governorate",
            telephone: "Phone",
            telephone_placeholder: "Enter your phone number",
            reserve: "Reserve",
            cancel: "Cancel",
            day: "1 Day",
            week: "1 Week",
            month: "1 Month",
            footer_about: "About",
            footer_about_text: "Green.tn promotes sustainable mobility through eco-friendly bike rental solutions in Tunisia.",
            footer_contact: "Contact",
            footer_copyright: "© 2025 Green.tn – All rights reserved",
            error_bike_invalid: "Selected bike is invalid.",
            error_start_date_invalid: "Start date must be today or later.",
            error_duration_invalid: "Selected duration is invalid.",
            error_bike_unavailable: "This bike is not available for the selected dates.",
            error_no_bikes: "No bikes available at the moment.",
            error_database: "Database error. Please try again later.",
            error_gouvernorat_empty: "Governorate is required.",
            error_telephone_invalid: "Invalid phone number.",
            success_reservation: "Reservation successfully created! Awaiting confirmation."
        }
    };

    fetch('/projet/assets/translations.json')
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

        document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
            const key = element.getAttribute('data-translate-placeholder');
            if (translations[lang] && translations[lang][key]) {
                element.placeholder = translations[lang][key];
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

        document.title = translations[lang]['title_add_reservation'] || 'Green.tn';
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
</script>
</body>
</html>