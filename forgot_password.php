<?php
session_start();
require 'db.php';

// Inclure manuellement les fichiers PHPMailer
require 'path/to/PHPMailer/src/PHPMailer.php';  // Remplace avec ton chemin
require 'path/to/PHPMailer/src/SMTP.php';       // Remplace avec ton chemin
require 'path/to/PHPMailer/src/Exception.php';  // Remplace avec ton chemin

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Générer un token aléatoire
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Enregistrer le token et son expiration dans la base de données
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $stmt->execute([$token, $expiry, $email]);

        // Envoi de l'email de réinitialisation
        $mail = new PHPMailer(true);

        try {
            // Paramètres Mailtrap
            $mail->isSMTP();
            $mail->Host = 'smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = 'ton-username';  // Remplace avec ton identifiant Mailtrap
            $mail->Password = 'ton-password';  // Remplace avec ton mot de passe Mailtrap
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Destinataire
            $mail->setFrom('ton-email@example.com', 'Application');
            $mail->addAddress($email, $user['prenom'] . ' ' . $user['nom']); // Destinataire de l'email

            // Contenu de l'email
            $mail->isHTML(true);
            $mail->Subject = 'Réinitialisation de votre mot de passe';
            $mail->Body    = 'Bonjour, <br> Pour réinitialiser votre mot de passe, veuillez <a href="http://localhost/projet/reset_password.php?token=' . $token . '">cliquer ici</a>.';

            $mail->send();
            $_SESSION['message'] = "Un email de réinitialisation a été envoyé.";
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "L'email n'a pas pu être envoyé. Erreur: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error'] = "Aucun utilisateur trouvé avec cet email.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
</head>
<body>
    <h2>Mot de passe oublié</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <button type="submit">Envoyer le lien de réinitialisation</button>
    </form>
</body>
</html>
