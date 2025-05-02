<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back-Office</title>
</head>
<body>
    <h1>Back-Office</h1>
    <a href="gerer_velos.php">Gérer les vélos</a><br>
    <a href="gerer_reservations.php">Gérer les réservations</a><br>
    <a href="../logout.php">Se déconnecter</a>
</body>
</html>
