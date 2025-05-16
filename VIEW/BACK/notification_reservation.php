<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

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
        'notifications' => 'Notifications',
        'message' => 'Message',
        'created_at' => 'Date',
        'status' => 'Statut',
        'read' => 'Lu',
        'unread' => 'Non lu',
        'no_notifications' => 'Aucune notification trouvée.',
        'home' => 'Accueil',
        'reservations' => 'Réservations',
        'profile_management' => 'Gestion de votre profil',
        'complaints' => 'Réclamations',
        'bikes_batteries' => 'Vélos',
        'repair_issues' => 'Réparer les pannes',
        'logout' => 'Déconnexion',
        'dark_mode' => 'Mode Sombre',
        'language' => 'Français',
        'error_database' => 'Erreur de base de données. Veuillez réessayer plus tard.',
        'error_user_not_found' => 'Utilisateur non trouvé.',
        'success_mark_read' => 'Notification marquée comme lue.',
        'details' => 'Détails'
    ],
    'en' => [
        'notifications' => 'Notifications',
        'message' => 'Message',
        'created_at' => 'Date',
        'status' => 'Status',
        'read' => 'Read',
        'unread' => 'Unread',
        'no_notifications' => 'No notifications found.',
        'home' => 'Home',
        'reservations' => 'Reservations',
        'profile_management' => 'Profile Management',
        'complaints' => 'Complaints',
        'bikes_batteries' => 'Bikes',
        'repair_issues' => 'Repair Issues',
        'logout' => 'Logout',
        'dark_mode' => 'Dark Mode',
        'language' => 'English',
        'error_database' => 'Database error. Please try again later.',
        'error_user_not_found' => 'User not found.',
        'success_mark_read' => 'Notification marked as read.',
        'details' => 'Details'
    ]
];

// Function to get translated text
function getTranslation($key, $lang, $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Fetch user info
$user_id = (int)$_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT prenom, nom FROM users WHERE id = ?");
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

// Handle mark as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $notification_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE notification_reservation SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        error_log("Notification ID $notification_id marquée comme lue pour l'utilisateur $user_id");
        $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('success_mark_read', $language, $translations)];
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de la notification ID $notification_id: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    }
    header("Location: notification_reservation.php");
    exit();
}

// Fetch notifications
try {
    $stmt = $pdo->prepare("SELECT id, message, created_at, is_read, reservation_id 
                           FROM notification_reservation 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC");
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
    <title data-translate="notifications">Notifications - Green.tn</title>
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
        .notifications-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }
        .notifications-table th, .notifications-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(76, 175, 80, 0.5);
            color: #000000;
        }
        .notifications-table th {
            background-color: rgba(96, 186, 151, 0.95);
            font-weight: bold;
        }
        .notifications-table td {
            background-color: transparent;
        }
        .notifications-table tr:last-child td {
            border-bottom: none;
        }
        .notifications-table tr.unread {
            background-color: #e8f5e9;
        }
        .notification-link {
            color: #2e7d32;
            text-decoration: none;
            cursor: pointer;
        }
        .notification-link:hover {
            text-decoration: underline;
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
        body.dark-mode .main-content, body.dark-mode .section-content {
            background-color: rgba(50, 50, 50, 0.9);
        }
        body.dark-mode .notifications-table {
            background-color: rgba(50, 50, 50, 0.9);
        }
        body.dark-mode .notifications-table th {
            background-color: rgba(56, 142, 60, 0.95);
            color: #ffffff;
        }
        body.dark-mode .notifications-table td {
            color: #ffffff;
        }
        body.dark-mode .notifications-table tr.unread {
            background-color: #2e7d32;
        }
        body d
        .dark-mode .notification-link {
            color: #4caf50;
        }
        @media (max-width: 992px) {
            .sidebar { left: -250px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
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
                    <a class="sidebar-nav-link" href="reservation.php" data-translate="reservations">
                        <span class="sidebar-nav-icon"><i class="bi bi-calendar"></i></span>
                        <span class="sidebar-nav-text">Réservations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="../reclamation/reclamations_utilisateur.php" data-translate="complaints">
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
                        <span class="sidebar-nav-text">Vélos</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link" href="repairs.html" data-translate="repair_issues">
                            <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                            <span class="sidebar-nav-text">Réparer les pannes</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link active" href="notification_reservation.php" data-translate="notifications">
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
        <h1 data-translate="notifications">Notifications</h1>

        <div class="section-content">
            <table class="notifications-table">
                <thead>
                    <tr>
                        <th data-translate="message">Message</th>
                        <th data-translate="created_at">Date</th>
                        <th data-translate="status">Statut</th>
                        <th data-translate="actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;" data-translate="no_notifications">Aucune notification trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <tr class="<?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                <td>
                                    <?php if (!empty($notification['reservation_id'])): ?>
                                        <a href="#" class="notification-link" onclick="window.open('reservation_details.php?id=<?php echo htmlspecialchars($notification['reservation_id']); ?>', 'ReservationDetails', 'width=400,height=500,scrollbars=yes,resizable=yes')" title="<?php echo getTranslation('details', $language, $translations); ?>">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($notification['created_at']); ?></td>
                                <td><?php echo $notification['is_read'] ? getTranslation('read', $language, $translations) : getTranslation('unread', $language, $translations); ?></td>
                                <td>
                                    <?php if (!$notification['is_read']): ?>
                                        <a href="?action=mark_read&id=<?php echo $notification['id']; ?>" class="btn" title="<?php echo getTranslation('read', $language, $translations); ?>">
                                            <i class="bi bi-check-circle"></i> <?php echo getTranslation('read', $language, $translations); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
                document.getElementById('toggle-language').innerHTML = `<i class="fas fa-globe"></i> ${translations[currentLanguage].language}`;
                document.title = translations[currentLanguage].notifications + ' - Green.tn';
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