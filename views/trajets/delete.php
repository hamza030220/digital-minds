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
    
    // First verify if trajet exists
    $stmt = $pdo->prepare("SELECT * FROM trajets WHERE id = ?");
    $stmt->execute([$id]);
    $trajet = $stmt->fetch();
    
    if ($trajet) {
        // Then get station names if they exist
        $stmt = $pdo->prepare("SELECT id, name FROM stations WHERE id IN (?, ?)");
        $stmt->execute([$trajet['start_station_id'], $trajet['end_station_id']]);
        $stations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $start_station_name = isset($stations[$trajet['start_station_id']]) ? $stations[$trajet['start_station_id']] : 'Station inconnue';
        $end_station_name = isset($stations[$trajet['end_station_id']]) ? $stations[$trajet['end_station_id']] : 'Station inconnue';
    }
    
    if (!$trajet) {
        $_SESSION['message'] = "Trajet introuvable.";
        $_SESSION['message_type'] = "danger";
        header("Location: list.php");
        exit();
    }
    
    // Delete the trajet
    $stmt = $pdo->prepare("DELETE FROM trajets WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "Le trajet supprimé avec succès.";
    $_SESSION['message_type'] = "success";
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['message'] = "Une erreur est survenue lors de la suppression du trajet.";
    $_SESSION['message_type'] = "danger";
}

// Redirect back to list
header("Location: list.php");
exit();

