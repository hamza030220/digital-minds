<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?section=trash&error=ID utilisateur invalide");
    exit();
}

$user_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM trash_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: dashboard.php?section=trash&error=Utilisateur introuvable dans la corbeille");
    exit();
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("DELETE FROM trash_users WHERE id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();
    header("Location: dashboard.php?section=trash&message=Utilisateur supprimé définitivement avec succès");
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: dashboard.php?section=trash&error=Erreur lors de la suppression définitive : " . urlencode($e->getMessage()));
    exit();
}
?>