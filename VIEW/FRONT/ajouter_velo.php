<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_velo = trim(isset($_POST['nom_velo']) ? $_POST['nom_velo'] : '');
    $type_velo = trim(isset($_POST['type_velo']) ? $_POST['type_velo'] : '');
    $prix_par_jour = floatval(isset($_POST['prix_par_jour']) ? $_POST['prix_par_jour'] : 0);
    $disponibilite = isset($_POST['disponibilite']) ? 1 : 0;

    if (empty($nom_velo)) {
        $errors[] = 'Le nom du vélo est requis.';
    }
    if (empty($type_velo)) {
        $errors[] = 'Le type de vélo est requis.';
    }
    if ($prix_par_jour <= 0) {
        $errors[] = 'Le prix par jour doit être supérieur à 0.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO velos (nom_velo, type_velo, prix_par_jour, disponibilite) VALUES (:nom_velo, :type_velo, :prix_par_jour, :disponibilite)");
            $stmt->execute([
                'nom_velo' => $nom_velo,
                'type_velo' => $type_velo,
                'prix_par_jour' => $prix_par_jour,
                'disponibilite' => $disponibilite
            ]);
            $success = 'Vélo ajouté avec succès !';
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de l\'ajout du vélo : ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Vélo - Green.tn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(45deg, #e9f5ec, #a8e6a3, #60c26d, #4a90e2);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            color: #333;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: -250px;
            background-color: rgba(96, 186, 151, 0.9);
            backdrop-filter: blur(5px);
            transition: left 0.3s ease;
            z-index: 1000;
        }
        .sidebar.show {
            left: 0;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
        }
        .sidebar-brand img {
            width: 60%;
            height: auto;
        }
        .sidebar-content {
            padding: 20px;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0;
        }
        .sidebar-nav-item {
            margin-bottom: 10px;
        }
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #d0f0d6;
            text-decoration: none;
            font-size: 15px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }
        .sidebar-nav-link:hover {
            background-color: #1b5e20;
            color: white;
        }
        .sidebar-nav-link.active {
            background-color: #388e3c;
            color: white;
        }
        .sidebar-nav-icon {
            margin-right: 10px;
        }
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 20px;
            text-align: center;
        }
        .sidebar-footer .btn {
            font-size: 14px;
            width: 100%;
            margin-bottom: 10px;
        }
        .sidebar-toggler {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background-color: #60BA97;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .sidebar-toggler:hover {
            background-color: #388e3c;
        }
        .main-content {
            margin-left: 0;
            padding: 40px;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            transition: margin-left 0.3s ease;
        }
        .main-content-expanded {
            margin-left: 250px;
        }
        .header-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-header {
            width: 110px;
            height: auto;
        }
        .main-content h1 {
            color: #2e7d32;
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }
        .section-content {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideIn 0.5s ease;
            margin-bottom: 40px;
        }
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background-color: #fff;
            color: #333;
        }
        .form-group input[type="checkbox"] {
            width: auto;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            font-size: 14px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #388e3c;
        }
        .btn.back {
            background-color: #6c757d;
        }
        .btn.back:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
        }
        .translate-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .translate-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            font-size: 14px;
            color: #ffffff;
            background-color: #1b5e20;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .translate-btn:hover {
            background-color: #4caf50;
        }
        .error-message {
            color: #a94442;
            font-size: 12px;
            display: block;
            margin-top: 5px;
            min-height: 20px;
        }
        body.dark-mode {
            background: linear-gradient(45deg, #1a3c34, #2c3e50, #34495e, #2e7d32);
            color: #e0e0e0;
        }
        body.dark-mode .sidebar {
            background-color: rgba(51, 51, 51, 0.9);
        }
        body.dark-mode .sidebar-nav-link {
            color: #ffffff;
        }
        body.dark-mode .main-content, body.dark-mode .section-content {
            background-color: rgba(50, 50, 50, 0.9);
        }
        body.dark-mode .form-group label {
            color: #e0e0e0;
        }
        body.dark-mode .form-group input, body.dark-mode .form-group select {
            background-color: #333;
            color: #e0e0e0;
            border-color: #555;
        }
        body.dark-mode .error-message {
            color: #ff6b6b;
        }
        @media (max-width: 992px) {
            .sidebar { left: -250px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a class="sidebar-brand" href="admin_dashboard.php?section=stats">
                <img src="logo.jpg" alt="Green.tn">
            </a>
        </div>
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="dashboard.php?section=stats" data-translate="home">
                        <span class="sidebar-nav-icon"><i class="bi bi-house-door"></i></span>
                        <span class="sidebar-nav-text">Accueil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="dashboard.php?page=gestion_utilisateurs" data-translate="profile_management">
                        <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                        <span class="sidebar-nav-text">Gestion de votre profil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="reservations.php" data-translate="reservations">
                        <span class="sidebar-nav-icon"><i class="bi bi-calendar"></i></span>
                        <span class="sidebar-nav-text">Réservations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="reclamation.php" data-translate="complaints">
                        <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                        <span class="sidebar-nav-text">Réclamations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link active" href="velos.php" data-translate="bikes_batteries">
                        <span class="sidebar-nav-icon"><i class="bi bi-bicycle"></i></span>
                        <span class="sidebar-nav-text">Vélos & Batteries</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <div class="mb-2">
                <span class="text-white">Bienvenue, Admin</span>
            </div>
            <a href="logout.php" class="btn btn-outline-light" data-translate="logout">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
            <a href="#" id="darkModeToggle" class="btn btn-outline-light mt-2" data-translate="dark_mode">
                <i class="bi bi-moon"></i> Mode Sombre
            </a>
        </div>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggler" type="button" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="main">
        <div class="header-logo">
            <img src="logo.jpg" alt="Logo Green.tn" class="logo-header">
        </div>
        <h1>Ajouter un Vélo</h1>

        <div class="section-content">
            <div class="translate-container">
                <button class="translate-btn" id="toggle-language" data-translate="language"><i class="fas fa-globe"></i> Français</button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" id="bikeForm" novalidate>
                <div class="form-group">
                    <label for="nom_velo" data-translate="name">Nom du vélo</label>
                    <input type="text" name="nom_velo" id="nom_velo" value="<?php echo htmlspecialchars(isset($_POST['nom_velo']) ? $_POST['nom_velo'] : ''); ?>" required>
                    <span class="error-message" id="nom_velo_error"><?php echo in_array('Le nom du vélo est requis.', $errors) ? htmlspecialchars('Le nom du vélo est requis.') : ''; ?></span>
                </div>
                <div class="form-group">
                    <label for="type_velo" data-translate="type">Type de vélo</label>
                    <input type="text" name="type_velo" id="type_velo" value="<?php echo htmlspecialchars(isset($_POST['type_velo']) ? $_POST['type_velo'] : ''); ?>" required>
                    <span class="error-message" id="type_velo_error"><?php echo in_array('Le type de vélo est requis.', $errors) ? htmlspecialchars('Le type de vélo est requis.') : ''; ?></span>
                </div>
                <div class="form-group">
                    <label for="prix_par_jour" data-translate="price">Prix par jour (€)</label>
                    <input type="number" name="prix_par_jour" id="prix_par_jour" step="0.01" value="<?php echo htmlspecialchars(isset($_POST['prix_par_jour']) ? $_POST['prix_par_jour'] : ''); ?>" required>
                    <span class="error-message" id="prix_par_jour_error"><?php echo in_array('Le prix par jour doit être supérieur à 0.', $errors) ? htmlspecialchars('Le prix par jour doit être supérieur à 0.') : ''; ?></span>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="disponibilite" <?php echo isset($_POST['disponibilite']) ? 'checked' : ''; ?>>
                        <span data-translate="availability">Disponible</span>
                    </label>
                </div>
                <button type="submit" class="btn" data-translate="add_bike"><i class="bi bi-plus"></i> Ajouter</button>
                <button type="button" class="btn back" onclick="history.back()" data-translate="back"><i class="bi bi-arrow-left"></i> Retour</button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const sidebarToggle = document.getElementById('sidebarToggle');

            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                main.classList.toggle('main-content-expanded');
            });

            function handleResize() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('show');
                    main.classList.remove('main-content-expanded');
                } else {
                    sidebar.classList.add('show');
                    main.classList.add('main-content-expanded');
                }
            }
            handleResize();
            window.addEventListener('resize', handleResize);

            const toggleButton = document.getElementById('darkModeToggle');
            const body = document.body;
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
            }
            toggleButton.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : '');
                updateTranslations();
            });

            const translations = {
                fr: {
                    home: "Accueil",
                    profile_management: "Gestion de votre profil",
                    reservations: "Réservations",
                    complaints: "Réclamations",
                    bikes_batteries: "Vélos & Batteries",
                    logout: "Déconnexion",
                    dark_mode: "Mode Sombre",
                    language: "Français",
                    name: "Nom du vélo",
                    type: "Type de vélo",
                    price: "Prix par jour (€)",
                    availability: "Disponible",
                    add_bike: "Ajouter",
                    back: "Retour",
                    name_required: "Le nom du vélo est requis.",
                    type_required: "Le type de vélo est requis.",
                    price_required: "Le prix par jour doit être supérieur à 0."
                },
                en: {
                    home: "Home",
                    profile_management: "Profile Management",
                    reservations: "Reservations",
                    complaints: "Complaints",
                    bikes_batteries: "Bikes & Batteries",
                    logout: "Logout",
                    dark_mode: "Dark Mode",
                    language: "English",
                    name: "Bike Name",
                    type: "Bike Type",
                    price: "Price per Day (€)",
                    availability: "Available",
                    add_bike: "Add",
                    back: "Back",
                    name_required: "The bike name is required.",
                    type_required: "The bike type is required.",
                    price_required: "The price per day must be greater than 0."
                }
            };

            let currentLanguage = localStorage.getItem('language') || 'fr';
            function updateTranslations() {
                document.querySelectorAll('[data-translate]').forEach(element => {
                    const key = element.getAttribute('data-translate');
                    if (translations[currentLanguage][key]) {
                        element.textContent = translations[currentLanguage][key];
                    }
                });
                document.getElementById('toggle-language').innerHTML = `<i class="fas fa-globe"></i> ${translations[currentLanguage].language}`;
            }

            document.getElementById('toggle-language').addEventListener('click', () => {
                currentLanguage = currentLanguage === 'fr' ? 'en' : 'fr';
                localStorage.setItem('language', currentLanguage);
                updateTranslations();
            });

            // Validation du formulaire
            const form = document.getElementById('bikeForm');
            form.addEventListener('submit', function(event) {
                const nomVelo = form.querySelector('#nom_velo');
                const typeVelo = form.querySelector('#type_velo');
                const prixParJour = form.querySelector('#prix_par_jour');
                const nomVeloError = form.querySelector('#nom_velo_error');
                const typeVeloError = form.querySelector('#type_velo_error');
                const prixParJourError = form.querySelector('#prix_par_jour_error');

                let isValid = true;

                // Réinitialiser les messages d'erreur
                nomVeloError.textContent = '';
                typeVeloError.textContent = '';
                prixParJourError.textContent = '';

                // Validation du nom du vélo
                if (!nomVelo.value.trim()) {
                    nomVeloError.textContent = translations[currentLanguage].name_required;
                    isValid = false;
                }

                // Validation du type de vélo
                if (!typeVelo.value.trim()) {
                    typeVeloError.textContent = translations[currentLanguage].type_required;
                    isValid = false;
                }

                // Validation du prix par jour
                if (!prixParJour.value || parseFloat(prixParJour.value) <= 0) {
                    prixParJourError.textContent = translations[currentLanguage].price_required;
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault();
                }
            });

            // Validation en temps réel
            ['nom_velo', 'type_velo', 'prix_par_jour'].forEach(id => {
                const input = document.getElementById(id);
                const errorSpan = document.getElementById(`${id}_error`);
                input.addEventListener('input', () => {
                    if (id === 'prix_par_jour') {
                        if (!input.value || parseFloat(input.value) <= 0) {
                            errorSpan.textContent = translations[currentLanguage].price_required;
                        } else {
                            errorSpan.textContent = '';
                        }
                    } else {
                        if (!input.value.trim()) {
                            errorSpan.textContent = translations[currentLanguage][`${id}_required`];
                        } else {
                            errorSpan.textContent = '';
                        }
                    }
                });
            });

            updateTranslations();
        });
    </script>
</body>
</html>