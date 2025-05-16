<?php
// Inclure db.php pour la connexion à la base de données
require_once __DIR__ . '/../../CONFIG/db.php';

// Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];
    $role = 'admin';  // Définir l'utilisateur comme admin

    // Hacher le mot de passe avant de l'insérer
    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    // Préparer la requête SQL pour insérer l'utilisateur
    $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $email, $hashed_password, $role]);

    echo "Admin ajouté avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Admin</title>
</head>
<body>
    <h2>Ajouter un administrateur</h2>
    <form method="POST" action="add_admin.php">
        <label for="nom">Nom</label>
        <input type="text" name="nom" required><br><br>

        <label for="prenom">Prénom</label>
        <input type="text" name="prenom" required><br><br>

        <label for="email">Email</label>
        <input type="email" name="email" required><br><br>

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" name="mot_de_passe" required><br><br>

        <button type="submit">Ajouter Admin</button>
    </form>
</body>
</html>
