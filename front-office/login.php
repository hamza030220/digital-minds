<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Vérifier d'abord l'utilisateur (client)
    $query = "SELECT * FROM utilisateur WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
         echo "Found one.";
        // Vérification du mot de passe haché
        if ($password = $user['password']) {
            $_SESSION['role'] = 'client';
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_name'] = $user['nom'];
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Identifiants incorrects.";
        }
    }

    echo "Identifiants incorrects.";
}

if (file_exists('connextion.php')) {
    echo "Fichier trouvé.";
} else {
    echo "Fichier non trouvé.";
}
?>
