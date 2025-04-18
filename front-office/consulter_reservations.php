<?php
session_start();

// Vérifier si l'utilisateur a le rôle 'client'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: index.php');
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les réservations
    $query = '
    SELECT r.*, u.nom AS nom_utilisateur
    FROM reservation r
    INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur
    WHERE r.id_client = :id_client
    ORDER BY r.id_reservation ASC
    ';
    $stmt = $pdo->prepare($query); // Préparer la requête
    $stmt->bindParam(':id_client', $_SESSION['user_id'], PDO::PARAM_INT); // Lier le paramètre
    $stmt->execute(); // Exécuter la requête
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC); // Récupérer les résultats
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulter les Réservations</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Gestion des Réservations</h1>
            <a href="logout.php" class="btn btn-logout">Déconnexion</a>
        </div>
    </header>

    <main class="main-container">
        <div class="container">
            <h2>Liste des Réservations</h2>
            <a href="ajouter_reservation.php" class="btn btn-add">Ajouter une Réservation</a>

            <?php if (count($reservations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Réservation</th>
                            <th>ID Vélo</th>
                            <th>Nom Utilisateur</th>
                            <th>Date Début</th>
                            <th>Date Fin</th>
                            <th>Gouvernorat</th>
                            <th>Téléphone</th>
                            <th>Durée Réservation (jours)</th>
                            <th>Date Réservation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['id_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['id_velo']); ?></td>
                                <td><?= htmlspecialchars($reservation['nom_utilisateur']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_debut']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_fin']); ?></td>
                                <td><?= htmlspecialchars($reservation['gouvernorat']); ?></td>
                                <td><?= htmlspecialchars($reservation['telephone']); ?></td>
                                <td><?= htmlspecialchars($reservation['duree_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_reservation']); ?></td>
                                <td>
                                    <a href="modifier_reservation.php?id=<?= $reservation['id_reservation']; ?>" class="btn btn-edit">Modifier</a>
                                    <a href="supprimer_reservation.php?id=<?= $reservation['id_reservation']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucune réservation trouvée.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date("Y"); ?> Gestion des Réservations. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>
