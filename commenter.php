<?php
session_start();
include 'connexion.php';

if (!isset($_GET['id'])) {
    echo "ID de réclamation manquant.";
    exit;
}

$reclamation_id = $_GET['id'];

// Récupérer les infos de la réclamation
$stmt = $pdo->prepare("SELECT * FROM reclamations WHERE id = ?");
$stmt->execute([$reclamation_id]);
$reclamation = $stmt->fetch();

if (!$reclamation) {
    echo "Réclamation non trouvée.";
    exit;
}

// Traitement du commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['commentaire'])) {
    $contenu = $_POST['commentaire'];
    $date_commentaire = date('Y-m-d H:i:s');
    $utilisateur_id = $_SESSION['user_id'] ?? null;

    if (!$utilisateur_id) {
        echo "Vous devez être connecté pour commenter.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO commentaires (reclamation_id, utilisateur_id, contenu, date_commentaire)
                               VALUES (?, ?, ?, ?)");
        $stmt->execute([$reclamation_id, $utilisateur_id, $contenu, $date_commentaire]);
        echo "<p>Commentaire ajouté avec succès !</p>";
    }
}

// Récupérer les commentaires
$stmt = $pdo->prepare("SELECT c.*, u.nom AS utilisateur_nom FROM commentaires c 
                       JOIN utilisateurs u ON c.utilisateur_id = u.id 
                       WHERE c.reclamation_id = ?
                       ORDER BY c.date_commentaire DESC");
$stmt->execute([$reclamation_id]);
$commentaires = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Voir / Commenter - Green.tn</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Réclamation : <?php echo htmlspecialchars($reclamation['titre']); ?></h2>
<p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>

<hr>
<h3>Commentaires</h3>
<?php foreach ($commentaires as $commentaire): ?>
    <div class="commentaire">
        <p><strong><?php echo htmlspecialchars($commentaire['utilisateur_nom']); ?></strong> a commenté :</p>
        <p><?php echo nl2br(htmlspecialchars($commentaire['contenu'])); ?></p>
        <p><em><?php echo $commentaire['date_commentaire']; ?></em></p>
        <hr>
    </div>
<?php endforeach; ?>

<!-- Formulaire de commentaire -->
<?php if (isset($_SESSION['user_id'])): ?>
    <form action="" method="POST">
        <label for="commentaire">Votre commentaire :</label><br>
        <textarea name="commentaire" id="commentaire" required></textarea><br><br>
        <button type="submit">Envoyer</button>
    </form>
<?php else: ?>
    <p><a href="login.php">Connectez-vous</a> pour ajouter un commentaire.</p>
<?php endif; ?>
</body>
</html>
