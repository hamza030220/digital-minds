<?php
$pdo = new PDO("mysql:host=localhost;dbname=green_tn", "root", "");

$reclamation_id = $_POST['reclamation_id'];
$contenu = $_POST['contenu'];
$role = $_POST['role']; // utilisateur ou admin

$stmt = $pdo->prepare("INSERT INTO reponses (reclamation_id, contenu, role, date_creation) VALUES (?, ?, ?, NOW())");
$stmt->execute([$reclamation_id, $contenu, $role]);

header("Location: voir_reclamation.php?id=$reclamation_id");
exit;
