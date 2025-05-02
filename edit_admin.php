<?php
session_start();
require_once __DIR__ . '/models/db.php';

// Vérifier si l'utilisateur est un super-admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_GET['super_admin']) || $_GET['super_admin'] !== '1') {
    header('Location: index.php');
    exit();
}

// Vérifier si l'ID de l'admin à modifier est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php?section=users');
    exit();
}

$admin_id = (int)$_GET['id'];
$errors = [];
$success = '';

try {
    // Récupérer les informations de l'admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'admin'");
    $stmt->execute(['id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        header('Location: dashboard.php?section=users');
        exit();
    }
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim(isset($_POST['nom']) ? $_POST['nom'] : '');
    $prenom = trim(isset($_POST['prenom']) ? $_POST['prenom'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $telephone = trim(isset($_POST['telephone']) ? $_POST['telephone'] : '');
    $age = (int)(isset($_POST['age']) ? $_POST['age'] : 0);
    $gouvernorats = isset($_POST['gouvernorats']) ? $_POST['gouvernorats'] : '';

    // Validation côté serveur
    if (empty($nom) || strlen($nom) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères.';
    }
    if (empty($prenom) || strlen($prenom) < 2) {
        $errors[] = 'Le prénom doit contenir au moins 2 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    if (!preg_match('/^[0-9]{8}$/', $telephone)) {
        $errors[] = 'Le téléphone doit comporter 8 chiffres.';
    }
    if ($age < 18 || $age > 80) {
        $errors[] = 'Âge invalide (doit être entre 18 et 80 ans).';
    }
    if (!in_array($gouvernorats, ['Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'])) {
        $errors[] = 'Gouvernorat invalide.';
    }

    // Vérifier l'unicité de l'email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
    $stmt->execute(['email' => $email, 'id' => $admin_id]);
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Cet email est déjà utilisé.';
    }

    // Gestion de la photo
    $photo_path = $admin['photo'];
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
                $errors[] = 'Erreur lors du téléchargement de la photo.';
            }
        } else {
            $errors[] = 'Type de fichier non autorisé (JPG, PNG, GIF uniquement).';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, photo = :photo, age = :age, gouvernorats = :gouvernorats WHERE id = :id");
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'photo' => $photo_path,
                'age' => $age,
                'gouvernorats' => $gouvernorats,
                'id' => $admin_id
            ]);
            $success = 'Administrateur modifié avec succès !';
            // Mettre à jour les données de l'admin pour l'affichage
            $admin = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'photo' => $photo_path,
                'age' => $age,
                'gouvernorats' => $gouvernorats
            ];
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la modification de l\'administrateur : ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Administrateur - Green.tn</title>
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
        .form-group input[type="file"] {
            padding: 5px;
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
        .error {
            color: #a94442;
            font-size: 12px;
            margin-top: 5px;
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
        body.dark-mode .alert-success {
            background-color: #3c763d;
            color: #dff0d8;
        }
        body.dark-mode .alert-danger {
            background-color: #a94442;
            color: #f2dede;
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
            <a class="sidebar-brand" href="dashboard.php?section=stats">
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
                    <a class="sidebar-nav-link active" href="velos.php?super_admin=1" data-translate="bikes_batteries">
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
        <h1>Modifier un Administrateur</h1>

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

            <form method="post" id="editAdminForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nom" data-translate="name">Nom</label>
                    <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars(isset($_POST['nom']) ? $_POST['nom'] : $admin['nom']); ?>" >
                    <div class="error" id="nom_error"></div>
                </div>
                <div class="form-group">
                    <label for="prenom" data-translate="first_name">Prénom</label>
                    <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars(isset($_POST['prenom']) ? $_POST['prenom'] : $admin['prenom']); ?>" >
                    <div class="error" id="prenom_error"></div>
                </div>
                <div class="form-group">
                    <label for="email" data-translate="email">Email</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $admin['email']); ?>" >
                    <div class="error" id="email_error"></div>
                </div>
                <div class="form-group">
                    <label for="telephone" data-translate="phone">Téléphone</label>
                    <input type="text" name="telephone" id="telephone" value="<?php echo htmlspecialchars(isset($_POST['telephone']) ? $_POST['telephone'] : $admin['telephone']); ?>" pattern="[0-9]{8}" title="8 chiffres requis" >
                    <div class="error" id="telephone_error"></div>
                </div>
                <div class="form-group">
                    <label for="age" data-translate="age">Âge</label>
                    <input type="number" name="age" id="age" value="<?php echo htmlspecialchars(isset($_POST['age']) ? $_POST['age'] : $admin['age']); ?>" min="18" max="80" >
                    <div class="error" id="age_error"></div>
                </div>
                <div class="form-group">
                    <label for="gouvernorats" data-translate="governorate">Gouvernorat</label>
                    <select name="gouvernorats" id="gouvernorats" >
                        <?php
                        $gouvernorats_list = ['Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'];
                        foreach ($gouvernorats_list as $gouv) {
                            $selected = (isset($_POST['gouvernorats']) ? $_POST['gouvernorats'] : $admin['gouvernorats']) === $gouv ? 'selected' : '';
                            echo "<option value=\"$gouv\" $selected>$gouv</option>";
                        }
                        ?>
                    </select>
                    <div class="error" id="gouvernorats_error"></div>
                </div>
                <div class="form-group">
                    <label for="photo" data-translate="profile_picture">Photo de profil</label>
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif">
                    <div class="error" id="photo_error"></div>
                </div>
                <button type="submit" class="btn" data-translate="update"><i class="bi bi-pencil"></i> Modifier</button>
                <button type="button" class="btn back" onclick="history.back()" data-translate="back"><i class="bi bi-arrow-left"></i> Retour</button>
            </form>
        </div>
    </div>

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

            // Validation du formulaire
            const form = document.getElementById('editAdminForm');
            form.addEventListener('submit', function(e) {
                // Réinitialiser les messages d'erreur
                document.querySelectorAll('.error').forEach(el => el.textContent = '');

                // Récupérer les valeurs
                const nom = document.getElementById('nom').value.trim();
                const prenom = document.getElementById('prenom').value.trim();
                const email = document.getElementById('email').value.trim();
                const telephone = document.getElementById('telephone').value.trim();
                const age = parseInt(document.getElementById('age').value);
                const gouvernorats = document.getElementById('gouvernorats').value;

                let hasError = false;

                // Validation
                if (!nom || nom.length < 2) {
                    document.getElementById('nom_error').textContent = 'Le nom doit contenir au moins 2 caractères.';
                    hasError = true;
                }
                if (!prenom || prenom.length < 2) {
                    document.getElementById('prenom_error').textContent = 'Le prénom doit contenir au moins 2 caractères.';
                    hasError = true;
                }
                if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    document.getElementById('email_error').textContent = 'Email invalide.';
                    hasError = true;
                }
                if (!telephone.match(/^\d{8}$/)) {
                    document.getElementById('telephone_error').textContent = 'Le téléphone doit comporter 8 chiffres.';
                    hasError = true;
                }
                if (isNaN(age) || age < 18 || age > 80) {
                    document.getElementById('age_error').textContent = 'Âge invalide (doit être entre 18 et 80 ans).';
                    hasError = true;
                }
                if (!gouvernorats) {
                    document.getElementById('gouvernorats_error').textContent = 'Veuillez sélectionner un gouvernorat.';
                    hasError = true;
                }

                if (hasError) {
                    e.preventDefault(); // Empêcher la soumission
                }
            });

            // Traductions
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
                    name: "Nom",
                    first_name: "Prénom",
                    email: "Email",
                    phone: "Téléphone",
                    age: "Âge",
                    governorate: "Gouvernorat",
                    profile_picture: "Photo de profil",
                    update: "Modifier",
                    back: "Retour"
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
                    name: "Name",
                    first_name: "First Name",
                    email: "Email",
                    phone: "Phone",
                    age: "Age",
                    governorate: "Governorate",
                    profile_picture: "Profile Picture",
                    update: "Update",
                    back: "Back"
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

            updateTranslations();
        });
    </script>
</body>
</html>