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

    // Récupérer les vélos
    $query = $pdo->query('SELECT * FROM velos ORDER BY id_velo ASC');
    $velos = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
 <link rel="stylesheet" href="style.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulter les Vélos</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Gestion des vélos</h1>
            <a href="logout.php" class="btn btn-logout">Déconnexion</a>
        </div>
    </header>

    <main class="main-container">
        <div class="container">
            <h2>Liste des vélos</h2>
            <a href="ajouter_velo.php" class="btn btn-add">Ajouter un vélo</a>

            <?php if (count($velos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Prix par jour (TDN)</th>
                            <th>Disponibilité</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($velos as $velo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($velo['id_velo']); ?></td>
                                <td><?php echo htmlspecialchars($velo['nom_velo']); ?></td>
                                <td><?php echo htmlspecialchars($velo['type_velo']); ?></td>
                                <td><?php echo number_format($velo['prix_par_jour'], 2, '.', ''); ?> €</td>
                                <td>
                                    <?php echo $velo['disponibilite'] ? '<span class="status available">Disponible</span>' : '<span class="status unavailable">Non disponible</span>'; ?>
                                </td>
                                <td>
                                    <a href="modifier_velo.php?id=<?php echo $velo['id_velo']; ?>" class="btn btn-edit">Modifier</a>
                                    <a href="supprimer_velo.php?id=<?php echo $velo['id_velo']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce vélo ?');">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucun vélo trouvé.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Gestion des vélos. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>
