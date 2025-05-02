<?php
session_start();
require_once __DIR__ . '/models/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: velos.php');
    exit();
}

$velo_id = (int)$_GET['id'];

try {
    // Récupérer les informations du vélo
    $stmt = $pdo->prepare("SELECT * FROM velos WHERE id_velo = ?");
    $stmt->execute([$velo_id]);
    $velo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($velo) {
        // Insérer dans corbeille_velos
        $stmt = $pdo->prepare("INSERT INTO corbeille_velos (id_velo, nom_velo, type_velo, prix_par_jour, disponibilite) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $velo['id_velo'],
            $velo['nom_velo'],
            $velo['type_velo'],
            $velo['prix_par_jour'],
            $velo['disponibilite']
        ]);

        // Supprimer de la table velos
        $stmt = $pdo->prepare("DELETE FROM velos WHERE id_velo = ?");
        $stmt->execute([$velo_id]);
    }

    header('Location: velos.php');
    exit();
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
?>