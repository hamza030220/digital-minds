<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'eco_pedal');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// Paramètres généraux
define('BASE_URL', 'http://localhost/eco_pedal/');
define('UPLOAD_DIR', 'uploads/');
define('PHOTO_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Connexion à la base de données
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
?>
