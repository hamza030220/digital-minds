<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $stmt = $pdo->prepare("SELECT nom, prenom, email, telephone, age, gouvernorats, cin, photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
}
?>