<?php
session_start();
require_once 'C:\xampp\htdocs\projetweb\CONFIG\db.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $age = (int)$_POST['age'];
    $gouvernorats = $_POST['gouvernorats'];

    // Validation
    $errors = [];
    if (empty($nom) || strlen($nom) < 2) {
        $errors[] = "Le nom doit contenir au moins 2 caractères.";
    }
    if (empty($prenom) || strlen($prenom) < 2) {
        $errors[] = "Le prénom doit contenir au moins 2 caractères.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    }
    if (!preg_match('/^[0-9]{8}$/', $telephone)) {
        $errors[] = "Le téléphone doit comporter 8 chiffres.";
    }
    if ($age < 5 || $age > 80) {
        $errors[] = "Âge invalide.";
    }
    if (!in_array($gouvernorats, ['Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'])) {
        $errors[] = "Gouvernorat invalide.";
    }

    // Vérifier l'unicité de l'email (exclure l'utilisateur actuel)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Cet email est déjà utilisé.";
    }

    // Gestion de l'upload de la photo
    $photo_path = $user['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_tmp = $_FILES['photo']['tmp_name'];
        $photo_type = mime_content_type($photo_tmp);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($photo_type, $allowed_types)) {
            $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $photo_path = $upload_dir . $photo_name;
            if (!move_uploaded_file($photo_tmp, $photo_path)) {
                $errors[] = "Erreur lors du téléchargement de la photo.";
            }
        } else {
            $errors[] = "Type de fichier non autorisé (JPG, PNG, GIF uniquement).";
        }
    }

    // Mise à jour si aucune erreur
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, photo = ?, age = ?, gouvernorats = ? WHERE id = ?");
        $stmt->execute([$nom, $prenom, $email, $telephone, $photo_path, $age, $gouvernorats, $user_id]);
        $_SESSION['success_message'] = "Profil mis à jour avec succès.";
        header("Location: update_profil_admin.php");
        exit();
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Définir le chemin de base pour les liens
$basePath = '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Profil - Green.tn</title>
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

        /* Sidebar Styles */
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

        /* Sidebar Toggle Button */
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

        /* Main Content */
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

        /* Section Content */
        .section-content {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideIn 0.5s ease;
            max-width: 600px;
            margin: 0 auto;
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Alerts */
        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert.success {
            background-color: #4caf50;
            color: #ffffff;
            border: 1px solid #388e3c;
        }

        .alert.error {
            background-color: #e74c3c;
            color: #ffffff;
            border: 1px solid #c0392b;
        }

        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: 600;
            color: #333;
        }

        input, select {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            width: 100%;
        }

        input[type="file"] {
            padding: 5px;
        }

        .btn {
            padding: 10px 20px;
            font-size: 14px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #388e3c;
        }

        .btn.cancel {
            background-color: #e74c3c;
        }

        .btn.cancel:hover {
            background-color: #c0392b;
        }

        .btn-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        /* Dark Mode */
        body.dark-mode {
            background: linear-gradient(45deg, #1a3c34, #2c3e50, #34495e, #2e7d32);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            color: #e0e0e0;
        }

        body.dark-mode .sidebar {
            background-color: rgba(51, 51, 51, 0.9);
        }

        body.dark-mode .sidebar-nav-link {
            color: #ffffff;
        }

        body.dark-mode .sidebar-nav-link:hover {
            background-color: #444444;
        }

        body.dark-mode .main-content {
            background: rgba(30, 30, 30, 0.8);
        }

        body.dark-mode .main-content h1 {
            color: #4caf50;
        }

        body.dark-mode .section-content {
            background-color: rgba(50, 50, 50, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .alert.success {
            background-color: #388e3c;
            border-color: #2e7d32;
        }

        body.dark-mode .alert.error {
            background-color: #e74c3c;
            border-color: #c0392b;
        }

        body.dark-mode label {
            color: #e0e0e0;
        }

        body.dark-mode input,
        body.dark-mode select {
            background-color: #444;
            color: #ffffff;
            border-color: #666;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .section-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a class="sidebar-brand" href="<?php echo $basePath; ?>dashboard.php?section=stats">
                <img src="<?php echo $basePath; ?>logo.jpg" alt="Green.tn">
            </a>
        </div>
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' && isset($_GET['section']) && $_GET['section'] === 'stats' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>dashboard.php?section=stats" data-translate="home">
                        <span class="sidebar-nav-icon"><i class="bi bi-house-door"></i></span>
                        <span class="sidebar-nav-text">Accueil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'update_profil_admin.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>update_profil_admin.php" data-translate="profile_management">
                        <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                        <span class="sidebar-nav-text">Gestion de votre profil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>reservation.php" data-translate="reservations">
                        <span class="sidebar-nav-icon"><i class="bi bi-calendar"></i></span>
                        <span class="sidebar-nav-text">Voir réservations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>reclamation.php" data-translate="complaints">
                        <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                        <span class="sidebar-nav-text">Réclamations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="velos.php?super_admin=1" data-translate="bikes_batteries">
                        <span class="sidebar-nav-icon"><i class="bi bi-bicycle"></i></span>
                        <span class="sidebar-nav-text">Vélos & Batteries</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="repairs.html" data-translate="repair_issues">
                        <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                        <span class="sidebar-nav-text">Réparer les pannes</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?php echo $currentPage === 'stations' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>stations/list.php">
                    <span class="sidebar-nav-icon"><i class="bi bi-geo-alt"></i></span>
                    <span class="sidebar-nav-text">Stations</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?php echo $currentPage === 'trajets' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>trajets/list.php">
                    <span class="sidebar-nav-icon"><i class="bi bi-map"></i></span>
                    <span class="sidebar-nav-text">Trajets</span>
                </a>
            </li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <div class="mb-2">
                <span class="text-white">Bienvenue, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
            </div>
            <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-light" data-translate="logout">
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
        <h1>Modifier Mon Profil</h1>

        <div class="section-content">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert success">
                    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <label for="nom" data-translate="name">Nom</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                
                <label for="prenom" data-translate="first_name">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                
                <label for="email" data-translate="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                
                <label for="telephone" data-translate="phone">Téléphone</label>
                <input type="text" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>" pattern="[0-9]{8}" title="8 chiffres requis" required>
                
                <label for="age" data-translate="age">Âge</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user['age']); ?>" min="5" max="80" required>
                
                <label for="gouvernorats" data-translate="governorate">Gouvernorat</label>
                <select id="gouvernorats" name="gouvernorats" required>
                    <?php
                    $gouvernorats_list = ['Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'];
                    foreach ($gouvernorats_list as $gouv) {
                        $selected = $user['gouvernorats'] === $gouv ? 'selected' : '';
                        echo "<option value=\"$gouv\" $selected>$gouv</option>";
                    }
                    ?>
                </select>
                
                <label for="photo" data-translate="profile_picture">Photo de profil</label>
                <input type="file" id="photo" name="photo" accept="image/*">
                
                <div class="btn-container">
                    <button type="submit" class="btn" data-translate="update"><i class="bi bi-save"></i> Mettre à jour</button>
                    <a href="dashboard.php" class="btn cancel" data-translate="cancel"><i class="bi bi-x"></i> Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts pour la sidebar, dark mode et traductions -->
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const sidebarToggle = document.getElementById('sidebarToggle');

            // Toggle sidebar on button click
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                main.classList.toggle('main-content-expanded');
            });

            // Handle responsive behavior
            function handleResize() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('show');
                    main.classList.remove('main-content-expanded');
                } else {
                    sidebar.classList.add('show');
                    main.classList.add('main-content-expanded');
                }
            }

            // Initial call and resize listener
            handleResize();
            window.addEventListener('resize', handleResize);

            // Dark Mode Toggle
            const toggleButton = document.getElementById('darkModeToggle');
            const body = document.body;

            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
            }

            toggleButton.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                if (body.classList.contains('dark-mode')) {
                    localStorage.setItem('darkMode', 'enabled');
                } else {
                    localStorage.removeItem('darkMode');
                }
                updateTranslations(); // Update translations after dark mode toggle
            });

            // Translation functionality
            const translations = {
                fr: {
                    home: "Accueil",
                    profile_management: "Gestion de votre profil",
                    reservations: "Réservations",
                    complaints: "Réclamations",
                    bikes_batteries: "Vélos & Batteries",
                    repair_issues: "Réparer les pannes",
                    logout: "Déconnexion",
                    dark_mode: "Mode Sombre",
                    name: "Nom",
                    first_name: "Prénom",
                    email: "Email",
                    phone: "Téléphone",
                    age: "Âge",
                    governorate: "Gouvernorats",
                    profile_picture: "Photo de profil",
                    update: "Mettre à jour",
                    cancel: "Annuler"
                },
                en: {
                    home: "Home",
                    profile_management: "Profile Management",
                    reservations: "Reservations",
                    complaints: "Complaints",
                    bikes_batteries: "Bikes & Batteries",
                    repair_issues: "Repair Issues",
                    logout: "Logout",
                    dark_mode: "Dark Mode",
                    name: "Name",
                    first_name: "First Name",
                    email: "Email",
                    phone: "Phone",
                    age: "Age",
                    governorate: "Governorate",
                    profile_picture: "Profile Picture",
                    update: "Update",
                    cancel: "Cancel"
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
            }

            // Initialize translations on page load
            updateTranslations();
        });
    </script>
</body>
</html>