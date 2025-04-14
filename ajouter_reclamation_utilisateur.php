<?php
// Connexion à la base de données
include 'connexion.php';

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $lieu = $_POST['lieu'];
    $type_probleme = $_POST['type_probleme'];
    $utilisateur_id = 1; // ID de l'utilisateur (à adapter avec un vrai mécanisme d'authentification)

    // Insérer la réclamation dans la base de données
    $stmt = $pdo->prepare("INSERT INTO reclamations (titre, description, lieu, type_probleme, utilisateur_id, statut) 
                           VALUES (?, ?, ?, ?, ?, 'ouverte')");
    $stmt->execute([$titre, $description, $lieu, $type_probleme, $utilisateur_id]);

    echo "<p>Réclamation ajoutée avec succès !</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une réclamation - Green.tn</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- En-tête du site -->
    <header>
        <div class="logo">
            <h1>Green.tn</h1>
            <p>Mobilité durable, énergie propre</p>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="liste_reclamations_utilisateur.php">Mes réclamations</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <!-- Section principale -->
    <main>
        <h2>Ajouter une nouvelle réclamation</h2>

        <form action="ajouter_reclamation_utilisateur.php" method="POST">
            <label for="titre">Titre :</label>
            <input type="text" id="titre" name="titre" required><br><br>

            <label for="description">Description :</label>
            <textarea id="description" name="description" required></textarea><br><br>

            <label for="lieu">Lieu :</label>
            <input type="text" id="lieu" name="lieu" required><br><br>

            <label for="type_probleme">Type de problème :</label>
            <select id="type_probleme" name="type_probleme" required>
                <option value="mecanique">Mécanique</option>
                <option value="batterie">Batterie</option>
                <option value="ecran">Écran</option>
                <option value="pneu">Pneu</option>
            </select><br><br>

            <button type="submit">Soumettre la réclamation</button>
        </form>
    </main>

    <!-- Pied de page -->
    <footer>
        <p>&copy; 2025 Green.tn - Tous droits réservés.</p>
    </footer>

</body>
</html>


