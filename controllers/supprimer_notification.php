<?php
// controllers/supprimer_notification.php
// Script to delete a notification

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Include translation helper for error message
require_once __DIR__ . '/../translate.php';

// Authentication Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?error=' . urlencode(t('error_session_required')));
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$notification_id) {
    header('Location: ../notifications.php?delete_status=error');
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Verify that the notification belongs to the user before deleting
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);

    // Check if a row was deleted
    if ($stmt->rowCount() > 0) {
        header('Location: ../views/notifications.php?delete_status=success');
    } else {
        header('Location: ../notifications.php?delete_status=error');
    }
    exit;
} catch (PDOException $e) {
    error_log("Error deleting notification: " . $e->getMessage());
    header('Location: ../notifications.php?delete_status=error');
    exit;
}
?>