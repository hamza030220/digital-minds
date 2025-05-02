<?php
require_once __DIR__ . '/../models/User.php';
session_start();

class LoginController {
    public static function handleRequest() {
        // Redirection si déjà connecté
        if (isset($_SESSION['user_id'])) {
            $role = strtolower(trim($_SESSION['role']));
            header("Location: " . ($role === 'admin' ? "dashboard.php" : "info2.php"));
            exit();
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];

            if (empty($email)) {
                $error = "Veuillez entrer un email.";
            } else {
                $user = User::findByEmail($email);

                // ⚠️ À sécuriser : mot de passe en clair (à améliorer avec password_hash + password_verify)
                if ($user && $password === $user['mot_de_passe']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = strtolower(trim($user['role']));
                    session_regenerate_id(true);

                    header("Location: " . ($_SESSION['role'] === 'admin' ? "dashboard.php" : "info2.php"));
                    exit();
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            }
        }

        // Charger la vue avec $error
        include __DIR__ . '/../views/login_view.php';
    }
}
