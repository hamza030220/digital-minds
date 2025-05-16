```php
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

// Handle reservation acceptance (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_reservation']) && $user['role'] === 'admin') {
    $reservation_id = (int)$_POST['reservation_id'];
    try {
        $stmt = $pdo->prepare("UPDATE reservation SET statut = 'accepted' WHERE id_reservation = ? AND statut != 'accepted'");
        $stmt->execute([$reservation_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('reservation_accepted', $language, $translations)];
            // Ajouter une notification
            $stmt = $pdo->prepare("INSERT INTO notification_reservation (user_id, message, reservation_id, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
            $message = getTranslation('reservation_accepted_notification', $language, $translations);
            $stmt->execute([$user_id, $message, $reservation_id]);
        } else {
            $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_reservation_not_found', $language, $translations)];
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'acceptation de la réservation: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    }
    header("Location: consulter_mes_reservations.php");
    exit();
}

// Handle reservation rejection (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_reservation']) && $user['role'] === 'admin') {
    $reservation_id = (int)$_POST['reservation_id'];
    try {
        $stmt = $pdo->prepare("UPDATE reservation SET statut = 'rejected' WHERE id_reservation = ? AND statut != 'rejected'");
        $stmt->execute([$reservation_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('reservation_rejected', $language, $translations)];
            // Ajouter une notification
            $stmt = $pdo->prepare("INSERT INTO notification_reservation (user_id, message, reservation_id, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
            $message = getTranslation('reservation_rejected_notification', $language, $translations);
            $stmt->execute([$user_id, $message, $reservation_id]);
        } else {
            $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_reservation_not_found', $language, $translations)];
        }
    } catch (PDOException $e) {
        error_log("Erreur lors du refus de la réservation: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    }
    header("Location: consulter_mes_reservations.php");
    exit();
}

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    try {
        $stmt = $pdo->prepare("UPDATE notification_reservation SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('notification_marked_read', $language, $translations)];
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de la notification comme lue: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    }
    header("Location: consulter_mes_reservations.php");
    exit();
}

// Handle marking all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_as_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE notification_reservation SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('all_notifications_marked_read', $language, $translations)];
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de toutes les notifications comme lues: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    }
    header("Location: consulter_mes_reservations.php");
    exit();
}

// Handle sorting
$allowed_columns = ['id_reservation', 'date_debut', 'date_fin', 'gouvernorat', 'date_reservation'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_columns) ? $_GET['sort'] : 'id_reservation';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';

// Fetch user reservations
try {
    $query = "SELECT r.*, v.nom_velo AS bike_name, v.type_velo FROM reservation r LEFT JOIN velos v ON r.id_velo = v.id_velo WHERE r.id_client = ? ORDER BY r.$sort_column $sort_order";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Réservations récupérées pour utilisateur ID: $user_id - " . count($reservations) . " réservations");
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des réservations: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    $reservations = [];
}

// Fetch user notifications
try {
    $stmt = $pdo->prepare("SELECT * FROM notification_reservation WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    $notifications = [];
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="title_view_reservations">Green.tn - Mes Réservations</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .notification-icon {
            cursor: pointer;
            font-size: 1.2rem;
            color: #2e7d32;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s;
            position: relative;
        }

        .notification-icon:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .notification-icon .badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #FF0000;
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            padding: 2px 6px;
            display: none;
        }

        .notification-icon .badge.show {
            display: block;
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

        .main-content {
            padding: 5rem 2rem 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .reservation-card {
            background: #F9F5E8;
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
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .reservations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .reservations-table th, .reservations-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #4CAF50;
        }

        .reservations-table th {
            background-color: #F9F5E8;
            color: #2e7d32;
            font-weight: 600;
            cursor: pointer;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .reservations-table th:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .reservations-table td {
            color: #333;
        }

        .reservations-table .actions {
            display: flex;
            gap: 10px;
        }

        .reservations-table .btn {
            padding: 8px 12px;
            font-size: 14px;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            line-height: 1;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .reservations-table .btn.edit {
            background-color: #2e7d32;
        }

        .reservations-table .btn.edit:hover {
            background-color: #4CAF50;
        }

        .reservations-table .btn.delete {
            background-color: #FF0000;
        }

        .reservations-table .btn.delete:hover {
            background-color: #CC0000;
        }

        .reservations-table .btn.accept {
            background-color: #2e7d32;
        }

        .reservations-table .btn.accept:hover {
            background-color: #4CAF50;
        }

        .reservations-table .btn.details {
            background-color: #2e7d32;
            padding: 8px;
        }

        .reservations-table .btn.details:hover {
            background-color: #4CAF50;
        }

        .back-btn {
            background-color: #2e7d32;
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
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .back-btn:hover {
            background-color: #4CAF50;
        }

        .history-btn {
            background-color: #2e7d32;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 1rem;
            margin-left: 10px;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .history-btn:hover {
            background-color: #4CAF50;
        }

        .sort-container {
            margin-bottom: 1rem;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .sort-container label {
            font-weight: 500;
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .sort-container select {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #4CAF50;
            font-size: 14px;
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

        .notification-container {
            background: #F9F5E8;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: none;
            max-height: 400px;
            overflow-y: auto;
            position: relative;
            transition: all 0.3s ease;
        }

        .notification-container.show {
            display: block;
            animation: slideDown 0.3s ease-in-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification-container h3 {
            color: #2e7d32;
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 1rem;
            font-family: "Bauhaus 93", Arial, sans-serif;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-container .mark-all-btn {
            background-color: #4CAF50;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            margin-bottom: 1rem;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .notification-container .mark-all-btn:hover {
            background-color: #2e7d32;
        }

        .notification-list {
            list-style: none;
        }

        .notification-item {
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .notification-item.unread {
            background-color: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }

        .notification-item .icon {
            font-size: 1.2rem;
            color: #2e7d32;
        }

        .notification-item .content {
            flex: 1;
        }

        .notification-item .content p {
            margin: 0;
            color: #333;
            font-size: 14px;
            line-height: 1.4;
        }

        .notification-item .timestamp {
            font-size: 12px;
            color: #777;
            margin-top: 4px;
        }

        .notification-item .actions {
            display: flex;
            gap: 8px;
        }

        .notification-item .btn {
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 4px;
        }

        .notification-item .btn.dismiss {
            background-color: #FF0000;
        }

        .notification-item .btn.dismiss:hover {
            background-color: #CC0000;
        }

        .notification-item .btn.edit {
            background-color: #2e7d32;
        }

        .notification-item .btn.edit:hover {
            background-color: #4CAF50;
        }

        .no-notifications {
            text-align: center;
            color: #777;
            font-size: 14px;
            padding: 1rem;
        }

        .footer {
            background-color: #F9F5E8;
            color: #333;
            padding: 2rem;
            text-align: center;
            font-family: "Berlin Sans FB", Arial, sans-serif;
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
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .footer-column p, .footer-column a {
            font-size: 0.9rem;
            color: #555;
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
            font-family: "Berlin Sans FB", Arial, sans-serif;
        }

        .footer-column a:hover {
            color: #4CAF50;
        }

        .footer-bottom {
            margin-top: 1rem;
            font-size: 0.9rem;
            font-family: "Berlin Sans FB", Arial, sans-serif;
        }

        body.dark-mode {
            background-color: #2e7d32;
            color: #F9F5E8;
        }

        body.dark-mode .topbar,
        body.dark-mode .show-topbar-btn,
        body.dark-mode .nav-menu,
        body.dark-mode .footer {
            background-color: #F9F5E8;
        }

        body.dark-mode .reservation-card {
            background: #F9F5E8;
        }

        body.dark-mode .reservation-card h2 {
            color: #4CAF50;
        }

        body.dark-mode .reservations-table th {
            background-color: #F9F5E8;
            color: #4CAF50;
        }

        body.dark-mode .reservations-table td {
            color: #333;
        }

        body.dark-mode .back-btn {
            background-color: #2e7d32;
        }

        body.dark-mode .back-btn:hover {
            background-color: #4CAF50;
        }

        body.dark-mode .history-btn {
            background-color: #2e7d32;
        }

        body.dark-mode .history-btn:hover {
            background-color: #4CAF50;
        }

        body.dark-mode .sort-container label {
            color: #4CAF50;
        }

        body.dark-mode .sort-container select {
            background-color: #F9F5E8;
            color: #333;
            border-color: #4CAF50;
        }

        body.dark-mode .page-indicator {
            color: #333;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1rem;
            align-items: center;
        }

        .pagination .btn {
            padding: 8px 12px;
            font-size: 14px;
            color: white;
            background-color: #2e7d32;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .pagination .btn:hover {
            background-color: #4CAF50;
        }

        .pagination .btn.disabled {
            background-color: #4CAF50;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .page-indicator {
            font-size: 14px;
            color: #2e7d32;
            font-weight: 500;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        body.dark-mode .pagination .btn {
            background-color: #2e7d32;
        }

        body.dark-mode .pagination .btn:hover {
            background-color: #4CAF "-"50;
        }

        body.dark-mode .pagination .btn.disabled {
            background-color: #4CAF50;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #F9F5E8;
            padding: 20px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .modal-content h3 {
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
            margin-bottom: 15px;
            text-align: center;
        }

        .modal-content p {
            color: #333;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .modal-content .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            color: #2e7d32;
            cursor: pointer;
            transition: color 0.3s;
        }

        .modal-content .close-btn:hover {
            color: #4CAF50;
        }

        body.dark-mode .modal-content {
            background-color: #F9F5E8;
        }

        body.dark-mode .modal-content h3 {
            color: #4CAF50;
        }

        body.dark-mode .modal-content p {
            color: #333;
        }

        body.dark-mode .modal-content .close-btn {
            color: #4CAF50;
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

            .reservations-table {
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

            .notification-container {
                padding: 1rem;
            }

            .notification-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .notification-item .actions {
                margin-top: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .reservation-card {
                padding: 1rem;
            }

            .reservation-card h2 {
                font-size: 1.5rem;
            }

            .notification-container h3 {
                font-size: 1.4rem;
            }

            .notification-item .content p {
                font-size: 13px;
            }

            .notification-item .btn {
                padding: 5px 8px;
                font-size: 11px;
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
            <a href="consulter_mes_reservations.php" class="active" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
            <a href="forum.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'forum.php' ? 'active' : ''; ?>" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>
            <a href="javascript:void(0)" id="toggle-dark-mode" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
            <a href="javascript:void(0)" id="toggle-language" data-translate="language">🌐 <?php echo $language === 'fr' ? 'Français' : 'English'; ?></a>
        </div>
        <div class="notification-icon" onclick="toggleNotifications()">
            <i class="fas fa-bell"></i>
            <span class="badge" id="notification-badge"><?php echo count(array_filter($notifications, function($n) { return !$n['is_read']; })); ?></span>
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
        <a href="consulter_mes_reservations.php" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
        <a href="forum.php" class="active" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>
        <a href="javascript:void(0)" id="toggle-dark-mode-mobile" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
        <a href="javascript:void(0)" id="toggle-language-mobile" data-translate="language">🌐 <?php echo $language === 'fr' ? 'Français' : 'English'; ?></a>
        <a href="info2.php?page=gestion_utilisateurs&action=infos" data-translate="profile_info">📄 Mes informations</a>
        <a href="logout.php" data-translate="logout">🚪 Déconnexion</a>
    </div>

    <!-- Alert Container -->
    <div class="alert-container"></div>

    <!-- Modal for Reservation Details -->
    <div class="modal" id="reservation-details-modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">×</span>
            <h3 data-translate="reservation_details">Détails de la Réservation</h3>
            <p><strong data-translate="reservation_id">ID Réservation:</strong> <span id="modal-reservation-id"></span></p>
            <p><strong data-translate="client_id">ID Client:</strong> <span id="modal-client-id"></span></p>
            <p><strong data-translate="bike_id">ID Vélo:</strong> <span id="modal-bike-id"></span></p>
            <p><strong data-translate="bike">Vélo:</strong> <span id="modal-bike-name"></span></p>
            <p><strong data-translate="start_date">Date de Début:</strong> <span id="modal-start-date"></span></p>
            <p><strong data-translate="end_date">Date de Fin:</strong> <span id="modal-end-date"></span></p>
            <p><strong data-translate="gouvernorat">Gouvernorat:</strong> <span id="modal-gouvernorat"></span></p>
            <p><strong data-translate="telephone">Téléphone:</strong> <span id="modal-telephone"></span></p>
            <p><strong data-translate="duration">Durée:</strong> <span id="modal-duration"></span></p>
            <p><strong data-translate="reservation_date">Date de Réservation:</strong> <span id="modal-reservation-date"></span></p>
            <p><strong data-translate="status">Statut:</strong> <span id="modal-status"></span></p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php
        if (isset($_SESSION['alert'])) {
            $alert = $_SESSION['alert'];
            echo "<script>showAlert('{$alert['type']}', '{$alert['message']}');</script>";
            unset($_SESSION['alert']);
        }
        ?>

        <!-- Notification Section -->
        <div class="notification-container" id="notification-container">
            <h3 data-translate="notifications"><i class="fas fa-bell"></i> Notifications</h3>
            <?php if (empty($notifications)): ?>
                <p class="no-notifications" data-translate="no_notifications">Aucune notification trouvée.</p>
            <?php else: ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="mark_all_as_read" class="mark-all-btn" data-translate="mark_all_as_read">Tout marquer comme lu</button>
                </form>
                <ul class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                        <li class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <i class="fas fa-bell icon"></i>
                            <div class="content">
                                <p><?php echo htmlspecialchars($notification['message']); ?> (Réservation ID: <?php echo htmlspecialchars($notification['reservation_id']); ?>)</p>
                                <div class="timestamp"><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></div>
                            </div>
                            <div class="actions">
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars($notification['id']); ?>">
                                        <button type="submit" name="mark_as_read" class="btn edit" data-translate="mark_as_read">Marquer comme lu</button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn dismiss" onclick="dismissNotification(this)" title="<?php echo getTranslation('dismiss', $language, $translations); ?>"><i class="fas fa-times"></i></button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="reservation-card">
            <a href="reservationuser.php" class="back-btn" data-translate="back">Retour</a>
            <a href="history_reservations.php" class="history-btn" data-translate="history">Historique</a>
            <input type="text" id="search-id" placeholder="<?php echo getTranslation('search_reservation_or_bike_id', $language, $translations); ?>" style="padding: 8px; border-radius: 6px; border: 1px solid #4CAF50; font-size: 14px; margin-bottom: 1rem; margin-left: 10px;">
            <h2 data-translate="my_reservations">Mes Réservations</h2>
            <div class="sort-container">
                <label for="sort-column" data-translate="sort_by">Trier par :</label>
                <select id="sort-column" onchange="sortTable()">
                    <option value="id_reservation" <?php echo $sort_column === 'id_reservation' ? 'selected' : ''; ?> data-translate="reservation_id">ID Réservation</option>
                    <option value="date_debut" <?php echo $sort_column === 'date_debut' ? 'selected' : ''; ?> data-translate="start_date">Date de Début</option>
                    <option value="date_fin" <?php echo $sort_column === 'date_fin' ? 'selected' : ''; ?> data-translate="end_date">Date de Fin</option>
                    <option value="gouvernorat" <?php echo $sort_column === 'gouvernorat' ? 'selected' : ''; ?> data-translate="gouvernorat">Gouvernorat</option>
                    <option value="date_reservation" <?php echo $sort_column === 'date_reservation' ? 'selected' : ''; ?> data-translate="reservation_date">Date de Réservation</option>
                </select>
                <select id="sort-order" onchange="sortTable()">
                    <option value="asc" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?> data-translate="ascending">Croissant</option>
                    <option value="desc" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?> data-translate="descending">Décroissant</option>
                </select>
            </div>
            <!-- Pagination au-dessus du tableau -->
            <div class="pagination">
                <button class="btn prev-btn disabled" onclick="changePage(-1)" data-translate="previous"><i class="fas fa-arrow-left"></i> Précédent</button>
                <span class="page-indicator" data-translate="page_indicator"></span>
                <button class="btn next-btn <?php echo count($reservations) <= 3 ? 'disabled' : ''; ?>" onclick="changePage(1)" data-translate="next">Suivant <i class="fas fa-arrow-right"></i></button>
            </div>
            <?php if (empty($reservations)): ?>
                <p data-translate="no_reservations">Aucune réservation trouvée.</p>
            <?php else: ?>
                <table class="reservations-table" id="reservations-table">
                    <thead>
                        <tr>
                            <th data-sort="id_reservation" data-translate="reservation_id">ID Réservation</th>
                            <th data-sort="id_client" data-translate="client_id">ID Client</th>
                            <th data-sort="id_velo" data-translate="bike_id">ID Vélo</th>
                            <th data-translate="bike">Vélo</th>
                            <th data-sort="date_debut" data-translate="start_date">Date de Début</th>
                            <th data-sort="date_fin" data-translate="end_date">Date de Fin</th>
                            <th data-sort="gouvernorat" data-translate="gouvernorat">Gouvernorat</th>
                            <th data-translate="telephone">Téléphone</th>
                            <th data-translate="duration">Durée</th>
                            <th data-sort="date_reservation" data-translate="reservation_date">Date de Réservation</th>
                            <th data-translate="status">Statut</th>
                            <th data-translate="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $index => $reservation): ?>
                            <tr class="reservation-row" style="display: <?php echo $index < 3 ? 'table-row' : 'none'; ?>">
                                <td><?php echo htmlspecialchars($reservation['id_reservation']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['id_client']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['id_velo']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['bike_name'] ?: ($reservation['type_velo'] ?: 'Vélo ' . $reservation['id_velo'])); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_debut']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_fin']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['gouvernorat']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['telephone']); ?></td>
                                <td>
                                    <?php
                                    switch ($reservation['duree_reservation']) {
                                        case '1':
                                            echo getTranslation('day', $language, $translations);
                                            break;
                                        case '7':
                                            echo getTranslation('week', $language, $translations);
                                            break;
                                        case '30':
                                            echo getTranslation('month', $language, $translations);
                                            break;
                                        default:
                                            echo htmlspecialchars($reservation['duree_reservation']) . ' ' . getTranslation('days', $language, $translations);
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($reservation['date_reservation']); ?></td>
                                <td><?php echo htmlspecialchars(getTranslation($reservation['statut'] ?: 'pending', $language, $translations)); ?></td>
                                <td class="actions">
                                    <?php if ($reservation['statut'] !== 'cancelled'): ?>
                                        <a href="modifier_reservation.php?id=<?php echo htmlspecialchars($reservation['id_reservation']); ?>" class="btn edit" title="<?php echo getTranslation('edit', $language, $translations); ?>"><i class="fas fa-edit"></i></a>
                                        <a href="supprimer_reservation.php?id=<?php echo htmlspecialchars($reservation['id_reservation']); ?>" class="btn delete" onclick="return confirmDelete();" title="<?php echo getTranslation('delete_reservation', $language, $translations); ?>"><i class="fas fa-trash"></i></a>
                                        <button class="btn details" onclick="showReservationDetails(<?php echo htmlspecialchars(json_encode($reservation)); ?>)" title="<?php echo getTranslation('reservation_details', $language, $translations); ?>"><i class="fas fa-info-circle"></i></button>
                                        <?php if ($user['role'] === 'admin' && $reservation['statut'] !== 'accepted' && $reservation['statut'] !== 'rejected'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id_reservation']); ?>">
                                                <button type="submit" name="accept_reservation" class="btn accept" title="<?php echo getTranslation('accept_reservation', $language, $translations); ?>"><i class="fas fa-check"></i></button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id_reservation']); ?>">
                                                <button type="submit" name="reject_reservation" class="btn delete" title="<?php echo getTranslation('reservation_rejected', $language, $translations); ?>"><i class="fas fa-times"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
        let currentPage = 1;
        const itemsPerPage = 3;
        let filteredRows = [];

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

        function toggleNotifications() {
            const notificationContainer = document.getElementById('notification-container');
            notificationContainer.classList.toggle('show');
        }

        function updateNotificationBadge() {
            const badge = document.getElementById('notification-badge');
            const unreadCount = document.querySelectorAll('.notification-item.unread').length;
            badge.textContent = unreadCount;
            badge.classList.toggle('show', unreadCount > 0);
        }

        function dismissNotification(button) {
            const notificationItem = button.closest('.notification-item');
            notificationItem.style.opacity = '0';
            notificationItem.style.transform = 'translateX(20px)';
            setTimeout(() => {
                notificationItem.remove();
                updateNotificationBadge();
                if (!document.querySelector('.notification-item')) {
                    const notificationContainer = document.getElementById('notification-container');
                    notificationContainer.innerHTML += '<p class="no-notifications" data-translate="no_notifications">Aucune notification trouvée.</p>';
                }
            }, 300);
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
                title_view_reservations: "Green.tn - Mes Réservations",
                my_reservations: "Mes Réservations",
                no_reservations: "Aucune réservation trouvée.",
                reservation_id: "ID Réservation",
                client_id: "ID Client",
                bike_id: "ID Vélo",
                bike: "Vélo",
                start_date: "Date de Début",
                end_date: "Date de Fin",
                gouvernorat: "Gouvernorat",
                telephone: "Téléphone",
                duration: "Durée",
                reservation_date: "Date de Réservation",
                status: "Statut",
                actions: "Actions",
                back: "Retour",
                history: "Historique",
                edit: "Modifier",
                delete_reservation: "Supprimer la réservation",
                accept_reservation: "Accepter",
                reservation_details: "Détails de la Réservation",
                reservation_accepted: "Réservation acceptée",
                reservation_rejected: "Réservation refusée",
                error_reservation_not_found: "Réservation non trouvée ou déjà traitée",
                delete_confirm: "Êtes-vous sûr de vouloir supprimer cette réservation ?",
                footer_about: "À propos",
                footer_about_text: "Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.",
                footer_contact: "Contact",
                footer_copyright: "© 2025 Green.tn – Tous droits réservés",
                error_user_not_found: "Utilisateur non trouvé.",
                error_database: "Erreur de base de données. Veuillez réessayer plus tard.",
                day: "jour",
                week: "semaine",
                month: "mois",
                days: "jours",
                no_history: "Aucun historique de réservation trouvé.",
                pending: "En attente",
                accepted: "Acceptée",
                rejected: "Refusée",
                cancelled: "Annulé",
                completed: "Terminé",
                sort_by: "Trier par",
                ascending: "Croissant",
                descending: "Décroissant",
                previous: "Précédent",
                next: "Suivant",
                search_reservation_or_bike_id: "Rechercher par ID de réservation ou ID de vélo",
                page_indicator: "Page {current} sur {total}",
                reservation_accepted_notification: "Votre réservation a été acceptée.",
                reservation_rejected_notification: "Votre réservation a été refusée.",
                mark_as_read: "Marquer comme lu",
                mark_all_as_read: "Tout marquer comme lu",
                dismiss: "Ignorer",
                notifications: "Notifications",
                no_notifications: "Aucune notification trouvée.",
                notification_marked_read: "Notification marquée comme lue.",
                all_notifications_marked_read: "Toutes les notifications marquées comme lues."
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
                title_view_reservations: "Green.tn - My Reservations",
                my_reservations: "My Reservations",
                no_reservations: "No reservations found.",
                reservation_id: "Reservation ID",
                client_id: "Client ID",
                bike_id: "Bike ID",
                bike: "Bike",
                start_date: "Start Date",
                end_date: "End Date",
                gouvernorat: "Governorate",
                telephone: "Phone",
                duration: "Duration",
                reservation_date: "Reservation Date",
                status: "Status",
                actions: "Actions",
                back: "Back",
                history: "History",
                edit: "Edit",
                delete_reservation: "Delete Reservation",
                accept_reservation: "Accept",
                reservation_details: "Reservation Details",
                reservation_accepted: "Reservation accepted",
                reservation_rejected: "Reservation rejected",
                error_reservation_not_found: "Reservation not found or already processed",
                delete_confirm: "Are you sure you want to delete this reservation?",
                footer_about: "About",
                footer_about_text: "Green.tn promotes sustainable mobility through eco-friendly bike rental solutions in Tunisia.",
                footer_contact: "Contact",
                footer_copyright: "© 2025 Green.tn – All rights reserved",
                error_user_not_found: "User not found.",
                error_database: "Database error. Please try again later.",
                day: "day",
                week: "week",
                month: "month",
                days: "days",
                no_history: "No reservation history found.",
                pending: "Pending",
                accepted: "Accepted",
                rejected: "Rejected",
                cancelled: "Cancelled",
                completed: "Completed",
                sort_by: "Sort by",
                ascending: "Ascending",
                descending: "Descending",
                previous: "Previous",
                next: "Next",
                search_reservation_or_bike_id: "Search by Reservation ID or Bike ID",
                page_indicator: "Page {current} of {total}",
                reservation_accepted_notification: "Your reservation has been accepted.",
                reservation_rejected_notification: "Your reservation has been rejected.",
                mark_as_read: "Mark as read",
                mark_all_as_read: "Mark all as read",
                dismiss: "Dismiss",
                notifications: "Notifications",
                no_notifications: "No notifications found.",
                notification_marked_read: "Notification marked as read.",
                all_notifications_marked_read: "All notifications marked as read."
            }
        };

        fetch('/assets/translations.json')
            .then(response => response.json())
            .then(data => {
                translations = { ...translations, ...data };
                applyTranslations(currentLanguage);
                updatePagination();
                updateNotificationBadge();
            })
            .catch(error => {
                console.error('Error loading translations:', error);
                applyTranslations(currentLanguage);
                updatePagination();
                updateNotificationBadge();
            });

        function applyTranslations(lang) {
            document.querySelectorAll('[data-translate]').forEach(element => {
                const key = element.getAttribute('data-translate');
                if (translations[lang] && translations[lang][key]) {
                    if (key === 'page_indicator') {
                        const totalPages = Math.ceil((filteredRows.length || document.querySelectorAll('#reservations-table .reservation-row').length) / itemsPerPage);
                        element.textContent = translations[lang][key].replace('{current}', currentPage).replace('{total}', totalPages || 1);
                    } else {
                        element.textContent = translations[lang][key];
                    }
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

            document.title = translations[lang]['title_view_reservations'] || 'Green.tn';
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

        function confirmDelete() {
            return confirm(translations[currentLanguage]['delete_confirm']);
        }

        function sortTable() {
            const sortColumn = document.getElementById('sort-column').value;
            const sortOrder = document.getElementById('sort-order').value;
            window.location.href = `?sort=${sortColumn}&order=${sortOrder}${currentLanguage ? '&lang=' + currentLanguage : ''}`;
        }

        function updatePagination() {
            const rows = filteredRows.length ? filteredRows : Array.from(document.querySelectorAll('#reservations-table .reservation-row'));
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / itemsPerPage);

            // Hide all rows initially
            rows.forEach(row => row.style.display = 'none');

            // Show only the rows for the current page
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            rows.slice(startIndex, endIndex).forEach(row => row.style.display = 'table-row');

            // Update button states
            const prevBtn = document.querySelector('.prev-btn');
            const nextBtn = document.querySelector('.next-btn');
            if (prevBtn) prevBtn.classList.toggle('disabled', currentPage === 1);
            if (nextBtn) nextBtn.classList.toggle('disabled', currentPage >= totalPages || totalRows <= itemsPerPage);

            // Update page indicator
            const pageIndicator = document.querySelector('.page-indicator');
            if (pageIndicator) {
                pageIndicator.textContent = translations[currentLanguage]['page_indicator'].replace('{current}', currentPage).replace('{total}', totalPages || 1);
            }
        }

        function changePage(direction) {
            const rows = filteredRows.length ? filteredRows : Array.from(document.querySelectorAll('#reservations-table .reservation-row'));
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / itemsPerPage);

            // Update current page
            currentPage += direction;
            if (currentPage < 1) currentPage = 1;
            if (currentPage > totalPages) currentPage = totalPages;

            // Update pagination
            updatePagination();
        }

        function searchById() {
            const searchValue = document.getElementById('search-id').value.trim().toLowerCase();
            const rows = Array.from(document.querySelectorAll('#reservations-table .reservation-row'));
            filteredRows = [];

            rows.forEach(row => {
                const reservationId = row.cells[0].textContent.toLowerCase();
                const bikeId = row.cells[2].textContent.toLowerCase();
                if (searchValue === '' || reservationId.includes(searchValue) || bikeId.includes(searchValue)) {
                    filteredRows.push(row);
                }
            });

            currentPage = 1;
            updatePagination();
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function showReservationDetails(reservation) {
            document.getElementById('modal-reservation-id').textContent = reservation.id_reservation;
            document.getElementById('modal-client-id').textContent = reservation.id_client;
            document.getElementById('modal-bike-id').textContent = reservation.id_velo;
            document.getElementById('modal-bike-name').textContent = reservation.bike_name || (reservation.type_velo || 'Vélo ' + reservation.id_velo);
            document.getElementById('modal-start-date').textContent = reservation.date_debut;
            document.getElementById('modal-end-date').textContent = reservation.date_fin;
            document.getElementById('modal-gouvernorat').textContent = reservation.gouvernorat;
            document.getElementById('modal-telephone').textContent = reservation.telephone;
            document.getElementById('modal-duration').textContent = (() => {
                switch (reservation.duree_reservation) {
                    case '1': return translations[currentLanguage]['day'];
                    case '7': return translations[currentLanguage]['week'];
                    case '30': return translations[currentLanguage]['month'];
                    default: return reservation.duree_reservation + ' ' + translations[currentLanguage]['days'];
                }
            })();
            document.getElementById('modal-reservation-date').textContent = reservation.date_reservation;
            document.getElementById('modal-status').textContent = translations[currentLanguage][reservation.statut] || reservation.statut || translations[currentLanguage]['pending'];
            document.getElementById('reservation-details-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('reservation-details-modal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', () => {
            updatePagination();
            updateNotificationBadge();
            document.getElementById('search-id').addEventListener('input', debounce(searchById, 300));
        });
    </script>
</body>
</html>
```