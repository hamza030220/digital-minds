<?php
require_once '../../../CONFIG/db.php';

// Require login


// Initialize session for messages if not exists
if (!isset($_SESSION['message'])) {
    $_SESSION['message'] = '';
    $_SESSION['message_type'] = '';
}

// Get station ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = getDBConnection();
    
    // Verify station exists
    $stmt = $pdo->prepare("SELECT name FROM stations WHERE id = ?");
    $stmt->execute([$id]);
    $station = $stmt->fetch();
    
    if (!$station) {
        $_SESSION['message'] = "Station introuvable.";
        $_SESSION['message_type'] = "danger";
        header("Location: list.php");
        exit();
    }
    
    // Check if station is used in any trajet
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM trajets 
        WHERE start_station_id = ? OR end_station_id = ?
    ");
    $stmt->execute([$id, $id]);
    $usageCount = $stmt->fetchColumn();
    
    if ($usageCount > 0) {
        $_SESSION['message'] = "Impossible de supprimer la station car elle est utilisée dans {$usageCount} trajet(s).";
        $_SESSION['message_type'] = "danger";
        header("Location: list.php");
        exit();
    }
    
    // If we get here, we can safely delete the station
    $stmt = $pdo->prepare("DELETE FROM stations WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "La station \"{$station['name']}\" a été supprimée avec succès.";
    $_SESSION['message_type'] = "success";
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['message'] = "Une erreur est survenue lors de la suppression de la station.";
    $_SESSION['message_type'] = "danger";
}

// Redirect back to list
header("Location: list.php");
exit();

