<?php
session_start();
require_once 'C:\xampp\htdocs\projetweb\CONFIG\db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Count active and total stations
    $stmtStations = $pdo->query("SELECT 
        COUNT(*) as total_stations,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_stations,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_stations
        FROM stations");
    $stationsStats = $stmtStations->fetch();

    // Get stations per city for bar chart
    $stmtCities = $pdo->query("SELECT city, COUNT(*) as count FROM stations GROUP BY city");
    $locationStats = $stmtCities->fetchAll();

    // Count total trajets
    $stmtTrajets = $pdo->query("SELECT COUNT(*) as total_trajets FROM trajets");
    $trajetsStats = $stmtTrajets->fetch();

    // Fetch data for trajet statistics
    $trajetCo2 = $pdo->query("SELECT id, description, COALESCE(co2_saved,0) as co2_saved FROM trajets ORDER BY id DESC LIMIT 20")->fetchAll();
    $trajetEnergy = $pdo->query("SELECT id, description, COALESCE(battery_energy,0) as battery_energy, COALESCE(fuel_saved,0) as fuel_saved FROM trajets ORDER BY id DESC LIMIT 20")->fetchAll();
    $trajetDistances = $pdo->query("SELECT id, COALESCE(distance,0) as distance FROM trajets ORDER BY id DESC")->fetchAll();

    // Fetch statistics for reclamations (corrected to handle case sensitivity)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN LOWER(statut) = 'ouverte' THEN 1 ELSE 0 END) AS ouvertes,
            SUM(CASE WHEN LOWER(statut) = 'en_cours' THEN 1 ELSE 0 END) AS en_cours,
            SUM(CASE WHEN LOWER(statut) = 'resolue' THEN 1 ELSE 0 END) AS resolues
        FROM reclamations
    ");
    $reclamation_stats = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : ['total' => 0, 'ouvertes' => 0, 'en_cours' => 0, 'resolues' => 0];

    $total_reclamations = (int)$reclamation_stats['total'];
    $reclamations_ouvertes = (int)$reclamation_stats['ouvertes'];
    $reclamations_en_cours = (int)$reclamation_stats['en_cours'];
    $reclamations_resolues = (int)$reclamation_stats['resolues'];

    // Fetch types of reclamations
    $stmt_type = $pdo->query("SELECT type_probleme AS type, COUNT(*) AS count FROM reclamations GROUP BY type_probleme");
    $reclamation_types = $stmt_type ? $stmt_type->fetchAll(PDO::FETCH_ASSOC) : [];

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Erreur lors de la récupération des statistiques.";
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

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_technicien = isset($_SESSION['role']) && $_SESSION['role'] === 'technicien';
$is_user = isset($_SESSION['role']) && $_SESSION['role'] === 'user';

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

    $errors = [];
    if (empty($nom) || strlen($nom) < 2) $errors[] = "Le nom doit contenir au moins 2 caractères.";
    if (empty($prenom) || strlen($prenom) < 2) $errors[] = "Le prénom doit contenir au moins 2 caractères.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (!preg_match('/^[0-9]{8}$/', $telephone)) $errors[] = "Le téléphone doit comporter 8 chiffres.";
    if ($age < 5 || $age > 80) $errors[] = "Âge invalide.";
    if (!in_array($gouvernorats, ['Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'])) $errors[] = "Gouvernorat invalide.";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $target_user_id]);
    if ($stmt->rowCount() > 0) $errors[] = "Cet email est déjà utilisé.";

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
            if (!move_uploaded_file($photo_tmp, $photo_path)) $errors[] = "Erreur lors du téléchargement de la photo.";
        } else {
            $errors[] = "Type de fichier non autorisé (JPG, PNG, GIF uniquement).";
        }
    } else {
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
            'user' => ['id' => $target_user_id, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'telephone' => $telephone, 'age' => $age, 'gouvernorats' => $gouvernorats, 'photo' => $photo_path]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    }
    exit();
}

$notifications = [];
if ($is_admin) {
    $stmt = $pdo->prepare("SELECT id, message, created_at FROM notifications WHERE is_read = FALSE ORDER BY created_at DESC");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($is_admin) {
    $nom_filter = isset($_GET['nom']) ? $_GET['nom'] : '';
    $email_filter = isset($_GET['email']) ? $_GET['email'] : '';
    $age_filter = isset($_GET['age_range']) ? $_GET['age_range'] : '';
    $age_sort = isset($_GET['age_sort']) ? $_GET['age_sort'] : '';
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $users_per_page = 3;

    $sql = "SELECT id, nom, prenom, email, telephone, role, age, gouvernorats, cin FROM users WHERE 1=1";
    $params = [];
    if (!empty($nom_filter)) {
        $sql .= " AND nom LIKE :nom";
        $params[':nom'] = '%' . $nom_filter . '%';
    }
    if (!empty($email_filter)) {
        $sql .= " AND email LIKE :email";
        $params[':email'] = '%' . $email_filter . '%';
    }
    if (!empty($age_filter)) {
        switch ($age_filter) {
            case '5-10': $sql .= " AND age BETWEEN 5 AND 10"; break;
            case '15-19': $sql .= " AND age BETWEEN 15 AND 19"; break;
            case '21-39': $sql .= " AND age BETWEEN 21 AND 39"; break;
            case '40-50': $sql .= " AND age BETWEEN 40 AND 50"; break;
            case '50+': $sql .= " AND age > 50"; break;
        }
    }
    if (!empty($age_sort)) {
        $sql .= " ORDER BY age " . ($age_sort === 'asc' ? 'ASC' : 'DESC');
    }

    $count_sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    if (!empty($nom_filter)) $count_sql .= " AND nom LIKE :nom";
    if (!empty($email_filter)) $count_sql .= " AND email LIKE :email";
    if (!empty($age_filter)) {
        switch ($age_filter) {
            case '5-10': $count_sql .= " AND age BETWEEN 5 AND 10"; break;
            case '15-19': $count_sql .= " AND age BETWEEN 15 AND 19"; break;
            case '21-39': $count_sql .= " AND age BETWEEN 21 AND 39"; break;
            case '40-50': $count_sql .= " AND age BETWEEN 40 AND 50"; break;
            case '50+': $count_sql .= " AND age > 50"; break;
        }
    }
    $count_stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) $count_stmt->bindValue($key, $value);
    $count_stmt->execute();
    $total_users = $count_stmt->fetchColumn();
    $total_pages = ceil($total_users / $users_per_page);

    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $users_per_page;
    $sql .= " LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) $stmt->bindValue($key, $value);
    $stmt->bindValue(':limit', $users_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

    if (isset($_GET['section']) && $_GET['section'] === 'trash') {
        $trash_sql = "SELECT id, nom, prenom, email, telephone, role, age, gouvernorats, cin, deleted_at FROM trash_users";
        $trash_stmt = $pdo->prepare($trash_sql);
        $trash_stmt->execute();
        $trash_users = $trash_stmt->fetchAll();
    }
}

$stat_total = $stat_admin = $stat_user = $stat_technicien = 0;
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $roleStat) {
    $stat_total += $roleStat['count'];
    switch ($roleStat['role']) {
        case 'admin': $stat_admin = $roleStat['count']; break;
        case 'user': $stat_user = $roleStat['count']; break;
        case 'technicien': $stat_technicien = $roleStat['count']; break;
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user['role'] === 'user') {
        $users = [$user];
        $total_pages = 1;
        $page = 1;
    }
} else {
    exit("Utilisateur non connecté.");
}

$gouvernoratStats = $pdo->query("SELECT gouvernorats, COUNT(*) as total FROM users GROUP BY gouvernorats")->fetchAll(PDO::FETCH_ASSOC);
$gouvernorats = []; $totals = [];
foreach ($gouvernoratStats as $row) {
    $gouvernorats[] = $row['gouvernorats'];
    $totals[] = $row['total'];
}
$ageStats = $pdo->query("SELECT age, COUNT(*) as total FROM users GROUP BY age ORDER BY age")->fetchAll(PDO::FETCH_ASSOC);
$ageLabels = []; $ageData = [];
foreach ($ageStats as $row) {
    $ageLabels[] = $row['age'];
    $ageData[] = $row['total'];
}

$basePath = '';
$section = isset($_GET['section']) ? $_GET['section'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Green.tn</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="theme.js" defer></script>
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
        .sidebar { height: 100vh; width: 250px; position: fixed; top: 0; left: -250px; background-color: rgba(96, 186, 151, 0.9); backdrop-filter: blur(5px); transition: left 0.3s ease; z-index: 1000; }
        .sidebar.show { left: 0; }
        .sidebar-header { padding: 20px; text-align: center; }
        .sidebar-brand img { width: 60%; height: auto; }
        .sidebar-content { padding: 20px; }
        .sidebar-nav { list-style: none; padding: 0; }
        .sidebar-nav-item { margin-bottom: 10px; }
        .sidebar-nav-link { display: flex; align-items: center; padding: 12px 20px; color: #d0f0d6; text-decoration: none; font-size: 15px; border-radius: 6px; transition: background-color 0.3s ease; }
        .sidebar-nav-link:hover { background-color: #1b5e20; color: white; }
        .sidebar-nav-link.active { background-color: #388e3c; color: white; }
        .sidebar-nav-icon { margin-right: 10px; }
        .sidebar-footer { position: absolute; bottom: 20px; width: 100%; padding: 20px; text-align: center; }
        .sidebar-footer .btn { font-size: 14px; width: 100%; margin-bottom: 10px; }
        .sidebar-toggler { position: fixed; top: 20px; left: 20px; z-index: 1100; background-color: #60BA97; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; transition: background-color 0.3s ease; }
        .sidebar-toggler:hover { background-color: #388e3c; }
        .main-content { margin-left: 0; padding: 40px; min-height: 100vh; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(5px); border-radius: 12px; transition: margin-left 0.3s ease; }
        .main-content-expanded { margin-left: 250px; }
        .header-logo { text-align: center; margin-bottom: 20px; }
        .logo-header { width: 110px; height: auto; }
        .main-content h1 { color: #2e7d32; text-align: center; margin-bottom: 20px; font-size: 28px; font-weight: 600; }
        .nav-buttons { display: flex; justify-content: center; gap: 20px; margin-bottom: 40px; flex-wrap: wrap; }
        .nav-btn { display: flex; align-items: center; gap: 5px; padding: 12px 24px; font-size: 16px; color: white; background-color: #4caf50; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); position: relative; overflow: hidden; }
        .nav-btn:hover { background-color: #388e3c; transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); }
        .nav-btn.active { background-color: #2e7d32; transform: scale(1.05); }
        .nav-btn::before { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; background: rgba(255, 255, 255, 0.2); border-radius: 50%; transform: translate(-50%, -50%); transition: width 0.4s ease, height 0.4s ease; }
        .nav-btn:hover::before { width: 200px; height: 200px; }
        .section-content { background-color: rgba(255, 255, 255, 0.9); padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.2); animation: slideIn 0.5s ease; margin-bottom: 40px; }
        @keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .task-bar { display: flex; justify-content: space-between; align-items: center; background-color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; }
        .search-container { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .search-input { padding: 8px; font-size: 14px; width: 150px; border-radius: 6px; border: 1px solid #ffffff; background-color: #ffffff; color: #333; }
        .sort-container { display: flex; align-items: center; gap: 10px; }
        .sort-container select { padding: 8px; font-size: 14px; border-radius: 6px; border: 1px solid #ffffff; background-color: #ffffff; color: #333; }
        .translate-container { display: flex; align-items: center; gap: 10px; }
        .translate-btn { display: flex; align-items: center; gap: 5px; padding: 8px 16px; font-size: 14px; color: #ffffff; background-color: #1b5e20; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.3s ease; }
        .translate-btn:hover { background-color: #4caf50; }
        .translate-btn i { font-size: 14px; }
        .export-container .btn { padding: 8px 16px; font-size: 14px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px; font-size: 14px; color: white; background-color: #4caf50; border: none; border-radius: 6px; text-decoration: none; transition: background-color 0.3s ease; }
        .btn i { font-size: 14px; }
        .btn:hover { background-color: #388e3c; }
        .btn.supprimer { background-color: #e74c3c; }
        .btn.supprimer:hover { background-color: #c0392b; }
        .btn.modifier { background-color: #3498db; }
        .btn.modifier:hover { background-color: #2980b9; }
        .btn.disabled { opacity: 0.5; pointer-events: none; cursor: not-allowed; }
        .notification-container { width: 100%; max-width: 800px; margin: 0 auto 20px; }
        .notification { display: flex; justify-content: space-between; align-items: center; background-color: #4caf50; padding: 12px 20px; margin-bottom: 10px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); border: 1px solid #388e3c; color: #ffffff; font-size: 14px; animation: fadeIn 0.5s ease-in; transition: transform 0.2s ease, opacity 0.2s ease; }
        .notification:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .notification i.bi-bell { margin-right: 10px; font-size: 16px; }
        .notification .dismiss-btn { background-color: #e74c3c; padding: 6px 12px; font-size: 12px; border-radius: 4px; transition: background-color 0.2s ease; }
        .notification .dismiss-btn:hover { background-color: #c0392b; }
        .notification .dismiss-btn i { font-size: 14px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; z-index: 2000; }
        .modal-content { background: rgba(255, 255, 255, 0.95); padding: 20px; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); animation: fadeIn 0.3s ease-in; }
        .modal-content h3 { margin: 0 0 20px; color: #2e7d32; font-size: 20px; text-align: center; }
        .modal-content form { display: flex; flex-direction: column; gap: 15px; }
        .modal-content label { font-weight: 600; color: #333; }
        .modal-content input, .modal-content select { padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; width: 100%; }
        .modal-content input[type="file"] { padding: 5px; }
        .modal-content .btn-container { display: flex; gap: 10px; justify-content: center; }
        .modal-content .btn { padding: 10px 20px; font-size: 14px; }
        .modal-content .btn.cancel { background-color: #e74c3c; }
        .modal-content .btn.cancel:hover { background-color: #c0392b; }
        .modal-content .loading { display: none; text-align: center; font-size: 14px; color: #4caf50; }
        .alert { padding: 12px 20px; margin-bottom: 10px; border-radius: 8px; font-size: 14px; animation: slideInAlert 0.3s ease; display: none; }
        .alert.success { background-color: #4caf50; color: #ffffff; border: 1px solid #388e3c; }
        .alert.error { background-color: #e74c3c; color: #ffffff; border: 1px solid #c0392b; }
        @keyframes slideInAlert { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        .stats-container { display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .stat-card { flex: 1; min-width: 200px; background-color: rgba(255, 255, 255, 0.95); padding: 20px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); text-align: center; transition: transform 0.2s ease; border-left: 5px solid #4caf50; backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { margin: 0; font-size: 16px; color: #000000; font-weight: 500; }
        .stat-card p { margin: 10px 0 0; font-size: 24px; font-weight: bold; color: #000000; }
        .user-table { width: 100%; border-collapse: collapse; font-size: 14px; background-color: rgba(255, 255, 255, 0.95); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); border: 1px solid rgba(76, 175, 80, 0.5); }
        .user-table th, .user-table td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(76, 175, 80, 0.5); color: #000000; }
        .user-table th { background-color: rgba(96, 186, 151, 0.95); font-weight: bold; }
        .user-table td { background-color: transparent; }
        .user-table tr:last-child td { border-bottom: none; }
        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
        .pagination .btn i { font-size: 14px; }
        .chart-container { display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; }
        .chart-title { text-align: center; font-size: 18px; font-weight: bold; color: #000000; margin-bottom: 10px; }
        #userChart, #ageChart, #reclamationPieChart, #reclamationTypeChart { max-width: 300px; max-height: 300px; background-color: rgba(255, 255, 255, 0.95); padding: 15px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .export-section { text-align: center; }
        .export-section p { font-size: 16px; margin-bottom: 20px; color: #000000; }
        body.dark-mode { background: linear-gradient(45deg, #1a3c34, #2c3e50, #34495e, #2e7d32); background-size: 200% 200%; animation: gradientShift 15s ease infinite; color: #e0e0e0; }
        body.dark-mode .sidebar { background-color: rgba(51, 51, 51, 0.9); }
        body.dark-mode .sidebar-nav-link { color: #ffffff; }
        body.dark-mode .sidebar-nav-link:hover { background-color: #444444; }
        body.dark-mode .main-content { background: rgba(30, 30, 30, 0.8); }
        body.dark-mode .main-content h1 { color: #4caf50; }
        body.dark-mode .section-content, body.dark-mode .stat-card, body.dark-mode #userChart, body.dark-mode #ageChart, body.dark-mode #reclamationPieChart, body.dark-mode #reclamationTypeChart { background-color: rgba(50, 50, 50, 0.9); border: 1px solid rgba(255, 255, 255, 0.2); }
        body.dark-mode .task-bar { background-color: #1b5e20; border: 1px solid rgba(255, 255, 255, 0.2); }
        body.dark-mode .translate-btn { background-color: #388e3c; }
        body.dark-mode .translate-btn:hover { background-color: #4caf50; }
        body.dark-mode .user-table { background-color: rgba(50, 50, 50, 0.9); border: 1px solid rgba(76, 175, 80, 0.5); }
        body.dark-mode .user-table th { background-color: rgba(56, 142, 60, 0.95); color: #ffffff; }
        body.dark-mode .user-table td { border-color: rgba(76, 175, 80, 0.5); color: #ffffff; }
        body.dark-mode .notification { background-color: #388e3c; border: 1px solid #2e7d32; color: #ffffff; }
        body.dark-mode .notification .dismiss-btn { background-color: #e74c3c; }
        body.dark-mode .notification .dismiss-btn:hover { background-color: #c0392b; }
        body.dark-mode .modal-content { background-color: rgba(50, 50, 50, 0.95); color: #ffffff; }
        body.dark-mode .modal-content label { color: #e0e0e0; }
        body.dark-mode .modal-content input, body.dark-mode .modal-content select { background-color: #444; color: #ffffff; border-color: #666; }
        body.dark-mode .modal-content h3 { color: #4caf50; }
        body.dark-mode .alert.success { background-color: #388e3c; border-color: #2e7d32; }
        body.dark-mode .alert.error { background-color: #e74c3c; border-color: #c0392b; }
        body.dark-mode .stat-card h3, body.dark-mode .stat-card p, body.dark-mode .chart-title, body.dark-mode .export-section p { color: #ffffff; }
        body.dark-mode .btn { background-color: #555555; color: #ffffff; }
        body.dark-mode .btn:hover { background-color: #666666; }
        body.dark-mode .btn.supprimer { background-color: #e74c3c; }
        body.dark-mode .btn.supprimer:hover { background-color: #c0392b; }
        body.dark-mode .btn.modifier { background-color: #3498db; }
        body.dark-mode .btn.modifier:hover { background-color: #2980b9; }
        @media (max-width: 992px) {
            .sidebar { left: -250px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
            .task-bar { flex-direction: column; gap: 10px; }
            .search-container { width: 100%; justify-content: center; }
            .search-input { width: 100%; max-width: 200px; }
            .sort-container { width: 100%; justify-content: center; }
            .sort-container select { width: 100%; max-width: 200px; }
            .translate-container { width: 100%; justify-content: center; }
        }
        @media (max-width: 768px) {
            .stats-container { flex-direction: column; align-items: center; }
            .stat-card { width: 100%; max-width: 300px; }
            .nav-buttons { flex-direction: column; align-items: center; }
            .nav-btn { width: 100%; max-width: 300px; }
            .pagination { flex-direction: column; align-items: center; }
            .pagination .btn { width: 100%; max-width: 200px; }
            .notification { flex-direction: column; gap: 10px; align-items: flex-start; padding: 15px; }
            .notification .dismiss-btn { width: 100%; text-align: center; }
            .modal-content { width: 95%; padding: 15px; }
        }
        .modal-stat-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-stat-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; position: relative; }
        .modal-stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-stat-close { font-size: 24px; border: none; background: none; cursor: pointer; }
    </style>
</head>
<body>
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
                <a class="sidebar-nav-link" href="<?php echo $basePath; ?>../../VIEW/reclamation/reclamations_utilisateur.php" data-translate="complaints">
                    <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                    <span class="sidebar-nav-text">Réclamations</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="../reclamation/liste_avis.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-star"></i></span>
                        <span class="sidebar-nav-text"> Avis</span>
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

<button class="sidebar-toggler" type="button" id="sidebarToggle">
    <i class="bi bi-list"></i>
</button>

<div class="main-content" id="main">
    <div class="header-logo">
        <img src="logo.jpg" alt="Logo Green.tn" class="logo-header">
    </div>
    <h1>Bienvenue, <?php echo htmlspecialchars($user['nom']); ?></h1>

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
        <div class="nav-buttons">
            <button class="nav-btn <?php echo $section === 'users' ? 'active' : ''; ?>" onclick="window.location.href='?section=users'" data-translate="users"><i class="bi bi-people"></i> Consulter Utilisateurs</button>
            <button class="nav-btn <?php echo $section === 'export' ? 'active' : ''; ?>" onclick="window.location.href='export_csv.php'" data-translate="export"><i class="bi bi-download"></i> Exporter</button>
            <button class="nav-btn <?php echo $section === 'trash' ? 'active' : ''; ?>" onclick="window.location.href='?section=trash'" data-translate="trash"><i class="bi bi-trash"></i> Corbeille</button>
        </div>

        <div class="section-content">
            <?php if ($section === 'users'): ?>
                <div id="alert-container"></div>
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
                                    <a href="edit_admin.php?id=<?php echo $user['id']; ?>&super_admin=1" class="btn modifier" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Êtes-vous sûr ?');" title="Supprimer"><button class="btn supprimer"><i class="bi bi-trash"></i></button></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($is_admin && $total_pages > 1): ?>
                <div class="pagination">
                    <a href="?section=users<?php echo !empty($nom_filter) ? '&nom=' . urlencode($nom_filter) : ''; ?><?php echo !empty($email_filter) ? '&email=' . urlencode($email_filter) : ''; ?><?php echo !empty($age_filter) ? '&age_range=' . urlencode($age_filter) : ''; ?><?php echo !empty($age_sort) ? '&age_sort=' . $age_sort : ''; ?>&page=<?php echo $page - 1; ?>" class="btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" title="Page précédente"><i class="bi bi-arrow-left"></i></a>
                    <a href="?section=users<?php echo !empty($nom_filter) ? '&nom=' . urlencode($nom_filter) : ''; ?><?php echo !empty($email_filter) ? '&email=' . urlencode($email_filter) : ''; ?><?php echo !empty($age_filter) ? '&age_range=' . urlencode($age_filter) : ''; ?><?php echo !empty($age_sort) ? '&age_sort=' . $age_sort : ''; ?>&page=<?php echo $page + 1; ?>" class="btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" title="Page suivante"><i class="bi bi-arrow-right"></i></a>
                </div>
                <?php endif; ?>

                <div class="modal" id="editUserModal">
                    <div class="modal-content">
                        <h3 data-translate="edit_info">Modifier les informations</h3>
                        <form id="editUserForm" enctype="multipart/form-data">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <input type="hidden" name="ajax_update_user" value="1">
                            <label for="edit_nom" data-translate="name">Nom</label>
                            <input type="text" id="edit_nom" name="nom">
                            <label for="edit_prenom" data-translate="first_name">Prénom</label>
                            <input type="text" id="edit_prenom" name="prenom">
                            <label for="edit_email" data-translate="email">Email</label>
                            <input type="email" id="edit_email" name="email">
                            <label for="edit_telephone" data-translate="phone">Téléphone</label>
                            <input type="text" id="edit_telephone" name="telephone" pattern="[0-9]{8}" title="8 chiffres requis">
                            <label for="edit_age" data-translate="age">Âge</label>
                            <input type="number" id="edit_age" name="age" min="5" max="80">
                            <label for="edit_gouvernorats" data-translate="governorate">Gouvernorats</label>
                            <select id="edit_gouvernorats" name="gouvernorats">
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

                <div class="container-fluid p-4">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Stations</h5>
                                    <p class="card-text">
                                        Total: <?php echo isset($stationsStats) ? $stationsStats['total_stations'] : '0'; ?><br>
                                        Actives: <?php echo isset($stationsStats) ? $stationsStats['active_stations'] : '0'; ?>
                                    </p>
                                    <div class="d-flex">
                                        <a href="stations/list.php" class="btn btn-primary">Gérer les stations</a>
                                        <button id="showStatsBtn" class="btn btn-success ms-2" style="box-shadow:0 2px 8px #60BA9733;">
                                            <i class="bi bi-bar-chart-fill"></i> Voir statistiques
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Trajets</h5>
                                    <p class="card-text">
                                        Total: <?php echo isset($trajetsStats) ? $trajetsStats['total_trajets'] : '0'; ?>
                                    </p>
                                    <div class="d-flex">
                                        <a href="trajets/list.php" class="btn btn-primary">Gérer les trajets</a>
                                        <button id="showTrajetStatsBtn" class="btn btn-success ms-2" style="box-shadow:0 2px 8px #60BA9733;">
                                            <i class="bi bi-bar-chart-fill"></i> Voir statistiques
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Réclamations</h5>
                                    <p class="card-text">
                                        Total: <?php echo $total_reclamations; ?><br>
                                        Ouvertes: <?php echo $reclamations_ouvertes; ?><br>
                                        En cours: <?php echo $reclamations_en_cours; ?><br>
                                        Résolues: <?php echo $reclamations_resolues; ?>
                                    </p>
                                    <div class="d-flex">
                                        <a href="../../VIEW/reclamation/reclamations_utilisateur.php" class="btn btn-primary">Gérer les réclamations</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-content mt-4">
                        <h3 class="chart-title" data-translate="reclamation_statistics">Statistiques des Réclamations</h3>
                        <div class="chart-container">
                            <div>
                                <div class="chart-title" data-translate="open_vs_resolved">Réclamations Ouvertes vs En cours vs Résolues</div>
                                <canvas id="reclamationPieChart" width="300" height="300" style="max-width:300px; max-height:300px; width:100%; height:300px;"></canvas>
                            </div>
                            <div>
                                <div class="chart-title" data-translate="reclamation_types">Types de Réclamations</div>
                                <canvas id="reclamationTypeChart" width="300" height="300" style="max-width:300px; max-height:300px; width:100%; height:300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($section === 'export'): ?>
                <div class="export-section">
                    <p data-translate="export_users">Exporter la liste des utilisateurs au format CSV.</p>
                    <a href="export_csv.php" class="btn" data-translate="download_csv">Télécharger le fichier CSV</a>
                </div>

            <?php elseif ($section === 'trash'): ?>
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

    <div class="modal-stat-overlay" id="statModal">
        <div class="modal-stat-content">
            <div class="modal-stat-header">
                <h4 id="chartTitle"><i class="bi bi-bar-chart-fill"></i> Statistiques des stations</h4>
                <button class="modal-stat-close" id="closeStatModal" aria-label="Fermer">×</button>
            </div>
            <div class="chart-slider-container position-relative" style="min-height:420px;">
                <button id="slideLeft" class="btn btn-outline-secondary position-absolute top-50 start-0 translate-middle-y" style="z-index:10;display:none;">
                    <i class="bi bi-arrow-left-circle" style="font-size:2rem;"></i>
                </button>
                <div class="chart-slider" style="overflow:hidden; width:100%;">
                    <div class="chart-slide chart-box" id="slide-0" style="width:100%; display:flex; flex-direction:column; align-items:center;">
                        <canvas id="pieChart" width="420" height="320" style="max-width:420px; max-height:320px; width:100%; height:320px;"></canvas>
                        <div class="mt-2 text-center fw-bold">Actives vs Inactives</div>
                    </div>
                    <div class="chart-slide chart-box" id="slide-1" style="width:100%; display:none; flex-direction:column; align-items:center;">
                        <canvas id="barChart" width="420" height="320" style="max-width:420px; max-height:320px; width:100%; height:320px;"></canvas>
                        <div class="mt-2 text-center fw-bold">Stations par emplacement</div>
                    </div>
                </div>
                <button id="slideRight" class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y" style="z-index:10;">
                    <i class="bi bi-arrow-right-circle" style="font-size:2rem;"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="modal-stat-overlay" id="trajetStatModal">
        <div class="modal-stat-content">
            <div class="modal-stat-header">
                <h4 id="trajetChartTitle"><i class="bi bi-bar-chart-fill"></i> Statistiques des trajets</h4>
                <button class="modal-stat-close" id="closeTrajetStatModal" aria-label="Fermer">×</button>
            </div>
            <div class="chart-slider-container position-relative" style="min-height:420px;">
                <button id="trajetSlideLeft" class="btn btn-outline-secondary position-absolute top-50 start-0 translate-middle-y" style="z-index:10;display:none;">
                    <i class="bi bi-arrow-left-circle" style="font-size:2rem;"></i>
                </button>
                <div class="chart-slider" style="overflow:hidden; width:100%;">
                    <div class="chart-slide chart-box" id="trajet-slide-0" style="width:100%; display:flex; flex-direction:column; align-items:center;">
                        <canvas id="trajetCo2Chart" width="420" height="320"></canvas>
                        <div class="mt-2 text-center fw-bold">CO₂ économisé par trajet</div>
                    </div>
                    <div class="chart-slide chart-box" id="trajet-slide-1" style="width:100%; display:none; flex-direction:column; align-items:center;">
                        <canvas id="trajetEnergyChart" width="420" height="320"></canvas>
                        <div class="mt-2 text-center fw-bold">Consommation d'énergie par trajet</div>
                    </div>
                    <div class="chart-slide chart-box" id="trajet-slide-2" style="width:100%; display:none; flex-direction:column; align-items:center;">
                        <canvas id="trajetDistanceChart" width="420" height="320"></canvas>
                        <div class="mt-2 text-center fw-bold">Répartition des distances</div>
                    </div>
                </div>
                <button id="trajetSlideRight" class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y" style="z-index:10;">
                    <i class="bi bi-arrow-right-circle" style="font-size:2rem;"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.getElementById('main');

    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        mainContent.classList.toggle('main-content-expanded');
    });

    const darkModeToggle = document.getElementById('darkModeToggle');
    darkModeToggle.addEventListener('click', (e) => {
        e.preventDefault();
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        updateDarkModeText();
    });

    function updateDarkModeText() {
        const isDarkMode = document.body.classList.contains('dark-mode');
        darkModeToggle.innerHTML = `<i class="bi ${isDarkMode ? 'bi-sun' : 'bi-moon'}"></i> ${isDarkMode ? 'Mode Clair' : 'Mode Sombre'}`;
    }

    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
        updateDarkModeText();
    }

    const userChart = new Chart(document.getElementById('userChart'), {
        type: 'pie',
        data: {
            labels: ['Admins', 'Techniciens', 'Utilisateurs'],
            datasets: [{
                data: [<?php echo $stat_admin; ?>, <?php echo $stat_technicien; ?>, <?php echo $stat_user; ?>],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#000000' }
                }
            }
        }
    });

    const ageChart = new Chart(document.getElementById('ageChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo "'" . implode("','", $ageLabels) . "'"; ?>],
            datasets: [{
                label: 'Nombre d\'utilisateurs',
                data: [<?php echo implode(',', $ageData); ?>],
                backgroundColor: '#36A2EB'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: { display: false }
            },
            scales: {
                x: { ticks: { color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#000000' } },
                y: { ticks: { color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#000000' } }
            }
        }
    });

    const reclamationPieChart = new Chart(document.getElementById('reclamationPieChart'), {
        type: 'pie',
        data: {
            labels: ['Ouvertes', 'En cours', 'Résolues'],
            datasets: [{
                data: [<?php echo $reclamations_ouvertes; ?>, <?php echo $reclamations_en_cours; ?>, <?php echo $reclamations_resolues; ?>],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#000000' }
                }
            }
        }
    });

    const reclamationTypeChart = new Chart(document.getElementById('reclamationTypeChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo "'" . implode("','", array_column($reclamation_types, 'type')) . "'"; ?>],
            datasets: [{
                label: 'Nombre de réclamations',
                data: [<?php echo implode(',', array_column($reclamation_types, 'count')); ?>],
                backgroundColor: '#36A2EB'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: { display: false }
            },
            scales: {
                x: { ticks: { color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#000000' } },
                y: { ticks: { color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#000000' } }
            }
        }
    });

    document.body.addEventListener('classChange', () => {
        const isDarkMode = document.body.classList.contains('dark-mode');
        userChart.options.plugins.legend.labels.color = isDarkMode ? '#ffffff' : '#000000';
        ageChart.options.scales.x.ticks.color = isDarkMode ? '#ffffff' : '#000000';
        ageChart.options.scales.y.ticks.color = isDarkMode ? '#ffffff' : '#000000';
        reclamationPieChart.options.plugins.legend.labels.color = isDarkMode ? '#ffffff' : '#000000';
        reclamationTypeChart.options.scales.x.ticks.color = isDarkMode ? '#ffffff' : '#000000';
        reclamationTypeChart.options.scales.y.ticks.color = isDarkMode ? '#ffffff' : '#000000';
        userChart.update();
        ageChart.update();
        reclamationPieChart.update();
        reclamationTypeChart.update();
    });

    const statModal = document.getElementById('statModal');
    const showStatsBtn = document.getElementById('showStatsBtn');
    const closeStatModal = document.getElementById('closeStatModal');
    const slideLeft = document.getElementById('slideLeft');
    const slideRight = document.getElementById('slideRight');
    let currentSlide = 0;
    const slides = document.querySelectorAll('.chart-slide');

    showStatsBtn.addEventListener('click', () => {
        statModal.style.display = 'flex';
        showSlide(currentSlide);
    });

    closeStatModal.addEventListener('click', () => {
        statModal.style.display = 'none';
    });

    slideLeft.addEventListener('click', () => {
        currentSlide--;
        showSlide(currentSlide);
    });

    slideRight.addEventListener('click', () => {
        currentSlide++;
        showSlide(currentSlide);
    });

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.style.display = i === index ? 'flex' : 'none';
        });
        slideLeft.style.display = index === 0 ? 'none' : 'block';
        slideRight.style.display = index === slides.length - 1 ? 'none' : 'block';
    }

    const pieChart = new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: ['Actives', 'Inactives'],
            datasets: [{
                data: [<?php echo $stationsStats['active_stations']; ?>, <?php echo $stationsStats['inactive_stations']; ?>],
                backgroundColor: ['#4CAF50', '#F44336']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    const barChart = new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo "'" . implode("','", array_column($locationStats, 'city')) . "'"; ?>],
            datasets: [{
                label: 'Nombre de stations',
                data: [<?php echo implode(',', array_column($locationStats, 'count')); ?>],
                backgroundColor: '#4CAF50'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const trajetStatModal = document.getElementById('trajetStatModal');
    const showTrajetStatsBtn = document.getElementById('showTrajetStatsBtn');
    const closeTrajetStatModal = document.getElementById('closeTrajetStatModal');
    const trajetSlideLeft = document.getElementById('trajetSlideLeft');
    const trajetSlideRight = document.getElementById('trajetSlideRight');
    let currentTrajetSlide = 0;
    const trajetSlides = document.querySelectorAll('.chart-slide[id^="trajet-slide-"]');

    showTrajetStatsBtn.addEventListener('click', () => {
        trajetStatModal.style.display = 'flex';
        showTrajetSlide(currentTrajetSlide);
    });

    closeTrajetStatModal.addEventListener('click', () => {
        trajetStatModal.style.display = 'none';
    });

    trajetSlideLeft.addEventListener('click', () => {
        currentTrajetSlide--;
        showTrajetSlide(currentTrajetSlide);
    });

    trajetSlideRight.addEventListener('click', () => {
        currentTrajetSlide++;
        showTrajetSlide(currentTrajetSlide);
    });

    function showTrajetSlide(index) {
        trajetSlides.forEach((slide, i) => {
            slide.style.display = i === index ? 'flex' : 'none';
        });
        trajetSlideLeft.style.display = index === 0 ? 'none' : 'block';
        trajetSlideRight.style.display = index === trajetSlides.length - 1 ? 'none' : 'block';
    }

    const trajetCo2Chart = new Chart(document.getElementById('trajetCo2Chart'), {
        type: 'bar',
        data: {
            labels: [<?php echo "'" . implode("','", array_column($trajetCo2, 'description')) . "'"; ?>],
            datasets: [{
                label: 'CO₂ économisé (g)',
                data: [<?php echo implode(',', array_column($trajetCo2, 'co2_saved')); ?>],
                backgroundColor: '#4CAF50'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const trajetEnergyChart = new Chart(document.getElementById('trajetEnergyChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo "'" . implode("','", array_column($trajetEnergy, 'description')) . "'"; ?>],
            datasets: [
                {
                    label: 'Énergie batterie (Wh)',
                    data: [<?php echo implode(',', array_column($trajetEnergy, 'battery_energy')); ?>],
                    backgroundColor: '#36A2EB'
                },
                {
                    label: 'Carburant économisé (L)',
                    data: [<?php echo implode(',', array_column($trajetEnergy, 'fuel_saved')); ?>],
                    backgroundColor: '#FFCE56'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const trajetDistanceChart = new Chart(document.getElementById('trajetDistanceChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo "'" . implode("','", array_column($trajetDistances, 'id')) . "'"; ?>],
            datasets: [{
                label: 'Distance (km)',
                data: [<?php echo implode(',', array_column($trajetDistances, 'distance')); ?>],
                backgroundColor: '#FF6384'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const translations = {
        fr: {
            home: "Accueil",
            profile_management: "Gestion de votre profil",
            reservations: "Voir réservations",
            complaints: "Réclamations",
            bikes_batteries: "Vélos & Batteries",
            forum: "Forum",
            repair_issues: "Réparer les pannes",
            logout: "Déconnexion",
            dark_mode: "Mode Sombre",
            light_mode: "Mode Clair",
            users: "Consulter Utilisateurs",
            export: "Exporter",
            trash: "Corbeille",
            search_name: "Nom :",
            search_email: "Email :",
            search_age: "Tranche d'âge :",
            enter_name: "Entrez un nom",
            enter_email: "Entrez un email",
            all_ages: "Toutes les tranches",
            search: "Rechercher",
            sort_by_age: "Trier par âge :",
            choose: "Choisir",
            ascending: "Croissant",
            descending: "Décroissant",
            language: "Français",
            name: "Nom",
            first_name: "Prénom",
            cin: "CIN",
            email: "Email",
            phone: "Téléphone",
            role: "Rôle",
            age: "Âge",
            governorate: "Gouvernorats",
            actions: "Actions",
            edit_info: "Modifier les informations",
            profile_picture: "Photo de profil",
            update: "Mettre à jour",
            cancel: "Annuler",
            updating: "Mise à jour en cours...",
            total_users: "Total Utilisateurs",
            admins: "Admins",
            technicians: "Techniciens",
            users_label: "Utilisateurs",
            role_distribution: "Répartition des Utilisateurs par Rôle",
            age_distribution: "Répartition des Utilisateurs par Âge",
            reclamation_statistics: "Statistiques des Réclamations",
            open_vs_resolved: "Réclamations Ouvertes vs En cours vs Résolues",
            reclamation_types: "Types de Réclamations",
            export_users: "Exporter la liste des utilisateurs au format CSV.",
            download_csv: "Télécharger le fichier CSV",
            deleted_at: "Supprimé le"
        },
        ar: {
            home: "الرئيسية",
            profile_management: "إدارة ملفك الشخصي",
            reservations: "عرض الحجوزات",
            complaints: "الشكاوى",
            bikes_batteries: "الدراجات والبطاريات",
            forum: "المنتدى",
            repair_issues: "إصلاح الأعطال",
            logout: "تسجيل الخروج",
            dark_mode: "الوضع الداكن",
            light_mode: "الوضع الفاتح",
            users: "استعراض المستخدمين",
            export: "تصدير",
            trash: "سلة المحذوفات",
            search_name: "الاسم:",
            search_email: "البريد الإلكتروني:",
            search_age: "الفئة العمرية:",
            enter_name: "أدخل الاسم",
            enter_email: "أدخل البريد الإلكتروني",
            all_ages: "كل الفئات",
            search: "بحث",
            sort_by_age: "ترتيب حسب العمر:",
            choose: "اختر",
            ascending: "تصاعدي",
            descending: "تنازلي",
            language: "العربية",
            name: "الاسم",
            first_name: "اللقب",
            cin: "رقم الهوية",
            email: "البريد الإلكتروني",
            phone: "الهاتف",
            role: "الدور",
            age: "العمر",
            governorate: "المحافظة",
            actions: "الإجراءات",
            edit_info: "تعديل المعلومات",
            profile_picture: "صورة الملف الشخصي",
            update: "تحديث",
            cancel: "إلغاء",
            updating: "جاري التحديث...",
            total_users: "إجمالي المستخدمين",
            admins: "الإداريون",
            technicians: "الفنيون",
            users_label: "المستخدمون",
            role_distribution: "توزيع المستخدمين حسب الدور",
            age_distribution: "توزيع المستخدمين حسب العمر",
            reclamation_statistics: "إحصائيات الشكاوى",
            open_vs_resolved: "الشكاوى المفتوحة مقابل قيد التقدم مقابل المحلولة",
            reclamation_types: "أنواع الشكاوى",
            export_users: "تصدير قائمة المستخدمين بصيغة CSV.",
            download_csv: "تنزيل ملف CSV",
            deleted_at: "تم الحذف في"
        }
    };

    let currentLang = 'fr';
    const toggleLanguageBtn = document.getElementById('toggle-language');
    toggleLanguageBtn.addEventListener('click', () => {
        currentLang = currentLang === 'fr' ? 'ar' : 'fr';
        toggleLanguageBtn.textContent = currentLang === 'fr' ? 'Français' : 'العربية';
        document.querySelectorAll('[data-translate]').forEach(element => {
            const key = element.getAttribute('data-translate');
            element.textContent = translations[currentLang][key] || element.textContent;
        });
        document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
            const key = element.getAttribute('data-translate-placeholder');
            element.placeholder = translations[currentLang][key] || element.placeholder;
        });
        document.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.lang = currentLang;
    });

    const editButtons = document.querySelectorAll('.btn.modifier');
    const editUserModal = document.getElementById('editUserModal');
    const editUserForm = document.getElementById('editUserForm');
    const cancelEditBtn = document.getElementById('cancelEdit');
    const alertContainer = document.getElementById('alert-container');

    editButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const row = button.closest('tr');
            const userId = row.getAttribute('data-user-id');
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_nom').value = row.querySelector('.nom').textContent;
            document.getElementById('edit_prenom').value = row.querySelector('.prenom').textContent;
            document.getElementById('edit_email').value = row.querySelector('.email').textContent;
            document.getElementById('edit_telephone').value = row.querySelector('.telephone').textContent;
            document.getElementById('edit_age').value = row.querySelector('.age').textContent;
            document.getElementById('edit_gouvernorats').value = row.querySelector('.gouvernorats').textContent === 'Non spécifié' ? '' : row.querySelector('.gouvernorats').textContent;
            editUserModal.style.display = 'flex';
        });
    });

    cancelEditBtn.addEventListener('click', () => {
        editUserModal.style.display = 'none';
        editUserForm.reset();
    });

    editUserForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(editUserForm);
        const loading = editUserForm.querySelector('.loading');
        loading.style.display = 'block';

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            editUserModal.style.display = 'none';
            const alert = document.createElement('div');
            alert.className = `alert ${data.success ? 'success' : 'error'}`;
            alert.textContent = data.message;
            alert.style.display = 'block';
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);

            if (data.success) {
                const row = document.querySelector(`tr[data-user-id="${data.user.id}"]`);
                row.querySelector('.nom').textContent = data.user.nom;
                row.querySelector('.prenom').textContent = data.user.prenom;
                row.querySelector('.email').textContent = data.user.email;
                row.querySelector('.telephone').textContent = data.user.telephone;
                row.querySelector('.age').textContent = data.user.age;
                row.querySelector('.gouvernorats').textContent = data.user.gouvernorats || 'Non spécifié';
            }

            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        })
        .catch(error => {
            loading.style.display = 'none';
            const alert = document.createElement('div');
            alert.className = 'alert error';
            alert.textContent = 'Une erreur s\'est produite.';
            alert.style.display = 'block';
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);

            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        });
    });

    document.querySelectorAll('.notification .dismiss-btn').forEach(button => {
        button.addEventListener('click', () => {
            const notification = button.closest('.notification');
            const notificationId = notification.getAttribute('data-notification-id');

            fetch('dismiss_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notification.remove();
                }
            });
        });
    });
</script>

</body>
</html>