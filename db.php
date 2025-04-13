<?php
$host = 'localhost';  // Host de la base de données
$dbname = 'greentn'; // Nom de votre base de données
$username = 'root';  // Nom d'utilisateur de la base de données
$password = '';  // Mot de passe

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
