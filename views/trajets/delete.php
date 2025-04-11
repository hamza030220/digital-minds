<?php
require_once '../includes/config.php';

// Require login
requireLogin();

// Initialize session for messages if not exists
if (!isset($_SESSION['message'])) {
    $_SESSION['message'] = '';
    $_SESSION['message_type'] = '';
}

// Get trajet ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = getDBConnection();
    
    // Verify trajet exists
    $stmt = $pdo->prepare("
        SELECT t.*, s1.name as start_station_name, s2.name as end_station_name 
        FROM trajets t
        JOIN stations s1 ON t.start_station_id = s1.id
        JOIN stations s2 ON t.end_station_id = s2.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $trajet = $stmt->fetch();
    
    if (!$trajet) {
        $_SESSION['message'] = "Trajet introuvable.";
        $_SESSION['message_type'] = "danger";
        header("Location: list.php");
        exit();
    }
    
    // Delete the trajet
    $stmt = $pdo->prepare("DELETE FROM trajets WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "Le trajet entre \"{$trajet['start_station_name']}\" et \"{$trajet['end_station_name']}\" a été supprimé avec succès.";
    $_SESSION['message_type'] = "success";
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['message'] = "Une erreur est survenue lors de la suppression du trajet.";
    $_SESSION['message_type'] = "danger";
}

// Redirect back to list
header("Location: list.php");
exit();

