<?php
session_start();
require_once __DIR__ . '/models/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['user_id'])) {
    error_log("Accès refusé: role=" . (isset($_SESSION['role']) ? $_SESSION['role'] : 'none') . ", user_id=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'none'));
    header('Location: index.php');
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
        'reservations' => 'Réservations',
        'id' => 'ID',
        'client_id' => 'ID Client',
        'bike' => 'Vélo',
        'start_date' => 'Date de début',
        'end_date' => 'Date de fin',
        'gouvernorat' => 'Gouvernorat',
        'telephone' => 'Téléphone',
        'reservation_date' => 'Date de réservation',
        'status' => 'Statut',
        'actions' => 'Actions',
        'accept' => 'Accepter',
        'reject' => 'Rejeter',
        'trash' => 'Corbeille',
        'pending' => 'En attente',
        'accepted' => 'Acceptée',
        'refused' => 'Refusée',
        'no_reservations' => 'Aucune réservation trouvée.',
        'home' => 'Accueil',
        'profile_management' => 'Gestion de votre profil',
        'complaints' => 'Réclamations',
        'bikes_batteries' => 'Vélos',
        'repair_issues' => 'Réparer les pannes',
        'logout' => 'Déconnexion',
        'dark_mode' => 'Mode Sombre',
        'search' => 'Rechercher',
        'sort_by' => 'Trier par :',
        'filter_by_status' => 'Filtrer par statut :',
        'all_statuses' => 'Tous',
        'language' => 'Français',
        'search_reservations' => 'Vélo ou gouvernorat',
        'details' => 'Détails',
        'error_database' => 'Erreur de base de données. Veuillez réessayer plus tard.',
        'error_user_not_found' => 'Utilisateur non trouvé.',
        'success_status_update' => 'Statut de la réservation mis à jour avec succès.',
        'success_notification_sent' => 'Notification envoyée à l\'utilisateur.',
        'error_notification' => 'Erreur lors de l\'envoi de la notification.',
        'notifications' => 'Notifications',
        'success_reservation_trashed' => 'Réservation déplacée vers la corbeille.',
        'back' => 'Retour', // Added translation
    ],
    'en' => [
        'reservations' => 'Reservations',
        'id' => 'ID',
        'client_id' => 'Client ID',
        'bike' => 'Bike',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'gouvernorat' => 'Governorate',
        'telephone' => 'Phone',
        'reservation_date' => 'Reservation Date',
        'status' => 'Status',
        'actions' => 'Actions',
        'accept' => 'Accept',
        'reject' => 'Reject',
        'trash' => 'Trash',
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'refused' => 'Rejected',
        'no_reservations' => 'No reservations found.',
        'home' => 'Home',
        'profile_management' => 'Profile Management',
        'complaints' => 'Complaints',
        'bikes_batteries' => 'Bikes',
        'repair_issues' => 'Repair Issues',
        'logout' => 'Logout',
        'dark_mode' => 'Dark Mode',
        'search' => 'Search',
        'sort_by' => 'Sort by:',
        'filter_by_status' => 'Filter by status:',
        'all_statuses' => 'All',
        'language' => 'English',
        'search_reservations' => 'Bike or governorate',
        'details' => 'Details',
        'error_database' => 'Database error. Please try again later.',
        'error_user_not_found' => 'User not found.',
        'success_status_update' => 'Reservation status updated successfully.',
        'success_notification_sent' => 'Notification sent to the user.',
        'error_notification' => 'Error sending notification.',
        'notifications' => 'Notifications',
        'success_reservation_trashed' => 'Reservation moved to trash.',
        'back' => 'Back', // Added translation
    ]
];

// Function to get translated text
function getTranslation($key, $lang, $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Fetch user info
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

// Pagination
$reservations_per_page = 5;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $reservations_per_page;

// Fetch reservations
try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id_reservation';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $valid_sort_columns = ['id_reservation', 'date_debut', 'statut'];
    $valid_statuses = ['en_attente', 'acceptee', 'refusee', ''];
    if (!in_array($sort_by, $valid_sort_columns)) {
        $sort_by = 'id_reservation';
    }
    if (!in_array($status_filter, $valid_statuses)) {
        $status_filter = '';
    }

    // Count total reservations
    $sql_count = "SELECT COUNT(*) FROM reservation r 
                  JOIN velos v ON r.id_velo = v.id_velo 
                  WHERE (v.nom_velo LIKE :search OR r.gouvernorat LIKE :search)";
    if ($status_filter) {
        $sql_count .= " AND r.statut = :status";
    }
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->bindValue(':search', "%$search%", PDO::PARAM_STR);
    if ($status_filter) {
        $stmt_count->bindValue(':status', $status_filter, PDO::PARAM_STR);
    }
    $stmt_count->execute();
    $total_reservations = $stmt_count->fetchColumn();
    $total_pages = ceil($total_reservations / $reservations_per_page);

    // Fetch reservations
    $sql = "SELECT r.*, v.nom_velo 
            FROM reservation r 
            JOIN velos v ON r.id_velo = v.id_velo 
            WHERE (v.nom_velo LIKE :search OR r.gouvernorat LIKE :search)";
    if ($status_filter) {
        $sql .= " AND r.statut = :status";
    }
    $sql .= " ORDER BY $sort_by ASC 
              LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    if ($status_filter) {
        $stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $reservations_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistics
    $stat_pending = $pdo->query("SELECT COUNT(*) FROM reservation WHERE statut = 'en_attente'")->fetchColumn();
    $stat_accepted = $pdo->query("SELECT COUNT(*) FROM reservation WHERE statut = 'acceptee'")->fetchColumn();
    $stat_refused = $pdo->query("SELECT COUNT(*) FROM reservation WHERE statut = 'refusee'")->fetchColumn();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des réservations: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
}

// Handle accept/refuse actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    try {
        // Fetch reservation details for notification
        $stmt = $pdo->prepare("SELECT r.id_client, r.date_debut, r.date_fin, v.nom_velo 
                               FROM reservation r 
                               JOIN velos v ON r.id_velo = v.id_velo 
                               WHERE r.id_reservation = ?");
        $stmt->execute([$id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            error_log("Réservation introuvable pour ID: $id");
            $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
            header("Location: reservations.php?page=$current_page&search=" . urlencode($search) . "&sort_by=$sort_by&status=" . urlencode($status_filter));
            exit();
        }

        $client_id = $reservation['id_client'];
        $bike_name = $reservation['nom_velo'];
        $date_debut = $reservation['date_debut'];
        $date_fin = $reservation['date_fin'];

        if ($action === 'accept') {
            // Update reservation status
            $stmt = $pdo->prepare("UPDATE reservation SET statut = 'acceptee' WHERE id_reservation = ?");
            $stmt->execute([$id]);
            error_log("Réservation ID $id acceptée");

            // Create notification for user
            $message = sprintf(
                "Votre réservation #%d pour le vélo %s (du %s au %s) a été acceptée.",
                $id, $bike_name, $date_debut, $date_fin
            );
            try {
                $stmt = $pdo->prepare("INSERT INTO notification_reservation (user_id, message, created_at, is_read) 
                                       VALUES (?, ?, NOW(), 0)");
                $stmt->execute([$client_id, $message]);
                error_log("Notification créée pour l'utilisateur $client_id pour la réservation $id");
                $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('success_status_update', $language, $translations) . ' ' . getTranslation('success_notification_sent', $language, $translations)];
            } catch (PDOException $e) {
                error_log("Erreur lors de la création de la notification pour l'utilisateur $client_id: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_notification', $language, $translations)];
            }
            header("Location: reservation.php?id=$id");
            exit();
        } elseif ($action === 'refuse') {
            // Update reservation status to refused
            $stmt = $pdo->prepare("UPDATE reservation SET statut = 'refusee' WHERE id_reservation = ?");
            $stmt->execute([$id]);
            $affected_rows = $stmt->rowCount();
            if ($affected_rows > 0) {
                error_log("Réservation ID $id refusée et déplacée vers la corbeille");
                // Create notification for user
                $message = sprintf(
                    "Votre réservation #%d pour le vélo %s (du %s au %s) a été refusée et déplacée vers la corbeille.",
                    $id, $bike_name, $date_debut, $date_fin
                );
                try {
                    $stmt = $pdo->prepare("INSERT INTO notification_reservation (user_id, message, created_at, is_read) 
                                           VALUES (?, ?, NOW(), 0)");
                    $stmt->execute([$client_id, $message]);
                    error_log("Notification créée pour l'utilisateur $client_id pour la réservation $id");
                    $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('success_reservation_trashed', $language, $translations) . ' ' . getTranslation('success_notification_sent', $language, $translations)];
                } catch (PDOException $e) {
                    error_log("Erreur lors de la création de la notification pour l'utilisateur $client_id: " . $e->getMessage());
                    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_notification', $language, $translations)];
                }
            } else {
                error_log("Échec de la mise à jour de la réservation ID $id: aucune ligne affectée");
                $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
            }
            header("Location: reservation.php?id=$id");
            exit();
        } else {
            error_log("Action non valide pour la réservation ID $id: $action");
            $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
            header("Location: reservations.php?page=$current_page&search=" . urlencode($search) . "&sort_by=$sort_by&status=" . urlencode($status_filter));
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour de la réservation ID $id: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
        header("Location: reservations.php?page=$current_page&search=" . urlencode($search) . "&sort_by=$sort_by&status=" . urlencode($status_filter));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="reservations">Réservations - Green.tn</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .task-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
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
            background-color: #1b5e20;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .translate-btn:hover {
            background-color: #4caf50;
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
        .btn:hover {
            background-color: #388e3c;
        }
        .btn.accept {
            background-color: #3498db;
        }
        .btn.accept:hover {
            background-color: #2980b9;
        }
        .btn.refuse {
            background-color: #f39c12;
        }
        .btn.refuse:hover {
            background-color: #e67e22;
        }
        .btn.trash {
            background-color: #e63946;
            padding: 8px 16px;
        }
        .btn.trash:hover {
            background-color: #c0392b;
        }
        .btn.details {
            background-color: #17a2b8;
            padding: 6px;
            font-size: 12px;
        }
        .btn.details:hover {
            background-color: #138496;
        }
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
        .status.pending {
            color: #f39c12;
            font-weight: bold;
        }
        .status.accepted {
            color: #4CAF50;
            font-weight: bold;
        }
        .status.refused {
            color: #e63946;
            font-weight: bold;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination .btn.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            min-width: 200px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            border-left: 5px solid #4caf50;
            backdrop-filter: blur(5px);
        }
        .stat-card h3 {
            margin: 0;
            font-size: 16px;
            color: #000000;
        }
        .stat-card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: bold;
            color: #000000;
        }
        .chart-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        #reservationsChart {
            max-width: 300px;
            max-height: 300px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
        body.dark-mode .main-content, body.dark-mode .section-content, body.dark-mode .stat-card, body.dark-mode #reservationsChart {
            background-color: rgba(50, 50, 50, 0.9);
        }
        body.dark-mode .task-bar {
            background-color: #1b5e20;
        }
        body.dark-mode .user-table {
            background-color: rgba(50, 50, 50, 0.9);
        }
        body.dark-mode .user-table th {
            background-color: rgba(56, 142, 60, 0.95);
            color: #ffffff;
        }
        body.dark-mode .user-table td {
            color: #ffffff;
        }
        @media (max-width: 992px) {
            .sidebar { left: -250px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
            .task-bar { flex-direction: column; gap: 10px; }
            .search-container, .sort-container, .translate-container { width: 100%; justify-content: center; }
            .search-input, .sort-container select { width: 100%; max-width: 200px; }
        }
        @media (max-width: 768px) {
            .stats-container { flex-direction: column; align-items: center; }
            .stat-card { width: 100%; max-width: 300px; }
        }
    </style>
</head>
<body>
    <!-- Alert Container -->
    <div class="alert-container"></div>

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
                    <a class="sidebar-nav-link active" href="reservations.php?super_admin=1" data-translate="reservations">
                        <span class="sidebar-nav-icon"><i class="bi bi-calendar"></i></span>
                        <span class="sidebar-nav-text">Voir réservations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="reclamation.php" data-translate="complaints">
                        <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                        <span class="sidebar-nav-text">Réclamations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="velos.php?super_admin=1" data-translate="bikes_batteries">
                        <span class="sidebar-nav-icon"><i class="bi bi-bicycle"></i></span>
                        <span class="sidebar-nav-text">Vélos</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link" href="repair_panne.php" data-translate="repair_issues">
                            <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                            <span class="sidebar-nav-text">Réparer les pannes</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="notification_reservation.php" data-translate="notifications">
                        <span class="sidebar-nav-icon"><i class="bi bi-bell"></i></span>
                        <span class="sidebar-nav-text">Notifications</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <div class="mb-2">
                <span class="text-white">Bienvenue, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
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
        <?php
        if (isset($_SESSION['alert'])) {
            $alert = $_SESSION['alert'];
            echo "<script>showAlert('{$alert['type']}', '{$alert['message']}');</script>";
            unset($_SESSION['alert']);
        }
        ?>
        <div class="header-logo">
            <img src="logo.jpg" alt="Logo Green.tn" class="logo-header">
        </div>
        <h1 data-translate="reservations">Réservations</h1>

        <div class="section-content">
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3 data-translate="pending">Réservations en attente</h3>
                    <p><?php echo $stat_pending; ?></p>
                </div>
                <div class="stat-card">
                    <h3 data-translate="accepted">Réservations acceptées</h3>
                    <p><?php echo $stat_accepted; ?></p>
                </div>
                <div class="stat-card">
                    <h3 data-translate="refused">Réservations refusées</h3>
                    <p><?php echo $stat_refused; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total des réservations</h3>
                    <p><?php echo $total_reservations; ?></p>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container">
                <canvas id="reservationsChart"></canvas>
            </div>

            <!-- Task Bar -->
            <div class="task-bar">
                <form method="get" class="search-container">
                    <label for="search" class="search-label" data-translate="search"><i class="bi bi-search"></i> Rechercher :</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo getTranslation('search_reservations', $language, $translations); ?>" class="search-input" data-translate-placeholder="search_reservations">
                    <label for="sort_by" class="search-label" data-translate="sort_by"><i class="bi bi-sort-alpha-down"></i> Trier par :</label>
                    <select name="sort_by" id="sort_by" class="search-input" onchange="this.form.submit()">
                        <option value="id_reservation" <?php echo $sort_by == 'id_reservation' ? 'selected' : ''; ?> data-translate="id">ID Réservation</option>
                        <option value="date_debut" <?php echo $sort_by == 'date_debut' ? 'selected' : ''; ?> data-translate="start_date">Date de début</option>
                        <option value="statut" <?php echo $sort_by == 'statut' ? 'selected' : ''; ?> data-translate="status">Statut</option>
                    </select>
                    <label for="status" class="search-label" data-translate="filter_by_status"><i class="bi bi-filter"></i> Filtrer par statut :</label>
                    <select name="status" id="status" class="search-input" onchange="this.form.submit()">
                        <option value="" <?php echo $status_filter == '' ? 'selected' : ''; ?> data-translate="all_statuses">Tous</option>
                        <option value="en_attente" <?php echo $status_filter == 'en_attente' ? 'selected' : ''; ?> data-translate="pending">En attente</option>
                        <option value="acceptee" <?php echo $status_filter == 'acceptee' ? 'selected' : ''; ?> data-translate="accepted">Acceptée</option>
                        <option value="refusee" <?php echo $status_filter == 'refusee' ? 'selected' : ''; ?> data-translate="refused">Refusée</option>
                    </select>
                    
                    <button type="submit" class="btn" data-translate="search"><i class="bi bi-search"></i> Rechercher</button>
                    <a href="?status=refusee" class="btn trash" data-translate="trash"><i class="bi bi-trash"></i> Corbeille</a>
                    <a href="reservation.php" class="btn" data-translate="back"><i class="bi bi-arrow-left"></i> Retour</a>
                </form>
                <div class="translate-container">
                    <button class="translate-btn" id="toggle-language" data-translate="language"><i class="fas fa-globe"></i> <?php echo $language === 'fr' ? 'Français' : 'English'; ?></button>
                </div>
            </div>

            <!-- Table -->
            <table class="user-table">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash"></i> <span data-translate="id">ID</span></th>
                        <th><i class="bi bi-bicycle"></i> <span data-translate="bike">Vélo</span></th>
                        <th><i class="bi bi-person"></i> <span data-translate="client_id">Client ID</span></th>
                        <th><i class="bi bi-calendar"></i> <span data-translate="start_date">Date Début</span></th>
                        <th><i class="bi bi-calendar-check"></i> <span data-translate="end_date">Date Fin</span></th>
                        <th><i class="bi bi-geo-alt"></i> <span data-translate="gouvernorat">Gouvernorat</span></th>
                        <th><i class="bi bi-telephone"></i> <span data-translate="telephone">Téléphone</span></th>
                        <th><i class="bi bi-flag"></i> <span data-translate="status">Statut</span></th>
                        <th><i class="bi bi-gear"></i> <span data-translate="actions">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;" data-translate="no_reservations">Aucune réservation trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['id_reservation']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['nom_velo']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['id_client']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_debut']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_fin']); ?></td>
                                <td><?php echo htmlspecialchars(isset($reservation['gouvernorat']) ? $reservation['gouvernorat'] : 'Non spécifié'); ?></td>
                                <td><?php echo htmlspecialchars(isset($reservation['telephone']) ? $reservation['telephone'] : 'Non spécifié'); ?></td>
                                <td class="status <?php echo $reservation['statut'] === 'en_attente' ? 'pending' : ($reservation['statut'] === 'acceptee' ? 'accepted' : 'refused'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($reservation['statut'])); ?>
                                </td>
                                <td>
                                    <?php if ($reservation['statut'] === 'en_attente' || $reservation['statut'] === 'acceptee'): ?>
                                        <a href="?action=accept&id=<?php echo $reservation['id_reservation']; ?>" class="btn accept" title="<?php echo getTranslation('accept', $language, $translations); ?>"><i class="bi bi-check-circle"></i></a>
                                        <a href="?action=refuse&id=<?php echo $reservation['id_reservation']; ?>" class="btn refuse" title="<?php echo getTranslation('reject', $language, $translations); ?>" onclick="return confirm('<?php echo getTranslation('reject', $language, $translations); ?> cette réservation ?');"><i class="bi bi-x-circle"></i></a>
                                    <?php endif; ?>
                                    <button class="btn details" onclick="window.open('reservation_details.php?id=<?php echo $reservation['id_reservation']; ?>', 'ReservationDetails', 'width=400,height=500,scrollbars=yes,resizable=yes')" title="<?php echo getTranslation('details', $language, $translations); ?>" data-translate="details">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn <?php echo $current_page <= 1 ? 'disabled' : ''; ?>" title="Page précédente"><i class="bi bi-arrow-left"></i></a>
                    <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>" title="Page suivante"><i class="bi bi-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Sidebar toggle
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

            // Dark Mode
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

            // Chart
            const ctx = document.getElementById('reservationsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['<?php echo getTranslation('pending', $language, $translations); ?>', '<?php echo getTranslation('accepted', $language, $translations); ?>', '<?php echo getTranslation('refused', $language, $translations); ?>'],
                    datasets: [{
                        label: '<?php echo getTranslation('reservations', $language, $translations); ?>',
                        data: [<?php echo $stat_pending; ?>, <?php echo $stat_accepted; ?>, <?php echo $stat_refused; ?>],
                        backgroundColor: ['#f39c12', '#4CAF50', '#e63946']
                    }]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Translation
            const translations = <?php echo json_encode($translations); ?>;
            let currentLanguage = '<?php echo $language; ?>';

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
                document.getElementById('toggle-language').innerHTML = `<i class="fas fa-globe"></i> ${translations[currentLanguage].language}`;
                document.title = translations[currentLanguage].reservations + ' - Green.tn';
            }

            document.getElementById('toggle-language').addEventListener('click', () => {
                currentLanguage = currentLanguage === 'fr' ? 'en' : 'fr';
                localStorage.setItem('language', currentLanguage);
                window.location.href = `?lang=${currentLanguage}${window.location.search.replace(/lang=[a-z]{2}/, '')}`;
            });

            updateTranslations();

            // Alert function
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
        });
    </script>
</body>
</html>