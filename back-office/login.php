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
        
        // Vérification du mot de passe haché
        if ($password = $user['password']) {
            $_SESSION['role'] = 'client';
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_name'] = $user['nom'];
            header("Location: front-office/dashboard.php");
            exit;
        } else {
            echo "Identifiants incorrects.";
        }
    }

    // Vérifier l'admin
    $query = "SELECT * FROM admin WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $email);
    $stmt->execute();
    echo $email;
    echo $password;
    if ($stmt->rowCount() > 0) {
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "inside admin";
        echo $admin['password'];
        // Vérification du mot de passe haché pour l'admin
        if ($password == $admin['password']) {
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_id'] = $admin['id_admin'];  // Assurez-vous que vous avez un champ id_admin
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Identifiants admin incorrects.";
        }
    }

    echo "Identifiants incorrects last.";
}

if (file_exists('connextion.php')) {
    echo "Fichier trouvé.";
} else {
    echo "Fichier non trouvé.";
}
?>
