<?php
// Connexion à la base de données
require 'db.php';
require_once('C:/xampp/htdocs/projet/db.php');
session_start();

// Vérifier si l'utilisateur est connecté et si c'est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Vérifier que l'ID est passé dans l'URL et qu'il est un entier valide
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = (int) $_GET['id'];

    // Journalisation pour le débogage
    error_log("Tentative de suppression de l'utilisateur avec l'ID : $id");

    // Vérifier si l'utilisateur existe dans la base
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user) {
        error_log("Utilisateur trouvé : " . print_r($user, true));

        // Supprimer l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        // Message de succès
        $_SESSION['success_message'] = "Utilisateur supprimé avec succès!";

        // Redirection vers le tableau de bord
        header("Location: dashboard.php");
        exit();
    } else {
        // Utilisateur introuvable
        error_log("Utilisateur introuvable pour l'ID : $id");
        $_SESSION['error_message'] = "Utilisateur introuvable.";
        header("Location: dashboard.php");
        exit();
    }
} else {
    // ID non valide
    $_SESSION['error_message'] = "ID invalide.";
    header("Location: dashboard.php");
    exit();
}
?>
