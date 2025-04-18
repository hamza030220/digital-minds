<?php
$servername = "127.0.0.1";
$username = "root";  // Par défaut sur XAMPP
$password = "";      // Par défaut sur XAMPP
$dbname = "velo_reservation";  // Le nom de votre base de données

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données: " . $e->getMessage();
}
?>
