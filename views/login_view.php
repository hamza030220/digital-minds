<?php
require_once __DIR__ . '/../models/User.php';
session_start();

class LoginController {
    public static function handleRequest() {
        // ✅ Redirection si l'utilisateur est déjà connecté
        if (isset($_SESSION['user_id'])) {
            $role = strtolower(trim($_SESSION['role']));
            header("Location: " . ($role === 'admin' ? "dashboard.php" : "info2.php"));
            exit();
        }

        $error = null;

        // ✅ Si le formulaire est soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];

            if (empty($email) || empty($password)) {
                $error = "Veuillez entrer l'email et le mot de passe.";
            } else {
                $user = User::findByEmail($email);

                // ✅ Vérifie si l'utilisateur existe et si le mot de passe est correct (haché)
                if ($user && User::verifyPassword($password, $user['mot_de_passe'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = strtolower(trim($user['role']));
                    session_regenerate_id(true);

                    // ✅ Redirection selon le rôle
                    header("Location: " . ($_SESSION['role'] === 'admin' ? "dashboard.php" : "info2.php"));
                    exit();
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            }
        }

        // ✅ Affichage de la vue (avec $error si nécessaire)
        include __DIR__ . '/../views/login_view.php';
    }
}
