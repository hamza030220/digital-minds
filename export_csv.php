<?php
// Inclure la connexion à la base de données
require 'db.php';

// Définir les en-têtes du fichier CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="export.csv"');

// Ouvrir la sortie pour écrire dans le fichier CSV
$output = fopen('php://output', 'w');

// Ajouter les en-têtes des colonnes
fputcsv($output, ['Nom', 'Prénom', 'Email', 'Téléphone', 'Rôle']);

// Récupérer les utilisateurs depuis la base de données
$stmt = $pdo->prepare("SELECT nom, prenom, email, telephone, role FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajouter les lignes de données dans le CSV
foreach ($users as $user) {
    fputcsv($output, $user);
}

// Fermer le fichier CSV
fclose($output);
?>
