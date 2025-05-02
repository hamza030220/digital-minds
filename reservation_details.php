<?php
session_start();
require_once __DIR__ . '/models/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: reservations.php');
    exit();
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT r.*, v.nom_velo 
                       FROM reservation r 
                       JOIN velos v ON r.id_velo = v.id_velo 
                       WHERE r.id_reservation = ?");
$stmt->execute([$id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    echo "Réservation introuvable.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de la Réservation - Green.tn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }
        .container {
            max-width: 360px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }
        h1 {
            color: #2e7d32;
            text-align: center;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .details p {
            margin: 10px 0;
            font-size: 14px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            font-size: 14px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #388e3c;
        }
        body.dark-mode {
            background: rgba(50, 50, 50, 0.9);
            color: #e0e0e0;
        }
        body.dark-mode .container {
            background: rgba(50, 50, 50, 0.9);
        }
        body.dark-mode h1 {
            color: #4caf50;
        }
    </style>
    <script>
        // Appliquer le mode sombre si défini dans la fenêtre parent
        if (window.opener && window.opener.document.body.classList.contains('dark-mode')) {
            document.body.classList.add('dark-mode');
        }

        // Traductions
        const translations = {
            fr: {
                reservation_details: "Détails de la Réservation",
                bike: "Vélo",
                client_id: "Client ID",
                start_date: "Date Début",
                end_date: "Date Fin",
                governorate: "Gouvernorat",
                phone: "Téléphone",
                status: "Statut",
                close: "Fermer"
            },
            en: {
                reservation_details: "Reservation Details",
                bike: "Bike",
                client_id: "Client ID",
                start_date: "Start Date",
                end_date: "End Date",
                governorate: "Governorate",
                phone: "Phone",
                status: "Status",
                close: "Close"
            }
        };

        const currentLanguage = window.opener ? (window.opener.localStorage.getItem('language') || 'fr') : 'fr';
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector('h1').textContent = `${translations[currentLanguage].reservation_details} #<?php echo htmlspecialchars($reservation['id_reservation']); ?>`;
            document.querySelectorAll('[data-translate]').forEach(element => {
                const key = element.getAttribute('data-translate');
                if (translations[currentLanguage][key]) {
                    element.textContent = translations[currentLanguage][key] + ' :';
                }
            });
            document.querySelector('.btn').innerHTML = `${translations[currentLanguage].close} <i class="bi bi-x"></i>`;
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Détails de la Réservation #<?php echo htmlspecialchars($reservation['id_reservation']); ?></h1>
        <div class="details">
            <p><strong data-translate="bike">Vélo :</strong> <?php echo htmlspecialchars($reservation['nom_velo']); ?></p>
            <p><strong data-translate="client_id">Client ID :</strong> <?php echo htmlspecialchars($reservation['id_client']); ?></p>
            <p><strong data-translate="start_date">Date Début :</strong> <?php echo htmlspecialchars($reservation['date_debut']); ?></p>
            <p><strong data-translate="end_date">Date Fin :</strong> <?php echo htmlspecialchars($reservation['date_fin']); ?></p>
            <p><strong data-translate="governorate">Gouvernorat :</strong> <?php echo htmlspecialchars(isset($reservation['gouvernorat']) ? $reservation['gouvernorat'] : 'Non spécifié'); ?></p>
            <p><strong data-translate="phone">Téléphone :</strong> <?php echo htmlspecialchars(isset($reservation['telephone']) ? $reservation['telephone'] : 'Non spécifié'); ?></p>
            <p><strong data-translate="status">Statut :</strong> <?php echo htmlspecialchars(ucfirst($reservation['statut'])); ?></p>
        </div>
        <button class="btn" onclick="window.close()">Fermer <i class="bi bi-x"></i></button>
    </div>
</body>
</html>