<?php
// Connexion à la base de données
include 'connexion.php';

// Vérification si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $lieu = $_POST['lieu'];
    $type_probleme = $_POST['type_probleme'];
    $utilisateur_id = 1; // À remplacer par l'ID utilisateur connecté
    $date_creation = date('Y-m-d H:i:s');
    
    // Préparer et exécuter la requête pour insérer la réclamation
    $stmt = $pdo->prepare("INSERT INTO reclamations (titre, description, lieu, type_probleme, utilisateur_id, date_creation, statut) 
                           VALUES (?, ?, ?, ?, ?, ?, 'ouverte')");
    $stmt->execute([$titre, $description, $lieu, $type_probleme, $utilisateur_id, $date_creation]);

    // Message de succès
    echo "<p>Réclamation ajoutée avec succès!</p>";
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
                <li><a href="liste_reclamations.php">Voir réclamations</a></li>
                <li><a href="#">Mon profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <!-- Section principale -->
    <main>
        <h2>Nouvelle réclamation</h2>

        <form action="ajouter_reclamation.php" method="POST">
            <label for="titre">Titre de la réclamation:</label>
            <input type="text" id="titre" name="titre" required><br><br>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea><br><br>

            <label for="lieu">Lieu du problème:</label>
            <input type="text" id="lieu" name="lieu" required><br><br>

            <label for="type_probleme">Type de problème:</label>
            <select id="type_probleme" name="type_probleme" required>
                <option value="mecanique">Problème mécanique</option>
                <option value="batterie">Problème de batterie</option>
                <option value="ecran">Problème d'écran</option>
                <option value="pneu">Problème de pneu</option>
                <option value="autre">Autre</option>
            </select><br><br>

            <button type="submit">Ajouter la réclamation</button>
        </form>
    </main>

    <!-- Pied de page -->
    <footer>
        <p>&copy; 2025 Green.tn - Tous droits réservés.</p>
    </footer>

</body>
</html>
