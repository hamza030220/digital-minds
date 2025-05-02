<?php
// Démarrer la session
session_start();

// Détruire toutes les variables de session
session_unset();

// Détruire la session
session_destroy();

// Rediriger vers la page d'inscription
header("Location: login.php");  // Redirige l'utilisateur vers la page d'inscription
exit();
?>
