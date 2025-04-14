<?php
// Connexion à la base de données
include 'connexion.php';

// Vérifier si l'ID de la réclamation est passé en paramètre
if (isset($_GET['id'])) {
    $reclamation_id = $_GET['id'];

    // Supprimer la réclamation de la base de données
    $stmt = $pdo->prepare("DELETE FROM reclamations WHERE id = ?");
    $stmt->execute([$reclamation_id]);

    echo "<p>Réclamation supprimée avec succès !</p>";
    echo "<a href='liste_reclamations.php'>Retour à la liste des réclamations</a>";
} else {
    echo "ID de réclamation manquant.";
    exit;
}
?>
