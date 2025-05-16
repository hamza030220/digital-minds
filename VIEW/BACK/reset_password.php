<?php
require_once __DIR__ . '/../../CONFIG/db.php';
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = '';

if (!$token) {
    $error = "Lien invalide ou expiré.";
} else {
    // Verify token
    $stmt = $pdo->prepare("SELECT * FROM password_reset WHERE token = ? AND expire > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = "Lien invalide ou expiré.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        if ($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($password) < 8) {
            $error = "Le mot de passe doit contenir au moins 8 caractères.";
        } else {
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET mot_de_passe = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $reset['email']]);

            // Delete used token
            $stmt = $pdo->prepare("DELETE FROM password_reset WHERE token = ?");
            $stmt->execute([$token]);

            $success = "Votre mot de passe a été réinitialisé avec succès. <a href='login.php'>Connectez-vous</a>.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - Location de Vélos</title>
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

        input[type="password"] {
            width: 100%;
            padding: 14px 14px 14px 40px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
        }

        input[type="password"]:focus {
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

        body.dark-mode input[type="password"] {
            background-color: #333;
            border: 1px solid #555;
            color: white;
        }

        body.dark-mode input[type="password"]:focus {
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
        <h2>Réinitialiser le mot de passe</h2>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!$error && !$success): ?>
            <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Nouveau mot de passe" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
                </div>
                <button type="submit">Réinitialiser le mot de passe</button>
            </form>
        <?php endif; ?>
        <p><a href="login.php">Retour à la connexion</a></p>
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