<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';

$erreur = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];

    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();

    // Création d'une instance de User
    $user = new User($db);
    $data = $user->findByEmail($email);

    // Vérification des identifiants
    if ($data && $mot_de_passe === $user->mot_de_passe) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_nom'] = $user->nom;
        $_SESSION['user_role'] = $user->role;

        if ($user->role === 'admin') {
            header("Location: ../views/reclamations_utilisateur.php");
        } else {
            header("Location: ../views/liste_reclamations.php");
        }
        exit();
    } else {
        $erreur = "Identifiants incorrects.";
        include('../login.php');
    }
} else {
    header("Location: ../login.php");
    exit();
}
