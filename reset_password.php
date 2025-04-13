<?php
session_start();
require 'db.php';

// Inclure manuellement les fichiers PHPMailer
require 'path/to/PHPMailer/src/PHPMailer.php';  // Remplace avec ton chemin vers PHPMailer
require 'path/to/PHPMailer/src/SMTP.php';       // Remplace avec ton chemin vers PHPMailer
require 'path/to/PHPMailer/src/Exception.php';  // Remplace avec ton chemin vers PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Vérifier si le token existe dans la base de données et s'il est encore valide
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // L'utilisateur a le droit de réinitialiser son mot de passe
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $confirm_password = $_POST['confirm_password'];

            // Vérifier que les mots de passe correspondent
            if ($new_password === password_hash($confirm_password, PASSWORD_DEFAULT)) {
                // Mettre à jour le mot de passe dans la base de données
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
                $stmt->execute([$new_password, $token]);

                $_SESSION['message'] = "Votre mot de passe a été réinitialisé avec succès.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            }
        }
    } else {
        $_SESSION['error'] = "Ce lien de réinitialisation est invalide ou a expiré.";
    }
} else {
    $_SESSION['error'] = "Aucun token de réinitialisation trouvé.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe</title>
</head>
<body>
    <h2>Réinitialiser votre mot de passe</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="password">Nouveau mot de passe</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirmer le mot de passe</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">Réinitialiser le mot de passe</button>
    </form>
</body>
</html>
