<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php'; // Vérifiez bien le chemin

// Afficher les erreurs pour déboguer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

// Vérifier si une requête AJAX est envoyée pour valider les dates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_dates') {
    $dateDebut = $_POST['date_debut'];
    $dateFin = $_POST['date_fin'];

    try {
        $query = "
            SELECT v.id_velo, v.nom_velo
            FROM velos v
            LEFT JOIN reservation r
            ON v.id_velo = r.id_velo
            WHERE v.disponibilite >= 1
            AND (
                (r.date_debut NOT BETWEEN :dateDebut AND :dateFin) AND 
                (r.date_fin NOT BETWEEN :dateDebut AND :dateFin)
            )
        ";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':dateDebut', $dateDebut);
        $stmt->bindParam(':dateFin', $dateFin);
        $stmt->execute();
        $velosDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($velosDisponibles);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => "Erreur lors de la récupération des vélos : " . $e->getMessage()]);
        exit;
    }
}

// Traitement du formulaire pour ajouter une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_reservation') {
    $id_velo = $_POST['id_velo'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $gouvernorat = $_POST['gouvernorat'];
    $telephone = $_POST['telephone'];

    $duree_reservation = (strtotime($date_fin) - strtotime($date_debut)) / (60 * 60 * 24);

    try {
        $query = "
            INSERT INTO reservation (
                id_client, id_velo, date_debut, date_fin, gouvernorat, telephone, duree_reservation, date_reservation
            ) VALUES (
                :id_client, :id_velo, :date_debut, :date_fin, :gouvernorat, :telephone, :duree_reservation, NOW()
            )
        ";
        $stmt = $conn->prepare($query);

        $stmt->bindParam(':id_client', $_SESSION['user_id']);
        $stmt->bindParam(':id_velo', $id_velo);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
        $stmt->bindParam(':gouvernorat', $gouvernorat);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':duree_reservation', $duree_reservation);

        if ($stmt->execute()) {
            echo "<p style='color: green;'>Réservation ajoutée avec succès.</p>";
            header("Location: consulter_reservations.php");
            exit;
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout de la réservation.</p>";
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
    <title>Ajouter une Réservation</title>
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
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <h1>Ajouter une Réservation</h1>
    <form method="POST" id="reservation-form">
        <input type="hidden" name="action" value="add_reservation">

        <label for="date_debut">Date Début :</label>
        <input type="date" id="date_debut" name="date_debut" required>

        <label for="date_fin">Date Fin :</label>
        <input type="date" id="date_fin" name="date_fin" required>

        <div id="extra-fields" class="hidden">
            <label for="id_velo">Vélo Disponible :</label>
            <select name="id_velo" id="id_velo" required>
                <option value="" disabled selected>-- Sélectionnez un vélo --</option>
            </select>

            <label for="gouvernorat">Gouvernorat :</label>
            <select name="gouvernorat" required>
                <option value="Tunis">Tunis</option>
                <option value="Sfax">Sfax</option>
                <option value="Sousse">Sousse</option>
                <option value="Gabès">Gabès</option>
            </select>

            <label for="telephone">Téléphone :</label>
            <input type="text" name="telephone" pattern="\d{8}" title="Veuillez entrer un numéro de téléphone à 8 chiffres." required>
        </div>

        <button type="button" id="validate-dates">Valider les Dates</button>
        <button type="submit" id="submit-btn" class="hidden">Ajouter Réservation</button>
    </form>

    <script>
        document.getElementById('validate-dates').addEventListener('click', function() {
            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = document.getElementById('date_fin').value;

            if (dateDebut && dateFin && new Date(dateDebut) <= new Date(dateFin)) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=check_dates&date_debut=${dateDebut}&date_fin=${dateFin}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        const veloSelect = document.getElementById('id_velo');
                        veloSelect.innerHTML = '<option value="" disabled selected>-- Sélectionnez un vélo --</option>';
                        data.forEach(velo => {
                            const option = document.createElement('option');
                            option.value = velo.id_velo;
                            option.textContent = velo.nom_velo;
                            veloSelect.appendChild(option);
                        });
                        document.getElementById('extra-fields').classList.remove('hidden');
                        document.getElementById('submit-btn').classList.remove('hidden');
                        this.classList.add('hidden');
                    }
                });
            } else {
                alert('Veuillez sélectionner des dates valides.');
            }
        });
    </script>
</body>
</html>
