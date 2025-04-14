<?php
$pdo = new PDO("mysql:host=localhost;dbname=green_tn", "root", "");

$reclamation_id = $_POST['reclamation_id'];
$statut = $_POST['statut'];

$stmt = $pdo->prepare("UPDATE reclamations SET statut = ? WHERE id = ?");
$stmt->execute([$statut, $reclamation_id]);

header("Location: voir_reclamation.php?id=$reclamation_id");
exit;
