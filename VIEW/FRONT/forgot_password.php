<?php
require_once __DIR__ . '/../../CONFIG/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if OpenSSL is enabled
if (!extension_loaded('openssl')) {
    die('Erreur : L\'extension OpenSSL n\'est pas activée. Activez-la dans php.ini.');
}

// Generate CAPTCHA if not set
if (!isset($_SESSION['captcha_answer'])) {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_question'] = "Combien font $num1 + $num2 ?";
    $_SESSION['captcha_answer'] = $num1 + $num2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $captcha_input = trim($_POST['captcha']);

    // Verify CAPTCHA
    if (!is_numeric($captcha_input) || (int)$captcha_input !== $_SESSION['captcha_answer']) {
        $error = "Réponse CAPTCHA incorrecte.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } else {
        // Generate reset token using openssl_random_pseudo_bytes
        $token = bin2hex(openssl_random_pseudo_bytes(32));
        $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in password_reset
        $stmt = $pdo->prepare("
            INSERT INTO password_reset (email, token, expire)
            VALUES (?, ?, ?)
        ");
        $stmt->execute(array($email, $token, $expire));

        // Send reset email
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'emnajouinii2000@gmail.com';
            $mail->Password = 'ggzhgbhgmaqzsnjc'; // Remplacez par le NOUVEAU mot de passe d'application
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('emnajouinii2000@gmail.com', 'Location de Vélos');
            $mail->addAddress($email);
            $mail->addReplyTo('emnajouinii2000@gmail.com', 'Location de Vélos');

            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Réinitialisation de votre mot de passe';
            $mail->Body = "
            <!DOCTYPE html>
            <html lang='fr'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body { font-family: 'Montserrat', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #2ecc71, #27ae60); padding: 20px; text-align: center; }
                    .header img { max-width: 150px; }
                    .content { padding: 30px; }
                    h1 { color: #2c3e50; font-size: 24px; margin-bottom: 20px; }
                    p { color: #555; font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
                    .button { display: inline-block; padding: 12px 24px; background-color: #27ae60; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; }
                    .button:hover { background-color: #219150; }
                    .footer { background: #2c3e50; color: #ffffff; text-align: center; padding: 15px; font-size: 14px; }
                    @media screen and (max-width: 600px) {
                        .container { margin: 10px; }
                        .content { padding: 20px; }
                        h1 { font-size: 20px; }
                        p { font-size: 14px; }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='http://localhost/projet/logo.jpg' alt='Logo'>
                    </div>
                    <div class='content'>
                        <h1>Réinitialisation de votre mot de passe</h1>
                        <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                        <p style='text-align: center;'>
                            <a href='http://localhost/projet/reset_password.php?token=$token' class='button'>Réinitialiser mon mot de passe</a>
                        </p>
                        <p>Ce lien expirera dans 1 heure. Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                    </div>
                    <div class='footer'>
                        <p>© " . date('Y') . " Location de Vélos. Tous droits réservés.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->send();
            $success = "Un lien de réinitialisation a été envoyé à votre adresse email.";
        } catch (Exception $e) {
            error_log("Échec de l'envoi de l'email de réinitialisation à $email: " . $mail->ErrorInfo);
            $error = "Erreur lors de l'envoi de l'email. Veuillez réessayer plus tard.";
        }
    }

    // Regenerate CAPTCHA
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_question'] = "Combien font $num1 + $num2 ?";
    $_SESSION['captcha_answer'] = $num1 + $num2;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Location de Vélos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f5e9, #4caf50, #2e7d32);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            width: 90%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(6px);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .success-message {
            background: #e7f3e7;
            color: #2e7d32;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        input[type="email"],
        input[type="number"] {
            width: 100%;
            padding: 14px 14px 14px 40px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
        }

        input[type="email"]:focus,
        input[type="number"]:focus {
            border-color: #4caf50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
        }

        .form-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #4caf50;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: #2ecc71;
            border: none;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background-color: #27ae60;
        }

        p {
            text-align: center;
            margin-top: 20px;
        }

        a {
            color: #2980b9;
            text-decoration: none;
        }

        .theme-toggle-wrapper {
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .theme-toggle {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            line-height: 48px;
            text-align: center;
            font-size: 1.4rem;
            color: #2c3e50;
            cursor: pointer;
        }

        .theme-toggle:hover {
            background: #4caf50;
            color: #fff;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #1a3c34, #2e7d32);
        }

        body.dark-mode .container {
            background: rgba(30, 30, 30, 0.85);
            color: white;
        }

        body.dark-mode h2 {
            color: white;
        }

        body.dark-mode input[type="email"],
        body.dark-mode input[type="number"] {
            background-color: #333;
            border: 1px solid #555;
            color: white;
        }

        body.dark-mode input[type="email"]:focus,
        body.dark-mode input[type="number"]:focus {
            border-color: #4caf50;
        }

        body.dark-mode a {
            color: #1abc9c;
        }

        body.dark-mode .theme-toggle {
            background: #2e7d32;
            border: 1px solid #4caf50;
            color: #fff;
        }

        body.dark-mode .error-message {
            background: #4b1c1c;
            color: #f87171;
        }

        body.dark-mode .success-message {
            background: #1a3c34;
            color: #4caf50;
        }

        .captcha-container {
            margin-bottom: 25px;
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
        }

        .captcha-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .captcha-title::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #4caf50;
            margin-right: 8px;
        }

        .captcha-question {
            font-size: 1.1rem;
            font-weight: 500;
            color: #2c3e50;
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #d0d0d0;
        }

        .captcha-input-wrapper {
            position: relative;
        }

        .captcha-input-wrapper::before {
            content: '\f1ec';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #4caf50;
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
        }

        body.dark-mode .captcha-container {
            background: rgba(50, 50, 50, 0.9);
            border: 1px solid #555;
        }

        body.dark-mode .captcha-title,
        body.dark-mode .captcha-question {
            color: #ffffff;
            background: linear-gradient(135deg, #444, #333);
            border: 1px solid #666;
        }

        @media screen and (max-width: 600px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            .theme-toggle-wrapper {
                top: 10px;
                left: 10px;
            }

            .theme-toggle {
                width: 40px;
                height: 40px;
                line-height: 40px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="theme-toggle-wrapper">
        <button class="theme-toggle"><i class="fas fa-moon"></i></button>
    </div>
    <div class="container">
        <h2>Mot de passe oublié</h2>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Adresse e-mail" required>
            </div>
            <div class="captcha-container">
                <div class="captcha-title">Vérifiez que vous n'êtes pas un robot</div>
                <div class="captcha-question"><?php echo isset($_SESSION['captcha_question']) ? htmlspecialchars($_SESSION['captcha_question']) : 'Chargement...'; ?></div>
                <div class="captcha-input-wrapper">
                    <input type="number" name="captcha" placeholder="Entrez la réponse" required>
                </div>
            </div>
            <button type="submit">Envoyer le lien de réinitialisation</button>
        </form>
        <p><a href="login.php">Retour à la connexion</a></p>
        <p>Pas encore inscrit ? <a href="sign_up.php">Créer un compte</a></p>
    </div>

    <script>
        document.querySelector(".theme-toggle").addEventListener("click", function() {
            var isDark = document.body.classList.toggle("dark-mode");
            localStorage.setItem("theme", isDark ? "dark" : "light");
            var icon = document.querySelector(".theme-toggle i");
            if (isDark) {
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
            } else {
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
            }
        });

        window.addEventListener("DOMContentLoaded", function() {
            var theme = localStorage.getItem("theme");
            var icon = document.querySelector(".theme-toggle i");
            if (theme === "dark") {
                document.body.classList.add("dark-mode");
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
            } else {
                document.body.classList.remove("dark-mode");
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
            }
        });
    </script>
</body>
</html>