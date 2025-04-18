<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les réservations
    $query = $pdo->query('SELECT * FROM reservation ORDER BY id_reservation ASC');
    $reservations = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">

<link rel="stylesheet" href="assets/style.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard client </title>
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
            <a href="ajouter_reservation.php" class="btn btn-add">Ajouter une réservation</a>

            <?php if (count($reservations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Réservation</th>
                            <th>ID Client</th>
                            <th>ID Vélo</th>
                            <th>Date Début</th>
                            <th>Date Fin</th>
                            <th>Gouvernorat</th>
                            <th>Téléphone</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservations): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservations['id_reservation']); ?></td>
                                <td><?php echo htmlspecialchars($reservations['id_client']); ?></td>
                                <td><?php echo htmlspecialchars($reservations['id_velo']); ?></td>
                                <td><?php echo htmlspecialchars($reservations['date_debut']); ?></td>
                                <td><?php echo htmlspecialchars($reservations['date_fin']); ?></td>
                                <td><?php echo htmlspecialchars($reservations['gouvernorat']); ?></td>
                                <td><?php echo htmlspecialchars($reservations['telephone']); ?></td>
                                <td>
                                    <a href="modifier_reservation.php?id=<?php echo $reservations['id_reservation']; ?>" class="btn btn-edit">Modifier</a>
                                    <a href="supprimer_reservation.php?id=<?php echo $reservations['id_reservation']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">Supprimer</a>
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
            <p>&copy; <?php echo date("Y"); ?> Gestion des Réservations. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>
