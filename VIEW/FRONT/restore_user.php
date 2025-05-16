<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Vérifier si l'ID de l'utilisateur à restaurer est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?section=trash&error=ID utilisateur invalide");
    exit();
}

$user_id = (int)$_GET['id'];

// Vérifier si l'utilisateur existe dans trash_users
$stmt = $pdo->prepare("SELECT * FROM trash_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: dashboard.php?section=trash&error=Utilisateur introuvable dans la corbeille");
    exit();
}

// Début de la transaction
$pdo->beginTransaction();

try {
    // Restaurer l'utilisateur dans la table users
    $stmt = $pdo->prepare("
        INSERT INTO users (id, nom, prenom, email, telephone, role, age, gouvernorats, cin, photo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    // Utiliser isset pour éviter tout problème avec PHP < 7.0
    $photo = isset($user['photo']) ? $user['photo'] : null;
    $stmt->execute([
        $user['id'],
        $user['nom'],
        $user['prenom'],
        $user['email'],
        $user['telephone'],
        $user['role'],
        $user['age'],
        $user['gouvernorats'],
        $user['cin'],
        $photo
    ]);

    // Supprimer l'utilisateur de la table trash_users
    $stmt = $pdo->prepare("DELETE FROM trash_users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Valider la transaction
    $pdo->commit();

    // Rediriger avec un message de succès
    header("Location: dashboard.php?section=trash&message=Utilisateur restauré avec succès");
    exit();
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    // Rediriger avec un message d'erreur
    header("Location: dashboard.php?section=trash&error=Erreur lors de la restauration : " . urlencode($e->getMessage()));
    exit();
}
?>