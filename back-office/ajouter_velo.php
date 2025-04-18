<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php'; // Vérifiez bien le chemin


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_velo = $_POST['nom_velo'];
    $type_velo = $_POST['type_velo'];
    $etat_velo = $_POST['etat_velo'];
    $prix_par_jour = $_POST['prix_par_jour'];

    try {
        // Préparation de la requête pour ajouter un vélo
        $query = "INSERT INTO velos (nom_velo, type_velo, etat_velo, prix_par_jour) 
                  VALUES (:nom_velo, :type_velo, :etat_velo, :prix_par_jour)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nom_velo', $nom_velo);
        $stmt->bindParam(':type_velo', $type_velo);
        $stmt->bindParam(':etat_velo', $etat_velo);
        $stmt->bindParam(':prix_par_jour', $prix_par_jour);

        if ($stmt->execute()) {
            echo "<p style='color: green;'>Vélo ajouté avec succès.</p>";
            header("Cache-Control: no-cache, must-revalidate");
            header("Location: consulter_velos.php"); 
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout du vélo.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Vélo</title>
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
    <h1>Ajouter un Vélo</h1>
    <form method="POST">
        <label for="nom_velo">Nom du Vélo :</label>
        <input type="text" name="nom_velo" required>

        <label for="type_velo">Type de Vélo :</label>
        <select name="type_velo" required>
            <option value="VTT">VTT</option>
            <option value="Vélo de route">Vélo de route</option>
            <option value="Vélo électrique">Vélo électrique</option>
            <option value="Vélo pliant">Vélo pliant</option>
        </select>

        <label for="etat_velo">État du Vélo :</label>
        <select name="etat_velo" required>
            <option value="Neuf">NEUF</option>
            <option value="Bon état">Bon état</option>
            <option value="Réparable">Réparable</option>
        </select>

        <label for="prix_par_jour">Prix par Jour (en TND) :</label>
        <input type="number" name="prix_par_jour" min="1" required>

        <button type="submit">Ajouter Vélo</button>
    </form>
</body>
</html>

