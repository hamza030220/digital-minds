<?php
// Connexion à la base de données
include 'connexion.php';

// Vérifier si l'ID de la réclamation est passé en paramètre
if (isset($_GET['id'])) {
    $reclamation_id = $_GET['id'];

    // Récupérer les informations de la réclamation
    $stmt = $pdo->prepare("SELECT * FROM reclamations WHERE id = ?");
    $stmt->execute([$reclamation_id]);
    $reclamation = $stmt->fetch();

    // Vérifier si la réclamation existe
    if (!$reclamation) {
        echo "Réclamation non trouvée.";
        exit;
    }

    // Si le formulaire de modification est soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titre = $_POST['titre'];
        $description = $_POST['description'];
        $lieu = $_POST['lieu'];
        $type_probleme = $_POST['type_probleme'];

        // Mettre à jour les informations de la réclamation dans la base de données
        $stmt = $pdo->prepare("UPDATE reclamations SET titre = ?, description = ?, lieu = ?, type_probleme = ? WHERE id = ?");
        $stmt->execute([$titre, $description, $lieu, $type_probleme, $reclamation_id]);

        echo "<p>Réclamation modifiée avec succès !</p>";
    }
} else {
    echo "ID de réclamation manquant.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la réclamation - Green.tn</title>
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
                <li><a href="liste_reclamations.php">Liste des réclamations</a></li>
                <li><a href="#">Mon profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <!-- Section principale -->
    <main>
        <h2>Modifier la réclamation</h2>

        <form action="modifier_reclamation.php?id=<?php echo $reclamation['id']; ?>" method="POST">
            <label for="titre">Titre :</label>
            <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($reclamation['titre']); ?>" required><br><br>

            <label for="description">Description :</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($reclamation['description']); ?></textarea><br><br>

            <label for="lieu">Lieu :</label>
            <input type="text" id="lieu" name="lieu" value="<?php echo htmlspecialchars($reclamation['lieu']); ?>" required><br><br>

            <label for="type_probleme">Type de problème :</label>
            <select id="type_probleme" name="type_probleme" required>
                <option value="mecanique" <?php if ($reclamation['type_probleme'] == 'mecanique') echo 'selected'; ?>>Mécanique</option>
                <option value="batterie" <?php if ($reclamation['type_probleme'] == 'batterie') echo 'selected'; ?>>Batterie</option>
                <option value="ecran" <?php if ($reclamation['type_probleme'] == 'ecran') echo 'selected'; ?>>Écran</option>
                <option value="pneu" <?php if ($reclamation['type_probleme'] == 'pneu') echo 'selected'; ?>>Pneu</option>
            </select><br><br>

            <button type="submit">Mettre à jour la réclamation</button>
        </form>
    </main>

    <!-- Pied de page -->
    <footer>
        <p>&copy; 2025 Green.tn - Tous droits réservés.</p>
    </footer>

</body>
</html>
