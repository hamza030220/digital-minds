<?php
$host = 'localhost';  // Host de la base de données
$dbname = 'projet'; // Nom de votre base de données
$username = 'root';  // Nom d'utilisateur de la base de données
$password = '';  // Mot de passe

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
//----------------------------------------------------------------------------

function getDBConnection() {
    global $host, $dbname, $username, $password;
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        die("Connection failed. Please try again later.");
    }
}
?>
