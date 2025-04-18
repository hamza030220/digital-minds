<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php'; // Vérifiez bien le chemin

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header("Location: dashboard.php");
    exit;
}

// Vérifier si l'ID de la réservation est passé dans l'URL
if (isset($_GET['id'])) {
    $id_reservation = $_GET['id'];

    // Requête SQL pour supprimer la réservation
    $query = "DELETE FROM reservation WHERE id_reservation = :id_reservation";
    $stmt = $conn->prepare($query);

    // Lier l'ID de la réservation
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);

    // Exécution de la requête
    if ($stmt->execute()) {
        echo "Réservation supprimée avec succès.";
        header("Cache-Control: no-cache, must-revalidate");
        header("Location: consulter_reservations.php"); // Rediriger vers la page des réservations après suppression
        exit;
    } else {
        echo "Erreur lors de la suppression.";
    }
}
?>
