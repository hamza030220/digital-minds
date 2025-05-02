<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php'; // Vérifiez bien le chemin


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Vérifier si l'ID du vélo est passé dans l'URL
if (isset($_GET['id'])) {
    $id_velo = $_GET['id'];

    // Requête SQL pour supprimer le vélo
    $query = "DELETE FROM velos WHERE id_velo = :id_velo";
    $stmt = $conn->prepare($query);

    // Lier l'ID du vélo
    $stmt->bindParam(':id_velo', $id_velo, PDO::PARAM_INT);

    // Exécution de la requête
    if ($stmt->execute()) {
        echo "Vélo supprimé avec succès.";
        header("Cache-Control: no-cache, must-revalidate");
        header("Location: consulter_velos.php"); // Rediriger vers la page des vélos après suppression
        exit;
    } else {
        echo "Erreur lors de la suppression.";
    }
}
?>

