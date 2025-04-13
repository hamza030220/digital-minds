<?php
// Inclure db.php pour la connexion à la base de données
require 'db.php';
session_start();

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    header("Location: info2.php");
    exit();
}

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assainir les entrées utilisateur
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL); // Assainir l'email
    $password = $_POST['password'];

    // Vérifier si l'email est vide ou non
    if (empty($email)) {
        $error = "Veuillez entrer un email.";
    } else {
        // Vérifier les identifiants dans la base de données
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // L'utilisateur est authentifié, enregistrer l'ID et le rôle en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Sécuriser la session pour éviter les attaques de fixation de session
            session_regenerate_id(true);

            // Rediriger vers le tableau de bord en fonction du rôle
            header("Location: info2.php");
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se connecter</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header-container">
    <!-- Logo centré -->
    <div class="logo-container">
        <img src="logo.jpg" alt="Logo" class="logo" width="250px">
    </div>
</header>

    <div class="container">
        <h2>Se connecter</h2>

        <!-- Afficher les messages d'erreur -->
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

        <!-- Formulaire de connexion stylisé -->
        <div class="registration-frame">
            <form method="POST" action="login.php">
                <table class="registration-table">
                    <tr>
                        <td><label for="email">Email</label></td>
                        <td><input type="email" name="email" placeholder="Email" required></td>
                    </tr>
                    <tr>
                        <td><label for="password">Mot de passe</label></td>
                        <td><input type="password" name="password" placeholder="Mot de passe" required></td>
                    </tr>
                </table>
                <button type="submit">Se connecter</button>
            </form>
        </div>

        <!-- Lien pour récupérer son mot de passe -->
        <p><a href="forgot_password.php">Mot de passe oublié ?</a></p>

        <p>Pas encore inscrit ? <a href="sign_up.php">S'inscrire</a></p>
    </div>
</body>
</html>
