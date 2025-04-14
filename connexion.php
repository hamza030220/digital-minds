<?php
$host = 'localhost';
$dbname = 'green_tn';
$username = 'root';
$password = ''; // par défaut, XAMPP ne met pas de mot de passe pour root

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connexion réussie !";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    die();
}
?>
