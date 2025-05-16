<?php
session_start();
require_once 'C:\xampp\htdocs\projetweb\CONFIG\db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
        'home' => 'Accueil',
        'bikes' => 'Nos vélos',
        'about' => 'À propos de nous',
        'pricing' => 'Tarifs',
        'contact' => 'Contact',
        'reservations' => 'Réservations',
        'dark_mode' => 'Mode Sombre',
        'language' => 'Français',
        'profile_info' => 'Mes informations',
        'logout' => 'Déconnexion',
        'title_reservation' => 'Green.tn - Réserver un Vélo',
        'reserve_bike' => 'Réserver un Vélo',
        'select_bike' => 'Sélectionner un vélo',
        'select_gouvernorat' => 'Choisir un gouvernorat',
        'start_date' => 'Date de début',
        'end_date' => 'Date de fin',
        'gouvernorat' => 'Gouvernorat',
        'telephone' => 'Téléphone',
        'telephone_placeholder' => 'Entrez votre numéro de téléphone',
        'reserve' => 'Réserver',
        'cancel' => 'Annuler',
        'cancel_confirm' => 'Êtes-vous sûr de vouloir annuler ?',
        'footer_about' => 'À propos',
        'footer_about_text' => 'Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.',
        'footer_contact' => 'Contact',
        'footer_copyright' => '© 2025 Green.tn – Tous droits réservés',
        'error_bike_invalid' => 'Vélo sélectionné invalide.',
        'error_start_date_invalid' => 'La date de début doit être dans l\'année en cours.',
        'error_end_date_invalid' => 'La date de fin doit être postérieure ou égale à la date de début.',
        'error_bike_unavailable' => 'Ce vélo n\'est pas disponible pour les dates sélectionnées.',
        'error_no_bikes' => 'Aucun vélo disponible pour le moment.',
        'error_database' => 'Erreur de base de données. Veuillez réessayer plus tard.',
        'error_gouvernorat_empty' => 'Le gouvernorat est requis.',
        'error_telephone_invalid' => 'Numéro de téléphone invalide.',
        'success_reservation' => 'Réservation effectuée avec succès ! En attente de confirmation par l\'administrateur.',
        'manage_reservations' => 'Gérer Mes Réservations',
        'add_reservation' => 'Ajouter Réservation',
        'view_reservations' => 'Voir Mes Réservations',
        'stats_gouvernorat' => 'Statistiques par Gouvernorat',
        'error_user_not_found' => 'Utilisateur non trouvé.',
        'captcha_question' => 'Résolvez :',
        'captcha_placeholder' => 'Entrez la réponse',
        'captcha_error' => 'Erreur CAPTCHA. Veuillez recharger la page.',
        'error_captcha_invalid' => 'Réponse CAPTCHA incorrecte. Veuillez réessayer.'
        
    ],
    'en' => [
        'home' => 'Home',
        'bikes' => 'Bikes',
        'about' => 'About Us',
        'pricing' => 'Pricing',
        'contact' => 'Contact',
        'reservations' => 'Reservations',
        'dark_mode' => 'Dark Mode',
        'language' => 'English',
        'profile_info' => 'My Information',
        'logout' => 'Logout',
        'title_reservation' => 'Green.tn - Reserve a Bike',
        'reserve_bike' => 'Reserve a Bike',
        'select_bike' => 'Select a Bike',
        'select_gouvernorat' => 'Choose a governorate',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'gouvernorat' => 'Governorate',
        'telephone' => 'Phone',
        'telephone_placeholder' => 'Enter your phone number',
        'reserve' => 'Reserve',
        'cancel' => 'Cancel',
        'cancel_confirm' => 'Are you sure you want to cancel?',
        'footer_about' => 'About',
        'footer_about_text' => 'Green.tn promotes sustainable mobility through eco-friendly bike rental solutions in Tunisia.',
        'footer_contact' => 'Contact',
        'footer_copyright' => '© 2025 Green.tn – All rights reserved',
        'error_bike_invalid' => 'Selected bike is invalid.',
        'error_start_date_invalid' => 'Start date must be within the current year.',
        'error_end_date_invalid' => 'End date must be on or after the start date.',
        'error_bike_unavailable' => 'This bike is not available for the selected dates.',
        'error_no_bikes' => 'No bikes available at the moment.',
        'error_database' => 'Database error. Please try again later.',
        'error_gouvernorat_empty' => 'Governorate is required.',
        'error_telephone_invalid' => 'Invalid phone number.',
        'success_reservation' => 'Reservation successfully created! Awaiting confirmation by the administrator.',
        'manage_reservations' => 'Manage My Reservations',
        'add_reservation' => 'Add Reservation',
        'view_reservations' => 'View My Reservations',
        'stats_gouvernorat' => 'Governorate Statistics',
        'error_user_not_found' => 'User not found.',
        'captcha_question' => 'Solve:',
        'captcha_placeholder' => 'Enter the answer',
        'captcha_error' => 'CAPTCHA error. Please reload the page.',
        'error_captcha_invalid' => 'Incorrect CAPTCHA answer. Please try again.'
    ]
];

// Function to get translated text
function getTranslation($key, $lang, $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Get user information
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

// Fetch distinct bike types
try {
    $stmt = $pdo->query("SELECT DISTINCT type_velo FROM velos WHERE disponibilite = 1");
    $bike_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($bike_types)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_no_bikes', $language, $translations)];
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des types de vélos: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
    $bike_types = [];
}

// Fetch available bikes for display in the grid
try {
    $stmt = $pdo->query("SELECT * FROM velos WHERE disponibilite = 1");
    $bikes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des vélos: " . $e->getMessage());
    $bikes = [];
}

// Generate CAPTCHA
if (!isset($_SESSION['captcha_answer'])) {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['captcha_question'] = getTranslation('captcha_question', $language, $translations) . " $num1 + $num2 = ?";
    $_SESSION['captcha_answer'] = $num1 + $num2;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($bike_types)) {
    $type_velo = trim($_POST['type_velo']);
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $gouvernorat = trim($_POST['gouvernorat']);
    $telephone = trim($_POST['telephone']);
    $captcha = isset($_POST['captcha']) ? (int)$_POST['captcha'] : null;
    $date_reservation = date('Y-m-d H:i:s');

    // Validation
    $errors = [];

    // Validate CAPTCHA
    if ($captcha !== $_SESSION['captcha_answer']) {
        $errors[] = getTranslation('error_captcha_invalid', $language, $translations);
        // Regenerate CAPTCHA
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $_SESSION['captcha_question'] = getTranslation('captcha_question', $language, $translations) . " $num1 + $num2 = ?";
        $_SESSION['captcha_answer'] = $num1 + $num2;
    } else {
        unset($_SESSION['captcha_question'], $_SESSION['captcha_answer']);
    }

    // Validate bike type
    if (empty($type_velo) || !in_array($type_velo, $bike_types)) {
        $errors[] = getTranslation('error_bike_invalid', $language, $translations);
    }

    // Validate dates
    $min_date = date('Y') . '-01-01';
    if (empty($date_debut) || strtotime($date_debut) < strtotime($min_date)) {
        $errors[] = getTranslation('error_start_date_invalid', $language, $translations);
    }
    if (empty($date_fin) || strtotime($date_fin) < strtotime($date_debut)) {
        $errors[] = getTranslation('error_end_date_invalid', $language, $translations);
    }

    // Validate gouvernorat
    $valid_gouvernorats = [
        'Ariana', 'Béja', 'Ben Arous', 'Bizerte', 'Gabès', 'Gafsa', 'Jendouba', 'Kairouan',
        'Kasserine', 'Kébili', 'Le Kef', 'Mahdia', 'La Manouba', 'Médenine', 'Monastir',
        'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Sousse', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'
    ];
    if (empty($gouvernorat) || !in_array($gouvernorat, $valid_gouvernorats)) {
        $errors[] = getTranslation('error_gouvernorat_empty', $language, $translations);
    }

    // Validate telephone
    if (empty($telephone) || !preg_match('/^[0-9+\-\s]{8,15}$/', $telephone)) {
        $errors[] = getTranslation('error_telephone_invalid', $language, $translations);
    }

    // Find an available bike
    $id_velo = null;
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id_velo FROM velos WHERE type_velo = ? AND disponibilite = 1");
            $stmt->execute([$type_velo]);
            $available_bikes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($available_bikes as $bike) {
                $stmt = $pdo->prepare("SELECT * FROM reservation WHERE id_velo = ? AND statut = 'acceptee' AND (
                    (date_debut <= ? AND date_fin >= ?) OR
                    (date_debut <= ? AND date_fin >= ?)
                )");
                $stmt->execute([$bike['id_velo'], $date_debut, $date_debut, $date_fin, $date_fin]);
                if ($stmt->rowCount() == 0) {
                    $id_velo = $bike['id_velo'];
                    break;
                }
            }

            if (!$id_velo) {
                $errors[] = getTranslation('error_bike_unavailable', $language, $translations);
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de la disponibilité: " . $e->getMessage());
            $errors[] = getTranslation('error_database', $language, $translations);
        }
    }

    // Process reservation and notifications
    if (empty($errors)) {
        try {
            // Insert reservation
            $stmt = $pdo->prepare("INSERT INTO reservation (id_client, id_velo, date_debut, date_fin, gouvernorat, telephone, date_reservation, statut) VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente')");
            $stmt->execute([$user_id, $id_velo, $date_debut, $date_fin, $gouvernorat, $telephone, $date_reservation]);
            $reservation_id = $pdo->lastInsertId();
            error_log("Réservation créée : ID $reservation_id pour l'utilisateur $user_id");

            // Create notifications for admins
            $stmt = $pdo->prepare("SELECT id, prenom, nom FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Admins trouvés : " . count($admins));

            $successful_notifications = 0;
            if (empty($admins)) {
                error_log("Aucun admin trouvé pour les notifications. La réservation est enregistrée, mais aucun admin n'a été notifié.");
                $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('success_reservation', $language, $translations) . ' (Note: Aucun admin n\'a été notifié.)'];
            } else {
                $user_name = htmlspecialchars($user['prenom'] . ' ' . $user['nom']);
                $message = sprintf(
                    "Nouvelle réservation #%d par %s pour le vélo type %s (ID: %d, du %s au %s).",
                    $reservation_id, $user_name, $type_velo, $id_velo, $date_debut, $date_fin
                );
                foreach ($admins as $admin) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO notification_reservation (user_id, message, created_at, is_read, reservation_id) VALUES (?, ?, NOW(), 0, ?)");
                        $stmt->execute([$admin['id'], $message, $reservation_id]);
                        error_log("Notification créée pour l'admin {$admin['id']} ({$admin['prenom']} {$admin['nom']}) pour la réservation $reservation_id");
                        $successful_notifications++;
                    } catch (PDOException $e) {
                        error_log("Erreur lors de la création de la notification pour l'admin {$admin['id']}: " . $e->getMessage());
                    }
                }
                error_log("Notifications envoyées avec succès à $successful_notifications admins sur " . count($admins));
                $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('success_reservation', $language, $translations)];
            }

            header("Location: reservationuser.php");
            exit();
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de la réservation: " . $e->getMessage());
            $errors[] = getTranslation('error_database', $language, $translations);
        }
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="title_reservation">Green.tn - Réserver un Vélo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

.reservation-card {
    background: #F9F5E8; /* Updated from rgba(255, 255, 255, 0.95) */
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
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.reservation-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.reservation-form label {
    font-weight: 600;
    color: #333;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.reservation-form select,
.reservation-form input {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #4CAF50; /* Updated from #ccc */
    font-size: 14px;
    width: 100%;
}

.reservation-form .error-message {
    color: #FF0000; /* Updated from #e74c3c */
    font-size: 12px;
    margin-top: 4px;
    display: none;
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
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.reservation-form .btn.submit {
    background-color: #2e7d32;
}

.reservation-form .btn.submit:hover {
    background-color: #4CAF50; /* Updated from #1b5e20 */
}

.reservation-form .btn.cancel {
    background-color: #FF0000; /* Updated from #e74c3c */
}

.reservation-form .btn.cancel:hover {
    background-color: #CC0000; /* Updated from #c0392b */
}

.bike-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 1.5rem;
}

.bike-card {
    background-color: #F9F5E8; /* Updated from #f9f9f9 */
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
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
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

body.dark-mode .reservation-card {
    background: #F9F5E8; /* Updated from rgba(50, 50, 50, 0.95) */
}

body.dark-mode .reservation-card h2 {
    color: #4CAF50;
}

body.dark-mode .reservation-form label {
    color: #333; /* Updated from #e0e0e0 */
}

body.dark-mode .reservation-form input,
body.dark-mode .reservation-form select {
    background: #F9F5E8; /* Updated from #444 */
    color: #333; /* Updated from #e0e0e0 */
    border-color: #4CAF50; /* Updated from #666 */
}

body.dark-mode .bike-card {
    background-color: #F9F5E8; /* Updated from #3a506b */
}

body.dark-mode .bike-card .bike-info h3 {
    color: #4CAF50;
}

.reservation-actions {
    background: #F9F5E8; /* Updated from rgba(255, 255, 255, 0.95) */
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    text-align: center;
}

.reservation-actions h3 {
    color: #2e7d32;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.reservation-actions .btn {
    padding: 10px 20px;
    font-size: 14px;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin: 0 10px;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.reservation-actions .btn.add {
    background-color: #2e7d32;
}

.reservation-actions .btn.add:hover {
    background-color: #4CAF50; /* Updated from #1b5e20 */
}

.reservation-actions .btn.view {
    background-color: #2e7d32; /* Updated from #7b1fa2 */
}

.reservation-actions .btn.view:hover {
    background-color: #4CAF50; /* Updated from #4a0072 */
}

.reservation-actions .btn.stats {
    background-color: #2e7d32; /* Updated from #0288d1 */
}

.reservation-actions .btn.stats:hover {
    background-color: #4CAF50; /* Updated from #01579b */
}

body.dark-mode .reservation-actions {
    background: #F9F5E8; /* Updated from rgba(50, 50, 50, 0.95) */
}

body.dark-mode .reservation-actions h3 {
    color: #4CAF50;
}

.captcha-container {
    margin: 1rem 0;
}

.captcha-container label {
    display: block;
    margin-bottom: 0.5rem;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated from Poppins */
}

.captcha-container input {
    width: 100px;
}

.captcha-container .error-message {
    color: #FF0000; /* Updated from #e74c3c */
    font-size: 12px;
    margin-top: 4px;
    display: none;
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

    .reservation-card, .reservation-actions {
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

    .reservation-actions .btn {
        display: block;
        margin: 10px auto;
        width: 80%;
    }
}

@media (max-width: 480px) {
    .reservation-card, .reservation-actions {
        padding: 1rem;
    }

    .reservation-card h2, .reservation-actions h3 {
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
            <a href="forum.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'forum.php' ? 'active' : ''; ?>" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>

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
        <a href="forum.php" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>

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

    <!-- Reservation Actions -->
    <div class="reservation-actions">
        <h3 data-translate="manage_reservations">Gérer Mes Réservations</h3>
        <a href="reservationuser.php" class="btn add" data-translate="add_reservation"><i class="fas fa-plus"></i> Ajouter Réservation</a>
        <a href="consulter_mes_reservations.php" class="btn view" data-translate="view_reservations"><i class="fas fa-eye"></i> Voir Mes Réservations</a>
        <a href="stats_gouvernorat.php" class="btn stats" data-translate="stats_gouvernorat"><i class="fas fa-chart-bar"></i> Statistiques par Gouvernorat</a>
    </div>

    <!-- Reservation Form -->
    <div class="reservation-card">
        <h2 data-translate="reserve_bike">Réserver un Vélo</h2>
        <?php if (empty($bike_types)): ?>
            <p data-translate="error_no_bikes">Aucun vélo disponible pour le moment.</p>
        <?php else: ?>
            <div class="bike-grid">
                <?php foreach ($bikes as $bike): ?>
                    <div class="bike-card">
                        <img src="<?php echo htmlspecialchars(isset($bike['image']) ? $bike['image'] : 'bike.jpg'); ?>" alt="<?php echo htmlspecialchars($bike['type_velo']); ?>">
                        <div class="bike-info">
                            <h3><?php echo htmlspecialchars($bike['type_velo']); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="reservation-form" id="reservation-form">
                <label for="type_velo" data-translate="select_bike">Sélectionner un type de vélo</label>
                <select id="type_velo" name="type_velo">
                    <option value="" disabled selected data-translate="select_bike">Choisir un type de vélo</option>
                    <?php foreach ($bike_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="error-message" id="type_velo_error"></div>

                <label for="date_debut" data-translate="start_date">Date de début</label>
                <input type="date" id="date_debut" name="date_debut" min="<?php echo date('Y') . '-01-01'; ?>">
                <div class="error-message" id="date_debut_error"></div>

                <label for="date_fin" data-translate="end_date">Date de fin</label>
                <input type="date" id="date_fin" name="date_fin" min="<?php echo date('Y') . '-01-01'; ?>">
                <div class="error-message" id="date_fin_error"></div>

                <label for="gouvernorat" data-translate="gouvernorat">Gouvernorat</label>
                <select id="gouvernorat" name="gouvernorat">
                    <option value="" disabled selected data-translate="select_gouvernorat">Choisir un gouvernorat</option>
                    <option value="Ariana">Ariana</option>
                    <option value="Béja">Béja</option>
                    <option value="Ben Arous">Ben Arous</option>
                    <option value="Bizerte">Bizerte</option>
                    <option value="Gabès">Gabès</option>
                    <option value="Gafsa">Gafsa</option>
                    <option value="Jendouba">Jendouba</option>
                    <option value="Kairouan">Kairouan</option>
                    <option value="Kasserine">Kasserine</option>
                    <option value="Kébili">Kébili</option>
                    <option value="Le Kef">Le Kef</option>
                    <option value="Mahdia">Mahdia</option>
                    <option value="La Manouba">La Manouba</option>
                    <option value="Médenine">Médenine</option>
                    <option value="Monastir">Monastir</option>
                    <option value="Nabeul">Nabeul</option>
                    <option value="Sfax">Sfax</option>
                    <option value="Sidi Bouzid">Sidi Bouzid</option>
                    <option value="Siliana">Siliana</option>
                    <option value="Sousse">Sousse</option>
                    <option value="Tataouine">Tataouine</option>
                    <option value="Tozeur">Tozeur</option>
                    <option value="Tunis">Tunis</option>
                    <option value="Zaghouan">Zaghouan</option>
                </select>
                <div class="error-message" id="gouvernorat_error"></div>

                <label for="telephone" data-translate="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" placeholder="<?php echo getTranslation('telephone_placeholder', $language, $translations); ?>">
                <div class="error-message" id="telephone_error"></div>

                <div class="captcha-container">
                    <label for="captcha"><?php echo isset($_SESSION['captcha_question']) ? htmlspecialchars($_SESSION['captcha_question']) : getTranslation('captcha_error', $language, $translations); ?></label>
                    <input type="number" id="captcha" name="captcha" placeholder="<?php echo getTranslation('captcha_placeholder', $language, $translations); ?>">
                    <div class="error-message" id="captcha_error"></div>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn submit" data-translate="reserve">Réserver</button>
                    <a href="info2.php?page=accueil" class="btn cancel" data-translate="cancel" onclick="return confirmCancel();">Annuler</a>
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
            <p><a href="tel:+21624531890" title="Appeler +216 24 531 890" class="phone-link">📞 +216 24 531 890</a></p>
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

    let currentLanguage = '<?php echo $language; ?>';
    let translations = <?php echo json_encode($translations); ?>;

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

        document.title = translations[lang]['title_reservation'] || 'Green.tn';
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

    document.getElementById('reservation-form').addEventListener('submit', function(event) {
        let isValid = true;
        const errors = [];

        document.querySelectorAll('.error-message').forEach(error => {
            error.style.display = 'none';
            error.textContent = '';
        });

        const typeVelo = document.getElementById('type_velo').value;
        if (!typeVelo) {
            isValid = false;
            const errorElement = document.getElementById('type_velo_error');
            errorElement.textContent = translations[currentLanguage]['error_bike_invalid'];
            errorElement.style.display = 'block';
            errors.push(translations[currentLanguage]['error_bike_invalid']);
        }

        const dateDebut = document.getElementById('date_debut').value;
        const minDate = '<?php echo date('Y') . '-01-01'; ?>';
        if (!dateDebut || dateDebut < minDate) {
            isValid = false;
            const errorElement = document.getElementById('date_debut_error');
            errorElement.textContent = translations[currentLanguage]['error_start_date_invalid'];
            errorElement.style.display = 'block';
            errors.push(translations[currentLanguage]['error_start_date_invalid']);
        }

        const dateFin = document.getElementById('date_fin').value;
        if (!dateFin || dateFin < dateDebut) {
            isValid = false;
            const errorElement = document.getElementById('date_fin_error');
            errorElement.textContent = translations[currentLanguage]['error_end_date_invalid'];
            errorElement.style.display = 'block';
            errors.push(translations[currentLanguage]['error_end_date_invalid']);
        }

        const gouvernorat = document.getElementById('gouvernorat').value;
        if (!gouvernorat) {
            isValid = false;
            const errorElement = document.getElementById('gouvernorat_error');
            errorElement.textContent = translations[currentLanguage]['error_gouvernorat_empty'];
            errorElement.style.display = 'block';
            errors.push(translations[currentLanguage]['error_gouvernorat_empty']);
        }

        const telephone = document.getElementById('telephone').value.trim();
        if (!telephone || !/^[0-9+\-\s]{8,15}$/.test(telephone)) {
            isValid = false;
            const errorElement = document.getElementById('telephone_error');
            errorElement.textContent = translations[currentLanguage]['error_telephone_invalid'];
            errorElement.style.display = 'block';
            errors.push(translations[currentLanguage]['error_telephone_invalid']);
        }

        const captcha = document.getElementById('captcha').value;
        if (!captcha) {
            isValid = false;
            const errorElement = document.getElementById('captcha_error');
            errorElement.textContent = translations[currentLanguage]['error_captcha_invalid'];
            errorElement.style.display = 'block';
            errors.push(translations[currentLanguage]['error_captcha_invalid']);
        }

        if (!isValid) {
            event.preventDefault();
            showAlert('error', errors.join('<br>'));
        }
    });

    function confirmCancel() {
        return confirm(translations[currentLanguage]['cancel_confirm']);
    }

    // Dynamically update date_fin min attribute based on date_debut
    document.getElementById('date_debut').addEventListener('change', function() {
        const dateDebut = this.value;
        const dateFinInput = document.getElementById('date_fin');
        dateFinInput.min = dateDebut;
        if (dateFinInput.value && dateFinInput.value < dateDebut) {
            dateFinInput.value = dateDebut;
        }
    });
</script>
</body>
</html>