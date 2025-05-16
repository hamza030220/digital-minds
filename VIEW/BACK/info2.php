<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

// Load translations
$translations_file = __DIR__ . '/assets/translations.json';
$translations = json_decode(file_get_contents($translations_file), true);

// Determine current language
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'fr';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
    $language = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?')); // Redirect to remove lang param
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

// Retrieve logged-in user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if user data is available
if (!$user) {
    echo "Error: User not found.";
    exit();
}

// Handle user update form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $age = (int)$_POST['age'];
    $gouvernorats = $_POST['gouvernorats'];

    // Validation
    if (empty($nom) || strlen($nom) < 2) {
        $errors['nom'] = getTranslation('error_name_short', $language, $translations);
    }
    if (empty($prenom) || strlen($prenom) < 2) {
        $errors['prenom'] = getTranslation('error_first_name_short', $language, $translations);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = getTranslation('error_email_invalid', $language, $translations);
    }
    if (!preg_match('/^[0-9]{8}$/', $telephone)) {
        $errors['telephone'] = getTranslation('error_phone_invalid', $language, $translations);
    }
    if ($age < 5 || $age > 80) {
        $errors['age'] = getTranslation('error_age_invalid', $language, $translations);
    }
    $valid_gouvernorats = ['Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'];
    if (!in_array($gouvernorats, $valid_gouvernorats)) {
        $errors['gouvernorats'] = getTranslation('error_gouvernorat_invalid', $language, $translations);
    }

    // Check email uniqueness (exclude current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->rowCount() > 0) {
        $errors['email'] = getTranslation('error_email_taken', $language, $translations);
    }

    // Handle photo upload
    $photo_path = $user['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_tmp = $_FILES['photo']['tmp_name'];
        $photo_type = mime_content_type($photo_tmp);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($photo_type, $allowed_types)) {
            $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            $upload_dir = 'user_images/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $photo_path = $photo_name;
            if (!move_uploaded_file($photo_tmp, $upload_dir . $photo_name)) {
                $errors['photo'] = getTranslation('error_photo_upload', $language, $translations);
            }
        } else {
            $errors['photo'] = getTranslation('error_photo_type', $language, $translations);
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, photo = ?, age = ?, gouvernorats = ? WHERE id = ?");
        $stmt->execute([$nom, $prenom, $email, $telephone, $photo_path, $age, $gouvernorats, $user_id]);
        header("Location: ?page=gestion_utilisateurs&action=infos");
        exit();
    }
}

// Store user information for display
$users = [$user];

// SQL query for governorates chart
$stmtGouv = $pdo->query("SELECT gouvernorats, COUNT(*) as count FROM users GROUP BY gouvernorats");
$gouvData = $stmtGouv->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data = [];
$colors = [];

foreach ($gouvData as $row) {
    $labels[] = $row['gouvernorats'] ?: 'Non spécifié';
    $data[] = $row['count'];
    $colors[] = 'rgba(' . rand(50, 200) . ',' . rand(100, 200) . ',' . rand(100, 255) . ', 0.7)';
}

// Statistics for stats section
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalTechnicians = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'technicien'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="title">Green.tn - Location de Vélos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif; /* Updated fallback font */
}

body {
    background-color: #60BA97; /* Updated from #e8f5e9 */
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: #333;
    min-height: 100vh;
    animation: fadeIn 1s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Topbar */
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
    font-size: 16px;
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
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
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

/* Show Topbar Button */
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

/* Hamburger Menu */
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

/* Main Content */
.main-content {
    padding: 5rem 2rem 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

/* Hero Section (Dashboard) */
.hero-section {
    text-align: center;
    padding: 3rem 2rem;
    background: #F9F5E8; /* Updated from rgba(255, 255, 255, 0.95) */
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.hero-section img {
    width: 120px;
    margin-bottom: 1rem;
}

.hero-section h1 {
    color: #2e7d32;
    font-size: 2.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.hero-section p {
    font-size: 1.2rem;
    color: #333; /* Updated from #4b5563 */
    max-width: 600px;
    margin: 0 auto 1.5rem;
    line-height: 1.6;
}

.hero-section .cta-button {
    display: inline-block;
    padding: 0.8rem 2rem;
    background-color: #2e7d32;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    transition: background-color 0.3s, transform 0.2s;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.hero-section .cta-button:hover {
    background-color: #4CAF50; /* Updated from #1b5e20 */
    transform: translateY(-2px);
}

/* Card Sections */
.section-card {
    background: #F9F5E8; /* Updated from rgba(255, 255, 255, 0.95) */
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.section-card h2 {
    color: #2e7d32;
    font-size: 1.6rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 1rem;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.feature-card {
    background: #F9F5E8; /* Updated from #f9fafb */
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
}

.feature-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

.feature-card i {
    font-size: 2rem;
    color: #2e7d32;
    margin-bottom: 0.5rem;
}

.feature-card h3 {
    font-size: 1.1rem;
    color: #2e7d32;
    margin-bottom: 0.5rem;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.feature-card p {
    font-size: 0.9rem;
    color: #333; /* Updated from #4b5563 */
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: #F9F5E8; /* Updated from #f9fafb */
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
    font-size: 0.95rem;
    color: #2e7d32;
    margin-bottom: 0.5rem;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.stat-card p {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333; /* Updated from #1a1a1a */
}

/* Chart Section */
.chart-container {
    max-width: 700px;
    margin: 0 auto;
}

.chart-container canvas {
    background: #F9F5E8; /* Updated from #f9fafb */
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Profile Section */
.profile-box {
    display: flex;
    padding: 1.5rem;
    border-radius: 8px;
    background: #F9F5E8; /* Updated from rgba(255, 255, 255, 0.95) */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    align-items: center;
    margin-bottom: 2rem;
}

.profile-pic {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin-right: 1.5rem;
    object-fit: cover;
    border: 2px solid #4CAF50; /* Updated from #e8f5e9 */
}

.profile-details {
    max-width: 600px;
    flex-grow: 1;
}

.profile-info {
    margin-top: 1rem;
}

.info-item {
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.info-item strong {
    color: #2e7d32;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.edit-profile-btn {
    padding: 8px 16px;
    background-color: #2e7d32;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.edit-profile-btn:hover {
    background-color: #4CAF50; /* Updated from #1b5e20 */
    transform: translateY(-2px);
}

/* Edit User Form */
.edit-user-card {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px;
    background: #F9F5E8; /* Updated from no background */
}

.edit-user-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.edit-user-form label {
    font-weight: 600;
    color: #333;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.edit-user-form input,
.edit-user-form select {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #4CAF50; /* Updated from #ccc */
    font-size: 14px;
    width: 100%;
}

.edit-user-form input[type="file"] {
    padding: 5px;
}

.edit-user-form .error {
    color: #FF0000; /* Updated from #e74c3c for consistency */
    font-size: 12px;
    margin-top: 5px;
}

.edit-user-form .btn-container {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.edit-user-form .btn {
    padding: 10px 20px;
    font-size: 14px;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.edit-user-form .btn.submit {
    background-color: #2e7d32;
}

.edit-user-form .btn.submit:hover {
    background-color: #4CAF50; /* Updated from #1b5e20 */
}

.edit-user-form .btn.cancel {
    background-color: #FF0000; /* Updated from #e74c3c */
}

.edit-user-form .btn.cancel:hover {
    background-color: #CC0000; /* Updated from #c0392b */
}

/* Footer (Dashboard) */
.footer {
    background-color: #F9F5E8; /* Updated from #f5f5f5 */
    color: #333; /* Updated from #4b5563 */
    padding: 2rem;
    text-align: center;
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated font */
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
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.footer-column p, .footer-column a {
    font-size: 0.9rem;
    color: #2e7d32;
    text-decoration: none;
    margin-bottom: 0.5rem;
    display: block;
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated font */
}

.footer-column a:hover {
    color: #4CAF50; /* Updated from #1b5e20 */
}

.footer-bottom {
    margin-top: 1rem;
    font-size: 0.9rem;
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated font */
}

/* Styles de green.css pour la section accueil */
<?php if (!isset($_GET['page']) || $_GET['page'] == 'accueil'): ?>
/* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Hero Section */
.hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 60px 10%;
    background-color: #F9F5E8; /* Updated from #f4f4f4 */
    min-height: 500px;
    gap: 20px;
}

.hero-box {
    flex: 1;
    max-width: 50%;
}

.hero-box h1 {
    font-size: 48px;
    color: #2e7d32;
    margin-bottom: 20px;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.hero-box p {
    font-size: 18px;
    color: #333; /* Updated from #555 */
}

.hero-image {
    flex: 1;
    max-width: 50%;
    text-align: right;
}

.hero-image img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
}

.hero.about {
    background-color: #60BA97; /* Updated from #e8f5e9 */
}

/* Nos vélos Section */
#a-nos-velos {
    padding: 60px 10%;
    background-color: #60BA97; /* Updated from #fff */
}

#a-nos-velos h2 {
    font-size: 36px;
    color: #2e7d32;
    text-align: center;
    margin-bottom: 40px;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.bike-section {
    margin-bottom: 60px;
}

.bike-section h3 {
    font-size: 28px;
    color: #2e7d32;
    margin-bottom: 20px;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.bike-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
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

.bike-card .buttons {
    display: flex;
    justify-content: space-between;
    padding: 15px;
}

.bike-card .buttons button {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.bike-card .buttons .detail {
    background-color: #4CAF50;
    color: #fff;
}

.bike-card .buttons .detail:hover {
    background-color: #2e7d32; /* Updated from #388e3c */
}

.bike-card .buttons .reserve {
    background-color: #2e7d32; /* Updated from #1E90FF */
    color: #fff;
}

.bike-card .buttons .reserve:hover {
    background-color: #4CAF50; /* Updated from #32CD32 */
}

/* Tarifs Section */
.pricing {
    padding: 60px 10%;
    background-color: #F9F5E8; /* Updated from #f4f4f4 */
    text-align: center;
}

.pricing h2 {
    font-size: 36px;
    color: #2e7d32;
    margin-bottom: 40px;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.pricing-card {
    background-color: #F9F5E8; /* Updated from #fff */
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.pricing-card:hover {
    transform: translateY(-5px);
}

.pricing-card h3 {
    font-size: 24px;
    color: #2e7d32;
    margin-bottom: 10px;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.pricing-card .price {
    font-size: 18px;
    color: #333; /* Updated from #555 */
    margin-bottom: 10px;
}

/* Contact Section */
.contact {
    padding: 60px 10%;
    background-color: #60BA97; /* Updated from #fff */
}

.contact h2 {
    font-size: 36px;
    color: #2e7d32;
    text-align: center;
    margin-bottom: 40px;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.contact form {
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.name-row {
    display: flex;
    gap: 20px;
}

.name-field {
    flex: 1;
}

.contact label {
    font-size: 16px;
    color: #2e7d32;
    margin-bottom: 5px;
    display: block;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.contact input,
.contact textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #4CAF50; /* Updated from #ccc */
    border-radius: 5px;
    font-size: 16px;
}

.contact textarea {
    resize: vertical;
    min-height: 100px;
}

.contact button {
    padding: 10px 20px;
    background-color: #2e7d32; /* Updated from #4caf50 */
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
}

.contact button:hover {
    background-color: #4CAF50; /* Updated from #388e3c */
}

/* Footer (from green.css) */
footer.green-footer {
    background-color: #F9F5E8; /* Updated from #2e7d32 */
    color: #333; /* Updated from #fff */
    padding: 40px 10%;
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated font */
}

.footer-content {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.footer-left {
    flex: 1;
    min-width: 200px;
}

.footer-logo img {
    max-width: 150px;
    height: auto;
}

.social-icons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.social-icons a img {
    width: 30px;
    height: 30px;
}

.footer-section {
    flex: 1;
    min-width: 200px;
}

.footer-section h3 {
    font-size: 20px;
    margin-bottom: 20px;
    font-family: "Bauhaus 93", Arial, sans-serif; /* Updated font */
    color: #2e7d32; /* Updated from inherited #fff */
}

.footer-section ul {
    list-style: none;
}

.footer-section ul li {
    margin-bottom: 10px;
}

.footer-section ul li a {
    color: #555; /* Updated from #d0f0d6 */
    text-decoration: none;
    transition: color 0.3s ease;
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated font */
}

.footer-section ul li a:hover {
    color: #4CAF50; /* Updated from #fff */
}

.footer-section p {
    font-size: 16px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #555; /* Updated from inherited #fff */
    font-family: "Berlin Sans FB", Arial, sans-serif; /* Updated font */
}

.footer-section p img {
    width: 20px;
    height: 20px;
}

/* Dark Mode Adjustments for green.css */
body.dark-mode .hero,
body.dark-mode .pricing {
    background-color: #2e7d32; /* Updated from #2c3e50 */
    color: #F9F5E8; /* Updated from #e0e0e0 */
}

body.dark-mode #a-nos-velos,
body.dark-mode .contact {
    background-color: #2e7d32; /* Updated from #34495e */
    color: #F9F5E8; /* Updated from #e0e0e0 */
}

body.dark-mode .bike-card,
body.dark-mode .pricing-card {
    background-color: #F9F5E8; /* Updated from #3a506b */
    color: #333; /* Updated from #e0e0e0 */
}

body.dark-mode .contact input,
body.dark-mode .contact textarea {
    background-color: #F9F5E8; /* Updated from #444 */
    color: #333; /* Updated from #e0e0e0 */
    border-color: #4CAF50; /* Updated from #666 */
}

body.dark-mode footer.green-footer {
    background-color: #2e7d32; /* Updated from #1a3c34 */
}

/* Responsive */
@media (max-width: 768px) {
    .hero {
        flex-direction: column;
        text-align: center;
        padding: 40px 5%;
    }

    .hero-box,
    .hero-image {
        max-width: 100%;
    }

    .hero-image {
        text-align: center;
        margin-top: 20px;
    }

    .name-row {
        flex-direction: column;
    }

    .footer-content {
        flex-direction: column;
    }
}
<?php endif; ?>

/* Dark Mode (Dashboard) */
body.dark-mode {
    background-color: #2e7d32; /* Updated from gradient */
    color: #F9F5E8; /* Updated from #e0e0e0 */
}

body.dark-mode .topbar,
body.dark-mode .show-topbar-btn,
body.dark-mode .nav-menu,
body.dark-mode .footer {
    background-color: #F9F5E8; /* Updated from #2a2a2a */
}

body.dark-mode .hero-section,
body.dark-mode .section-card,
body.dark-mode .profile-box {
    background: #F9F5E8; /* Updated from rgba(50, 50, 50, 0.95) */
}

body.dark-mode .feature-card,
body.dark-mode .stat-card,
body.dark-mode .chart-container canvas {
    background: #F9F5E8; /* Updated from #3a3a3a */
}

body.dark-mode .profile-info strong,
body.dark-mode .section-card h2,
body.dark-mode .hero-section h1,
body.dark-mode .stat-card h3,
body.dark-mode .feature-card h3,
body.dark-mode .footer-column h3 {
    color: #4CAF50; /* Unchanged, matches bike site */
}

body.dark-mode .hero-section p,
body.dark-mode .feature-card p,
body.dark-mode .footer {
    color: #333; /* Updated from #b0b0b0 */
}

body.dark-mode .edit-user-card {
    background: #F9F5E8; /* Updated from rgba(50, 50, 50, 0.95) */
}

body.dark-mode .edit-user-form label {
    color: #333; /* Updated from #e0e0e0 */
}

body.dark-mode .edit-user-form input,
body.dark-mode .edit-user-form select {
    background: #F9F5E8; /* Updated from #444 */
    color: #333; /* Updated from #e0e0e0 */
    border-color: #4CAF50; /* Updated from #666 */
}

body.dark-mode .edit-profile-btn {
    background-color: #2e7d32; /* Updated from #4caf50 */
}

body.dark-mode .edit-profile-btn:hover {
    background-color: #4CAF50; /* Updated from #388e3c */
}

/* Responsive Design (Dashboard) */
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

    .hero-section h1 {
        font-size: 2rem;
    }

    .hero-section p {
        font-size: 1rem;
    }

    .features-grid,
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .profile-box {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .profile-pic {
        margin-bottom: 1rem;
    }

    .nav-menu {
        width: 100%;
    }

    .show-topbar-btn {
        top: 0.5rem;
        right: 3.5rem;
    }

    .edit-user-card {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: 2rem 1rem;
    }

    .hero-section img {
        width: 100px;
    }

    .section-card {
        padding: 1rem;
    }

    .chart-container canvas {
        padding: 0.5rem;
    }
}
    </style>
</head>
<body>
<!-- Topbar -->
<div class="topbar">
    <img src="logo.jpg" alt="Logo Green.tn" class="logo">
    <div class="nav-links">
        <a href="?page=accueil" class="<?php echo (!isset($_GET['page']) || $_GET['page'] == 'accueil') ? 'active' : ''; ?>" data-translate="home"><i class="fas fa-home"></i> Accueil</a>
        <a href="#a-nos-velos" data-translate="bikes"><i class="fas fa-bicycle"></i> Nos vélos</a>
        <a href="#a-propos-de-nous" data-translate="about"><i class="fas fa-info-circle"></i> À propos de nous</a>
        <a href="#pricing" data-translate="pricing"><i class="fas fa-dollar-sign"></i> Tarifs</a>
        <a href="../reclamation/liste_reclamations.php" data-translate="Reclamation"><i class="fas fa-envelope"></i> Reclamation</a>
        <a href="reservationuser.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reservationuser.php' ? 'active' : ''; ?>" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
         <a href="forum.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'forum.php' ? 'active' : ''; ?>" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>
        <a href="javascript:void(0)" id="toggle-dark-mode" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
        <a href="javascript:void(0)" id="toggle-language" data-translate="language">🌐 Français</a>
    </div>
    <div class="profile-icon">
        <a href="javascript:void(0)">
            <img src="user_images/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profil" class="top-profile-pic">
        </a>
        <div class="profile-menu">
            <a href="?page=gestion_utilisateurs&action=infos" class="profile-menu-item" data-translate="profile_info">📄 Mes informations</a>
            <a href="logout.php" class="profile-menu-item logout" data-translate="logout">🚪 Déconnexion</a>
        </div>
    </div>
    <div class="toggle-topbar" onclick="toggleTopbar()">▼</div>
</div>

<!-- Show Topbar Button -->
<div class="show-topbar-btn" onclick="toggleTopbar()">
    <span>▲</span>
</div>

<!-- Hamburger Menu (for mobile) -->
<div class="hamburger-menu">
    <div class="hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
    </div>
</div>
<div class="nav-menu">
    <a href="?page=accueil" data-translate="home"><i class="fas fa-home"></i> Accueil</a>
    <a href="#a-nos-velos" data-translate="bikes"><i class="fas fa-bicycle"></i> Nos vélos</a>
    <a href="#a-propos-de-nous" data-translate="about"><i class="fas fa-info-circle"></i> À propos de nous</a>
    <a href="#pricing" data-translate="pricing"><i class="fas fa-dollar-sign"></i> Tarifs</a>
    <a href="#contact" data-translate="contact"><i class="fas fa-envelope"></i> Contact</a>
    <a href="reservationuser.php" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
        <a href="forum.php" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>

    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
    <a href="signin.php"><i class="fas fa-user-plus"></i> Signin</a>
    <a href="javascript:void(0)" id="toggle-dark-mode-mobile" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
    <a href="javascript:void(0)" id="toggle-language-mobile" data-translate="language">🌐 Français</a>
    <a href="?page=gestion_utilisateurs&action=infos" data-translate="profile_info">📄 Mes informations</a>
    <a href="logout.php" data-translate="logout">🚪 Déconnexion</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <?php
    $utilisateur = $user;

    if (!isset($_GET['page']) || $_GET['page'] == 'accueil') {
        ?>
        <!-- Content from green.html -->
        <main id="home">
            <!-- Hero Section -->
            <section class="hero">
                <div class="hero-box">
                    <h1 data-translate="welcome">Bienvenue chez Green.tn</h1>
                    <p data-translate="discover">Découvrez nos vélos et nos services</p>
                    <button class="reserve" data-translate="reserve_bike" onclick="reserveBike()">Réserver un vélo</button>
                </div>
                <div class="hero-image">
                    <img src="image/hero.png" alt="Hero Bike Image">
                </div>
            </section>

            <!-- À nos vélos Section -->
            <section id="a-nos-velos">
                <h2 data-translate="bikes">Nos vélos</h2>
                <div>
                    <!-- Vélo de ville Section -->
                    <section class="bike-section" id="velo-de-ville">
                        <h3 data-translate="city_bike">Vélo de ville</h3>
                        <div class="bike-grid">
                            <div class="bike-card">
                                <img src="image/velo-de-ville.png" alt="Vélo de ville 1">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                            <div class="bike-card">
                                <img src="image/velo-de-ville.png" alt="Vélo de ville 2">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                            <div class="bike-card">
                                <img src="image/velo-de-ville.png" alt="Vélo de ville 3">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Vélo de course Section -->
                    <section class="bike-section" id="velo-de-course">
                        <h3 data-translate="road_bike">Vélo de course</h3>
                        <div class="bike-grid">
                            <div class="bike-card">
                                <img src="image/bike.png" alt="Vélo de course 1">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                            <div class="bike-card">
                                <img src="image/bike.png" alt="Vélo de course 2">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                            <div class="bike-card">
                                <img src="image/bike.png" alt="Vélo de course 3">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Vélo de montagne Section -->
                    <section class="bike-section" id="velo-de-montagne">
                        <h3 data-translate="mountain_bike">Vélo de montagne</h3>
                        <div class="bike-grid">
                            <div class="bike-card">
                                <img src="image/bike.png" alt="Vélo de montagne 1">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                            <div class="bike-card">
                                <img src="image/bike.png" alt="Vélo de montagne 2">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                            <div class="bike-card">
                                <img src="image/bike.png" alt="Vélo de montagne 3">
                                <div class="buttons">
                                    <button class="detail" data-translate="detail">Détail</button>
                                    <button class="reserve" data-translate="reserve" onclick="reserveBike()">Réserver</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <!-- À propos de nous Section -->
            <section class="hero about" id="a-propos-de-nous">
                <div class="hero-box">
                    <h1 data-translate="about_us">À propos de nous</h1>
                    <p data-translate="about_description">Découvrez qui nous sommes et notre mission</p>
                </div>
                <div class="hero-image">
                    <img src="image/hero.png" alt="About Us Bike Image">
                </div>
            </section>

            <!-- Tarifs Section -->
            <section class="pricing" id="pricing">
                <h2 data-translate="pricing_title">Tarifs</h2>
                <div class="pricing-grid">
                    <div class="pricing-card">
                        <h3 data-translate="day">Jour</h3>
                        <p class="price">30DT</p>
                        <p class="price">hot description ya zeus</p>
                    </div>
                    <div class="pricing-card">
                        <h3 data-translate="week">Semaine</h3>
                        <p class="price">160DT</p>
                        <p class="price">hot description ya zeus</p>
                    </div>
                    <div class="pricing-card">
                        <h3 data-translate="month">Mois</h3>
                        <p class="price">630DT</p>
                        <p class="price">hot description ya zeus</p>
                    </div>
                </div>
            </section>

            <!-- Contact Section -->
            <section class="contact" id="contact">
                <h2 data-translate="contact_title">Contact</h2>
                <form>
                    <div class="name-row">
                        <div class="name-field">
                            <label for="nom" data-translate="name">Nom</label>
                            <input type="text" id="nom" placeholder="Nom" data-translate-placeholder="name">
                        </div>
                        <div class="name-field">
                            <label for="prenom" data-translate="first_name">Prénom</label>
                            <input type="text" id="prenom" placeholder="Prénom" data-translate-placeholder="first_name">
                        </div>
                    </div>
                    <label for="mail" data-translate="email">Mail</label>
                    <input type="email" id="mail" placeholder="Mail" data-translate-placeholder="email">
                    <label for="telephone" data-translate="phone">Numéro téléphone</label>
                    <input type="tel" id="telephone" placeholder="Numéro téléphone" data-translate-placeholder="phone">
                    <label for="message" data-translate="message">Message</label>
                    <textarea id="message" placeholder="Message" data-translate-placeholder="message"></textarea>
                    <button type="submit" data-translate="send">Envoyer</button>
                </form>
            </section>
        </main>

        <!-- Footer from green.html -->
        <footer class="green-footer">
            <div class="footer-content">
                <div class="footer-left">
                    <div class="footer-logo">
                        <img src="image/ho.png" alt="Green.tn Logo">
                    </div>
                    <div class="social-icons">
                        <a href="https://instagram.com"><img src="image/insta.png" alt="Instagram"></a>
                        <a href="https://facebook.com"><img src="image/fb.png" alt="Facebook"></a>
                        <a href="https://twitter.com"><img src="image/x.png" alt="Twitter"></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3 data-translate="navigation">Navigation</h3>
                    <ul>
                        <li><a href="#home" data-translate="home">Home</a></li>
                        <li><a href="#a-nos-velos" data-translate="bikes">Nos vélos</a></li>
                        <li><a href="#a-propos-de-nous" data-translate="about">À propos de nous</a></li>
                        <li><a href="#pricing" data-translate="pricing">Tarifs</a></li>
                        <li><a href="#contact" data-translate="contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 data-translate="contact">Contact</h3>
                    <p>
                        <img src="image/location.png" alt="Location Icon">
                        Eprit technopole
                    </p>
                    <p>
                        <img src="image/telephone.png" alt="Phone Icon">
                        +216245678
                    </p>
                    <p>
                        <img src="image/mail.png" alt="Email Icon">
                        <a href="mailto:Green@green.com">Green@green.com</a>
                    </p>
                </div>
            </div>
        </footer>

        <?php
    } elseif (isset($_GET['action']) && $_GET['action'] === 'infos') {
        ?>
        <div class="profile-box">
            <img src="user_images/<?php echo htmlspecialchars($utilisateur['photo']); ?>" alt="Photo de profil" class="profile-pic">
            <div class="profile-details">
                <h2 class="profile-name"><?php echo htmlspecialchars($utilisateur['nom'] . ' ' . $utilisateur['prenom']); ?></h2>
                <h3 class="profile-email"><?php echo htmlspecialchars($utilisateur['email']); ?></h3>
                <div class="profile-info">
                    <div class="info-item"><strong data-translate="name">Nom:</strong> <span class="profile-nom"><?php echo htmlspecialchars($utilisateur['nom']); ?></span></div>
                    <div class="info-item"><strong data-translate="first_name">Prénom:</strong> <span class="profile-prenom"><?php echo htmlspecialchars($utilisateur['prenom']); ?></span></div>
                    <div class="info-item"><strong data-translate="email">Email:</strong> <span class="profile-email"><?php echo htmlspecialchars($utilisateur['email']); ?></span></div>
                    <div class="info-item"><strong data-translate="phone">Téléphone:</strong> <span class="profile-telephone"><?php echo htmlspecialchars($utilisateur['telephone']); ?></span></div>
                    <div class="info-item"><strong data-translate="age">Âge:</strong> <span class="profile-age"><?php echo htmlspecialchars($utilisateur['age']); ?></span></div>
                    <div class="info-item"><strong data-translate="gouvernorat">Gouvernorat:</strong> <span class="profile-gouvernorats"><?php echo htmlspecialchars($utilisateur['gouvernorats']); ?></span></div>
                    <div class="info-item"><strong data-translate="cin">CIN:</strong> <span class="profile-cin"><?php echo htmlspecialchars($utilisateur['cin']); ?></span></div>
                </div>
                <a href="?page=gestion_utilisateurs&action=edit" class="edit-profile-btn" data-translate="edit_profile">Modifier</a>
            </div>
        </div>
        <?php
    } elseif (isset($_GET['action']) && $_GET['action'] === 'edit') {
        ?>
        <div class="section-card edit-user-card">
            <h2 data-translate="edit_title">Modifier mes informations</h2>
            <form method="POST" enctype="multipart/form-data" class="edit-user-form">
                <div>
                    <label for="nom" data-translate="name">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>">
                    <?php if (isset($errors['nom'])): ?>
                        <div class="error"><?php echo $errors['nom']; ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="prenom" data-translate="first_name">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>">
                    <?php if (isset($errors['prenom'])): ?>
                        <div class="error"><?php echo $errors['prenom']; ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="email" data-translate="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="telephone" data-translate="phone">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>" pattern="[0-9]{8}" title="8 chiffres requis">
                    <?php if (isset($errors['telephone'])): ?>
                        <div class="error"><?php echo $errors['telephone']; ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="age" data-translate="age">Âge</label>
                    <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user['age']); ?>" min="5" max="80">
                    <?php if (isset($errors['age'])): ?>
                        <div class="error"><?php echo $errors['age']; ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="gouvernorats" data-translate="gouvernorat">Gouvernorat</label>
                    <select id="gouvernorats" name="gouvernorats">
                        <?php
                        $gouvernorats_list = ['Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'];
                        foreach ($gouvernorats_list as $gouv) {
                            $selected = $gouv === $user['gouvernorats'] ? 'selected' : '';
                            echo "<option value=\"$gouv\" $selected>$gouv</option>";
                        }
                        ?>
                    </select>
                    <?php if (isset($errors['gouvernorats'])): ?>
                        <div class="error"><?php echo $errors['gouvernorats']; ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="photo" data-translate="photo">Photo de profil</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                    <?php if (isset($errors['photo'])): ?>
                        <div class="error"><?php echo $errors['photo']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="btn-container">
                    <button type="submit" class="btn submit" data-translate="update">Mettre à jour</button>
                    <a href="?page=gestion_utilisateurs&action=infos" class="btn cancel" data-translate="cancel">Annuler</a>
                </div>
            </form>
        </div>
        <?php
    }
    ?>
</div>

<!-- Footer (Dashboard, shown only when not in accueil) -->
<?php if (isset($_GET['page']) && $_GET['page'] != 'accueil'): ?>
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
<?php endif; ?>

<script>
    // Function to handle reservation redirect
    function reserveBike() {
        console.log('Bouton Réserver cliqué, redirection vers reservationuser.php');
        window.location.href = 'reservationuser.php';
    }

    // Topbar Show/Hide Toggle
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

    // Scroll-based Topbar Hide/Show
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
        let currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        const topbar = document.querySelector('.topbar');
        const showBtn = document.querySelector('.show-topbar-btn');
        
        if (currentScroll > lastScrollTop && currentScroll > 100) {
            // Scroll down
            topbar.classList.add('hidden');
            showBtn.classList.add('show');
            isTopbarVisible = false;
        } else if (currentScroll < lastScrollTop) {
            // Scroll up
            topbar.classList.remove('hidden');
            showBtn.classList.remove('show');
            isTopbarVisible = true;
        }
        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    });

    // Profile Menu Toggle
    document.querySelector('.top-profile-pic').addEventListener('click', function(event) {
        event.stopPropagation();
        document.querySelector('.profile-menu').classList.toggle('show');
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('.profile-icon')) {
            document.querySelector('.profile-menu').classList.remove('show');
        }
    });

    // Hamburger Menu Toggle
    document.querySelector('.hamburger-menu').addEventListener('click', function() {
        document.querySelector('.nav-menu').classList.toggle('show');
        document.querySelector('.hamburger-icon').classList.toggle('active');
    });

    // Dark Mode Toggle
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }

    document.getElementById('toggle-dark-mode').addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        if (document.body.classList.contains('dark-mode')) {
            localStorage.setItem('darkMode', 'enabled');
        } else {
            localStorage.setItem('darkMode', 'disabled');
        }
    });

    document.getElementById('toggle-dark-mode-mobile').addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        if (document.body.classList.contains('dark-mode')) {
            localStorage.setItem('darkMode', 'enabled');
        } else {
            localStorage.setItem('darkMode', 'disabled');
        }
    });

    // Translation functionality
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
            title: "Green.tn - Location de Vélos",
            welcome: "Bienvenue chez Green.tn",
            discover: "Découvrez nos vélos et nos services",
            about_us: "À propos de nous",
            about_description: "Découvrez qui nous sommes et notre mission",
            pricing_title: "Tarifs",
            day: "Jour",
            week: "Semaine",
            month: "Mois",
            contact_title: "Contact",
            name: "Nom",
            first_name: "Prénom",
            email: "Email",
            phone: "Téléphone",
            message: "Message",
            send: "Envoyer",
            city_bike: "Vélo de ville",
            road_bike: "Vélo de course",
            mountain_bike: "Vélo de montagne",
            detail: "Détail",
            reserve: "Réserver",
            reserve_bike: "Réserver un vélo",
            navigation: "Navigation",
            footer_about: "À propos",
            footer_about_text: "Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.",
            footer_contact: "Contact",
            footer_copyright: "© 2025 Green.tn – Tous droits réservés",
            edit_title: "Modifier mes informations",
            age: "Âge",
            gouvernorat: "Gouvernorat",
            cin: "CIN",
            photo: "Photo de profil",
            update: "Mettre à jour",
            cancel: "Annuler",
            edit_profile: "Modifier",
            forum: 'Forum',
            error_name_short: "Le nom doit contenir au moins 2 caractères",
            error_first_name_short: "Le prénom doit contenir au moins 2 caractères",
            error_email_invalid: "L'email n'est pas valide",
            error_phone_invalid: "Le numéro de téléphone doit contenir 8 chiffres",
            error_age_invalid: "L'âge doit être compris entre 5 et 80 ans",
            error_gouvernorat_invalid: "Gouvernorat invalide",
            error_email_taken: "Cet email est déjà utilisé",
            error_photo_upload: "Erreur lors du téléchargement de la photo",
            error_photo_type: "Type de fichier non autorisé (JPEG, PNG, GIF uniquement)"
        },
        en: {
            home: "Home",
            bikes: "Bikes",
            forum: "Forum",
            about: "About Us",
            pricing: "Pricing",
            contact: "Contact",
            reservations: "Reservations",
            dark_mode: "Dark Mode",
            language: "English",
            profile_info: "My Information",
            logout: "Logout",
            title: "Green.tn - Bike Rental",
            welcome: "Welcome to Green.tn",
            discover: "Discover our bikes and services",
            about_us: "About Us",
            about_description: "Learn who we are and our mission",
            pricing_title: "Pricing",
            day: "Day",
            week: "Week",
            month: "Month",
            contact_title: "Contact",
            name: "Name",
            first_name: "First Name",
            email: "Email",
            phone: "Phone",
            message: "Message",
            send: "Send",
            city_bike: "City Bike",
            road_bike: "Road Bike",
            mountain_bike: "Mountain Bike",
            detail: "Detail",
            reserve: "Reserve",
            reserve_bike: "Reserve a Bike",
            navigation: "Navigation",
            footer_about: "About",
            footer_about_text: "Green.tn promotes sustainable mobility through eco-friendly bike rental solutions in Tunisia.",
            footer_contact: "Contact",
            footer_copyright: "© 2025 Green.tn – All rights reserved",
            edit_title: "Edit My Information",
            age: "Age",
            gouvernorat: "Governorate",
            cin: "ID Card",
            photo: "Profile Photo",
            update: "Update",
            cancel: "Cancel",
            edit_profile: "Edit Profile",
            error_name_short: "Name must be at least 2 characters long",
            error_first_name_short: "First name must be at least 2 characters long",
            error_email_invalid: "Invalid email address",
            error_phone_invalid: "Phone number must be 8 digits",
            error_age_invalid: "Age must be between 5 and 80",
            error_gouvernorat_invalid: "Invalid governorate",
            error_email_taken: "This email is already taken",
            error_photo_upload: "Error uploading photo",
            error_photo_type: "Invalid file type (JPEG, PNG, GIF only)"
        }
    };

    fetch('/projet/assets/translations.json')
        .then(response => response.json())
        .then(data => {
            translations = { ...translations, ...data }; // Merge JSON translations with defaults
            applyTranslations(currentLanguage);
        })
        .then(() => {
            // Ensure translations don't interfere with onclick
            document.querySelectorAll('.reserve').forEach(button => {
                button.onclick = reserveBike;
            });
        })
        .catch(error => {
            console.error('Error loading translations:', error);
            applyTranslations(currentLanguage); // Use fallback translations
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

        // Update language button text
        const langButton = document.getElementById('toggle-language');
        const langButtonMobile = document.getElementById('toggle-language-mobile');
        if (langButton) {
            langButton.textContent = `🌐 ${translations[lang]['language']}`;
        }
        if (langButtonMobile) {
            langButtonMobile.textContent = `🌐 ${translations[lang]['language']}`;
        }

        // Update page title
        document.title = translations[lang]['title'] || 'Green.tn';

        localStorage.setItem('language', lang);
    }

    // Toggle language
    function toggleLanguage() {
        currentLanguage = currentLanguage === 'fr' ? 'en' : 'fr';
        applyTranslations(currentLanguage);
        // Update URL to reflect language change
        window.location.href = `?lang=${currentLanguage}${window.location.search.replace(/lang=[a-z]{2}/, '')}`;
    }

    document.getElementById('toggle-language')?.addEventListener('click', toggleLanguage);
    document.getElementById('toggle-language-mobile')?.addEventListener('click', toggleLanguage);

    // Apply translations on page load
    applyTranslations(currentLanguage);
</script>
</body>
</html>