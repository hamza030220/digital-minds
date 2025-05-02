<?php
// Inclure db.php pour la connexion à la base de données
require_once __DIR__ . '/models/db.php';

// Vérifier si la requête est une insertion pour admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Données de l'administrateur
    $nom = 'Admin';  // Nom de l'admin
    $prenom = 'Admin';  // Prénom de l'admin
    $email = 'admin@example.com';  // Email de l'admin
    $mot_de_passe = 'adminpassword';  // Mot de passe à hacher
    $role = 'admin';  // Le rôle est administrateur

    // Hacher le mot de passe avant de l'insérer dans la base de données
    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    // Préparer la requête SQL pour insérer l'utilisateur avec le rôle admin
    try {
        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $hashed_password, $role]);
        echo "L'administrateur a été ajouté avec succès.";
    } catch (PDOException $e) {
        echo "Erreur lors de l'ajout de l'administrateur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insertion Administrateur</title>
</head>
<body>
    <h2>Administrateur ajouté avec succès</h2>
    <p>L'administrateur a été ajouté avec le rôle "admin". Tu peux maintenant te connecter avec l'email "admin@example.com" et le mot de passe "adminpassword".</p>
</body>
</html>
