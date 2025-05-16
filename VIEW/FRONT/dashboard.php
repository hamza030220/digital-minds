<?php
session_start();
require_once  'C:\xampp\htdocs\projetweb\CONFIG\db.php';


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Vérifier si les données de l'utilisateur sont disponibles
if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

// Vérifier les rôles
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_technicien = isset($_SESSION['role']) && $_SESSION['role'] === 'technicien';
$is_user = isset($_SESSION['role']) && $_SESSION['role'] === 'user';

// Handle AJAX user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update_user'])) {
    header('Content-Type: application/json');
    if (!$is_admin) {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
        exit();
    }

    $target_user_id = $_POST['user_id'];
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

    // Check email uniqueness (exclude current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $target_user_id]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Cet email est déjà utilisé.";
    }

    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_tmp = $_FILES['photo']['tmp_name'];
        $photo_type = mime_content_type($photo_tmp);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($photo_type, $allowed_types)) {
            $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $photo_path = $upload_dir . $photo_name;
            if (!move_uploaded_file($photo_tmp, $photo_path)) {
                $errors[] = "Erreur lors du téléchargement de la photo.";
            }
        } else {
            $errors[] = "Type de fichier non autorisé (JPG, PNG, GIF uniquement).";
        }
    } else {
        // Fetch existing photo
        $stmt = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $photo_path = $stmt->fetchColumn();
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, photo = ?, age = ?, gouvernorats = ? WHERE id = ?");
        $stmt->execute([$nom, $prenom, $email, $telephone, $photo_path, $age, $gouvernorats, $target_user_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Informations mises à jour avec succès.',
            'user' => [
                'id' => $target_user_id,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'age' => $age,
                'gouvernorats' => $gouvernorats,
                'photo' => $photo_path
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    }
    exit();
}

// Récupérer les notifications pour les admins
$notifications = [];
if ($is_admin) {
    $stmt = $pdo->prepare("SELECT id, message, created_at FROM notifications WHERE is_read = FALSE ORDER BY created_at DESC");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Si l'utilisateur est un administrateur, afficher la gestion des utilisateurs
if ($is_admin) {
    // Initialisation des filtres
    $nom_filter = isset($_GET['nom']) ? $_GET['nom'] : '';
    $email_filter = isset($_GET['email']) ? $_GET['email'] : '';
    $age_filter = isset($_GET['age_range']) ? $_GET['age_range'] : '';
    $age_sort = isset($_GET['age_sort']) ? $_GET['age_sort'] : '';
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $users_per_page = 3;

    // Requête SQL
    $sql = "SELECT id, nom, prenom, email, telephone, role, age, gouvernorats, cin FROM users WHERE 1=1";
    $params = [];

    // Filtrage par nom
    if (!empty($nom_filter)) {
        $sql .= " AND nom LIKE :nom";
        $params[':nom'] = '%' . $nom_filter . '%';
    }

    // Filtrage par email
    if (!empty($email_filter)) {
        $sql .= " AND email LIKE :email";
        $params[':email'] = '%' . $email_filter . '%';
    }

    // Filtrage par tranche d'âge
    if (!empty($age_filter)) {
        switch ($age_filter) {
            case '5-10':
                $sql .= " AND age BETWEEN 5 AND 10";
                break;
            case '15-19':
                $sql .= " AND age BETWEEN 15 AND 19";
                break;
            case '21-39':
                $sql .= " AND age BETWEEN 21 AND 39";
                break;
            case '40-50':
                $sql .= " AND age BETWEEN 40 AND 50";
                break;
            case '50+':
                $sql .= " AND age > 50";
                break;
        }
    }

    // Tri par âge
    if (!empty($age_sort)) {
        $sql .= " ORDER BY age " . ($age_sort === 'asc' ? 'ASC' : 'DESC');
    }

    // Exécution de la requête pour compter le total
    $count_sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    if (!empty($nom_filter)) {
        $count_sql .= " AND nom LIKE :nom";
    }
    if (!empty($email_filter)) {
        $count_sql .= " AND email LIKE :email";
    }
    if (!empty($age_filter)) {
        switch ($age_filter) {
            case '5-10':
                $count_sql .= " AND age BETWEEN 5 AND 10";
                break;
            case '15-19':
                $count_sql .= " AND age BETWEEN 15 AND 19";
                break;
            case '21-39':
                $count_sql .= " AND age BETWEEN 21 AND 39";
                break;
            case '40-50':
                $count_sql .= " AND age BETWEEN 40 AND 50";
                break;
            case '50+':
                $count_sql .= " AND age > 50";
                break;
        }
    }
    $count_stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_users = $count_stmt->fetchColumn();
    $total_pages = ceil($total_users / $users_per_page);

    // S'assurer que la page est valide
    $page = max(1, min($page, $total_pages));

    // Calculer l'offset
    $offset = ($page - 1) * $users_per_page;
    $sql .= " LIMIT :limit OFFSET :offset";
    
    // Exécution de la requête avec pagination
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $users_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

    // Fetch trashed users for the trash section
    if (isset($_GET['section']) && $_GET['section'] === 'trash') {
        $trash_sql = "SELECT id, nom, prenom, email, telephone, role, age, gouvernorats, cin, deleted_at FROM trash_users";
        $trash_stmt = $pdo->prepare($trash_sql);
        $trash_stmt->execute();
        $trash_users = $trash_stmt->fetchAll();
    }
}

// Initialisation des statistiques
$stat_total = $stat_admin = $stat_user = $stat_technicien = 0;

// Récupération des rôles et du nombre d'utilisateurs pour chaque rôle
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques par rôle
foreach ($roles as $roleStat) {
    $stat_total += $roleStat['count'];

    switch ($roleStat['role']) {
        case 'admin':
            $stat_admin = $roleStat['count'];
            break;
        case 'user':
            $stat_user = $roleStat['count'];
            break;
        case 'technicien':
            $stat_technicien = $roleStat['count'];
            break;
    }
}

// Vérification de la session utilisateur
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Récupération des informations de l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Si l'utilisateur est un simple utilisateur, on ne garde que ses infos
    if ($user['role'] === 'user') {
        $users = [$user];
        $total_pages = 1; // Pas de pagination pour les utilisateurs non-admin
        $page = 1;
    }
} else {
    exit("Utilisateur non connecté.");
}

// Récupérer statistiques des gouvernorats
$gouvernoratStats = $pdo->query("SELECT gouvernorats, COUNT(*) as total FROM users GROUP BY gouvernorats")->fetchAll(PDO::FETCH_ASSOC);
$gouvernorats = [];
$totals = [];
foreach ($gouvernoratStats as $row) {
    $gouvernorats[] = $row['gouvernorats'];
    $totals[] = $row['total'];
}
$ageStats = $pdo->query("SELECT age, COUNT(*) as total FROM users GROUP BY age ORDER BY age")->fetchAll(PDO::FETCH_ASSOC);
$ageLabels = [];
$ageData = [];
foreach ($ageStats as $row) {
    $ageLabels[] = $row['age'];
    $ageData[] = $row['total'];
}

// Définir le chemin de base pour les liens
$basePath = '';

// Déterminer la section active
$section = isset($_GET['section']) ? $_GET['section'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Green.tn</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="theme.js" defer></script>
    <!-- Inclure Bootstrap Icons pour les icônes -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Inclure Font Awesome pour l'icône de traduction -->
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

        /* Navigation Buttons */
        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 12px 24px;
            font-size: 16px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .nav-btn:hover {
            background-color: #388e3c;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        .nav-btn.active {
            background-color: #2e7d32;
            transform: scale(1.05);
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
        }

        .nav-btn:hover::before {
            width: 200px;
            height: 200px;
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
            margin-bottom: 40px;
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Barre de tâches */
        .task-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2e7d32; /* Fond vert */
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff; /* Texte blanc pour contraste */
        }

        .search-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 8px;
            font-size: 14px;
            width: 150px;
            border-radius: 6px;
            border: 1px solid #ffffff;
            background-color: #ffffff;
            color: #333;
        }

        .sort-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sort-container select {
            padding: 8px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ffffff;
            background-color: #ffffff;
            color: #333;
        }

        .translate-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .translate-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            font-size: 14px;
            color: #ffffff;
            background-color: #1b5e20; /* Vert plus foncé pour le bouton */
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .translate-btn:hover {
            background-color: #4caf50; /* Vert clair au survol */
        }

        .translate-btn i {
            font-size: 14px;
        }

        .export-container .btn {
            padding: 8px 16px;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            font-size: 14px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn i {
            font-size: 14px;
        }

        .btn:hover {
            background-color: #388e3c;
        }

        .btn.supprimer {
            background-color: #e74c3c;
        }

        .btn.supprimer:hover {
            background-color: #c0392b;
        }

        .btn.modifier {
            background-color: #3498db;
        }

        .btn.modifier:hover {
            background-color: #2980b9;
        }

        .btn.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }

        /* Notifications */
        .notification-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto 20px;
        }

        .notification {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #4caf50;
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: 1px solid #388e3c;
            color: #ffffff;
            font-size: 14px;
            animation: fadeIn 0.5s ease-in;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .notification:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification i.bi-bell {
            margin-right: 10px;
            font-size: 16px;
        }

        .notification .dismiss-btn {
            background-color: #e74c3c;
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }

        .notification .dismiss-btn:hover {
            background-color: #c0392b;
        }

        .notification .dismiss-btn i {
            font-size: 14px;
        }

        /* Edit User Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.3s ease-in;
        }

        .modal-content h3 {
            margin: 0 0 20px;
            color: #2e7d32;
            font-size: 20px;
            text-align: center;
        }

        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-content label {
            font-weight: 600;
            color: #333;
        }

        .modal-content input,
        .modal-content select {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            width: 100%;
        }

        .modal-content input[type="file"] {
            padding: 5px;
        }

        .modal-content .btn-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-content .btn {
            padding: 10px 20px;
            font-size: 14px;
        }

        .modal-content .btn.cancel {
            background-color: #e74c3c;
        }

        .modal-content .btn.cancel:hover {
            background-color: #c0392b;
        }

        .modal-content .loading {
            display: none;
            text-align: center;
            font-size: 14px;
            color: #4caf50;
        }

        /* Alerts */
        .alert {
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-size: 14px;
            animation: slideInAlert 0.3s ease;
            display: none;
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

        @keyframes slideInAlert {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Statistiques */
        .stats-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1;
            min-width: 200px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            transition: transform 0.2s ease;
            border-left: 5px solid #4caf50;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 16px;
            color: #000000;
            font-weight: 500;
        }

        .stat-card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: bold;
            color: #000000;
        }

        /* Tableau */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }

        .user-table th, .user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(76, 175, 80, 0.5);
            color: #000000;
        }

        .user-table th {
            background-color: rgba(96, 186, 151, 0.95);
            font-weight: bold;
        }

        .user-table td {
            background-color: transparent;
        }

        .user-table tr:last-child td {
            border-bottom: none;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination .btn i {
            font-size: 14px;
        }

        /* Graphiques */
        .chart-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .chart-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 10px;
        }

        #userChart, #ageChart {
            max-width: 300px;
            max-height: 300px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Export Section */
        .export-section {
            text-align: center;
        }

        .export-section p {
            font-size: 16px;
            margin-bottom: 20px;
            color: #000000;
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

        body.dark-mode .section-content,
        body.dark-mode .stat-card,
        body.dark-mode #userChart,
        body.dark-mode #ageChart {
            background-color: rgba(50, 50, 50, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .task-bar {
            background-color: #1b5e20; /* Vert foncé en mode sombre */
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .translate-btn {
            background-color: #388e3c;
        }

        body.dark-mode .translate-btn:hover {
            background-color: #4caf50;
        }

        body.dark-mode .user-table {
            background-color: rgba(50, 50, 50, 0.9);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }

        body.dark-mode .user-table th {
            background-color: rgba(56, 142, 60, 0.95);
            color: #ffffff;
        }

        body.dark-mode .user-table td {
            border-color: rgba(76, 175, 80, 0.5);
            color: #ffffff;
        }

        body.dark-mode .notification {
            background-color: #388e3c;
            border: 1px solid #2e7d32;
            color: #ffffff;
        }

        body.dark-mode .notification .dismiss-btn {
            background-color: #e74c3c;
        }

        body.dark-mode .notification .dismiss-btn:hover {
            background-color: #c0392b;
        }

        body.dark-mode .modal-content {
            background-color: rgba(50, 50, 50, 0.95);
            color: #ffffff;
        }

        body.dark-mode .modal-content label {
            color: #e0e0e0;
        }

        body.dark-mode .modal-content input,
        body.dark-mode .modal-content select {
            background-color: #444;
            color: #ffffff;
            border-color: #666;
        }

        body.dark-mode .modal-content h3 {
            color: #4caf50;
        }

        body.dark-mode .alert.success {
            background-color: #388e3c;
            border-color: #2e7d32;
        }

        body.dark-mode .alert.error {
            background-color: #e74c3c;
            border-color: #c0392b;
        }

        body.dark-mode .stat-card h3,
        body.dark-mode .stat-card p,
        body.dark-mode .chart-title,
        body.dark-mode .export-section p {
            color: #ffffff;
        }

        body.dark-mode .btn {
            background-color: #555555;
            color: #ffffff;
        }

        body.dark-mode .btn:hover {
            background-color: #666666;
        }

        body.dark-mode .btn.supprimer {
            background-color: #e74c3c;
        }

        body.dark-mode .btn.supprimer:hover {
            background-color: #c0392b;
        }

        body.dark-mode .btn.modifier {
            background-color: #3498db;
        }

        body.dark-mode .btn.modifier:hover {
            background-color: #2980b9;
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

            .task-bar {
                flex-direction: column;
                gap: 10px;
            }

            .search-container {
                width: 100%;
                justify-content: center;
            }

            .search-input {
                width: 100%;
                max-width: 200px;
            }

            .sort-container {
                width: 100%;
                justify-content: center;
            }

            .sort-container select {
                width: 100%;
                max-width: 200px;
            }

            .translate-container {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                align-items: center;
            }

            .stat-card {
                width: 100%;
                max-width: 300px;
            }

            .nav-buttons {
                flex-direction: column;
                align-items: center;
            }

            .nav-btn {
                width: 100%;
                max-width: 300px;
            }

            .pagination {
                flex-direction: column;
                align-items: center;
            }

            .pagination .btn {
                width: 100%;
                max-width: 200px;
            }

            .notification {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
                padding: 15px;
            }

            .notification .dismiss-btn {
                width: 100%;
                text-align: center;
            }

            .modal-content {
                width: 95%;
                padding: 15px;
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
                <a class="sidebar-nav-link <?php echo $section === 'stats' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>dashboard.php?section=stats" data-translate="home">
                    <span class="sidebar-nav-icon"><i class="bi bi-house-door"></i></span>
                    <span class="sidebar-nav-text">Accueil</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?php echo isset($_GET['page']) && $_GET['page'] === 'gestion_utilisateurs' ? 'active' : ''; ?>" href="?page=gestion_utilisateurs" data-translate="profile_management">
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
            <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'forum_admin.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>forum_admin.php" data-translate="forum">
                <span class="sidebar-nav-icon"><i class="bi bi-chat"></i></span>
                <span class="sidebar-nav-text">Forum</span>
            </a>
        </li>
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="repairs.html" data-translate="repair_issues">
                        <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                        <span class="sidebar-nav-text">Réparer les pannes</span>
                    </a>
                </li>
                <a class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'update_profil_admin.php' ? 'active' : ''; ?>" href="update_profil_admin.php" data-translate="profile_management">
    <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
    <span class="sidebar-nav-text">Editer mon profil</span>
</a>
            <?php endif; ?>
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

<!-- CONTENU PRINCIPAL -->
<div class="main-content" id="main">
    <div class="header-logo">
        <img src="logo.jpg" alt="Logo Green.tn" class="logo-header">
    </div>
    <h1>Bienvenue, <?php echo htmlspecialchars($user['nom']); ?></h1>

    <!-- Notifications -->
    <?php if ($is_admin && !empty($notifications) && $section === 'stats'): ?>
    <div class="notification-container">
        <?php foreach ($notifications as $notification): ?>
        <div class="notification" data-notification-id="<?php echo $notification['id']; ?>">
            <span><i class="bi bi-bell"></i> <?php echo htmlspecialchars($notification['message']); ?> (<?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>)</span>
            <button class="btn dismiss-btn"><i class="bi bi-x"></i> Ignorer</button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
    <div class="dashboard-container">
        <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <button class="nav-btn <?php echo $section === 'users' ? 'active' : ''; ?>" onclick="window.location.href='?section=users'" data-translate="users"><i class="bi bi-people"></i> Consulter Utilisateurs</button>
            <button class="nav-btn <?php echo $section === 'export' ? 'active' : ''; ?>" onclick="window.location.href='export_csv.php'" data-translate="export"><i class="bi bi-download"></i> Exporter</button>
            <button class="nav-btn <?php echo $section === 'trash' ? 'active' : ''; ?>" onclick="window.location.href='?section=trash'" data-translate="trash"><i class="bi bi-trash"></i> Corbeille</button>
        </div>

        <!-- Section Content -->
        <div class="section-content">
            <?php if ($section === 'users'): ?>
                <!-- Alert Container -->
                <div id="alert-container"></div>

                <!-- Barre de tâches -->
                <div class="task-bar">
                    <form method="get" class="search-container">
                        <input type="hidden" name="show" value="users">
                        <input type="hidden" name="section" value="users">
                        <label for="nom" class="search-label" data-translate="search_name"><i class="bi bi-search"></i> Nom :</label>
                        <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($nom_filter); ?>" placeholder="Entrez un nom" class="search-input" data-translate-placeholder="enter_name">
                        <label for="email" class="search-label" data-translate="search_email"><i class="bi bi-envelope"></i> Email :</label>
                        <input type="text" name="email" id="email" value="<?php echo htmlspecialchars($email_filter); ?>" placeholder="Entrez un email" class="search-input" data-translate-placeholder="enter_email">
                        <label for="age_range" class="search-label" data-translate="search_age"><i class="bi bi-calendar"></i> Tranche d'âge :</label>
                        <select name="age_range" id="age_range" class="search-input">
                            <option value="" <?php echo empty($age_filter) ? 'selected' : ''; ?> data-translate="all_ages">Toutes les tranches</option>
                            <option value="5-10" <?php echo $age_filter === '5-10' ? 'selected' : ''; ?>>5-10 ans</option>
                            <option value="15-19" <?php echo $age_filter === '15-19' ? 'selected' : ''; ?>>15-19 ans</option>
                            <option value="21-39" <?php echo $age_filter === '21-39' ? 'selected' : ''; ?>>21-39 ans</option>
                            <option value="40-50" <?php echo $age_filter === '40-50' ? 'selected' : ''; ?>>40-50 ans</option>
                            <option value="50+" <?php echo $age_filter === '50+' ? 'selected' : ''; ?>>Plus de 50 ans</option>
                        </select>
                        <button type="submit" class="btn" data-translate="search"><i class="bi bi-search"></i> Rechercher</button>
                        <div class="sort-container">
                            <label for="age_sort" data-translate="sort_by_age"><i class="bi bi-sort-alpha-down"></i> Trier par âge :</label>
                            <select name="age_sort" id="age_sort" onchange="this.form.submit()">
                                <option value="" data-translate="choose">Choisir</option>
                                <option value="asc" <?php echo ($age_sort == 'asc') ? 'selected' : ''; ?> data-translate="ascending">Croissant</option>
                                <option value="desc" <?php echo ($age_sort == 'desc') ? 'selected' : ''; ?> data-translate="descending">Décroissant</option>
                            </select>
                        </div>
                    </form>
                    <div class="translate-container">
                        <button class="translate-btn" id="toggle-language" data-translate="language"><i class="fas fa-globe"></i> Français</button>
                    </div>
                </div>

                <!-- Tableau -->
                <table class="user-table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-person"></i> <span data-translate="name">Nom</span></th>
                            <th><i class="bi bi-person-fill"></i> <span data-translate="first_name">Prénom</span></th>
                            <th><i class="bi bi-card-id"></i> <span data-translate="cin">CIN</span></th>
                            <th><i class="bi bi-envelope"></i> <span data-translate="email">Email</span></th>
                            <th><i class="bi bi-telephone"></i> <span data-translate="phone">Téléphone</span></th>
                            <th><i class="bi bi-shield-lock"></i> <span data-translate="role">Rôle</span></th>
                            <th><i class="bi bi-calendar"></i> <span data-translate="age">Âge</span></th>
                            <th><i class="bi bi-geo-alt"></i> <span data-translate="governorate">Gouvernorats</span></th>
                            <th><i class="bi bi-gear"></i> <span data-translate="actions">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?php echo $user['id']; ?>">
                            <td class="nom"><?php echo htmlspecialchars($user['nom']); ?></td>
                            <td class="prenom"><?php echo htmlspecialchars($user['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($user['cin']); ?></td>
                            <td class="email"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="telephone"><?php echo htmlspecialchars($user['telephone']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="age"><?php echo htmlspecialchars($user['age']); ?></td>
                            <td class="gouvernorats"><?php echo $user['gouvernorats'] ? htmlspecialchars($user['gouvernorats']) : 'Non spécifié'; ?></td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                     <a href="edit_admin.php?id=<?php echo $user['id']; ?>&super_admin=1" class="btn modifier" title="Modifier"><i class="bi bi-pencil"></i>
                                <?php endif; ?>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Êtes-vous sûr ?');" title="Supprimer"><button class="btn supprimer"><i class="bi bi-trash"></i></button></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($is_admin && $total_pages > 1): ?>
                <div class="pagination">
                    <a href="?section=users<?php echo !empty($nom_filter) ? '&nom=' . urlencode($nom_filter) : ''; ?><?php echo !empty($email_filter) ? '&email=' . urlencode($email_filter) : ''; ?><?php echo !empty($age_filter) ? '&age_range=' . urlencode($age_filter) : ''; ?><?php echo !empty($age_sort) ? '&age_sort=' . $age_sort : ''; ?>&page=<?php echo $page - 1; ?>" class="btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" title="Page précédente"><i class="bi bi-arrow-left"></i></a>
                    <a href="?section=users<?php echo !empty($nom_filter) ? '&nom=' . urlencode($nom_filter) : ''; ?><?php echo !empty($email_filter) ? '&email=' . urlencode($email_filter) : ''; ?><?php echo !empty($age_filter) ? '&age_range=' . urlencode($age_filter) : ''; ?><?php echo !empty($age_sort) ? '&age_sort=' . $age_sort : ''; ?>&page=<?php echo $page + 1; ?>" class="btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" title="Page suivante"><i class="bi bi-arrow-right"></i></a>
                </div>
                <?php endif; ?>

                <!-- Edit User Modal -->
                <div class="modal" id="editUserModal">
                    <div class="modal-content">
                        <h3 data-translate="edit_info">Modifier les informations</h3>
                        <form id="editUserForm" enctype="multipart/form-data">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <input type="hidden" name="ajax_update_user" value="1">
                            <label for="edit_nom" data-translate="name">Nom</label>
                            <input type="text" id="edit_nom" name="nom" >
                            <label for="edit_prenom" data-translate="first_name">Prénom</label>
                            <input type="text" id="edit_prenom" name="prenom" >
                            <label for="edit_email" data-translate="email">Email</label>
                            <input type="email" id="edit_email" name="email" >
                            <label for="edit_telephone" data-translate="phone">Téléphone</label>
                            <input type="text" id="edit_telephone" name="telephone" pattern="[0-9]{8}" title="8 chiffres requis">
                            <label for="edit_age" data-translate="age">Âge</label>
                            <input type="number" id="edit_age" name="age" min="5" max="80" >
                            <label for="edit_gouvernorats" data-translate="governorate">Gouvernorats</label>
                            <select id="edit_gouvernorats" name="gouvernorats" >
                                <?php
                                $gouvernorats_list = ['Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'];
                                foreach ($gouvernorats_list as $gouv) {
                                    echo "<option value=\"$gouv\">$gouv</option>";
                                }
                                ?>
                            </select>
                            <label for="edit_photo" data-translate="profile_picture">Photo de profil</label>
                            <input type="file" id="edit_photo" name="photo" accept="image/*">
                            <div class="btn-container">
                                <button type="submit" class="btn" data-translate="update"><i class="bi bi-save"></i> Mettre à jour</button>
                                <button type="button" class="btn cancel" id="cancelEdit" data-translate="cancel"><i class="bi bi-x"></i> Annuler</button>
                            </div>
                            <div class="loading" data-translate="updating">Mise à jour en cours...</div>
                        </form>
                    </div>
                </div>

            <?php elseif ($section === 'stats'): ?>
                <!-- Statistiques -->
                <div class="stats-container">
                    <div class="stat-card">
                        <h3 data-translate="total_users">Total Utilisateurs</h3>
                        <p><?php echo $stat_total; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3 data-translate="admins">Admins</h3>
                        <p><?php echo $stat_admin; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3 data-translate="technicians">Techniciens</h3>
                        <p><?php echo $stat_technicien; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3 data-translate="users_label">Utilisateurs</h3>
                        <p><?php echo $stat_user; ?></p>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="chart-container">
                    <div>
                        <div class="chart-title" data-translate="role_distribution">Répartition des Utilisateurs par Rôle</div>
                        <canvas id="userChart"></canvas>
                    </div>
                    <div>
                        <div class="chart-title" data-translate="age_distribution">Répartition des Utilisateurs par Âge</div>
                        <canvas id="ageChart"></canvas>
                    </div>
                </div>

            <?php elseif ($section === 'export'): ?>
                <!-- Export Section -->
                <div class="export-section">
                    <p data-translate="export_users">Exporter la liste des utilisateurs au format CSV.</p>
                    <a href="export_csv.php" class="btn" data-translate="download_csv">Télécharger le fichier CSV</a>
                </div>

            <?php elseif ($section === 'trash'): ?>
                <!-- Corbeille Section -->
                <div id="alert-container"></div>
                <div class="task-bar">
                    <div class="translate-container">
                        <button class="translate-btn" id="toggle-language" data-translate="language"><i class="fas fa-globe"></i> Français</button>
                    </div>
                </div>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-person"></i> <span data-translate="name">Nom</span></th>
                            <th><i class="bi bi-person-fill"></i> <span data-translate="first_name">Prénom</span></th>
                            <th><i class="bi bi-card-id"></i> <span data-translate="cin">CIN</span></th>
                            <th><i class="bi bi-envelope"></i> <span data-translate="email">Email</span></th>
                            <th><i class="bi bi-telephone"></i> <span data-translate="phone">Téléphone</span></th>
                            <th><i class="bi bi-shield-lock"></i> <span data-translate="role">Rôle</span></th>
                            <th><i class="bi bi-calendar"></i> <span data-translate="age">Âge</span></th>
                            <th><i class="bi bi-geo-alt"></i> <span data-translate="governorate">Gouvernorats</span></th>
                            <th><i class="bi bi-clock"></i> <span data-translate="deleted_at">Supprimé le</span></th>
                            <th><i class="bi bi-gear"></i> <span data-translate="actions">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trash_users as $trash_user): ?>
                            <tr data-user-id="<?php echo $trash_user['id']; ?>">
                                <td><?php echo htmlspecialchars($trash_user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($trash_user['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($trash_user['cin']); ?></td>
                                <td><?php echo htmlspecialchars($trash_user['email']); ?></td>
                                <td><?php echo htmlspecialchars($trash_user['telephone']); ?></td>
                                <td><?php echo htmlspecialchars($trash_user['role']); ?></td>
                                <td><?php echo htmlspecialchars($trash_user['age']); ?></td>
                                <td><?php echo $trash_user['gouvernorats'] ? htmlspecialchars($trash_user['gouvernorats']) : 'Non spécifié'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($trash_user['deleted_at'])); ?></td>
                                <td>
                                    <a href="restore_user.php?id=<?php echo $trash_user['id']; ?>" onclick="return confirm('Voulez-vous restaurer cet utilisateur ?');" title="Restaurer"><button class="btn modifier"><i class="bi bi-arrow-counterclockwise"></i></button></a>
                                    <a href="permanent_delete_user.php?id=<?php echo $trash_user['id']; ?>" onclick="return confirm('Voulez-vous supprimer définitivement cet utilisateur ?');" title="Supprimer définitivement"><button class="btn supprimer"><i class="bi bi-trash"></i></button></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Scripts pour les graphiques, la sidebar, les notifications, l'édition et la traduction -->
<script>
<?php if ($section === 'stats'): ?>
const ctx = document.getElementById('userChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Admins', 'Techniciens', 'Utilisateurs'],
        datasets: [{
            data: [<?php echo $stat_admin; ?>, <?php echo $stat_technicien; ?>, <?php echo $stat_user; ?>],
            backgroundColor: ['#3498db', '#2ecc71', '#f39c12']
        }]
    },
    options: { 
        responsive: true, 
        plugins: { 
            legend: { position: 'bottom' } 
        } 
    }
});

const ctxAge = document.getElementById('ageChart').getContext('2d');
new Chart(ctxAge, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($ageLabels); ?>,
        datasets: [{
            label: 'Répartition par âge',
            data: <?php echo json_encode($ageData); ?>,
            backgroundColor: [
                '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6',
                '#1abc9c', '#e67e22', '#34495e', '#d35400',
                '#7f8c8d', '#2980b9'
            ]
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

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

    // Notification Dismiss Functionality
    const dismissButtons = document.querySelectorAll('.dismiss-btn');
    dismissButtons.forEach(button => {
        button.addEventListener('click', function() {
            const notification = this.closest('.notification');
            const notificationId = notification.getAttribute('data-notification-id');

            // Envoyer une requête AJAX pour marquer la notification comme lue
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'dismiss_notification.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        notification.style.opacity = '0';
                        setTimeout(() => notification.remove(), 300);
                    } else {
                        alert('Erreur lors de la suppression de la notification.');
                    }
                }
            };
            xhr.send('notification_id=' + encodeURIComponent(notificationId));
        });
    });

    // Edit User Modal Functionality
    const editModal = document.getElementById('editUserModal');
    const editForm = document.getElementById('editUserForm');
    const cancelEdit = document.getElementById('cancelEdit');
    const alertContainer = document.getElementById('alert-container');
    const loading = editForm.querySelector('.loading');

    // Show modal when clicking edit button
    document.querySelectorAll('.edit-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const row = this.closest('tr');
            const userData = {
                id: userId,
                nom: row.querySelector('.nom').textContent,
                prenom: row.querySelector('.prenom').textContent,
                email: row.querySelector('.email').textContent,
                telephone: row.querySelector('.telephone').textContent,
                age: row.querySelector('.age').textContent,
                gouvernorats: row.querySelector('.gouvernorats').textContent === 'Non spécifié' ? '' : row.querySelector('.gouvernorats').textContent
            };

            // Populate form
            editForm.querySelector('#edit_user_id').value = userData.id;
            editForm.querySelector('#edit_nom').value = userData.nom;
            editForm.querySelector('#edit_prenom').value = userData.prenom;
            editForm.querySelector('#edit_email').value = userData.email;
            editForm.querySelector('#edit_telephone').value = userData.telephone;
            editForm.querySelector('#edit_age').value = userData.age;
            editForm.querySelector('#edit_gouvernorats').value = userData.gouvernorats;

            editModal.style.display = 'flex';
        });
    });

    // Close modal
    cancelEdit.addEventListener('click', () => {
        editModal.style.display = 'none';
        editForm.reset();
    });

    // Close modal when clicking outside
    editModal.addEventListener('click', (e) => {
        if (e.target === editModal) {
            editModal.style.display = 'none';
            editForm.reset();
        }
    });

    // Handle form submission
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Client-side validation
        const nom = editForm.querySelector('#edit_nom').value.trim();
        const prenom = editForm.querySelector('#edit_prenom').value.trim();
        const email = editForm.querySelector('#edit_email').value.trim();
        const telephone = editForm.querySelector('#edit_telephone').value.trim();
        const age = parseInt(editForm.querySelector('#edit_age').value);

        let errors = [];
        if (nom.length < 2) errors.push("Le nom doit contenir au moins 2 caractères.");
        if (prenom.length < 2) errors.push("Le prénom doit contenir au moins 2 caractères.");
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) errors.push("Email invalide.");
        if (!telephone.match(/^\d{8}$/)) errors.push("Téléphone invalide (8 chiffres).");
        if (age < 5 || age > 80) errors.push("Âge invalide.");

        if (errors.length > 0) {
            showAlert('error', errors.join('<br>'));
            return;
        }

        const formData = new FormData(editForm);
        loading.style.display = 'block';
        editForm.querySelectorAll('button').forEach(btn => btn.disabled = true);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                loading.style.display = 'none';
                editForm.querySelectorAll('button').forEach(btn => btn.disabled = false);
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Update table row
                    const row = document.querySelector(`tr[data-user-id="${response.user.id}"]`);
                    row.querySelector('.nom').textContent = response.user.nom;
                    row.querySelector('.prenom').textContent = response.user.prenom;
                    row.querySelector('.email').textContent = response.user.email;
                    row.querySelector('.telephone').textContent = response.user.telephone;
                    row.querySelector('.age').textContent = response.user.age;
                    row.querySelector('.gouvernorats').textContent = response.user.gouvernorats || 'Non spécifié';

                    showAlert('success', response.message);
                    editModal.style.display = 'none';
                    editForm.reset();
                } else {
                    showAlert('error', response.message);
                }
            }
        };
        xhr.send(formData);
    });

    // Show alert
    function showAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert ${type}`;
        alert.innerHTML = message;
        alertContainer.appendChild(alert);
        alert.style.display = 'block';
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

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
            search_name: "Nom :",
            search_email: "Email :",
            search_age: "Tranche d'âge :",
            search: "Rechercher",
            sort_by_age: "Trier par âge :",
            choose: "Choisir",
            ascending: "Croissant",
            descending: "Décroissant",
            enter_name: "Entrez un nom",
            enter_email: "Entrez un email",
            all_ages: "Toutes les tranches",
            language: "Français",
            users: "Consulter Utilisateurs",
            export: "Exporter",
            total_users: "Total Utilisateurs",
            admins: "Admins",
            technicians: "Techniciens",
            users_label: "Utilisateurs",
            role_distribution: "Répartition des Utilisateurs par Rôle",
            age_distribution: "Répartition des Utilisateurs par Âge",
            export_users: "Exporter la liste des utilisateurs au format CSV.",
            download_csv: "Télécharger le fichier CSV",
            edit_info: "Modifier les informations",
            update: "Mettre à jour",
            cancel: "Annuler",
            updating: "Mise à jour en cours...",
            name: "Nom",
            first_name: "Prénom",
            cin: "CIN",
            email: "Email",
            phone: "Téléphone",
            role: "Rôle",
            age: "Âge",
            governorate: "Gouvernorats",
            actions: "Actions",
            profile_picture: "Photo de profil",
            trash: "Corbeille",
            deleted_at: "Supprimé le"
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
            search_name: "Name:",
            search_email: "Email:",
            search_age: "Age range:",
            search: "Search",
            sort_by_age: "Sort by age:",
            choose: "Choose",
            ascending: "Ascending",
            descending: "Descending",
            enter_name: "Enter a name",
            enter_email: "Enter an email",
            all_ages: "All age ranges",
            language: "English",
            users: "View Users",
            export: "Export",
            total_users: "Total Users",
            admins: "Admins",
            technicians: "Technicians",
            users_label: "Users",
            role_distribution: "User Distribution by Role",
            age_distribution: "User Distribution by Age",
            export_users: "Export the list of users in CSV format.",
            download_csv: "Download CSV file",
            edit_info: "Edit Information",
            update: "Update",
            cancel: "Cancel",
            updating: "Updating...",
            name: "Name",
            first_name: "First Name",
            cin: "ID Card",
            email: "Email",
            phone: "Phone",
            role: "Role",
            age: "Age",
            governorate: "Governorate",
            actions: "Actions",
            profile_picture: "Profile Picture",
            trash: "Trash",
            deleted_at: "Deleted At"
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

        document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
            const key = element.getAttribute('data-translate-placeholder');
            if (translations[currentLanguage][key]) {
                element.placeholder = translations[currentLanguage][key];
            }
        });

        const languageButton = document.getElementById('toggle-language');
        languageButton.innerHTML = `<i class="fas fa-globe"></i> ${translations[currentLanguage].language}`;
    }

    document.getElementById('toggle-language').addEventListener('click', () => {
        currentLanguage = currentLanguage === 'fr' ? 'en' : 'fr';
        localStorage.setItem('language', currentLanguage);
        updateTranslations();
    });

    // Initialize translations on page load
    updateTranslations();
});
</script>

</body>
</html> 