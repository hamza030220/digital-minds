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

    // Si une réponse est soumise
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reponse = $_POST['reponse'];
        $date_reponse = date('Y-m-d H:i:s');
        $utilisateur_id = 1; // L'ID de l'administrateur (à remplacer avec l'ID réel de l'administrateur)

        // Insérer la réponse dans la base de données
        $stmt = $pdo->prepare("INSERT INTO reponses (reclamation_id, contenu, utilisateur_id, date_reponse) 
                               VALUES (?, ?, ?, ?)");
        $stmt->execute([$reclamation_id, $reponse, $utilisateur_id, $date_reponse]);

        echo "<p>Réponse ajoutée avec succès !</p>";
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
    <title>Répondre à la réclamation - Green.tn</title>
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
        <h2>Répondre à la réclamation</h2>

        <h3>Réclamation : <?php echo htmlspecialchars($reclamation['titre']); ?></h3>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>

        <!-- Formulaire pour répondre à la réclamation -->
        <form action="repondre_reclamation.php?id=<?php echo $reclamation['id']; ?>" method="POST">
            <label for="reponse">Votre réponse :</label>
            <textarea id="reponse" name="reponse" required></textarea><br><br>
            <button type="submit">Répondre</button>
        </form>

        <br><br>

        <h3>Réponses déjà ajoutées :</h3>
        <?php
        // Afficher les réponses existantes
        $stmt = $pdo->prepare("SELECT * FROM reponses WHERE reclamation_id = ?");
        $stmt->execute([$reclamation_id]);
        $reponses = $stmt->fetchAll();

        foreach ($reponses as $reponse) {
            echo "<div class='reponse'>";
            echo "<p><strong>Réponse de l'admin:</strong></p>";
            echo "<p>" . nl2br(htmlspecialchars($reponse['contenu'])) . "</p>";
            echo "<p><i>Réponse donnée le " . $reponse['date_reponse'] . "</i></p>";
            echo "</div><hr>";
        }
        ?>
    </main>

    <!-- Pied de page -->
    <footer>
        <p>&copy; 2025 Green.tn - Tous droits réservés.</p>
    </footer>

</body>
</html>
