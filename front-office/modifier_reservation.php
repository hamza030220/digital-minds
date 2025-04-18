<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php'; // Vérifiez bien le chemin

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

// Vérifier si l'ID de la réservation est passé
if (isset($_GET['id'])) {
    $id_reservation = $_GET['id'];
    $query = "SELECT * FROM reservation WHERE id_reservation = :id_reservation";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reservation) {    
        die("Réservation introuvable.");
    }
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reservation = $_POST['id_reservation'];
    $id_velo = $_POST['id_velo'];
    $id_client = $_POST['id_client'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $gouvernorat = $_POST['gouvernorat'];
    $telephone = $_POST['telephone'];

    // Requête pour mettre à jour les informations de la réservation
    $query = "UPDATE reservation 
              SET id_velo = :id_velo,
                  id_client = :id_client,
                  date_debut = :date_debut,
                  date_fin = :date_fin,
                  gouvernorat = :gouvernorat,
                  telephone = :telephone
              WHERE id_reservation = :id_reservation";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
    $stmt->bindParam(':id_velo', $id_velo, PDO::PARAM_INT);
    $stmt->bindParam(':id_client', $id_client, PDO::PARAM_INT);
    $stmt->bindParam(':date_debut', $date_debut, PDO::PARAM_STR);
    $stmt->bindParam(':date_fin', $date_fin, PDO::PARAM_STR);
    $stmt->bindParam(':gouvernorat', $gouvernorat, PDO::PARAM_STR);
    $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>Réservation modifiée avec succès.</p>";
        header("Location: consulter_reservations.php");
        exit;
    } else {
        echo "<p style='color: red;'>Erreur lors de la modification de la réservation.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Réservation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: auto;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Modifier une Réservation</h1>
    <form method="POST">
        <input type="hidden" name="id_reservation" value="<?= $reservation['id_reservation'] ?>">

        <label for="id_velo">ID du Vélo :</label>
        <input type="number" name="id_velo" value="<?= $reservation['id_velo'] ?>" required>

        <label for="id_client">ID du Client :</label>
        <input type="number" name="id_client" value="<?= $reservation['id_client'] ?>" required>

        <label for="date_debut">Date de Début :</label>
        <input type="date" name="date_debut" value="<?= $reservation['date_debut'] ?>" required>

        <label for="date_fin">Date de Fin :</label>
        <input type="date" name="date_fin" value="<?= $reservation['date_fin'] ?>" required>

        <label for="gouvernorat">Gouvernorat :</label>
        <input type="text" name="gouvernorat" value="<?= $reservation['gouvernorat'] ?>" required>

        <label for="telephone">Téléphone :</label>
        <input type="tel" name="telephone" pattern="[0-9]{8}" value="<?= $reservation['telephone'] ?>" required>

        <button type="submit">Modifier</button>
    </form>
</body>
</html>
