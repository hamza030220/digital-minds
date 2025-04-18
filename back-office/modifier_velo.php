<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) && $_SESSION['role'] !=='admin') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_velo = $_GET['id'];
    $query = "SELECT * FROM velos WHERE id_velo = :id_velo";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_velo', $id_velo, PDO::PARAM_INT);
    $stmt->execute();
    $velo = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_velo = $_POST['id_velo'];
    $nom_velo = $_POST['nom_velo'];
    $type_velo = $_POST['type_velo'];
    $etat_velo = $_POST['etat_velo'];
    $prix_par_jour = $_POST['prix_par_jour'];
    $disponibilite = $_POST['disponibilite'];

    // Requête pour mettre à jour les informations du vélo
    $query = "UPDATE velos 
              SET nom_velo = :nom_velo,
                  type_velo = :type_velo,
                  etat_velo = :etat_velo,
                  prix_par_jour = :prix_par_jour,
                  disponibilite = :disponibilite
              WHERE id_velo = :id_velo";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_velo', $id_velo, PDO::PARAM_INT);
    $stmt->bindParam(':nom_velo', $nom_velo, PDO::PARAM_STR);
    $stmt->bindParam(':type_velo', $type_velo, PDO::PARAM_STR);
    $stmt->bindParam(':etat_velo', $etat_velo, PDO::PARAM_STR);
    $stmt->bindParam(':prix_par_jour', $prix_par_jour, PDO::PARAM_INT);
    $stmt->bindParam(':disponibilite', $disponibilite, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo "Vélo modifié avec succès.";
        header("Location: consulter_velos.php");
        exit;
    } else {
        echo "Erreur lors de la modification.";
    }
}
?>

<form method="POST">
    <input type="hidden" name="id_velo" value="<?= $velo['id_velo'] ?>">

    <label for="nom_velo">Nom du Vélo :</label>
    <input type="text" name="nom_velo" value="<?= $velo['nom_velo'] ?>" required>

    <label for="type_velo">Type de Vélo :</label>
    <input type="text" name="type_velo" value="<?= $velo['type_velo'] ?>" required>

    <label for="etat_velo">État du Vélo :</label>
    <select name="etat_velo" required>
        <option value="Disponible" <?= $velo['etat_velo'] == 'Disponible' ? 'selected' : '' ?>>Disponible</option>
        <option value="En réparation" <?= $velo['etat_velo'] == 'En réparation' ? 'selected' : '' ?>>En réparation</option>
        <option value="Indisponible" <?= $velo['etat_velo'] == 'Indisponible' ? 'selected' : '' ?>>Indisponible</option>
    </select>

    <label for="prix_jour">Prix par Jour :</label>
    <input type="number" name="prix_par_jour" value="<?= $velo['prix_par_jour'] ?>" min="1" required>

    <button type="submit">Modifier</button>
</form>
