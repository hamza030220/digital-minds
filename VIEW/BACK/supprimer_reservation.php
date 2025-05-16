<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

// Load translations
$translations_file = __DIR__ . '/assets/translations.json';
$translations = file_exists($translations_file) ? json_decode(file_get_contents($translations_file), true) : [];
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'fr';

// Function to get translated text
function getTranslation($key, $lang = 'fr', $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_user_not_found', $language, $translations)];
    header("Location: login.php");
    exit();
}

// Check if reservation ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_invalid_reservation', $language, $translations)];
    header("Location: consulter_mes_reservations.php");
    exit();
}

$reservation_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

try {
    // Delete the reservation in one line, ensuring it belongs to the user and is not cancelled
    $stmt = $pdo->prepare("DELETE FROM reservation WHERE id_reservation = ? AND id_client = ? AND statut != 'cancelled'");
    $stmt->execute([$reservation_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        // Update bike availability
        $stmt = $pdo->prepare("UPDATE velos SET disponibilite = 'disponible' WHERE id_velo = (SELECT id_velo FROM reservation WHERE id_reservation = ?)");
        $stmt->execute([$reservation_id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => getTranslation('success_reservation_deleted', $language, $translations)];
    } else {
        $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_reservation_not_found', $language, $translations)];
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la suppression de la réservation: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => getTranslation('error_database', $language, $translations)];
}

header("Location: consulter_mes_reservations.php");
exit();
?>